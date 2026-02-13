---
name: create-screen
description: Cria uma nova tela/screen no ZyraVision Mobile seguindo a arquitetura 5 camadas. Use quando o usuario pedir para criar tela, pagina, view.
---
# Criar Screen — ZyraVision Mobile

## Workflow (ordem EXATA)

### Step 1: Screen Widget (UI Layer)
Criar em `lib/ui/screens/{feature}/{feature}_screen.dart`:
- StatelessWidget ou ConsumerWidget (Riverpod)
- Usar ZyScaffold com ZyAppBar
- Composicao de widgets Zy*
- Responsivo (phone + tablet)

### Step 2: ViewModel (Presentation Layer)
Criar em `lib/presentation/viewmodels/{feature}_viewmodel.dart`:
- Mapeia estado para UI
- Formatacao de dados para exibicao
- Tratamento de erros para mensagens

### Step 3: State/Providers (State Layer)
Criar em `lib/state/{feature}/`:
- `{feature}_notifier.dart` — AsyncNotifier
- `{feature}_providers.dart` — Riverpod providers

### Step 4: Use Case (Domain Layer)
Criar em `lib/domain/usecases/{feature}/`:
- Um use case por operacao
- Injeta repository interface
- Retorna entidade de dominio

### Step 5: Entity (Domain Layer)
Se nova entidade necessaria em `lib/domain/entities/`:
- Usar freezed para imutabilidade
- Nenhuma dependencia de Data layer

### Step 6: Repository Implementation (Data Layer)
Se novo endpoint em `lib/data/repositories/`:
- Implementa interface do Domain
- Usa datasource remote/local
- Mapeia Model → Entity

## Template Screen

```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class FeatureScreen extends ConsumerWidget {
  const FeatureScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(featureProvider);

    return ZyScaffold(
      appBar: ZyAppBar(title: 'Feature'),
      body: state.when(
        data: (data) => _buildContent(data),
        loading: () => const ZyLoadingIndicator(),
        error: (e, _) => ZyEmptyState(message: e.toString()),
      ),
    );
  }
}
```

## Checklist
- [ ] Screen em lib/ui/screens/{feature}/
- [ ] ConsumerWidget com ref.watch
- [ ] Usa componentes Zy*
- [ ] ViewModel em lib/presentation/
- [ ] Providers em lib/state/
- [ ] Use case em lib/domain/usecases/
- [ ] Repository em lib/data/repositories/
- [ ] Responsivo (phone + tablet)
- [ ] Dark mode suportado
