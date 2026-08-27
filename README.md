# Feira Esquerda Livre

Plataforma de marketplace, agenda de feiras, comunidade e AVA para lojistas, expositores populares, clientes e equipe interna. O projeto combina CMS, painel administrativo, área do lojista, área do cliente, catálogo por eixos, checkout, comunicação pós-venda, email marketing e cursos digitais.

A experiência foi desenhada para funcionar bem em redes lentas (3G/4G), com navegação mobile first e foco em público 40+.

---

## MVP Demonstrável

O projeto já possui um fluxo mínimo viável para apresentação a clientes, diretores e sócios da feira:

- Home pública com identidade visual aplicada, banners responsivos, próximas feiras, expositores, marketplace, blog, chamada para expositores e newsletter.
- Navbar, footer, botão de voltar ao topo e páginas institucionais padronizadas.
- Página de contato com canais oficiais, formulário e resposta automática em HTML para o usuário.
- Política de Privacidade, Termos de Uso e central de preferências de privacidade.
- Painel administrativo com dashboard, configurações do site, logo/favicon, banners, conteúdos, eventos, expositores, visibilidade e checkout.
- Marketplace com produtos, serviços, cuidados, carrinho, checkout autenticado e integração inicial com Mercado Pago.
- Dados demonstrativos para produtos, expositores, posts de comunidade e curso online com certificado.

---

## Stack Técnica

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2+ (o ambiente Docker roda 8.3) · Laravel 12 |
| Frontend reativo | Livewire 4 · AlpineJS 3 |
| Estilização | TailwindCSS 4 |
| Build | Vite 7 |
| Banco de dados | MySQL 8.4 no Docker de desenvolvimento · MySQL 8+ em produção · SQLite em memória nos testes |
| Cache / sessão / filas | Driver `database` (Redis disponível no Docker, mas desligado por padrão) |
| E-mail em desenvolvimento | Mailpit (nenhum e-mail real sai da máquina local) |
| Permissões | spatie/laravel-permission |
| API mobile | Laravel Sanctum (tokens Bearer, consumidos pelo app Flutter) |
| Processamento de imagens | Intervention/Image 3 |
| PDF | barryvdh/laravel-dompdf |
| Frete | Melhor Envio e Frenet |
| Pagamento | Mercado Pago |
| Inteligência de Cliente | Módulo nativo (`app/CustomerIntelligence`) |

---

# Como rodar o projeto

Há dois caminhos. **O Docker é o caminho suportado** — é o único que reproduz
exatamente as versões usadas no desenvolvimento e o único com documentação de
troubleshooting.

