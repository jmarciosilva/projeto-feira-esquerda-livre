# Customer Intelligence — Módulo Interno

Documentação do módulo nativo de Customer Intelligence da Feira Esquerda Livre.

> **O Customer Intelligence é 100% interno.** Desde a CI-08 não existe mais SDK
> externo: nem no Composer, nem no Docker, nem no ambiente. O projeto funciona
> com um único `git clone`.

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
| **CI-05** | Migração das 7 chamadas atuais | **CONCLUÍDA** |
| **CI-06** | Painel e agregação local | **CONCLUÍDA** |
| **CI-07** | Desativação do SDK externo em runtime | **CONCLUÍDA** |
| **CI-08** | Remoção física do SDK externo | **CONCLUÍDA** |
| **CI-09** | Confiabilidade, retenção, LGPD e limpeza final | **CONCLUÍDA** |

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

### O que a CI-05 entregou

**A virada.** As sete chamadas de rastreamento deixaram de apontar para o SDK
externo e passaram a alimentar o módulo interno. Corte direto, sem escrita
dupla, conforme a decisão 1 da auditoria.

Nasceu a fachada `App\CustomerIntelligence\Facades\CustomerIntelligence`, para
que as chamadas continuassem se lendo como sempre — a migração virou uma troca
de `use` mais a substituição da string pelo enum, conforme a decisão 6.

**Nenhum evento sai mais da aplicação por HTTP.** Os produtos, pedidos e splits
envolvidos deixaram de viver apenas dentro do JSON de `properties` e viraram
referência real em `entity_type`/`entity_id`.

### O que a CI-06 entregou

O painel deixou de falar com a plataforma externa. Dashboard, eventos,
visitantes e detalhe passaram a ler `ci_daily_metrics`, `ci_events`,
`ci_visitors` e `ci_sessions` — nenhuma chamada HTTP em nenhuma das telas.

Nasceram a camada de consultas (`Queries/`), a agregação diária incremental e o
comando de reconstrução dos agregados. A tela de detalhe do visitante, que
existia como view mas nunca teve rota, passou a ser alcançável.

**A leitura e a escrita do Customer Intelligence pertencem agora ao próprio
projeto.**

### O que a CI-07 entregou

O SDK saiu do runtime. O ServiceProvider dele deixou de ser descoberto pelo
Composer, então nada mais dele executa: nem o middleware, nem o registro de
componentes Livewire, nem a validação de configuração no boot.

Uma busca por `JmfSystem\` em `app/`, `routes/`, `bootstrap/` e `config/`
retorna **zero**. O pacote continua instalado no `vendor/`, mas a aplicação não
usa nenhuma classe dele.

### O que a CI-08 entregou

A remoção física. Saíram o pacote do Composer, o bloco `repositories` do tipo
`path`, os dois bind mounts `../jmf-ci-sdk` do `compose.yaml`, a configuração
publicada do SDK, as quatro variáveis `JMF_CI_*` e a documentação operacional
obsoleta.

Provado com instalação limpa: volume `vendor` destruído e recriado do zero,
`composer install` com `/var/www/jmf-ci-sdk` inexistente dentro do container —
123 pacotes instalados, nenhum erro, `vendor/jmf-system` não recriado.

### O que a CI-09 entregou

**Idempotência.** O `event_uuid` passou a nascer no despacho e a viajar com o
job. Uma retentativa reconhece o evento já gravado pela chave única de
`ci_events` e não duplica nem o evento nem os agregados. A proteção é do banco,
não de um `if` em PHP — resiste a corrida.

**Retenção operacional.** `customer-intelligence:prune-events`, com `--dry-run`,
processamento em lotes e agendamento diário às 03:20. Os agregados são
preservados.

**LGPD.** Política documentada e testada: minimização, pseudonimização,
desvínculo na exclusão de conta e `customer-intelligence:forget-user` para
pedidos de eliminação sem encerrar a conta.

**Limpeza.** Comentários que narravam a migração deram lugar a comentários que
descrevem o sistema atual. Oito arquivos órfãos removidos.

### O que ficou de fora, por decisão de produto

- **banner de consentimento** para rastreamento comportamental;
- **auditoria de quem consulta o painel** — o acesso é restrito pela permissão
  `customer_intelligence.visualizar`, mas não há trilha de quem olhou o quê;
- as views em `resources/views/plugins/jmf-ci/` e os três componentes
  `x-jmf-ci-*` restantes continuam com os nomes antigos. Estão em uso e o
  prefixo é apenas nominal; renomear seria cosmética com risco desnecessário;
- os cookies continuam `jmf_ci_*`, para não zerar a identidade dos visitantes
  já conhecidos.

---

## Arquitetura

```
Requisição web
      │
      ├── TrackVisitorSession (middleware)  → ci_visitors · ci_sessions
      │        └── VisitorContext           ← visitante da requisição
      │
