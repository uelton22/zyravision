<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DemoController extends Controller
{
    /**
     * Show demo login page with all demo users.
     */
    public function redirect(): Response
    {
        return Inertia::render('Auth/Login', [
            'demo' => true,
            'demoUsers' => self::getDemoUsers(),
        ]);
    }

    /**
     * Get all demo user credentials.
     */
    public static function getDemoUsers(): array
    {
        return [
            [
                'role' => 'Super Admin',
                'email' => 'super@zyravision.com.br',
                'password' => 'ZyraVision@2024!',
                'description' => 'Acesso total à plataforma (gerencia tenants)',
                'badge' => 'danger',
            ],
            [
                'role' => 'Administrador',
                'email' => 'admin@demo.zyravision.com.br',
                'password' => 'demo2024',
                'description' => 'Gerencia usuários e câmeras do tenant',
                'badge' => 'primary',
            ],
            [
                'role' => 'Operador',
                'email' => 'operador@demo.zyravision.com.br',
                'password' => 'demo2024',
                'description' => 'Opera câmeras e visualiza gravações',
                'badge' => 'warning',
            ],
            [
                'role' => 'Visualizador',
                'email' => 'viewer@demo.zyravision.com.br',
                'password' => 'demo2024',
                'description' => 'Apenas visualização (somente leitura)',
                'badge' => 'secondary',
            ],
        ];
    }
}
