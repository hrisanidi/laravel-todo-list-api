<?php

namespace App\Listeners;

use App\Events\NoteCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNoteCreatedNotification implements ShouldQueue
{
    public $tries = 3;
    public $timeout = 120;

    public function handle(NoteCreated $event)
    {
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            Log::info('Notification sent to admin', [
                'admin_email' => $admin->email,
                'admin_id' => $admin->id,
                'note_id' => $event->note->id,
                'note_title' => $event->note->title,
                'note_user_id' => $event->note->user_id,
                'note_user_email' => $event->note->user->email,
            ]);
        }
    }

    public function failed(NoteCreated $event, $exception)
    {
        Log::error('Failed to send admin notification', [
            'note_id' => $event->note->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