Ação de negócio                             ← os 7 eventos entram aqui
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

Desde a CI-05 o caminho está inteiro: as sete ações de negócio chamam `track()`
e os eventos terminam em `ci_events`, sem nenhuma chamada de rede.

### Estrutura de diretórios

```
app/CustomerIntelligence/
├── Actions/
│   └── ResolveVisitorSession.php          encontra ou abre visitante e sessão
├── Enums/
│   └── EventName.php                      os 7 eventos, tipados
├── Facades/
│   └── CustomerIntelligence.php           fachada usada pelas 7 chamadas
├── Console/
│   └── RebuildDailyMetricsCommand.php     reconstroi os agregados
├── Http/Middleware/
│   └── TrackVisitorSession.php            coleta a cada requisição web
├── Jobs/
│   └── TrackCustomerEventJob.php          gravação assíncrona
├── Models/
│   ├── Visitor.php                        ci_visitors
│   ├── VisitorSession.php                 ci_sessions
│   ├── TrackedEvent.php                   ci_events
│   └── DailyMetric.php                    ci_daily_metrics
├── Queries/
│   ├── DashboardQuery.php                 cartões, gráfico e recentes
│   ├── EventQuery.php                     listagem e timeline
│   └── VisitorQuery.php                   listagem e detalhe
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

O painel administrativo vive em `app/Livewire/Admin/CustomerIntelligence/`,
**não** dentro do módulo. Motivo concreto: `config/livewire.php` define
`class_namespace => App\Livewire`, e componentes fora desse namespace exigiriam
registro manual no ServiceProvider — exatamente o que o `AppServiceProvider`
fazia com os cinco aliases `jmf-ci-*` do SDK. Respeitando a convenção, os quatro
componentes internos são descobertos sozinhos e nenhum registro é preciso.

A CI-07 removeu esses cinco registros manuais e tirou o namespace do SDK de
`class_namespaces`.

### ServiceProvider e ordem do middleware

`CustomerIntelligenceServiceProvider` (registrado em `bootstrap/providers.php`)
faz o merge da configuração, registra o `VisitorContext` como `scoped` e anexa
o middleware ao grupo `web`.

Ele **não** é registrado em `bootstrap/app.php`, e isso foi deliberado: enquanto
o middleware do SDK existiu, o do módulo precisava entrar na pilha **depois**
dele. A configuração de `bootstrap/app.php` é aplicada antes de qualquer
`boot()`, e providers da aplicação inicializam depois dos descobertos por
pacote — registrar pelo provider garantia a ordem.

Desde a CI-07 a pilha do grupo `web` termina apenas com `TrackVisitorSession`:

```
[0] EncryptCookies              [4] ValidateCsrfToken
[1] AddQueuedCookiesToResponse  [5] SubstituteBindings
[2] StartSession                [6] TrackVisitorSession   ← único coletor
[3] ShareErrorsFromSession
```

A adoção do cookie já enfileirado (`Cookie::queued()`) continua no código e
deixou de ter efeito prático — sem ninguém para adotar, o middleware gera os
próprios identificadores. Foi mantida por ser API do próprio Laravel, sem
acoplamento a classe nenhuma, e por proteger o caso de outro middleware vir a
emitir os mesmos cookies.

Reenfileirar o cookie com o mesmo nome é seguro: o CookieJar indexa a fila por
nome e caminho, então apenas um `Set-Cookie` sai na resposta. Há teste para a
unicidade do cookie e para a ausência de qualquer middleware `JmfSystem\` no
grupo `web`.

### Como o SDK saiu

Em duas etapas, de propósito. A **CI-07** desligou o runtime com uma linha
declarativa em `composer.json` — `dont-discover` — que fez o Laravel parar de
auto-registrar o ServiceProvider do pacote, derrubando de uma vez o middleware,
os cinco registros Livewire, o merge de configuração e o `ConfigValidator`.

Com o runtime já provado independente, a **CI-08** removeu o pacote de verdade:
`composer remove`, o bloco `repositories`, os bind mounts e o `dont-discover`,
que deixou de fazer sentido apontando para pacote inexistente.

Separar as duas etapas permitiu validar cada risco isoladamente: primeiro que
nada usava o SDK, depois que nada precisava dele instalado.

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

**`metric_date` é sempre canônico (`Y-m-d`).** Os três caminhos de escrita —
`IncrementDailyMetric`, o comando de reconstrução e `DailyMetric::record()` —
gravam pelo query builder justamente por isso: o cast `date` do Eloquent
reformataria a data com o formato do grammar (`Y-m-d H:i:s`) na escrita, e o
valor gravado divergiria do usado na busca. O MySQL disfarçaria, porque a coluna
é `DATE` e normaliza; o SQLite não. Sem essa garantia, a chave única composta
poderia tratar o mesmo dia como dois.

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

## Painel administrativo

Desde a CI-06 o painel lê exclusivamente o banco local. Não há nenhuma chamada
HTTP em nenhuma das telas.

```
Livewire (App\Livewire\Admin\CustomerIntelligence)
   │
   ▼
