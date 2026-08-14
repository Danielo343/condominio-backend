<?php

namespace App\Events;

use App\Models\Notificacion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notificacion;

    public function __construct(Notificacion $notificacion)
    {
        $this->notificacion = $notificacion;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('notificaciones-generales')
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificacionCreada';
    }
}