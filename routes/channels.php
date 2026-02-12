<?php

use App\Models\Camera;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels — ZyraVision
|--------------------------------------------------------------------------
|
| Canais privados para broadcasting em tempo real.
| Cada canal verifica se o usuario tem permissao de acesso.
|
*/

// Canal do tenant: todos os usuarios do tenant recebem eventos
Broadcast::channel('tenant.{tenantId}', function (User $user, string $tenantId) {
    return $user->isSuperAdmin() || $user->tenant_id === $tenantId;
});

// Canal da camera: somente usuarios do tenant da camera
Broadcast::channel('camera.{cameraId}', function (User $user, string $cameraId) {
    if ($user->isSuperAdmin()) {
        return true;
    }

    $camera = Camera::find($cameraId);

    return $camera && $camera->tenant_id === $user->tenant_id;
});

// Canal do usuario: somente o proprio usuario
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});
