# ZyModal — Design System ZyraVision

## Estrutura

```html
<!-- Overlay -->
<div class="fixed inset-0 z-50">
  <!-- Backdrop -->
  <div class="fixed inset-0 bg-black/50 dark:bg-black/70 transition-opacity" />

  <!-- Container -->
  <div class="fixed inset-0 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">

      <!-- Modal -->
      <div class="relative w-full max-w-lg bg-white dark:bg-gray-800
                  rounded-xl shadow-xl border border-gray-200 dark:border-gray-700
                  transition-all">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700
                    flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Titulo do Modal
          </h3>
          <button class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-4">
          <slot />
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700
                    flex items-center justify-end gap-3 bg-gray-50 dark:bg-gray-800/50
                    rounded-b-xl">
          <ZyButton variant="secondary" @click="close">Cancelar</ZyButton>
          <ZyButton variant="primary" @click="confirm">Confirmar</ZyButton>
        </div>
      </div>
    </div>
  </div>
</div>
```

## Tamanhos

| Tamanho | Max Width | Uso |
|---------|-----------|-----|
| sm | max-w-sm (384px) | Confirmacoes simples |
| md | max-w-lg (512px) | Formularios, detalhes |
| lg | max-w-2xl (672px) | Formularios complexos |
| xl | max-w-4xl (896px) | Tabelas, previews |
| full | max-w-full mx-4 | Player de video fullscreen |

## Variantes

### ZyConfirmDialog (Confirmacao de Exclusao)

```html
<ZyModal size="sm">
  <div class="text-center py-4">
    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-danger-50 dark:bg-danger-500/10 flex items-center justify-center">
      <ExclamationTriangleIcon class="h-6 w-6 text-danger-500" />
    </div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Excluir Camera?</h3>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
      Esta acao nao pode ser desfeita. Todas as gravacoes serao removidas.
    </p>
  </div>
  <template #footer>
    <ZyButton variant="secondary" @click="close">Cancelar</ZyButton>
    <ZyButton variant="danger" @click="confirm">Excluir</ZyButton>
  </template>
</ZyModal>
```

### ZySlideOver (Painel Lateral)

```html
<div class="fixed inset-y-0 right-0 z-50 w-full max-w-md">
  <div class="h-full bg-white dark:bg-gray-800 shadow-xl flex flex-col">
    <!-- Header -->
    <!-- Body com scroll -->
    <!-- Footer fixo -->
  </div>
</div>
```

## Props

```typescript
interface ModalProps {
  show: boolean
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  closeable?: boolean
  closeOnEscape?: boolean
  closeOnClickOutside?: boolean
  title?: string
}
```

## Animacao

```
Entrada: opacity-0 scale-95 -> opacity-100 scale-100 (duration-200)
Saida:   opacity-100 scale-100 -> opacity-0 scale-95 (duration-150)
Backdrop: opacity-0 -> opacity-100 (duration-200)
```

## Regras
- Fechar com ESC e click no backdrop (configuravel)
- Focus trap ativo quando modal aberto
- Scroll do body bloqueado quando aberto
- Maximo 1 nivel de modal (nao empilhar)
- Botao de fechar SEMPRE visivel
- Acoes destrutivas: confirmar com ZyConfirmDialog
