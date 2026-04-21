<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\SyncBatch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAgents = Agent::count();
        $totalClients = Client::count();
        $totalCollecte = 0; // Placeholder, as there's no collection model yet
        $pendingSyncBatches = SyncBatch::where('status', 'pending_review')->count();
        $recentClients = Client::with('agent')->latest()->take(5)->get();
        $recentAgents = Agent::latest()->take(5)->get();

        return view('admin.dashboard', compact('totalAgents', 'totalClients', 'totalCollecte', 'pendingSyncBatches', 'recentClients', 'recentAgents'));
    }
}
