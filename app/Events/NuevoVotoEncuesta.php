<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // <-- Muy importante
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoVotoEncuesta implements ShouldBroadcastNow // <-- Implementamos la interfaz
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Estas variables públicas son los datos que viajarán mágicamente hasta tu Vue
    public $opcion;
    public $totalVotos;

    /**
     * Create a new event instance.
     */
    public function __construct($opcion, $totalVotos)
    {
        $this->opcion = $opcion;
        $this->totalVotos = $totalVotos;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Usamos un Channel (Canal Público) porque todos los vecinos pueden ver los resultados de la encuesta
        return [
            new Channel('encuestas-condominio'),
        ];
    }

    /**
     * Opcional: Le damos un nombre personalizado al evento para escucharlo más fácil en Vue
     */
    public function broadcastAs(): string
    {
        return 'voto.registrado';
    }
}