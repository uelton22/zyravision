---
name: create-feature
description: Cria um modulo/feature completo no ZyraVision Mobile com todas as 5 camadas. Use quando o usuario pedir para criar funcionalidade, modulo, CRUD completo.
---
# Criar Feature Module — ZyraVision Mobile

## Workflow (TODAS as 5 camadas)

### Camada 5 — Data Layer
1. Model: `lib/data/models/{feature}_model.dart` (json_serializable)
2. Remote DataSource: `lib/data/datasources/remote/{feature}_remote_datasource.dart`
3. Local DataSource (se cache): `lib/data/datasources/local/{feature}_local_datasource.dart`
4. Mapper: `lib/data/mappers/{feature}_mapper.dart`
5. Repository Impl: `lib/data/repositories/{feature}_repository_impl.dart`

### Camada 4 — Domain Layer
1. Entity: `lib/domain/entities/{feature}.dart` (freezed)
2. Repository Interface: `lib/domain/repositories/{feature}_repository.dart`
3. Use Cases: `lib/domain/usecases/{feature}/` (um por operacao)
4. Enums: `lib/domain/enums/{feature}_status.dart` (se necessario)

### Camada 3 — State Layer
1. Notifier: `lib/state/{feature}/{feature}_notifier.dart`
2. Providers: `lib/state/{feature}/{feature}_providers.dart`

### Camada 2 — Presentation Layer
1. ViewModel: `lib/presentation/viewmodels/{feature}_viewmodel.dart`
2. Formatters: `lib/presentation/formatters/{feature}_formatter.dart` (se necessario)

### Camada 1 — UI Layer
1. List Screen: `lib/ui/screens/{feature}/{feature}_list_screen.dart`
2. Detail Screen: `lib/ui/screens/{feature}/{feature}_detail_screen.dart`
3. Feature Widgets: `lib/ui/widgets/cards/zy_{feature}_card.dart`

### Extras
1. Rotas: adicionar em `lib/ui/router/app_router.dart`
2. Testes: `test/{layer}/{feature}/`

## Checklist
- [ ] Entity com freezed
- [ ] Model com json_serializable
- [ ] Repository interface + impl
- [ ] Use cases para cada operacao
- [ ] Notifier + Providers
- [ ] ViewModel
- [ ] Screens (list + detail)
- [ ] Rotas configuradas
- [ ] Testes unitarios
