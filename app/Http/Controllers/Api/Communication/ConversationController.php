<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    use ApiResponse, OwnableAuthorization;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        $conversations = Conversation::with([
                'participants',
                'messages' => fn ($q) => $q->latest()->take(1),
                'createdBy',
            ])
            ->forUser($user)
            ->latest()
            ->paginate(20);

        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            $conversation->unread_count = $conversation->unreadCountFor($user->id);
            return $conversation;
        });

        $unreadTotal = Conversation::forUser($user)
            ->get()
            ->sum(fn ($c) => $c->unreadCountFor($user->id));

        return $this->paginated($conversations, '')
            ->setData([
                'data' => $conversations->items(),
                'meta' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'total' => $conversations->total(),
                    'unread_total' => $unreadTotal,
                ],
            ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:255',
            'type' => 'required|in:direct,group,ticket',
            'body' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:users,id',
            'linkable_type' => 'nullable|string',
            'linkable_id' => 'nullable|integer',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        $participantIds = array_unique(array_merge($data['participant_ids'], [$user->id]));

        $conversation = Conversation::create([
            'subject' => $data['subject'] ?? null,
            'type' => $data['type'],
            'status' => 'active',
            'priority' => $data['priority'] ?? null,
            'created_by_id' => $user->id,
            'assigned_to_id' => ($data['type'] === 'ticket' && isset($data['participant_ids'][0]))
                ? $data['participant_ids'][0]
                : null,
            'linkable_type' => $data['linkable_type'] ?? null,
            'linkable_id' => $data['linkable_id'] ?? null,
        ]);

        $conversation->participants()->attach($participantIds);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        if ($conversation->type === 'ticket' && $conversation->assigned_to_id) {
            $assigned = User::find($conversation->assigned_to_id);
            if ($assigned && $assigned->id !== $user->id) {
                Notification::create([
                    'user_id' => $assigned->id,
                    'type' => 'new_ticket',
                    'title' => 'New ticket: ' . ($conversation->subject ?? 'No subject'),
                    'body' => Str::limit($message->body, 100),
                    'data' => [
                        'conversation_id' => $conversation->id,
                        'message_id' => $message->id,
                        'link' => '/messages/' . $conversation->id,
                    ],
                ]);
            }
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = 'msg_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/conversations', $filename, 'local');

            MessageAttachment::create([
                'message_id' => $message->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $conversation->load(['participants', 'createdBy']);
        $conversation->unread_count = 0;

        return $this->created($conversation, 'Conversation created');
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if (!$user->hasRole('Admin') && !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return $this->forbidden('You are not a participant of this conversation');
        }

        $conversation->load(['participants', 'createdBy', 'assignedTo', 'linkable']);

        $messages = $conversation->messages()
            ->with(['sender', 'replies', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $conversation->unread_count = $conversation->unreadCountFor($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages->items(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'total' => $messages->total(),
                ],
            ],
        ]);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if (!$user->hasRole('Admin') && !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return $this->forbidden('You are not a participant of this conversation');
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,resolved,closed,archived',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = array_filter($validator->validated(), fn ($v) => !is_null($v));

        $conversation->update($data);
        $conversation->load(['participants', 'createdBy', 'assignedTo']);

        return $this->updated($conversation);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if ((int) $conversation->created_by_id !== (int) $user->id) {
            return $this->forbidden('Only the creator can delete this conversation');
        }

        $conversation->messages()->delete();
        $conversation->participants()->detach();
        $conversation->delete();

        return $this->deleted('Conversation deleted');
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if (!$user->hasRole('Admin') && !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return $this->forbidden('You are not a participant of this conversation');
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Conversation marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        $conversations = Conversation::forUser($user)->get();
        $total = $conversations->sum(fn ($c) => $c->unreadCountFor($user->id));

        return response()->json(['success' => true, 'data' => ['unread_total' => $total]]);
    }
}
