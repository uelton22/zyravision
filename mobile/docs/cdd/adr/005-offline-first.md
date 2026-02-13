# ADR-005: Estrategia Offline-First

## Status
Aceito

## Contexto
Operadores de CFTV podem estar em areas com conectividade instavel.
O app precisa funcionar com dados em cache quando sem internet.

## Decisao
Adotar **Cache-First com Sync Incremental** usando Hive como storage local.

## Estrategia por Feature

| Feature | Offline | Estrategia |
|---------|---------|------------|
| Dashboard | Parcial | Cache stats + timestamp "atualizado ha X min" |
| Lista cameras | Sim | Cache completo, atualiza quando online |
| Detalhes camera | Sim | Cache por camera_id |
| Live view | Nao | Requer rede (streaming) |
| Gravacoes (lista) | Sim | Cache de metadados |
| Playback | Nao | Requer rede (download segmentos) |
| Alertas | Sim | Cache + sync quando reconectar |
| Configuracoes | Sim | Local-first, sync quando online |
| Login | Nao | Requer validacao servidor |

## Implementacao

```dart
// Padrao Repository com fallback
Future<List<Camera>> getCameras() async {
  try {
    // 1. Tenta remoto
    final remote = await _remote.getCameras();
    // 2. Atualiza cache
    await _local.cacheCameras(remote);
    return remote;
  } on NetworkException {
    // 3. Retorna cache
    return _local.getCachedCameras();
  }
}
```

### Hive Boxes

| Box | Tipo | Conteudo | Encriptado |
|-----|------|----------|-----------|
| cameras | LazyBox | Lista de cameras | Nao |
| recordings | LazyBox | Metadados gravacoes | Nao |
| alerts | LazyBox | Ultimos 100 alertas | Nao |
| dashboard | Box | Stats do dashboard | Nao |
| settings | Box | Preferencias | Nao |
| auth | Box | Tokens, user | Sim |

## Sync Incremental

```
App reconecta →
  1. GET /sync/status?last_sync={timestamp}
  2. Recebe {cameras_updated: [...], alerts_new: [...]}
  3. Merge com cache local
  4. Atualiza last_sync timestamp
```

## Consequencias
- Dados offline podem estar desatualizados (mostrar timestamp)
- Conflitos de sync precisam de estrategia (server wins)
- Hive adiciona ~200KB ao app
- Cache precisa de limpeza periodica (TTL 7 dias)
