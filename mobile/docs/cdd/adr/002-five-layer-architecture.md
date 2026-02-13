# ADR-002: Arquitetura 5 Camadas

## Status
Aceito

## Contexto
Precisamos de uma arquitetura que:
- Separe responsabilidades claramente
- Permita testes unitarios sem dependencias externas
- Facilite offline-first com troca de datasources
- Escale para 40+ telas planejadas

Opcoes consideradas:
1. **Clean Architecture (3 camadas)** — Presentation, Domain, Data
2. **5 Camadas** — UI, Presentation, State, Domain, Data
3. **BLoC Pattern** — UI, BLoC, Repository, Data
4. **MVC/MVVM simples**

## Decisao
Adotar **Arquitetura 5 Camadas**: UI → Presentation → State → Domain ← Data

## Justificativas
- **Separacao UI vs State**: UI pura (widgets) e State (Riverpod) ficam desacoplados
- **Presentation layer**: ViewModels e formatadores facilitam transformacoes UI-especificas
- **Domain independente**: Inversao de dependencia garante testabilidade
- **Data isolada**: Troca facil entre Remote/Local datasources para offline
- **Escalabilidade**: Cada camada evolui independentemente

## Estrutura

```
UI → Presentation → State → Domain ← Data
                                ↑
                              Core
```

- **UI**: Widgets Flutter puros, Zy* Design System, GoRouter
- **Presentation**: ViewModels, formatadores, mapeamento estado→UI
- **State**: Riverpod providers, AsyncNotifiers, estado global
- **Domain**: Entities (freezed), Use Cases, Repository interfaces
- **Data**: Repository impls, Models (json_serializable), DataSources, Mappers
- **Core**: Infraestrutura compartilhada (DI, network, storage, auth)

## Regra de Dependencia
- Domain NAO depende de nenhuma outra camada
- Data IMPLEMENTA interfaces definidas em Domain
- Cada camada so depende das camadas abaixo dela
- Core pode ser importado por qualquer camada

## Consequencias
- Mais arquivos por feature (~8-10 vs ~4-5 em MVVM simples)
- Curva de aprendizado para novos devs
- Boilerplate de mappers e interfaces (mitigado por code generation)
- Testabilidade excelente em todas as camadas
