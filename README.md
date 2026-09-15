# Feira Esquerda Livre

Plataforma de marketplace, agenda de feiras, comunidade e cursos digitais para
lojistas e expositores populares, clientes e equipe interna. Combina CMS, painel
administrativo, área do lojista, área do cliente, catálogo em três eixos
(produtos, serviços e cuidados), checkout multilojas com Mercado Pago, frete,
comunicação pós-venda, email marketing, AVA com certificado e uma API REST
consumida por um app Flutter.

Pensada para redes lentas, mobile first e público 40+.

## Documentação

| Quero saber | Onde |
|---|---|
| **Estado do desenvolvimento, próximas fases, bloqueios e dívidas** | [`ROADMAP.md`](ROADMAP.md) |
| **Arquitetura, domínios, invariantes e Decision Log** | [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) |
| Contrato da API `/api/v1` | [`docs/API.md`](docs/API.md) |
| Módulo Customer Intelligence (também exibido no painel) | [`docs/CUSTOMER_INTELLIGENCE_INTERNAL.md`](docs/CUSTOMER_INTELLIGENCE_INTERNAL.md) |
| App mobile | [`feira_esquerda_livre_app/README.md`](feira_esquerda_livre_app/README.md) |

Este README cobre **instalação, execução e uso**. Fases novas atualizam o
`ROADMAP.md`; decisões novas, o `docs/ARCHITECTURE.md`. Não são criados
documentos por fase.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2+ (Docker roda 8.3) · Laravel 12 |
| Frontend | Livewire 4 · AlpineJS 3 · TailwindCSS 4 · Vite 7 |
| Banco | MySQL 8.4 (Docker) · SQLite em memória nos testes |
| Cache, sessão e filas | driver `database` (Redis disponível, desligado) |
| Permissões | spatie/laravel-permission |
| API mobile | Laravel Sanctum (Bearer) |
| Imagens · PDF | Intervention Image 3 · barryvdh/laravel-dompdf |
| Pagamento · frete | Mercado Pago · Melhor Envio e Frenet |
| E-mail local | Mailpit |

---

## Instalação com Docker (caminho suportado)

### Pré-requisitos

- Docker Desktop com WSL2 (Windows 11) ou Docker Engine (Linux/macOS)
- Git
- ~4 GB de disco para imagens e volumes

**Não é preciso** PHP, Composer, Node, MySQL, Laragon ou XAMPP no host.

### Primeira instalação

```bash
git clone https://github.com/jmarciosilva/projeto-feira-esquerda-livre.git feira-esquerda-livre
cd feira-esquerda-livre

# 1. Ambiente — o .env.example já aponta para os serviços do compose
cp .env.example .env

# 2. Imagens e stack
docker compose build
docker compose up -d

# 3. Dependências PHP dentro do container (o serviço "node" roda npm install sozinho)
docker compose exec app composer install

# 4. Chave, banco com dados de demonstração e link de storage
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Depois disso, http://localhost responde com a home populada.

> `composer run setup` **não serve para o Docker**: roda tudo no host e não
> executa seeders nem `storage:link`.

### Serviços

| Serviço | Porta no host | Endereço | Observação |
|---|---|---|---|
| Aplicação (Nginx) | 80 | http://localhost | |
| Vite (dev server) | 5173 | http://localhost:5173 | HMR com polling |
| phpMyAdmin | 8081 | http://localhost:8081 | `feira` / `feira_local` (ou `root` / `root_local`) |
| Mailpit (web) | 8025 | http://localhost:8025 | captura todo e-mail local |
| Mailpit (SMTP) | 1025 | — | |
| MySQL | 3306 | — | banco `feira_esquerda_livre`, `feira` / `feira_local` |
| Redis | **6380** | — | interno `redis:6379`; não usado por padrão |
| `app` | — | — | PHP-FPM 8.3 + Composer |
| `queue` | — | — | worker de filas, mesma imagem do `app` |

Todas as portas são configuráveis no `.env` (`DOCKER_HTTP_PORT`,
`DOCKER_MYSQL_PORT`, `DOCKER_PHPMYADMIN_PORT`, `DOCKER_REDIS_PORT`,
`DOCKER_VITE_PORT`, `DOCKER_MAILPIT_HTTP_PORT`, `DOCKER_MAILPIT_SMTP_PORT`). Se a
subida falhar com *port is already allocated*, troque o valor e rode
`docker compose up -d` de novo.

### Uso diário

```bash
docker compose up -d          # ligar
docker compose ps             # mysql precisa estar "healthy"
docker compose down           # desligar — dados preservados
```

`docker compose down -v` **apaga o banco local**.

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan pail
docker compose exec app composer require vendor/pacote
docker compose exec node npm run build
docker compose logs -f queue
docker compose restart queue          # obrigatório depois de alterar um Job
```

