# Coding Standards — ZyraVision

## Principios Gerais

1. **Clareza sobre cleverness** — Codigo legivel > codigo inteligente
2. **Consistencia** — Mesmo padrao em todo o projeto
3. **Tipagem forte** — TypeScript no frontend, PHP typed no backend
4. **Imutabilidade** — Preferir dados imutaveis quando possivel
5. **Single Responsibility** — Cada classe/funcao faz uma coisa

## PHP / Laravel

### Naming Conventions

```php
// Classes: PascalCase
class CameraService {}
class StoreCameraRequest {}

// Metodos e variaveis: camelCase
public function findById(string $id): Camera {}
$cameraCount = 0;

// Constantes: UPPER_SNAKE_CASE
const MAX_CAMERAS_PER_TENANT = 1000;

// Tabelas: snake_case plural
// cameras, storage_pools, ai_events

// Colunas: snake_case
// tenant_id, rtsp_url, created_at
```

### Tipagem

```php
// SEMPRE tipar parametros e retornos
public function create(string $tenantId, array $data): Camera
{
    // ...
}

// SEMPRE usar strict types
declare(strict_types=1);
```

### Controllers

```php
// Thin controllers, fat services
class CameraController extends Controller
{
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

### Query Builder

```php
// SEMPRE eager loading (prevenir N+1)
Camera::with(['streams', 'recordings'])
    ->forTenant($tenantId)
    ->active()
    ->paginate(25);

// NUNCA queries em loops
// NUNCA N+1 queries
```

## Vue.js / TypeScript

### Naming Conventions

```typescript
// Componentes: PascalCase
ZyButton.vue
CameraCard.vue

// Props: camelCase
defineProps<{ isActive: boolean }>()

// Emits: camelCase
defineEmits<{ updateStatus: [status: string] }>()

// Composables: use* prefix
useCamera()
useTheme()

// Types/Interfaces: PascalCase
interface Camera { ... }
type CameraStatus = 'online' | 'offline'

// Constantes: UPPER_SNAKE_CASE
const MAX_GRID_COLUMNS = 6
```

### Componentes

```vue
<!-- Ordem padrao do <script setup> -->
<script setup lang="ts">
// 1. Imports
// 2. Types/Interfaces
// 3. Props
// 4. Emits
// 5. Composables/Hooks
// 6. Reactive state
// 7. Computed
// 8. Methods
// 9. Watchers
// 10. Lifecycle hooks
</script>

<template>
  <!-- Template limpo, sem logica complexa -->
</template>
```

### TypeScript

```typescript
// SEMPRE tipar explicitamente
const cameras: Ref<Camera[]> = ref([])

// NUNCA usar 'any'
// Usar 'unknown' quando tipo nao e conhecido

// Preferir types para unions
type Status = 'online' | 'offline' | 'error'

// Preferir interfaces para objetos
interface Camera {
  id: string
  name: string
  status: Status
}
```

## Dart / Flutter

### Naming Conventions

```dart
// Classes: PascalCase
class CameraListScreen {}
class CameraProvider {}

// Variaveis e metodos: camelCase
final cameraName = '';
void loadCameras() {}

// Constantes: lowerCamelCase
const maxRetries = 3;

// Arquivos: snake_case
camera_list_screen.dart
camera_provider.dart
```

## Git

### Commits

Formato: `type(scope): descricao`

```
feat(cameras): adicionar cadastro de cameras RTSP
fix(auth): corrigir refresh token expirado
refactor(storage): extrair service de retencao
docs(adr): adicionar ADR-0026 mobile compatibility
test(cameras): adicionar testes de listagem
chore(deps): atualizar laravel para 11.x
```

### Branches

```
main              # Producao
develop           # Desenvolvimento
feature/cameras   # Features
fix/auth-bug      # Bugfixes
release/1.0.0     # Releases
```

## Code Review

### Checklist

- [ ] Tipagem completa (sem 'any')
- [ ] Testes adicionados
- [ ] Segue Design System
- [ ] Sem N+1 queries
- [ ] Tenant isolation verificado
- [ ] Form Request com validacao
- [ ] API Resource com camelCase
- [ ] Sem CSS inline ou estilos ad-hoc
