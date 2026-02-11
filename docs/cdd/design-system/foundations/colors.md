# Paleta de Cores — ZyraVision Design System

## Filosofia

A paleta do ZyraVision e inspirada em sistemas de monitoramento profissional. Tons escuros dominam para conforto visual em operacoes 24/7, com acentos de cor para indicar status e acoes.

## Cores Primarias (Brand)

```
primary-50:  #EEF2FF   (bg hover sutil)
primary-100: #E0E7FF   (bg selecionado)
primary-200: #C7D2FE   (borda focus)
primary-300: #A5B4FC   (texto secundario)
primary-400: #818CF8   (icones)
primary-500: #6366F1   (botao hover)
primary-600: #4F46E5   (botao primario — COR PRINCIPAL)
primary-700: #4338CA   (botao pressed)
primary-800: #3730A3   (texto destaque)
primary-900: #312E81   (bg forte)
primary-950: #1E1B4B   (bg mais forte)
```

## Cores Neutras (Gray)

```
gray-50:  #F9FAFB   (bg pagina light)
gray-100: #F3F4F6   (bg card light)
gray-200: #E5E7EB   (bordas light)
gray-300: #D1D5DB   (bordas, dividers)
gray-400: #9CA3AF   (texto placeholder)
gray-500: #6B7280   (texto secundario)
gray-600: #4B5563   (texto body)
gray-700: #374151   (texto forte)
gray-800: #1F2937   (bg card dark)
gray-900: #111827   (bg pagina dark)
gray-950: #030712   (bg mais escuro)
```

## Cores de Status

### Sucesso (Verde)
```
success-50:  #ECFDF5
success-100: #D1FAE5
success-500: #10B981   (icone/badge)
success-600: #059669   (botao)
success-700: #047857   (texto)
```
Uso: Camera online, stream ativo, acao concluida

### Perigo (Vermelho)
```
danger-50:  #FEF2F2
danger-100: #FEE2E2
danger-500: #EF4444   (icone/badge)
danger-600: #DC2626   (botao)
danger-700: #B91C1C   (texto)
```
Uso: Camera offline, erro, acao destrutiva, alerta critico

### Alerta (Amarelo)
```
warning-50:  #FFFBEB
warning-100: #FEF3C7
warning-500: #F59E0B   (icone/badge)
warning-600: #D97706   (botao)
warning-700: #B45309   (texto)
```
Uso: Camera com problema, disco quase cheio, alerta medio

### Info (Azul)
```
info-50:  #EFF6FF
info-100: #DBEAFE
info-500: #3B82F6   (icone/badge)
info-600: #2563EB   (botao)
info-700: #1D4ED8   (texto)
```
Uso: Informacao, notificacao, estado neutro

## Cores Especiais CFTV

```
stream-live:     #EF4444  (vermelho — indicador LIVE)
stream-recording:#EF4444  (vermelho — REC pulsante)
stream-offline:  #6B7280  (cinza — offline)
stream-buffering:#F59E0B  (amarelo — carregando)
ptz-active:      #8B5CF6  (roxo — PTZ em uso)
ai-detection:    #06B6D4  (ciano — deteccao IA)
```

## Tailwind Config

```typescript
// tailwind.config.ts
export default {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#EEF2FF', 100: '#E0E7FF', 200: '#C7D2FE',
          300: '#A5B4FC', 400: '#818CF8', 500: '#6366F1',
          600: '#4F46E5', 700: '#4338CA', 800: '#3730A3',
          900: '#312E81', 950: '#1E1B4B',
        },
        success: {
          50: '#ECFDF5', 100: '#D1FAE5', 500: '#10B981',
          600: '#059669', 700: '#047857',
        },
        danger: {
          50: '#FEF2F2', 100: '#FEE2E2', 500: '#EF4444',
          600: '#DC2626', 700: '#B91C1C',
        },
        warning: {
          50: '#FFFBEB', 100: '#FEF3C7', 500: '#F59E0B',
          600: '#D97706', 700: '#B45309',
        },
        info: {
          50: '#EFF6FF', 100: '#DBEAFE', 500: '#3B82F6',
          600: '#2563EB', 700: '#1D4ED8',
        },
      },
    },
  },
}
```

## Uso de Cores — Guia Rapido

| Contexto | Light Mode | Dark Mode |
|----------|-----------|-----------|
| Fundo pagina | gray-50 | gray-900 |
| Fundo card | white | gray-800 |
| Fundo sidebar | white | gray-950 |
| Texto principal | gray-700 | gray-200 |
| Texto secundario | gray-500 | gray-400 |
| Borda | gray-200 | gray-700 |
| Botao primario | primary-600 | primary-500 |
| Link | primary-600 | primary-400 |
| Divider | gray-200 | gray-700 |

## Contraste Minimo

- Texto sobre fundo: ratio 4.5:1 (WCAG AA)
- Texto grande: ratio 3:1
- Icones e indicadores: ratio 3:1