Queries (App\CustomerIntelligence\Queries)
   │
   ├── DashboardQuery  → ci_daily_metrics (cartões e gráfico)
   │                     ci_events · ci_visitors (listas de recentes)
   ├── EventQuery      → ci_events
   └── VisitorQuery    → ci_visitors + users (por relação)
```

As queries vivem fora dos componentes de propósito: um componente Livewire cheio
de `selectRaw` e `groupBy` é difícil de testar e impossível de reaproveitar.

| Tela | Componente | Fonte |
|---|---|---|
| Dashboard | `Dashboard` | `ci_daily_metrics` + recentes |
| Eventos | `EventIndex` | `ci_events`, paginado no banco |
| Visitantes | `VisitorIndex` | `ci_visitors` + `users` |
| Detalhe | `VisitorShow` | `ci_visitors` + timeline de `ci_events` |

Os componentes ficam em `App\Livewire\Admin\CustomerIntelligence`, respeitando o
`class_namespace` de `config/livewire.php` — nenhum registro manual é preciso.

### O que mudou na interface

O layout foi preservado. Duas exceções, ambas por não ter como reproduzir o dado
com honestidade:

**`lead_score` deixou de existir.** Vinha do CRM da plataforma remota, que nunca
recebeu um `identify()` — na prática o número sempre foi de um cadastro que o
projeto não alimentava. A coluna passou a mostrar a **contagem de eventos** do
visitante, que é um dado real do banco local.

**O card "Validar Conexão" saiu do dashboard.** Ele testava a conexão com a VPS.
Sem servidor externo, não há conexão a validar. O componente do SDK continua
registrado até a CI-07, apenas não é mais renderizado.

A tela de detalhe do visitante ganhou rota
(`admin.customer-intelligence.visitante`) e passou a mostrar identificador,
primeira visita, última visita e contagem de sessões e eventos. Antes ela existia
como view mas era inalcançável: as rotas do plugin do SDK nunca foram
registradas.

---

## Agregação diária

`ci_daily_metrics` guarda agregados, nunca cópias de eventos.

| Métrica | Dimensão | Quando é incrementada |
|---|---|---|
| `eventos` | — | a cada evento gravado |
| `eventos` | `event_name` | a cada evento gravado, por tipo |
| `sessoes` | — | a cada sessão aberta |
| `visitantes` | — | na primeira sessão do visitante no dia |
| `conversoes` | — | a cada `pedido.criado` |

**Conversão é `pedido.criado`.** Na plataforma externa isso vivia numa
configuração do servidor; aqui é uma decisão explícita do projeto, registrada em
`MetricName::conversionEvent()`.

### Quando ocorre

De forma **incremental**, no momento em que o evento é gravado — ou seja, dentro
do job da fila, fora do caminho da requisição. O painel nunca recalcula o
histórico: ele soma linhas já prontas de `ci_daily_metrics`.

A contagem de sessões e visitantes acontece na abertura da sessão, que é o único
ponto no caminho da requisição. Sessões são raras — uma por janela de 30 minutos
por visitante —, então o custo é desprezível.

### Concorrência

Dois jobs podem incrementar a mesma métrica ao mesmo tempo. Ler o valor em PHP,
somar e gravar perderia incrementos. Por isso a soma acontece no banco:

```sql
UPDATE ci_daily_metrics SET metric_value = metric_value + ? WHERE ...
```

atômico tanto no MySQL quanto no SQLite. Se a linha ainda não existir, tentamos
inserir; se outra conexão inserir primeiro, a chave única rejeita a segunda
tentativa e caímos de volta no incremento. Nada se perde e nada duplica.

Não há ramificação por dialeto de banco: `increment()` do query builder resolve
igual nos dois.

### Visitantes distintos

É a única métrica que não é aditiva — somar visitantes de dois dias não dá
visitantes distintos no período. Duas consequências assumidas:

- no incremento, contamos apenas quando é a **primeira sessão do visitante no
  dia**, o que uma consulta indexada por `visitor_id` resolve;
- o total exibido para um período é a **soma dos dias**, então um visitante que
  volta em dias diferentes conta uma vez por dia. É a mesma semântica de
  "visitantes diários" que a plataforma externa usava.

### Reconstrução

```bash
docker compose exec app php artisan customer-intelligence:rebuild-daily-metrics
docker compose exec app php artisan customer-intelligence:rebuild-daily-metrics --from=2026-08-01 --to=2026-08-25
```

Recalcula os agregados a partir de `ci_events` e `ci_sessions`. Existe para os
casos em que o incremento não aconteceu: uma falha de job, uma importação, ou
uma correção de métrica.

**Idempotente:** apaga os agregados do intervalo antes de recalcular, então rodar
duas vezes produz o mesmo resultado. **Nunca toca os eventos brutos** — só
`ci_daily_metrics`.

**Nunca vai além do que pode reconstruir.** Como os eventos brutos são expurgados
aos 180 dias e os agregados são permanentes, reconstruir um período cujo evento
já saiu zeraria a série histórica. Duas proteções:

- um `--from` anterior ao evento mais antigo disponível é ajustado, com aviso;
- sem nenhum evento em `ci_events`, o comando **não apaga nada** e informa.

`ci_sessions` não serve como fonte substituta para decidir o que apagar — as
sessões não são expurgadas junto com os eventos e sobreviveriam a eles.

---

## Índices e performance

A CI-06 acrescentou **um** índice:

| Índice | Query que o justificou |
|---|---|
| `ci_events (visitor_id, occurred_at)` | timeline do detalhe: `WHERE visitor_id = ? ORDER BY occurred_at DESC`. A foreign key já cobria o filtro, mas a ordenação caía em filesort. Como o índice começa por `visitor_id`, também satisfaz a exigência da FK — nada ficou redundante. |

Os demais índices, criados na CI-02, atendem as consultas desta fase sem
alteração: `(event_name, occurred_at)` para o filtro por tipo, `(occurred_at)`
para recortes de período, e a chave única de `ci_daily_metrics`, que começa por
`metric_date`, para as somas do dashboard.

### Medições

Massa sintética, em SQLite de teste:

| Tela | 10.000 eventos | 100.000 eventos |
|---|---|---|
| Dashboard | 5 queries · 375 ms | 5 queries · 699 ms |
| Eventos | 3 queries · 211 ms | 3 queries · 167 ms |
| Visitantes | 2 queries · 159 ms | 2 queries · 121 ms |
| Detalhe | 5 queries · 135 ms | 5 queries · 119 ms |

O que importa é a **forma** das consultas: a contagem de queries não muda com o
volume. O teste falha se qualquer tela passar de 15 queries — um N+1 numa página
de 50 eventos passaria disso com folga.

Foi assim que um N+1 real apareceu durante a implementação: a timeline do
detalhe fazia 29 queries porque carregava visitante e usuário evento a evento.
Resolvido com eager loading.

### Cache

Nenhum. A modelagem, os índices e a agregação resolveram o problema; acrescentar
cache serviria apenas para esconder query ruim. Redis continua disponível se
algum dia houver benefício claro.

---

## LGPD e privacidade

Esta seção descreve o **comportamento técnico do sistema**. Não é parecer
jurídico: a adequação legal depende da política de privacidade da plataforma e
da avaliação de quem responde por ela.

### Finalidade

Entender o comportamento de navegação e compra para melhorar a plataforma:
quais produtos são vistos, o que entra no carrinho, onde o checkout é
abandonado, quais lojistas convertem.

### O que é coletado

| Dado | Onde | Observação |
|---|---|---|
| `visitor_uuid` | `ci_visitors` | pseudônimo técnico, gerado pelo sistema |
| `session_uuid` | `ci_sessions` | janela de navegação de 30 minutos |
| `user_id` | `ci_visitors`, `ci_events` | referência a `users`, quando autenticado |
| nome do evento e instante | `ci_events` | um dos sete eventos de negócio |
| entidade do evento | `ci_events` | referência a `Product`, `Order`, `OrderSplit` |
| `properties` | `ci_events` | payload do evento, já sanitizado |
| caminho de entrada, referrer, UTMs | `ci_sessions` | só na abertura da sessão |

### O que **não** é coletado

Endereço IP, completo ou parcial. User-agent. Nome, e-mail, telefone, CPF,
CNPJ, endereço. Query strings — nem da landing, nem do referrer, que é reduzido
a esquema, host e caminho.

Onde há dado pessoal, o módulo **referencia** em vez de copiar: `user_id`
aponta para `users`, e nome e e-mail chegam pela relação. Nada é duplicado.

### Minimização na escrita

`PropertySanitizer` percorre as propriedades antes da gravação e **redige**
valores cujas chaves aparentem carregar dado sensível — senha, token, cartão,
CPF, CNPJ, documento e afins, sem diferenciar maiúsculas, recursivamente até
cinco níveis. Ele redige em vez de lançar exceção: rastreamento não pode
derrubar um fluxo de compra por causa de uma chave mal escolhida, mas também
não deve gravar o dado.

### Pseudonimização, não anonimização

O `visitor_uuid` **não é dado anônimo**. É um identificador persistente, com
cookie de dois anos, que liga todas as visitas de um mesmo navegador. Somado ao
histórico de eventos, constitui **dado pseudonimizado**: sozinho não nomeia
ninguém, mas permite reidentificação se cruzado com outra fonte — por exemplo,
o `user_id` quando o visitante se autentica.

Tratá-lo como anônimo seria incorreto. O tratamento adequado é o de dado
pessoal pseudonimizado.

### Visitante autenticado

Quando o visitante faz login, o `user_id` é gravado **uma única vez**, no
primeiro acesso autenticado, e não é sobrescrito por outro usuário no mesmo
navegador — duas pessoas no mesmo computador permanecem dois visitantes assim
que a segunda receber seu próprio cookie.

### Exclusão

Dois caminhos, ambos cobertos por teste:

**Conta excluída.** As foreign keys são `nullOnDelete`. Ao apagar o `User`,
`ci_visitors.user_id` e `ci_events.user_id` viram `null` automaticamente. Os
eventos permanecem, agora sem apontar para ninguém.

**Pedido de eliminação sem excluir a conta** (LGPD art. 18):

```bash
docker compose exec app php artisan customer-intelligence:forget-user maria@exemplo.com
```

Desvincula visitantes e eventos da conta **e rotaciona o `visitor_uuid`**,
quebrando a ponte entre o cookie que ainda está no navegador e o histórico
gravado. A conta continua existindo. A operação é irreversível e pede
confirmação.

### Por que os eventos não são apagados

Depois da desvinculação os registros não identificam mais a pessoa: são
contagens sob um pseudônimo que ninguém consegue relacionar a ela. Apagá-los
destruiria a base analítica sem ganho de privacidade correspondente.

Os **agregados de `ci_daily_metrics` nunca são apagados** por pedido
individual, e não poderiam ser com precisão: eles não têm granularidade
individual — são somas por dia, sem nenhuma coluna de identidade. Um teste
verifica exatamente isso.

### Sessões

`ci_sessions` guarda apenas origem de tráfego e janelas de tempo, sem nada que
identifique a pessoa além do vínculo com o visitante. Após a rotação do
`visitor_uuid`, elas seguem o mesmo destino do visitante: continuam existindo
sob um pseudônimo que já não corresponde a ninguém.

### Retenção

| Dado | Prazo |
|---|---|
| `ci_events` | **180 dias**, expurgo automático |
| `ci_daily_metrics` | **permanente** |
| `ci_sessions`, `ci_visitors` | **sem rotina automática de expurgo nesta versão** |

`ci_sessions` e `ci_visitors` crescem sem expurgo. É decisão atual, não
esquecimento: apagá-las exige decidir antes o que fazer com cookies de dois anos
ainda vivos no navegador, com sessões órfãs e com o ciclo de vida do visitante —
apagar um visitante cujo cookie continua ativo apenas o recria na visita
seguinte, com identidade nova e histórico perdido. Fica como decisão de produto.

As duas tabelas são pequenas em comparação com `ci_events`: uma linha por
navegador e uma por janela de 30 minutos, contra uma por ação.

O expurgo roda diariamente às 03:20 pelo scheduler. É o agregado que torna a
retenção curta viável: o painel lê dele, então apagar o evento bruto não apaga a
série histórica.

### Cookies

`jmf_ci_visitor_id` (2 anos) e `jmf_ci_session_id` (30 minutos, rolante). O
prefixo é histórico e não implica nenhuma dependência externa. `CI_ENABLED=false`
desliga a coleta por completo: o middleware não grava nada e não emite cookies.

### Pontos que dependem de decisão de produto

O módulo não implementa **banner de consentimento** nem **registro de
auditoria de quem consultou o painel**. O acesso já é restrito pela permissão
`customer_intelligence.visualizar`, mas não há trilha de quem olhou o quê.
Ambos são decisões de produto, não limitações técnicas.

---

---|---|
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

Os valores são idênticos aos que o SDK externo enviava. Isso preserva o
histórico e torna a migração da fase seguinte uma troca mecânica: o `use` no
topo do arquivo muda e a string vira um caso do enum.

`event_category` é derivada do prefixo, para permitir agrupar por família sem
`LIKE` no nome.

---

## Como usar

É assim que as sete chamadas ficaram:

```php
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;

