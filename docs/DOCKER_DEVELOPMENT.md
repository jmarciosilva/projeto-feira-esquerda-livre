# Ambiente de Desenvolvimento com Docker

Ambiente local completo da **Feira Esquerda Livre** em containers. Dispensa
Laragon, XAMPP, PHP, MySQL, Composer e Node instalados no Windows.

> **Este ambiente é exclusivamente local.** A produção não usa Docker e não foi
> alterada: servidor, deploy, domínio, banco, SMTP real, Mercado Pago e Melhor
> Envio permanecem exatamente como estavam.

---

## Arquitetura

```
Docker Compose  (rede "fel")
│
├── app          PHP 8.3.33 FPM + Composer 2.8.12        (interno :9000)
├── nginx        Nginx 1.27-alpine                       :80    -> http://localhost
├── mysql        MySQL 8.4.11                            :3306
├── phpmyadmin   phpMyAdmin 5.2-apache                   :8081  -> http://localhost:8081
├── redis        Redis 7.4.11-alpine                     :6380  (interno 6379)
├── node         Node 22 + Vite 7.3.5                    :5173  -> http://localhost:5173
├── queue        Laravel Queue Worker (mesma imagem do app)
└── mailpit      Mailpit v1.21                           :8025 (web) / :1025 (SMTP)
```

Nenhuma imagem usa a tag `latest`.

### Versões

| Componente | Versão |
|---|---|
| PHP | 8.3.33 (FPM, non-thread-safe) |
| Laravel | 12.65.0 |
| Composer | 2.8.12 |
| Node | 22 (bookworm-slim) |
| Vite | 7.3.5 |
| MySQL | 8.4.11 |
| phpMyAdmin | 5.2 (Apache) |
| Redis | 7.4.11 |
| Nginx | 1.27 (alpine) |

### Extensões PHP instaladas

`bcmath` · `exif` · `gd` (freetype/jpeg/webp) · `intl` · `opcache` · `pcntl` ·
`pdo_mysql` · `redis` · `zip`

Já vêm compiladas na imagem base: `mbstring`, `fileinfo`, `ctype`, `curl`,
`dom`, `json`, `openssl`, `pdo_sqlite` (usada pelos testes).

---

## Portas

| Serviço | Host | Container | URL |
|---|---|---|---|
| Aplicação (Nginx) | 80 | 80 | http://localhost |
| phpMyAdmin | 8081 | 80 | http://localhost:8081 |
| Vite | 5173 | 5173 | http://localhost:5173 |
| Mailpit (web) | 8025 | 8025 | http://localhost:8025 |
| Mailpit (SMTP) | 1025 | 1025 | — |
| MySQL | 3306 | 3306 | — |
| Redis | **6380** | 6379 | — |

**Redis publicado em 6380** porque a porta 6379 do host costuma estar ocupada
pelo serviço Memurai do Windows. Dentro da rede Docker o Redis continua em
`redis:6379` — é apenas o mapeamento externo que muda.

Todas as portas do host são configuráveis pelo `.env`:

```env
DOCKER_HTTP_PORT=80
DOCKER_MYSQL_PORT=3306
DOCKER_PHPMYADMIN_PORT=8081
DOCKER_REDIS_PORT=6380
DOCKER_VITE_PORT=5173
DOCKER_MAILPIT_HTTP_PORT=8025
DOCKER_MAILPIT_SMTP_PORT=1025
```

Se uma porta estiver ocupada, altere o valor e rode `docker compose up -d`.

---

## Volumes

| Volume | Conteúdo | Motivo |
|---|---|---|
| `mysql-data` | `/var/lib/mysql` | Persiste o banco entre `down` e `up` |
| `redis-data` | `/data` | Persistência AOF do Redis |
| `vendor` | `/var/www/html/vendor` | **Performance** — veja abaixo |
| `node-modules` | `/var/www/html/node_modules` | Binários nativos Linux |

