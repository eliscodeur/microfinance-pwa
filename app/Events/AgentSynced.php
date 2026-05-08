<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentSynced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $agent;

    /**
     * On passe l'instance de l'agent au constructeur pour que
     * ses données soient envoyées vers le JavaScript.
     */
    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    /**
     * On utilise un Channel public pour que l'Admin puisse
     * écouter sans configuration de sécurité complexe au début.
     */
    public function broadcastOn()
    {
        return new Channel('agents-channel');
    }

    /**
     * Optionnel : Tu peux choisir le nom de l'événement côté JS.
     * Par défaut, c'est "AgentSynced".
     */
    public function broadcastAs()
    {
        return 'AgentSyncedEvent';
    }
}
