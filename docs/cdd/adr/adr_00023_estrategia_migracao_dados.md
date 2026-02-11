# ADR-00023 — Estratégia de Migração de Dados

**Status:** Aprovado  
**Data:** 2026-01-22  
**Contexto:** ZyraVision — Plataforma Profissional de Monitoramento CFTV

---

## Contexto

O ZyraVision precisará realizar migrações de dados em diversos cenários:
- Atualizações de versão do schema do banco
- Migração de clientes de sistemas legados
- Migração entre ambientes (on-premise → cloud)
- Merge/split de tenants
- Migração de storage (local → S3)

Em sistemas CFTV, migrações são especialmente críticas devido ao:
- Volume massivo de dados de vídeo
- Operação contínua 24/7 (zero downtime desejado)
- Requisitos de compliance (não perder dados)
- Integridade de metadados indexados

---

## Decisão

Implementar uma **estratégia de migração estruturada** com:
- Schema migrations versionadas
- Data migrations separadas
- Zero-downtime deployments
- Rollback automatizado
- Validação de integridade

---

## Tipos de Migração

### 1. Schema Migrations (DDL)

Mudanças estruturais no banco de dados.

```php
// database/migrations/2026_01_22_000001_add_resolution_to_cameras.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('resolution', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('resolution');
        });
    }
};
```

### 2. Data Migrations

Transformações de dados sem mudança de schema.

```php
// database/data-migrations/2026_01_22_migrate_legacy_camera_status.php
<?php

use App\Models\Camera;
use Illuminate\Support\Facades\DB;

return new class extends DataMigration
{
    public function up(): void
    {
        // Migra status legado para novo formato
        DB::table('cameras')
            ->where('status', 'active')
            ->update(['status' => 'online']);
            
        DB::table('cameras')
            ->where('status', 'inactive')
            ->update(['status' => 'offline']);
    }

    public function down(): void
    {
        DB::table('cameras')
            ->where('status', 'online')
            ->update(['status' => 'active']);
            
        DB::table('cameras')
            ->where('status', 'offline')
            ->update(['status' => 'inactive']);
    }
    
    public function estimate(): int
    {
        return Camera::count(); // Número de registros afetados
    }
};
```

### 3. Storage Migrations

Movimentação de arquivos de vídeo entre storages.

```php
// app/Jobs/StorageMigrationJob.php
<?php

class StorageMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public string $tenantId,
        public string $sourceStorage,
        public string $targetStorage
    ) {}
    
    public function handle(): void
    {
        $recordings = Recording::where('tenant_id', $this->tenantId)
            ->where('storage_type', $this->sourceStorage)
            ->cursor();
            
        foreach ($recordings as $recording) {
            // Copiar arquivo
            Storage::disk($this->targetStorage)
                ->put($recording->path, 
                    Storage::disk($this->sourceStorage)->get($recording->path));
            
            // Atualizar registro
            $recording->update([
                'storage_type' => $this->targetStorage,
                'migrated_at' => now(),
            ]);
            
            // Opcional: deletar original após validação
        }
    }
}
```

---

## Padrões de Zero-Downtime

### 1. Expand and Contract

Para mudanças de schema que afetam código existente:

```
Fase 1 (Expand): Adicionar nova coluna/tabela mantendo antiga
    ↓
Fase 2 (Migrate): Copiar/transformar dados
    ↓
Fase 3 (Switch): Atualizar código para usar nova estrutura
    ↓
Fase 4 (Contract): Remover estrutura antiga
```

#### Exemplo: Renomear coluna

```php
// Fase 1: Adicionar nova coluna
Schema::table('cameras', function (Blueprint $table) {
    $table->string('stream_url')->nullable(); // Nova coluna
});

// Fase 2: Copiar dados
DB::statement('UPDATE cameras SET stream_url = rtsp_url');

// Fase 3: Deploy código que usa stream_url

// Fase 4: Remover coluna antiga (próximo deploy)
Schema::table('cameras', function (Blueprint $table) {
    $table->dropColumn('rtsp_url');
});
```

### 2. Feature Flags

```php
// Durante migração
if (Feature::active('new_camera_schema')) {
    return $camera->stream_url;
} else {
    return $camera->rtsp_url;
}
```

### 3. Blue-Green Migrations

```
┌─────────────────┐     ┌─────────────────┐
│   Database V1   │────>│   Database V2   │
│   (Blue - Old)  │ sync│   (Green - New) │
└─────────────────┘     └─────────────────┘
         ↑                       ↑
         │                       │
    ┌─────────┐             ┌─────────┐
    │  App V1 │             │  App V2 │
    └─────────┘             └─────────┘
```

---

## Migração de Sistema Legado

### Processo de Onboarding de Cliente

```
1. Export de dados do sistema legado
    ↓
2. Transformação para formato ZyraVision
    ↓
3. Validação de integridade
    ↓
4. Import em ambiente de staging
    ↓
5. Testes de aceitação do cliente
    ↓
6. Migração para produção
    ↓
7. Período de operação paralela
    ↓
8. Cutover final
```

### Suporte a Formatos Legados