O `.env.example` do repositório é escrito **para o Docker**. Quem for rodar sem
Docker precisa editá-lo (ver [Caminho B](#caminho-b--sem-docker)).

---

## Caminho A — Docker (recomendado)

### Pré-requisitos

- **Docker Desktop** com backend WSL2 (Windows 11) ou Docker Engine (Linux/macOS).
- **Git**.
- Nada além disso. **Não é necessário** ter PHP, Composer, Node, MySQL, Laragon
  ou XAMPP instalados no host.
- Espaço em disco: cerca de 4 GB entre imagens e volumes.

### Primeira instalação (passo a passo completo)

```bash
git clone https://github.com/jmarciosilva/projeto-feira-esquerda-livre.git feira-esquerda-livre
cd feira-esquerda-livre

# 1. Arquivo de ambiente (já vem apontando para os serviços do compose)
cp .env.example .env

# 2. Construir as imagens e subir a stack
docker compose build
docker compose up -d

# 3. Dependências PHP dentro do container
#    (o serviço "node" roda `npm install` sozinho ao subir)
docker compose exec app composer install

# 4. Chave da aplicação, banco e link de storage
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Depois disso, http://localhost já responde com a home populada.

> **`composer run setup` não serve para o Docker.** Ele foi escrito para
> instalação local direta: roda `composer install`, copia o `.env`, gera a
> chave, migra e compila assets — mas **não roda os seeders nem o
> `storage:link`**, e executaria tudo no host, fora dos containers.

### Serviços, portas e endereços

| Serviço | Host | URL | Observação |
|---|---|---|---|
| Aplicação (Nginx) | 80 | http://localhost | |
| Vite (dev server) | 5173 | http://localhost:5173 | HMR |
| phpMyAdmin | 8081 | http://localhost:8081 | `feira` / `feira_local` |
| Mailpit (webmail) | 8025 | http://localhost:8025 | captura todo e-mail local |
| Mailpit (SMTP) | 1025 | — | |
| MySQL | 3306 | — | `feira` / `feira_local` |
| Redis | **6380** | — | interno continua `redis:6379` |

O Redis é publicado em **6380** porque a 6379 do host costuma estar ocupada pelo
serviço Memurai do Windows.

Toda porta do host é configurável pelo `.env`, para conviver com outros projetos
já rodando na máquina:

```env
DOCKER_HTTP_PORT=80
DOCKER_MYSQL_PORT=3306
DOCKER_PHPMYADMIN_PORT=8081
DOCKER_REDIS_PORT=6380
DOCKER_VITE_PORT=5173
DOCKER_MAILPIT_HTTP_PORT=8025
DOCKER_MAILPIT_SMTP_PORT=1025
```

Se a subida falhar com "port is already allocated", troque o valor e rode
`docker compose up -d` de novo.

### Uso diário

```bash
docker compose up -d          # ligar
docker compose ps             # conferir (mysql precisa estar "healthy")
docker compose down           # desligar — os dados são preservados
```

`docker compose down -v` **apaga o banco local**. Não use por engano.

### Comandos dentro dos containers

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan optimize:clear
docker compose exec app composer require vendor/pacote
docker compose exec node npm run build
docker compose logs -f queue
```

> Depois de alterar o `composer.json`, rode `composer install` **dentro** do
> container: o `vendor/` dos containers fica em volume nomeado (por
> performance), separado do `vendor/` do Windows, que existe apenas para o
> autocomplete da IDE. O mesmo vale para o `node_modules/`, cujos binários do
> Windows (rollup, esbuild) nem executam em Linux.

Arquitetura dos volumes, performance no WSL2 e troubleshooting completo em
**[docs/DOCKER_DEVELOPMENT.md](docs/DOCKER_DEVELOPMENT.md)**.

---

## Caminho B — sem Docker

### Requisitos

- PHP >= 8.2 com as extensões `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (com
  suporte a jpeg/webp/freetype), `intl`, `bcmath`, `exif`, `zip`, `curl`,
  `openssl` e `pdo_sqlite` (esta última usada pelos testes).
- Composer >= 2.
- Node.js >= 20 (o Docker usa 22) e npm.
- MySQL 8+ para o ambiente persistente.
- Um servidor SMTP, ou o Mailpit instalado no host — sem isso, todo envio de
  e-mail falha.

### Instalação

```bash
git clone https://github.com/jmarciosilva/projeto-feira-esquerda-livre.git feira-esquerda-livre
cd feira-esquerda-livre
composer run setup
```

O script `composer run setup` executa `composer install`, copia
`.env.example` → `.env`, gera a chave, roda `php artisan migrate --force`,
`npm install` e `npm run build`.

**Ele não é suficiente sozinho.** Faltam três passos, todos obrigatórios:

```bash
php artisan storage:link      # sem isso nenhuma imagem enviada aparece no site
php artisan db:seed           # sem isso o site sobe vazio, sem admin nem conteúdo
php artisan queue:work        # sem isso e-mails e eventos nunca são processados
```

### Ajustes obrigatórios no `.env`

O `.env.example` aponta para os **nomes de serviço do Docker**. Fora dele, os
hosts precisam mudar:

```env
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1        # em vez de "mysql"
REDIS_HOST=127.0.0.1     # em vez de "redis" (só importa se ativar o Redis)
MAIL_HOST=127.0.0.1      # em vez de "mailpit"
```

`DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` também precisam bater com o MySQL
da sua máquina — o `.env.example` traz `feira_esquerda_livre` / `feira` /
`feira_local`, que são as credenciais criadas pelo container.

### Desenvolvimento

```bash
composer run dev
```

Sobe em paralelo, num terminal só: servidor Laravel, worker de filas, stream de
logs com Pail e o Vite com HMR. Acesse `http://localhost:8000`.

Para gerar assets de produção: `npm run build`.

---

## O que precisa estar rodando para o sistema funcionar de verdade

Subir a aplicação não basta: partes centrais do produto dependem de processos de
segundo plano. No Docker o worker já sobe junto; o scheduler **não**.

### 1. Worker de filas — obrigatório

```bash
php artisan queue:work --queue=default,email-marketing,customer-intelligence \
  --tries=3 --sleep=3 --timeout=120 --max-time=3600
```

A ordem das filas é intencional e não deve ser trocada: o worker só olha
`customer-intelligence` quando `default` e `email-marketing` estão vazias — um
pico de navegação nunca deve atrasar um e-mail de pedido.

Sem o worker, e-mails transacionais, campanhas, impressões de expositor e
eventos de Inteligência de Cliente ficam parados na tabela `jobs`.

No Docker, o serviço `queue` faz isso continuamente. **Reinicie-o sempre que
alterar o código de um Job** (`docker compose restart queue`) — o worker mantém
a versão antiga em memória.

### 2. Scheduler — necessário para as rotinas automáticas

Não há serviço dedicado no compose. Em produção, agende o cron do Laravel; em
desenvolvimento, rode sob demanda:

```bash
php artisan schedule:run       # dispara o que estiver vencido agora
php artisan schedule:work      # mantém em primeiro plano
```

Tarefas agendadas hoje (`routes/console.php`):

| Quando | Tarefa |
|---|---|
| 08h, 14h e 20h | Atualização de status de envios em trânsito |
| A cada 5 minutos | Disparo de campanhas de email marketing agendadas |
| 03h20, diariamente | `customer-intelligence:prune-events` (retenção de 180 dias) |
| 03h40, diariamente | `customer-intelligence:prune-audit-logs` (retenção de 730 dias) |

### 3. Assets

O Vite precisa estar rodando (`npm run dev`, ou o serviço `node`) **ou** os
assets precisam ter sido compilados (`npm run build`). Sem um dos dois, a página
carrega sem estilo. Enquanto o dev server estiver de pé, ele grava `public/hot` e
o Blade aponta para ele; para voltar aos assets compilados, pare o dev server.

### 4. `storage:link`

`FILESYSTEM_DISK=public`: logo, favicon, banners, imagens de produto e materiais
do AVA são servidos via `public/storage`. Sem o link simbólico, tudo isso
retorna 404.

---

## Variáveis de ambiente que importam

Além das chaves padrão do Laravel:

| Variável | Padrão | Para que serve |
|---|---|---|
| `DOCKER_*_PORT` | ver acima | Portas publicadas no host pelo compose |
| `DB_ROOT_PASSWORD` | `root_local` | Senha do root do MySQL local (container e phpMyAdmin) |
| `CI_ENABLED` | `true` | Liga/desliga toda a coleta de Inteligência de Cliente |
| `CI_QUEUE` | `customer-intelligence` | Fila de gravação dos eventos |
| `CI_RETENTION_DAYS` | `180` | Retenção dos eventos brutos |
| `CI_AUDIT_RETENTION_DAYS` | `730` | Retenção da trilha de auditoria administrativa |
| `CI_CONSENT_COOKIE_MINUTES` | `525600` | Validade da preferência de consentimento (12 meses) |
| `HOME_EXPOSITORES_COUNT` | `9` | Expositores exibidos na home |
| `HOME_FEATURED_MAX` | `2` | Limite de destaques na home |
| `HOME_CACHE_TTL_MINUTES` | `5` | Cache da home |
| `MELHOR_ENVIO_TOKEN` / `MELHOR_ENVIO_ENVIRONMENT` | vazio / `sandbox` | Fallback do frete Melhor Envio |
| `FRENET_TOKEN` | vazio | Fallback do frete Frenet |

O Redis está disponível no Docker mas **não é usado por padrão**: cache, sessão
e filas continuam em `database`. Para migrar, altere `CACHE_STORE`,
`QUEUE_CONNECTION` e `SESSION_DRIVER` para `redis`.

### Integrações externas: o painel vem antes do `.env`

Credenciais de pagamento e frete são configuradas em
**`/admin/settings/checkout`** e ficam no banco, criptografadas
(`mercado_pago_access_token`, `melhor_envio_token`, `frenet_token`). O `.env`
funciona apenas como fallback para Melhor Envio e Frenet quando não há valor
salvo no painel. **Mercado Pago não tem fallback por `.env`** — sem configurar o
painel, o pagamento não inicia.

- **Mercado Pago:** fluxo iniciado em `/pedido/{reference}/pagar`, com webhook
  em `/pagamentos/mercado-pago/webhook`.
- **Melhor Envio:** cotação e rastreio; há conexão OAuth pelo próprio painel
  (`/admin/settings/checkout/conectar`).
- **Frenet:** provedor alternativo de cotação, selecionável por `frete_provedor`.
- **SMTP:** notificações, campanhas, AVA e rastreio de entrega. Em
  desenvolvimento, o Mailpit substitui o SMTP real.

Detalhes de frete em
[`docs/INTEGRACAO_FRETE_MELHOR_ENVIO.md`](docs/INTEGRACAO_FRETE_MELHOR_ENVIO.md).

---

## Seeders e dados demonstrativos

```bash
php artisan db:seed
# no Docker:
docker compose exec app php artisan db:seed
```

Os seeders criam permissões e perfis, usuário administrador, configurações do
site, contrato de expositor, categorias por eixo, banners, eventos, expositores
com imagens, produtos, serviços, cuidados, posts de comunidade, publicações de
feed, dados logísticos e um curso online de demonstração no AVA.

### Usuários demonstrativos

| Perfil | E-mail | Senha | Uso sugerido |
|---|---|---|---|
| Administrador | `admin@feiraesquerdalivre.com.br` | `password` | Painel administrativo |
| Lojista demo | `tech@teste.com` | `password` | Loja Tecnologia Solidária e curso digital |
| Cliente curso demo | `cliente.curso@teste.com` | `password` | `/minha-conta/aprendizado`, concluir curso e baixar certificado |

Outros lojistas demonstrativos seguem o mesmo padrão (`raiz@teste.com`,
`costura@teste.com`, `ervas@teste.com`, `corpo@teste.com`, `mente@teste.com`,
`foto@teste.com`, `conserto@teste.com`, `lojista@teste.com`), todos com senha
`password`.

O seeder `DemoAvaCourseSeeder` cria o produto digital `Curso Online de
Informática Popular`, com valor `R$ 0,01`, quatro aulas, matrícula ativa para o
cliente demo e certificado habilitado.

---

## Testes

```bash
composer run test
# no Docker:
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=NomeDoTeste
```

São 46 arquivos de teste entre `tests/Unit` e `tests/Feature`. A suíte roda em
**SQLite em memória** (definido no bloco `<php>` do `phpunit.xml`) — o MySQL de
desenvolvimento não é tocado.

> **Nunca adicione `env_file: [.env]` aos serviços `app` ou `queue` do
> `compose.yaml`.** Isso injeta as variáveis como ambiente real do container,
> que tem precedência sobre o `phpunit.xml`, e `php artisan test` passa a rodar
> `RefreshDatabase` **contra o MySQL de desenvolvimento, apagando os dados
> locais**. O Laravel lê o `.env` do bind mount sozinho.

A suíte cobre, entre outros módulos:

- carrinho e checkout autenticado
- cotação de frete
- Mercado Pago
- governança administrativa
- email marketing
- visibilidade de expositores
- rastreio de pedidos
- FAQ, Q&A e chat
- AVA, materiais e certificados
- API mobile `/api/v1` (autenticação, catálogo, carrinho, checkout, pedidos, chat, endereços, AVA e lojista)
- Inteligência de Cliente (consentimento, dashboard, rastreamento e auditoria)

---

## App Mobile (Flutter)

O diretório `feira_esquerda_livre_app/` contém o aplicativo Flutter, que consome
a API `/api/v1`. Ele tem ciclo de build próprio (Flutter SDK + Android/iOS) e
**não faz parte do build web** — no ambiente Docker o diretório é inclusive
mascarado por um `tmpfs` dentro do container `node`, para o Tailwind não varrer
seus arquivos. Consulte o README dentro daquele diretório.

---

## Inteligência de Cliente

Módulo nativo do projeto: coleta comportamental, análise de visitantes e
métricas de conversão, tudo no próprio banco. Nenhuma dependência externa e
nenhuma chamada HTTP.

### Consentimento (opt-in)

O modelo é **opt-in**: sem aceite explícito não há coleta, não há visitante e
não há cookie de analytics. A decisão fica centralizada no `TrackingPolicy`
(`app/CustomerIntelligence/Support/TrackingPolicy.php`), nunca espalhada pelos
pontos de negócio. O visitante gerencia a escolha em `/privacidade/preferencias`,
e o cookie de preferência vale 12 meses — expirado, o estado volta a `unknown` e
a pergunta pode ser feita de novo.

`CI_ENABLED=false` desliga a coleta inteira sem precisar remover o middleware.

### Funcionalidades

- **Dashboard:** eventos, visitantes, sessões e conversões por período, com tendência diária
- **Rastreamento automático:** produtos, carrinho e pedidos
- **Visitantes:** identidade anônima persistente, vinculada à conta quando o visitante se autentica
- **Eventos:** listagem filtrável e timeline por visitante
- **Auditoria:** trilha das ações administrativas sobre os dados coletados

### Rastreamento de eventos

```php
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;

CustomerIntelligence::track(
    EventName::ProdutoVisualizado,
    ['produto_id' => $produto->id, 'preco' => $produto->price],
    $produto,
);
```

`track()` enfileira na fila `customer-intelligence` e devolve o controle na hora
— a requisição não paga pela gravação. O terceiro argumento é a entidade de
domínio do evento, que vira referência real em vez de ficar dentro do JSON.

Os sete eventos rastreados hoje: `produto.visualizado`,
`produto.adicionado_carrinho`, `produto.removido_carrinho`,
`carrinho.checkout_iniciado`, `pedido.criado`, `pedido.pagamento_confirmado` e
`pedido.enviado`.

### Comandos

```bash
php artisan customer-intelligence:prune-events          # expurga eventos > 180 dias
php artisan customer-intelligence:prune-audit-logs      # expurga auditoria > 730 dias
php artisan customer-intelligence:rebuild-daily-metrics # reconstrói os agregados
php artisan customer-intelligence:forget-user           # direito ao esquecimento (LGPD)
```

Os dois primeiros já rodam pelo scheduler. Os agregados de `ci_daily_metrics`
são permanentes: é o que torna o expurgo dos eventos brutos viável sem perder a
série histórica.

### Painel

`/admin/customer-intelligence`, protegido pela permissão
`customer_intelligence.visualizar`. A auditoria tem permissão própria,
`customer_intelligence.auditoria`.

Consulte [`docs/CUSTOMER_INTELLIGENCE_INTERNAL.md`](docs/CUSTOMER_INTELLIGENCE_INTERNAL.md)
para arquitetura, agregação, retenção e LGPD.

---

## Catalog Intelligence — em construção

Trilha em andamento. Constrói uma camada de inteligência do próprio catálogo —
base de conhecimento, similaridade entre itens, assistente de cadastro e memória
de feedback humano — para ajudar o lojista a descrever produtos, serviços e
cuidados.

Já existe a **base de conhecimento** (tabelas `catalog_*`): conceitos do
catálogo — técnicas, materiais, tipos de item, contextos — com sinônimos,
relações entre si e registro de origem. Cada conhecimento sabe de onde veio, e
só o que uma pessoa aprovou é reutilizado.

E já existe o **motor de similaridade**, que usa essa base para responder quais
conceitos se aplicam a um item e quais itens do catálogo se parecem com ele —
sempre com a razão junto, em português: *"técnica compartilhada: Cerâmica;
atributo compartilhado: Feito à mão"*. Ele é determinístico e auditável, e roda
por linha de comando e testes: **ainda não aparece no cadastro do lojista**.

**Não há geração de texto nem IA externa em lugar nenhum.**

Não é um botão "Gerar com IA": o conhecimento acumulado pela própria Feira vem
primeiro, e IA externa é consultada só quando esse conhecimento não basta. Três
regras inegociáveis:

1. a inteligência **não inventa fatos objetivos** (material, medidas, composição);
2. **gerar nunca é salvar** — toda sugestão passa por revisão humana;
3. **falha da inteligência não impede o cadastro manual**.

Status por fase, arquitetura e riscos:
[`docs/CATALOG_INTELLIGENCE.md`](docs/CATALOG_INTELLIGENCE.md) e
[`docs/ROADMAP_CATALOG_INTELLIGENCE.md`](docs/ROADMAP_CATALOG_INTELLIGENCE.md).

---

# Referência do Produto

## Visão do Produto

A Feira Esquerda Livre está organizada em seis grandes áreas funcionais:

| Área | Descrição |
|---|---|
| CMS e conteúdo | Banners, páginas, menus, posts/notícias, mídia, eventos e configurações globais |
| Marketplace | Catálogo público, lojas, produtos, serviços, cuidados, carrinho, checkout e pedidos multilojas |
| Lojistas | Solicitação pública, aprovação administrativa, painel da loja, produtos, cursos, pedidos, perguntas, feed e relatório de exposição |
| Clientes | Cadastro, pedidos, endereços, checkout autenticado, chats por pedido, aprendizado e certificados |
| Comunidade e marketing | Feed social, moderação, compartilhamento, campanhas de email marketing e descadastro LGPD |
| AVA | Cursos digitais, módulos, aulas, materiais protegidos, progresso do aluno e certificado PDF |

---

## Os Três Eixos do Marketplace

O catálogo é unificado na tabela `products`, mas separado por `item_type` para navegação pública, filtros e campos específicos.

| Eixo | Rota pública | Campos exclusivos |
|---|---|---|
| Produtos | `/produtos` | Estoque, quantidade, peso e dimensões de frete |
| Serviços | `/servicos` | Modalidade, tipo de preço e duração |
| Cuidados & Bem Viver | `/cuidados` | Modalidade, tipo de preço e duração |

Um mesmo lojista pode atuar em todos os eixos. O carrinho e o checkout agrupam itens por loja para gerar splits de pedido.

---

## Rotas Principais

### Público

| Rota | Descrição |
|---|---|
| `GET /` | Homepage com banners, eventos, expositores rotacionados, produtos, serviços, cuidados e posts |
| `GET /produtos` | Catálogo de produtos físicos |
| `GET /servicos` | Catálogo de serviços |
| `GET /cuidados` | Catálogo de cuidados e bem viver |
| `GET /feed` | Feed social público |
| `GET /contato` | Página de contato com telefones, e-mail, endereço e formulário |
| `POST /contato` | Envio do formulário de contato e confirmação automática por e-mail |
| `GET /politica-de-privacidade` | Política de Privacidade |
| `GET /privacidade/preferencias` | Central de preferências de privacidade |
| `POST /privacidade/consentimento` | Registro da escolha de consentimento |
| `GET /termos-de-uso` | Termos de Uso |
| `POST /newsletter` | Inscrição na newsletter |
| `GET /newsletter/descadastro/{token}` | Página de descadastro de campanhas |
| `GET /mk/o/{token}` · `GET /mk/c/{token}` | Rastreio de abertura e de clique em campanhas |
| `GET /seja-um-expositor` | Formulário público de solicitação para novos lojistas |
| `GET /agenda` | Agenda de feiras com filtros |
| `GET /agenda/{slug}` | Detalhe de evento/feira |
| `GET /blog/{slug}` | Detalhe de post/notícia |
| `GET /loja/{slug}` | Página pública de uma loja |
| `GET /loja/{slug}/{productSlug}` | Página pública de produto, serviço ou cuidado |
| `GET /loja/{slug}/{productSlug}/compartilhar.png` | Imagem dinâmica para compartilhamento |
| `GET /checkout` | Finalização de compra |
| `POST /shipping/quote` | Cotação de frete (Melhor Envio ou Frenet) |
| `GET /pedido/{reference}` | Confirmação e acompanhamento do pedido |
| `GET /pedido/{reference}/pagar` | Início do pagamento Mercado Pago |
| `POST /pagamentos/mercado-pago/webhook` | Webhook de confirmação de pagamento |
| `GET /rastreio/{trackingCode}` | Página pública de rastreio de entrega |

### Painel Administrativo (`/admin`)

Requer autenticação, papel interno e permissões via `spatie/laravel-permission`.

| Rota | Descrição |
|---|---|
| `/admin` | Dashboard administrativo |
| `/admin/settings` | Configurações gerais |
| `/admin/settings/mail` | Configurações de email |
| `/admin/settings/checkout` | Frete, checkout e credenciais de pagamento/frete |
| `/admin/settings/checkout/conectar` | Conexão OAuth com o Melhor Envio |
| `/admin/usuarios` | Gestão de usuários internos |
| `/admin/perfis-acesso` | Perfis e permissões |
| `/admin/pages` | Páginas estáticas |
| `/admin/banners` | Banners da home |
| `/admin/menus` | Menus de navegação |
| `/admin/media` | Biblioteca de mídia |
| `/admin/posts` | Posts, notícias e campanhas editoriais |
| `/admin/events` | Eventos e feiras |
| `/admin/expositores` | Gestão de expositores |
| `/admin/expositores/visibilidade` | Rotação, pesos, slots e visibilidade de expositores |
| `/admin/categorias` | Categorias por eixo |
| `/admin/lojistas/solicitacoes` | Aprovação de lojistas |
| `/admin/pedidos` | Pedidos e acompanhamento operacional |
| `/admin/clientes` | Gestão de clientes |
| `/admin/feed/reportes` | Moderação de denúncias do feed |
| `/admin/customer-intelligence` | Dashboard de Inteligência de Cliente |
| `/admin/customer-intelligence/visitantes/{visitor}` | Timeline de um visitante |
| `/admin/customer-intelligence/auditoria` | Trilha de auditoria do módulo |
| `/admin/customer-intelligence/documentacao` | Documentação interna do módulo |
| `/admin/email-marketing` | Campanhas de email marketing |

### Painel do Lojista (`/minha-loja`)

Requer autenticação com papel `lojista` e expositor ativo.

| Rota | Descrição |
|---|---|
| `/minha-loja` | Dashboard do lojista |
| `/minha-loja/loja` | Perfil público da loja |
| `/minha-loja/produtos` | Produtos, serviços e cuidados |
| `/minha-loja/produtos/novo` | Cadastro de item |
| `/minha-loja/produtos/{product}/editar` | Edição de item |
| `/minha-loja/produtos/{product}/imagem-compartilhamento` | Imagem dinâmica para divulgação |
| `/minha-loja/pedidos` | Pedidos recebidos, pagamento, envio e rastreio |
| `/minha-loja/pedidos/{split}/chat` | Chat pós-pedido por loja |
| `/minha-loja/perguntas` | Perguntas públicas de produtos |
| `/minha-loja/feed` | Publicações do lojista no feed |
| `/minha-loja/exposicao` | Relatório de visibilidade na home |
| `/minha-loja/cursos` | Cursos digitais vinculados aos produtos |
| `/minha-loja/cursos/{course}/builder` | Course builder do AVA |

### Área do Cliente (`/minha-conta`)

Requer autenticação.

| Rota | Descrição |
|---|---|
| `/minha-conta/pedidos` | Pedidos do cliente |
| `/minha-conta/enderecos` | Endereços salvos |
| `/minha-conta/aprendizado` | Cursos em que o cliente está matriculado |
| `/minha-conta/aprendizado/{enrollment}/player` | Player do curso |
| `/minha-conta/aprendizado/{enrollment}/certificado` | Download do certificado |
| `/ava/materiais/{material}/download` | Download protegido de materiais por URL assinada |

---

## API Mobile (Flutter)

API REST versionada em `/api/v1`, autenticada via Laravel Sanctum (tokens Bearer), consumida pelo app mobile em Flutter para clientes compradores e lojistas. Reaproveita os mesmos Services do site (`CartService`, `OrderService`, `MercadoPagoService`, `Shipping\MelhorEnvioService`, `AvaEnrollmentService`) — mesmo comportamento de negócio, exposto em JSON.

Cobre: cadastro/login, catálogo público, perguntas de produto, carrinho (exige login), cotação de frete, checkout, pedidos, chat pós-pedido, endereços, AVA (Meu Aprendizado, progresso e certificado) e, para o lojista, perfil da loja, CRUD de produtos, gestão de pedidos recebidos, perguntas, exposição na home e publicação de cursos.

Documentação completa de rotas, formatos de resposta e exemplos: [`docs/API.md`](docs/API.md).

---

## Perfis de Usuário

| Papel | Contexto | Acesso |
|---|---|---|
| `admin` | Equipe interna | Administração completa |
| `gerente` | Equipe interna | Gestão operacional conforme permissões |
| `supervisor` | Equipe interna | Rotinas operacionais específicas |
| `editor` | Equipe interna | CMS, mídia e conteúdo |
| `lojista` | Expositor | Painel da própria loja, produtos, cursos e pedidos |
| `user` | Cliente | Minha conta, pedidos, endereços e aprendizado |

A autorização administrativa combina o enum `UserRole` com roles e permissions do pacote Spatie. O campo `users.role` permanece como compatibilidade e separação de contexto.

---

## Modelos Principais

```text
User                     -> autenticação, papel base e roles Spatie
CustomerProfile          -> perfil comprador e opt-in de marketing
CustomerAddress          -> endereços do cliente
SiteSetting              -> configurações globais e credenciais de integração
Banner                   -> banners da homepage
Menu / MenuItem          -> navegação dinâmica
Page / PageSection       -> páginas estáticas
Post                     -> posts, notícias e campanhas editoriais
Event                    -> feiras/eventos; pivot event_expositores
Expositor                -> loja pública vinculada ao lojista
ExpositorVisibilitySlot  -> slots e prioridades de visibilidade
ExpositorImpression      -> registros assíncronos de impressão
Product                  -> catálogo unificado dos três eixos
ProductFaq               -> FAQ estático por produto
ProductQuestion          -> Q&A público por produto
CartItem                 -> carrinho por sessão/usuário
Order                    -> pedido principal
OrderItem                -> snapshot dos itens comprados
OrderSplit               -> divisão por loja
OrderShipping            -> envio/rastreio por split
OrderTrackingEvent       -> linha do tempo de entrega
OrderMessage             -> chat pós-pedido
NewsletterSubscriber     -> base de newsletter
EmailCampaign            -> campanhas de email marketing
EmailCampaignSend        -> envios, abertura, clique e descadastro
LojistasSolicitacao      -> entrada e aprovação de lojistas
Media                    -> biblioteca de mídia
ContentCategory          -> categorias filtradas por eixo
FeedPost / FeedComment   -> feed social
FeedLike / FeedReport    -> curtidas, denúncias e moderação
AvaCourse                -> curso digital vinculado ao produto
AvaModule / AvaLesson    -> estrutura do curso
AvaLessonMaterial        -> materiais complementares protegidos
AvaEnrollment            -> matrícula do aluno
AvaLessonProgress        -> progresso por aula
```

---

## Enums

| Enum | Valores principais |
|---|---|
| `ItemType` | `produto`, `servico`, `cuidado` |
| `PriceType` | `fixo`, `por_hora`, `por_sessao`, `sob_consulta` |
| `Modality` | `presencial`, `online`, `ambos` |
| `UserRole` | `admin`, `gerente`, `supervisor`, `editor`, `lojista`, `user` |
| `SolicitacaoStatus` | `pendente`, `aprovado`, `bloqueado` |
| `OrderStatus` | `aguardando_pagamento`, `pagamento_confirmado`, `concluido`, `cancelado` |
| `OrderSplitStatus` | `pendente`, `confirmado` |
| `ShippingStatus` | ciclo de envio e entrega |
| `TrackingEventSource` | origem manual ou integração |
| `DeliveryType` | tipos de entrega/retirada |
| `CampaignStatus` | rascunho, agendada, enviada e estados relacionados |
| `RecipientType` | públicos-alvo de campanhas |
| `MarketplaceStatus` | status do perfil de cliente |
| `AvaEnrollmentStatus` | matrícula ativa, expirada, cancelada ou reembolsada |
| `FeedPostType` | tipos de publicação do feed |
| `VisibilitySlotType` | tipos de slot de exposição |

---

## Status do Projeto

Consulte [`docs/ROADMAP.md`](docs/ROADMAP.md) para o planejamento detalhado. O código atual representa um MVP demonstrável, com os principais fluxos públicos, administrativos, de marketplace, comunicação, marketing e AVA já implementados.

| Fase | Status no código | Entregável |
|---|---|---|
| Fase 1 — CMS, Admin & Home | Concluída | Plataforma funcional com conteúdo dinâmico |
| Fase 2 — Lojistas & Agenda | Concluída | Entrada de lojistas, painel da loja e agenda pública |
| Fase 3 — Catálogo & Três Eixos | Concluída | Produtos, serviços, cuidados, loja pública e carrinho multilojas |
| Fase 4 — Checkout, Frete & Pagamentos | MVP avançado | Checkout, pedidos, cotação Melhor Envio/Frenet, Mercado Pago, envio e rastreio por split |
| Fase 5 — Comunidade & Marketing | Implementada no código | Feed social, moderação, email marketing e visibilidade de expositores |
| Fase 6 — Governança Admin | Concluída | Usuários internos, perfis, permissões e proteção de rotas/ações |
| Fase 7 — Comunicação Loja-Cliente | Concluída | FAQ, Q&A público e chat pós-pedido |
| Fase 8 — AVA | Concluída | Course builder, player, materiais protegidos, progresso e certificado PDF |
| Fase 9 — API Mobile (Flutter) | Concluída (v1) | API `/api/v1` com Sanctum, catálogo, carrinho, checkout, pedidos, chat, endereços, AVA e endpoints de lojista |
| Fase 10 — Inteligência de Cliente | Concluída | Módulo nativo, consentimento opt-in, dashboard, auditoria e expurgo automático |
| Trilha CAT — Catalog Intelligence | Em andamento (CAT-04 de 11) | Descrição curta, base de conhecimento e motor de similaridade explicável; assistente de cadastro pela frente |

### Cenário demo do AVA

O MVP inclui um curso online demonstrativo para validar a jornada de produto digital:

1. O expositor `Tecnologia Solidária` possui o produto `Curso Online de Informática Popular`.
2. O produto é digital, custa `R$ 0,01` e pode ser usado em testes de checkout.
3. O cliente `cliente.curso@teste.com` já possui matrícula ativa.
4. Em `/minha-conta/aprendizado`, o cliente pode abrir o player, marcar as quatro aulas como concluídas e baixar o certificado em PDF.

### Pós-MVP planejado

- Split automático completo via Mercado Pago.
- OAuth por lojista para Melhor Envio.
- Compra e geração de etiquetas físicas.
- Auditoria administrativa ampliada.
- Recuperação de carrinho abandonado.
- Integração SendGrid/SES para campanhas em larga escala.
- API: construtor de curso AVA, feed/comunidade e recuperação de senha para o app Flutter.

---

## Princípios Transversais

- **Público 40+ first:** fonte mínima de 16px, botões com área de toque generosa e fluxos sem gestos complexos.
- **Performance em redes lentas:** compressão de imagens, WebP, lazy loading e dependências JavaScript enxutas.
- **Mobile first:** layout pensado a partir de 360px e escalado para tablets/desktops.
- **LGPD:** CPF/CNPJ e dados pessoais tratados com cuidado, consentimento explícito para marketing e analytics, e descadastro disponível.
- **Autorização real:** menus podem esconder opções, mas a proteção precisa estar nas rotas, policies, middlewares e ações Livewire.
