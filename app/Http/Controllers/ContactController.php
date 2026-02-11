<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Store a contact form submission from the landing page (Inertia).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'cameras' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ]);

        // Log the contact submission
        Log::info('Landing page contact form submission', $validated);

        // TODO: Send email notification to contato@ziravision.com.br
        // TODO: Store in database (contacts table)

        return redirect()->back()->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
    }
}
