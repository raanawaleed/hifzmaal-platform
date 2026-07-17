<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ContactMessage;
use App\Notifications\ContactReplyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactMessageController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $messages = ContactMessage::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%"));
            })
            ->withCount('replies')
            ->latest()
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($messages);
    }

    public function show(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->load(['replies.user']);

        return response()->json([
            'data' => [
                'id' => $contactMessage->id,
                'user_id' => $contactMessage->user_id,
                'name' => $contactMessage->name,
                'email' => $contactMessage->email,
                'subject' => $contactMessage->subject,
                'message' => $contactMessage->message,
                'status' => $contactMessage->status,
                'created_at' => $contactMessage->created_at,
                'updated_at' => $contactMessage->updated_at,
                'replies' => $contactMessage->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'body' => $reply->body,
                    'admin_name' => $reply->user?->name,
                    'created_at' => $reply->created_at,
                ]),
            ],
        ]);
    }

    public function update(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
        ]);

        $contactMessage->update($validated);

        return response()->json([
            'message' => 'Inquiry status updated.',
            'data' => $contactMessage->fresh(),
        ]);
    }

    public function reply(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $reply = $contactMessage->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        // On-demand: the submitter may not have an account.
        Notification::route('mail', $contactMessage->email)
            ->notify(new ContactReplyNotification($contactMessage, $reply));

        // First reply moves a fresh inquiry into "in progress"; already
        // resolved/closed inquiries keep their status.
        if ($contactMessage->status === 'new') {
            $contactMessage->update(['status' => 'in_progress']);
        }

        return response()->json([
            'message' => 'Reply sent to ' . $contactMessage->email . '.',
            'data' => [
                'id' => $reply->id,
                'body' => $reply->body,
                'admin_name' => $request->user()->name,
                'created_at' => $reply->created_at,
            ],
        ], 201);
    }
}
