<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;

class EnsurePermissionWithFeedback
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if ($request->user() && $request->user()->can($permission)) {
            return $next($request);
        }

        $label = $this->permissionLabel($permission);
        $message = $label
            ? "Votre rôle ne dispose pas de la permission « {$label} »."
            : 'Votre rôle ne dispose pas de la permission requise pour cette opération.';

        if ($request->headers->has('X-Inertia')) {
            return redirect()->back()->with('error', $message);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->back()->with('error', $message);
    }

    private function permissionLabel(string $permission): ?string
    {
        foreach (PermissionCatalog::groups() as $group) {
            foreach ($group['modules'] as $module) {
                if (isset($module['permissions'][$permission])) {
                    return $module['permissions'][$permission];
                }
            }
        }

        return null;
    }
}
