<?php

namespace App\Http\Controllers\Api;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactAcknowledgmentNotification;
use App\Notifications\NewContactInquiryNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends ApiController
{
    /**
     * Public contact form submission. No auth required — but if the
     * visitor happens to be logged in (Sanctum SPA cookie), we link
     * the message to their account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $contactMessage = ContactMessage::create([
            ...$validated,
            'user_id' => $request->user('sanctum')?->id,
            'status' => 'new',
        ]);

        // On-demand: the submitter may not have an account.
        Notification::route('mail', $contactMessage->email)
            ->notify(new ContactAcknowledgmentNotification($contactMessage));

        $superadmins = User::role('superadmin')->get();
        Notification::send($superadmins, new NewContactInquiryNotification($contactMessage));

        return response()->json([
            'message' => 'Thank you for contacting us. We have received your message and will get back to you soon.',
        ], 201);
    }
}
