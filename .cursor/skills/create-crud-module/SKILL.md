---
name: create-crud-module
description: Cria modulo CRUD completo no ZyraVision (Model, Migration, Controller, Service, FormRequest, Resource, Routes, Vue Page, Tests). Use quando o usuario pedir para criar um novo modulo, entidade, recurso ou CRUD.
---

# Criar Modulo CRUD Completo — ZyraVision

## Workflow

Ao criar um novo modulo CRUD, seguir esta ordem EXATA:

### Step 1: Migration

```bash
php artisan make:migration create_{resource_plural}_table
```

Regras:
- UUID como primary key
- `tenant_id` obrigatorio
- `timestamps()` e `softDeletes()`
- Indices compostos em `[tenant_id, ...]`

### Step 2: Model

Criar em `app/Domains/{Domain}/Models/{Resource}.php`:

```php
class Camera extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', ...];
    protected $casts = ['metadata' => 'array'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
```

### Step 3: Service

Criar em `app/Domains/{Domain}/Services/{Resource}Service.php`:
- Metodos: list, find, create, update, delete
- Injetar dependencias via constructor
- Lancar exceptions de dominio

### Step 4: Form Requests

- `Store{Resource}Request` — validacao para criacao
- `Update{Resource}Request` — validacao para atualizacao
- `Index{Resource}Request` — filtros e paginacao

### Step 5: API Resource

- `{Resource}Resource` — representacao JSON
- `{Resource}Collection` — listagem paginada

### Step 6: Controller

Criar em `app/Http/Controllers/Api/V1/{Resource}Controller.php`:
- Metodos padrao: index, show, store, update, destroy
- Injetar Service
- Usar Form Requests e Resources

### Step 7: Routes

Adicionar em `routes/api.php`:

```php
Route::apiResource('cameras', CameraController::class);
```

### Step 8: Vue Pages

Criar em `resources/js/Pages/{Domain}/`:
- `Index.vue` — Listagem com ZyTable
- `Create.vue` — Formulario de criacao
- `Edit.vue` — Formulario de edicao
- `Show.vue` — Detalhes

OBRIGATORIO: Usar componentes do Design System (ZyTable, ZyButton, ZyInput, ZyModal).

### Step 9: Tests

- Feature test para cada endpoint
- Unit test para Service
- Usar Factories

## Checklist

- [ ] Migration criada com UUID e tenant_id
- [ ] Model com scopes e relationships
- [ ] Service com logica de negocio
- [ ] Form Requests com validacao
- [ ] API Resource para response
- [ ] Controller com 5 metodos
- [ ] Routes registradas
- [ ] Vue Pages com Design System
- [ ] Tests (feature + unit)
