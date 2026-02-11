---
name: create-test
description: Cria testes padronizados para o ZyraVision usando Pest PHP (backend) e Vitest (frontend). Use quando o usuario pedir para criar teste, testar funcionalidade, ou validar comportamento.
---

# Criar Testes — ZyraVision

## Backend — Pest PHP

### Feature Test (Endpoint API)

```php
// tests/Feature/Api/V1/CameraTest.php
use App\Models\User;
use App\Models\Camera;

describe('Camera API', function () {

    it('lista cameras do tenant', function () {
        $user = User::factory()->create();
        Camera::factory()->count(3)
            ->for($user->tenant)
            ->create();

        actingAs($user)
            ->getJson('/api/v1/cameras')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'rtspUrl', 'status']],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    });

    it('nao lista cameras de outro tenant', function () {
        $user = User::factory()->create();
        Camera::factory()->count(2)->create(); // outro tenant

        actingAs($user)
            ->getJson('/api/v1/cameras')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('cria camera com dados validos', function () {
        $user = User::factory()->admin()->create();

        actingAs($user)
            ->postJson('/api/v1/cameras', [
                'name' => 'Entrada Principal',
                'rtsp_url' => 'rtsp://192.168.1.100/stream',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('cameras', [
            'name' => 'Entrada Principal',
            'tenant_id' => $user->tenant_id,
        ]);
    });

    it('rejeita criacao sem permissao', function () {
        $user = User::factory()->viewer()->create();

        actingAs($user)
            ->postJson('/api/v1/cameras', [
                'name' => 'Test',
                'rtsp_url' => 'rtsp://test/stream',
            ])
            ->assertForbidden();
    });
});
```

### Unit Test (Service)

```php
// tests/Unit/Domains/Camera/CameraServiceTest.php
use App\Domains\Camera\Services\CameraService;

it('calcula status da camera baseado no heartbeat', function () {
    $service = new CameraService();
    // ... test logic
    expect($status)->toBe('online');
});
```

## Frontend — Vitest

```typescript
// resources/js/Components/UI/__tests__/ZyButton.spec.ts
import { mount } from '@vue/test-utils'
import ZyButton from '../ZyButton.vue'

describe('ZyButton', () => {
  it('renderiza com variante primary', () => {
    const wrapper = mount(ZyButton, {
      props: { variant: 'primary' },
      slots: { default: 'Salvar' },
    })
    expect(wrapper.text()).toBe('Salvar')
  })

  it('emite click quando clicado', async () => {
    const wrapper = mount(ZyButton)
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toHaveLength(1)
  })

  it('nao emite click quando disabled', async () => {
    const wrapper = mount(ZyButton, {
      props: { disabled: true },
    })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeUndefined()
  })
})
```

## Convencoes

- Descrever testes em portugues
- Um conceito por `it()`
- Usar Factories (backend) e mount helpers (frontend)
- Database transactions em feature tests
- Mock servicos externos

## Checklist

- [ ] Feature tests para todos os endpoints
- [ ] Unit tests para Services
- [ ] Testa cenarios de sucesso e erro
- [ ] Testa autorizacao (RBAC)
- [ ] Testa isolamento de tenant
- [ ] Frontend: testa renderizacao e interacao
