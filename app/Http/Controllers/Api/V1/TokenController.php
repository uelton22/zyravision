<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    use ApiResponder;

    public const AVAILABLE_ABILITIES = [
        'cameras:read',
        'cameras:write',
        'cameras:delete',
        'devices:read',
        'devices:write',
        'devices:delete',
        'streams:read',
        'streams:write',
        'recordings:read',
        'recordings:write',
        'recordings:download',
        'recordings:delete',
        'storage:read',
        'storage:write',
        'users:read',
        'users:write',
        'profile:read',
        'profile:write',
        'mobile:dashboard',
        'mobile:sync',
        'admin:*',
    ];

    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
                'created_at' => $token->created_at->toISOString(),
            ]);

        return $this->success($tokens);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string|in:' . implode(',', self::AVAILABLE_ABILITIES),
            'expires_at' => 'nullable|date|after:now',
        ]);

        $expiresAt = isset($validated['expires_at'])
            ? \Carbon\Carbon::parse($validated['expires_at'])
            : null;

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'],
            $expiresAt
        );

        return $this->created([
            'token' => $token->plainTextToken,
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => $expiresAt?->toISOString(),
            'created_at' => $token->accessToken->created_at->toISOString(),
        ], ['warning' => 'Store this token securely. It will not be shown again.']);
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            return $this->notFound('Token not found.');
        }

        // Prevent revoking the currently used token via this endpoint
        if ($request->user()->currentAccessToken()->id === $token->id) {
            return $this->error('CANNOT_REVOKE_CURRENT', 'Cannot revoke the token currently in use. Use /auth/logout instead.', 409);
        }

        $token->delete();

        return $this->success(['message' => 'Token revoked successfully.']);
    }

    public function abilities(): JsonResponse
    {
        $grouped = [
            'cameras' => ['cameras:read', 'cameras:write', 'cameras:delete'],
            'devices' => ['devices:read', 'devices:write', 'devices:delete'],
            'streams' => ['streams:read', 'streams:write'],
            'recordings' => ['recordings:read', 'recordings:write', 'recordings:download', 'recordings:delete'],
            'storage' => ['storage:read', 'storage:write'],
            'users' => ['users:read', 'users:write'],
            'profile' => ['profile:read', 'profile:write'],
            'mobile' => ['mobile:dashboard', 'mobile:sync'],
            'admin' => ['admin:*'],
        ];

        return response()->json([
            'data' => $grouped,
            'meta' => ['api_version' => 'v1'],
        ]);
    }
}
