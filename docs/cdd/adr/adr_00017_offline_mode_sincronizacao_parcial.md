# ADR-00017 — Offline Mode e Sincronização Parcial

**Status:** Aprovado  
**Data:** 2026-01-21  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

Usuários mobile do ZyraVision operam em condições adversas de conectividade:
- Operadores em campo com cobertura irregular
- Áreas industriais com interferência
- Elevadores, subsolos e áreas remotas
- Transições entre redes

Em sistemas CFTV críticos, a **perda de informação devido a problemas de rede é inaceitável**. O aplicativo deve:
- Manter funcionalidade básica offline
- Sincronizar dados quando reconectar
- Nunca perder ações do usuário

---

## Decisão

Implementar **sincronização parcial e cache local** para garantir funcionalidade básica mesmo sem conectividade ativa.

---

## Estratégia de Offline

### Dados Cacheados Localmente

| Tipo de Dado | Prioridade | TTL | Tamanho Máximo |
|--------------|------------|-----|----------------|
| Lista de câmeras | Alta | 24h | 1MB |
| Configurações do usuário | Alta | ∞ | 100KB |
| Últimos alertas | Alta | 4h | 500KB |
| Thumbnails de câmeras | Média | 1h | 50MB |
| Histórico de eventos | Média | 8h | 2MB |
| Favoritos | Alta | ∞ | 100KB |

### Dados NÃO Cacheados

- Streams de vídeo ao vivo
- Gravações completas
- Dados de outros tenants
- Informações sensíveis de segurança

---

## Arquitetura de Cache

### Camadas

```
┌─────────────────────────────────────────┐
│           UI / Presentation            │
├─────────────────────────────────────────┤
│           Repository Layer             │
│  ┌───────────────┐  ┌───────────────┐  │
│  │  Remote Data  │  │  Local Cache  │  │
│  │   (API)       │  │  (SQLite)     │  │
│  └───────────────┘  └───────────────┘  │
├─────────────────────────────────────────┤
│         Sync Manager / Queue           │
└─────────────────────────────────────────┘
```

### Componentes

#### 1. Local Database (SQLite/Hive)
- Armazenamento estruturado
- Queries rápidas
- Criptografia opcional

#### 2. Sync Queue
- Ações pendentes
- Retry automático
- Conflito handling

#### 3. Network Monitor
- Estado de conectividade
- Tipo de rede (WiFi/Mobile)
- Qualidade da conexão

---

## Padrões de Sincronização

### 1. Cache-First

Para dados com alta taxa de hit:

```dart
Future<List<Camera>> getCameras() async {
  // 1. Tenta cache local
  final cached = await _localDb.getCameras();
  if (cached.isNotEmpty && !_isStale(cached)) {
    return cached;
  }
  
  // 2. Busca remoto se online
  if (await _network.isConnected) {
    final remote = await _api.getCameras();
    await _localDb.saveCameras(remote);
    return remote;
  }
  
  // 3. Retorna cache mesmo stale se offline
  return cached;
}
```

### 2. Network-First with Fallback

Para dados críticos e frequentemente atualizados:

```dart
Future<List<Alert>> getAlerts() async {
  if (await _network.isConnected) {
    try {
      final remote = await _api.getAlerts();
      await _localDb.saveAlerts(remote);
      return remote;
    } catch (e) {
      // Fallback para cache em caso de erro
      return await _localDb.getAlerts();
    }
  }
  return await _localDb.getAlerts();
}
```

### 3. Write-Behind (Offline Actions)

Para ações do usuário:

```dart
Future<void> acknowledgeAlert(String alertId) async {
  // 1. Salva ação localmente
  await _localDb.queueAction(
    Action(type: 'acknowledge', id: alertId, timestamp: DateTime.now())
  );
  
  // 2. Sincroniza se online
  if (await _network.isConnected) {
    await _syncManager.processQueue();
  }
}
```

---

## Sincronização Incremental

### Delta Sync

