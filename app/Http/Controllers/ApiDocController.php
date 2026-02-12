<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ApiDocController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/ApiDocs/Index', [
            'baseUrl' => config('app.url') . '/api/v1',
            'endpoints' => $this->getEndpoints(),
        ]);
    }

    protected function getEndpoints(): array
    {
        return [
            [
                'group' => 'Autenticacao',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/auth/login',
                        'description' => 'Autenticar e obter token de acesso',
                        'auth' => false,
                        'params' => [
                            ['name' => 'email', 'type' => 'string', 'required' => true],
                            ['name' => 'password', 'type' => 'string', 'required' => true],
                            ['name' => 'device_name', 'type' => 'string', 'required' => true],
                        ],
                        'response' => '{"token":"1|abc...","user":{"id":"...","name":"...","roles":["admin"]}}',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/auth/logout',
                        'description' => 'Revogar token atual',
                        'auth' => true,
                        'params' => [],
                        'response' => '{"message":"Logout realizado com sucesso."}',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/auth/me',
                        'description' => 'Dados do usuario autenticado',
                        'auth' => true,
                        'params' => [],
                        'response' => '{"user":{"id":"...","name":"...","tenant_id":"...","roles":["admin"],"permissions":[...]}}',
                    ],
                ],
            ],
            [
                'group' => 'Cameras',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/cameras', 'description' => 'Listar cameras', 'auth' => true, 'params' => [
                        ['name' => 'search', 'type' => 'string', 'required' => false],
                        ['name' => 'status', 'type' => 'string', 'required' => false, 'values' => 'online|offline|recording|active|inactive'],
                        ['name' => 'device_id', 'type' => 'uuid', 'required' => false],
                        ['name' => 'include', 'type' => 'string', 'required' => false, 'values' => 'device,activeStream,tenant'],
                        ['name' => 'per_page', 'type' => 'integer', 'required' => false],
                    ], 'response' => '{"data":[...],"meta":{"total":42,"per_page":15},"links":{...}}'],
                    ['method' => 'POST', 'path' => '/cameras', 'description' => 'Criar camera', 'auth' => true, 'params' => [
                        ['name' => 'name', 'type' => 'string', 'required' => true],
                        ['name' => 'channel_type', 'type' => 'string', 'required' => true, 'values' => 'analog|ip|direct'],
                        ['name' => 'address', 'type' => 'string', 'required' => false],
                        ['name' => 'port', 'type' => 'integer', 'required' => false],
                    ], 'response' => '{"data":{"id":"...","name":"..."},"meta":{"api_version":"v1"}}'],
                    ['method' => 'GET', 'path' => '/cameras/{camera}', 'description' => 'Detalhes da camera', 'auth' => true, 'params' => [], 'response' => '{"data":{"id":"...","name":"...","is_online":true}}'],
                    ['method' => 'PUT', 'path' => '/cameras/{camera}', 'description' => 'Atualizar camera', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                    ['method' => 'DELETE', 'path' => '/cameras/{camera}', 'description' => 'Excluir camera', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Camera deleted successfully."}}'],
                    ['method' => 'GET', 'path' => '/cameras/{camera}/thumbnail', 'description' => 'Thumbnail da camera', 'auth' => true, 'params' => [], 'response' => '{"data":{"snapshot_url":"..."}}'],
                ],
            ],
            [
                'group' => 'Dispositivos',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/devices', 'description' => 'Listar dispositivos', 'auth' => true, 'params' => [
                        ['name' => 'search', 'type' => 'string', 'required' => false],
                        ['name' => 'type', 'type' => 'string', 'required' => false, 'values' => 'dvr|nvr|encoder|camera|hybrid'],
                    ], 'response' => '{"data":[...],"meta":{...}}'],
                    ['method' => 'POST', 'path' => '/devices', 'description' => 'Criar dispositivo', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                    ['method' => 'GET', 'path' => '/devices/{device}', 'description' => 'Detalhes do dispositivo', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                    ['method' => 'PUT', 'path' => '/devices/{device}', 'description' => 'Atualizar dispositivo', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                    ['method' => 'DELETE', 'path' => '/devices/{device}', 'description' => 'Excluir dispositivo', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Device deleted."}}'],
                ],
            ],
            [
                'group' => 'Streaming',
                'endpoints' => [
                    ['method' => 'POST', 'path' => '/cameras/{camera}/stream/start', 'description' => 'Iniciar stream da camera', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Stream is being started.","camera_id":"..."},"meta":{"api_version":"v1"}}'],
                    ['method' => 'POST', 'path' => '/cameras/{camera}/stream/stop', 'description' => 'Parar stream da camera', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Stream is being stopped."}}'],
                    ['method' => 'GET', 'path' => '/cameras/{camera}/stream', 'description' => 'Status do stream (polling)', 'auth' => true, 'params' => [], 'response' => '{"data":{"active":true,"camera_online":true,"stream":{"hls_url":"...","webrtc_url":"..."}}}'],
                ],
            ],
            [
                'group' => 'Gravacoes',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/recordings', 'description' => 'Listar gravacoes', 'auth' => true, 'params' => [
                        ['name' => 'camera_id', 'type' => 'uuid', 'required' => false],
                        ['name' => 'status', 'type' => 'string', 'required' => false],
                        ['name' => 'from', 'type' => 'datetime', 'required' => false],
                        ['name' => 'to', 'type' => 'datetime', 'required' => false],
                    ], 'response' => '{"data":[...],"meta":{...}}'],
                    ['method' => 'GET', 'path' => '/recordings/{recording}', 'description' => 'Detalhes da gravacao', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                    ['method' => 'DELETE', 'path' => '/recordings/{recording}', 'description' => 'Excluir gravacao', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Recording deleted."}}'],
                    ['method' => 'GET', 'path' => '/recordings/{recording}/stream', 'description' => 'Stream de video (byte-range)', 'auth' => true, 'params' => [], 'response' => 'Binary video/mp4 stream'],
                    ['method' => 'GET', 'path' => '/recordings/{recording}/download', 'description' => 'Download da gravacao', 'auth' => true, 'params' => [], 'response' => 'File download'],
                    ['method' => 'POST', 'path' => '/recordings/{recording}/clip', 'description' => 'Criar clip de trecho', 'auth' => true, 'params' => [
                        ['name' => 'start', 'type' => 'number', 'required' => true],
                        ['name' => 'end', 'type' => 'number', 'required' => true],
                    ], 'response' => '{"data":{"message":"Clip creation queued."}}'],
                    ['method' => 'GET', 'path' => '/cameras/{camera}/recordings', 'description' => 'Gravacoes de uma camera', 'auth' => true, 'params' => [], 'response' => '{"data":[...]}'],
                ],
            ],
            [
                'group' => 'Armazenamento',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/storage', 'description' => 'Dashboard de armazenamento', 'auth' => true, 'params' => [], 'response' => '{"data":{"used_gb":12.5,"limit_gb":100,"percentage":12.5,"backends":[...]}}'],
                    ['method' => 'PUT', 'path' => '/storage/retention', 'description' => 'Atualizar politica de retencao', 'auth' => true, 'params' => [
                        ['name' => 'retention_days', 'type' => 'integer', 'required' => true],
                    ], 'response' => '{"data":{"message":"Retention policy updated.","retention_days":30}}'],
                ],
            ],
            [
                'group' => 'Planos',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/plans', 'description' => 'Listar planos disponiveis', 'auth' => true, 'params' => [], 'response' => '{"data":[{"id":"...","name":"Pro","limits":{...},"features":{...}}]}'],
                    ['method' => 'GET', 'path' => '/plans/{plan}', 'description' => 'Detalhes do plano', 'auth' => true, 'params' => [], 'response' => '{"data":{...}}'],
                ],
            ],
            [
                'group' => 'Perfil',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/profile', 'description' => 'Dados do perfil', 'auth' => true, 'params' => [], 'response' => '{"data":{"id":"...","name":"...","roles":["admin"],"quotas":{...}}}'],
                    ['method' => 'PUT', 'path' => '/profile', 'description' => 'Atualizar perfil', 'auth' => true, 'params' => [
                        ['name' => 'name', 'type' => 'string', 'required' => false],
                        ['name' => 'email', 'type' => 'string', 'required' => false],
                    ], 'response' => '{"data":{...}}'],
                    ['method' => 'PUT', 'path' => '/profile/password', 'description' => 'Alterar senha', 'auth' => true, 'params' => [
                        ['name' => 'current_password', 'type' => 'string', 'required' => true],
                        ['name' => 'password', 'type' => 'string', 'required' => true],
                        ['name' => 'password_confirmation', 'type' => 'string', 'required' => true],
                    ], 'response' => '{"data":{"message":"Password updated."}}'],
                ],
            ],
            [
                'group' => 'Tokens',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/tokens', 'description' => 'Listar tokens', 'auth' => true, 'params' => [], 'response' => '{"data":[{"id":"...","name":"...","abilities":[...]}]}'],
                    ['method' => 'POST', 'path' => '/tokens', 'description' => 'Criar token', 'auth' => true, 'params' => [
                        ['name' => 'name', 'type' => 'string', 'required' => true],
                        ['name' => 'abilities', 'type' => 'array', 'required' => true],
                    ], 'response' => '{"data":{"token":"1|abc...","id":"...","abilities":[...]}}'],
                    ['method' => 'DELETE', 'path' => '/tokens/{token}', 'description' => 'Revogar token', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Token revoked."}}'],
                ],
            ],
            [
                'group' => 'Mobile',
                'endpoints' => [
                    ['method' => 'GET', 'path' => '/mobile/dashboard', 'description' => 'Dashboard agregado para mobile', 'auth' => true, 'params' => [], 'response' => '{"data":{"stats":{...},"cameras":[...],"recent_events":[...]}}'],
                    ['method' => 'GET', 'path' => '/mobile/sync/status', 'description' => 'Verificar se ha mudancas', 'auth' => true, 'params' => [
                        ['name' => 'since', 'type' => 'datetime', 'required' => false],
                    ], 'response' => '{"data":{"has_changes":true,"cameras_updated":3}}'],
                    ['method' => 'POST', 'path' => '/mobile/sync/pull', 'description' => 'Puxar dados atualizados', 'auth' => true, 'params' => [], 'response' => '{"data":{"cameras":[...],"recordings":[...]}}'],
                    ['method' => 'POST', 'path' => '/mobile/sync/push', 'description' => 'Enviar dados do device', 'auth' => true, 'params' => [], 'response' => '{"data":{"message":"Sync data received."}}'],
                ],
            ],
        ];
    }
}