CustomerIntelligence::track(
    EventName::ProdutoVisualizado,
    ['preco' => 89.90],
    $product,
);
```

O terceiro argumento é a **entidade de domínio** do evento — `Product`, `Order`,
`OrderSplit`. É o que permite perguntar "quantas visualizações este produto teve
antes da primeira venda" sem vasculhar JSON. Opcional: passe apenas quando o
model já estiver em mãos, sem provocar consulta extra.

Quem preferir injeção de dependência resolve `CustomerIntelligenceService`
diretamente; a fachada é açúcar para os pontos onde não há construtor, como
closures de rota e componentes Livewire.

`track()` enfileira e devolve o controle na hora — é o que a aplicação deve
usar, para não pagar a gravação dentro da requisição. A sessão vem do
`VisitorContext` sozinha; passá-la explicitamente é opcional.

### Atomicidade e idempotência

O evento e **todos** os seus agregados diários são gravados na mesma transação.
O estado válido é sempre um dos dois:

```
nada gravado          ou    evento + todas as suas métricas
```

Nunca evento sem métrica, nunca métrica parcial. As duas propriedades se
sustentam juntas: é por a agregação estar dentro da transação que "o evento
existe" pode ser lido como "as métricas dele também" — e é isso que autoriza a
retentativa a simplesmente devolver o registro existente.

Se qualquer incremento falhar, a transação inteira volta atrás e o job vai para
retry. Deadlock, timeout e conexão perdida seguem esse caminho: **só a colisão
de `event_uuid`** é sinal de evento já persistido.

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
| `InternalPanelTest` | dashboard, filtros, paginação, detalhe, agregação, comando de reconstrução |
| `InternalPanelPerformanceTest` | volume de 10.000 eventos, contagem de queries, ausência de N+1 |

Dois testes guardam a direção da fiação: navegar por um produto e adicionar ao
carrinho precisa despachar o job do módulo, e o evento precisa cair na fila
`customer-intelligence`. Outro navega por duas páginas carregando os cookies,
como um navegador faria, e confirma que o mesmo navegador continua sendo um
único visitante.

Dois cobrem o risco da coexistência com o SDK: que o middleware do módulo está
na pilha **depois** do middleware do SDK, e que sai **um único** `Set-Cookie` de
cada nome.

Dois cobrem a resiliência do fluxo de compra: com o módulo lançando exceção, o
item ainda entra no carrinho e o pedido ainda é criado.

Todos os testes do painel bloqueiam a rede com `Http::preventStrayRequests()`:
se algum componente voltasse a chamar a plataforma externa, o teste falharia.

Os testes rodam em **SQLite em memória** (`phpunit.xml`). O banco MySQL de
desenvolvimento não é tocado, e a massa sintética do benchmark vive apenas ali.
