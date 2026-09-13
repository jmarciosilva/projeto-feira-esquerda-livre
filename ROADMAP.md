# Roadmap — Feira Esquerda Livre

> **Única fonte de verdade do andamento do projeto.** Se este documento e
> qualquer outro discordarem sobre o estado de uma fase, vale este.

## Índice

1. [Como usar este documento](#1-como-usar-este-documento)
2. [Estado atual](#2-estado-atual)
3. [Legenda](#3-legenda)
4. [Visão macro](#4-visão-macro)
5. [Fundação — CMS, admin e home](#5-fundação--cms-admin-e-home)
6. [Marketplace](#6-marketplace)
7. [Social, feed e marketing](#7-social-feed-e-marketing)
8. [Checkout e pagamentos](#8-checkout-e-pagamentos)
9. [Frete e rastreio](#9-frete-e-rastreio)
10. [AVA](#10-ava)
11. [API mobile e app Flutter](#11-api-mobile-e-app-flutter)
12. [Customer Intelligence](#12-customer-intelligence)
13. [Catalog Domain](#13-catalog-domain)
14. [Catalog Intelligence](#14-catalog-intelligence)
15. [Segurança](#15-segurança)
16. [Infraestrutura e produção](#16-infraestrutura-e-produção)
17. [Dívidas técnicas](#17-dívidas-técnicas)
18. [Próximas fases](#18-próximas-fases)
19. [Histórico macro e evolução da suíte](#19-histórico-macro-e-evolução-da-suíte)

---

## 1. Como usar este documento

Abra este arquivo para saber **onde o projeto está, o que terminou, o que vem a
seguir e o que está bloqueado**. Ele não explica *como* o sistema funciona —
isso está em [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — nem *como
instalar* — isso está em [`README.md`](README.md).

### Governança documental

A partir da **DOC-CONSOLIDATION-01** (2026-09-12) a documentação do projeto tem
três documentos principais:

| Documento | Responde | Atualizar quando |
|---|---|---|
| [`README.md`](README.md) | O que é, como instalar, executar e testar | Muda instalação, operação, serviço, comando ou variável de ambiente |
| **`ROADMAP.md`** (este) | Onde estamos, o que terminou, o que vem, o que bloqueia | **Toda fase ou subfase** — início, conclusão, bloqueio, dívida nova |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Como e por que o sistema foi construído: domínios, invariantes, decisões, Decision Log | Toda decisão arquitetural nova, invariante novo ou decisão superada |

Regras:

1. **Novos documentos Markdown permanentes não são criados por fase.** A CAT-06E
   não gera `CAT_06E_*.md`, a CAT-06F não gera `CAT_06F_*.md`, e assim por
   diante. O padrão de um documento por fase está encerrado.
2. Fases futuras **atualizam este ROADMAP**. Decisões novas **atualizam o
   ARCHITECTURE** — com ID novo, nunca reaproveitado, no Decision Log.
3. Mudanças de instalação ou operação **atualizam o README**.
4. O histórico detalhado de cada fase — diffs, relatórios, controles negativos —
   **vive no Git**. Os documentos por fase anteriores à consolidação continuam
   acessíveis no histórico (último commit que os contém: `6ae5981`).

Markdown que **permanecem fora dos três**, por necessidade técnica:

| Arquivo | Por que existe |
|---|---|
| [`docs/API.md`](docs/API.md) | Contrato público da API `/api/v1`. Citado por `routes/api.php` e pelo app Flutter. Atualizar junto com qualquer mudança de contrato |
| [`docs/CUSTOMER_INTELLIGENCE_INTERNAL.md`](docs/CUSTOMER_INTELLIGENCE_INTERNAL.md) | **Lido em runtime** por `DocsShow` e exibido em `/admin/customer-intelligence/documentacao`. Apagá-lo quebra a tela |
| `docs/CAT_05C_LISTING_CONTEXT_E_SANITIZER.md` · `docs/FIN_SEC_01_INTEGRIDADE_COMERCIAL.md` | **Ponteiros sem conteúdo**, mantidos só porque código os cita (docblocks PHP e `tests/Concurrency/prove.sh`). Remover junto com a atualização desses comentários |
| [`feira_esquerda_livre_app/README.md`](feira_esquerda_livre_app/README.md) | Ponto de entrada do subprojeto Flutter |
| [`tests/Concurrency/README.md`](tests/Concurrency/README.md) | Instruções da prova de concorrência, junto do script. Fica em `tests/`, que a consolidação não altera; o essencial também está no README principal |
| `feira_esquerda_livre_app/ios/.../LaunchImage.imageset/README.md` | Template do Xcode — pertence à ferramenta |

---

## 2. Estado atual

| | |
|---|---|
| **Fase atual** | **CAT-06 — IA externa (opcional)**, em andamento. Subfases 06A–06G concluídas e publicadas; **CAT-06H em andamento** — 06H-A publicada nos ciclos técnico (`ca51910`) e documental (`d59a500`); 06H-B publicada nos ciclos técnico (`9838b0d`) e documental (`82a82ce`); 06H-C tecnicamente concluída e publicada (`f9d72df`), reconciliação documental em andamento; 06H-D → 06H-H pendentes |
| **Última fase concluída** | **CAT-06G** — fallback ligado e desfecho: **F-1** fechado, **B-5** decidido. Publicada em `5a667b4` |
| **Última fase com commit** | **CAT-06H-C** — commit técnico `f9d72df` |
| **Próxima implementação** | **CAT-06H-D** — ausência de persistência (H-05), só após a publicação e a verificação remota da reconciliação documental da CAT-06H-C: `GenerateListingSuggestion` segue como mecanismo de sugestão sem escrita — mesmo com sugestão externa válida aproveitada, nada é persistido em produto, oferta, conhecimento, pivots ou entidade relacionada |
| **Último commit técnico publicado** | `f9d72df` (`f9d72df98c2f9c5b19ca4123632f9643e4961529`) — `test: garante zero retry na CAT-06H-C` |
| **Último commit documental publicado** | `82a82ce` (`82a82ce1bf0160955708778cf90bf674b6c453d3`) — `docs: reconcilia publicação técnica da CAT-06H-B` |
| **Última suíte completa** | **1289 passed · 5603 assertions · 0 failures** · 839,90s (2026-09-13, sobre o código publicado em `5a667b4`, medida antes do commit; container `app`, SQLite em memória). Desde então nenhum arquivo de produção mudou |
| **Validação da CAT-06H-C** | `DesfechoDoAssistenteTest` sobre o blob exato commitado em `f9d72df` (`82a82ce` + H-04, sem H-05): **33 passed · 176 assertions · 0 failures**. Resultados auxiliares sobre o working tree exploratório não são baseline. Suíte completa não executada nesta subfase — evidência e motivo em [§14](#cat-06h--validação-hardening-e-encerramento-da-cat-06-) |
| **Documentação** | DOC-CONSOLIDATION-01 concluída — três documentos principais, commit `962eb5a` |

> **Nota de reconciliação sobre a CAT-06D.** O roadmap anterior da trilha ainda a
> mostrava "em andamento", porque o commit de promoção que as outras subfases
> tiveram (`docs: promove … a concluída`) não chegou a ser feito. A consolidação
> a registrou como concluída com base em evidência: os três commits da subfase
> estão em `origin/main` e a suíte completa passou sobre `6ae5981`, que os
> contém. **A CAT-06D foi formalmente confirmada como CONCLUÍDA pelo operador
> durante a revisão da DOC-CONSOLIDATION-01, em 2026-09-12.**

### Bloqueadores

| # | Bloqueia | Situação |
|---|---|---|
| **B-3 · B-6** | **Provider externo real entrar em operação** — não bloqueia as subfases | Abertos, sem fase. Os três gates estão fechados: **C-2** na **06E** (`FreeTextRedactor`), **S-1** na **06F** (`PromptGuard`) e **F-1** na **06G** (desfecho e fallback, `5a667b4`) |
| **G-1** | Multi-oferta, backfill de conhecimento em produção (P-1) e revisão de conceitos sem uso (D-4) | Aberto. Não existe superfície de curadoria — **CAT-08** |
| **F-06** | Produção endurecida do webhook Mercado Pago | Aberto, mitigado por desenho (security debt) |
| **SEC-DEP-01** | `league/commonmark` 2.9.0 com 4 advisories HIGH | Aberto — atualizar para `>=2.10.0` em fase própria |
| **GOV-02** | Coleta de eventos que nascem fora do navegador do comprador | Pendência de produto, não implementada |

Nenhum bloqueador funcional acima afeta a **CAT-06H-D**. Ela é a próxima implementação
planejada, mas só pode ser iniciada após a publicação e a verificação remota da
reconciliação documental da CAT-06H-C.

---

## 3. Legenda

| Marca | Significa |
|---|---|
| `[x]` | concluído |
| `[~]` | em andamento |
| `[ ]` | pendente |
| `[!]` | bloqueado |
| `[-]` | cancelado ou substituído |

---

## 4. Visão macro

| Trilha | Estado | Seção |
|---|---|---|
| Fase 1 — CMS, admin e home | `[x]` | [§5](#5-fundação--cms-admin-e-home) |
| Fase 2 — Lojistas e agenda | `[x]` | [§6](#6-marketplace) |
| Fase 3 — Catálogo e três eixos | `[x]` com 3 pendências | [§6](#6-marketplace) |
| Fase 4 — Checkout, frete, pagamento e rastreio | `[x]` MVP avançado; pós-MVP pendente | [§8](#8-checkout-e-pagamentos) · [§9](#9-frete-e-rastreio) |
| Fase 5 — Comunidade, marketing e visibilidade | `[x]` | [§7](#7-social-feed-e-marketing) |
| Fase 6 — Governança administrativa | `[x]` | [§5](#5-fundação--cms-admin-e-home) |
| Fase 7 — Comunicação loja-cliente | `[x]` | [§6](#6-marketplace) |
| Fase 8 — AVA | `[x]` | [§10](#10-ava) |
| Fase 9 — API mobile v1 | `[x]` | [§11](#11-api-mobile-e-app-flutter) |
| App Flutter | `[~]` fases 1–2 e dois módulos extras | [§11](#11-api-mobile-e-app-flutter) |
| Fase 10 — Inteligência de Cliente (SDK externo) | `[-]` substituída pela Trilha CI | [§12](#12-customer-intelligence) |
| Trilha CI — Customer Intelligence interno (CI-01…CI-09) | `[x]` | [§12](#12-customer-intelligence) |
| GOV-01 — Consentimento e auditoria | `[x]` | [§12](#12-customer-intelligence) |
| GOV-02 — Consentimento em eventos assíncronos | `[ ]` decisão de produto | [§12](#12-customer-intelligence) |
| Infraestrutura — ambiente Docker | `[x]` | [§16](#16-infraestrutura-e-produção) |
| SEC-01 · SEC-02 · SEC-03 | `[x]` | [§15](#15-segurança) |
| FIN-SEC-01 — integridade comercial | `[x]` | [§8](#8-checkout-e-pagamentos) |
| CAT-DOM-01 · CAT-DOM-02 (02A→02I) | `[x]` | [§13](#13-catalog-domain) |
| CAT-01 → CAT-05 | `[x]` | [§14](#14-catalog-intelligence) |
| **CAT-06 — IA externa (opcional)** | **`[~]` 06A–06G concluídas e publicadas · 06H em andamento (06H-A e 06H-B publicadas; 06H-C tecnicamente publicada em `f9d72df`) · próxima 06H-D, após a publicação e verificação remota da reconciliação documental da 06H-C** | [§14](#14-catalog-intelligence) |
| CAT-07 → CAT-11 | `[ ]` | [§14](#14-catalog-intelligence) |
| FIN-DOM-01 — domínio financeiro (repasse, ledger) | `[ ]` não iniciada | [§18](#18-próximas-fases) |

---

## 5. Fundação — CMS, admin e home

### Fase 1 — CMS, admin e home `[x]`

- `[x]` CMS com painel administrativo em Livewire: banners, eventos, posts, páginas, menus, mídia e configurações do site
- `[x]` Models `Banner`, `Event`, `Post`, `Menu`, `Page`, `SiteSetting`, `Expositor`, `Product`, `NewsletterSubscriber`
- `[x]` Home pública com oito seções alimentadas pelo banco, identidade visual `#F4E294`
- `[x]` Carrossel de banners responsivo, newsletter (`POST /newsletter`)
- `[x]` Páginas institucionais, contato com resposta automática, Política de Privacidade e Termos de Uso

### Fase 6 — Governança administrativa, usuários internos e permissões `[x]`

Concluída em julho de 2026.

- `[x]` `spatie/laravel-permission`, papéis `administrador`, `gerente`, `supervisor`, `editor`, `lojista`, `cliente`, permissões versionadas em seeder idempotente
- `[x]` `/admin/usuarios`, `/admin/perfis-acesso`, `/admin/clientes`
- `[x]` `customer_profiles` separando status no marketplace do status global do usuário; modelo multi-papel (um e-mail acumula papel interno e perfil de cliente)
- `[x]` Menu renderizado por permissão; rotas com `can:*`; ações Livewire protegidas no backend; testes por perfil, URL direta e ação bloqueada
- `[ ]` Auditoria detalhada de alterações administrativas e de permissões — pós-MVP (a trilha de auditoria existente cobre só o Customer Intelligence)

---

## 6. Marketplace

### Fase 2 — Ecossistema do lojista e agenda de feiras `[x]`

- `[x]` `/seja-um-expositor` com validação de CPF/CNPJ e eixos declarados; `lojista_solicitacoes` (`pendente`/`aprovado`/`bloqueado`)
- `[x]` Aprovação administrativa cria `User` (`lojista`) e `Expositor`
- `[x]` Middleware `lojista`, área `/minha-loja`, perfil da loja (logo, banner, redes, slug, cidade/UF, PIX, banco)
- `[x]` Agenda pública `/agenda` com filtros e `/agenda/{slug}` com expositores confirmados; pivot `event_expositores`

### Fase 3 — Catálogo, três eixos, loja pública e carrinho `[x]`

- `[x]` Eixos `produto` · `servico` · `cuidado` (`ItemType`), `PriceType`, `Modality`, categorias por eixo, `eixos` em expositores e solicitações
- `[x]` `/produtos`, `/servicos`, `/cuidados`; `/loja/{slug}` e `/loja/{slug}/{produto}`
- `[x]` CRUD do lojista com compressão de imagem (`intervention/image`, WebP)
- `[x]` Carrinho multilojas (`CartDrawer` + `CartService`) com agrupamento por loja
- `[ ]` Ordenação drag-and-drop dos itens do lojista — não entregue; `sort_order` é campo numérico
- `[ ]` Persistência de 7 dias do carrinho de visitante por cookie assinado — a validar
- `[ ]` Limite de 50 itens por lojista — sem enforcement

> Pendências da Fase 3 registradas no roadmap original e **não reverificadas**
> nesta consolidação.

> **Evolução posterior do catálogo.** Desde a CAT-DOM-01 o item de catálogo
> (`Product`) e a condição de venda (`ProductOffer`) são entidades separadas, e a
> CAT-DOM-02 completou a separação. Estado em [§13](#13-catalog-domain); regras em
> `docs/ARCHITECTURE.md`.

### Fase 7 — Comunicação entre loja e cliente `[x]`

Concluída em julho de 2026.

- `[x]` FAQ por item, editável pelo lojista — hoje **FAQ da oferta** (`product_offer_faqs`), desde a CAT-DOM-02E
- `[x]` Q&A público (`ProductQandA`) e painel de perguntas do lojista (`PerguntaIndex`) — a pergunta carrega a oferta destinatária desde a CAT-DOM-02E, e só o dono da oferta responde desde a CAT-DOM-02F
- `[x]` Chat pós-pedido por split (`OrderChat`, polling de 5s), badges de não lidas

---

## 7. Social, feed e marketing

### Fase 5 — Comunidade, marketing digital e visibilidade `[x]`

| Módulo | Estado |
|---|---|
| 5.1 Feed da comunidade — publicações do lojista, curtidas, comentários, denúncias e moderação (`/admin/feed/reportes`) | `[x]` |
| 5.2 Central de compartilhamento — Open Graph, imagem gerada por item (`compartilhar.png`, `imagem-compartilhamento`) | `[x]` |
| 5.3 Email marketing — campanhas, agendamento, fila `email-marketing`, rastreio de abertura/clique, descadastro LGPD | `[x]` |
| 5.4 Visibilidade de expositores — slots de destaque, rotação ponderada, impressões assíncronas, relatório `/minha-loja/exposicao` | `[x]` |

Pós-MVP pendente:

- `[ ]` Recuperação de carrinho abandonado
- `[ ]` Integração SendGrid ou Amazon SES para listas grandes
- `[ ]` Ativação automática de slot de destaque por pagamento
- `[ ]` Publicar no feed pelo app (hoje só pelo site)

---

## 8. Checkout e pagamentos

### Fase 4 — Checkout, pedidos e Mercado Pago `[x]` MVP avançado

- `[x]` `orders`, `order_items`, `order_splits` (um split por loja), comissão configurável
- `[x]` Checkout em `/checkout` — visitante vê autenticação inline; o pedido exige usuário autenticado
- `[x]` Mercado Pago configurável pelo painel (`/admin/settings/checkout`), webhook `/pagamentos/mercado-pago/webhook`, retorno `/pagamentos/mercado-pago/retorno/{reference}`
- `[x]` Painéis de pedido do admin e do lojista
- `[ ]` Split automático via API Marketplace do Mercado Pago — pós-MVP (FIN-DOM-01)
- `[ ]` Login simplificado por link mágico ou Google; ViaCEP; PIX com QR Code próprio — adiados

> A decisão de 16/06/2026 de um MVP 100% manual (PIX direto ao lojista,
> confirmação manual) foi **superada** pela integração Mercado Pago e pela
> FIN-SEC-01G.1, que restringiu a confirmação manual a pedido já pago.

### FIN-SEC-01 — Integridade comercial e preservação histórica `[x]`

Trilha nascida de achados preexistentes da revisão pré-commit da CAT-DOM-01.
Princípio: **o relacionamento com o vendedor é temporal; o pedido é histórico.**

| Subfase | Estado | Entrega | Commit |
|---|---|---|---|
| 01A | `[x]` | Auditoria, matriz de riscos F-01…F-13, reprodução dos cenários | — |
| 01B | `[x]` | FKs comerciais `CASCADE` → `SET NULL`, snapshot `expositor_name`; SEC-03 corrigida | `c67a450` |
| 01C | `[x]` | `order_splits.shipping_amount`; frete escolhido por cotação, nunca por preço do navegador | `214fbe1` |
| 01C.1 | `[x]` | F-13 fechado: frete da API recotado no servidor (`shipping_options`) | `3a73ef1` |
| 01D | `[x]` | `ConfirmOrderPayment` atômica, idempotente, igualdade monetária em centavos; F-03 e F-05 fechados | `04112b6` |
| 01E | `[x]` | Reserva de estoque no checkout, consumo no pagamento, lock no banco; F-02 fechado | `db210a3` |
| 01F-A…C.2 | `[x]` | Roteamento de eventos, cancelamento, expiração de pagamento (`orders:expire-payments`) | `6b4a3a0` · `a839266` |
| 01F-D / D.1 | `[x]` | Reversão financeira (`Estornado`/`Revertido`), `payment_conflicts`, `cancelled` pós-pagamento vira conflito | `6f355b0` |
| 01G / G.1 | `[x]` | Hardening, `tests/Concurrency/prove.sh`, repasse só sobre pedido pago | `4d73786` |

Dívidas remanescentes em [§17](#17-dívidas-técnicas). Nenhuma permite perda de
dinheiro, duplo consumo, ressurreição de estado terminal ou fabricação de
estoque; por isso não existe uma 01H.

---

## 9. Frete e rastreio

| Item | Estado |
|---|---|
| Cotação Melhor Envio por loja, com CEP de origem do expositor e dimensões da oferta | `[x]` |
| Frenet como provedor alternativo (`frete_provedor`) | `[x]` |
| Conexão OAuth da conta da plataforma com o Melhor Envio (`/admin/melhor-envio/conectar`) | `[x]` |
| Cotação compartilhada web/API (`CartShippingQuoter`), preço decidido no servidor, fail closed | `[x]` (FIN-SEC-01C/C.1) |
| Frete por loja congelado no pedido (`order_splits.shipping_amount`) | `[x]` |
| Rastreio: `order_shippings`, `order_tracking_events`, `TrackShipmentsJob` 3×/dia, página pública `/rastreio/{codigo}`, notificação por e-mail | `[x]` |
| OAuth **por lojista** no Melhor Envio | `[ ]` pós-MVP |
| Compra e geração de etiquetas | `[ ]` pós-MVP |
| Split de frete e conciliação por loja | `[ ]` FIN-DOM-01 |
| Recotação da API em paralelo (hoje sequencial, uma chamada por loja) | `[ ]` dívida de latência |

---

## 10. AVA

### Fase 8 — Ambiente Virtual de Aprendizagem `[x]`

Concluída em julho de 2026.

- `[x]` `is_digital` no item; `ava_courses` (1:1 com `Product`), módulos, aulas, materiais, matrículas, progresso
- `[x]` Matrícula por evento `OrderSplitConfirmed` → `HandleAvaEnrollmentOnSplitConfirmed` → `AvaEnrollmentService`
- `[x]` Course builder do lojista, player do aluno, materiais por URL assinada (15 min), certificado PDF (dompdf) idempotente
- `[x]` Curso demonstrativo "Curso Online de Informática Popular" com matrícula demo

Evolução posterior, já concluída:

- `[x]` Matrícula automática no pagamento online (FIN-SEC-01D, F-03)
- `[x]` Acesso revogado (`Refunded`) na reversão financeira, preservando progresso e certificado (FIN-SEC-01F-D)
- `[x]` Curso é canônico do `Product`; autoridade sobre ele é a canônica; matrícula resolve a oferta de origem pela compra (CAT-DOM-02G)

Pendente:

- `[ ]` **F-07** — `DELETE Product` apaga curso, matrículas e progresso (alcançável só por SQL manual)
- `[ ]` Certificado continua baixável por matrícula revogada — decisão de produto
- `[ ]` Construtor de curso pela API

---

## 11. API mobile e app Flutter

### Fase 9 — API mobile v1 `[x]`

Concluída em agosto de 2026. Contrato completo em [`docs/API.md`](docs/API.md).

- `[x]` Sanctum em modo token pessoal (Bearer); `routes/api.php` com prefixo `/api/v1` (62 rotas)
- `[x]` Autenticação, catálogo público, perguntas, carrinho (exige login), frete, checkout, pedidos, chat, endereços
- `[x]` AVA — Meu Aprendizado, concluir aula, certificado
- `[x]` Lojista — painel, loja, CRUD de itens, pedidos recebidos, perguntas, exposição, cursos (listar/publicar)
- `[x]` Comunidade — listar, curtir, comentar, denunciar
- `[x]` Evoluções de contrato compatíveis: `shipping_options` no checkout (FIN-SEC-01C.1), `product_offer_id` opcional em perguntas (CAT-DOM-02E) e carrinho (CAT-DOM-02G)
- `[ ]` Construtor de curso AVA, publicar no feed e recuperação de senha pela API

### App Flutter (`feira_esquerda_livre_app/`)

Consome a API `/api/v1`. Ciclo de build próprio, fora do build web. Último commit
no app: `808dd8b` (2026-08-04).

| Fase do app | Estado |
|---|---|
| 1 — Setup e autenticação (Riverpod, dio, go_router, secure storage) | `[x]` |
| 2 — Catálogo e loja pública sem login | `[x]` |
| Módulo extra — Comunidade (feed); falta a UI de denúncia | `[x]` |
| Módulo extra — Home e navegação principal (carrosséis, bottom nav de 4 abas, Nunito) | `[x]` |
| 3 — Carrinho e checkout | `[ ]` **próxima recomendada no app** |
| 4 — Pedidos, rastreio e chat | `[~]` só rastreio público |
| 5 — AVA — Meu Aprendizado | `[ ]` |
| 6 — Painel do lojista | `[~]` placeholder |
| 7 — Qualidade, testes e publicação nas lojas | `[ ]` |

Fora do escopo do app por ora: construtor de curso, recuperação de senha, push,
modo offline.

---

## 12. Customer Intelligence

> **Módulo de telemetria e comportamento** — visitas, sessões, carrinho, pedido,
> agregações, LGPD e retenção. **Não se confunde com a Catalog Intelligence**
> ([§14](#14-catalog-intelligence)), que é assistência ao cadastro do catálogo.
> Os roadmaps das duas trilhas são independentes.

### Fase 10 — Inteligência de Cliente via SDK externo `[-]` substituída

Agosto de 2026 (Sprint 3). Integrou o SDK `jmf-system/customer-intelligence-sdk`
por HTTP, com dashboard, rastreamento dos sete eventos e testes E2E (236 testes).
**Substituída integralmente pela Trilha CI**, que manteve eventos, painel,
permissão e views e trocou a origem dos dados para o próprio banco. O SDK foi
removido na CI-08.

### Trilha CI — Customer Intelligence interno `[x]`

**Não recriar como trabalho futuro.** Concluída em 25/08/2026.

| Fase | Escopo | Estado | Commit |
|---|---|---|---|
| CI-01 | Auditoria e arquitetura (decisões 1–6) | `[x]` | — |
| CI-02 | Fundação: `ci_visitors`, `ci_sessions`, `ci_events`, `ci_daily_metrics`, Models, `record()` | `[x]` | `822fda8` |
| CI-03 | Coleta de visitante e sessão (middleware `TrackVisitorSession`, ServiceProvider) | `[x]` | `4064b67` |
| CI-04 | Escrita de eventos pela fila `customer-intelligence` | `[x]` | `c527df1` |
| CI-05 | Migração das 7 chamadas de rastreamento (corte direto, fachada) | `[x]` | `25fb8d5` |
| CI-06 | Painel e agregação diária locais | `[x]` | `c15c406` |
| CI-07 | SDK externo desligado em runtime | `[x]` | `49059fd` |
| CI-08 | Remoção física do SDK | `[x]` | `5cbf6d0` |
| CI-09 | Idempotência, atomicidade, retenção de 180 dias, LGPD, limpeza | `[x]` | `da661d3` |

### GOV-01 — Consentimento opt-in e auditoria `[x]`

Commit `bc23032` (26/08/2026). Opt-in com três estados (`unknown`/`accepted`/
`rejected`), decisão centralizada no `TrackingPolicy`, cookie
`fel_privacy_consent` de 12 meses, banner e `/privacidade/preferencias`,
`ci_audit_logs` append-only com permissão própria `customer_intelligence.auditoria`,
retenção de 730 dias com expurgo agendado separado.

### GOV-02 — Consentimento em eventos assíncronos `[ ]`

Registrada, **não implementada**, decisão de produto. Dois dos sete eventos não
nascem no navegador de quem comprou: `pedido.pagamento_confirmado` (webhook,
nunca coletado) e `pedido.enviado` (depende da preferência do lojista). Direção
provável: persistir a decisão no `Visitor`. As conversões do painel não são
afetadas.

### Pendências do módulo

- `[ ]` Expurgo de `ci_sessions` e `ci_visitors` — decisão de produto
- `[ ]` Eventos de carrinho sem `product_offer_id` (M-13) — performance por vendedor inderivável

---

## 13. Catalog Domain

Separa **identidade de catálogo** (`Product`) de **relação comercial**
(`ProductOffer`). Regras completas em `docs/ARCHITECTURE.md`, seção
*Product × ProductOffer*.

### CAT-DOM-01 — Produto mestre × oferta do expositor `[x]`

Commit `a0c36f3` (27/08/2026). 01A→01H concluídas. `product_offers` criada,
backfill 1:1 de 75 ofertas com zero divergência, `SaveProductWithOffer` como
porta única de escrita, visibilidade por `ProductOffer::scopeVigente()`.
Decisões humanas H-1 (item de expositor inativo sai das vitrines) e H-2 (colunas
comerciais ficam em espelho — **superada** pela 02C/02H). Suíte 577 → 594.

### CAT-DOM-02 — Autoridade, curadoria e conteúdo `[x]` ENCERRADA

| Subfase | Estado | Entrega | Commit |
|---|---|---|---|
| 02 (auditoria) | `[x]` | Inventário, 17 bloqueadores de multi-oferta | — |
| 02A | `[x]` | Home lendo a oferta, FAQ preservada por omissão na API, painéis contando ofertas, autor do curso no AVA | `1c5a6bd` |
| 02B | `[x]` | Decisões D-CAT-09 a D-CAT-21 e gates G-1…G-11 (sem código) | `c7cc1d0` |
| 02C | `[x]` | Delegação canônica explícita, `ProductPolicy`, `products.is_active` sob curadoria, fim do write-through | `05a30d3` |
| 02D | `[x]` | `product_offers.images`, `product_offer_faqs`, `product_questions.product_offer_id`, backfill por command | `9d29f48` · `3532bd1` |
| 02E | `[x]` | Writers e readers migrados, fallback de imagem centralizado, D-02E-1 | `d0825a3` |
| 02F | `[x]` | Ownership comercial por `ProductOffer::pertenceAoExpositorDe()`, resposta só pelo dono da oferta | `bc03f33` |
| 02G | `[x]` | `ResolveProductOffer` + `Contexto`, oferta de origem da matrícula, slug desambiguado | `861c193` |
| 02H | `[x]` | Doze espelhos comerciais removidos; `products` 29 → 17 colunas | `e67ebf9` |
| 02I | `[x]` | Hardening final, `CatalogoHardeningFinalTest`, gates I-1…I-13 | `e7ae4da` |

Reconciliação pós-02H: `8c84517`. Correção do `DemoProductSeeder` que ainda
gravava espelhos: `6ae5981`.

> O roadmap geral anterior marcava 02D→02I como "aguardando revisão pré-commit".
> A consolidação as registra como concluídas: os commits existem, a CAT-05A
> (2026-09-01) auditou o estado pós-02I, e a trilha seguiu sobre ele.

**Estado dos gates de multi-oferta** (02B §13):

| Gate | Estado |
|---|---|
| G-1 autoridade explícita **e superfície de curadoria** | `[!]` mecanismo `[x]` (02C); superfície `[ ]` — CAT-08 |
| G-2 criação de oferta sobre item existente só por curadoria | `[ ]` não existe caminho nenhum (estrutural) |
| G-3 caminho de proposta para o lojista sem delegação | `[ ]` |
| G-4 imagem e FAQ isoladas por oferta | `[x]` 02D/02E/02F |
| G-5 pergunta com destinatário, só ele responde | `[x]` 02E/02F |
| G-6 `products.is_active` só curadoria | `[x]` 02C |
| G-7 SEC-02 estendida com teste A × B | `[x]` 02F |
| G-8 nenhuma autorização por `products.expositor_id` | `[x]` 02F/02I |
| G-9 seleção de oferta explícita | `[x]` 02G para compra/histórico/resposta; apresentação `[ ]` |
| G-10 autoria do curso | `[x]` 02G |
| G-11 colisão de slug | `[x]` 02G |

**Multi-oferta está preparada e não habilitada.**

---

## 14. Catalog Intelligence

> **Assistência inteligente ao cadastro e à classificação do catálogo** —
> conhecimento, similaridade, sugestões, `SuggestionPolicy`, providers, redação,
> `PromptGuard`, fallback e feedback. **Não se confunde com o Customer
> Intelligence** ([§12](#12-customer-intelligence)).

Três regras invioláveis: a inteligência **não inventa fatos objetivos**; **nada é
salvo sem aprovação humana**; **falha da inteligência não bloqueia o cadastro**.

### Fases

| Fase | Estado | Entrega | Commit |
|---|---|---|---|
| CAT-01 | `[x]` | Auditoria e arquitetura | `67f545c` |
| CAT-02 | `[x]` | `short_description` no domínio, formulário, API e factories | `ef3fbcd` |
| CAT-03 | `[x]` | Base de conhecimento `catalog_*`, governança de proveniência, 28 conceitos | `2e369c1` |
| CAT-04 | `[x]` | Motor de similaridade determinístico e explicável | `3cab7e2` |
| CAT-05 | `[x]` ENCERRADA | Assistente de conteúdo interno, A→H | ver abaixo |
| **CAT-06** | **`[~]`** | **IA externa opcional — contrato, redator, guard, fallback. Nenhum texto sai da aplicação ao fim dela** | ver abaixo |
| CAT-07 | `[ ]` | Feedback humano e memória (sugerido → aplicado → final → desfecho) | — |
| CAT-08 | `[ ]` | Interface administrativa da inteligência — **fecha G-1** | — |
| CAT-09 | `[ ]` | Integração no cadastro do lojista (pré-visualização, aplicação seletiva) | — |
| CAT-10 | `[ ]` | Observabilidade, custos e segurança com provider acoplado | — |
| CAT-11 | `[ ]` | Hardening, testes e revisão final | — |

### CAT-05 — Assistente de conteúdo `[x]`

| Subfase | Estado | Entrega | Commit |
|---|---|---|---|
| 05A | `[x]` | Auditoria de reconciliação, blockers B-1…B-6 | `a086d7e` |
| 05B | `[x]` | D-CAT-05B-1…5; similaridade só oferece item vigente (M-17) | `a086d7e` · `5681be5` |
| 05C | `[x]` | `ListingContext` + `ContextSanitizer` | `f193b07` |
| 05D | `[x]` | `GenerateListingSuggestion`, `ListingSuggestion`, sem provider | `630d343` |
| 05E | `[x]` | `ListingGap`, keywords por termo comercial e sinônimo (P-4) | `858c9cc` |
| 05F | `[x]` | Degradação parcial, guarda de log, fronteira do cadastro | `c73cf03` |
| 05G | `[x]` | Teto de 6 consultas, fronteira de prompt (S-1, S-2) | `6cbe5f0` |
| 05H | `[x]` | Validação sobre 75 itens reais, backfill rodado e revertido em dev | `93ccbf1` · `0e96b66` |

Suíte 1048 → 1139 ao longo da fase.

### CAT-06 — IA externa (opcional) `[~]`

| Subfase | Estado | Entrega | Commit |
|---|---|---|---|
| 06A | `[x]` | Auditoria de reconciliação — documento reconstruído após perda de sessão (§10 e §11 não recuperadas) | `9e5685f` |
| 06B | `[x]` | D-CAT-06B-1…6: desfecho em DTO de 4 estados (F-1), `FreeTextRedactor` na fronteira de saída (C-2) | `9e5685f` · `960ece7` |
| 06C | `[x]` | `SuggestionPolicy` + `config/catalog-intelligence.php`; veredito `KnowledgeSufficiency` | `d7a8ba2` · `494018c` |
| 06D | `[x]` | `Contracts/CatalogAiProvider`, `NullCatalogAiProvider`, `FakeCatalogAiProvider`, `ProviderResponseValidator` (B-4) | `9e105bd` · `be8833d` |
| 06E | `[x]` | `Support/FreeTextRedactor` — **fecha C-2**. 29 testes: positivo e negativo por faixa, idempotência, falha fechada; controle negativo por mutação | `101748a` |
| 06F | `[x]` | `Support/PromptGuard`, `DTOs/GuardedPrompt`, `Enums/ProviderInstruction`; `FronteiraDePromptTest` reescrito — **fecha S-1**. 21 testes (11 de estrutura, 10 de fronteira); controle negativo por mutação | `f7b39c2` |
| 06G | `[x]` | Fallback ligado: `suggest(GuardedPrompt)`, `Support/GuardedPromptRedactor`, `Exceptions/CatalogAiProviderException`, desfecho `ListingOutcome` de 8 estados, composição conservadora da resposta externa (nome equivalente não é contribuição), `Null` como binding padrão; `minimum_gaps` = 3 revalidado nos 75 itens reais — **fecha F-1**, **decide B-5** (8 s, 0 novas tentativas). 46 testes novos; 12 controles negativos por mutação | `5a667b4` |
| 06H | `[~]` | Validação, hardening e encerramento da CAT-06 — em andamento, subdividida em 06H-A → 06H-H; 06H-A e 06H-B publicadas, 06H-C tecnicamente publicada ([ver abaixo](#cat-06h--validação-hardening-e-encerramento-da-cat-06-)) | `ca51910` (06H-A) · `9838b0d` (06H-B) · `f9d72df` (06H-C) |

A ordem 06E/06F antes de 06G é deliberada (D-CAT-06B-6): redator e guard existem
antes de a saída ser ligada. **Nenhum fornecedor real é integrado, nenhuma
credencial criada, nenhum segredo versionado.**

Escopo exato da **06E** (D-CAT-06B-2): classe própria em `Support/`, aplicada só
na fronteira de saída para o provider — nunca dentro do `ContextSanitizer`.
Telefone, e-mail, CPF/CNPJ e CEP **sempre** redigidos; medidas, preço e
quantidade **nunca**; URL e `@handle` **não por padrão**. Cada faixa com teste
positivo e negativo.

Entregue: `redigir(string): string`, marcador `[redigido]`, sem dependência, sem
log e sem exceção; texto ilegível falha fechado. Sem máscara, CPF/CNPJ só com
dígito verificador válido e CEP só depois da palavra "CEP"; fixo sem máscara e
fixo sem DDD não são reconhecidos (limitação declarada). Desde a CAT-06G, o
`FreeTextRedactor` é aplicado na fronteira de saída por meio do
`GuardedPromptRedactor`, antes da chamada ao provider.

Escopo da **06F** (D-CAT-06F-1…5): instrução, contexto recuperado e dado do
lojista em três propriedades de `GuardedPrompt`, nunca juntas em texto. A
instrução é um enum puro fixado pelo guard; `knowledge` e `similar_items` são
contexto; todo o resto é dado. O guard classifica por origem e não lê conteúdo —
não há lista de frases. Desde a 06G é chamado pelo assistente.

Escopo da **06G** (D-CAT-06G-1…12): o provider recebe `GuardedPrompt`, e o texto
livre dos canais `context` e `data` é redigido depois do guard. A falha esperada
do provider é uma exceção tipada; qualquer outra sobe. O desfecho é exaustivo, com
8 estados; falha do motor interno tem estado próprio e não consulta o provider. A
resposta externa válida complementa a interna, sem substituí-la; provider consultado
sem nada aproveitado — nome equivalente ao atual incluído — tem desfecho próprio. B-5: 8 s no
adaptador futuro, sem nova tentativa e sem chave de config até existir adaptador.
**Nenhum provider real, credencial ou chamada de rede.**

### CAT-06H — Validação, hardening e encerramento da CAT-06 `[~]`

Em andamento. Uma **auditoria exploratória** levantou os achados **H-01 a H-14**.
Esses identificadores são da auditoria, **não são nomes de fase**: os achados foram
agrupados nas subfases **06H-A → 06H-H** abaixo, para permitir rastreabilidade e
publicação incremental. A 06H-A e a 06H-B foram publicadas nos ciclos técnico e
documental; a 06H-C tem entrega técnica publicada, com a reconciliação documental em
andamento; as demais seguem pendentes, e nenhum resultado local da auditoria vale como
baseline oficial antes de ser publicado pela subfase correspondente.

| Subfase | Estado | Origem | Objetivo | Commit |
|---|---|---|---|---|
| 06H-A — Matriz de desfechos e composição conservadora | `[x]` | H-01 · H-07 | Cobertura dos 8 estados de `ListingOutcomeState`, inclusive contribuição externa só por descrição; composição conservadora; exaustividade independente da ordem de declaração do enum | `ca51910` |
| 06H-B — Fronteira de exceções do provider | `[x]` | H-02 · H-03 | Só `CatalogAiProviderException` é falha operacional esperada do provider; `RuntimeException`, `TypeError`, `Error` e demais defeitos não tipados não são mascarados como fallback normal, nem em `isAvailable()` nem em `suggest()` | `9838b0d` |
| 06H-C — Zero retry | `[~]` | H-04 | Uma única chamada ao provider nos caminhos atualmente exercitáveis — resposta inválida, falha e resposta válida sem contribuição. Preserva a decisão B-5 de 0 retry também para o timeout do adaptador futuro (8 s, 0 novas tentativas), **sem implementar nem testar timeout real**: nada de `sleep`, rede, cliente HTTP ou configuração de timeout nesta fase | `f9d72df` |
| 06H-D — Ausência de persistência | `[ ]` | H-05 | `GenerateListingSuggestion` segue como mecanismo de sugestão sem escrita: o caminho externo, mesmo com sugestão válida aproveitada, não persiste alteração em produto, oferta ou entidade relacionada | — |
| 06H-E — Isolamento do caminho externo | `[ ]` | H-06 · H-13 · H-14 | A fronteira externa não passa a depender de `ProductOffer`, `Expositor`, cadastro, request nem Customer Intelligence. As dependências de oferta de `FindSimilarProducts` e `ContextSanitizer` pertencem a caminhos internos já decididos (D-CAT-05B-2, D-CAT-05C-7) e não violam a fronteira externa | — |
| 06H-F — Reconciliação de nomenclatura | `[ ]` | H-08 · H-10 | `GenerateListingSuggestion` adotado formalmente como nome técnico canônico; sem criar `ListingAssistant`, alias, facade ou wrapper para reproduzir nomenclatura histórica; testes e referências técnicas legadas reconciliados | — |
| 06H-G — Reconciliação documental e dívidas | `[ ]` | H-09 · H-11 · H-12 | Reconciliar documentação e registrar, sem correção oportunista durante o hardening: docblocks obsoletos (H-09); onde residirá o texto de `ProviderInstruction` quando existir adaptador real (H-11); o comportamento histórico do `Throwable` do motor interno em `GenerateListingSuggestion::completar()` (H-12) | — |
| 06H-H — Auditoria final e encerramento da CAT-06 | `[ ]` | — | Validação consolidada depois das subfases publicadas: 8 estados, fronteira `GuardedPrompt`, C-2, S-1, F-1, fronteira de exceções, zero retry, ausência de persistência, isolamento externo, binding `Null`, nomenclatura reconciliada, dívidas registradas, testes dirigidos, Catalog Intelligence, suíte completa, `git diff --check`, estado remoto e ausência de regressões | — |

**06H-A — publicada nos dois ciclos:** entrega técnica em `ca51910` (`test: endurece
matriz de desfechos da CAT-06H-A`) e reconciliação documental em `d59a500` (`docs:
reconcilia publicação técnica da CAT-06H-A`), ambos verificados no remoto. A condição
registrada para o `[x]` — publicação e verificação remota do commit documental — foi
cumprida.

- Só teste: `tests/Feature/CatalogIntelligence/DesfechoDoAssistenteTest.php`. Nenhum
  arquivo de produção mudou — o comportamento protegido já existia, e a subfase
  acrescenta evidência contra regressão.
- **H-01** — `test_contribuicao_so_de_descricao_e_uso_externo`: resposta externa válida
  em que a descrição é a única contribuição aproveitada, com desfecho
  `ExternalSuggestionUsed`; o resumo já escrito pela lojista é preservado (composição
  conservadora), e `source` só vira `External` porque houve contribuição efetiva.
  Controle negativo **N-H01** (a descrição externa deixa de ser aproveitada): o teste
  falhou; a mutação foi restaurada antes do commit.
- **H-07** — a matriz dos 8 estados de `ListingOutcomeState` (`InternalKnowledgeSufficient`,
  `InternalKnowledgeInsufficient`, `InternalIntelligenceFailed`, `ProviderUnavailable`,
  `ProviderFailed`, `ProviderResponseInvalid`, `ExternalSuggestionUsed`,
  `ExternalSuggestionNotUsed`) passou a ser comparada com `assertEqualsCanonicalizing`.
  **N-H07a** (conjunto de estados incorreto): o teste falhou. **N-H07b** (ordem de dois
  estados invertida): o teste continuou passando. A cobertura detecta mudança no
  conjunto e não depende da ordem de declaração do enum.
- Validação: `DesfechoDoAssistenteTest` sobre o blob staged exato, depois commitado —
  **31 passed · 172 assertions · 0 failures** (16,31s); o mesmo arquivo no working tree,
  que ainda continha alterações exploratórias das subfases seguintes — 34 passed · 181
  assertions · 0 failures (18,45s); Catalog Intelligence no mesmo working tree — **324
  passed · 2627 assertions · 0 failures** (115,96s). A suíte completa não foi executada
  nesta subfase: nenhuma produção mudou, não houve rename, o domínio passou inteiro e o
  blob exato do commit foi validado.

**06H-B — publicada nos dois ciclos:** entrega técnica em `9838b0d` (`test: endurece
fronteira de exceções da CAT-06H-B`, parent `d59a500`) e reconciliação documental em
`82a82ce` (`docs: reconcilia publicação técnica da CAT-06H-B`), ambos verificados no
remoto. A condição registrada para o `[x]` — publicação e verificação remota do commit
documental — foi cumprida.

- Só teste: `tests/Feature/CatalogIntelligence/DesfechoDoAssistenteTest.php` (44 linhas
  acrescentadas, nenhuma removida). Nenhum arquivo de produção mudou — a fronteira
  estreita já existia desde a CAT-06G, e a subfase acrescenta evidência contra regressão.
- **Contrato:** `CatalogAiProviderException` é a falha operacional esperada da fronteira
  do provider, e é a categoria que `GenerateListingSuggestion` trata explicitamente nas
  chamadas a `isAvailable()` e a `suggest()`. Defeitos inesperados que não são
  `CatalogAiProviderException` permanecem visíveis ao chamador. A cobertura publicada é
  pontual, não uma matriz completa de categoria × chamada: em `suggest()`,
  `CatalogAiProviderException`, `RuntimeException`, `TypeError` e, desde esta subfase,
  `Error`; em `isAvailable()`, `CatalogAiProviderException` e, desde esta subfase,
  `RuntimeException` genérica.
- **H-02** — fronteira de exceção em `suggest()`.
  `test_error_do_provider_nao_e_mascarado_como_falha_do_provider`: um `Error` lançado por
  `CatalogAiProvider::suggest()` propaga ao chamador com a própria classe. A execução não
  termina em desfecho algum — nem `ProviderFailed`, `ProviderUnavailable`,
  `ProviderResponseInvalid` ou `ExternalSuggestionNotUsed`, nem outro fallback operacional
  normal.
- **H-03** — fronteira de exceção em `isAvailable()`.
  `test_excecao_generica_ao_perguntar_disponibilidade_nao_e_engolida`: uma
  `RuntimeException` genérica lançada por `isAvailable()` propaga ao chamador com a
  própria classe; não vira `ProviderUnavailable` nem `ProviderFailed`, e `suggest()` não é
  chamado (`sugestoes === 0`).
- Controle negativo **N-H02**: o `catch (CatalogAiProviderException)` da chamada a
  `suggest()` foi ampliado temporariamente para `catch (Throwable)`, passando a mascarar
  defeitos inesperados como `ProviderFailed`. O teste H-02 falhou, e as demais coberturas
  da fronteira de `suggest()` também detectaram a regressão (`RuntimeException`,
  `TypeError` e o registro da falha sem a mensagem). Produção restaurada byte a byte.
- Controle negativo **N-H03**: o tratamento da chamada a `isAvailable()` foi ampliado
  temporariamente para `Throwable`, convertendo o defeito inesperado em fallback
  operacional (`ProviderUnavailable`). O teste H-03 falhou, e a cobertura da falha
  esperada ao perguntar disponibilidade também detectou a regressão. Produção restaurada
  byte a byte. Nenhuma das duas mutações entrou no commit.
- Validação formal: `DesfechoDoAssistenteTest` sobre o blob exato commitado em `9838b0d`
  — `d59a500` + H-02 + H-03, sem H-04/H-05 — **33 passed · 175 assertions · 0 failures**
  (19,95s). Auxiliares, sobre o working tree que ainda contém alterações exploratórias das
  subfases seguintes e por isso não são baseline: `DesfechoDoAssistenteTest` 34 passed ·
  181 assertions · 0 failures (20,04s); Catalog Intelligence 324 passed · 2627 assertions
  · 0 failures (137,96s). A suíte completa não foi executada nesta subfase: só um teste
  mudou, nenhuma produção, não houve rename, e o blob exato do commit foi validado; a
  última suíte completa oficial continua a de §2.

**06H-C — entrega técnica publicada em `f9d72df`** (`test: garante zero retry na
CAT-06H-C`, parent `82a82ce`). O `[~]` registra que a entrega técnica da 06H-C foi
concluída, publicada e verificada no remoto, enquanto a reconciliação documental da
subfase permanece em andamento. A 06H-C só passa a `[x]` e pode ser considerada
CLOSED/FROZEN depois da publicação e verificação remota do commit documental
correspondente.

- Só teste: `tests/Feature/CatalogIntelligence/DesfechoDoAssistenteTest.php` (uma linha
  alterada e uma acrescentada). Nenhum arquivo de produção mudou — o assistente já
  tentava uma vez só (D-CAT-06G-6), e a subfase acrescenta evidência contra regressão.
- **H-04** — a resposta inválida do provider não tinha asserção explícita de zero retry.
  `test_resposta_invalida_e_recusada_com_os_motivos_e_nada_dela_entra` já verificava o
  desfecho `ProviderResponseInvalid`, os motivos e que nada da resposta entra; passou a
  guardar a referência do fake e a afirmar **1 chamada** ao provider
  (`assertSame(1, $fake->chamadas())`). Garantia protegida: `ProviderResponseInvalid` não
  gera nova tentativa automática.
- **Situações da B-5:** falha esperada do provider (`ProviderFailed`, inclusive prazo
  esgotado) e resposta válida sem contribuição (`ExternalSuggestionNotUsed`) já contavam
  uma chamada antes desta subfase; a resposta inválida era a única sem contagem. A
  subfase fecha essa lacuna específica — não é prova exaustiva de todos os caminhos
  possíveis do provider.
- **B-5 preservada, sem implementação nova:** a decisão continua 8 s de timeout no
  adaptador futuro e 0 novas tentativas. A 06H-C não implementou timeout real, cliente
  HTTP, adaptador, mecanismo ou política de retry, nem configuração, e não usa `sleep`;
  só endureceu por teste o comportamento existente.
- Controle negativo **N-H04**: uma segunda chamada artificial a `provider->suggest()` foi
  inserida temporariamente no caminho de `ProviderResponseInvalid`. Sobre o arquivo de
  teste de `82a82ce`, sem H-04: **33 passed** — a nova tentativa indevida passava
  despercebida. Sobre o blob com H-04: **1 failed / 32 passed** — a nova asserção
  detectou a regressão. Produção restaurada byte a byte; a mutação não entrou no commit.
- Validação formal: `DesfechoDoAssistenteTest` sobre o blob exato commitado em `f9d72df`
  — `82a82ce` + H-04, sem H-05 — **33 passed · 176 assertions · 0 failures** (16,31s).
  Auxiliares, sobre o working tree que ainda contém alterações exploratórias de H-05,
  H-06 e H-08/H-10 e por isso não são baseline: `DesfechoDoAssistenteTest` 34 passed ·
  181 assertions · 0 failures (20,86s); Catalog Intelligence 324 passed · 2627 assertions
  · 0 failures (116,51s). A suíte completa não foi executada nesta subfase: nenhuma
  produção mudou, nenhum rename nem arquivo novo entrou no commit, e o blob exato, o teste
  dirigido e o Catalog Intelligence passaram; a última suíte completa oficial continua a
  de §2.

**H-09 tem duas naturezas de alteração, e elas vão em ciclos diferentes da 06H-G:**

- docblocks e comentários PHP em `app/` pertencem ao ciclo **técnico** da 06H-G —
  commit técnico, com revisão, push manual e verificação remota próprios;
- ROADMAP, ARCHITECTURE e o registro das decisões e dívidas pertencem ao ciclo
  **documental** posterior da 06H-G.

Nenhum arquivo PHP entra num commit declarado exclusivamente documental.

A CAT-06 só pode ser declarada **PUBLISHED / CLOSED / FROZEN** depois da 06H-H e
das publicações técnica e documental correspondentes.

Cada subfase técnica aplicável segue o [protocolo de fase](#protocolo-de-fase):

```text
implementação/auditoria técnica
→ revisão pré-commit → commit técnico → revisão pós-commit
→ push manual pelo operador humano → verificação remota
→ atualização documental → revisão documental → commit documental
→ push manual → verificação remota
```

**O agente de código nunca realiza push.**

### Dívidas da trilha

Tabela única em [§17](#17-dívidas-técnicas) — C-1, C-2, F-1, S-1, S-2, P-1, B-4,
G-1, E-1, D-1…D-4 (CAT-05H), B-3, B-5, B-6 (CAT-06A).

---

## 15. Segurança

| Trilha | Estado | Entrega | Commit |
|---|---|---|---|
| SEC-01 — credencial legada | `[x]` | Credencial da plataforma externa revogada na origem; histórico do Git **não** reescrito (string inerte); `.gitignore` cobre `*.backup` e `*.bak` | `7572731` |
| SEC-02 — isolamento do catálogo por expositor | `[x]` | IDOR com transferência de propriedade no `ProdutoForm` corrigido; guard escopado em `mount`, `save`, `removeImage`; `expositor_id` fora do update; 21 testes em `CatalogoIsolamentoTest`. Baseline `67f545c`, 455 testes | `472b3cf` |
| SEC-03 — autorização com identidade nullable | `[x]` | `null === null` concedia acesso ao chat do pedido; identidade válida exigida antes de comparar propriedade | `c67a450` (FIN-SEC-01B) |
| Extensões de isolamento | `[x]` | Conteúdo por oferta (02F), autoridade canônica × ownership comercial (02C/02F/02I) | ver §13 |
| Gate de IA externa C-2 — redação de PII em texto livre | `[x]` | `FreeTextRedactor` na fronteira de saída | CAT-06E · `101748a` |
| Gate de IA externa S-1 — separação de prompt | `[x]` | `PromptGuard`: instrução, contexto e dado em canais estruturais do `GuardedPrompt` | CAT-06F · `f7b39c2` |
| Gate de IA externa F-1 — desfecho e fallback | `[x]` | O provider só recebe `GuardedPrompt` redigido; só a falha tipada vira fallback; `TypeError` e defeito interno sobem; log sem mensagem | CAT-06G · `5a667b4` |

Dívidas de segurança abertas: **SEC-DEP-01** (commonmark), **F-06** (assinatura
do webhook), **LGPD-01** (CPF/CNPJ em claro), **C-1**, **S-2** — ver
[§17](#17-dívidas-técnicas).

---

## 16. Infraestrutura e produção

### Ambiente Docker de desenvolvimento `[x]`

Commit `024ee33` (25/08/2026). Oito serviços (`app`, `nginx`, `mysql`,
`phpmyadmin`, `redis`, `node`, `queue`, `mailpit`), `vendor/` e `node_modules/`
em volume nomeado, instalação com um único `git clone`. Operação em
[`README.md`](README.md); decisões em `docs/ARCHITECTURE.md`.

### Produção

`[ ]` Não versionada neste repositório. **Produção não usa Docker.** Checklist
herdado da FIN-SEC-01G, ainda válido:

- `[ ]` `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` definida, segredos fora do Git
- `[ ]` HTTPS no endpoint do webhook; `notification_url` pública; credenciais Mercado Pago de produção
- `[ ]` `php artisan migrate --force` com backup antes
- `[ ]` **`php artisan schedule:run` no cron — obrigatório** (expiração de pagamento libera estoque)
- `[ ]` Worker de filas ativo — recomendado; não é autoridade financeira
- `[ ]` MySQL 8 com `STRICT_TRANS_TABLES`
- `[ ]` `config:cache`, `route:cache`, `view:cache` após o deploy
- `[ ]` Resolver **SEC-DEP-01** antes de publicar

---

## 17. Dívidas técnicas

IDs preservados das fases de origem. Quando o mesmo código foi usado por fases
diferentes, a origem vai entre parênteses — `D-1 (CAT-DOM-01)` e `D-1 (CAT-05H)`
são dívidas distintas.

### Segurança e LGPD

| ID | Dívida | Severidade | Destino |
|---|---|---|---|
| **SEC-DEP-01** | `league/commonmark` 2.9.0 com 4 advisories HIGH (DoS em extensões, XSS por `on*` com form feed). Correção em `>=2.10.0`. Reconfirmado por `composer audit` em 2026-09-12. Usado por `DocsShow` e pelo Laravel. **Nova — ID atribuído nesta consolidação** | Alta | Fase própria de dependências |
| **F-06** (FIN-SEC) | Webhook Mercado Pago sem verificação de assinatura; mitigado porque o corpo não é fonte de verdade (`getPayment()` autenticado) | Média | Hardening próprio ou troca de gateway |
| **LGPD-01** | CPF/CNPJ gravado **sem criptografia** em `lojista_solicitacoes`. O princípio "CPF/CNPJ armazenado sempre encriptado" do roadmap original nunca foi implementado. **Nova — ID atribuído nesta consolidação** | Média | Decisão de produto + migration |
| **LGPD-02** | "Logs de acesso ao painel admin retidos por 90 dias" e "`cart_items` anônimos expiram em 7 dias", do roadmap original, **não existem** no código. **Nova — ID atribuído nesta consolidação** | Baixa | Decisão de produto |
| **C-1** (CAT-05C) | `knownAttributes` protegido por lista de proibição; quem o popular deve mapear campo a campo | Média | CAT-09 |
| **C-2** (CAT-05C) | Texto livre não é redigido antes de sair para provider | Gate — **`[x]` fechado** pelo `FreeTextRedactor` | **CAT-06E** · `101748a` |
| **S-1** (CAT-05G) | Teste de prompt injection real, com `PromptGuard` | Gate — **`[x]` fechado** pelo `PromptGuard` | **CAT-06F** · `f7b39c2` |
| **S-2** (CAT-05G) | A sugestão ecoa texto do lojista: renderizar sempre escapado | Média | CAT-09 |
| SEC-01 | Credencial revogada permanece como string inerte no histórico do Git | Baixa | Só com tarefa coordenada de reescrita de histórico |

### Catálogo e multi-oferta

| ID | Dívida | Severidade | Destino |
|---|---|---|---|
| **G-1** (CAT-DOM-02B) | Sem superfície de curadoria; `ProductPolicy` decide e quase nada a invoca; FAQ canônica não tem como nascer | Bloqueia multi-oferta, P-1, D-4 | CAT-08 |
| G-3 (CAT-DOM-02B) | Sem workflow de proposta para lojista sem delegação — hoje ele só é recusado | Média | Fase de contribuição |
| — (CAT-DOM-02I) | Vinculação de oferta a `Product` existente sem superfície | Mantém multi-oferta fechada | Fase de contribuição |
| — (CAT-DOM-02G) | Apresentação sob multi-oferta: vitrine, home, catálogo e `ProductResource` usam `ofertaVigente` | Baixa hoje | Decisão de produto na ativação |
| M-12 (CAT-DOM-02B) | SEO canônico sob multi-oferta (`rel=canonical`, sitemap) | Baixa hoje | Ativação de multi-oferta |
| M-13 (CAT-DOM-02B) | Eventos do Customer Intelligence sem `product_offer_id` | Baixa | Fase de CI |
| M-14 (CAT-DOM-02B) | Fallback de expositor no `ProductResource` — não reverificado | Baixa | A reverificar |
| M-05 (CAT-DOM-02B) | Imagem canônica sem guard próprio; nenhum writer runtime a alcança desde a 02E | Baixa | Superfície de curadoria |
| R-4 (CAT-DOM-02D) | Arquivos órfãos em disco; `ImageService` apaga por caminho sem contar referências | Baixa | Operação de limpeza explícita |
| — (Fase 3) | Drag-and-drop de ordenação, carrinho de visitante 7 dias, limite de 50 itens | Baixa | Backlog |
| #6 (CAT-01) | `product_faqs` vazio — sem corpus de FAQ | Baixa | — |

Resolvidas e registradas para não serem reabertas: D-1 (CAT-DOM-01, espelho
comercial — 02C/02H), D-2 (CAT-DOM-01, conteúdo autoral no mestre — 02C a 02F),
D-3 (CAT-DOM-01, regra de cadastro duplicada — `SaveProductWithOffer`), M-01,
M-02, M-03, M-04, M-06, M-08, M-09 (decisões de compra), M-10/F-09 (slug — 02G),
M-16, M-17 (05B), itens 1, 3, 5, 7–12 da tabela de riscos da CAT-01.

### Catalog Intelligence

| ID | Dívida | Destino |
|---|---|---|
| **F-1** (CAT-05F) | Sem sinal de modo degradado | **`[x]` fechado** na **CAT-06G** — desfecho `ListingOutcome`, 8 estados (D-CAT-06G-3, D-CAT-06G-11) |
| **B-5** (CAT-06A) | Timeout de chamada a provider | **`[x]` decidido** na **CAT-06G** — 8 s aplicados pelo adaptador, 0 novas tentativas, sem config até haver leitor (D-CAT-06G-5, D-CAT-06G-6) |
| **B-3** (CAT-06A) | `EmbeddingProvider` órfão: na especificação, nenhuma fase o reivindica; trava de teste o mantém inexistente | Em aberto, sem decisão |
| **B-6** (CAT-06A) | Custo e rate limit | Em aberto, sem fase |
| **P-1** (CAT-05B) | Backfill de `catalog_product_knowledge` em produção (em dev foi rodado e revertido na 05H) | Decisão humana após G-1 |
| **B-4** (CAT-05A) | Corpus de seeder: `short_description` vazia em 75/75, "demonstração" em 34/75 | Depende de catálogo real |
| **E-1** (CAT-05E) | `KnowledgeTermType::Keyword` sem uso | Decidir quando houver registro |
| **D-1** (CAT-05H) | Caminho da descrição sem cobertura real (75/75 já têm descrição) | CAT-09 |
| **D-2** (CAT-05H) | `palavrasChave()` não pondera por score | CAT-07 (alternativa CAT-11) |
| **D-3** (CAT-05H) | Casamento por frase exata não alcança termo intercalado | CAT-11 — reabre a CAT-04 |
| **D-4** (CAT-05H) | 8 de 28 conceitos sem evidência direta (inclusive `Crochê`) | CAT-08 |
| — (CAT-06A/06B) | Nomenclatura `ListingAssistant` × `GenerateListingSuggestion` (o DTO de desfecho foi batizado na 06G: `ListingOutcome`) | CAT-06H-F |
| — (CAT-06D §10) | Auditorias devem varrer asserções (`assertFalse(class_exists`), não só arquivos | CAT-06H-H |
| — (CAT-05G) | `ListingContext::deProduct()` custa 1 consulta por ancestral sem `with('category.parent')` — observação, não dívida | CAT-09 |

### Financeiro, pedidos e AVA

| ID | Dívida | Classificação | Destino |
|---|---|---|---|
| **F-07** (FIN-SEC) | `DELETE Product` apaga curso, matrículas e progresso (só por SQL manual) | Product debt, HIGH | Fase própria do AVA |
| F-08 · F-11 (FIN-SEC) | Sem entidade de pagamento/recebível; acoplamento ao Mercado Pago | Arquitetura | FIN-DOM-01 |
| F-12 (FIN-SEC) | Nenhum model usa SoftDeletes | Documentada | Fase própria |
| — (01F-D) | Refund parcial real, ciclo do chargeback, repasse, retenção, saldo | Domínio financeiro | FIN-DOM-01 |
| — (01F-D) | Certificado baixável após matrícula revogada | Product debt | Decisão de produto |
| — (01G.1) | Botão manual de confirmação de repasse (restrito a pedido pago) | Product debt | Decisão de produto |
| — (01F-D) | Superfície administrativa de `payment_conflicts` | Product debt | Quando houver volume |
| — (01D) | Pedidos digitais pagos enquanto o F-03 existia podem estar sem matrícula | Operacional | Reconciliação própria com `--dry-run` |
| — (01C.1) | Recotação de frete da API sequencial por loja | Latência | Otimização sem perder fail closed |
| — (01D) | Sem histórico de todas as notificações de pagamento (identidade só por `payment_id`) | Financeiro | FIN-DOM-01 |

### Customer Intelligence

| ID | Dívida | Destino |
|---|---|---|
| GOV-02 | Consentimento de eventos que nascem fora do navegador do comprador | Decisão de produto |
| — (CI-09) | `ci_sessions` e `ci_visitors` sem expurgo automático | Decisão de produto |

### Qualidade e documentação

| ID | Dívida | Destino |
|---|---|---|
| — | Violações de Pint preexistentes em ~184 arquivos (ex.: `AvaEnrollment.php`, `CursoBuilder.php`, `ProductQandA.php`, `ProductQuestion.php`, testes de FAQ e Q&A). **Nunca rodar Pint global** | Tarefa própria de estilo |
| DOC-01 | Docblocks de `ContextSanitizer.php`, `ListingContext.php` e o cabeçalho de `tests/Concurrency/prove.sh` apontam para documentos que viraram ponteiros | Atualizar comentários e remover os dois ponteiros |

---

## 18. Próximas fases

Ordem recomendada, sem prejuízo de decisão de produto:

1. **CAT-06H — validação, hardening e encerramento da CAT-06**, em andamento: a 06H-A foi publicada nos ciclos técnico (`ca51910`) e documental (`d59a500`); a 06H-B, nos ciclos técnico (`9838b0d`) e documental (`82a82ce`); a 06H-C foi tecnicamente publicada em `f9d72df`, com a reconciliação documental em andamento; a próxima implementação planejada é a **CAT-06H-D — ausência de persistência** (H-05), que só pode ser iniciada após a publicação e a verificação remota da reconciliação documental da CAT-06H-C. A CAT-06G foi publicada em `5a667b4`.
2. **CAT-08 — interface administrativa**: fecha G-1 e destrava P-1 e D-4. A CAT-05H
   registrou que ela pode ser mais útil que a CAT-07; a ordem entre 07 e 08 é
   decisão de produto.
3. CAT-07 → CAT-09 → CAT-10 → CAT-11.

Fora da Catalog Intelligence, sem ordem definida:

- **SEC-DEP-01** — atualizar `league/commonmark` (antes de produção).
- **FIN-DOM-01** — domínio financeiro: pagamento como entidade, repasse, ledger, refund parcial, chargeback.
- **GOV-02** — decisão de produto sobre consentimento em eventos assíncronos.
- Fase própria do **AVA** — F-07 e certificado após revogação.
- **App Flutter — Fase 3** (carrinho e checkout).
- Pós-MVP de checkout e frete — split automático, OAuth por lojista, etiquetas.
- **DOC-01** — atualizar os três comentários de código e remover os ponteiros.

---

## 19. Histórico macro e evolução da suíte

O detalhe de cada fase está no Git. Marcos de suíte (`passed · assertions`):

| Marco | Suíte |
|---|---|
| Fim da Fase 10 | 236 |
| Docker validado | 238 · 624 |
| Fim da CI-09 | 355 · 1029 |
| Baseline CAT-01 / SEC-02 (`bb932fe`, `67f545c`) | 455 · 1318 |
| Fim da CAT-02 → CAT-04 | 498 → 542 → 577 |
| Fim da CAT-DOM-01 (`a0c36f3`) | 594 · 1626 |
| CAT-DOM-02A → 02C | 867 · 2494 → 889 · 2559 |
| CAT-DOM-02D → 02E | 931 · 2657 → 962 · 2745 |
| Antes da 02G / da 02H / da 02I | 992 · 2825 / 1030 · 2902 / 1037 · 2995 |
| Baseline CAT-05A (`8c84517`) | 1048 · 3117 |
| Fim da CAT-05 (`0e96b66`) | 1139 · 4028 |
| Fim da CAT-06C | 1151 · 4102 |
| Baseline `6ae5981` (código idêntico em `962eb5a`) | 1198 · 4485 |
| Fim da CAT-06E (`101748a`) | 1227 · 4629 |
| Fim da CAT-06F (`f7b39c2`) | 1243 · 5233 |
| **Fim da CAT-06G (`5a667b4`)** | **1289 · 5603** |

Critério permanente: nenhuma fase é concluída com teste vermelho, e o número de
testes nunca cai sem justificativa escrita.

### Protocolo de fase

Vigente a partir da **CAT-06H**: código/testes e documentação são publicados em
ciclos separados. Na CAT-06H, a sequência abaixo vale para cada subfase técnica
aplicável (06H-A → 06H-H).

1. Baseline confirmado (branch, HEAD, `origin/main`, working tree limpo) — parar se divergir.
2. Auditoria antes de implementar — parar em decisão humana quando necessário.
3. Implementação exclusivamente técnica, dentro do escopo.
4. Testes dirigidos + suíte necessária ou completa sobre o código final (`docker compose exec -T app php artisan test`) + controles negativos para teste novo.
5. Revisão de `git status`, `git diff --check`, diff/stat e escopo no relatório.
6. **Parar para revisão pré-commit técnico.**
7. Após autorização, criar somente o commit técnico de código/testes, com `git add` nominal.
8. **Parar para revisão pós-commit técnico.**
9. Após autorização, o operador humano faz o push manual.
10. Confirmar o SHA técnico no repositório remoto.
11. Somente após a confirmação remota, atualizar ROADMAP / ARCHITECTURE / README conforme o caso — nunca um documento novo por fase.
12. **Parar para revisão documental pré-commit.**
13. Após autorização, criar commit exclusivamente documental.
14. **Parar para revisão pós-commit documental.**
15. Após autorização, o operador humano faz o push manual do commit documental.
16. Confirmar o commit documental no repositório remoto.
17. Só então declarar a fase PUBLISHED / CLOSED / FROZEN e iniciar a próxima.

Regras explícitas:

- Commit técnico não antecipa estado documental de concluído.
- Commit documental registra somente fatos já ocorridos e SHAs já publicados.
- Coding AI nunca faz push.
- Código/testes e documentação não entram no mesmo commit a partir da CAT-06H.