### Por que `vendor/` e `node_modules/` não usam bind mount

O bind mount do Windows para a VM do WSL2 (virtiofs) é lento por arquivo. O
`vendor/` tem cerca de 9.000 arquivos PHP e, servido pelo bind mount, cada
requisição da home levava **~25 segundos**. Com o `vendor/` em volume nomeado
(ext4 dentro da VM) o tempo cai para **~1 segundo**. O mesmo vale para o
Tailwind: a primeira compilação do CSS caiu de ~129s para ~15s.

Consequências práticas:

- O `vendor/` do Windows **continua existindo** e serve ao autocomplete da IDE.
  Ele apenas não é usado pelos containers.
- Depois de alterar o `composer.json`, rode `composer install` **dentro** do
  container — instalar pelo Windows não afeta o ambiente Docker.
- O `node_modules/` do Windows contém binários `win32` (rollup, esbuild) que não
  executam em Linux; por isso o volume separado é obrigatório, não opcional.

O código da aplicação (`app/`, `resources/`, `config/`, `routes/`, `database/`,
`public/`, `storage/`) permanece em bind mount: você edita no Windows e a
alteração vale imediatamente, sem rebuild.

O serviço `node` também recebe um `tmpfs` vazio sobre `feira_esquerda_livre_app`
(o projeto Flutter). Sem isso o Tailwind varria 1.775 arquivos pelo bind mount a
cada passada, ~13s por varredura. O projeto Flutter no host não é afetado.

---

## Primeira instalação

```bash
git clone <repo>
cd feira-esquerda-livre

# 1. Arquivo de ambiente
cp .env.example .env

# 2. Build e subida
docker compose build
docker compose up -d

# 3. Dependências (dentro dos containers)
docker compose exec app composer install
docker compose exec node npm install

# 4. Aplicação
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

> O serviço `node` já executa `npm install` sozinho ao subir; o passo 3 só é
> necessário se você quiser acompanhar a saída.
>
> O serviço `queue` aguarda o `vendor/autoload.php` aparecer antes de iniciar o
> worker, então ele não entra em crash-loop durante a primeira instalação.

Depois do passo 4, acesse http://localhost.

---

## Uso diário

```bash
docker compose up -d      # ligar
docker compose ps         # conferir
docker compose down       # desligar (dados preservados)
```

`docker compose down` **não** apaga os volumes. Nunca use `down -v` a menos que
queira perder o banco local.

### Logs

```bash
docker compose logs -f app        # PHP-FPM
docker compose logs -f nginx
docker compose logs -f queue      # worker
docker compose logs -f node       # Vite
docker compose exec app php artisan pail
```

### Rebuild

Necessário só ao alterar `docker/php/Dockerfile`:

```bash
docker compose build app
docker compose up -d --force-recreate app queue
```

Rebuild completo, sem cache:

```bash
docker compose build --no-cache
docker compose up -d --force-recreate
```

---

## Comandos por serviço

### Artisan e Composer

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan tinker
docker compose exec app php artisan test

docker compose exec app composer install
docker compose exec app composer require vendor/pacote
docker compose exec app composer dump-autoload
```

### npm e Vite

```bash
docker compose exec node npm install
docker compose exec node npm run build
docker compose restart node          # reiniciar o dev server
```

O Vite roda automaticamente com `--host 0.0.0.0` e o `vite.config.js` define
`hmr.host = localhost`, para o HMR funcionar a partir do navegador do Windows.
O `VITE_USE_POLLING=true` do compose ativa polling no watcher, já que o bind
mount do Windows não propaga eventos inotify.

Ao subir, o Vite grava `public/hot` e o Blade passa a apontar para
`http://localhost:5173`. Para usar assets compilados em vez do dev server, pare
o container do node (`docker compose stop node`) e rode
`docker compose run --rm node npm run build`.

### MySQL

