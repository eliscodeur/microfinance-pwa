<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\SyncController as AgentSyncController;
use App\Http\Controllers\Controller;
use App\Models\SyncBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncBatchController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending_review');

        $batches = SyncBatch::with(['agent', 'reviewer'])
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'pending_review' => SyncBatch::where('status', 'pending_review')->count(),
            'approved' => SyncBatch::where('status', 'approved')->count(),
            'rejected' => SyncBatch::where('status', 'rejected')->count(),
            'cancelled' => SyncBatch::where('status', 'cancelled')->count(),
        ];

        return view('admin.sync-batches.index', compact('batches', 'status', 'totals'));
    }

    public function show(SyncBatch $syncBatch)
    {
        $syncBatch->load([
            'agent',
            'reviewer',
            'cycles.carnet.client',
            'collectes.client',
        ]);

        $resume = [
            'total_pointages' => $syncBatch->collectes->sum('pointage'),
            'total_montant' => $syncBatch->collectes->sum('montant'),
            'clients' => $syncBatch->collectes->pluck('client_id')->unique()->count(),
        ];

        return view('admin.sync-batches.show', compact('syncBatch', 'resume'));
    }

    public function approve(SyncBatch $syncBatch, AgentSyncController $syncController)
    {
        if ($syncBatch->status !== 'pending_review') {
            return back()->with('error', 'Ce batch a deja ete traite.');
        }

        DB::transaction(function () use ($syncBatch, $syncController) {
            $syncController->finalizeBatch($syncBatch, auth()->id());

            $syncBatch->update([
                'status' => 'approved',
                'review_note' => request('review_note'),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.sync-batches.show', $syncBatch)
            ->with('success', 'Batch valide et integre dans les tables finales.');
    }

    public function reject(SyncBatch $syncBatch, Request $request)
    {
        if ($syncBatch->status !== 'pending_review') {
            return back()->with('error', 'Ce batch a deja ete traite.');
        }

        $syncBatch->update([
            'status' => 'rejected',
            'review_note' => $request->input('review_note'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.sync-batches.show', $syncBatch)
            ->with('success', 'Batch refuse. Les tables finales n\'ont pas ete modifiees.');
    }
}
