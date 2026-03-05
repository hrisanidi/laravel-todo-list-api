<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $note;

    public function __construct(Note $note)
    {
        $this->note = $note->load('user');
    }
}