```bash
# Cliente dentro do container
docker compose exec mysql mysql -u feira -p feira_esquerda_livre

# Backup
docker compose exec mysql mysqldump -u root -p feira_esquerda_livre > backup.sql

# Restore
docker compose exec -T mysql mysql -u root -p feira_esquerda_livre < backup.sql
```

Conexão externa (DBeaver, TablePlus, HeidiSQL):

```
Host:     localhost
Porta:    3306
Database: feira_esquerda_livre
Usuário:  feira
Senha:    feira_local
```

### phpMyAdmin

http://localhost:8081

```
Usuário: feira      Senha: feira_local
```

O usuário administrativo local também funciona (`root` / `root_local`).

O phpMyAdmin fala com o banco pelo nome do serviço (`PMA_HOST=mysql`), nunca por
`127.0.0.1`, e só sobe depois que o healthcheck do MySQL passa. Ele é um serviço
independente — não está instalado dentro do container PHP.

### Redis

```bash
docker compose exec redis redis-cli ping
docker compose exec redis redis-cli monitor
```

Do host: `redis-cli -p 6380`.

O Redis está disponível, mas **o projeto continua usando `database`** para
cache, sessão e filas. Isso é intencional: o comportamento atual foi preservado.
Para passar a usar Redis, altere no `.env`:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### Mailpit

http://localhost:8025

Todo e-mail enviado pela aplicação local é capturado pelo Mailpit. **Nenhum
e-mail real sai do ambiente de desenvolvimento.**

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

### Queue Worker

O serviço `queue` roda continuamente:

```bash
php artisan queue:work --queue=default,email-marketing --tries=3 --sleep=3 --timeout=120 --max-time=3600
```

Ele usa a mesma imagem do `app`. As duas filas do projeto (`default` e
`email-marketing`) são atendidas.

```bash
docker compose logs -f queue
docker compose restart queue          # necessário após alterar código de Jobs
docker compose exec app php artisan queue:failed
docker compose exec app php artisan queue:retry all
```

> O worker mantém o código em memória. **Reinicie o serviço `queue` sempre que
> alterar um Job**, senão a versão antiga continua rodando.

### Scheduler

O `routes/console.php` tem duas tarefas agendadas (rastreamento de envios 3x ao
dia e disparo de campanhas a cada 5 minutos). Não há serviço dedicado no
compose. Para exercitá-las manualmente:

```bash
docker compose exec app php artisan schedule:run
docker compose exec app php artisan schedule:work    # em primeiro plano
```

---

## Windows 11 + Docker Desktop + WSL2

- **Line endings:** o `.gitattributes` do projeto normaliza os arquivos. O
  `docker/php/entrypoint.sh` é gravado com LF; se um editor convertê-lo para
  CRLF o container falha com `exec format error` — reconverta para LF.
- **Permissões:** o entrypoint garante escrita em `storage/` e
  `bootstrap/cache/` a cada boot do container.
- **Performance:** ver a seção de volumes. Não mova `vendor/` ou
  `node_modules/` de volta para bind mount.
- **Caminhos:** no Git Bash, comandos `docker compose exec` com caminhos
  absolutos Linux (`/usr/share/...`) são convertidos para caminhos Windows.
  Prefixe com `MSYS_NO_PATHCONV=1` quando isso atrapalhar.
- **Comunicação entre containers:** use sempre o **nome do serviço** (`mysql`,
  `redis`, `mailpit`), nunca `127.0.0.1` — dentro de um container, `127.0.0.1`
  é o próprio container.

---

## JMF Customer Intelligence

O `composer.json` declara um repositório do tipo `path`:

```json
"repositories": [
    { "type": "path", "url": "../jmf-ci-sdk/packages/jmf-system/customer-intelligence-sdk" }
]
```

Esse diretório fica **fora** da raiz do projeto. Sem ele, `composer install`
falha dentro do container.

Solução adotada (sem alterar a integração): os serviços `app` e `queue` montam
o diretório em modo somente leitura, preservando o caminho relativo.

