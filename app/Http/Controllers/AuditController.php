<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::query()
            ->with('causer:id,name,email')
            ->latest();

        // Filter by tenant users only (unless super-admin)
        $user = $request->user();
        if ($user && !$user->isSuperAdmin() && $user->tenant) {
            $tenantUserIds = $user->tenant->users()->pluck('id')->toArray();
            $query->where(function ($q) use ($tenantUserIds) {
                $q->whereIn('causer_id', $tenantUserIds)
                  ->where('causer_type', \App\Models\User::class);
            });
        }

        if ($search = $request->input('search')) {
            $query->where('description', 'ilike', "%{$search}%");
        }

        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }

        $activities = $query->paginate($request->input('per_page', 20))->withQueryString();

        // Format dates for the frontend
        $activities->through(function ($activity) {
            $activity->created_at_formatted = $activity->created_at?->format('d/m/Y H:i');
            return $activity;
        });

        return Inertia::render('Audit/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
