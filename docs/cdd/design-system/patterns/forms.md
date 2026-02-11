# Padrao de Formularios — ZyraVision Design System

## Estrutura de Formulario

```vue
<ZyCard class="max-w-2xl">
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Secao 1 -->
    <div>
      <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
        Informacoes Basicas
      </h3>
      <div class="space-y-4">
        <ZyInput v-model="form.name" label="Nome" :error="form.errors.name" required />
        <ZyInput v-model="form.email" label="Email" type="email" :error="form.errors.email" required />
      </div>
    </div>

    <!-- Divider entre secoes -->
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    <!-- Secao 2 -->
    <div>
      <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
        Configuracoes
      </h3>
      <div class="space-y-4">
        <ZySelect v-model="form.role" label="Papel" :options="roles" :error="form.errors.role" />
        <ZySwitch v-model="form.active" label="Ativo" />
      </div>
    </div>

    <!-- Footer com acoes -->
    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
      <ZyButton variant="secondary" :href="backUrl">Cancelar</ZyButton>
      <ZyButton type="submit" :loading="form.processing">Salvar</ZyButton>
    </div>
  </form>
</ZyCard>
```

## Campos em Grid

Para formularios com campos relacionados lado a lado:

```html
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
  <ZyInput v-model="form.first_name" label="Nome" />
  <ZyInput v-model="form.last_name" label="Sobrenome" />
</div>
```

## Validacao

### Client-side (Feedback imediato)

```typescript
import { z } from 'zod'

const cameraSchema = z.object({
  name: z.string().min(3, 'Minimo 3 caracteres').max(255),
  rtsp_url: z.string().url().startsWith('rtsp://', 'URL deve iniciar com rtsp://'),
})
```

### Server-side (Laravel)

Erros do Inertia sao mapeados automaticamente para `form.errors.*`.

### Visual de Erro

```html
<!-- Input com erro -->
<div>
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
    Nome da Camera
  </label>
  <input class="w-full rounded-lg border-danger-500 focus:ring-danger-500 ..." />
  <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
    O nome e obrigatorio
  </p>
</div>
```

## Campo Obrigatorio

```html
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
  Nome <span class="text-danger-500">*</span>
</label>
```

## Formularios Especiais CFTV

### Configuracao de Camera

```
Secao 1: Dados Basicos (nome, localizacao, grupo)
Secao 2: Conexao (URL RTSP, porta, protocolo)
Secao 3: Credenciais (usuario, senha)
Secao 4: Gravacao (ativar, schedule, retencao)
Secao 5: AI (ativar deteccao, modelo, sensibilidade)
```

### Configuracao de Tenant

```
Secao 1: Dados da Organizacao
Secao 2: Plano/Quota (cameras, storage, retencao)
Secao 3: Configuracoes de Seguranca
```

## Regras

1. Gap entre campos: `space-y-4`
2. Gap entre secoes: `space-y-6` + divider
3. Botoes SEMPRE no footer, alinhados a direita
4. Cancelar SEMPRE presente
5. Submit com loading state
6. Validacao visual imediata
7. Labels SEMPRE visiveis (nao apenas placeholder)
8. Max width: `max-w-2xl` para formularios
9. Formularios complexos: usar abas ou steps
