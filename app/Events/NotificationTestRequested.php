<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationTestRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(string $type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }
}
