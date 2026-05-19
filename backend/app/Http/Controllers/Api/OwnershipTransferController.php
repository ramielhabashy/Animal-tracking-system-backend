<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\Animal;
use App\Models\Notification;
use App\Models\OwnershipHistory;
use App\Models\OwnershipTransfer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnershipTransferController extends Controller
{
    use OwnableAuthorization;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 15), 50);
        $status = $request->status;
        $type = $request->type;

        $query = OwnershipTransfer::with([
            'fromUser:id,name,email',
            'toUser:id,name,email',
            'animals:id,animal_id,name,species',
        ])->orderByDesc('created_at');

        if ($user->hasRole('Admin')) {
            // Admin sees all
        } else {
            $query->forUser($user->id);
        }

        if ($status) {
            $statuses = explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        if ($type === 'sent') {
            $query->where('from_user_id', $user->id);
        } elseif ($type === 'received') {
            $query->where('to_user_id', $user->id);
        }

        $transfers = $query->paginate($perPage);

        return response()->json([
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('Admin') && !$user->hasRole('Owner')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'animal_ids' => 'required_without:group_id|array',
            'animal_ids.*' => 'integer|exists:animals,id',
            'group_id' => 'required_without:animal_ids|integer|exists:animal_groups,id',
            'to_user_id' => 'required|integer|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'agreed_price' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $toUser = User::findOrFail($validated['to_user_id']);

        if (!$toUser->hasRole('Admin') && !$toUser->hasRole('Owner')) {
            return response()->json(['message' => 'Target user must be Admin or Owner'], 422);
        }

        if ((int) $toUser->id === (int) $user->id) {
            return response()->json(['message' => 'Cannot transfer to yourself'], 422);
        }

        // Resolve animals
        $animalIds = $validated['animal_ids'] ?? [];
        if (!empty($validated['group_id'])) {
            $groupAnimals = Animal::whereHas('groups', function ($q) use ($validated) {
                $q->where('animal_groups.id', $validated['group_id']);
            })->pluck('id')->toArray();
            $animalIds = array_unique(array_merge($animalIds, $groupAnimals));
        }

        if (empty($animalIds)) {
            return response()->json(['message' => 'No animals to transfer'], 422);
        }

        // Verify sender owns all animals
        $animals = Animal::whereIn('id', $animalIds)->get();
        foreach ($animals as $animal) {
            if (!$this->canModifyOwner($request, $animal->owner_id)) {
                return response()->json([
                    'message' => "You cannot transfer animal {$animal->animal_id}",
                ], 403);
            }
        }

        // Verify to_user can manage the target animals count
        $tier = $toUser->subscriptionTier;
        $currentCount = Animal::where('owner_id', $toUser->id)->count();
        $maxAnimals = $tier ? ($tier->max_animals ?: PHP_INT_MAX) : PHP_INT_MAX;
        if (($currentCount + count($animalIds)) > $maxAnimals) {
            return response()->json([
                'message' => "Target user cannot accept {$toUser->name} animals (limit: {$maxAnimals})",
            ], 422);
        }

        $commissionEnabled = Setting::getBoolean('transfer_commission_enabled', false);
        $commissionPercentage = 0;
        $commissionAmount = 0;

        if ($commissionEnabled && !empty($validated['agreed_price'])) {
            $commissionType = Setting::get('transfer_commission_type', 'percentage');
            $commissionPercentage = (float) Setting::get('transfer_commission_percentage', 5);
            if ($commissionType === 'percentage') {
                $commissionAmount = round($validated['agreed_price'] * $commissionPercentage / 100, 2);
            } else {
                $commissionAmount = (float) Setting::get('transfer_commission_fixed', 0);
            }
        }

        $expiresAt = $validated['expires_at'] ?? now()->addDays(7);

        DB::beginTransaction();
        try {
            $transfer = OwnershipTransfer::create([
                'from_user_id' => $user->id,
                'to_user_id' => $toUser->id,
                'status' => 'pending',
                'transfer_type' => 'manual',
                'notes' => $validated['notes'] ?? null,
                'agreed_price' => $validated['agreed_price'] ?? null,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'expires_at' => $expiresAt,
            ]);

            $transfer->animals()->attach($animalIds);

            // Create notification for receiver
            Notification::create([
                'user_id' => $toUser->id,
                'type' => 'ownership_transfer_pending',
                'title' => 'Transfer Request',
                'body' => "{$user->name} wants to transfer " . count($animalIds) . " animal(s) to you.",
                'data' => [
                    'transfer_id' => $transfer->id,
                    'from_user_id' => $user->id,
                    'from_user_name' => $user->name,
                    'animal_count' => count($animalIds),
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create transfer', 'error' => $e->getMessage()], 500);
        }

        $transfer->load(['fromUser:id,name,email', 'toUser:id,name,email', 'animals:id,animal_id,name,species']);

        return response()->json(['data' => $transfer, 'message' => 'Transfer request created'], 201);
    }

    public function show(Request $request, OwnershipTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('Admin') && $transfer->from_user_id !== $user->id && $transfer->to_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transfer->load([
            'fromUser:id,name,email,phone',
            'toUser:id,name,email,phone',
            'animals:id,animal_id,name,species,breed,gender,owner_id',
            'animals.owner:id,name',
            'historyEntries' => function ($q) {
                $q->orderByDesc('created_at');
            },
        ]);

        return response()->json(['data' => $transfer]);
    }

    public function accept(Request $request, OwnershipTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('Admin') && $transfer->to_user_id !== $user->id) {
            return response()->json(['message' => 'Only the receiver can accept this transfer'], 403);
        }

        if (!$transfer->isPending()) {
            return response()->json(['message' => 'Transfer is no longer pending'], 422);
        }

        if ($transfer->expires_at && $transfer->expires_at->isPast()) {
            $transfer->update(['status' => 'expired']);
            return response()->json(['message' => 'Transfer has expired'], 422);
        }

        $transfer->load('animals');

        DB::beginTransaction();
        try {
            $transfer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $now = now();
            $historyData = [];
            foreach ($transfer->animals as $animal) {
                $oldOwnerId = $animal->owner_id;
                $animal->update(['owner_id' => $transfer->to_user_id]);

                $historyData[] = [
                    'animal_id' => $animal->id,
                    'from_user_id' => $oldOwnerId,
                    'to_user_id' => $transfer->to_user_id,
                    'transfer_id' => $transfer->id,
                    'transfer_type' => $transfer->transfer_type,
                    'reference_type' => $transfer->reference_type,
                    'reference_id' => $transfer->reference_id,
                    'commission_amount' => $transfer->commission_amount,
                    'agreed_price' => $transfer->agreed_price,
                    'created_at' => $now,
                ];
            }

            OwnershipHistory::insert($historyData);

            // Auto-complete if no commission
            if ($transfer->commission_amount <= 0) {
                $transfer->update([
                    'status' => 'completed',
                    'completed_at' => $now,
                ]);
            }

            // Notify sender
            Notification::create([
                'user_id' => $transfer->from_user_id,
                'type' => 'ownership_transfer_accepted',
                'title' => 'Transfer Accepted',
                'body' => "{$user->name} accepted your transfer of {$transfer->animals->count()} animal(s).",
                'data' => [
                    'transfer_id' => $transfer->id,
                    'accepted_by' => $user->id,
                    'animal_count' => $transfer->animals->count(),
                    'auto_completed' => $transfer->commission_amount <= 0,
                ],
            ]);

            // Notify admin if commission pending
            if ($transfer->commission_amount > 0) {
                $adminUsers = User::role('Admin')->get();
                foreach ($adminUsers as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'ownership_transfer_commission_due',
                        'title' => 'Commission Pending',
                        'body' => "Transfer #{$transfer->id} has SAR {$transfer->commission_amount} commission pending.",
                        'data' => [
                            'transfer_id' => $transfer->id,
                            'commission_amount' => $transfer->commission_amount,
                        ],
                    ]);
                }
            }

            if ($transfer->status === 'completed') {
                Notification::create([
                    'user_id' => $transfer->to_user_id,
                    'type' => 'ownership_transfer_completed',
                    'title' => 'Transfer Completed',
                    'body' => 'Transfer of ' . $transfer->animals->count() . ' animal(s) has been completed.',
                    'data' => [
                        'transfer_id' => $transfer->id,
                        'animal_count' => $transfer->animals->count(),
                    ],
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to accept transfer', 'error' => $e->getMessage()], 500);
        }

        $transfer->load(['fromUser:id,name,email', 'toUser:id,name,email', 'animals:id,animal_id,name,species']);

        $message = $transfer->status === 'completed'
            ? 'Transfer accepted and completed'
            : 'Transfer accepted, commission pending';

        return response()->json(['data' => $transfer, 'message' => $message]);
    }

    public function reject(Request $request, OwnershipTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('Admin') && $transfer->to_user_id !== $user->id) {
            return response()->json(['message' => 'Only the receiver can reject this transfer'], 403);
        }

        if (!$transfer->isPending()) {
            return response()->json(['message' => 'Transfer is no longer pending'], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $transfer->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        Notification::create([
            'user_id' => $transfer->from_user_id,
            'type' => 'ownership_transfer_rejected',
            'title' => 'Transfer Rejected',
            'body' => $validated['rejection_reason']
                ? "{$user->name} rejected your transfer: {$validated['rejection_reason']}"
                : "{$user->name} rejected your transfer.",
            'data' => [
                'transfer_id' => $transfer->id,
                'rejected_by' => $user->id,
                'rejection_reason' => $validated['rejection_reason'],
            ],
        ]);

        return response()->json(['data' => $transfer, 'message' => 'Transfer rejected']);
    }

    public function cancel(Request $request, OwnershipTransfer $transfer): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasRole('Admin') && $transfer->from_user_id !== $user->id) {
            return response()->json(['message' => 'Only the sender can cancel this transfer'], 403);
        }

        if (!in_array($transfer->status, ['pending', 'accepted'])) {
            return response()->json(['message' => 'Transfer cannot be cancelled in its current state'], 422);
        }

        $transfer->update(['status' => 'cancelled']);

        Notification::create([
            'user_id' => $transfer->to_user_id,
            'type' => 'ownership_transfer_cancelled',
            'title' => 'Transfer Cancelled',
            'body' => "{$user->name} cancelled the transfer.",
            'data' => [
                'transfer_id' => $transfer->id,
                'cancelled_by' => $user->id,
            ],
        ]);

        return response()->json(['data' => $transfer, 'message' => 'Transfer cancelled']);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 20), 50);
        $animalId = $request->animal_id;

        $query = OwnershipHistory::with([
            'animal:id,animal_id,name,species',
            'fromUser:id,name,email',
            'toUser:id,name,email',
        ])->orderByDesc('created_at');

        if (!$user->hasRole('Admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id)
                    ->orWhereHas('animal', function ($aq) use ($user) {
                        $aq->where('owner_id', $user->id);
                    });
            });
        }

        if ($animalId) {
            $query->where('animal_id', $animalId);
        }

        $results = $query->paginate($perPage);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $baseQuery = OwnershipTransfer::query();
        if (!$user->hasRole('Admin')) {
            $baseQuery->forUser($user->id);
        }

        $pendingCount = (clone $baseQuery)->where('status', 'pending')->count();
        $completedCount = (clone $baseQuery)->where('status', 'completed')->count();
        $totalCommission = (clone $baseQuery)->where('status', 'completed')->sum('commission_amount');
        $paidCommission = (clone $baseQuery)->where('status', 'completed')->where('commission_paid', true)->sum('commission_amount');

        return response()->json([
            'data' => [
                'pending_count' => $pendingCount,
                'completed_count' => $completedCount,
                'total_commission' => $totalCommission,
                'paid_commission' => $paidCommission,
                'pending_commission' => $totalCommission - $paidCommission,
            ],
        ]);
    }

    // ---- Admin endpoints ----

    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $status = $request->status;

        $query = OwnershipTransfer::with([
            'fromUser:id,name,email',
            'toUser:id,name,email',
            'animals:id,animal_id,name,species',
        ])->orderByDesc('created_at');

        if ($status) {
            $query->whereIn('status', explode(',', $status));
        }

        $transfers = $query->paginate($perPage);

        return response()->json([
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function adminUpdateCommission(Request $request, OwnershipTransfer $transfer): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'commission_paid' => 'boolean',
            'commission_amount' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['commission_amount'])) {
            $transfer->commission_amount = $validated['commission_amount'];
        }
        if (isset($validated['commission_paid'])) {
            $transfer->commission_paid = $validated['commission_paid'];
        }

        // If commission paid and status is accepted, complete the transfer
        if ($transfer->commission_paid && $transfer->status === 'accepted') {
            $transfer->status = 'completed';
            $transfer->completed_at = now();
        }

        $transfer->save();

        return response()->json(['data' => $transfer, 'message' => 'Commission updated']);
    }

    public function adminCommissionStats(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $totalCommission = OwnershipTransfer::where('status', 'completed')->sum('commission_amount');
        $paidCommission = OwnershipTransfer::where('status', 'completed')->where('commission_paid', true)->sum('commission_amount');

        $monthlyStats = OwnershipTransfer::where('status', 'completed')
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as month")
            ->selectRaw('SUM(commission_amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => [
                'total_commission' => $totalCommission,
                'paid_commission' => $paidCommission,
                'pending_commission' => $totalCommission - $paidCommission,
                'total_transfers' => OwnershipTransfer::where('status', 'completed')->count(),
                'monthly' => $monthlyStats,
            ],
        ]);
    }

    // ---- Legacy backward-compatible single-transfer endpoint ----

    public function legacyTransfer(Request $request, Animal $animal): JsonResponse
    {
        $user = $request->user();

        if (!$this->canModifyOwner($request, $animal->owner_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'new_owner_id' => 'required|integer|exists:users,id',
        ]);

        $newOwner = User::findOrFail($validated['new_owner_id']);
        if (!$newOwner->hasRole('Admin') && !$newOwner->hasRole('Owner')) {
            return response()->json(['message' => 'New owner must be Admin or Owner'], 422);
        }

        $oldOwnerId = $animal->owner_id;

        DB::beginTransaction();
        try {
            // Create transfer record
            $transfer = OwnershipTransfer::create([
                'from_user_id' => $oldOwnerId ?? $user->id,
                'to_user_id' => $newOwner->id,
                'status' => 'completed',
                'transfer_type' => 'manual',
                'commission_amount' => 0,
                'commission_percentage' => 0,
                'completed_at' => now(),
            ]);

            $transfer->animals()->attach($animal->id);

            // Update ownership
            $animal->update(['owner_id' => $newOwner->id]);

            // Log history
            OwnershipHistory::create([
                'animal_id' => $animal->id,
                'from_user_id' => $oldOwnerId,
                'to_user_id' => $newOwner->id,
                'transfer_id' => $transfer->id,
                'transfer_type' => 'manual',
                'created_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transfer failed', 'error' => $e->getMessage()], 500);
        }

        $animal->load(['owner', 'device']);

        return response()->json([
            'data' => $animal,
            'message' => 'Ownership transferred successfully',
        ]);
    }
}
