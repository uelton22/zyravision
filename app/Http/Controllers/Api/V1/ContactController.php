<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Store a contact form submission from the landing page.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'cameras' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ]);

        // Log the contact for now (will be replaced with proper email/CRM integration)
        Log::info('Landing page contact form submission', $validated);

        // TODO: Send notification email to contato@ziravision.com.br
        // TODO: Store in contacts table
        // TODO: Integrate with CRM

        return response()->json([
            'message' => 'Mensagem enviada com sucesso! Entraremos em contato em breve.',
        ], 201);
    }
}
