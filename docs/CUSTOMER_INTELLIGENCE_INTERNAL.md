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
| CI-03 | Coleta: visitante e sessão (middleware, cookies) | não iniciada |
| CI-04 | Escrita de eventos pela fila | não iniciada |
| CI-05 | Migração das 7 chamadas atuais | não iniciada |
| CI-06 | Dashboard lendo do banco local | não iniciada |
| CI-07 | Desativação do SDK externo | não iniciada |
| CI-08 | Limpeza de Composer, Docker e `.env` | não iniciada |
| CI-09 | Retenção, LGPD e documentação final | não iniciada |

### O que a CI-02 entregou

Persistência, Models e o caminho de gravação — testados e funcionando.

### O que a CI-02 deliberadamente **não** fez

- não migrou nenhuma das 7 chamadas de rastreamento existentes;
- não gravou nenhum evento real no banco (o módulo não é acionado por nada);
- não removeu o SDK externo, o repositório `path` do Composer nem o volume Docker;
- não criou cookies nem middleware;
- não implementou expurgo, agregadores nem interface administrativa.

Nada do comportamento de produção mudou nesta fase.

---

## Arquitetura

```
Ação de negócio
      │
      ▼
CustomerIntelligenceService::record()      ← existe, testado, sem chamadores
      │
      ├── PropertySanitizer                ← minimização LGPD na escrita
      │
      ▼
ci_events (MySQL local)                    ← nenhuma chamada HTTP
```

O `TrackCustomerEventJob` é o mesmo caminho, fora do ciclo da requisição.
Ele também existe e está testado, mas nada o despacha ainda.

### Estrutura de diretórios

```
app/CustomerIntelligence/
├── Enums/
│   └── EventName.php                      os 7 eventos, tipados
├── Jobs/
│   └── TrackCustomerEventJob.php          gravação assíncrona
├── Models/
│   ├── Visitor.php                        ci_visitors
│   ├── VisitorSession.php                 ci_sessions
│   ├── TrackedEvent.php                   ci_events
│   └── DailyMetric.php                    ci_daily_metrics
├── Services/
│   └── CustomerIntelligenceService.php    porta de entrada do módulo
└── Support/
    └── PropertySanitizer.php              redige dados sensíveis
```

Só existem as pastas que têm conteúdo. Não há `Contracts/`, `DTOs/`,
`Repositories/` nem `Exceptions/` porque nada nesta fase os justificaria — o
projeto usa Services concretos e Eloquent direto, e uma interface com uma única
implementação e nenhum consumidor seria abstração vazia. Quando houver um
segundo implementador ou um ponto de troca real, a interface entra.

### Componentes Livewire ficam fora

O painel administrativo continuará em `app/Livewire/Admin/CustomerIntelligence/`,
**não** dentro do módulo. Motivo concreto: `config/livewire.php` define
`class_namespace => App\Livewire`. Componentes fora desse namespace exigem
registro manual no ServiceProvider — exatamente o que o `AppServiceProvider`
faz hoje com os aliases `jmf-ci-*`. Respeitar a convenção elimina esse registro.

### Ainda não existe um ServiceProvider do módulo

Nada nesta fase precisa de binding, config merge ou middleware — o container
resolve o Service por autowiring. Um provider vazio seria camada artificial.
Ele nasce na CI-03, junto com o middleware de visitante.

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

Decisão registrada: quando a identificação de visitante for implementada, os
nomes de cookie atuais serão **preservados**:

```
jmf_ci_visitor_id     2 anos
jmf_ci_session_id     30 minutos, rolante
```

Renomeá-los zeraria a identidade de todos os visitantes já conhecidos, e o ganho
seria apenas cosmético. O prefixo `jmf_ci_` passa a ser um nome histórico, sem
vínculo com o serviço externo.

**Nenhum cookie foi criado ou alterado nesta fase.** Os cookies que existem hoje
continuam sendo emitidos pelo middleware do SDK externo.

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

Ainda não há chamadores. O caminho previsto:

```php
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;

app(CustomerIntelligenceService::class)->record(
    event: EventName::ProdutoVisualizado,
    properties: ['preco' => 89.90],
    entity: $product,
    session: $session,
);
```

Ou, fora do ciclo da requisição:

```php
TrackCustomerEventJob::dispatch(
    EventName::ProdutoVisualizado,
    ['preco' => 89.90],
    $product,
    $session,
);
```

O job fica na **fila padrão** por enquanto, de propósito: a CI-02 não altera o
serviço `queue` do Docker. A decisão sobre uma fila dedicada
(`customer-intelligence`) pertence à fase seguinte, junto com o ajuste do
parâmetro `--queue` do worker.

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

Um dos testes verifica explicitamente que **nada na aplicação despacha o job**
e que `ci_events` continua vazia após navegar por um produto e adicionar ao
carrinho — a garantia automatizada de que a CI-02 não alterou comportamento.

Os testes rodam em **SQLite em memória** (`phpunit.xml`). O banco MySQL de
desenvolvimento não é tocado.
