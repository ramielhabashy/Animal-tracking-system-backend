<?php

namespace App\Http\Controllers\Api\Health;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\SendsEmailNotifications;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Traits\OwnableAuthorization;

class MedicalRecordController extends Controller
{
    use OwnableAuthorization, SendsEmailNotifications;

    private function getAnimalIdsForUser(Request $request): array
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return [0];
        }

        if ($user->hasRole('Admin')) {
            return [];
        }

        if ($user->hasRole('Owner')) {
            return Animal::where('owner_id', $user->id)->pluck('id')->toArray();
        }

        if ($user->hasRole('Manager')) {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUsers[] = $user->id;
            return Animal::whereIn('owner_id', $managedUsers)->pluck('id')->toArray();
        }

        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return Animal::where('owner_id', $user->managed_by)->pluck('id')->toArray();
            }
            return [0];
        }

        return Animal::where('owner_id', $user->id)->pluck('id')->toArray();
    }

    private function canAccessRecord(Request $request, MedicalRecord $record): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        $animal = $record->animal;
        if (!$animal) {
            return false;
        }

        if ($user->hasRole('Owner')) {
            return (int) $animal->owner_id === (int) $user->id;
        }

        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return (int) $animal->owner_id === (int) $user->managed_by;
            }
            return false;
        }

        if ($user->hasRole('Manager')) {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUsers[] = $user->id;
            return in_array((int) $animal->owner_id, array_map('intval', $managedUsers));
        }

        return (int) $animal->owner_id === (int) $user->id;
    }

    private function canManageAnimal(Request $request, Animal $animal): bool
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Owner')) {
            return (int) $animal->owner_id === (int) $user->id;
        }

        if ($user->hasRole('Doctor')) {
            if ($user->managed_by) {
                return (int) $animal->owner_id === (int) $user->managed_by;
            }
            return false;
        }

        if ($user->hasRole('Manager')) {
            $managedUsers = User::where('managed_by', $user->id)->pluck('id')->toArray();
            $managedUsers[] = $user->id;
            return in_array((int) $animal->owner_id, array_map('intval', $managedUsers));
        }

        return false;
    }

    public function index(Request $request): JsonResponse
    {
        $userRole = $this->getUserRole($request);

        $query = MedicalRecord::with(['animal', 'attachments']);

        if ($userRole !== 'Admin') {
            $animalIds = $this->getAnimalIdsForUser($request);
            $query->whereIn('animal_id', $animalIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('veterinarian', 'like', "%{$search}%")
                  ->orWhere('medication', 'like', "%{$search}%")
                  ->orWhere('record_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('record_type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('health_status') && $request->health_status !== 'all') {
            $query->where('health_status', $request->health_status);
        }

        if ($request->filled('animal_id') && $request->animal_id !== 'all') {
            $query->where('animal_id', $request->animal_id);
        }

        if ($request->filled('date_from')) {
            $query->where('record_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('record_date', '<=', $request->date_to);
        }

        $records = $query->orderBy('record_date', 'desc')->paginate(20);

        return response()->json($records);
    }

    public function show(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        if (!$this->canAccessRecord($request, $medicalRecord)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $medicalRecord->load(['animal', 'attachments']);

        return response()->json(['data' => $medicalRecord]);
    }

    public function store(Request $request): JsonResponse
    {
        $userRole = $this->getUserRole($request);
        $userId = $this->getUserId($request);

        if (!in_array($userRole, ['Admin', 'Owner', 'Manager', 'Doctor'])) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'record_type' => 'required|in:vaccination,checkup,surgery,treatment,emergency',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'record_date' => 'required|date',
            'veterinarian' => 'nullable|string|max:255',
            'medication' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'health_status' => 'nullable|in:stable,recovering,critical,deceased',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'next_follow_up' => 'nullable|date|after:record_date',
        ]);

        $animal = Animal::find($validated['animal_id']);

        if (!$this->canManageAnimal($request, $animal)) {
            return response()->json(['message' => 'Unauthorized to add record for this animal', 'error' => 'unauthorized'], 403);
        }

        $recordData = $validated;
        unset($recordData['attachments']);
        $recordData['owner_id'] = $userId;
        $recordData['status'] = $recordData['status'] ?? 'completed';

        $record = MedicalRecord::create($recordData);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('medical-records', 'public');
                $record->attachments()->create([
                    'file_path' => '/storage/' . $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $ownerId = $animal->owner_id;
        if ($ownerId && (int) $ownerId !== (int) $userId) {
            \App\Models\Notification::create([
                'user_id' => $ownerId,
                'type' => 'medical_record_added',
                'title' => 'New Medical Record Added',
                'body' => 'A ' . ($recordData['record_type'] ?? 'medical') . ' record was added for ' . ($animal->name ?? 'your animal'),
                'data' => [
                    'record_id' => $record->id,
                    'animal_id' => $animal->id,
                    'animal_name' => $animal->name,
                    'record_type' => $recordData['record_type'] ?? 'checkup',
                    'record_date' => $recordData['record_date'] ?? null,
                    'link' => '/medical-records',
                ],
            ]);

            $owner = User::find($ownerId);
            if ($owner) {
                $animalName = $animal->name ?? 'Your Animal';
                $recordType = $recordData['record_type'] ?? 'medical';
                $description = $recordData['description'] ?? '';
                $this->sendNotificationMail(
                    $owner,
                    'medical',
                    "New Medical Record – {$animalName}",
                    [
                        "A {$recordType} record was added for {$animalName}.",
                        $description ? "Details: {$description}" : '',
                    ],
                    rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/medical-records',
                    'View Records',
                );
            }
        }

        return response()->json([
            'message' => 'Medical record created successfully',
            'data' => $record->load(['animal', 'attachments']),
        ], 201);
    }

    public function update(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        if (!$this->canAccessRecord($request, $medicalRecord)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'record_type' => 'nullable|in:vaccination,checkup,surgery,treatment,emergency',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'record_date' => 'nullable|date',
            'veterinarian' => 'nullable|string|max:255',
            'medication' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'health_status' => 'nullable|in:stable,recovering,critical,deceased',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'delete_attachment_ids' => 'nullable|array',
            'delete_attachment_ids.*' => 'integer|exists:medical_record_attachments,id',
            'next_follow_up' => 'nullable|date',
        ]);

        if ($request->has('delete_attachment_ids')) {
            $attachments = MedicalRecordAttachment::whereIn('id', $request->delete_attachment_ids)
                ->where('medical_record_id', $medicalRecord->id)
                ->get();
            foreach ($attachments as $attachment) {
                $filePath = str_replace('/storage/', '', $attachment->file_path);
                Storage::disk('public')->delete($filePath);
                $attachment->delete();
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('medical-records', 'public');
                $medicalRecord->attachments()->create([
                    'file_path' => '/storage/' . $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $medicalRecord->update($validated);

        return response()->json([
            'message' => 'Medical record updated successfully',
            'data' => $medicalRecord->load(['animal', 'attachments']),
        ]);
    }

    public function destroy(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        if (!$this->canAccessRecord($request, $medicalRecord)) {
            return response()->json(['message' => 'Unauthorized', 'error' => 'unauthorized'], 403);
        }

        foreach ($medicalRecord->attachments as $attachment) {
            $filePath = str_replace('/storage/', '', $attachment->file_path);
            Storage::disk('public')->delete($filePath);
        }

        $medicalRecord->delete();

        return response()->json(['message' => 'Medical record deleted successfully']);
    }

    public function stats(Request $request): JsonResponse
    {
        $userRole = $this->getUserRole($request);

        $query = MedicalRecord::query();

        if ($userRole !== 'Admin') {
            $animalIds = $this->getAnimalIdsForUser($request);
            $query->whereIn('animal_id', $animalIds);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'vaccinations' => (clone $query)->where('record_type', 'vaccination')->count(),
            'checkups' => (clone $query)->where('record_type', 'checkup')->count(),
            'surgeries' => (clone $query)->where('record_type', 'surgery')->count(),
            'treatments' => (clone $query)->where('record_type', 'treatment')->count(),
            'emergencies' => (clone $query)->where('record_type', 'emergency')->count(),
            'scheduled' => (clone $query)->where('status', 'scheduled')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