| Sistema Origem | Formato | Suporte |
|----------------|---------|---------|
| Milestone XProtect | SQL Server + Files | ✅ |
| Genetec | SQL + Archiver | ⚠️ Parcial |
| Hikvision | SQLite + Files | ✅ |
| ZoneMinder | MySQL | ✅ |
| Shinobi | MongoDB | ✅ |

### Script de Migração Exemplo

```php
// app/Console/Commands/MigrateLegacySystem.php
<?php

class MigrateLegacySystem extends Command
{
    protected $signature = 'migrate:legacy 
        {source : Path to export file}
        {tenant : Target tenant ID}
        {--dry-run : Simulate without writing}';
    
    public function handle(): int
    {
        $source = $this->argument('source');
        $tenant = Tenant::findOrFail($this->argument('tenant'));
        
        $this->info("Parsing legacy export...");
        $data = $this->parseLegacyExport($source);
        
        $this->info("Found {$data['cameras']->count()} cameras");
        $this->info("Found {$data['recordings']->count()} recordings");
        
        if ($this->option('dry-run')) {
            $this->warn("Dry run - no changes made");
            return 0;
        }
        
        DB::transaction(function () use ($data, $tenant) {
            foreach ($data['cameras'] as $legacyCamera) {
                Camera::create([
                    'tenant_id' => $tenant->id,
                    'name' => $legacyCamera['name'],
                    'stream_url' => $this->transformUrl($legacyCamera['url']),
                    'legacy_id' => $legacyCamera['id'],
                ]);
            }
        });
        
        $this->info("Migration completed!");
        return 0;
    }
}
```

---

## Migração de Storage

### Estratégias por Volume

| Volume | Estratégia | Tempo Estimado |
|--------|------------|----------------|
| < 100 GB | Online, síncrono | < 1h |
| 100 GB - 1 TB | Background, assíncrono | 4-24h |
| > 1 TB | Incremental, multi-fase | Dias/Semanas |

### Migração Incremental

```yaml
# config/storage-migration.yml
migration:
  source: local
  target: s3
  
  phases:
    - name: cold_data
      filter: "older_than:30d"
      priority: 1
      
    - name: warm_data
      filter: "older_than:7d"
      priority: 2
      
    - name: hot_data
      filter: "newer_than:7d"
      priority: 3
      sync_mode: continuous

  options:
    bandwidth_limit: 100Mbps
    parallel_transfers: 10
    verify_checksum: true
    delete_source_after: 7d
```

### Validação de Integridade

```php
class StorageMigrationValidator
{
    public function validate(Recording $recording): ValidationResult
    {
        $sourceHash = Storage::disk($recording->original_storage)
            ->checksum($recording->path, 'sha256');
            
        $targetHash = Storage::disk($recording->current_storage)
            ->checksum($recording->path, 'sha256');
            
        return new ValidationResult(
            valid: $sourceHash === $targetHash,
            sourceHash: $sourceHash,
            targetHash: $targetHash
        );
    }
}
```

---

## Rollback

### Estratégias de Rollback

| Tipo de Migração | Estratégia | Tempo de Rollback |
|------------------|------------|-------------------|
| Schema (reversível) | `down()` migration | < 1 min |
| Schema (irreversível) | Restore from backup | 1-4h |
| Data | Reverse data migration | Variável |
| Storage | Manter fonte até validação | Imediato |

### Checklist Pré-Migração

```markdown
## Checklist de Migração - [DATA]

### Preparação
- [ ] Backup full do banco realizado
- [ ] Backup de configurações realizado
- [ ] Teste de restore validado
- [ ] Rollback plan documentado
- [ ] Time de suporte de plantão

### Validação Pré
- [ ] Contagem de registros por tabela
- [ ] Soma de checksums críticos
- [ ] Teste de conectividade

### Execução
- [ ] Migração executada
- [ ] Logs verificados

### Validação Pós
- [ ] Contagem de registros conferida
- [ ] Aplicação funcionando
- [ ] Testes de smoke passando
- [ ] Métricas normais

### Finalização
- [ ] Documentação atualizada
- [ ] Backup pós-migração
- [ ] Comunicação aos stakeholders
```

---

## Monitoramento de Migrações

### Métricas

```promql
# Progresso de migração
migration_records_processed / migration_records_total * 100

# Taxa de migração
rate(migration_records_processed[5m])

# Erros de migração
sum(migration_errors_total) by (migration_id)
```

### Logs Estruturados

```json
{
  "event": "migration_record_processed",
  "migration_id": "2026_01_22_camera_status",
  "record_id": "cam_001",
  "status": "success",
  "duration_ms": 15,
  "timestamp": "2026-01-22T10:30:00Z"
}
```

---

## Consequências

### Positivas
- Migrações seguras e confiáveis
- Zero-downtime para maioria dos casos
- Rollback garantido
- Auditoria completa

### Negativas / Trade-offs
- Complexidade adicional
- Migrações mais lentas (fases múltiplas)
- Storage temporário durante migração

---

## Alternativas Rejeitadas

### Downtime para Migração
- ❌ Inaceitável para sistema 24/7
- ❌ Perda de gravações durante janela

### Migração Manual
- ❌ Propenso a erros
- ❌ Não escalável
- ❌ Sem auditoria

---

## Referências

- Laravel Migrations Best Practices
- PostgreSQL Zero-Downtime Migrations
- AWS Database Migration Service
- Stripe Expand/Contract Pattern
