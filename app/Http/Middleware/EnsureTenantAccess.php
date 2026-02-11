<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Ensure the authenticated user has access to the current tenant context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Autenticação necessária.');
        }

        if (! $user->tenant_id) {
            abort(403, 'Usuário não pertence a nenhum tenant.');
        }

        // Super admins can access any tenant
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Check if the user's tenant matches the request context
        $requestTenantId = $request->header('X-Tenant-ID') ?? $request->route('tenant');

        if ($requestTenantId && $requestTenantId !== $user->tenant_id) {
            abort(403, 'Acesso negado a este tenant.');
        }

        return $next($request);
    }
}
