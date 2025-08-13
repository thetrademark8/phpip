<?php

namespace App\Events;

use App\Models\Matter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatterStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Matter $matter,
        public string $oldStatus,
        public string $newStatus
    ) {
    }
}