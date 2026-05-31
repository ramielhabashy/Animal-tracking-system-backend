<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->generateTimeBasedNotifications($user);

        $query = Notification::where('user_id', $user->id);

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->generateTimeBasedNotifications($user);

        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $user = $request->user();
        if (!$user || $notification->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    private function generateTimeBasedNotifications($user): void
    {
        if ($user->hasRole('Admin')) {
            $this->checkSubscriptionExpiry($user);
        }
    }

    private function checkSubscriptionExpiry($admin): void
    {
        $expiringSoon = UserSubscription::with('user:id,name')
            ->where('status', 'active')
            ->where('ends_at', '<=', now()->addDays(7))
            ->where('ends_at', '>', now())
            ->get();

        foreach ($expiringSoon as $sub) {
            $exists = Notification::where('user_id', $admin->id)
                ->where('type', 'subscription_expiring')
                ->where('data->subscription_id', $sub->id)
                ->whereNull('read_at')
                ->exists();

            if (!$exists) {
                $daysLeft = now()->diffInDays($sub->ends_at);
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'subscription_expiring',
                    'title' => 'Subscription Expiring Soon',
                    'body' => ($sub->user?->name ?? 'A user') . "'s subscription expires in {$daysLeft} day(s)",
                    'data' => [
                        'subscription_id' => $sub->id,
                        'user_id' => $sub->user_id,
                        'ends_at' => $sub->ends_at?->toDateString(),
                        'days_left' => $daysLeft,
                    ],
                ]);
            }
        }
    }
}