- `vendor/` e `node_modules/` dos containers ficam em **volumes nomeados**, por
  performance. Instalar dependências pelo Windows não afeta o Docker: rode
  `composer install` e `npm install` **dentro** dos containers.
- Rebuild só ao alterar `docker/php/Dockerfile`:
  `docker compose build app && docker compose up -d --force-recreate app queue`.

### Nunca adicione `env_file` a `app` ou `queue`

Injetar o `.env` como variáveis reais do container faz elas sobreporem o
`phpunit.xml`, e `php artisan test` passa a rodar `RefreshDatabase` **contra o
MySQL de desenvolvimento, apagando os dados**. O Laravel já lê o `.env` pelo bind
mount.

---

## O que precisa estar rodando

| Processo | Obrigatório | No Docker |
|---|---|---|
| **Worker de filas** — e-mails, campanhas, impressões, eventos de Customer Intelligence | sim | serviço `queue` |
| **Scheduler** — expiração de pagamentos e reservas, rastreio, campanhas, expurgos | **sim em produção** | não há serviço: rode sob demanda |
| **Vite** (`npm run dev`) **ou** assets compilados (`npm run build`) | sim | serviço `node` |
| **`storage:link`** — logo, banners, imagens e materiais do AVA | sim | uma vez, na instalação |

```bash
php artisan queue:work --queue=default,email-marketing,customer-intelligence \
  --tries=3 --sleep=3 --timeout=120 --max-time=3600
```

A ordem das filas é **prioridade** e não deve ser trocada.

```bash
docker compose exec app php artisan schedule:run     # dispara o que venceu
docker compose exec app php artisan schedule:work    # em primeiro plano
```

Tarefas agendadas (`routes/console.php`):

| Quando | Tarefa |
|---|---|
| 08h, 14h, 20h | Atualização de envios em trânsito (`TrackShipmentsJob`) |
| A cada 5 min | Disparo de campanhas de email marketing agendadas |
| A cada 5 min | `orders:expire-payments` — libera estoque de pagamentos e checkouts vencidos |
| 03:20 | `customer-intelligence:prune-events` (180 dias) |
| 03:40 | `customer-intelligence:prune-audit-logs` (730 dias) |

Enquanto o Vite roda ele grava `public/hot` e o Blade aponta para
`http://localhost:5173`; para usar assets compilados, pare o `node`
(`docker compose stop node`) e rode `docker compose run --rm node npm run build`.

---

## Variáveis de ambiente que importam

Além das padrão do Laravel. Os valores entre parênteses são os padrões em
`config/`; várias não constam do `.env.example` e só precisam ser declaradas para
mudar o padrão.

