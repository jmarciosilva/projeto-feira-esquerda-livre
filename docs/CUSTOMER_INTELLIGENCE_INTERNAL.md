# Customer Intelligence — Módulo Interno

Documentação do módulo nativo de Customer Intelligence da Feira Esquerda Livre.

> **Este documento descreve o módulo interno.** A integração com o SDK externo
> continua ativa e documentada em [JMF_CI_INTEGRATION.md](JMF_CI_INTEGRATION.md).
> Os dois coexistem de propósito durante a migração.

---

## Objetivo

Trazer para dentro do projeto a capacidade de entender o comportamento de
visitantes e clientes: quais produtos são vistos, o que entra no carrinho, onde
o checkout é abandonado, quais lojistas convertem.

## Decisão de internalização

Até aqui, o Customer Intelligence era um **cliente de telemetria**: cada ação
de negócio virava uma requisição HTTP para uma plataforma externa
(`jmf-system/customer-intelligence-sdk`, servida por uma VPS de terceiros).
Nenhum dado comportamental ficava no banco da Feira, e o painel administrativo
só mostrava números enquanto aquele servidor estivesse no ar.

A decisão arquitetural é que **comportamento gerado na Feira Esquerda Livre
pertence à Feira Esquerda Livre**. O módulo passa a gravar localmente, sem
chamada de rede, e o painel passa a ler do próprio MySQL.

Consequências assumidas:

- a Feira deixa de compartilhar dado comportamental com terceiro e passa a ser
  controladora integral — daí a seção de LGPD adiante;
- o painel funciona offline, sem depender de disponibilidade externa;
- `produto_id` e `pedido_id` deixam de ser JSON solto e viram referência real;
- o projeto passa a rodar com um único `git clone`, sem repositório vizinho.

---

## Status da migração

| Fase | Escopo | Status |
|---|---|---|
| **CI-01** | Auditoria e arquitetura | **CONCLUÍDA** |
| **CI-02** | Fundação do módulo interno | **CONCLUÍDA** |
| **CI-03** | Coleta: visitante e sessão (middleware, cookies) | **CONCLUÍDA** |
| **CI-04** | Escrita de eventos pela fila dedicada | **CONCLUÍDA** |
| CI-05 | Migração das 7 chamadas atuais | não iniciada |
| CI-06 | Dashboard lendo do banco local | não iniciada |
| CI-07 | Desativação do SDK externo | não iniciada |
| CI-08 | Limpeza de Composer, Docker e `.env` | não iniciada |
| CI-09 | Retenção, LGPD e documentação final | não iniciada |

### O que a CI-02 entregou

Persistência, Models e o caminho de gravação — testados e funcionando.

### O que a CI-03 entregou

Coleta real. O módulo passou a resolver visitante e sessão a cada requisição
web e a gravá-los em `ci_visitors` e `ci_sessions`. Nasceram o ServiceProvider,
o middleware `TrackVisitorSession`, o `VisitorContext` por requisição e o
arquivo `config/customer-intelligence-internal.php`.

**A coleta de visitante está ligada; a de eventos, não.** `ci_events` continua
vazia, e os sete eventos seguem saindo pelo SDK externo até a CI-05.

### O que a CI-04 entregou

O caminho assíncrono de gravação: `track()` enfileira, o worker grava. Fila
própria `customer-intelligence`, declarada no `--queue` do worker do Docker.

**A escrita de eventos existe e funciona, mas ninguém a aciona.** `ci_events`
continua vazia em uso normal.

### O que ainda **não** foi feito

- nenhuma das 7 chamadas de rastreamento foi migrada;
- nenhum evento é gravado no banco local (sem escrita dupla);
- o SDK externo, o repositório `path` do Composer e o volume Docker continuam;
- não há expurgo, agregadores nem interface administrativa nova.

Nada do comportamento de produção mudou até aqui.

---

## Arquitetura

```
Requisição web
      │
      ├── TrackVisitorSession (middleware)  → ci_visitors · ci_sessions
      │        └── VisitorContext           ← visitante da requisição
      │
Ação de negócio                             ← ainda ninguém chama daqui
      │
      ▼
CustomerIntelligenceService::track()        ← captura sessão, usuário, instante
      │
      ▼
TrackCustomerEventJob                       ← fila customer-intelligence
      │
      ▼
CustomerIntelligenceService::record()
      │
      ├── PropertySanitizer                 ← minimização LGPD na escrita
      │
      ▼
ci_events (MySQL local)                     ← nenhuma chamada HTTP
```

