---
name: create-api-endpoint
description: Cria endpoints de API REST padronizados no ZyraVision (Controller, Route, Request, Resource, teste). Use quando o usuario pedir para criar endpoint, rota de API, ou servico REST.
---

# Criar Endpoint API — ZyraVision

## Padrao de Endpoint

Toda API segue: Controller + FormRequest + Resource + Route + Test

### Step 1: Form Request

```php
// app/Http/Requests/Api/V1/Camera/StoreCameraRequest.php
class StoreCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Camera::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rtsp_url' => ['required', 'url', 'starts_with:rtsp://'],
            'location' => ['nullable', 'string'],
        ];
    }
}
```

### Step 2: API Resource

```php
// app/Http/Resources/Api/V1/CameraResource.php
class CameraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'rtspUrl' => $this->rtsp_url,
            'status' => $this->status,
            'createdAt' => $this->created_at->toISOString(),
        ];
    }
}
```

### Step 3: Controller

```php
// app/Http/Controllers/Api/V1/CameraController.php
class CameraController extends Controller
{
    public function __construct(
        private readonly CameraService $cameraService
    ) {}

    public function store(StoreCameraRequest $request): CameraResource
    {
        $camera = $this->cameraService->create(
            $request->user()->tenant_id,
            $request->validated()
        );

        return new CameraResource($camera);
    }
}
```

### Step 4: Route

```php
// routes/api.php
Route::prefix('v1')->middleware(['auth:sanctum', 'ensure.tenant'])->group(function () {
    Route::apiResource('cameras', CameraController::class);
});
```

### Step 5: Test

```php
it('cria camera com dados validos', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/cameras', [
            'name' => 'Camera Entrada',
            'rtsp_url' => 'rtsp://192.168.1.100/stream1',
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'name', 'rtspUrl']]);
});
```

## Mobile Compatibility

- Mesmos endpoints servem web e mobile
- Adicionar `?fields=id,name` para sparse fieldsets
- Suporte a `If-Modified-Since` header
- Response deve incluir `ETag` header

## Checklist

- [ ] FormRequest com authorize() e rules()
- [ ] Resource com camelCase keys
- [ ] Controller delega para Service
- [ ] Route versionada (/api/v1/)
- [ ] Middleware tenant
- [ ] Feature test criado
- [ ] Documentar no OpenAPI spec