```yaml
volumes:
  - ../jmf-ci-sdk:/var/www/jmf-ci-sdk:ro
```

Como o projeto fica em `/var/www/html`, o `../jmf-ci-sdk` do `composer.json`
resolve para `/var/www/jmf-ci-sdk` — exatamente o que foi montado.

**Requisito:** o repositório `jmf-ci-sdk` precisa estar clonado ao lado de
`feira-esquerda-livre`:

```
projeto-feira-esquerda-livre/
├── feira-esquerda-livre/     <- este projeto
└── jmf-ci-sdk/               <- necessário para o composer install
```

Sem ele o Docker cria um diretório vazio no lugar e o `composer install` acusa
o pacote como não encontrado.

> A remoção da integração JMF está prevista para uma etapa futura e **não** foi
> iniciada aqui. Quando ela ocorrer, o volume `../jmf-ci-sdk` e o bloco
> `repositories` do `composer.json` poderão ser removidos juntos.

---

## Testes

```bash
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=NomeDoTeste
```

Os testes usam **SQLite em memória** (`phpunit.xml`), não o MySQL de
desenvolvimento — seus dados locais ficam intactos.

> **Atenção ao editar o `compose.yaml`:** não adicione `env_file: [.env]` aos
> serviços `app` ou `queue`. Isso injeta as variáveis como variáveis de ambiente
> reais do container, que têm precedência sobre o `<env>` do `phpunit.xml`. O
> resultado é que `php artisan test` roda `RefreshDatabase` **contra o MySQL de
> desenvolvimento e apaga todos os seus dados**. O Laravel lê o `.env` do bind
> mount sozinho; `env_file` não é necessário.

---

## Troubleshooting

### `docker compose up` falha com "port is already allocated"

Alguma porta do host está ocupada. Descubra o culpado e ajuste a variável
`DOCKER_*_PORT` correspondente no `.env`:

```powershell
Get-NetTCPConnection -LocalPort 3306 -State Listen |
  ForEach-Object { Get-Process -Id $_.OwningProcess }
```

### Página demora 20 segundos ou mais

O `vendor/` provavelmente voltou ao bind mount. Confirme que o `compose.yaml`
mantém `- vendor:/var/www/html/vendor` nos serviços `app`, `queue` e `node`.

### `Class ... not found` ou `vendor/autoload.php` ausente

O volume `vendor` está vazio:

```bash
docker compose exec app composer install
```

### Vite não carrega os assets / página sem estilo

```bash
docker compose logs node          # o dev server subiu?
cat public/hot                    # deve conter http://localhost:5173
docker compose restart node
```

Se um Vite antigo do Windows estiver rodando, ele **apaga o `public/hot` ao
encerrar** e derruba o dev server do container. Encerre o `node.exe` do Windows
e reinicie o container do node.

### Erro do rollup/esbuild sobre plataforma incorreta

O `node_modules` do Windows vazou para dentro do container:

```bash
docker compose down
docker volume rm feira-esquerda-livre_node-modules
docker compose up -d
```

### `SQLSTATE[HY000] [2002] Connection refused`

O MySQL ainda está subindo. Aguarde o healthcheck:

```bash
docker compose ps          # mysql deve estar "healthy"
docker compose logs mysql
```

Confirme também que o `.env` usa `DB_HOST=mysql` — e não `127.0.0.1`.

### Tabela `cache` não existe

As migrations ainda não rodaram (o projeto usa `CACHE_STORE=database`):

```bash
docker compose exec app php artisan migrate
```

### Permissão negada em `storage/`

```bash
docker compose restart app     # o entrypoint reajusta as permissões
```

### Recomeçar o banco do zero

**Destrutivo — apaga todos os dados locais.**

```bash
docker compose down
docker volume rm feira-esquerda-livre_mysql-data
docker compose up -d
docker compose exec app php artisan migrate --seed
```
