<?php

namespace App\Events;

use App\Models\SyncBatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncBatchCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batch;

    public function __construct(SyncBatch $batch)
    {
        // On charge la relation agent pour avoir le nom et code dans le JSON
        $this->batch = $batch->load('agent');
    }

    public function broadcastOn()
    {
        return new Channel('admin-sync');
    }

    public function broadcastAs()
    {
        return 'sync.created';
    }
}