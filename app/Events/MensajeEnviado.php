<?php

namespace App\Events;

use App\Models\MensajeChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;

    public function __construct(MensajeChat $mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat-general')
        ];
    }

    public function broadcastAs(): string
    {
        return 'MensajeEnviado';
    }
}