| Variável | Padrão | Para quê |
|---|---|---|
| `DOCKER_*_PORT` | ver acima | Portas publicadas pelo compose |
| `DB_ROOT_PASSWORD` | `root_local` | Root do MySQL local |
| `CHECKOUT_RESERVATION_MINUTES` | `30` | Validade da reserva de estoque sem intenção de pagamento |
| `CI_ENABLED` | `true` | Liga/desliga toda a coleta de Customer Intelligence |
| `CI_QUEUE` · `CI_QUEUE_CONNECTION` | `customer-intelligence` · conexão padrão | Fila dos eventos |
| `CI_RETENTION_DAYS` | `180` | Retenção de eventos brutos |
| `CI_AUDIT_RETENTION_DAYS` | `730` | Retenção da auditoria administrativa |
| `CI_CONSENT_COOKIE_MINUTES` | `525600` | Validade da escolha de privacidade (12 meses) |
| `CI_VISITOR_COOKIE_*` · `CI_SESSION_COOKIE_*` | `jmf_ci_*`, 2 anos / 30 min | Nomes e validade dos cookies de coleta |
| `CATALOG_AI_MINIMUM_GAPS` | `3` | Limiar de lacunas da `SuggestionPolicy` (Catalog Intelligence) |
| `CATALOG_AI_ENABLED` | `false` | Liga o provider externo da Catalog Intelligence — ver [abaixo](#catalog-intelligence-provider-externo) |
| `CATALOG_AI_PROVIDER` · `_MODEL` · `_API_KEY` · `_TIMEOUT` | vazio (o `.env.example` traz `openai`) · vazio · vazio · `8` | Provider (só `openai`), modelo, chave e prazo total em segundos, limitado a 8 |
| `HOME_EXPOSITORES_COUNT` · `HOME_FEATURED_MAX` · `HOME_CACHE_TTL_MINUTES` | `9` · `2` · `5` | Vitrine de expositores na home |
| `MELHOR_ENVIO_BASE_URL` · `_TOKEN` · `_ENVIRONMENT` · `_TIMEOUT` | sandbox · vazio · `sandbox` · `20` | Fallback do Melhor Envio |
| `FRENET_TOKEN` · `FRENET_TIMEOUT` | vazio · `20` | Fallback da Frenet |

Para migrar cache, sessão e filas para Redis, altere `CACHE_STORE`,
`SESSION_DRIVER` e `QUEUE_CONNECTION` para `redis`.

### Integrações externas: o painel vem antes do `.env`

Credenciais de pagamento e frete ficam em **`/admin/settings/checkout`**,
criptografadas no banco. O `.env` é fallback **só** para Melhor Envio e Frenet;
**Mercado Pago não tem fallback** — sem configurar o painel, o pagamento não
inicia.

- **Mercado Pago:** pagamento em `/pedido/{reference}/pagar`, retorno em
  `/pagamentos/mercado-pago/retorno/{reference}`, webhook em
  `POST /pagamentos/mercado-pago/webhook`.
- **Melhor Envio:** cotação e rastreio; conexão OAuth da conta da plataforma em
  `/admin/melhor-envio/conectar`.
- **Frenet:** provedor alternativo de cotação, escolhido em `frete_provedor`.
- **SMTP:** configurável em `/admin/settings/mail`; no desenvolvimento, Mailpit.

### Catalog Intelligence: provider externo

**Desligado por padrão.** Sem ele, o assistente do cadastro de produtos usa só a
inteligência interna — é o estado normal, não uma falha. Não há tela no painel: a
configuração é só pelo `.env`.

```dotenv
CATALOG_AI_ENABLED=false
CATALOG_AI_PROVIDER=openai
CATALOG_AI_MODEL=
CATALOG_AI_API_KEY=
CATALOG_AI_TIMEOUT=8
```

- Para homologar: `CATALOG_AI_ENABLED=true`, com modelo e chave preenchidos. O
  projeto não fixa modelo; use o definido para a homologação.
- **A chave nunca é versionada** — fica só no `.env` do ambiente.
- Sem chave, sem modelo, com provider diferente de `openai` ou prazo inválido, a
  aplicação segue só com a inteligência interna, sem erro.
- `CATALOG_AI_TIMEOUT` é o prazo total em segundos; acima de 8, vale 8. Não há
  nova tentativa.
- Com configuração em cache, a mudança no `.env` só vale depois de recarregar:
  `docker compose exec app php artisan optimize:clear` no desenvolvimento, ou
  `php artisan config:cache` de novo no deploy.
- A ativação ampla em produção depende da CAT-10B (custo, rate limit e
  observabilidade) — bloqueador **B-6** no [`ROADMAP.md`](ROADMAP.md).

---

## Dados de demonstração

`migrate --seed` (ou `php artisan db:seed`) cria permissões e perfis, usuário
administrador, configurações do site, contrato de expositor, categorias por eixo,
banners, eventos, expositores com imagens, produtos, serviços, cuidados, itens de
demonstração com preço simbólico, posts, publicações de feed, dados logísticos,
CEPs de expositores, um curso online no AVA e a base inicial de conhecimento da
Catalog Intelligence.

> Os seeders de demonstração deixam **todas as ofertas com preço R$ 0,01**, para
> permitir testar checkout e pagamento. Não use em produção.

### Usuários demonstrativos

| Perfil | E-mail | Senha | Uso |
|---|---|---|---|
| Administrador | `admin@feiraesquerdalivre.com.br` | `Admin@2026!` | `/admin` |
| Lojista — Tecnologia Solidária | `tech@teste.com` | `password` | loja e curso digital |
| Cliente do curso demo | `cliente.curso@teste.com` | `password` | `/minha-conta/aprendizado`, player e certificado |

Outros lojistas, todos com senha `password`: `lojista@teste.com`,
`raiz@teste.com`, `corpo@teste.com`, `mente@teste.com`, `ervas@teste.com`,
`costura@teste.com`, `foto@teste.com`, `conserto@teste.com`,
`ceramica@teste.com`, `mel@teste.com`, `raizes@teste.com`, `pinceis@teste.com`,
`fios@teste.com`.

**Cenário do AVA:** a loja Tecnologia Solidária vende o "Curso Online de
Informática Popular"; o cliente `cliente.curso@teste.com` já está matriculado e
pode concluir as quatro aulas e baixar o certificado em PDF.

Instalações existentes que ganharem permissão nova precisam rodar
`php artisan db:seed --class=RolePermissionSeeder` (idempotente).

---

## Painel administrativo — guia rápido

Acesso em `/admin`, com papel interno e permissões por módulo.

| Módulo | Rota | Observações |
|---|---|---|
| Dashboard | `/admin` | |
| Configurações do site | `/admin/settings` | Registro único. Logo até **2 MB**, favicon até **512 KB**, imagem "sobre" até **4 MB** |
| E-mail | `/admin/settings/mail` | SMTP |
| Checkout, pagamento e frete | `/admin/settings/checkout` | Credenciais criptografadas, comissão, provedor de frete |
| Usuários internos e perfis | `/admin/usuarios` · `/admin/perfis-acesso` | |
| Clientes | `/admin/clientes` | |
| Páginas | `/admin/pages` | Slug gerado do título só na criação |
| Banners | `/admin/banners` | Imagem desktop obrigatória na criação; desktop e mobile até **4 MB** |
| Menus | `/admin/menus` | Localizações `header`, `footer`, `sidebar`, `mobile`; itens com submenu |
| Mídia | `/admin/media` | `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `pdf`, `mp4`, até **10 MB** por arquivo; upload múltiplo |
| Posts | `/admin/posts` | Tipos `post`, `news`, `campaign`; status `draft`, `published`, `archived`; capa até **4 MB** |
| Eventos | `/admin/events` | Imagem e banner até **4 MB** |
| Expositores e visibilidade | `/admin/expositores` · `/admin/expositores/visibilidade` | Slots de destaque, peso de rotação |
| Categorias | `/admin/categorias` | Por eixo |
| Solicitações de lojistas | `/admin/lojistas/solicitacoes` | Aprovação cria usuário e loja |
| Pedidos | `/admin/pedidos` | |
| Moderação do feed | `/admin/feed/reportes` | |
| Email marketing | `/admin/email-marketing` | |
| Customer Intelligence | `/admin/customer-intelligence` · `/auditoria` · `/documentacao` | Permissões `customer_intelligence.visualizar` e `.auditoria` |
| Melhor Envio | `/admin/melhor-envio/conectar` | OAuth da conta da plataforma |

Regras gerais: exclusões pedem confirmação; ao substituir uma imagem a anterior é
apagada do disco; listagens paginam 15 itens (mídia: 24). Não existe cadastro de
produto no admin — itens são cadastrados pelos lojistas.

---

## Testes

```bash
docker compose exec -T app php artisan test
docker compose exec -T app php artisan test --filter=NomeDoTeste
docker compose exec -T app php artisan test tests/Feature/CatalogIntelligence
```

- Rodam em **SQLite em memória** (`phpunit.xml`); o MySQL de desenvolvimento não é
  tocado. São 88 arquivos de teste; a suíte completa leva cerca de 15 minutos no
  container.
- A suíte completa verde sobre o código final é o critério de aceite de toda fase.
- Rode Pint **só nos arquivos alterados**:
  `docker compose exec app ./vendor/bin/pint caminho/do/Arquivo.php`. Há violações
  preexistentes em muitos arquivos; Pint global está proibido.
- Sem Docker: `composer run test`.

### Prova de concorrência (MySQL real)

SQLite não tem lock de linha, então disputas de checkout, pagamento, expiração e
estorno são provadas fora do PHPUnit:

```bash
bash tests/Concurrency/prove.sh
```

Sobe um banco **descartável** no container MySQL, roda doze disputas em processos
paralelos, confere os invariantes e derruba o banco. Sai `0` quando tudo passa. O
banco de desenvolvimento nunca é tocado.

| Variável | Padrão | Para quê |
|---|---|---|
| `FEL_APP_CONTAINER` | `fel_app` | container PHP |
| `FEL_MYSQL_CONTAINER` | `fel_mysql` | container MySQL |
| `FEL_SCRATCH_DB` | `fel_scratch_finsec01g` | banco descartável |
| `FEL_SEGURA_MS` | `2000` | quanto o primeiro lado segura o lock (aumente em máquina lenta) |
| `FEL_ATRASO_MS` | `300` | quando o segundo lado chega |

### Nunca, no banco de desenvolvimento

`migrate:fresh`, `migrate:refresh`, `db:wipe`, `docker compose down -v` ou
`ANALYZE TABLE` (commit implícito anula `ROLLBACK`). Para validar instalação
limpa, use um MySQL descartável — procedimento em `docs/ARCHITECTURE.md`, seção
*Testes e validação*.

---

## Sem Docker (não suportado oficialmente)

Requisitos: PHP ≥ 8.2 com `pdo_mysql`, `pdo_sqlite`, `mbstring`, `fileinfo`, `gd`
(jpeg/webp/freetype), `intl`, `bcmath`, `exif`, `zip`, `curl`, `openssl`;
Composer 2; Node ≥ 20; MySQL 8; um SMTP ou Mailpit local.

```bash
git clone https://github.com/jmarciosilva/projeto-feira-esquerda-livre.git feira-esquerda-livre
cd feira-esquerda-livre
composer run setup            # install, .env, key, migrate, npm install, npm run build
php artisan storage:link
php artisan db:seed
composer run dev              # servidor, fila, logs (Pail) e Vite em paralelo
```

No `.env`, troque os hosts do Docker: `APP_URL=http://localhost:8000`,
`DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`, `MAIL_HOST=127.0.0.1`, e ajuste
`DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`. Rode o scheduler à parte.

---

## Troubleshooting (Docker)

| Sintoma | Causa e solução |
|---|---|
| *port is already allocated* | Porta do host ocupada. Ajuste o `DOCKER_*_PORT` correspondente |
| Página leva 20s ou mais | `vendor/` voltou ao bind mount. Confirme `- vendor:/var/www/html/vendor` em `app`, `queue` e `node` |
| `Class ... not found` / sem `vendor/autoload.php` | Volume `vendor` vazio: `docker compose exec app composer install` |
| Página sem estilo | `docker compose logs node`; `public/hot` deve conter `http://localhost:5173`; `docker compose restart node`. Um Vite rodando no Windows apaga o `public/hot` ao encerrar |
| Erro de plataforma do rollup/esbuild | `node_modules` do Windows vazou: `docker compose down`, `docker volume rm feira-esquerda-livre_node-modules`, `docker compose up -d` |
| 502 depois de recriar o `app` | O Nginx guardou o IP antigo: `docker compose restart nginx` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL ainda subindo (`docker compose ps` até *healthy*) ou `DB_HOST` diferente de `mysql` |
| Tabela `cache` não existe | Migrations não rodaram: `docker compose exec app php artisan migrate` |
| Permissão negada em `storage/` | `docker compose restart app` — o entrypoint reajusta |
| `exec format error` no container | `docker/php/entrypoint.sh` convertido para CRLF; volte para LF |
| Caminho Linux virando caminho Windows no Git Bash | Prefixe com `MSYS_NO_PATHCONV=1` |
| Job antigo continua rodando | `docker compose restart queue` |

Backup e restore:

```bash
docker compose exec mysql mysqldump -u root -p feira_esquerda_livre > backup.sql
docker compose exec -T mysql mysql -u root -p feira_esquerda_livre < backup.sql
```

Recomeçar o banco do zero (**destrutivo**): `docker compose down`,
`docker volume rm feira-esquerda-livre_mysql-data`, `docker compose up -d`,
`docker compose exec app php artisan migrate --seed`.

---

## App mobile

`feira_esquerda_livre_app/` contém o app Flutter que consome `/api/v1`. Tem ciclo
de build próprio e não faz parte do build web — no Docker o diretório é mascarado
por `tmpfs` no serviço `node`. Instruções em
[`feira_esquerda_livre_app/README.md`](feira_esquerda_livre_app/README.md).

---

## Rotas principais

### Público

| Rota | Descrição |
|---|---|
| `/` | Home |
| `/produtos` · `/servicos` · `/cuidados` | Catálogo por eixo |
| `/loja/{slug}` · `/loja/{slug}/{productSlug}` | Loja e página do item (uma oferta) |
| `/loja/{slug}/{productSlug}/compartilhar.png` | Imagem de compartilhamento |
| `/agenda` · `/agenda/{slug}` | Agenda de feiras |
| `/blog/{slug}` · `/feed` | Conteúdo e comunidade |
| `/contato` · `/seja-um-expositor` | Contato e solicitação de lojista |
| `/politica-de-privacidade` · `/termos-de-uso` · `/privacidade/preferencias` | Institucional e consentimento |
| `/cadastro` · `/login` | Conta |
| `/checkout` | Finalização (exige login) |
| `/pedido/{reference}` · `/pedido/{reference}/pagar` · `/pedido/{reference}/status` | Pedido e pagamento |
| `/rastreio/{trackingCode}` | Rastreio público |
| `/newsletter/descadastro/{token}` · `/mk/o/{token}` · `/mk/c/{token}` | Campanhas |

### Lojista (`/minha-loja`)

Painel, `loja`, `produtos` (novo, editar, imagem de compartilhamento), `pedidos`
(e chat por split), `perguntas`, `feed`, `exposicao`, `cursos` e
`cursos/{course}/builder`.

### Cliente (`/minha-conta`)

`pedidos`, `enderecos`, `aprendizado` (player e certificado);
`/ava/materiais/{material}/download` com URL assinada.

### API

`/api/v1` — 62 rotas, documentadas em [`docs/API.md`](docs/API.md).