Da coleta de visitante para baixo tudo existe e está testado. O que falta é a
seta pontilhada: **nenhuma ação de negócio chama `track()` ainda**. Os sete
eventos continuam saindo pelo SDK externo até a CI-05.

### Estrutura de diretórios

```
app/CustomerIntelligence/
├── Actions/
│   └── ResolveVisitorSession.php          encontra ou abre visitante e sessão
├── Enums/
│   └── EventName.php                      os 7 eventos, tipados
├── Http/Middleware/
│   └── TrackVisitorSession.php            coleta a cada requisição web
├── Jobs/
│   └── TrackCustomerEventJob.php          gravação assíncrona
├── Models/
│   ├── Visitor.php                        ci_visitors
│   ├── VisitorSession.php                 ci_sessions
│   ├── TrackedEvent.php                   ci_events
│   └── DailyMetric.php                    ci_daily_metrics
├── Services/
│   └── CustomerIntelligenceService.php    porta de entrada do módulo
├── Support/
│   ├── PropertySanitizer.php              redige dados sensíveis
│   └── VisitorContext.php                 visitante/sessão da requisição
└── CustomerIntelligenceServiceProvider.php
```

Só existem as pastas que têm conteúdo. Não há `Contracts/`, `DTOs/`,
`Repositories/` nem `Exceptions/` porque nada até aqui os justificaria — o
projeto usa Services concretos e Eloquent direto, e uma interface com uma única
implementação e nenhum consumidor seria abstração vazia. Quando houver um
segundo implementador ou um ponto de troca real, a interface entra.

A regra de sessão vive em `Actions/ResolveVisitorSession`, e não no middleware,
porque "quando uma sessão termina e outra começa" não depende de HTTP — assim
fica testável sem simular requisição.

### Componentes Livewire ficam fora

O painel administrativo continuará em `app/Livewire/Admin/CustomerIntelligence/`,
**não** dentro do módulo. Motivo concreto: `config/livewire.php` define
`class_namespace => App\Livewire`. Componentes fora desse namespace exigem
registro manual no ServiceProvider — exatamente o que o `AppServiceProvider`
faz hoje com os aliases `jmf-ci-*`. Respeitar a convenção elimina esse registro.

### ServiceProvider e ordem do middleware

`CustomerIntelligenceServiceProvider` (registrado em `bootstrap/providers.php`)
faz o merge da configuração, registra o `VisitorContext` como `scoped` e anexa
o middleware ao grupo `web`.

Ele **não** é registrado em `bootstrap/app.php`, e isso é deliberado. Enquanto o
SDK externo existe, os dois middlewares convivem no mesmo grupo e emitem os
mesmos cookies. Duas medidas evitam que briguem:

**1. Ordem determinística.** A configuração de `bootstrap/app.php` é aplicada
antes de qualquer `boot()`; providers da aplicação inicializam depois dos
descobertos por pacote. Registrando pelo provider, o middleware do módulo entra
na pilha **depois** do middleware do SDK e enxerga o que ele já decidiu.

**2. Adoção do valor já enfileirado.** Numa primeira visita não há cookie na
requisição e o SDK acabou de gerar um identificador próprio. Em vez de gerar
outro — o que faria o servidor remoto e o banco local conhecerem o mesmo
visitante por dois nomes —, o middleware lê o valor enfileirado via
`Cookie::queued()`. Sem acoplamento a classes do SDK: é API do próprio Laravel.

Reenfileirar o cookie com o mesmo nome é seguro: o CookieJar indexa a fila por
nome e caminho, então a segunda chamada substitui a primeira e apenas um
`Set-Cookie` sai na resposta. Há testes para as duas coisas — a ordem na pilha e
a unicidade do cookie.

Quando o SDK for removido (CI-07), nada disso precisa mudar: sem ninguém para
adotar, o middleware passa a gerar os próprios identificadores.

---

## Banco de dados

### `ci_visitors`