Em vez de baixar tudo, sincronizar apenas mudanças:

```http
GET /api/v1/cameras?updated_since=2026-01-22T10:00:00Z
```

### Response com Metadados

```json
{
  "data": [...],
  "sync": {
    "server_time": "2026-01-22T10:30:00Z",
    "has_more": false,
    "next_cursor": null
  }
}
```

---

## Resolução de Conflitos

### Estratégias

| Tipo de Dado | Estratégia | Razão |
|--------------|------------|-------|
| Configurações | Last-Write-Wins | Usuário é fonte da verdade |
| Alertas | Server-Wins | Servidor tem visão global |
| Favoritos | Merge | Preservar intenção do usuário |

### Exemplo de Merge

```dart
List<String> mergeFavorites(List<String> local, List<String> remote) {
  final Set<String> merged = {...local, ...remote};
  return merged.toList();
}
```

---

## Estados de Conectividade

### Indicadores Visuais

| Estado | Indicador | Ações Disponíveis |
|--------|-----------|-------------------|
| Online | ✅ Verde | Todas |
| Sincronizando | 🔄 Azul | Todas |
| Offline (cache válido) | 📴 Amarelo | Leitura + Ações locais |
| Offline (cache stale) | ⚠️ Laranja | Leitura limitada |
| Sem dados | ❌ Vermelho | Nenhuma |

### Notificação ao Usuário

```dart
void showConnectivityBanner(ConnectivityState state) {
  switch (state) {
    case ConnectivityState.offline:
      showBanner('Você está offline. Mostrando dados salvos.');
      break;
    case ConnectivityState.syncing:
      showBanner('Sincronizando dados...');
      break;
    case ConnectivityState.online:
      hideBanner();
      break;
  }
}
```

---

## Queue de Ações Pendentes

### Estrutura

```dart
class PendingAction {
  final String id;
  final String type;        // 'acknowledge', 'favorite', 'ptz_command'
  final Map<String, dynamic> payload;
  final DateTime createdAt;
  final int retryCount;
  final ActionStatus status; // pending, processing, failed, completed
}
```

### Processamento

```dart
Future<void> processQueue() async {
  final actions = await _localDb.getPendingActions();
  
  for (final action in actions) {
    try {
      await _api.executeAction(action);
      await _localDb.markCompleted(action.id);
    } catch (e) {
      if (action.retryCount < MAX_RETRIES) {
        await _localDb.incrementRetry(action.id);
      } else {
        await _localDb.markFailed(action.id);
        _notifyUser('Ação falhou: ${action.type}');
      }
    }
  }
}
```

---

## Limites de Storage

### Quotas por Tipo

| Tipo | Limite | Política de Eviction |
|------|--------|---------------------|
| Cache total | 100MB | LRU |
| Thumbnails | 50MB | LRU |
| Actions queue | 1000 itens | FIFO (mais antigos) |
| Eventos | 10000 itens | TTL |

### Limpeza Automática

- Executar quando storage > 80% do limite
- Priorizar dados com TTL expirado
- Manter dados essenciais (favoritos, config)

---

## Consequências

### Positivas
- Melhor UX em condições adversas
- Maior confiabilidade percebida
- Operação contínua em campo

### Negativas / Trade-offs
- Complexidade de implementação
- Uso de storage local
- Potencial inconsistência temporária

---

## Alternativas Rejeitadas

### Full Offline (Download de Gravações)
- ❌ Storage excessivo
- ❌ DRM/Segurança comprometida

### Sem Offline (Sempre Online)
- ❌ UX degradada em campo
- ❌ Perda de ações do usuário

---

## Requisitos Não Funcionais

- Cache hit rate: > 90% para dados frequentes
- Sincronização: < 5s após reconexão
- Queue processing: < 30s para ações pendentes
- Storage máximo: 100MB

---

## Referências

- Offline-First Web Apps (Google)
- Flutter Offline Packages
- SQLite Best Practices
- Conflict Resolution in Distributed Systems
