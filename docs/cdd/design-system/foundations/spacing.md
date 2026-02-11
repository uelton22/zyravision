# Espacamento — ZyraVision Design System

## Escala Base

Escala de 4px (0.25rem), seguindo Tailwind default:

| Token | Tailwind | Pixels | Rem | Uso |
|-------|----------|--------|-----|-----|
| space-0 | p-0 / m-0 | 0 | 0 | Reset |
| space-1 | p-1 / m-1 | 4px | 0.25rem | Micro espacamento |
| space-2 | p-2 / m-2 | 8px | 0.5rem | Entre icones e texto |
| space-3 | p-3 / m-3 | 12px | 0.75rem | Padding interno compacto |
| space-4 | p-4 / m-4 | 16px | 1rem | Padding padrao de cards |
| space-5 | p-5 / m-5 | 20px | 1.25rem | Espacamento medio |
| space-6 | p-6 / m-6 | 24px | 1.5rem | Padding de secoes |
| space-8 | p-8 / m-8 | 32px | 2rem | Gap entre secoes |
| space-10 | p-10 / m-10 | 40px | 2.5rem | Espacamento grande |
| space-12 | p-12 / m-12 | 48px | 3rem | Margin entre blocos |
| space-16 | p-16 / m-16 | 64px | 4rem | Espacamento de pagina |

## Espacamento de Componentes

### Cards
```
Padding interno: p-4 (16px) — mobile
                 p-6 (24px) — desktop
Gap entre cards: gap-4 (16px)
                 gap-6 (24px) — desktop
```

### Tabelas
```
Padding de celula: px-4 py-3 (16px x 12px)
Header: px-4 py-3 com font-medium
Gap entre colunas: automatico
```

### Formularios
```
Gap entre campos: space-y-4 (16px)
Label para input: mb-1.5 (6px)
Grupo de botoes: gap-3 (12px)
Secoes: space-y-6 (24px)
```

### Modais
```
Padding: p-6 (24px)
Gap header/body/footer: space-y-4 (16px)
```

### Sidebar
```
Largura: w-64 (256px) — expandida
         w-16 (64px) — colapsada
Padding items: px-3 py-2
Gap entre items: space-y-1
```

### Header
```
Altura: h-16 (64px)
Padding: px-4 md:px-6
```

## Layout Grid

### Pagina Principal
```
Sidebar (256px) + Content (flex-1)
Content padding: p-6 md:p-8
```

### Grid de Cameras (Mosaico)
```
1 coluna: mobile
2 colunas: sm (640px+)
3 colunas: md (768px+)
4 colunas: lg (1024px+)
6 colunas: xl (1280px+)
Gap: gap-4
```

### Grid de Dashboard
```
1 coluna: mobile
2 colunas: md (768px+)
3-4 colunas: lg (1024px+)
Gap: gap-6
```

## Container

```
max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
```

## Regras

- SEMPRE usar escala Tailwind (nao valores arbitrarios)
- Valores preferidos: 2, 3, 4, 6, 8, 12, 16
- Consistencia: mesmo espacamento para mesma hierarquia
- Mobile: espacamentos menores (p-4 -> p-3)