Visitante conhecido pelo sistema, autenticado ou anônimo.

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | chave interna |
| `visitor_uuid` | uuid **unique** | identificador público |
| `user_id` | bigint null → `users.id` | `nullOnDelete` |
| `first_seen_at` / `last_seen_at` | timestamp null | |
| `metadata` | json null | comportamental mínimo, nunca dado pessoal |

Índices: `visitor_uuid` (unique), `last_seen_at`, FK em `user_id`.

### `ci_sessions`

Janela de navegação. Nome `ci_sessions` para não colidir com `sessions`, que é
a tabela de sessão do próprio Laravel (`SESSION_DRIVER=database`).

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `session_uuid` | uuid **unique** | |
| `visitor_id` | bigint → `ci_visitors.id` | `cascadeOnDelete` |
| `started_at` | timestamp | |
| `last_activity_at` / `ended_at` | timestamp null | |
| `landing_url`, `referrer` | varchar(512) null | |
| `utm_source`, `utm_medium`, `utm_campaign` | varchar(128) null | |

Índices: `session_uuid` (unique), `started_at`, FK em `visitor_id`.

### `ci_events`

Tabela principal. **Append-only**: um evento é um fato ocorrido, nunca editado.
Por isso não existe `updated_at` — só `created_at`.

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `event_uuid` | uuid **unique** | |
| `visitor_id` | bigint null → `ci_visitors.id` | `nullOnDelete` |
| `session_id` | bigint null → `ci_sessions.id` | `nullOnDelete` |
| `user_id` | bigint null → `users.id` | `nullOnDelete` |
| `event_name` | varchar(64) | valor do enum `EventName` |
| `event_category` | varchar(32) null | prefixo do nome |
| `entity_type` / `entity_id` | varchar(64) / bigint, null | morph para Product, Order… |
| `properties` | json null | payload do evento, já sanitizado |
| `occurred_at` | timestamp | instante do fato |
| `created_at` | timestamp null | instante da gravação |

Índices: `event_uuid` (unique), `(event_name, occurred_at)`, `(occurred_at)`,
`(entity_type, entity_id)`, mais os das três FKs.

`occurred_at` e `created_at` divergem quando a gravação passa pela fila — o job
congela `occurred_at` no momento do despacho, não do processamento.

Todas as três referências são nulas de propósito: um evento sem visitante
resolvido é gravado mesmo assim, em vez de descartado.

### `ci_daily_metrics`

Agregado diário pré-calculado. Retenção permanente.

| Coluna | Tipo | Observação |
|---|---|---|
| `metric_date` | date | |
| `metric_name` | varchar(64) | |
| `dimension_type` | varchar(32) **not null**, default `''` | |
| `dimension_value` | varchar(128) **not null**, default `''` | |
| `metric_value` | decimal(20,4) | |

Chave única: `(metric_date, metric_name, dimension_type, dimension_value)`.

**Por que as dimensões não são nulas.** No MySQL, valores `NULL` são tratados
como distintos entre si dentro de um índice `UNIQUE` — uma coluna nulável
permitiria gravar a mesma métrica global várias vezes, e a chave única não
cumpriria seu papel. A string vazia representa “sem dimensão” e faz a restrição
funcionar de verdade. `DailyMetric::record()` já usa `''` como padrão.

Esta tabela é o que torna a retenção curta de `ci_events` viável: o painel lê
daqui, então o evento bruto pode ser expurgado sem apagar a série histórica.

---

## Models

| Model | Tabela | Relacionamentos |
|---|---|---|
| `Visitor` | `ci_visitors` | `belongsTo User`, `hasMany VisitorSession`, `hasMany TrackedEvent` |
| `VisitorSession` | `ci_sessions` | `belongsTo Visitor`, `hasMany TrackedEvent` |
| `TrackedEvent` | `ci_events` | `belongsTo Visitor`, `belongsTo VisitorSession`, `belongsTo User`, `morphTo entity` |
| `DailyMetric` | `ci_daily_metrics` | — |

### Nomes

`TrackedEvent` e não `Event`: `App\Models\Event` já existe e representa uma
**feira da agenda**, coisa completamente diferente. `VisitorSession` e não
`Session` pelo mesmo motivo, em relação à sessão do Laravel.

---

## Estratégia de UUID

Cada tabela tem **duas identidades**:

- `id` — bigint auto incremental, chave primária e alvo das foreign keys;
- `*_uuid` — UUID ordenado, identificador público/técnico, com índice único.

Os Models usam a trait `HasUuids` do Laravel com `uniqueIds()` sobrescrito para
apontar para a coluna UUID em vez da chave primária. Como o nome da chave não
está em `uniqueIds()`, `getKeyType()` continua `int` e `getIncrementing()`
continua `true` — o `id` permanece auto incremental, e só o UUID é gerado
automaticamente.

O UUID é **ordenado** (`Str::orderedUuid()`, prefixado por timestamp). Em uma
tabela que cresce por append como `ci_events`, isso mantém a localidade do
índice: inserções ficam concentradas no fim da árvore em vez de espalhadas.

Um UUID informado explicitamente é preservado, o que permite idempotência
quando a gravação passar pela fila.

---

## Política de retenção

| Dado | Retenção |
|---|---|
| `ci_events` (evento bruto) | **180 dias** |
| `ci_sessions` | acompanha os eventos |
| `ci_visitors` | longa; anonimizar após inatividade prolongada |
| `ci_daily_metrics` (agregado) | **permanente** |

**Nenhuma rotina automática de exclusão foi criada nesta fase.** Não existe
comando de expurgo nem tarefa no scheduler. A implementação virá em fase
própria, com testes e aprovação explícita — apagar dado é irreversível e não
deve entrar de carona numa fase de fundação.

---

## LGPD e minimização

O que o módulo **não** armazena, por decisão:

- endereço IP, completo ou parcial;
- user-agent;
- nome, e-mail, telefone, CPF, CNPJ ou endereço;
- qualquer cópia de dado pessoal que já viva em `users`.

Onde há dado pessoal, o módulo **referencia** em vez de copiar: `user_id`
aponta para `users`, e nome e e-mail são alcançados pela relação. Se algum dado
pessoal precisar ser adicionado no futuro, a justificativa deve ser registrada
aqui antes da implementação.

### Sanitização na escrita

`PropertySanitizer` percorre as propriedades do evento antes da gravação e
**redige** valores cujas chaves aparentem carregar dado sensível — senha, token,
cartão, CPF, CNPJ, documento e afins, sem diferenciar maiúsculas, recursivamente
até cinco níveis.

A diferença deliberada em relação ao `PayloadValidator` do SDK: o SDK lançava
exceção, o módulo interno apenas substitui o valor por `[redigido]`.
Rastreamento nunca deve derrubar um fluxo de compra por causa de uma chave mal
escolhida — mas também não deve gravar o dado.

### Pontos ainda em aberto

Serão tratados na fase de LGPD, não agora:

- consentimento e eventual banner de rastreamento;
- comando de exclusão por titular (`visitor_uuid` ou `user_id`);
- anonimização ao excluir a conta — hoje o `nullOnDelete` já preserva o
  histórico agregado e desliga o vínculo pessoal;
- auditoria de quem consulta o painel (a permissão
  `customer_intelligence.visualizar` já isola o acesso).

---

## Cookies

O middleware do módulo emite dois cookies, com os nomes **preservados** do SDK:

| Cookie | Validade | Papel |
|---|---|---|
| `jmf_ci_visitor_id` | 2 anos | identidade anônima persistente |
| `jmf_ci_session_id` | 30 minutos, rolante | janela de navegação |

Renomeá-los zeraria a identidade de todos os visitantes já conhecidos, e o ganho
seria apenas cosmético. O prefixo `jmf_ci_` passa a ser um nome histórico, sem
vínculo com o serviço externo.

Nomes e validades são configuráveis por `CI_VISITOR_COOKIE_NAME`,
`CI_VISITOR_COOKIE_MINUTES`, `CI_SESSION_COOKIE_NAME` e
`CI_SESSION_COOKIE_MINUTES`. Os valores padrão espelham os do SDK de propósito,
para que os dois middlewares não disputem a validade do mesmo cookie durante a
coexistência.

A rotação da sessão acontece no servidor, não só no cookie: se a última
atividade for mais antiga que a janela, ou se o registro pertencer a outro
visitante, `ResolveVisitorSession` encerra a sessão anterior (preenchendo
`ended_at`) e abre outra — mantendo o mesmo visitante.

`CI_ENABLED=false` desliga a coleta por completo: o middleware não grava nada e
não emite cookies.

