<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Traits\OwnableAuthorization;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    use ApiResponse, OwnableAuthorization;

    private function ensureParticipant(Conversation $conversation, int $userId): void
    {
        $exists = $conversation->participants()->where('user_id', $userId)->exists();
        if (!$exists) {
            $conversation->participants()->attach($userId);
        }
    }

    private function notifyOtherParticipants(Conversation $conversation, Message $message, int $senderId): void
    {
        $sender = $message->sender;
        $recipients = $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->get();

        foreach ($recipients as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'new_message',
                'title' => 'New message from ' . $sender->name,
                'body' => Str::limit($message->body, 100),
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'link' => '/messages/' . $conversation->id,
                ],
            ]);
        }
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if (!$user->hasRole('Admin') && !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return $this->forbidden('You are not a participant of this conversation');
        }

        $messages = $conversation->messages()
            ->with(['sender', 'replies', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return $this->paginated($messages);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if (!$user->hasRole('Admin') && !$conversation->participants()->where('user_id', $user->id)->exists()) {
            return $this->forbidden('You are not a participant of this conversation');
        }

        $validator = Validator::make($request->all(), [
            'body' => 'nullable|string',
            'parent_id' => 'nullable|exists:messages,id',
            'file' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $validator->validated();

        $this->ensureParticipant($conversation, $user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $data['body'] ?? '',
            'parent_id' => $data['parent_id'] ?? null,
        ]);

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

        $message->load(['sender', 'attachments']);

        $this->notifyOtherParticipants($conversation, $message, $user->id);

        return $this->created($message, 'Message sent');
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if ((int) $message->sender_id !== (int) $user->id) {
            return $this->forbidden('You can only edit your own messages');
        }

        if ($message->created_at->diffInMinutes(now()) > 5) {
            return $this->forbidden('Messages can only be edited within 5 minutes of sending');
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $message->update(['body' => $validator->validated()['body']]);
        $message->load(['sender', 'attachments']);

        return $this->updated($message, 'Message updated');
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if ((int) $message->sender_id !== (int) $user->id) {
            return $this->forbidden('You can only delete your own messages');
        }

        if ($message->created_at->diffInMinutes(now()) > 5) {
            return $this->forbidden('Messages can only be deleted within 5 minutes of sending');
        }

        $message->attachments()->delete();
        $message->delete();

        return $this->deleted('Message deleted');
    }

    public function uploadAttachment(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        if ((int) $message->sender_id !== (int) $user->id) {
            return $this->forbidden('You can only attach files to your own messages');
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $file = $request->file('file');
        $filename = 'msg_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/conversations', $filename, 'local');

        $attachment = MessageAttachment::create([
            'message_id' => $message->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return $this->created($attachment, 'Attachment uploaded');
    }

    public function deleteAttachment(Request $request, MessageAttachment $attachment): JsonResponse
    {
        $user = $request->user();
        if (!$user) return $this->unauthorized();

        $message = $attachment->message;
        if (!$message || (int) $message->sender_id !== (int) $user->id) {
            return $this->forbidden('You can only delete attachments from your own messages');
        }

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return $this->deleted('Attachment deleted');
    }
}
