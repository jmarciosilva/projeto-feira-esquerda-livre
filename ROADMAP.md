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
| **Fase atual** | Nenhuma fase em andamento. A **CAT-10A — Primeiro provider real** está concluída e publicada (commit técnico `10fe2bf`). A **homologação real** com o provider ligado está pendente e **não é fase nova**. A homologação visual da **CAT-09** em navegador também segue pendente |
| **Última fase concluída** | **CAT-10A** — primeiro provider real, **OpenAI**, atrás de `CatalogAiProvider`: adaptador fora do módulo, em `app/Services/CatalogAi`, selecionado pelo `CatalogAiProviderSelector`, com o `NullCatalogAiProvider` como fallback; recurso desligado por padrão |
| **Última fase com commit** | **CAT-10A** — commit técnico `10fe2bf` |
| **Próxima implementação** | **Homologação real** do assistente com o provider ligado, em ambiente controlado — não é fase. Depois, **CAT-10B** — observabilidade, custo e segurança ampliados, não iniciada ([§18](#18-próximas-fases)) |
| **Último commit técnico publicado** | `10fe2bf` (`10fe2bfbb88132215d90644703b7c1e86c7f3a82`) — `feat: integra primeiro provider real ao Catalog Intelligence` |
| **Último commit documental publicado** | `a3f5a2d` (`a3f5a2df38ef192dce81ff5d7fa90851c8cf36b2`) — `docs: conclui a CAT-09` |
| **Última suíte completa** | **1411 passed · 8617 assertions · 0 failures** (2026-09-14, na CAT-10A), executada **antes do ajuste textual final da regra 1** da instrução do adaptador; depois do ajuste rodaram os testes dirigidos e o Catalog Intelligence completos. A anterior era 1327 · 6053, sobre `1efe1b6` |
| **Validação da CAT-10A** | Suíte completa **1411 · 8617 · 0** sobre a implementação **antes** do ajuste textual final da regra 1; **depois** do ajuste, dirigidos **85 passed · 2579 assertions** e Catalog Intelligence **442 passed · 5581 assertions**, sem repetir a suíte completa (dispensa explícita na revisão); homologação real **pendente**. Detalhe em [§14](#cat-10a--primeiro-provider-real-x) |
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
| **B-6** | **Ativação ampla de provider externo em produção** — **não bloqueia** a homologação controlada do provider da CAT-10A (decisão do operador, 2026-09-14) | Aberto — **CAT-10B**, onde entram custo, rate limit, observabilidade e a revisão ampliada. Os três gates estão fechados: **C-2** na **06E** (`FreeTextRedactor`), **S-1** na **06F** (`PromptGuard`) e **F-1** na **06G** (desfecho e fallback, `5a667b4`) |
| **B-3** | Só o `EmbeddingProvider` — **não bloqueia** a CAT-10A nem a homologação: o provider de sugestão não usa embeddings | Aberto, sem decisão |
| **G-1** | Multi-oferta, backfill de conhecimento em produção (P-1) e revisão de conceitos sem uso (D-4) | Aberto. Não existe superfície de curadoria — **CAT-08** |
| **F-06** | Produção endurecida do webhook Mercado Pago | Aberto, mitigado por desenho (security debt) |
| **SEC-DEP-01** | `league/commonmark` 2.9.0 com 4 advisories HIGH | Aberto — atualizar para `>=2.10.0` em fase própria |
| **GOV-02** | Coleta de eventos que nascem fora do navegador do comprador | Pendência de produto, não implementada |

A **CAT-09** foi concluída sem provider real. Na **CAT-10A** (`10fe2bf`), o provider real
entrou só por configuração de ambiente, desligado por padrão, para homologação controlada;
sem ele, o `CatalogAiProviderSelector` resolve o contrato para o `NullCatalogAiProvider`. A
B-6 continua impedindo a ativação ampla em produção até a CAT-10B.

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
| CAT-06 — IA externa (opcional) | `[x]` concluída — 06A → 06H, validação final na 06H-H | [§14](#14-catalog-intelligence) |
| CAT-09 — Implantação do Assistente no Catálogo | `[x]` concluída — `1efe1b6`; homologação visual em navegador pendente | [§14](#14-catalog-intelligence) |
| CAT-10A — Primeiro provider real (OpenAI) | `[x]` concluída — `10fe2bf`; homologação real pendente | [§14](#14-catalog-intelligence) |
| CAT-07 · CAT-08 · CAT-10B · CAT-11 | `[ ]` | [§14](#14-catalog-intelligence) |
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
| CAT-06 | `[x]` | IA externa opcional — contrato, redator, guard, fallback. Nenhum texto sai da aplicação ao fim dela | ver abaixo |
| CAT-07 | `[ ]` | Feedback humano e memória (sugerido → aplicado → final → desfecho) | — |
| CAT-08 | `[ ]` | Interface administrativa da inteligência — **fecha G-1** | — |
| CAT-09 | `[x]` | Implantação do Assistente no Catálogo — integração no cadastro e na edição do lojista (pré-visualização, aplicação explícita). Antecipada antes da CAT-07 e da CAT-08 | `1efe1b6` |
| CAT-10A | `[x]` | Primeiro provider real — um único adaptador externo, OpenAI, atrás de `CatalogAiProvider`, com o `Null` como fallback, para homologação real. Antecipada antes da CAT-07 e da CAT-08 | `10fe2bf` |
| CAT-10B | `[ ]` | Observabilidade, custos e segurança ampliados com provider acoplado | — |
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

### CAT-06 — IA externa (opcional) `[x]`

| Subfase | Estado | Entrega | Commit |
|---|---|---|---|
| 06A | `[x]` | Auditoria de reconciliação — documento reconstruído após perda de sessão (§10 e §11 não recuperadas) | `9e5685f` |
| 06B | `[x]` | D-CAT-06B-1…6: desfecho em DTO de 4 estados (F-1), `FreeTextRedactor` na fronteira de saída (C-2) | `9e5685f` · `960ece7` |
| 06C | `[x]` | `SuggestionPolicy` + `config/catalog-intelligence.php`; veredito `KnowledgeSufficiency` | `d7a8ba2` · `494018c` |
| 06D | `[x]` | `Contracts/CatalogAiProvider`, `NullCatalogAiProvider`, `FakeCatalogAiProvider`, `ProviderResponseValidator` (B-4) | `9e105bd` · `be8833d` |
| 06E | `[x]` | `Support/FreeTextRedactor` — **fecha C-2**. 29 testes: positivo e negativo por faixa, idempotência, falha fechada; controle negativo por mutação | `101748a` |
| 06F | `[x]` | `Support/PromptGuard`, `DTOs/GuardedPrompt`, `Enums/ProviderInstruction`; `FronteiraDePromptTest` reescrito — **fecha S-1**. 21 testes (11 de estrutura, 10 de fronteira); controle negativo por mutação | `f7b39c2` |
| 06G | `[x]` | Fallback ligado: `suggest(GuardedPrompt)`, `Support/GuardedPromptRedactor`, `Exceptions/CatalogAiProviderException`, desfecho `ListingOutcome` de 8 estados, composição conservadora da resposta externa (nome equivalente não é contribuição), `Null` como binding padrão; `minimum_gaps` = 3 revalidado nos 75 itens reais — **fecha F-1**, **decide B-5** (8 s, 0 novas tentativas). 46 testes novos; 12 controles negativos por mutação | `5a667b4` |
| 06H | `[x]` | Validação, hardening e encerramento da CAT-06 — subdividida em 06H-A → 06H-H e concluída com a validação final da 06H-H, sem código ([ver abaixo](#cat-06h--validação-hardening-e-encerramento-da-cat-06-x)) | `ca51910` (06H-A) · `9838b0d` (06H-B) · `f9d72df` (06H-C) · `b4662d9` (06H-D) · `54e4963` (06H-E) · `b8d3d56` (06H-F) · `dc217cf` (06H-G) |

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

### CAT-06H — Validação, hardening e encerramento da CAT-06 `[x]`

Concluída. Uma **auditoria exploratória** levantou os achados **H-01 a H-14**.
Esses identificadores são da auditoria, **não são nomes de fase**: os achados foram
agrupados nas subfases **06H-A → 06H-H** abaixo, para permitir rastreabilidade e
publicação incremental. Da 06H-A à 06H-F, as subfases foram publicadas nos ciclos
técnico e documental; a 06H-G (técnica `dc217cf`) e a 06H-H (validação final, sem código)
são registradas no mesmo commit documental que encerra a CAT-06, por decisão do operador
de não abrir microfase só de reconciliação.

| Subfase | Estado | Origem | Objetivo | Commit |
|---|---|---|---|---|
| 06H-A — Matriz de desfechos e composição conservadora | `[x]` | H-01 · H-07 | Cobertura dos 8 estados de `ListingOutcomeState`, inclusive contribuição externa só por descrição; composição conservadora; exaustividade independente da ordem de declaração do enum | `ca51910` |
| 06H-B — Fronteira de exceções do provider | `[x]` | H-02 · H-03 | Só `CatalogAiProviderException` é falha operacional esperada do provider; `RuntimeException`, `TypeError`, `Error` e demais defeitos não tipados não são mascarados como fallback normal, nem em `isAvailable()` nem em `suggest()` | `9838b0d` |
| 06H-C — Zero retry | `[x]` | H-04 | Uma única chamada ao provider nos caminhos atualmente exercitáveis — resposta inválida, falha e resposta válida sem contribuição. Preserva a decisão B-5 de 0 retry também para o timeout do adaptador futuro (8 s, 0 novas tentativas), **sem implementar nem testar timeout real**: nada de `sleep`, rede, cliente HTTP ou configuração de timeout nesta fase | `f9d72df` |
| 06H-D — Ausência de persistência | `[x]` | H-05 | `GenerateListingSuggestion` segue como mecanismo de sugestão sem escrita: o caminho externo, mesmo com sugestão válida aproveitada, não persiste alteração em produto, oferta ou entidade relacionada | `b4662d9` |
| 06H-E — Isolamento do caminho externo | `[x]` | H-06 · H-13 · H-14 | Trava sobre as dependências diretas dos 18 componentes do caminho externo: nenhum deles contém, no próprio código, nenhuma das 13 marcas ligadas a `ProductOffer`, `Expositor`, cadastro, Customer Intelligence e request/HTTP da aplicação — sem fechamento transitivo. Binding `Null` preservado (H-13). As dependências de oferta de `FindSimilarProducts` e `ContextSanitizer` pertencem a caminhos internos já decididos (D-CAT-05B-2, D-CAT-05C-7), ficam fora da trava e não violam a fronteira externa (H-14) | `54e4963` |
| 06H-F — Reconciliação de nomenclatura | `[x]` | H-08 · H-10 | `GenerateListingSuggestion` adotado formalmente como nome técnico canônico; `ListingAssistantTest` renomeado para `GenerateListingSuggestionTest` (R099) e o teste da H-08 renomeado para o contrato real — sem provider externo configurado, com o binding padrão `Null`, a fonte permanece interna; sem criar `ListingAssistant`, alias, facade ou wrapper para reproduzir nomenclatura histórica; testes e referências técnicas legadas reconciliados, com o resíduo textual de `ListingContext.php` reservado à H-09 (06H-G) | `b8d3d56` |
| 06H-G — Reconciliação documental e dívidas | `[x]` | H-09 · H-11 · H-12 | Reconciliar documentação e registrar, sem correção oportunista durante o hardening: docblocks obsoletos (H-09) — resolvida no ciclo técnico, só comentários e docblocks de 8 arquivos, sem token executável alterado; onde residirá o texto de `ProviderInstruction` quando existir adaptador real (H-11) — dívida / decisão futura, reaberta com o primeiro adaptador real; o comportamento histórico do `Throwable` do motor interno em `GenerateListingSuggestion::completar()` (H-12) — preservado nesta fase e registrado como dívida histórica e risco latente de observabilidade/privacidade | `dc217cf` |
| 06H-H — Auditoria final e encerramento da CAT-06 | `[x]` | — | Validação consolidada depois das subfases publicadas: 8 estados, fronteira `GuardedPrompt`, C-2, S-1, F-1, fronteira de exceções, zero retry, ausência de persistência, isolamento externo, binding `Null`, nomenclatura reconciliada, dívidas registradas, testes dirigidos, Catalog Intelligence, suíte completa, `git diff --check`, estado remoto e ausência de regressões — executada sem alteração de código; resultado abaixo | — (validação, sem código) |

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
  última suíte completa oficial era então 1289 · 5603 · 0.

**06H-C — publicada nos dois ciclos:** entrega técnica em `f9d72df` (`test: garante zero
retry na CAT-06H-C`, parent `82a82ce`) e reconciliação documental em `fa43af1` (`docs:
reconcilia publicação técnica da CAT-06H-C`), ambos verificados no remoto. A condição
registrada para o `[x]` — publicação e verificação remota do commit documental — foi
cumprida.

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
  dirigido e o Catalog Intelligence passaram; a última suíte completa oficial era então
  1289 · 5603 · 0.

**06H-D — publicada nos dois ciclos:** entrega técnica em `b4662d9` (`test: garante
ausência de persistência na CAT-06H-D`, parent `fa43af1`) e reconciliação documental em
`046c12c` (`docs: reconcilia publicação técnica da CAT-06H-D`), ambos verificados no
remoto. A condição registrada para o `[x]` — publicação e verificação remota do commit
documental — foi cumprida; a 06H-D está PUBLISHED / CLOSED / FROZEN.

- Só teste: `tests/Feature/CatalogIntelligence/DesfechoDoAssistenteTest.php` (48 linhas
  acrescentadas, nenhuma removida). Nenhum arquivo de produção mudou — o assistente já
  não gravava nada (D-CAT-05B-1), e a subfase acrescenta evidência contra regressão.
  Nenhuma mutação dos controles negativos entrou no commit.
- **H-05** — `GenerateListingSuggestion` é mecanismo de sugestão, não de persistência,
  inclusive no caminho provider disponível → resposta externa válida → contribuição
  externa efetivamente aproveitada → `ExternalSuggestionUsed`.
  `test_uso_externo_nao_grava_nada` exige primeiro esse desfecho e então verifica duas
  camadas: (1) captura, via `DB::listen` / `QueryExecuted`, dos comandos DML de escrita
  `INSERT`, `UPDATE`, `DELETE`, `REPLACE` e `TRUNCATE` emitidos durante a janela
  observada — só a execução do assistente, com fixtures e leituras de referência fora
  dela —, que precisa ficar vazia; (2) comparação do estado persistido antes e depois do
  produto, das ofertas, do conhecimento e do pivot. Leituras não contam como
  persistência. A garantia registrada é que nenhum comando DML de escrita é emitido
  durante a execução observada.
- Controle negativo **N-H05a**: persistência indevida em `Product` inserida
  temporariamente no caminho `ExternalSuggestionUsed`. Com H-05: **1 failed / 33
  passed**, com a captura de `UPDATE products`. Sem H-05, no Catalog Intelligence de
  `fa43af1`: **322 passed** — o baseline anterior não detectava a regressão.
- Controle negativo **N-H05b**: `INSERT` em `cache`, tabela fora das estruturas
  comparadas pela versão exploratória original do teste. Com a H-05 reforçada: **1
  failed / 33 passed**, com a captura de `INSERT INTO cache`. Com a versão exploratória
  original, que só comparava produto, oferta, conhecimento e pivot: **34 passed** — a
  comparação de estado sozinha não bastava para detectar escrita DML fora das estruturas
  comparadas, e por isso a versão final manteve a captura por `DB::listen(QueryExecuted)`.
  Nas duas mutações, produção restaurada byte a byte.
- **B-5 inalterada:** 8 s de timeout no adaptador futuro e 0 novas tentativas. A 06H-D
  não adicionou timeout real, cliente HTTP, provider real nem configuração.
- Validação formal, sobre o conteúdo exato commitado em `b4662d9` (`fa43af1` + H-05):
  `DesfechoDoAssistenteTest` **34 passed · 182 assertions · 0 failures** (22,15s);
  Catalog Intelligence **323 passed · 2568 assertions · 0 failures** (138,38s).
  Auxiliares, não baseline: H-05 com `--filter` 1 passed · 6 assertions;
  `DesfechoDoAssistenteTest` no working tree 34 passed · 182 assertions; Catalog
  Intelligence no working tree exploratório, que inclui H-06 e H-08/H-10, 324 passed ·
  2628 assertions · 0 failures. A suíte completa não foi executada nesta subfase: só um
  teste mudou, nenhuma produção, nenhum rename nem arquivo novo versionado, e o conteúdo
  exato do commit passou no arquivo e no Catalog Intelligence inteiro, com dois controles
  negativos válidos; a última suíte completa oficial era então 1289 · 5603 · 0.

**06H-E — publicada nos dois ciclos:** entrega técnica em `54e4963` (`test: endurece
isolamento externo da CAT-06H-E`, parent `046c12c`) e reconciliação documental em
`06b5f06` (`docs: reconcilia publicação técnica da CAT-06H-E`), ambos verificados no
remoto. A condição registrada para o `[x]` — publicação e verificação remota do commit
documental — foi cumprida; a 06H-E está PUBLISHED / CLOSED / FROZEN.

- Só teste: `tests/Feature/CatalogIntelligence/ContratosDeProviderTest.php` (76 linhas
  acrescentadas, nenhuma removida). Nenhum arquivo de produção mudou — nem bindings,
  provider real, HTTP, credenciais ou comportamento de runtime —, e a subfase acrescenta
  evidência contra regressão. Nenhuma mutação dos controles negativos entrou no commit, e
  as alterações exploratórias de H-08/H-10 (rename do teste e docblock) ficaram fora dele.
- **H-06** — "isolamento do caminho externo", nesta subfase, é uma **trava sobre as
  dependências diretas dos componentes protegidos**: não é fechamento transitivo nem
  garantia sobre o módulo inteiro. `test_o_caminho_externo_nao_depende_de_oferta_expositor_cadastro_nem_request`
  verifica **18 componentes** que participam da decisão, preparação, transporte,
  validação e desfecho da consulta externa — `CatalogAiProvider`,
  `NullCatalogAiProvider`, `FakeCatalogAiProvider` e `CatalogAiProviderException`;
  `GenerateListingSuggestion` e `SuggestionPolicy`; `ListingContext`, `PromptGuard`,
  `GuardedPrompt` e `ProviderInstruction`; `GuardedPromptRedactor` e `FreeTextRedactor`;
  `ListingSuggestion`, `SuggestionSource`, `ProviderResponseValidator` e
  `ProviderResponseViolation`; `ListingOutcome` e `ListingOutcomeState` — contra **13
  marcas** de dependência proibida: oferta e expositor por model e tabela
  (`ProductOffer`, `Expositor`, `product_offers`, `expositores`); cadastro
  (`SaveProductWithOffer`, `App\Actions\Catalog\`); Customer Intelligence, namespace e
  facade (`CustomerIntelligence`); request e HTTP da aplicação (`Illuminate\Http\`,
  `Illuminate\Foundation\Http\`, `Illuminate\Support\Facades\Request`, `Request::`,
  `request(`, `App\Http\`).
- **Como a trava lê o código:** cada arquivo passa por `token_get_all`, e os tokens
  `T_COMMENT` e `T_DOC_COMMENT` são descartados antes da busca — docblocks que citam
  `ProductOffer` ou `SaveProductWithOffer` para dizer que não os usam não viram violação.
  Strings continuam sob inspeção, porque classe e tabela também se alcançam por nome. A
  garantia publicada é que nenhum dos 18 componentes contém, no próprio código, alguma
  das 13 marcas; dependências alcançadas indiretamente, por meio de outras classes, não
  são objeto desta trava.
- **H-13 — auditada e preservada, sem código novo nesta subfase.**
  `test_o_binding_padrao_do_contrato_e_o_null` continua exigindo que `CatalogAiProvider`
  resolva para `NullCatalogAiProvider`, que o `CatalogIntelligenceServiceProvider` tenha
  exatamente um `->bind(` — hoje o único binding do módulo — e que não conheça
  `FakeCatalogAiProvider` nem `->singleton(`. A fragilidade é deliberada e não foi
  flexibilizada: um provider real, um segundo binding ou uma mudança estrutural precisa
  quebrar o teste e exigir revisão arquitetural explícita. **Revista na CAT-10A**
  (`10fe2bf`), como a trava previa — ver [§14](#cat-10a--primeiro-provider-real-x) e §17.
- **H-14 — auditada e preservada.** `FindSimilarProducts` e `ContextSanitizer` ficaram
  deliberadamente fora da lista da H-06: são dependências internas historicamente
  autorizadas e não representam a fronteira externa. A vigência da similaridade em
  `FindSimilarProducts` decorre da D-CAT-05B-2; o `ContextSanitizer` lê as estruturas
  comerciais — a lista de campos da oferta de `SaveProductWithOffer` (D-CAT-05C-7) —
  justamente para removê-las do contexto antes do prompt. Essas dependências não foram
  removidas nem alteradas.
- Controle negativo **N-H06a**: `use App\Models\ProductOffer;` inserido temporariamente
  em `PromptGuard.php`. Com H-06: **1 failed / 18 passed**. Não era lacuna: a lista
  fechada de importações do `PromptGuard` em `FronteiraDePromptTest` já protegia esse
  arquivo.
- Controle negativo **N-H06b**: `use Illuminate\Support\Facades\Request;` inserido
  temporariamente em `GuardedPromptRedactor.php`. Com a H-06 reforçada: **1 failed / 18
  passed**. A versão exploratória original da H-06 não detectava essa mutação, o que
  motivou o reforço das marcas de request; o baseline já tinha a proteção específica da
  06G, a lista fechada de importações do `GuardedPromptRedactor` em
  `GuardedPromptRedactorTest`.
- Controle negativo **N-H06c**: `use App\Models\ProductOffer;` inserido temporariamente
  em `ProviderResponseValidator.php`. Com a H-06 reforçada: **1 failed / 18 passed**. Com
  a versão exploratória original: **19 passed**. Sem H-06, no Catalog Intelligence de
  `046c12c`: **323 passed** — nenhuma trava anterior detectava a dependência. É a lacuna
  real do baseline e a principal evidência de que a H-06 ampliou a proteção, em vez de só
  duplicar travas existentes. As três mutações foram revertidas antes do commit.
- Validação formal, sobre o conteúdo exato commitado em `54e4963` (`046c12c` + H-06):
  `ContratosDeProviderTest` **19 passed · 422 assertions · 0 failures** (7,89s); Catalog
  Intelligence **324 passed · 2820 assertions · 0 failures** (115,77s); `git diff
  --cached --check` limpo. Resultados auxiliares sobre o working tree exploratório, que
  ainda contém H-08/H-10, não são baseline. A suíte completa não foi executada nesta
  subfase: alteração só de teste, nenhuma produção, nenhum rename nem arquivo novo
  versionado no commit, teste dirigido e domínio verdes, três controles negativos
  executados e conteúdo exato staged validado; a última suíte completa oficial era então
  1289 · 5603 · 0.

**06H-F — publicada nos dois ciclos:** entrega técnica em `b8d3d56` (`test: reconcilia
nomenclatura da CAT-06H-F`, parent `06b5f06`) e reconciliação documental em `874ec9d`
(`docs: reconcilia publicação técnica da CAT-06H-F`), ambos verificados no remoto. A
condição registrada para o `[x]` — publicação e verificação remota do commit documental
— foi cumprida; a 06H-F está PUBLISHED / CLOSED / FROZEN.

- Só testes: `b8d3d56` alterou `tests/Feature/CatalogIntelligence/ContratosDeProviderTest.php`
  e renomeou `tests/Feature/CatalogIntelligence/ListingAssistantTest.php` para
  `tests/Feature/CatalogIntelligence/GenerateListingSuggestionTest.php` — 2 arquivos, 4
  inserções e 3 remoções, com o rename reconhecido pelo Git como **R099** (99% de
  similaridade). Nenhuma alteração em `app/`, `config/`, `routes/`, `database/`,
  `README.md` ou `docs/`, e nenhuma mudança funcional.
- **H-08** — o nome `test_a_fonte_e_sempre_interna_nesta_fase` ficou semanticamente
  obsoleto quando a CAT-06G ligou o caminho externo: a fonte passa a `External` quando um
  provider válido contribui. O teste foi renomeado para
  `test_sem_provider_configurado_a_fonte_e_interna`, que declara o contrato real
  protegido: com o binding padrão `Null` e sem provider externo configurado, nenhuma
  contribuição externa ocorre e a fonte permanece `Internal`. Só o nome foi reconciliado,
  e o método ganhou um docblock curto sobre o papel do `Null`; corpo, fixtures, assertions
  e comportamento seguem inalterados. A H-08 não introduziu comportamento novo.
- Controle negativo **N-H08** — temporário, de auditoria, e não um teste permanente: no
  caminho padrão de `GenerateListingSuggestion::compor()`, `SuggestionSource::Internal`
  foi trocado por `SuggestionSource::External`.
  `test_sem_provider_configurado_a_fonte_e_interna` falhou (esperado `Internal`, recebido
  `External`), o que comprova que o teste renomeado continua protegendo comportamento
  real. A produção foi restaurada byte a byte e ficou sem diff; a mutação não integra o
  commit técnico.
- **H-10** — `GenerateListingSuggestion` é a nomenclatura canônica da orquestração.
  `ListingAssistantTest.php` passou a `GenerateListingSuggestionTest.php`, e
  `class ListingAssistantTest` a `class GenerateListingSuggestionTest`. A classe de
  produção `GenerateListingSuggestion` não foi renomeada, e nenhuma classe
  `ListingAssistant`, alias, wrapper ou camada de compatibilidade foi criada.
- **Referência histórica em `ContratosDeProviderTest`:** o cabeçalho citava
  `ListingAssistantTest::test_nenhuma_interface_de_provider_externo_existe`, método já
  removido antes, em `be8833d`. A 06H-F trocou a citação por uma descrição histórica — "a
  antiga trava do teste do assistente contra interface de provider externo" — que não
  aponta para símbolo inexistente. É reconciliação textual: nenhuma mudança funcional e
  nenhum contrato arquitetural novo.
- **Sem trava textual permanente contra `ListingAssistant`**, por decisão consciente de
  escopo: a H-10 é reconciliação nominal, sem comportamento de runtime correspondente, e
  uma trava textual seria frágil e exigiria tratamento especial para referências
  históricas. A auditoria final de nomenclatura fica com a 06H-H.
- **Resíduo conhecido:** depois da entrega técnica, `tests/` tem 0 ocorrências de
  `ListingAssistant`. Em produção permanece uma, em comentário histórico de
  `app/CatalogIntelligence/DTOs/ListingContext.php`, que a 06H-F não alterou — ou seja,
  `ListingAssistant` não foi eliminado integralmente do repositório. A ocorrência pertencia
  à H-09, que ficou para a 06H-G — e foi resolvida no ciclo técnico dela, em `dc217cf` —
  junto com os docblocks já identificados em
  `PromptGuard.php`, `FreeTextRedactor.php`, `GuardedPrompt.php`, `ProviderInstruction.php`,
  `ListingContext.php` e `SuggestionPolicy.php`.
- Validação formal, sobre o conteúdo exato commitado em `b8d3d56` (`06b5f06` + H-08 ·
  H-10): `GenerateListingSuggestionTest` **37 passed · 105 assertions · 0 failures**
  (20,47s); `ContratosDeProviderTest` **19 passed · 422 assertions · 0 failures**
  (10,00s); os dois juntos **56 passed · 527 assertions · 0 failures** (30,32s); Catalog
  Intelligence **324 passed · 2820 assertions · 0 failures** (137,26s). `pint --test` nos
  dois arquivos da subfase: PASS; `git diff --check` e `git diff --cached --check`
  limpos. A suíte completa não foi executada nesta subfase: a 06H-F alterou só
  nomenclatura de teste e comentário/docblock, sem alteração funcional ou de produção, e
  os testes dirigidos e todo o Catalog Intelligence foram executados; a última suíte
  completa oficial era então 1289 · 5603 · 0.

**06H-G — concluída:** entrega técnica em `dc217cf` (`docs: reconcilia comentários
técnicos da CAT-06H-G`, parent `874ec9d`), publicada e verificada no remoto, e
reconciliação documental registrada no mesmo commit documental da 06H-H, que encerra a
CAT-06. O prefixo `docs:` descreve a natureza da mudança — só comentários e docblocks em
PHP —, mas o commit pertence ao ciclo **técnico** da subfase, conforme a separação da H-09
registrada abaixo.

- **H-09 — resolvida no ciclo técnico da 06H-G.** `dc217cf` reconciliou comentários e
  docblocks obsoletos em 8 arquivos de `app/CatalogIntelligence/` — `DTOs/GuardedPrompt.php`,
  `DTOs/ListingContext.php`, `DTOs/ListingSuggestion.php`, `Enums/KnowledgeSufficiency.php`,
  `Enums/ProviderInstruction.php`, `Support/FreeTextRedactor.php`, `Support/PromptGuard.php`
  e `Support/SuggestionPolicy.php` —, com 44 inserções e 31 remoções e nenhum token
  executável alterado. As reconciliações principais: o resíduo `ListingAssistant` saiu da
  documentação inline, e `GenerateListingSuggestion` passou a ser nomeado como o componente
  real; fases do roadmap deixaram de aparecer como atores de runtime; `PromptGuard`
  descreve a ordem real `GenerateListingSuggestion` → `PromptGuard` →
  `GuardedPromptRedactor` → provider; `FreeTextRedactor` documenta seu uso pelo
  `GuardedPromptRedactor`; `KnowledgeSufficiency` e `SuggestionPolicy` nomeiam o componente
  que decide a consulta externa; o contrato documentado de `confidence` em
  `ListingSuggestion` reflete a composição externa já implementada; e `ProviderInstruction`
  registra que não existe adaptador real.
- Evidências da H-09: comparação de tokens sem `T_COMMENT` e `T_DOC_COMMENT` com zero
  diferença executável nos 8 arquivos, entre HEAD e index e, depois do commit, entre
  `874ec9d` e `dc217cf`; `GenerateListingSuggestion.php` inalterado (SHA-256
  `67e117510db6f20950a98e396e401573b4b81f8c2c45227c5b1c8b4fc925a684`); `ListingAssistant`
  com 0 ocorrências em `app/`, `tests/`, `config/`, `routes/` e `database/`; `pint --test`
  nos 8 arquivos: PASS; `git diff --check` e `git diff --cached --check` limpos.
- **H-11 — dívida / decisão futura.** Fatos auditados: não há adaptador real, transporte
  HTTP nem serialização para fornecedor; `ProviderInstruction` é enum estrutural;
  `PromptGuard` fixa `SuggestListing`, `GuardedPrompt` transporta a instrução e
  `GuardedPromptRedactor` a preserva; `FakeCatalogAiProvider` não é adaptador real, e o
  binding padrão é o `NullCatalogAiProvider`; nenhum texto específico de fornecedor
  existe. Decisão: não definir agora se o texto final da instrução pertence ao domínio ou
  ao adaptador — sem o primeiro adaptador real, escolher essa fronteira seria arquitetura
  especulativa. A decisão reabre quando o primeiro adaptador real de `CatalogAiProvider`
  for introduzido. Nada foi implementado. O que será decidido nesse momento está em
  [ARCHITECTURE §24](docs/ARCHITECTURE.md#24-dívidas-técnicas-arquiteturais).
- **H-12 — comportamento histórico deliberadamente preservado nesta fase, dívida histórica
  e risco latente de observabilidade/privacidade.** `GenerateListingSuggestion::completar()`
  captura `Throwable` amplo no motor interno: `RuntimeException`, `TypeError`, `Error`,
  `QueryException` e qualquer outro são convertidos em degradação — no caminho de
  conhecimento, `InternalIntelligenceFailed`; no de semelhantes, degradação acessória sem
  mudar o desfecho principal. Na fronteira do provider é o contrário: só
  `CatalogAiProviderException` é falha operacional esperada, e `RuntimeException`,
  `TypeError` e `Error` genéricos sobem. A assimetria vem do contrato histórico da CAT-05F
  e não é classificada como bug; foi identificada na 06H como dívida que pede decisão
  futura. Risco de observabilidade: um `TypeError` ou `Error` de programação no motor pode
  aparecer como `InternalIntelligenceFailed`, com `convidaARepetir()` verdadeiro — um
  defeito permanente com semântica de falha recuperável. Logging: `QueryException` registra
  só o SQLSTATE, sem SQL nem bindings; os demais `Throwable` registram classe, etapa e
  `getMessage()`, sem stack. A auditoria não achou exposição concreta de PII ou texto livre
  nos pontos de lançamento alcançados hoje pelo assistente, mas uma exceção futura cuja
  mensagem carregue conteúdo do lojista o levaria ao log — risco latente, não
  vulnerabilidade comprovada. Não foram adicionados testes de `TypeError`/`Error` no motor,
  para não cristalizar como contrato desejado um comportamento mantido como dívida; há
  testes para `RuntimeException` e `QueryException` no motor, para as falhas do provider —
  inclusive `RuntimeException`, `TypeError` e `Error` — e para o logging existente. Nada foi
  implementado. O que a fase futura decide está em
  [ARCHITECTURE §24](docs/ARCHITECTURE.md#24-dívidas-técnicas-arquiteturais).
- Validação da entrega técnica: testes dirigidos **173 passed · 2450 assertions · 0
  failures**; Catalog Intelligence **324 passed · 2820 assertions · 0 failures** —
  executados sobre o working tree antes do stage, cujos blobs são idênticos aos commitados
  em `dc217cf`. A suíte completa não foi executada nesta subfase: só comentários e
  docblocks mudaram, sem token executável alterado; a suíte completa da validação final
  está no bloco da 06H-H, abaixo.

**06H-H — validação final da CAT-06, sem código.** Executada sobre o código publicado em
`dc217cf`, sem alterar nenhum arquivo de `app/`, `tests/`, `config/`, `routes/` ou
`database/`. É subfase só de validação: não tem commit técnico, e o registro vai no mesmo
commit documental que conclui a 06H-G e encerra a CAT-06.

| Garantia da CAT-06 | Testes que a protegem (todos verdes na suíte completa) |
|---|---|
| Desfecho exaustivo de 8 estados (F-1) | `DesfechoDoAssistenteTest`: um caso por estado e `test_o_desfecho_e_exaustivo_e_diz_o_que_e_falha_e_o_que_convida_a_repetir` |
| Fronteira `GuardedPrompt` e S-1 | `PromptGuardTest` (11), `FronteiraDePromptTest` (10), `ContratosDeProviderTest::test_o_contrato_nao_aceita_mais_o_contexto_cru` |
| C-2 — redação na saída | `FreeTextRedactorTest` (29), `GuardedPromptRedactorTest` (12), `DesfechoDoAssistenteTest::test_o_provider_recebe_o_prompt_protegido_com_o_texto_livre_redigido` |
| Só `CatalogAiProviderException` é falha do provider | `DesfechoDoAssistenteTest`: `test_runtime_exception_generica_do_provider_nao_e_engolida`, `test_type_error_do_provider_nao_e_mascarado_como_falha_do_provider`, `test_error_do_provider_nao_e_mascarado_como_falha_do_provider`, `test_excecao_generica_ao_perguntar_disponibilidade_nao_e_engolida` |
| Zero retry (B-5) | `test_prazo_esgotado_vira_falha_do_provider_com_uma_tentativa_so`, `test_resposta_invalida_e_recusada_com_os_motivos_e_nada_dela_entra`, `test_resposta_valida_sem_contribuicao_tem_desfecho_proprio` |
| Gerar não grava | `DesfechoDoAssistenteTest::test_uso_externo_nao_grava_nada`, `GenerateListingSuggestionTest::test_gerar_nao_escreve_nada_em_lugar_nenhum`, `ResilienciaDoAssistenteTest::test_a_falha_nao_escreve_nada_no_banco` |
| Resposta externa só complementa | `DesfechoDoAssistenteTest::test_campo_preenchido_pelo_lojista_nao_e_sobrescrito` e vizinhos; `ValidacaoDeRespostaDoProviderTest` (16) |
| Isolamento do caminho externo | `ContratosDeProviderTest::test_o_caminho_externo_nao_depende_de_oferta_expositor_cadastro_nem_request` e as varreduras de cliente HTTP, fornecedor e credencial |
| Binding `Null` | `ContratosDeProviderTest::test_o_binding_padrao_do_contrato_e_o_null`, `GenerateListingSuggestionTest::test_sem_provider_configurado_a_fonte_e_interna` |
| Falha da inteligência não bloqueia o cadastro | `ResilienciaDoAssistenteTest::test_cadastro_conclui_com_o_assistente_quebrado`, `DesfechoDoAssistenteTest::test_provider_quebrado_nao_quebra_o_cadastro_manual` |
| Custo do assistente | `CustoDoAssistenteTest::test_o_assistente_inteiro_cabe_em_seis_consultas` |

- **Nomenclatura:** `ListingAssistant` tem 0 ocorrências no repositório versionado fora de
  `ROADMAP.md` e `docs/ARCHITECTURE.md`, onde aparece só como registro histórico.
- **Varredura de asserções** (dívida da CAT-06D §10): em `tests/`, a única trava de
  inexistência é a do `EmbeddingProvider`
  (`GenerateListingSuggestionTest::test_o_embedding_provider_continua_sem_existir_porque_a_b3_segue_em_aberto`),
  deliberada enquanto a B-3 seguir sem decisão.
- **Dívidas registradas:** H-11 e H-12 em §17 e em ARCHITECTURE §24; B-3 e B-6 seguem
  abertas; C-1, S-2, D-1 e a observação de custo de `deProduct()` seguem com a CAT-09.
- **Suíte completa:** **1294 passed · 5874 assertions · 0 failures** (1073,41s). Em relação
  à anterior (1289 · 5603, em `5a667b4`), os 5 testes a mais são os da 06H-A (1), 06H-B
  (2), 06H-D (1) e 06H-E (1); as demais subfases só mudaram asserções, nomes ou comentários.
  Catalog Intelligence isolado: **324 passed · 2820 assertions · 0 failures** (138,91s).
- **Git:** HEAD e `origin/main` em `dc217cf`, `0 0`, index vazio, `git diff --check`
  limpo; o working tree só tem `ROADMAP.md` e `docs/ARCHITECTURE.md`.
- Nenhuma regressão encontrada, nenhuma correção feita.

**H-09 tem duas naturezas de alteração, e elas vão em ciclos diferentes da 06H-G:**

- docblocks e comentários PHP em `app/` pertencem ao ciclo **técnico** da 06H-G —
  commit técnico, com revisão, push manual e verificação remota próprios;
- ROADMAP, ARCHITECTURE e o registro das decisões e dívidas pertencem ao ciclo
  **documental** posterior da 06H-G.

Nenhum arquivo PHP entra num commit declarado exclusivamente documental.

Com a 06H-H, a CAT-06 está concluída. A publicação é o push manual do commit documental
que a encerra, verificado no remoto pelo operador; não há commit posterior só para
reconciliar estado (decisão do operador, 2026-09-13).

Cada subfase técnica aplicável segue o [protocolo de fase](#protocolo-de-fase):

```text
implementação/auditoria técnica
→ revisão pré-commit → commit técnico → revisão pós-commit
→ push manual pelo operador humano → verificação remota
→ atualização documental → revisão documental → commit documental
→ push manual → verificação remota
```

**O agente de código nunca realiza push.**

### CAT-09 — Implantação do Assistente no Catálogo `[x]`

Concluída e publicada no commit técnico `1efe1b6`; entrega, validação e pendências ao fim
desta seção. Foi antecipada antes da CAT-07 e da CAT-08 por decisão de produto
(2026-09-13): colocar o Catálogo Inteligente utilizável no cadastro real. A numeração
planejada foi mantida — a CAT-09 já era a integração no cadastro do lojista — para não
reescrever referências de código, dívidas e decisões (D-CAT-05B-1: aplicação é CAT-09).
Fase única, sem subdivisão prévia; sub-etapas só se surgir risco técnico concreto.

**Objetivo.** No cadastro e na edição de item do painel do lojista (`ProdutoForm`, rotas
`lojista.produtos.create` e `lojista.produtos.edit`), o expositor pede uma sugestão, vê o
resultado antes de qualquer gravação e decide explicitamente, campo a campo, se aplica.

**Escopo**

- Botão "Gerar sugestão inteligente" no formulário, chamando
  `GenerateListingSuggestion::comContexto()` — sem duplicar composição, política, desfecho
  ou fronteira.
- Contexto montado com `ListingContext::paraItemNovo()` a partir do que está **na tela**,
  inclusive o que ainda não foi salvo; na edição, o `Product` entra como segundo argumento,
  para a similaridade. O caminho de categoria reaproveita a subida que o `ListingContext`
  já faz (privada até então, pública desde a CAT-09), exposta sem reescrita.
- Pré-visualização de nome sugerido, resumo, descrição, palavras-chave e informações
  faltantes, sempre escapados (S-2).
- Desfecho: os 8 estados de `ListingOutcomeState` com mensagem própria; `ehFalha()` separa
  aviso de informação e `convidaARepetir()` decide se a tela oferece gerar de novo. Com o
  `Null`, `ProviderUnavailable` aparece como sugestão feita só com o conhecimento da Feira,
  não como erro.
- Aplicação explícita por campo — nome, resumo e descrição: aplicar só altera o campo **na
  tela**; gravar continua sendo o salvar do formulário, pelo `SaveProductWithOffer`. Campo
  já preenchido nunca é sobrescrito em silêncio; o nome, sempre preenchido, só é trocado por
  ação explícita, com o valor atual visível. Palavras-chave e informações faltantes são
  exibidas, não gravadas — o domínio não tem campo de palavra-chave.
- Autoridade canônica: na edição sem `updateCanonical` (sem delegação nem curadoria), a
  sugestão é exibida e a aplicação de campo canônico fica indisponível, com a explicação. O
  `SaveProductWithOffer` continua recusando com `SemAutoridadeCanonica`; a tela só evita o
  caminho que terminaria em recusa.
- Multi-tenant: a ação nova confere `guardOwnership()` antes de montar o contexto, como
  `save()` e `removeImage()`; ninguém gera sugestão sobre item de outra loja.
- `knownAttributes` fica vazio: o formulário não tem atributo estruturado, e a C-1 não é
  exercida.
- Custo: contexto de item salvo custa uma consulta por ancestral de categoria sem eager
  load (observação da CAT-05G); a CAT-09 carrega `category.parent` ou mede e registra.

**Fora do escopo:** provider real, cliente HTTP, credencial, timeout ou retry — o binding
padrão continua `NullCatalogAiProvider`; gravar a sugestão, histórico de aplicação e
feedback (CAT-07); curadoria (CAT-08); API mobile; mudança em `GenerateListingSuggestion`,
no desfecho ou na fronteira; H-11 e H-12.

**Travas revistas por decisão, não por inércia**

- `ResilienciaDoAssistenteTest::test_o_caminho_de_cadastro_nao_referencia_a_inteligencia`
  proíbe `CatalogIntelligence` em `ProdutoForm`. Passa a admitir a dependência do formulário
  e continua proibindo-a em `SaveProductWithOffer` e no `ProdutoController` da API: o
  salvamento segue sem conhecer a inteligência.
- `test_cadastro_conclui_com_o_assistente_quebrado` ganha a versão pela tela: com o motor
  quebrado, gerar mostra o desfecho de falha e salvar conclui.

**Testes de integração previstos** (Livewire, `ProdutoForm`)

- gerar em item novo e em item existente preenche a pré-visualização com os valores da tela;
- gerar não grava nada — captura de DML de escrita e comparação de estado, como na H-05;
- aplicar só altera o campo na tela, nada muda no banco até salvar, e salvar persiste pelo
  caminho normal;
- campo preenchido não é sobrescrito sem ação explícita;
- sem autoridade canônica, aplicar campo canônico fica indisponível e o salvamento continua
  recusado;
- outro lojista não gera sugestão sobre item alheio (403);
- os desfechos relevantes têm mensagem, e falha do motor não impede salvar;
- o binding padrão continua `Null`, sem chamada externa;
- texto hostil do lojista é exibido escapado;
- o número de consultas do botão fica dentro do teto conhecido.

**Validação e homologação:** testes dirigidos, Catalog Intelligence e suíte completa antes
do commit técnico. Roteiro de homologação: item novo; item existente com e sem delegação;
item sem conhecimento associado; motor interno indisponível. Em todos, conferir que nada é
gravado sem salvar e que o texto aplicado é o exibido.

**Entrega — commit técnico `1efe1b6` (`1efe1b642224f145d7b729dda2e4af45dbddb06d`), publicado**

- `ProdutoForm::gerarSugestao()` chama `GenerateListingSuggestion::comContexto()` com o
  `ListingContext` montado a partir dos valores atuais da tela, inclusive os não salvos; na
  edição, o `Product` entra como segundo argumento. O caminho de categoria usa
  `ListingContext::caminhoDaCategoria()`, que passou de privado a público.
- Pré-visualização de nome, resumo, descrição, palavras-chave e informações faltantes; a
  view renderiza a sugestão só com `{{ }}`, e a S-2 fica resolvida.
- `aplicarSugestao()` copia nome, resumo ou descrição para a tela, por ação explícita do
  lojista; nada é gravado ao gerar nem ao aplicar. Gravar continua sendo o `save()`, pela
  `SaveProductWithOffer`, que segue como fronteira de persistência.
- Sobrescrita: resumo e descrição só recebem a sugestão quando vazios; o nome só é trocado
  pelo clique explícito, com o nome atual visível.
- O estado da sugestão é `#[Locked]`; `guardOwnership()` roda antes de gerar e antes de
  aplicar.
- Autoridade canônica (`updateCanonical`): verificada ao gerar e reconferida no servidor em
  `aplicarSugestao()`, não a cada `render()`; a recusa da `SaveProductWithOffer` com
  `SemAutoridadeCanonica` continua intacta.
- Os 8 `ListingOutcomeState` têm mensagem própria. Com o mesmo contexto e um desfecho que
  não `convidaARepetir()`, gerar de novo não chama o assistente.
- `wire:target="save"` separa o estado de salvamento do estado de geração.
- `knownAttributes` fica vazio: a C-1 não foi exercida nem resolvida, e segue para a CAT-11.
- `NullCatalogAiProvider` continua o binding padrão. Ainda não existe provider externo real,
  cliente HTTP ou credencial.

**Travas reconciliadas**

- `ResilienciaDoAssistenteTest::test_o_caminho_de_cadastro_nao_referencia_a_inteligencia`
  deixou de listar o `ProdutoForm`, continua proibindo `CatalogIntelligence` em
  `SaveProductWithOffer` e no `ProdutoController` da API e passou a proibir, no corpo de
  `ProdutoForm::save()`, `CatalogIntelligence`, `GenerateListingSuggestion`, `ListingContext`
  e `sugest`.
- A versão pela tela de `test_cadastro_conclui_com_o_assistente_quebrado` é
  `AssistenteNoCadastroTest::test_falha_do_motor_mostra_aviso_e_nao_impede_salvar_manualmente`.

**Testes:** `AssistenteNoCadastroTest`, 33 testes — 25 simples e os 8 casos de
`test_cada_desfecho_tem_mensagem_aviso_so_na_falha_e_convite_so_no_transitorio`, pelo
provider `desfechos` —, cobrindo os previstos acima, inclusive sugestão não adulterável
pelo cliente, autoridade revogada entre gerar e aplicar, `Http::assertNothingSent()` com o
binding `Null`, texto hostil escapado e custo em consultas.

**Validação** (executada antes do commit técnico, sobre o conteúdo que ele contém)

- CAT-09: **33 passed · 176 assertions · 0 failures**.
- Catalog Intelligence: **357 passed · 2999 assertions · 0 failures** (antes: 324 · 2820).
- Suíte completa: **1327 passed · 6053 assertions · 0 failures** (antes: 1294 · 5874); os
  33 testes a mais são os da CAT-09.
- `git diff --check` limpo; Pint aprovado nos arquivos alterados; ROADMAP, README e
  ARCHITECTURE intocados no commit técnico.
- **Homologação visual em navegador: pendente.** O roteiro acima ainda não foi percorrido
  em navegador.

**Fora da CAT-09, por decisão do operador na revisão**

- `session('error')` da `SemAutoridadeCanonica` não exibida ao lojista — débito separado,
  preexistente, em [§17](#17-dívidas-técnicas).
- Custo de consultas em hierarquias profundas de categoria — observação técnica separada,
  em [§17](#17-dívidas-técnicas).

### CAT-10A — Primeiro provider real `[x]`

Concluída e publicada em `10fe2bf`. Aberta em 2026-09-14 por decisão de produto, à frente
da CAT-07 e da CAT-08 e sem renumerar fases. A CAT-10 planejada — observabilidade, custos e
segurança com provider acoplado — foi dividida: a **CAT-10A** conectou o primeiro provider
real, e a **CAT-10B** permanece futura, para observabilidade, custo e segurança ampliados.

**Objetivo.** Conectar um único provider externo ao Catalog Intelligence para permitir a
homologação real da geração de sugestões no cadastro de produtos.

- **Primeiro provider real: OpenAI.**
- O restante da aplicação continua dependendo só de `CatalogAiProvider`, e o
  `NullCatalogAiProvider` continua o fallback.
- A tela e a experiência da CAT-09 não mudaram.
- Fora do escopo: CAT-07, CAT-08, CAT-10B e CAT-11; qualquer mudança em `Product`,
  `ProductOffer`, autoridade canônica ou multi-oferta; telemetria avançada.

**Decisões aprovadas pelo operador (2026-09-14)** — registradas na ARCHITECTURE como
D-CAT-10A-1 a D-CAT-10A-7

- O adaptador fica em `app/Services/CatalogAi/`, fora do módulo; o domínio continua sem
  nome de fornecedor, transporte ou credencial.
- H-13 revista: o contrato resolve pelo `CatalogAiProviderSelector`, que devolve o `Null`
  quando o recurso está desligado, o provider não é suportado, falta chave ou modelo, ou o
  prazo é inválido — sem capturar exceção nenhuma.
- H-11 resolvida no adaptador: o texto de `ProviderInstruction` vem de `match` exaustivo, e
  o domínio continua carregando só o enum.
- B-3 não bloqueia a CAT-10A: trata só do `EmbeddingProvider`. B-6 continua aberta e
  bloqueia a ativação ampla em produção, não a homologação controlada.
- Homologação com `store: false`, a redação existente preservada antes da saída e
  `similar_items` enviado; a revisão ampliada de privacidade e governança fica na CAT-10B.
- Prazo máximo de 8 s — acima disso, limitado a 8; ≤ 0 ou não numérico, configuração
  inválida e `Null`. Nenhuma nova tentativa. `confidence` nula, sem pedir confiança ao
  modelo.
- Resposta impossível de converter para o contrato vira `CatalogAiProviderException`;
  resposta representável mas inválida vai ao `ProviderResponseValidator`.
- Responses API com Structured Outputs estrito, `store: false`, sem tools, busca ou conversa
  persistente, uma chamada por geração.
- Na revisão pré-commit: fatos objetivos do item só a partir de `dados_do_item`;
  `contexto_recuperado` não serve como prova factual.

**Entrega — commit técnico `10fe2bf` (`10fe2bfbb88132215d90644703b7c1e86c7f3a82`), publicado**

- `OpenAiCatalogAiProvider`, em `app/Services/CatalogAi`, fora do módulo, sobre a
  **Responses API**: `instruction` em `instructions`; `context` e `data` em dois itens
  distintos de `input`, sem texto que junte canais; **Structured Outputs com JSON Schema
  estrito**; `store: false`, sem tools, busca ou conversa; uma chamada por geração.
- `CatalogAiProviderSelector`, também fora do módulo, lê `config('services.catalog_ai')` a
  cada resolução e devolve o adaptador real só com o recurso ligado, provider `openai`,
  chave e modelo preenchidos e prazo válido; em qualquer outro caso, o
  `NullCatalogAiProvider`. Não captura exceção nem registra log.
- A aplicação continua dependendo só de `CatalogAiProvider`: o
  `CatalogIntelligenceServiceProvider` mantém um binding só, agora resolvido pelo seletor,
  sem `singleton`.
- **Recurso desligado por padrão.** Configuração por ambiente em `config/services.php` e
  `.env.example` — `CATALOG_AI_ENABLED` (`false`), `CATALOG_AI_PROVIDER`,
  `CATALOG_AI_MODEL`, `CATALOG_AI_API_KEY`, `CATALOG_AI_TIMEOUT` —, sem modelo fixado no
  código e sem chave versionada. Operação em
  [`README.md`](README.md#catalog-intelligence-provider-externo).
- **Timeout máximo de 8 s**, no prazo total e no de conexão; acima disso, limitado a 8.
  **Zero retry.**
- Falha esperada — prazo, conexão, HTTP fora de 2xx, corpo ilegível, resposta incompleta,
  recusa, ausência de texto estruturado, JSON fora do contrato — vira
  `CatalogAiProviderException` com mensagem fixa e sem exceção encadeada. Resposta
  representável mas inválida segue ao `ProviderResponseValidator`. Defeito inesperado sobe.
- Regra 1 da instrução: **fatos objetivos do item só a partir de `dados_do_item`**;
  **`contexto_recuperado` não serve como prova factual** — só terminologia, clareza,
  organização e palavras-chave.
- **`confidence` = null**, sem pedir confiança ao modelo.
- Docblocks do módulo que ficariam falsos foram reconciliados, sem citar fornecedor.
- Nenhuma alteração em `ProdutoForm`, na UX da CAT-09, em `Product`, `ProductOffer`,
  autoridade canônica ou multi-oferta.

**Bloqueios e dívidas reconciliados**

- **H-11 — resolvida.** O texto de `ProviderInstruction` mora no adaptador, por `match`
  exaustivo sem `default`; o domínio continua carregando só o enum.
- **H-13 — resolvida e superada pelo desenho novo.** O binding deixou de ser o `Null`
  direto: o contrato resolve pelo `CatalogAiProviderSelector`, o `Null` continua fallback e
  o `Fake` nunca entra em runtime. `test_o_binding_padrao_do_contrato_e_o_null` foi revisto
  de propósito: um binding só, sem `singleton` nem `Fake`; `Null` sem configuração e
  adaptador real com configuração válida.
- **B-3** trata do `EmbeddingProvider` e **não bloqueia** a CAT-10A.
- **B-6 continua aberta.** Não bloqueia a homologação controlada; bloqueia a ativação ampla
  em produção até a **CAT-10B**, onde entram custo, rate limit, observabilidade e a revisão
  ampliada.

**Testes:** `AdaptadorOpenAiTest` (adaptador e integração ponta a ponta pelo
`GenerateListingSuggestion`, inclusive
`test_caracteristica_so_do_contexto_nao_e_autorizada_como_fato_do_item`),
`SelecaoDoProviderTest` (seletor) e `ContratosDeProviderTest` revisto (H-13). **Nenhuma
chamada real à OpenAI na suíte:** `Http::fake` com `preventStrayRequests`, e o
`phpunit.xml` força `CATALOG_AI_ENABLED=false` e `CATALOG_AI_API_KEY` vazia.

**Validação**

- Testes dirigidos: **85 passed · 2579 assertions**.
- Catalog Intelligence: **442 passed · 5581 assertions** (antes: 357 · 2999).
- Suíte completa: **1411 passed · 8617 assertions · 0 failures** (antes: 1327 · 6053),
  executada sobre a implementação técnica **antes do ajuste textual final da regra 1** da
  instrução. Depois do ajuste, os dois conjuntos acima — dirigidos e Catalog Intelligence
  completo — foram executados e passaram. O teste da regra 1 nasceu com o ajuste e não
  está entre os 1411.
- **Exceção deliberada** ao critério de suíte completa sobre o código final
  ([ARCHITECTURE §21](docs/ARCHITECTURE.md#21-testes-e-validação)): a repetição da suíte
  completa depois do ajuste textual foi dispensada explicitamente na revisão da CAT-10A.
  Os 1411 **não** foram executados sobre o conteúdo final de `10fe2bf`.
- Controles negativos: 8 da implementação e 1 da regra 1, todos detectados, com arquivos
  restaurados por hash.
- Pint aprovado nos arquivos alterados; `git diff --cached --check` limpo; ROADMAP,
  ARCHITECTURE e README fora do commit técnico.
- **Homologação real: pendente.** Não é fase nova: é a verificação do assistente com o
  provider ligado por ambiente, em ambiente controlado, antes da CAT-10B.

### Dívidas da trilha

Tabela única em [§17](#17-dívidas-técnicas) — C-1, C-2, F-1, S-1, S-2, P-1, B-4,
G-1, E-1, D-1…D-4 (CAT-05H), B-3, B-5, B-6 (CAT-06A), H-11, H-12 e H-13 (CAT-06H).

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
do webhook), **LGPD-01** (CPF/CNPJ em claro), **C-1** — ver
[§17](#17-dívidas-técnicas). A **S-2** foi resolvida na CAT-09 (`1efe1b6`).

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
- `[ ]` `CATALOG_AI_ENABLED=false` até a **CAT-10B** (B-6); chave do provider só no ambiente, nunca versionada
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
| **C-1** (CAT-05C) | `knownAttributes` protegido por lista de proibição; quem o popular deve mapear campo a campo | Média | **CAT-11** — hardening/revisão final. Não resolvida: a CAT-09 só preservou a restrição, com `knownAttributes` vazio |
| **C-2** (CAT-05C) | Texto livre não é redigido antes de sair para provider | Gate — **`[x]` fechado** pelo `FreeTextRedactor` | **CAT-06E** · `101748a` |
| **S-1** (CAT-05G) | Teste de prompt injection real, com `PromptGuard` | Gate — **`[x]` fechado** pelo `PromptGuard` | **CAT-06F** · `f7b39c2` |
| **S-2** (CAT-05G) | A sugestão ecoa texto do lojista: renderizar sempre escapado | **`[x]` resolvida** — a tela do lojista renderiza a sugestão só com saída escapada, com testes de conteúdo hostil (`test_texto_hostil_do_lojista_volta_escapado`, `test_texto_hostil_vindo_de_fora_volta_escapado`) | **CAT-09** · `1efe1b6` |
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
| — (CAT-09) | `ProdutoForm::save()` registra a recusa por `SemAutoridadeCanonica` em `session('error')`, mas a mensagem não é exibida ao lojista. Preexistente, identificado na revisão da CAT-09 | A classificar | Débito separado, fora da CAT-09 (decisão do operador) |

Resolvidas e registradas para não serem reabertas: D-1 (CAT-DOM-01, espelho
comercial — 02C/02H), D-2 (CAT-DOM-01, conteúdo autoral no mestre — 02C a 02F),
D-3 (CAT-DOM-01, regra de cadastro duplicada — `SaveProductWithOffer`), M-01,
M-02, M-03, M-04, M-06, M-08, M-09 (decisões de compra), M-10/F-09 (slug — 02G),
M-16, M-17 (05B), itens 1, 3, 5, 7–12 da tabela de riscos da CAT-01.

### Catalog Intelligence

| ID | Dívida | Destino |
|---|---|---|
| **F-1** (CAT-05F) | Sem sinal de modo degradado | **`[x]` fechado** na **CAT-06G** — desfecho `ListingOutcome`, 8 estados (D-CAT-06G-3, D-CAT-06G-11) |
| **B-5** (CAT-06A) | Timeout de chamada a provider | **`[x]` decidido** na **CAT-06G** — 8 s aplicados pelo adaptador, 0 novas tentativas, sem config até haver leitor (D-CAT-06G-5, D-CAT-06G-6). **Aplicado** na **CAT-10A** (`10fe2bf`): `CATALOG_AI_TIMEOUT`, limitado a 8 pelo `CatalogAiProviderSelector`, no prazo total e no de conexão, sem retry |
| **B-3** (CAT-06A) | `EmbeddingProvider` órfão: na especificação, nenhuma fase o reivindica; trava de teste o mantém inexistente | Em aberto, sem decisão. Trata só do `EmbeddingProvider` e **não bloqueia** a CAT-10A |
| **B-6** (CAT-06A) | Custo e rate limit | Em aberto — **CAT-10B**, com custo, rate limit, observabilidade e revisão ampliada. **Não bloqueia** a homologação controlada do provider da CAT-10A; **bloqueia** a ativação ampla em produção |
| **P-1** (CAT-05B) | Backfill de `catalog_product_knowledge` em produção (em dev foi rodado e revertido na 05H) | Decisão humana após G-1 |
| **B-4** (CAT-05A) | Corpus de seeder: `short_description` vazia em 75/75, "demonstração" em 34/75 | Depende de catálogo real |
| **E-1** (CAT-05E) | `KnowledgeTermType::Keyword` sem uso | Decidir quando houver registro |
| **D-1** (CAT-05H) | Caminho da descrição sem cobertura real (75/75 já têm descrição) | **CAT-11** — hardening/revisão final. Sem evidência de resolução na CAT-09 |
| **D-2** (CAT-05H) | `palavrasChave()` não pondera por score | CAT-07 (alternativa CAT-11) |
| **D-3** (CAT-05H) | Casamento por frase exata não alcança termo intercalado | CAT-11 — reabre a CAT-04 |
| **D-4** (CAT-05H) | 8 de 28 conceitos sem evidência direta (inclusive `Crochê`) | CAT-08 |
| — (CAT-06A/06B) | Nomenclatura `ListingAssistant` × `GenerateListingSuggestion` (o DTO de desfecho foi batizado na 06G: `ListingOutcome`) | Reconciliada tecnicamente na **CAT-06H-F** (`b8d3d56`), nos testes, e na **CAT-06H-G** (`dc217cf`), no resíduo textual de `ListingContext.php` (H-09): `GenerateListingSuggestion` canônico |
| — (CAT-06D §10) | Auditorias devem varrer asserções (`assertFalse(class_exists`), não só arquivos | **`[x]` feito** na **CAT-06H-H** — em `tests/`, a única trava de inexistência é a do `EmbeddingProvider`, deliberada (B-3) |
| **H-11** (CAT-06H) | Onde mora o texto da instrução (`ProviderInstruction`) quando existir adaptador real: no domínio ou no adaptador | **`[x]` resolvida** na **CAT-10A** (`10fe2bf`): o texto mora no adaptador, por `match` exaustivo sem `default`; o domínio continua carregando só o enum (D-CAT-10A-3) |
| **H-13** (CAT-06H) | `test_o_binding_padrao_do_contrato_e_o_null` fixava o contrato no `NullCatalogAiProvider`; um provider real precisaria quebrá-lo e exigir revisão arquitetural explícita | **`[x]` resolvida e superada** pelo desenho da **CAT-10A** (`10fe2bf`): o binding não é mais o `Null` direto — o contrato resolve pelo `CatalogAiProviderSelector`, o `Null` continua fallback e o `Fake` nunca entra em runtime; a trava foi revista para prender esse caminho (D-CAT-10A-2) |
| **H-12** (CAT-06H) | Captura ampla de `Throwable` no motor interno de `GenerateListingSuggestion::completar()`: defeito de programação pode virar `InternalIntelligenceFailed` que convida a repetir, e a mensagem de exceção que não seja `QueryException` vai para o log; sem teste de `TypeError`/`Error` no motor, de propósito | Dívida histórica e risco latente de observabilidade/privacidade — em aberto, sem fase; exige decisão explícita futura |
| — (CAT-05G · CAT-09) | Custo de consultas em hierarquias profundas de categoria: a subida do `ListingContext` custa 1 consulta por ancestral não carregado — em `deProduct()` sem `with('category.parent')` e, no `ProdutoForm` da CAT-09, a cada nível acima dos dois que o `with('parent')` cobre. Observação, não dívida | Observação técnica separada, sem fase — não otimizada na CAT-09 (decisão do operador) |

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

A **CAT-09** foi concluída e publicada em `1efe1b6`; a homologação visual dela em navegador
segue pendente ([§14](#cat-09--implantação-do-assistente-no-catálogo-x)). A **CAT-10A** foi
concluída e publicada em `10fe2bf` ([§14](#cat-10a--primeiro-provider-real-x)).

Ordem decidida pelo operador (2026-09-14):

1. **CAT-10A — primeiro provider real (OpenAI)** — `[x]` concluída.
2. **Homologação real** do assistente com o provider ligado, em ambiente controlado —
   pendente. **Não é fase nova.**
3. **CAT-10B — observabilidade, custo e segurança ampliados.** Vem antes da CAT-08 porque a
   B-6 continua impedindo a ativação ampla em produção sem observabilidade, custo e rate
   limit mínimos; inclui a revisão ampliada de privacidade e governança.
4. **CAT-08 — interface administrativa**: fecha G-1 e destrava P-1 e D-4.
5. **CAT-07 — feedback humano e memória.**
6. **CAT-11 — hardening e revisão final.**

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
| Fim da CAT-06G (`5a667b4`) | 1289 · 5603 |
| Fim da CAT-06 (`dc217cf`) | 1294 · 5874 |
| Fim da CAT-09 (`1efe1b6`) | 1327 · 6053 |
| **Fim da CAT-10A** (implementação antes do ajuste textual final contido em `10fe2bf`) | **1411 · 8617** — sobre o conteúdo final de `10fe2bf` rodaram só dirigidos 85 · 2579 e Catalog Intelligence 442 · 5581 (exceção deliberada, §14) |

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
- Subfase sem código — validação ou auditoria — tem só ciclo documental, e o fechamento
  da fase pode ir no mesmo commit documental, sem commit posterior só para trocar `[~]` por
  `[x]` (decisão do operador, 2026-09-13, no encerramento da CAT-06).