---

## Eventos

Os sete eventos que o projeto rastreia hoje estão tipados em
`App\CustomerIntelligence\Enums\EventName`:

| Caso | Valor | Categoria |
|---|---|---|
| `ProdutoVisualizado` | `produto.visualizado` | produto |
| `ProdutoAdicionadoCarrinho` | `produto.adicionado_carrinho` | produto |
| `ProdutoRemovidoCarrinho` | `produto.removido_carrinho` | produto |
| `CarrinhoCheckoutIniciado` | `carrinho.checkout_iniciado` | carrinho |
| `PedidoCriado` | `pedido.criado` | pedido |
| `PedidoPagamentoConfirmado` | `pedido.pagamento_confirmado` | pedido |
| `PedidoEnviado` | `pedido.enviado` | pedido |

Os valores são idênticos aos que o SDK externo já envia. Isso preserva o
histórico e torna a migração da fase seguinte uma troca mecânica: o `use` no
topo do arquivo muda e a string vira um caso do enum.

`event_category` é derivada do prefixo, para permitir agrupar por família sem
`LIKE` no nome.

---

## Como usar (quando estiver ligado)

Ainda não há chamadores. O caminho previsto para a CI-05:

```php
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;

app(CustomerIntelligenceService::class)->track(
    event: EventName::ProdutoVisualizado,
    properties: ['preco' => 89.90],
    entity: $product,
);
```

`track()` enfileira e devolve o controle na hora — é o que a aplicação deve
usar, para não pagar a gravação dentro da requisição. A sessão vem do
`VisitorContext` sozinha; passá-la explicitamente é opcional.

Quando o registro gravado for necessário de volta, `record()` escreve na hora e
devolve o `TrackedEvent`. É o que o job usa por dentro.

### Fila

| | |
|---|---|
| Fila | `customer-intelligence` (`CI_QUEUE`) |
| Conexão | padrão da aplicação (`CI_QUEUE_CONNECTION` sobrescreve) |
| Tentativas | 3 |

A fila é própria, e não a `default`, porque rastreamento é o trabalho menos
urgente do sistema. O worker do Docker declara
`--queue=default,email-marketing,customer-intelligence`, e **a ordem é
prioridade**: a fila do módulo só é olhada quando as outras estão vazias, então
um pico de navegação nunca atrasa um e-mail de pedido.

Um teste lê o `compose.yaml` e falha se a fila sumir do `--queue` — é a maneira
de impedir o erro clássico de despachar para uma fila que ninguém escuta.

### O que viaja com o job

Sessão, usuário autenticado e o instante do fato são capturados **no despacho**,
porque dentro do worker não existe cookie nem usuário logado para consultar.
Por isso `occurred_at` (quando o fato aconteceu) e `created_at` (quando a linha
foi gravada) divergem de propósito — o atraso da fila não desloca a história.

O usuário capturado tem precedência sobre `Auth::id()` na hora de gravar: o job
pode rodar depois do logout.

---

## Testes

```bash
docker compose exec app php artisan test tests/Feature/CustomerIntelligence
```

| Arquivo | Cobre |
|---|---|
| `InternalSchemaTest` | tabelas, colunas, nulabilidade, unicidade, FKs, índices críticos |
| `InternalModelsTest` | UUIDs, casts, relacionamentos, enum |
| `InternalEventRecordingTest` | gravação, morph, sanitização, job |
| `InternalVisitorTrackingTest` | middleware, cookies, rotação de sessão, identificação, UTMs, contexto |
| `InternalEventQueueTest` | fila dedicada, dados capturados no despacho, gravação ponta a ponta |

Dois testes guardam a fronteira desta fase: um verifica que **nada na aplicação
despacha o job** e que `ci_events` continua vazia após navegar por um produto e
adicionar ao carrinho; outro navega por duas páginas carregando os cookies, como
um navegador faria, e confirma que o visitante é único e que nenhum evento foi
gravado.

Outros dois cobrem o risco da coexistência: que o middleware do módulo está na
pilha **depois** do middleware do SDK, e que sai **um único** `Set-Cookie` de
cada nome.

Os testes rodam em **SQLite em memória** (`phpunit.xml`). O banco MySQL de
desenvolvimento não é tocado.
