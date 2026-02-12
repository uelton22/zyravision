<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('Settings/ApiTokens/Index', [
            'tokens' => $tokens,
            'availableAbilities' => TokenController::AVAILABLE_ABILITIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string|in:' . implode(',', TokenController::AVAILABLE_ABILITIES),
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

        return back()->with([
            'success' => 'Token criado com sucesso.',
            'newToken' => $token->plainTextToken,
        ]);
    }

    public function destroy(Request $request, string $tokenId)
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            return back()->with('error', 'Token nao encontrado.');
        }

        $token->delete();

        return back()->with('success', 'Token revogado com sucesso.');
    }
}
