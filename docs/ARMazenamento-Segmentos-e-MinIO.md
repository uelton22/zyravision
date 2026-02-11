# Armazenamento de segmentos e MinIO — Comportamento do sistema

## Por que ainda vejo e consigo reproduzir vídeos depois de apagar tudo no MinIO?

O ZyraVision usa **duas fontes de verdade** para os segmentos de gravação:

1. **Banco de dados** (`recording_segments`) — lista de segmentos, tamanhos, datas.
2. **Arquivos de mídia** — no disco local (gravação/MediaMTX), no MinIO (nuvem) e em **cache local**.

Quando você apaga os arquivos **só no MinIO**, acontece o seguinte:

### 1. Reprodução continua funcionando (cache local)

- Na **primeira vez** que um segmento é reproduzido a partir do MinIO, o sistema **baixa o arquivo** e guarda em **cache no disco** do servidor:
  - Caminho: `storage/app/segment_cache/{camera_id}/{filename}`.
- Nas **próximas reproduções**, o sistema **não consulta o MinIO**: ele verifica se o arquivo já existe no cache e, se existir (e o tamanho bater), **serve direto do cache**.
- Por isso, mesmo depois de apagar tudo no MinIO, os vídeos continuam tocando: eles estão sendo servidos a partir desse **cache local**, não do MinIO.

Resumo: **sim, é uma funcionalidade de cache**. O cache existe para evitar baixar do MinIO toda vez que alguém assiste ao mesmo segmento.

### 2. Contagem de segmentos e GB continua a mesma

- A **contagem de segmentos** (ex.: 388) e o **tamanho total** (ex.: 15,07 GB) vêm do **banco de dados** (`recording_segments`), não de uma consulta ao MinIO.
- Cada linha em `recording_segments` representa um segmento (com `file_size_bytes`, `remote_path`, etc.).
- Quando você apaga arquivos **apenas no MinIO**, as **linhas da tabela não são apagadas**. Por isso a interface continua mostrando 388 segmentos e 15,07 GB.

Ou seja: o sistema **não “espelha”** automaticamente o que existe no MinIO; ele confia no que está registrado no banco.

---

## Onde está cada coisa

| O quê                | Onde fica |
|----------------------|-----------|
| Lista de segmentos   | Banco: tabela `recording_segments` |
| Contagem e GB        | Soma/contagem a partir de `recording_segments` |
| Arquivos no MinIO    | Bucket no MinIO (quando você apagou) |
| Cache de reprodução  | Disco do servidor: `storage/app/segment_cache/{camera_id}/` |

---

## Como alinhar o sistema depois de apagar no MinIO

Se você quer que a aplicação **pare de mostrar** esses segmentos e **pare de usar** o cache deles:

1. **Limpar o cache de segmentos** (para parar de servir os vídeos do disco):
   ```bash
   # No host (ou no container da aplicação)
   rm -rf storage/app/segment_cache/*
   ```

2. **Ajustar o banco** (para a contagem e os GB refletirem a realidade):
   - Ou **apagar** as linhas correspondentes em `recording_segments` (por câmera, por data, ou em lote).
   - Ou criar um **comando/job** que marque como “indisponível” os segmentos cujo arquivo não existe mais no MinIO (e aí a aplicação pode contar só os “disponíveis”).

Se quiser, podemos definir juntos um comando Artisan (ex.: `segmentos:limpar-orfaos`) que:
- opcionalmente limpe o cache, e
- remova ou atualize registros em `recording_segments` quando o arquivo não existir mais no backend (MinIO).

---

## Resumo em uma frase

**Os vídeos ainda tocam porque estão no cache em disco; os números (segmentos e GB) continuam os mesmos porque vêm do banco, que não foi alterado quando você apagou os arquivos no MinIO.**
