---
name: create-migration
description: Cria migrations de banco de dados padronizadas para o ZyraVision (PostgreSQL, UUID, multi-tenant). Use quando o usuario pedir para criar tabela, migration, alterar banco de dados, ou adicionar coluna.
---

# Criar Migration — ZyraVision

## Comando

```bash
php artisan make:migration create_{table}_table
php artisan make:migration add_{column}_to_{table}_table
```

## Template — Nova Tabela

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            // Primary key UUID
            $table->uuid('id')->primary();

            // Tenant (OBRIGATORIO em tabelas multi-tenant)
            $table->foreignUuid('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // Campos do dominio
            $table->string('name');
            $table->string('rtsp_url');
            $table->string('status')->default('inactive');
            $table->jsonb('metadata')->nullable();
            $table->text('description')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
```

## Regras Obrigatorias

1. **UUID** como primary key — NUNCA auto-increment
2. **tenant_id** em TODA tabela multi-tenant
3. **timestamps()** em TODA tabela
4. **softDeletes()** em entidades criticas
5. **down()** SEMPRE funcional
6. **Indices compostos** com tenant_id primeiro
7. **JSONB** para dados flexiveis (NAO JSON)
8. **Foreign keys** com cascade appropriado

## Tipos PostgreSQL Preferidos

| Dado | Tipo |
|------|------|
| ID | uuid |
| Texto curto | string (varchar 255) |
| Texto longo | text |
| Dados flexiveis | jsonb |
| Dinheiro | decimal(10,2) |
| Booleano | boolean |
| Data/hora | timestamp |
| Tamanho (bytes) | bigInteger |
| Enum | string + PHP Enum |

## Particionamento (tabelas grandes)

Para `recordings` e `ai_events`, considerar particionamento por data:

```sql
-- Particionamento por range de data
CREATE TABLE recordings (
    ...
) PARTITION BY RANGE (created_at);
```

## Checklist

- [ ] UUID primary key
- [ ] tenant_id com foreign key
- [ ] timestamps e softDeletes
- [ ] Indices compostos
- [ ] down() implementado
- [ ] Tipos PostgreSQL corretos
