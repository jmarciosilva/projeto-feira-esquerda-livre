# 🗺️ Roadmap de Desenvolvimento — Feira Esquerda Livre

**Documento de Planejamento Estratégico**
**Versão:** 2.8 — Agosto de 2026
**Status geral do projeto:** MVP demonstrável. A plataforma já permite apresentar a jornada pública da feira, navegação por eixos, expositores, marketplace, carrinho, checkout, integração inicial com Mercado Pago, páginas institucionais, formulário de contato com resposta automática, painel administrativo com identidade visual, comunidade/feed, email marketing, visibilidade de expositores, rastreio, comunicação loja-cliente, AVA com curso online, progresso e certificado PDF, e uma API REST (`/api/v1`, Sanctum) pronta para o app mobile em Flutter (cliente e lojista). Fases futuras seguem concentradas em automações de escala: split automático completo via Mercado Pago, OAuth por lojista no Melhor Envio, compra/geração de etiquetas físicas, auditoria ampliada, recuperação de carrinho abandonado e ampliação da API mobile (construtor de curso e feed). Em paralelo, corre a **Trilha CI**, que transforma o Customer Intelligence de integração externa em módulo nativo do projeto, e o ambiente de desenvolvimento passou a rodar inteiramente em **Docker**.

---

## Visão Geral das Fases

```
[FASE 1 ✅ CONCLUÍDA]
     CMS, Admin & Home
           ↓
[FASE 2 ✅ CONCLUÍDA]
  Lojistas & Agenda
           ↓
[FASE 3 ✅ CONCLUÍDA]
  Catálogo & Três Eixos
           ↓
[FASE 4 ✅ MVP AVANÇADO]
  Checkout, cotação de frete, Mercado Pago inicial, pedidos e rastreio
           ↓
[FASE 5 ✅ IMPLEMENTADA]
  Comunidade, feed, email marketing e visibilidade de expositores
           ↓
[FASE 6 ✅ CONCLUÍDA]
  Usuários internos, perfis & permissões
           ↓
[FASE 7 ✅ CONCLUÍDA]
  Comunicação entre Loja e Cliente
  FAQ · Q&A Público · Chat pós-pedido
           ↓
[FASE 8 ✅ CONCLUÍDA]
  AVA — Ambiente Virtual de Aprendizagem
  Infraestrutura · Course Builder · Player · Materiais · Certificado PDF
           ↓
[FASE 9 ✅ CONCLUÍDA — v1]
  API Mobile (Flutter)
  Sanctum · Catálogo · Carrinho · Checkout · Pedidos · Chat · AVA · Lojista
           ↓
[FASE 10 ✅ CONCLUÍDA]
  Inteligência de Cliente (JMF CI) - Sprint 3
  Dashboard · Rastreamento · E2E Tests
           ↓
[TRILHA CI 🔄 EM ANDAMENTO]
  Internalização do Customer Intelligence
  CI-01 a CI-05 ✅ · CI-06 a CI-09 pendentes
  Deixa de depender do SDK externo em ../jmf-ci-sdk
```

> A Trilha CI não substitui a Fase 10: ela reaproveita o que a Fase 10 entregou
> (eventos, painel, permissão, views) e troca a origem dos dados — de uma API
> remota para o próprio banco da Feira. Detalhes em
> [Trilha CI](#-trilha-ci--internalização-do-customer-intelligence-em-andamento).

---

## Marco Atual — MVP Demonstrável

O projeto atingiu um ponto mínimo viável para demonstração completa ao cliente:

| Jornada | Situação |
|---|---|
| Visitante público | Home responsiva, navbar/footer padronizados, banners, agenda, expositores, marketplace, blog, contato, privacidade e termos |
| Cliente comprador | Cadastro/login, carrinho, checkout, Mercado Pago inicial, pedido, comunicação pós-venda e aprendizado |
| Expositor/lojista | Painel da loja, cadastro de produtos/serviços/cuidados, feed, pedidos, perguntas, cursos digitais e exposição |
| Equipe interna | Dashboard administrativo, CMS, configurações, logo/favicon, banners, páginas, eventos, mídia, usuários, permissões e checkout |
| App mobile (Flutter) | Backend pronto: API `/api/v1` com Sanctum cobrindo catálogo, carrinho, checkout, pedidos, chat, endereços, AVA e gestão da loja — ver `docs/API.md` |
| Demonstração de AVA | Produto digital `Curso Online de Informática Popular`, matrícula demo, quatro aulas, progresso e certificado PDF |

Credencial de demonstração do curso: `cliente.curso@teste.com` / `password`, com acesso em `/minha-conta/aprendizado`.

---

## ✅ Fase 1 — Fundação (Concluída)

### O que foi entregue

| Componente | Status |
|---|---|
| CMS com painel administrativo (Livewire) | ✅ Concluído |
| Models: Banner, Event, Post, Menu, Page, SiteSetting | ✅ Concluído |
| Models: Expositor, Product, NewsletterSubscriber | ✅ Concluído |
| Migrations e Seeders demonstrativos | ✅ Concluído |
| Home pública com 8 seções alimentadas pelo banco | ✅ Concluído |
| Navbar e Footer com identidade visual `#F4E294` | ✅ Concluído |
| Carousel de banners responsivo (AlpineJS) | ✅ Concluído |
| Seção Newsletter (POST /newsletter → DB) | ✅ Concluído |
| Gestão de Banners, Eventos, Posts, Mídia via Admin | ✅ Concluído |
| Correção de upload de arquivos (Windows + Livewire) | ✅ Concluído |

**Stack técnica estabelecida:**
Laravel 12 · Livewire 4 · TailwindCSS 4 · AlpineJS · MySQL · Vite

---

## ✅ Fase 2 — O Ecossistema do Lojista e Agenda de Feiras (Concluída)

**Período:** Semanas 4 a 6
**Objetivo estratégico:** Integrar lojistas e organizadores à plataforma e preparar o terreno para o catálogo de produtos.

### O que foi entregue

| Componente | Status |
|---|---|
| Formulário público `/seja-um-expositor` com validação de CPF/CNPJ | ✅ Concluído |
| Tabela `lojista_solicitacoes` com status pendente/aprovado/bloqueado | ✅ Concluído |
| Campos adicionais: Instagram, Facebook, PIX, dados bancários, eixos declarados | ✅ Concluído |
| Painel admin de aprovação de solicitações (Livewire) | ✅ Concluído |
| Aprovação cria `User` (role `lojista`) + `Expositor` vinculado automaticamente | ✅ Concluído |
| Middleware `lojista` com verificação de role e status do expositor | ✅ Concluído |
| Área restrita `/minha-loja` com sidebar simplificada | ✅ Concluído |
| Formulário de configuração da loja (logo, banner, descrição, redes, slug, cidade/UF) | ✅ Concluído |
| Campos financeiros no painel do lojista (PIX, banco) | ✅ Concluído |
| Agenda pública `/agenda` com filtro por estado e mês | ✅ Concluído |
| Página de detalhe `/agenda/{slug}` com expositores confirmados | ✅ Concluído |
| Pivot `event_expositores` (event_id · expositor_id · status) | ✅ Concluído |
| Campos Fase 2 na tabela `events` (banner, capacidade, vagas, is_featured) | ✅ Concluído |

---

### Módulo 2.1 — Fluxo de Entrada do Lojista

**Objetivo:** Criar o processo de solicitação e aprovação de novos lojistas.

**Entregas:**

**2.1.1 — Formulário Público de Solicitação**
- Campos obrigatórios: CPF/CNPJ, Nome da Loja, Nome do Responsável, WhatsApp, e-mail
- Campo opcional: Descrição breve do que vende
- Validação de CPF/CNPJ no frontend (algoritmo nativo, sem API externa)
- Rota pública: `/seja-um-expositor`
- Confirmação via mensagem de sucesso na tela (sem e-mail por ora)
- Dados salvos na tabela `lojista_solicitacoes` com status `pendente`

**2.1.2 — Painel de Aprovação (Admin)**
- Listagem de solicitações com filtro por status: Pendente · Aprovado · Bloqueado
- Ação de aprovação cria o `User` com role `lojista` e o registro `Expositor` vinculado
- Ação de bloqueio registra motivo (campo textarea) e impede futuros logins
- Notificação visual no dashboard do admin com contagem de pendências

**Tabelas novas:**
```
lojista_solicitacoes
  - id, nome_loja, responsavel, cpf_cnpj, whatsapp, email
  - descricao, status (pendente/aprovado/bloqueado), motivo_bloqueio
  - created_at, updated_at
```

**Dependências de Fase 1 utilizadas:**
- Model `Expositor` (já existe)
- Enum `UserRole` (adicionar `lojista`)
- Painel admin com Livewire (base pronta)

---

### Módulo 2.2 — Painel do Lojista (Perfil da Loja)

**Objetivo:** Dar ao lojista aprovado autonomia para configurar sua presença na plataforma.

**Entregas:**

**2.2.1 — Área restrita `/minha-loja`**
- Middleware `lojista` verificando `UserRole::Lojista` e status ativo no `Expositor` vinculado
- Sidebar simplificada: Minha Loja · Meus Produtos · Agenda · Sair

**2.2.2 — Formulário de Configuração da Loja**
- Upload de logotipo (recomendado: 400×400px, exibido em formato circular)
- Upload de banner da loja (recomendado: 1200×400px)
- Descrição da loja (textarea rico simples, sem editor pesado)
- Links de redes sociais: Instagram, Facebook, WhatsApp
- Slug da loja (gerado automaticamente, editável com verificação de unicidade)
- Cidade e Estado (UF em select — lista fixa dos 27 estados)

**Design para público 40+:**
- Todos os campos com `min-height: 52px`
- Labels grandes acima dos campos (não dentro)
- Botão salvar full-width no mobile com `min-height: 60px`
- Feedback visual imediato ao salvar (sem reload de página)

---

### Módulo 2.3 — Agenda Nacional de Feiras

**Objetivo:** Interface robusta para o administrador gerenciar os eventos físicos, com busca acessível para o público.

**Entregas:**

**2.3.1 — CRUD Admin de Eventos (expansão do existente)**
- O model `Event` já existe — expansão com campo `is_featured` para destaque na Home
- Adicionar campo `banner_image_path` (1920×600px) para página de detalhe do evento
- Adicionar campo `capacidade_expositores` e `vagas_restantes`
- Vínculo entre lojistas inscritos e o evento (`event_expositores` pivot)

**2.3.2 — Página Pública de Agenda `/agenda`**
- Layout em grade: cards grandes com data destacada, cidade, estado
- **Filtros acessíveis (foco no público 40+):**
  - Select de UF com todas as opções visíveis (não autocomplete)
  - Select de Mês/Ano
  - Botão "Buscar" grande, sem submit automático
  - Limpar filtros com um toque
- Sem mapas ou geolocalização nesta fase (carregamento leve em 3G/4G)
- Paginação simples: botão "Ver mais feiras" (lazy load)

**2.3.3 — Página de Detalhe do Evento `/agenda/{slug}`**
- Banner do evento full-width
- Data, horário, endereço completo, link para Google Maps (simples `maps.google.com?q=...`)
- Lista de expositores inscritos (grid de logos)
- Botão "Quero Expor Nesta Feira" → link para formulário do Módulo 2.1
- Botão "Compartilhar no WhatsApp" com texto pré-formatado

**Tabelas novas/alteradas:**
```
events (alter)
  + banner_image_path
  + capacidade_expositores
  + vagas_restantes
  + is_featured

event_expositores (nova — pivot)
  - event_id, expositor_id, status (confirmado/pendente), created_at
```

---

## ✅ Fase 3 — Catálogo de Produtos e Loja Pública (Concluída)

**Período:** Semanas 7 a 9
**Objetivo estratégico:** Transformar o marketplace em utilidade real — produtos cadastráveis pelos lojistas e navegáveis pelo público com fluidez nos três eixos.

### O que foi entregue

| Componente | Status |
|---|---|
| Campos Fase 3 na tabela `products` (imagens JSON, stock, is_active, sort_order) | ✅ Concluído |
| Tabela `cart_items` (sessão + user_id + price_snapshot + expositor_id) | ✅ Concluído |
| **Três eixos do marketplace — estrutura de dados** | ✅ Concluído |
| Campo `item_type` na tabela `products` com enum `ItemType` (produto/servico/cuidado) | ✅ Concluído |
| Campos `price_type` (fixo/por_hora/por_sessao/sob_consulta) e `modality` (presencial/online/ambos) | ✅ Concluído |
| Campo `duration_min` para serviços e cuidados com duração em minutos | ✅ Concluído |
| Campo `eixo` em `content_categories` para filtro por catálogo | ✅ Concluído |
| Campo `eixos` (JSON) em `expositores` — um lojista pode atuar nos três eixos | ✅ Concluído |
| Campo `eixos` (JSON) em `lojista_solicitacoes` — declarado no formulário de entrada | ✅ Concluído |
| Enums `ItemType`, `PriceType` e `Modality` com labels e emojis em pt-BR | ✅ Concluído |
| Rotas públicas `/produtos`, `/servicos` e `/cuidados` com paginação e filtros | ✅ Concluído |
| Scope `Product::scopeDoEixo()` e métodos `isProduto()` / `isServico()` / `isCuidado()` | ✅ Concluído |
| View `catalogo.index` compartilhada pelos três eixos | ✅ Concluído |
| CRUD de Produtos do lojista (`ProdutoIndex`/`ProdutoForm`) com upload e compressão via `intervention/image` | ✅ Concluído |
| Toggle ativo/inativo na listagem de produtos do lojista | ✅ Concluído |
| Loja pública `/loja/{slug}` e página de produto `/loja/{slug}/{produto-slug}` | ✅ Concluído |
| Carrinho multilojas (`CartDrawer` + `CartService`) com agrupamento por loja | ✅ Concluído |
| CRUD de Categorias no admin (`/admin/categorias`), vinculadas a um eixo | ✅ Concluído |
| Ordenação drag-and-drop dos produtos do lojista (Livewire + SortableJS) | ❌ Não entregue — adiado, `sort_order` é só um campo numérico manual |

---

### Módulo 3.1 — CRUD de Produtos (Área do Lojista) — ✅ Concluído (com 1 ressalva)

**Objetivo:** Permitir que o lojista gerencie seu catálogo de forma autônoma.

**Entregas:**

**3.1.1 — Formulário de Cadastro de Produto**
- Seletor de eixo no topo do formulário (Produto / Serviço / Cuidado & Bem Viver) — determina quais campos aparecem
- **Campos comuns (todos os eixos):** Nome, Descrição, Preço (R$), Categoria (filtrada pelo eixo selecionado)
- **Campos exclusivos de Produtos:** "Em estoque" (toggle) e quantidade opcional
- **Campos exclusivos de Serviços e Cuidados:** Modalidade (Presencial / Online / Ambos), Tipo de preço (Fixo / Por hora / Por sessão / A combinar), Duração estimada (minutos)
- Upload de até 4 fotos com compressão automática no backend
  - Biblioteca: `intervention/image`
  - Redimensionamento automático: thumbnail 300×300px · médio 800×800px
  - Formato de saída: WebP (reduz 30–40% do tamanho sem perda visual)
  - **Justificativa:** fundamental para carregamento rápido em redes 3G/4G durante as feiras
- Slug gerado automaticamente
- Vínculo automático com o `Expositor` do lojista logado (sem campo manual)

**Exemplos por eixo:**

| Eixo | Exemplos de itens |
|---|---|
| 🛍️ Produtos | Artesanato, livros, roupas, alimentos, cosméticos naturais |
| 🎯 Serviços | Design gráfico, comunicação, aulas, consultorias, fotografia |
| 🌿 Cuidados & Bem Viver | Massagens, terapias, práticas integrativas, yoga, meditação |

**3.1.2 — Listagem de Produtos do Lojista**
- Tabela com miniatura, nome, preço e status (ativo/inativo)
- Toggle rápido de ativo/inativo na listagem (sem abrir formulário)
- Ordenação drag-and-drop para definir ordem de exibição (Livewire + SortableJS) — **pendente**, ver Módulo 3.4
- Limite sugerido: 50 produtos por lojista na versão inicial

**Tabelas alteradas (já migradas):**
```
products (alter)
  + item_type   → enum: produto | servico | cuidado  ✅
  + category_id (FK content_categories)              ✅
  + stock_quantity                                   ✅
  + has_stock (boolean)                              ✅
  + images (JSON — paths das imagens comprimidas)    ✅
  + is_active                                        ✅
  + sort_order                                       ✅
  + price_type  → enum: fixo | por_hora | por_sessao | sob_consulta  ✅
  + modality    → enum: presencial | online | ambos  ✅
  + duration_min (smallint, nullable)                ✅

content_categories (alter)
  + eixo (nullable — filtra categorias por eixo)     ✅

expositores (alter)
  + eixos (JSON — ex.: ["produto","cuidado"])        ✅

lojista_solicitacoes (alter)
  + eixos (JSON — eixos declarados pelo candidato)   ✅
```

---

### Módulo 3.2 — Renderização das Lojas e Produtos (Visão do Cliente) — ✅ Concluído

**Objetivo:** Criar as páginas públicas das lojas com UX otimizada para o público 40+.

**Entregas:**

**3.2.1 — Página da Loja `/loja/{slug}`**
- Banner da loja full-width com sobreposição do logo
- Nome da loja, cidade/estado, descrição, links de redes sociais
- Grade de produtos: 2 colunas no mobile · 3 no tablet · 4 no desktop
- Filtro por categoria (tabs simples no topo — não dropdown)
- Barra de busca por nome do produto (busca local na página, sem backend)

**3.2.2 — Grade de Produtos (UX 40+)**
- Card de produto: imagem quadrada 1:1, nome (fonte mín. 16px), preço em destaque
- Botão "Adicionar ao Carrinho" full-width no mobile (altura mín. 52px)
- Sem modais — ao tocar no produto, vai para a página do produto
- Preço formatado em R$ com vírgula (ex.: R$ 89,90 — nunca R$ 89.90)

**3.2.3 — Página do Produto `/loja/{slug}/{produto-slug}`**
- Galeria de imagens com swipe no mobile (CSS nativo, sem biblioteca pesada)
- Nome, preço, descrição completa, nome da loja com link
- Botão "Adicionar ao Carrinho" fixo no rodapé mobile (sticky bottom)
- Botão "Falar com o Lojista no WhatsApp" com texto pré-formatado do produto

---

### Módulo 3.3 — Carrinho Multilojas (Livewire) — ✅ Concluído

**Objetivo:** Carrinho que agrupa produtos de lojistas diferentes de forma transparente.

**Entregas:**

**3.3.1 — Lógica do Carrinho**
- Armazenamento: sessão PHP + sincronização com banco para usuários logados
- Agrupamento visual por loja no carrinho (seções separadas por lojista)
- Subtotal por loja e total geral
- Persistência de 7 dias para usuários não logados (cookie assinado)

**3.3.2 — Interface do Carrinho (Livewire)**
- Ícone de carrinho na navbar com badge de quantidade (atualização reativa)
- Painel lateral deslizante (drawer) no mobile ao tocar no ícone
- Aumentar/diminuir quantidade com botões `−` e `+` grandes (mín. 44×44px)
- Remover item com confirmação de um toque
- Botão "Finalizar Compra" levando ao checkout da Fase 4

**Tabela nova:**
```
cart_items
  - id, session_id, user_id (nullable), product_id, quantity
  - price_snapshot (decimal — congela o preço no momento da adição)
  - expositor_id (desnormalizado para facilitar o split)
  - created_at, updated_at
```

---

### Módulo 3.4 — Pendências técnicas adiadas para depois da Fase 4

Itens descritos na Fase 3 original que não bloqueiam o checkout e foram conscientemente adiados:

- Ordenação drag-and-drop dos produtos do lojista (Livewire + SortableJS) — hoje `sort_order` é editado manualmente no formulário
- Persistência do carrinho por 7 dias via cookie assinado para usuários não logados — validar comportamento atual de expiração de sessão
- Limite de 50 produtos por lojista — ainda não há validação/enforcement no backend

---

## ✅ Fase 4 — Checkout, Frete, Pagamento e Rastreio (MVP avançado)

**Período:** Semanas 10 a 13
**Objetivo estratégico:** A fase mais crítica do core do negócio — calcular frete, cobrar e distribuir o dinheiro automaticamente.

**Decisão de produto (16/06/2026):** para validar o fluxo completo de compra com o Gerente de Produto e o cliente o mais rápido possível,
a Fase 4 foi entregue inicialmente como um **MVP 100% manual**: frete combinado diretamente entre cliente e lojista
(WhatsApp) e pagamento via PIX/dados bancários do próprio lojista, confirmado manualmente.

**Atualização técnica (01/07/2026):** a integração inicial com o Melhor Envio foi implementada para cálculo de frete
no checkout. O MVP usa uma conta única da plataforma Feira Esquerda Livre, com credenciais via `.env`/configuração,
busca CEP de origem por loja, envia peso e dimensões dos produtos e retorna opções padronizadas para seleção no checkout.
Compra de etiqueta, geração de etiqueta, OAuth por lojista, split de frete e painel financeiro permanecem fora do escopo atual.

**Atualização técnica (01/08/2026):** o checkout evoluiu para o MVP avançado, com Mercado Pago inicial configurável pelo
painel, tratamento adequado para testes locais, rastreio personalizado, pedidos por split e base operacional suficiente
para demonstração do fluxo de compra. O split automático completo e a geração de etiquetas seguem como pós-MVP.

### O que foi entregue

| Componente | Status |
|---|---|
| Tabelas `orders`, `order_items`, `order_splits` | ✅ Concluído |
| Campos de frete/pagamento manuais + credenciais futuras em `site_settings` | ✅ Concluído |
| Enums `OrderStatus` e `OrderSplitStatus` | ✅ Concluído |
| `OrderService::createFromCart()` — cria pedido, itens e split por loja a partir do carrinho | ✅ Concluído |
| Checkout público `/checkout` (dados do cliente + endereço, sem login obrigatório) | ✅ Concluído |
| Página de confirmação `/pedido/{reference}` com instruções de PIX/banco por loja | ✅ Concluído |
| Admin → `/admin/settings/checkout`: mensagem de frete manual e comissão da plataforma | ✅ Concluído |
| Admin → campos placeholder para Melhor Envio (client id/secret/token) e Mercado Pago (public key/access token) | ✅ Concluído — salvos para uso operacional/futuro |
| Admin → `/admin/pedidos`: visão geral de todos os pedidos, com atualização de status | ✅ Concluído |
| Lojista → `/minha-loja/pedidos`: confirmação manual de pagamento recebido por loja | ✅ Concluído |
| Botão "Finalizar Compra" do carrinho ligado ao checkout | ✅ Concluído |
| Cálculo automático de comissão (%) para fins de relatório (não retida de fato) | ✅ Concluído |
| Cotação real de frete (Melhor Envio) | ✅ Implementado no MVP — ver Módulo 4.1 |
| Cobrança e split automático (Mercado Pago) | ❌ Adiado — ver Módulo 4.3 |
| Login simplificado por link mágico/Google no checkout | ❌ Adiado — checkout aceita convidado com nome/WhatsApp/e-mail |

---

### Módulo 4.1 — Cálculo de Frete (Melhor Envio) — ✅ Cotação MVP implementada

**Objetivo:** Cotação de frete automatizada por lojista no carrinho.

**Status atual:** o checkout permite consultar frete por CEP de destino, agrupando itens por loja. Para cada loja,
o sistema usa o CEP de origem cadastrado no perfil do lojista e envia ao Melhor Envio os produtos físicos com peso,
altura, largura, comprimento, valor e quantidade. O retorno é normalizado para exibir transportadora, serviço, prazo,
preço e mensagens de erro. O cliente pode selecionar uma opção por loja e o resumo do pedido soma o frete ao total.

**Entregas concluídas no MVP:**
- Configuração via `.env`: `MELHOR_ENVIO_BASE_URL`, `MELHOR_ENVIO_TOKEN`, `MELHOR_ENVIO_ENVIRONMENT`
- `config/melhorenvio.php`
- Service `App\Services\Shipping\MelhorEnvioService`
- DTO `App\DTO\ShippingQuoteData`
- Endpoint `POST /shipping/quote`
- Campos de origem da loja: CEP, rua, número, bairro, cidade e estado
- Campos logísticos de produto: peso, altura, largura e comprimento
- Tratamento claro para produtos sem dados logísticos, sem quebrar o checkout
- Documentação técnica em `docs/INTEGRACAO_FRETE_MELHOR_ENVIO.md`

**Pendências futuras:**
- OAuth2 por lojista, com token individual por loja
- Compra de etiqueta
- Geração e impressão de etiqueta
- Rastreamento
- Persistência detalhada das opções selecionadas em `order_shippings`
- Split de frete e conciliação financeira por loja

**Tabela planejada (ainda não criada):**
```
order_shippings
  - id, order_id, expositor_id, carrier, service_name
  - price, estimated_days, tracking_code
  - status (pending/shipped/delivered)
  - created_at, updated_at
```

---

### Módulo 4.2 — Checkout — ✅ Concluído (versão manual)

**Objetivo:** Fluxo de compra mais simples possível para o público 40+.

**O que foi entregue:**
- Checkout em página única `/checkout`: dados do cliente (nome, WhatsApp, e-mail opcional) + endereço completo
- Sem exigência de login — aceita convidado (pré-preenche nome/e-mail se já estiver autenticado)
- Resumo do pedido agrupado por loja, com total geral
- Mensagem de frete manual configurável pelo admin
- Ao confirmar, gera `Order` + `OrderItem` + `OrderSplit` (um por loja) e limpa o carrinho
- Página de confirmação `/pedido/{reference}` com referência pública (não sequencial), endereço de entrega e
  instruções de pagamento por loja (PIX/banco + link direto para o WhatsApp do lojista)

**Adiado para quando o volume de pedidos justificar:**
- Login simplificado por link mágico via WhatsApp ou Google (4.2.1 original)
- Preenchimento automático de endereço via ViaCEP
- Pagamento via PIX com QR Code gerado automaticamente e verificação por polling — hoje o cliente paga direto na
  chave PIX do lojista, exibida na página de confirmação

---

### Módulo 4.3 — Mercado Pago — ✅ Integração inicial concluída; split automático pós-MVP

**Objetivo:** Cobrar o cliente uma vez e distribuir automaticamente para cada lojista, retendo a comissão da plataforma.

**Status atual:** o checkout já permite iniciar pagamento via Mercado Pago quando a integração está ativa nas configurações. O split automático entre lojistas permanece pós-MVP; a plataforma ainda registra os valores por loja para controle operacional. O que já existe:
- Campo de comissão da plataforma (%) configurável em `/admin/settings/checkout`
- `OrderSplit` calcula e registra `gross_amount`, `commission_amount` e `net_amount` por loja para fins de relatório,
  mesmo sem retenção real
- Campos de credenciais do Mercado Pago (Public Key, Access Token, sandbox) já salvos na mesma tela de configuração
- Confirmação manual/operacional do recebimento pelo lojista em `/minha-loja/pedidos`
- Ajuste para testes locais: URLs de retorno automático são tratadas com segurança quando `APP_URL` está em `localhost`

**Entregas futuras:**

**4.3.1 — Configuração da Conta Mercado Pago**
- Conta principal: plataforma Feira Esquerda Livre (recebe tudo e redistribui)
- Cada lojista vincula sua conta Mercado Pago via OAuth no painel da loja
- Ativar o modo `mercado_pago_ativo` nas configurações já existentes

**4.3.2 — Lógica do Split**
- Pagamento único feito pelo cliente para a conta da plataforma
- Após confirmação do pagamento (webhook):
  - Cálculo já implementado em `OrderSplit`: `net_amount = gross_amount − commission_amount`
  - Repasse automático via API Marketplace do Mercado Pago
  - Prazo de repasse: D+2 (conforme política do Mercado Pago)

**4.3.3 — Painel de Recebimentos do Lojista**
- Hoje: `/minha-loja/pedidos` já mostra valor bruto e status (Pendente/Confirmado) por pedido
- Futuro: adicionar valor líquido pós-comissão, repasse automático e filtro por mês

---

### Módulo 4.4 — Rastreio Personalizado de Entrega — ✅ Implementado

**Objetivo:** dar ao cliente visibilidade total sobre o ciclo de vida da entrega com uma experiência de rastreio com a identidade visual da Feira Esquerda Livre — não apenas um link para o site dos Correios ou transportadora.

**Contexto técnico:** a tabela `order_shippings` já está planejada no Módulo 4.1. Este módulo a cria efetivamente e constrói sobre ela a camada de rastreio.

**Fluxo completo:**

```
Pedido confirmado
    → Lojista informa código de rastreio em /minha-loja/pedidos/{id}
    → Sistema consulta Melhor Envio Tracking API (ou entrada manual)
    → Eventos de rastreio persistidos em order_tracking_events
    → Cliente acessa /minha-conta/pedidos/{reference}/rastreio
    → Página exibe linha do tempo personalizada e branded
    → E-mail + link WhatsApp notificam o cliente a cada mudança de status
```

**Entregas implementadas/planejadas como base técnica:**

**4.4.1 — Tabelas e Modelos**
```
order_shippings
  - id, order_id, expositor_id
  - carrier (ex.: "Correios", "Jadlog", "Sequoia")
  - service_name (ex.: "SEDEX", "PAC", "Econômico")
  - tracking_code (nullable até o envio)
  - price (decimal — frete cobrado)
  - estimated_days
  - status enum: pending | label_generated | shipped | in_transit | delivered | returned | failed
  - shipped_at, delivered_at
  - created_at, updated_at

order_tracking_events
  - id, order_shipping_id
  - status (string — código normalizado da Feira Esquerda Livre)
  - description (string — descrição amigável em português)
  - location (string nullable — "São Paulo, SP")
  - happened_at (timestamp — quando o evento ocorreu)
  - source enum: carrier_api | manual (quem criou o evento)
  - created_at
```

**4.4.2 — Painel do Lojista: informar envio**
- Em `/minha-loja/pedidos/{id}`, botão "Marcar como Enviado"
- Modal para informar: transportadora (select), código de rastreio, data do envio
- Ao confirmar: cria `OrderShipping`, muda status do pedido para `shipped`, dispara notificação ao cliente

**4.4.3 — Integração com Melhor Envio Tracking**
- Ao cadastrar o código, consulta a API de rastreio do Melhor Envio
- Normaliza os eventos recebidos para status da Feira: `Em Triagem`, `Em Trânsito`, `Saiu para Entrega`, `Entregue`, `Tentativa de Entrega`, `Devolvido`
- Job agendado (`TrackShipmentsJob`) roda 3× ao dia para atualizar pedidos em trânsito
- Se não há rastreio automático disponível, o lojista pode adicionar eventos manuais

**4.4.4 — Página de Rastreio do Cliente**
- Rota: `/minha-conta/pedidos/{reference}/rastreio`
- Rota pública alternativa: `/rastreio/{tracking_code}` (acessível sem login)
- **Linha do tempo visual (branded com `#F4E294` e `#1a472a`):**
  - Pedido Realizado ✓
  - Pagamento Confirmado ✓
  - Em Preparação ✓
  - Enviado (com data, transportadora e código clicável)
  - Em Trânsito (eventos detalhados com localização)
  - Saiu para Entrega
  - Entregue ✓
- Exibe estimativa de entrega (data calculada a partir de `shipped_at + estimated_days`)
- Botão "Rastrear no site da transportadora" (link externo para fallback)
- Responsivo: mobile-first, legível para público 40+

**4.4.5 — Notificações ao Cliente**
- E-mail automático quando o lojista informa o envio (inclui link da página de rastreio)
- E-mail quando o status muda para "Saiu para Entrega" (máximo 1 e-mail por etapa)
- Mensagem WhatsApp opcional: link pré-formatado com código e página de rastreio
- Nenhum e-mail enviado em loop — cada status notifica no máximo uma vez

**4.4.6 — Painel Admin**
- Em `/admin/pedidos/{id}`: visão do rastreio com todos os eventos
- Capacidade de adicionar evento manual (ex.: "Atraso por volume nas Festas de Final de Ano")
- Filtro de pedidos: por status de envio (pendente envio, em trânsito, entregue, com problema)

**Decisão de produto:**
- Para o MVP, o lojista informa o código manualmente; a plataforma consulta a API automaticamente
- A compra de etiqueta diretamente no sistema (Melhor Envio Label Purchase) permanece como evolução futura
- O rastreio é por loja: em pedidos com múltiplos lojistas, cada loja tem seu próprio tracking independente

---

## 👥 Fase 5 — Mini Rede Social e Marketing Digital

**Período estimado:** Semanas 14 a 17
**Objetivo estratégico:** Com o e-commerce ativo e a agenda funcionando, criar a camada de engajamento político e comunitário que diferencia a plataforma.

---

### Módulo 5.1 — Feed da Esquerda Livre

**Objetivo:** Feed de atualizações onde lojistas publicam conteúdo e a comunidade reage.

**Entregas:**

**5.1.1 — Publicações no Feed**
- Tipos de post: Foto da feira · Novo produto · Aviso · Texto livre
- Upload de até 4 fotos por publicação (com compressão automática — herança da Fase 3)
- Texto com limite de 500 caracteres (evita posts longos e pesados)
- Publicação vinculada ao perfil da loja (não ao usuário pessoa física)

**5.1.2 — Interações (Reatividade Livewire)**
- Curtida com feedback visual imediato (sem reload)
- Comentários: texto simples, sem menções ou threads (simplicidade para 40+)
- Contador de curtidas e comentários em tempo real via polling Livewire (a cada 30s)

**5.1.3 — Moderação pelo Administrador**
- Fila de reportes: qualquer usuário pode reportar uma publicação
- Admin visualiza reportes pendentes e pode ocultar publicações abusivas
- Log de moderação (quem ocultou, quando, motivo)

**Tabelas novas:**
```
feed_posts
  - id, expositor_id, type, content, images (JSON)
  - is_visible, reported_count, created_at

feed_likes
  - id, feed_post_id, user_id, created_at (unique: post+user)

feed_comments
  - id, feed_post_id, user_id, content, is_visible, created_at
```

---

### Módulo 5.2 — Central de Compartilhamento

**Objetivo:** Facilitar o marketing orgânico dos lojistas nas suas próprias redes e grupos de WhatsApp.

**Entregas:**

**5.2.1 — Gerador de Link Compartilhável**
- URL curta e limpa para cada produto: `feiraesquerdalivre.com.br/loja/atelie-maos/bolsa-macrame`
- Open Graph tags automáticas (título, descrição, imagem) para preview rico no WhatsApp e redes sociais
- Botão "Copiar Link" com feedback de confirmação (ícone ✓ por 2 segundos)

**5.2.2 — Gerador de Imagem Formatada**
- Template de card para compartilhamento (1080×1080px — formato Instagram/WhatsApp)
- Composição: foto do produto + nome + preço + logo da loja + URL
- Gerado no backend via `intervention/image` e entregue como download direto
- Botão "Gerar Imagem para Compartilhar" no painel do lojista

**5.2.3 — Botões de Compartilhamento Direto**
- WhatsApp: `https://wa.me/?text=` com texto pré-formatado do produto
- Facebook: Open Graph share
- Copiar URL: fallback universal

---

### Módulo 5.3 — Central de Email Marketing — ✅ Implementada

**Objetivo:** dar ao time de marketing e aos administradores da Feira Esquerda Livre uma ferramenta para criar, enviar e acompanhar campanhas de e-mail para a base de clientes e assinantes, dentro do próprio painel — sem depender de plataformas externas pagas para o MVP.

**Público-alvo das campanhas:**
- Assinantes da newsletter (`newsletter_subscribers`)
- Clientes do marketplace (`customer_profiles`)
- Combinação filtrada (ex.: clientes ativos que compraram nos últimos 60 dias)
- Segmento manual (lista de e-mails colados no campo de edição)

**Entregas implementadas/planejadas como base técnica:**

**5.3.1 — Gerenciamento de Campanhas (Admin)**
- Rota: `/admin/email-marketing`
- Listagem de campanhas com status, data de envio e contadores
- CRUD completo: criar, editar (somente rascunhos), duplicar, excluir (somente rascunhos)
- Visualização de relatório por campanha (enviados, abertos, cliques, descadastros)

**5.3.2 — Construtor de E-mail**
- Editor de conteúdo rico (TipTap ou equivalente leve — sem dependências pesadas)
- Campos obrigatórios: Nome interno da campanha, Assunto do e-mail, Remetente (padrão: `noreply@feiraesquerdalivre.com.br`)
- Seletor de destinatários com preview de contagem estimada
- Upload de imagem de cabeçalho (opcional)
- Variáveis de personalização suportadas: `{{nome}}`, `{{email}}`
- Link de descadastro inserido automaticamente no rodapé (exigência LGPD)
- Preview em tempo real da versão mobile e desktop
- Botão "Enviar e-mail de teste" para o endereço do usuário logado

**5.3.3 — Templates de Campanha**
Modelos pré-configurados com identidade visual da Feira:

| Template | Uso típico |
|---|---|
| Newsletter Mensal | Curadoria de novidades, feiras e produtos em destaque |
| Novo Evento | Divulgação de feira com data, local e expositores confirmados |
| Promoção de Produto | Destaque de produto(s) com preço e link direto |
| Boas-Vindas | Enviado automaticamente ao novo assinante da newsletter |
| Recuperação de Carrinho Abandonado | Para clientes com `cart_items` há mais de 24h sem checkout (futuro) |

**5.3.4 — Agendamento e Envio**
- Envio imediato: dispara o Job de envio na fila
- Agendamento: data e hora futuras (usa Laravel Scheduler + queue)
- Status de campanha: `rascunho → agendado → enviando → enviado → com_erros`
- Envio assíncrono via `SendEmailCampaignJob` em queue dedicada (`email-marketing`)
- Rate limiting configurável: máximo de e-mails por minuto (evitar blacklist de IP)
- Cada destinatário recebe uma entrada em `email_campaign_sends` antes do envio; falhas são registradas separadamente

**5.3.5 — Rastreio e Relatórios**
- Pixel de abertura (1×1 GIF transparente com UUID único por envio)
- Rastreio de cliques via redirect interno (`/mk/c/{token}` → URL de destino)
- Dashboard por campanha: taxa de abertura, taxa de clique, descadastros, bounces
- Descadastro: rota pública `/newsletter/descadastro/{token}` marca o e-mail como `unsubscribed_at` e impede reenvios

**5.3.6 — Conformidade LGPD**
- Todo e-mail enviado contém link de descadastro válido
- E-mails com `unsubscribed_at` nunca recebem campanhas futuras
- Assinantes `newsletter_subscribers` mantêm campo `consent_at` (data do opt-in)
- Para clientes do marketplace: o cadastro de conta equivale ao opt-in de comunicações transacionais; campanhas promocionais exigem consentimento explícito (campo `marketing_opt_in` na `customer_profiles`)
- Exportação de dados do assinante disponível para exercício do direito de acesso LGPD

**Tabelas novas:**
```
email_campaigns
  - id, name (interno), subject, from_name, from_email
  - body_html, body_text (gerado automaticamente do HTML)
  - recipient_type enum: all_subscribers | customers | customers_active | segment_manual
  - recipient_emails_manual (JSON nullable — para segmento manual)
  - template_key (string nullable — chave do template usado)
  - status enum: draft | scheduled | sending | sent | failed
  - scheduled_at (timestamp nullable)
  - sent_at (timestamp nullable)
  - recipients_count, sent_count, failed_count
  - created_by (FK users), created_at, updated_at

email_campaign_sends
  - id, campaign_id, email, name (nullable)
  - sent_at (timestamp nullable)
  - opened_at (timestamp nullable)
  - clicked_at (timestamp nullable)
  - unsubscribed_at (timestamp nullable)
  - bounce_type (nullable — soft | hard)
  - tracking_pixel_token (UUID único)
  - created_at
```

**Alteração em tabela existente:**
```
customer_profiles (alter)
  + marketing_opt_in (boolean, default: true)
  + marketing_opt_in_at (timestamp nullable)
```

**Configurações em `site_settings`:**
- `mail_from_name`, `mail_from_email` (já existentes)
- `marketing_rate_limit_per_minute` (novo — padrão: 60)
- `marketing_unsubscribe_secret` (novo — HMAC secret para tokens de descadastro)

**Decisão de produto para o MVP:**
- Envio via SMTP configurado no painel (`/admin/settings/mail`) — sem dependência de API de terceiros
- Rastreio de abertura e clique ativo por padrão; pode ser desativado por campanha
- Integração futura com SendGrid ou Amazon SES para melhorar deliverability em listas grandes

---

### Módulo 5.4 — Gestão de Visibilidade e Tempo de Exposição de Expositores — ✅ Implementada

**Objetivo:** tornar a exposição dos expositores na home page justa, transparente e monetizável. Hoje, quem é aprovado por último aparece primeiro — o novo sistema introduz rotação aleatória com rastreio de tempo de exibição, cotas configuráveis por expositor e um painel de relatórios para que cada lojista veja exatamente quanto tempo sua loja ficou visível para o público.

**Problema atual:** a seção "Nossos Expositores" da home exibe os expositores em ordem de cadastro (mais recente primeiro), o que penaliza quem foi aprovado mais cedo e favorece indevidamente os recém-chegados. Não há registro de quantas vezes ou por quanto tempo cada expositor foi exibido.

**Princípio de produto:** o marketplace vende *tempo de exposição* como diferencial comercial — expositores que contratam um plano de destaque têm garantia de presença mínima na vitrine; os demais participam de um pool de rotação aleatória democrática.

---

**5.4.1 — Modelo de dados**

Tabela nova `expositor_visibility_slots`:
```
id
expositor_id          FK → expositores
slot_type             enum: home_featured | home_rotation
priority              smallint (0 = rotação democrática; 1–100 = destaque pago)
active_from           timestamp nullable (início da janela contratada)
active_until          timestamp nullable (fim da janela contratada; null = indefinido)
created_by            FK → users
created_at / updated_at
```

Tabela nova `expositor_impressions`:
```
id
expositor_id          FK → expositores
rendered_at           timestamp (quando a home foi renderizada com este expositor)
session_hash          varchar(64) — hash anônimo da sessão do visitante (SHA-256 do session_id)
source                enum: home_featured | home_rotation
created_at
```
> `session_hash` garante rastreio sem armazenar dados pessoais (conformidade LGPD).

Alteração na tabela `expositores`:
```
+ show_on_home         boolean default false  (já existe — sem mudança)
+ home_rotation_weight smallint default 1     (peso na roleta: 1 = normal, >1 = destaque)
+ total_impressions    unsignedInteger default 0  (contador desnormalizado para leitura rápida)
```

---

**5.4.2 — Lógica de rotação na home**

- A seção "Nossos Expositores" exibe até **N expositores** por renderização (N configurável em `site_settings.home_expositores_count`, padrão: 8).
- A seleção obedece à seguinte hierarquia:
  1. **Destaques pagos ativos** (`slot_type = home_featured`, `priority > 0`, dentro da janela `active_from/until`) — sempre exibidos primeiro, até o limite configurado para destaques.
  2. **Pool de rotação democrática** — expositores com `show_on_home = true` e sem slot de destaque ativo são sorteados aleatoriamente, com peso proporcional a `home_rotation_weight`.
- A cada renderização da home que inclua expositores, registra-se uma linha em `expositor_impressions` por expositor exibido (disparado via `ExpositorImpressionJob` em fila para não impactar o tempo de resposta).
- **Cache da seleção:** a lista sorteada é cacheada por **5 minutos** (`expositor_home_selection`) para evitar sorteio a cada pageview, mas garantir rotatividade ao longo do dia.

---

**5.4.3 — Painel admin — Gestão de Visibilidade**

Rota: `/admin/expositores/visibilidade`  
Permissão: `expositores.visibilidade`

- Listagem de todos os expositores com `show_on_home = true`, exibindo:
  - Status atual (em destaque / em rotação / fora da home)
  - Total de impressões (acumulado e últimos 30 dias)
  - Janela de destaque contratada (se houver)
  - Peso de rotação atual
- Ações disponíveis:
  - **Ativar/desativar destaque pago:** define `slot_type = home_featured`, `priority`, `active_from`, `active_until`
  - **Ajustar peso de rotação:** edita `home_rotation_weight` (1–10)
  - **Remover da home:** toggle `show_on_home = false`
- Gráfico simples de impressões por dia (últimos 30 dias) usando dados de `expositor_impressions`

---

**5.4.4 — Painel do lojista — Relatório de Exposição**

Rota: `/minha-loja/exposicao`  
Visível apenas para lojistas com `show_on_home = true`

- Card de resumo:
  - Total de impressões na home (todos os tempos)
  - Impressões nos últimos 7 e 30 dias
  - Posição atual: "Em destaque" (com data de fim) ou "Em rotação democrática"
- Gráfico de barras: impressões por dia (últimos 30 dias)
- Tabela: top-10 dias com mais impressões
- Nota informativa: *"Sua loja participou da vitrine da Feira em X dias no último mês"*

---

**5.4.5 — Aprovação de expositor: integração ao fluxo existente**

Ao aprovar uma solicitação de lojista em `/admin/lojistas/solicitacoes`:
- Se `show_on_home` for marcado como `true` durante a aprovação, o sistema cria automaticamente um slot `home_rotation` com `priority = 0` e `home_rotation_weight = 1`.
- Um aviso contextual informa ao admin: *"Este expositor entrará no pool de rotação democrática. Para garantir visibilidade prioritária, configure um slot de destaque."*

---

**5.4.6 — Configurações em `site_settings`**

| Chave | Tipo | Padrão | Descrição |
|---|---|---|---|
| `home_expositores_count` | integer | 8 | Quantos expositores exibir por renderização |
| `home_featured_max` | integer | 2 | Máximo de slots de destaque pago simultâneos |
| `home_cache_ttl_minutes` | integer | 5 | TTL do cache de seleção da home |

---

**5.4.7 — Job de impressão assíncrono**

`ExpositorImpressionJob`:
- Recebe array de `expositor_id` exibidos e `session_hash`
- Insere registros em `expositor_impressions` em bulk
- Incrementa `expositores.total_impressions` para cada ID via `increment()`
- Roda na fila padrão com `tries = 3`

---

**Decisões de produto para o MVP:**

- Impressões são registradas por renderização de página, não por tempo de permanência (sem JavaScript de rastreio de scroll — mantém a filosofia de zero JS pesado).
- O `session_hash` é derivado do `session()->getId()` hasheado com SHA-256 — nunca armazena IP ou dados pessoais.
- Expositores não podem ver o `session_hash` nem dados de outros expositores — apenas os próprios relatórios agregados.
- A cobrança pelo slot de destaque é externa ao sistema (fatura manual / contrato) — o painel apenas registra as datas de vigência informadas pelo admin.
- Integração futura: webhook para ativar/desativar slots automaticamente mediante confirmação de pagamento via Mercado Pago.

---

## ✅ Fase 6 — Governança Administrativa, Usuários Internos e Permissões

**Objetivo estratégico:** profissionalizar a operação interna da plataforma, permitindo que a Feira Esquerda Livre tenha administradores, gerentes, supervisores e editores com acessos controlados, auditáveis e coerentes com suas responsabilidades.

**Status:** implementada em julho de 2026.

**Entregas realizadas:**
- Pacote `spatie/laravel-permission` instalado e configurado
- Roles iniciais criadas para administrador, gerente, supervisor, editor, lojista e cliente
- Permissões base versionadas em seeder idempotente
- Área `/admin/usuarios` para gestão de usuários internos
- Área `/admin/perfis-acesso` para configuração de permissões por perfil
- Área `/admin/clientes` com listagem de compradores, filtros e ações de inativação
- Tabela `customer_profiles` separando status no marketplace do status global do usuário
- Suporte a usuários com duplo papel (ex: administrador que também é cliente)
- Inativação de cliente no marketplace preserva acesso administrativo do usuário
- Permissões `clientes.visualizar` (leitura) e `clientes.gerenciar` (ações) implementadas
- Menu administrativo renderizado conforme permissões
- Rotas administrativas protegidas com middleware `can:*`
- Componentes Livewire administrativos protegendo ações críticas no backend
- Testes automatizados para acesso por perfil, URL direta e ações Livewire bloqueadas
- **Modelo multi-papel:** um único cadastro por e-mail pode acumular papel interno e perfil de cliente simultaneamente
- **Fluxo de promoção:** ao cadastrar usuário interno com e-mail de cliente existente, o painel oferece modal para conceder acesso interno preservando CustomerProfile, pedidos e endereços
- **Badge híbrido:** `UsuarioIndex` exibe badge "Cliente" para usuários internos que também são clientes do marketplace
- **Toggle de cliente no modo edição:** `UsuarioForm` permite adicionar ou remover o perfil de cliente de um usuário interno existente

**Pendências futuras fora da Fase 6:** auditoria detalhada de alterações administrativas, logs de alteração de permissões e possível campo auxiliar de contexto de usuário para relatórios.

**Princípio de arquitetura — modelo de papel único com perfil complementar:**
- `users.role` armazena **um único papel** que define acesso ao painel (admin, gerente, supervisor, editor, lojista, user)
- `CustomerProfile` é um registro complementar que declara "este usuário também é cliente do marketplace", independente do papel
- Um usuário com `role = gerente` e `CustomerProfile` é simultaneamente equipe interna e comprador
- `users.isInternal()` verifica se `role` pertence ao conjunto {admin, gerente, supervisor, editor}
- `AdminMiddleware` usa `isInternalUser()` para autorizar acesso ao painel — CustomerProfile não interfere
- **Os três universos de usuário:**
  - **Cliente puro:** `role = user`, com `CustomerProfile` criado automaticamente no registro
  - **Lojista:** `role = lojista`, acessa `/minha-loja`; pode ter `CustomerProfile` se também comprador
  - **Equipe interna:** `role ∈ {admin, gerente, supervisor, editor}`, acessa `/admin`; pode ter `CustomerProfile`

### Módulo 6.1 — Gestão de Usuários Internos

**Objetivo:** criar no painel administrativo uma área dedicada para cadastro e manutenção de usuários internos.

**Entregas planejadas:**
- Nova área `/admin/usuarios`
- Listagem de usuários internos com busca por nome, e-mail, papel e status
- Cadastro de usuário interno pelo administrador
- Edição de nome, e-mail, WhatsApp, papel/perfil e status
- Ativar/desativar acesso sem apagar histórico
- Redefinição de senha pelo administrador
- Envio de e-mail com credenciais temporárias quando aplicável
- Bloqueio para impedir que usuários sem permissão gerenciem outros usuários

**Papéis iniciais:**
- `administrador` — acesso total ao painel e configurações
- `gerente` — gestão operacional ampla, sem permissões sensíveis de sistema por padrão
- `supervisor` — acompanhamento e execução de rotinas específicas
- `editor` — conteúdo, CMS, posts, páginas e mídia

**Fora deste módulo:** clientes e lojistas não devem ser misturados com a gestão de equipe interna, mesmo estando tecnicamente na tabela `users`.

---

### Módulo 6.2 — Perfis de Acesso e Permissões

**Objetivo:** criar uma camada de autorização por perfil, evitando regras rígidas espalhadas pelo código.

**Permissões base sugeridas:**
- `cms.visualizar`
- `cms.editar`
- `lojistas.visualizar`
- `lojistas.aprovar`
- `produtos.visualizar`
- `produtos.moderar`
- `pedidos.visualizar`
- `pedidos.atualizar_status`
- `configuracoes.visualizar`
- `configuracoes.editar`
- `usuarios.visualizar`
- `usuarios.gerenciar`
- `permissoes.gerenciar`
- `feed.moderar`
- `relatorios.visualizar`

**Regras planejadas:**
- Administrador recebe todas as permissões.
- Gerente recebe permissões operacionais amplas, exceto gerenciamento de permissões sensíveis por padrão.
- Supervisor recebe permissões limitadas por módulo.
- Editor recebe permissões focadas em CMS, mídia e conteúdo.
- Permissões devem ser versionadas em seeders para garantir reprodutibilidade entre ambientes.

---

### Módulo 6.3 — Aplicação de Permissões em Menus, Rotas e Ações

**Objetivo:** garantir que a autorização não seja apenas visual. Esconder itens de menu melhora a experiência, mas a segurança deve estar nas rotas, componentes e ações.

**Entregas planejadas:**
- Menu administrativo renderizado conforme permissões do usuário
- Middleware/policies/gates protegendo rotas administrativas
- Proteção em componentes Livewire administrativos
- Bloqueio de ações críticas, como aprovar lojista, alterar pedido, editar configurações e gerenciar usuários
- Respostas claras para acesso negado
- Testes automatizados para permissões de rota e ações críticas

**Critério de aceite:** acessar uma URL administrativa diretamente sem permissão deve retornar bloqueio, mesmo que o link não apareça no menu.

---

### Módulo 6.4 — Base Técnica com `spatie/laravel-permission`

**Objetivo:** adotar uma solução madura para roles e permissions em Laravel, evitando criar uma camada própria desnecessária.

**Decisão técnica recomendada:**
- Usar o pacote `spatie/laravel-permission`
- Mapear os papéis internos para roles do pacote
- Mapear permissões de módulo/ação para permissions do pacote
- Migrar gradualmente o uso atual de `UserRole` para a nova estrutura, mantendo compatibilidade temporária com `admin`, `editor`, `lojista` e `user`

**Cuidados de implantação:**
- Avaliar impacto nas migrations existentes de `users.role`
- Criar seeders idempotentes para roles e permissões
- Evitar quebrar o login de lojistas e clientes
- Garantir que o primeiro administrador sempre tenha acesso total

---

### Módulo 6.5 — Separação de Contextos de Usuário

**Objetivo:** tornar explícito no código e na interface que existem usuários com naturezas diferentes, ainda que todos autentiquem pelo mesmo mecanismo.

**Diretrizes:**
- Cliente acessa `/minha-conta`
- Lojista acessa `/minha-loja`
- Equipe interna acessa `/admin`
- Usuários internos não precisam ter loja
- Lojistas não devem herdar permissões administrativas
- Clientes não devem aparecer como equipe interna
- Telas administrativas devem filtrar e exibir usuários pelo contexto correto

**Decisão técnica adotada:** separação feita via tabela `customer_profiles` com campo `marketplace_status`, preservando `users.is_active` para controle de acesso global. Um usuário pode ter qualquer papel e ainda possuir um perfil de cliente no marketplace. O perfil é criado automaticamente para novos usuários com papel `user` e para qualquer usuário autenticado ao finalizar o primeiro pedido.

---

## ✅ Fase 7 — Comunicação entre Loja e Cliente (Concluída)

**Período:** Julho de 2026
**Objetivo estratégico:** criar os três canais de comunicação que transformam a plataforma de vitrine em marketplace com relacionamento real: conteúdo educativo no produto (FAQ), diálogo público pré-venda (Q&A) e conversa privada pós-pedido (Chat).

### O que foi entregue

| Componente | Status |
|---|---|
| Tabela `product_faqs` e Model `ProductFaq` | ✅ Concluído |
| FAQ estático editável pelo lojista no formulário de produto | ✅ Concluído |
| Accordion público de FAQ na página do produto | ✅ Concluído |
| Tabela `product_questions` e Model `ProductQuestion` | ✅ Concluído |
| Componente `ProductQandA` — envio público de perguntas e exibição de respondidas | ✅ Concluído |
| Painel `PerguntaIndex` no lojista — resposta inline, edição, toggle de visibilidade | ✅ Concluído |
| Badge de perguntas pendentes na sidebar do lojista | ✅ Concluído |
| Tabela `order_messages` e Model `OrderMessage` | ✅ Concluído |
| Componente `OrderChat` (compartilhado) — chat por split com polling 5s | ✅ Concluído |
| Chat embutido na página do pedido do cliente (por split) | ✅ Concluído |
| Página dedicada `/minha-loja/pedidos/{split}/chat` para o lojista | ✅ Concluído |
| Badge de mensagens não lidas na sidebar do lojista | ✅ Concluído |
| Botão "Chat" por split na listagem de pedidos do lojista | ✅ Concluído |
| 31 testes automatizados cobrindo os três módulos | ✅ Concluído |

---

### Módulo 7.1 — FAQ Estático por Produto

**Objetivo:** o lojista cadastra perguntas e respostas fixas sobre um produto no próprio formulário de criação/edição, e o visitante as consulta na página pública do produto.

**Entregas:**

**7.1.1 — Cadastro pelo lojista**
- Seção "Perguntas Frequentes" no `ProdutoForm`, após a galeria de fotos
- Até 15 pares pergunta/resposta por produto
- Botão "+ Adicionar pergunta" e remoção individual
- Binding aninhado Livewire: `wire:model="faqs.{{ $i }}.question"` / `faqs.{{ $i }}.answer`
- Salvo em `product_faqs` (cascata no delete do produto)

**7.1.2 — Exibição pública**
- Accordion Alpine.js na página `/loja/{slug}/{produto-slug}`, acima da seção Q&A
- Abre/fecha por clique — somente uma pergunta aberta por vez
- Não renderizado se não houver FAQs cadastradas

**Tabela nova:**
```
product_faqs
  - id, product_id FK, question, answer, sort_order, timestamps
```

---

### Módulo 7.2 — Perguntas Públicas por Produto (Q&A)

**Objetivo:** canal aberto onde qualquer usuário cadastrado pode enviar perguntas ao lojista; as respondidas ficam visíveis para todos; as não respondidas ficam visíveis apenas para quem perguntou.

**Entregas:**

**7.2.1 — Componente público `ProductQandA`**
- Usuário logado: textarea de envio com validação (min 5 / max 500 chars)
- Visitante: botão "Faça login para perguntar"
- Perguntas respondidas: accordion público com nome do comprador (só primeiro nome) e data relativa
- Perguntas pendentes do próprio usuário: badge "aguardando resposta" visível só para ele
- Perguntas ocultas (`is_visible = false`) nunca aparecem para outros visitantes

**7.2.2 — Painel do lojista `PerguntaIndex`**
- Filtros: Aguardando / Respondidas / Todas — com badge de contagem
- Resposta inline por textarea, sem página separada; edição de resposta publicada
- Toggle "Ocultar / Tornar visível" para moderação
- Autorização por `expositor_id` — lojista só vê produtos da própria loja

**Tabela nova:**
```
product_questions
  - id, product_id FK, user_id FK, question, answer (nullable)
  - answered_at (nullable), answered_by FK users (nullable)
  - is_visible (boolean, default true)
  - timestamps
```

---

### Módulo 7.3 — Chat Pós-Pedido

**Objetivo:** canal de mensagens privado entre o cliente e o lojista, organizado por split de pedido — cada loja tem sua conversa separada dentro do mesmo pedido.

**Entregas:**

**7.3.1 — Componente compartilhado `OrderChat`**
- Balões de conversa: amarelo-âmbar (mensagens do usuário logado, à direita) e cinza (outra parte, à esquerda)
- `wire:poll.5s` — atualiza sem WebSocket nem JavaScript externo
- Auto-scroll ao fundo via Alpine.js (no mount e a cada mensagem enviada)
- Enter para enviar, Shift+Enter para nova linha
- Marca mensagens da outra parte como lidas em cada `render()` (aproveitando o ciclo do poll)
- Autorização interna: aborta 403 se o usuário não for o cliente dono do pedido nem o lojista dono do split

**7.3.2 — Área do cliente**
- Chat embutido abaixo dos dados de cada split na página `/pedido/{reference}`
- Um componente `OrderChat` isolado por split (`:key="'chat-'.$split->id"`)
- Visível apenas para o usuário autenticado dono do pedido

**7.3.3 — Painel do lojista**
- Página dedicada `/minha-loja/pedidos/{split}/chat` (`lojista.pedidos.chat`)
- Layout em duas colunas: resumo do pedido (referência, cliente, data, situação, valor, itens) + chat
- Botão "Chat" por split na listagem `PedidoIndex`
- Badge vermelho na sidebar com total de mensagens não lidas de todos os splits do expositor

**Tabela nova:**
```
order_messages
  - id, order_split_id FK, sender_id FK users
  - body (text), read_at (nullable)
  - created_at (imutável — sem updated_at)
  - índice composto (order_split_id, created_at)
  - índice composto (order_split_id, sender_id, read_at)
```

---

## 📊 Resumo do Roadmap

| Fase | Período | Módulos | Entregável Principal |
|---|---|---|---|
| ✅ Fase 1 — Fundação | Semanas 1–3 | CMS · Admin · Home | Plataforma funcional com conteúdo dinâmico |
| ✅ Fase 2 — Lojistas & Agenda | Semanas 4–6 | 2.1 · 2.2 · 2.3 | Lojistas cadastráveis · Agenda pública navegável |
| ✅ Fase 3 — Catálogo & Três Eixos | Semanas 7–9 | 3.1 · 3.2 · 3.3 | Três eixos, CRUD de produtos, loja pública e carrinho multilojas em produção |
| ✅ Fase 4 — Checkout, Frete & Pagamento | Semanas 10–13 | 4.1 · 4.2 · 4.3 · 4.4 | Checkout, pedidos, cotação Melhor Envio, Mercado Pago inicial e rastreio |
| ✅ Fase 5 — Comunidade & Marketing | Semanas 14–17 | 5.1 · 5.2 · 5.3 · 5.4 | Feed social, compartilhamento, email marketing e gestão de visibilidade de expositores |
| ✅ Fase 6 — Governança Admin | Concluída | 6.1 · 6.2 · 6.3 · 6.4 · 6.5 | Usuários internos, perfis de acesso, permissões, modelo multi-papel e proteção real do painel |
| ✅ **Fase 7 — Comunicação** | Julho 2026 | **7.1 · 7.2 · 7.3** | **FAQ estático, Q&A público e chat pós-pedido por split — 31 testes** |
| ✅ Fase 8 — AVA | Julho 2026 | 8.1 · 8.2 · 8.3 | Cursos digitais, course builder, player, materiais protegidos, progresso e certificado PDF |
| ✅ Fase 9 — API Mobile (Flutter) | Agosto 2026 | 9.1 · 9.2 · 9.3 · 9.4 · 9.5 | API `/api/v1` com Sanctum, catálogo, carrinho/checkout/pedidos/chat/endereços, AVA, lojista e comunidade (feed) — 52 testes |
| ✅ **Fase 10 — Inteligência de Cliente** | **Agosto 2026 (Sprint 3)** | **10.1 · 10.2 · 10.3** | **Dashboard admin, rastreamento de eventos (produtos/carrinho/pedidos) e testes E2E — 236 testes** |

### Trilhas paralelas

| Trilha | Período | Situação | Entregável Principal |
|---|---|---|---|
| ✅ **Infraestrutura — Ambiente Docker** | Agosto 2026 | Concluída | 8 serviços (PHP 8.3, Nginx, MySQL 8.4, phpMyAdmin, Redis 7, Node 22/Vite, queue, Mailpit); dispensa Laragon, XAMPP, PHP, MySQL, Composer e Node no Windows — ver `docs/DOCKER_DEVELOPMENT.md` |
| 🔄 **Trilha CI — Customer Intelligence interno** | Agosto 2026 — | 5 de 9 fases | Transformar o Customer Intelligence em módulo nativo, sem dependência de `../jmf-ci-sdk` — ver `docs/CUSTOMER_INTELLIGENCE_INTERNAL.md` |

---

## ✅ Fase 10 — Inteligência de Cliente (JMF CI) (Concluída)

> **Continuação:** a arquitetura desta fase — SDK externo com envio por HTTP — está sendo
> substituída por um módulo nativo do projeto. As funcionalidades permanecem; muda a origem
> dos dados. Acompanhamento na [Trilha CI](#-trilha-ci--internalização-do-customer-intelligence-em-andamento).

**Período:** Sprint 3 — Agosto 2026
**Objetivo estratégico:** integrar o JMF Customer Intelligence SDK para rastreamento comportamental, análise de visitantes e métricas de conversão — permitindo que a Feira Esquerda Livre entenda melhor seus clientes e otimize fluxos de compra.

### O que será entregue

| Componente | Status |
|---|---|
| SDK JMF Customer Intelligence v1.0.0 instalado | ✅ Concluído |
| Variáveis de ambiente configuradas (.env) | ✅ Concluído |
| Componentes Livewire disponíveis (5 componentes) | ✅ Concluído |
| Dashboard admin em `/admin/customer-intelligence` | ✅ Concluído |
| Rastreamento automático de eventos (produtos, carrinho, pedidos) | ✅ Concluído |
| Testes E2E ponta-a-ponta | ✅ Concluído |

**Correções aplicadas no SDK durante a integração (Sprint 3):**
- Componentes Blade do SDK (`x-jmf-ci-*`) precisaram ser copiados como arquivos "flat" em `resources/views/components/` — a convenção do Laravel para componentes com hífen no nome não é compatível com a estrutura de pastas original do pacote.
- `jmf-ci-event-table.blade.php` não declarava `@props`, o que quebrava quando usado sem `paginator`; corrigido com defaults explícitos para todos os atributos.
- `ContactIndex`, `ContactShow` e `EventIndex` usavam `$this->currentPage`, propriedade inexistente do trait `WithPagination` do Livewire — corrigido para `$this->getPage()`.
- `Configuration`, `Dashboard`, `ContactIndex`, `ContactShow` e `EventIndex` guardavam `JmfCiApiClient` como propriedade de instância setada só no `mount()` — como o Livewire não hidrata propriedades não-públicas entre requisições, qualquer ação subsequente (clique, paginação, filtro) lançava `must not be accessed before initialization`. Corrigido resolvendo o client via `app(JmfCiApiClient::class)` em cada uso.
- **Bug crítico:** `SendPayloadJob` enviava eventos para `{base_url}/events` e `{base_url}/contacts/identify`, sem o prefixo `api/v1/` usado por todas as demais rotas da API (inclusive `healthCheck()`, que usa `api/v1/ping` corretamente). Isso fazia todo evento de rastreamento (`produto.visualizado`, `pedido.criado`, etc.) falhar silenciosamente com `404 Endpoint não encontrado` — silencioso porque a chamada roda dentro de um Job com try/catch e apenas loga o erro (`storage/logs/laravel.log`), sem quebrar a navegação do usuário. Como "Validar Conexão" usa `healthCheck()` (rota diferente, já correta), o teste de conexão passava normalmente mesmo com o rastreamento de eventos 100% quebrado — por isso o problema só foi percebido ao navegar o site e checar os logs, não pela tela de configuração. Corrigido prefixando `api/v1/` apenas na URL da requisição HTTP dentro do `SendPayloadJob::handle()`, mantendo o valor original do endpoint (`events` / `contacts/identify`) intacto para `PayloadValidator`, que faz `match()` exato sobre esse valor.
- Essas correções foram feitas no repositório fonte do SDK (`jmf-ci-sdk`) e re-espelhadas para o `vendor/` do projeto consumidor. Validado com requisição HTTP real (log confirmou `status: 202`, "payload enviado com sucesso") e suíte completa (224 testes, sem regressões).

**Descoberta adicional — endpoints de leitura ausentes no servidor:** mesmo com o envio de eventos 100% funcional (confirmado no Analytics nativo da própria plataforma JMF CI: 15 eventos, 6 visitantes únicos), o dashboard embutido no admin da Feira continuava vazio. Causa: o backend (`D:\PROJETO-JMF-CUSTOMER-INTELLIGENCE`, projeto separado) só expunha endpoints de **escrita** (`POST /api/v1/events`, `POST /api/v1/contacts/identify`) e o health-check (`GET /api/v1/ping`) — os componentes Livewire do SDK (`Dashboard`, `EventIndex`, `ContactIndex`, `ContactShow`) dependem de endpoints de **leitura** (`GET /api/v1/metrics`, `/events`, `/contacts`, `/contacts/{id}`, `/contacts/{id}/events`) que ainda não existiam. Implementados no backend, reaproveitando as mesmas Actions (`GetDashboardOverviewAction`) já usadas pelo painel `/admin/analytics` nativo da plataforma, com isolamento por tenant/application e 17 novos testes automatizados (138 no total, sem regressões). Essa alteração vive no repositório do backend, fora do escopo de deploy da Feira Esquerda Livre — o deploy é feito separadamente pelo responsável pela VPS.

---

### Módulo 10.1 — Dashboard Admin

**Objetivo:** painel visual com métricas de inteligência de cliente integrado ao admin da Feira.

**Entregas planejadas:**

**10.1.1 — Página de Dashboard**
- Rota: `/admin/customer-intelligence`
- Middleware: `auth`, `admin`
- Componente Livewire: `<livewire:jmf-ci-dashboard />`
- Componente de configuração: `<livewire:jmf-ci-configuration />`
- Exibe:
  - Resumo de métricas (visitas, conversões, eventos rastreados)
  - Gráficos de comportamento do visitante
  - Validação de conexão com API JMF CI (base_url + token)
  - Status de rastreamento

**10.1.2 — Menu e Permissões**
- Link no menu do admin: "Inteligência de Cliente"
- Permissão: `customer_intelligence.visualizar`
- Acessível apenas para admin ou usuários com permissão explícita

**10.1.3 — Configuração da Integração**
- Exibir valores atuais de `JMF_CI_BASE_URL`, `JMF_CI_TOKEN` (masked), `JMF_CI_TIMEOUT`
- Link para documentação de configuração
- Botão para validar conexão em tempo real

---

### Módulo 10.2 — Rastreamento de Eventos — ✅ Concluído

**Objetivo:** capturar automaticamente eventos de produtos, carrinho e pedidos, enviando-os para a plataforma JMF CI.

**Decisão de implementação — proteção contra falhas do SDK:** com `JMF_CI_QUEUE_CONNECTION=sync` (usado em dev), o SDK executa a chamada HTTP de forma síncrona dentro do próprio job despachado — se a API JMF CI estiver indisponível, a exceção de retry se propagaria para o código que chamou `track()`. Todas as chamadas de rastreamento nesta fase foram envolvidas em `try/catch` com `report($exception)`, seguindo o mesmo padrão já usado no projeto para envio de e-mails — uma falha de analytics nunca deve quebrar um fluxo de compra.

**Entregas concluídas:**

| Evento | Localização | Status |
|---|---|---|
| `produto.visualizado` | `routes/web.php` — rota `GET /loja/{slug}/{productSlug}` | ✅ |
| `produto.adicionado_carrinho` | `app/Services/CartService.php::add()` | ✅ |
| `produto.removido_carrinho` | `app/Services/CartService.php::remove()` | ✅ |
| `carrinho.checkout_iniciado` | `app/Livewire/Checkout.php::confirmar()` | ✅ |
| `pedido.criado` | `app/Services/OrderService.php::createFromCart()` | ✅ |
| `pedido.pagamento_confirmado` | `app/Listeners/TrackOrderSplitConfirmedEvent.php` (ouve `OrderSplitConfirmed`) | ✅ |
| `pedido.enviado` | `app/Livewire/Lojista/Pedidos/PedidoIndex.php::markAsShipped()` | ✅ |

**Arquitetura de desacoplamento:** seguindo o mesmo padrão já usado para AVA (`OrderSplitConfirmed` → `HandleAvaEnrollmentOnSplitConfirmed`), o rastreamento de pagamento confirmado foi implementado como um segundo listener (`TrackOrderSplitConfirmedEvent`) registrado no mesmo evento, em vez de acoplar a chamada ao SDK diretamente no model `OrderSplit`.

**Validação:** suíte completa de testes (224 testes) executada após a instrumentação — nenhuma regressão introduzida nos fluxos de carrinho, checkout, criação de pedido, confirmação de split e rastreio de envio.

**Entregas planejadas (histórico do planejamento original):**

**10.2.1 — Rastreamento de Produtos**

Evento: `produto.visualizado`
```php
// ProductController::show() ou ProdutoLoja::mount()
CustomerIntelligence::track('produto.visualizado', [
    'produto_id' => $produto->id,
    'nome' => $produto->nome,
    'preco' => $produto->preco,
    'eixo' => $produto->item_type,
    'expositor_id' => $produto->expositor_id,
]);
```

Evento: `produto.adicionado_carrinho`
```php
// CartService::addItem() ou CartDrawer component
CustomerIntelligence::track('produto.adicionado_carrinho', [
    'produto_id' => $product->id,
    'quantidade' => $quantity,
    'preco_unitario' => $product->preco,
]);
```

Evento: `produto.removido_carrinho`
```php
CustomerIntelligence::track('produto.removido_carrinho', [
    'produto_id' => $cartItem->product_id,
    'quantidade' => $cartItem->quantity,
]);
```

**10.2.2 — Rastreamento de Carrinho**

Evento: `carrinho.visualizado`
```php
CustomerIntelligence::track('carrinho.visualizado', [
    'total_itens' => $cart->count(),
    'valor_total' => $cart->sum(),
]);
```

Evento: `carrinho.checkout_iniciado`
```php
// CheckoutController::confirmar() ou checkout form
CustomerIntelligence::track('carrinho.checkout_iniciado', [
    'total_itens' => $order->items->count(),
    'valor_total' => $order->grand_total,
    'quantidade_lojas' => $order->splits->count(),
]);
```

**10.2.3 — Rastreamento de Pedidos**

Evento: `pedido.criado`
```php
// OrderService::createFromCart()
CustomerIntelligence::track('pedido.criado', [
    'pedido_id' => $order->id,
    'referencia' => $order->reference,
    'valor_total' => $order->grand_total,
    'quantidade_itens' => $order->items->count(),
    'status_pagamento' => $order->status,
]);
```

Evento: `pedido.pagamento_confirmado`
```php
// OrderSplit::confirmar()
CustomerIntelligence::track('pedido.pagamento_confirmado', [
    'pedido_id' => $order->id,
    'split_id' => $split->id,
    'valor_recebido' => $split->gross_amount,
]);
```

Evento: `pedido.enviado`
```php
// Quando lojista marca como enviado
CustomerIntelligence::track('pedido.enviado', [
    'pedido_id' => $order->id,
    'split_id' => $split->id,
    'transportadora' => $shipping->carrier,
    'codigo_rastreio' => $shipping->tracking_code,
]);
```

**10.2.4 — Locations no Código**

| Evento | Localização | Método |
|---|---|---|
| `produto.visualizado` | `ProductController::show()` | GET `/loja/{slug}/{produto-slug}` |
| `produto.adicionado_carrinho` | `CartService::addItem()` | POST carrinho (Livewire) |
| `produto.removido_carrinho` | `CartService::removeItem()` | DELETE carrinho (Livewire) |
| `carrinho.visualizado` | `CartDrawer component mount()` | Livewire init |
| `carrinho.checkout_iniciado` | `CheckoutController::confirmar()` | POST `/checkout` |
| `pedido.criado` | `OrderService::createFromCart()` | OrderService |
| `pedido.pagamento_confirmado` | `OrderSplit::confirmar()` | Event listener |
| `pedido.enviado` | `LojistaPedidoController::marcarEnviado()` | Livewire action |

---

### Módulo 10.3 — Testes E2E — ✅ Concluído

**Objetivo:** validar fluxo ponta-a-ponta do dashboard e do rastreamento de eventos, sem depender de rede/API real durante a suíte de testes.

**Decisão de implementação — isolamento de rede nos testes:**
- Testes que verificam *quais* eventos são disparados usam `Bus::fake()` para interceptar o `SendPayloadJob` antes que ele chegue perto de HTTP — rápido e determinístico, sem tocar a API JMF CI real.
- Um teste dedicado (`test_tracking_failure_does_not_break_add_to_cart`) deliberadamente **não** usa `Bus::fake()`: com `QUEUE_CONNECTION=sync` (usado em testes, igual ao dev), o Job roda de verdade e uma falha HTTP simulada via `Http::fake(['*' => Http::response('Service Unavailable', 500)])` propaga uma exceção real através do SDK — validando de ponta a ponta que o `try/catch` em `CartService::trackEvent()` protege o carrinho mesmo quando a chamada de rede falha de verdade, não apenas em teoria.

**Entregas concluídas:**

`tests/Feature/CustomerIntelligence/DashboardTest.php` (5 testes):
- Admin acessa o dashboard
- Gerente (via permissão Spatie) acessa o dashboard
- Editor sem a permissão `customer_intelligence.visualizar` recebe 403
- Cliente (role `user`) recebe 403
- Visitante não autenticado é redirecionado para o login

`tests/Feature/CustomerIntelligence/EventTrackingTest.php` (7 testes):
- Visualizar produto dispara `produto.visualizado`
- Adicionar ao carrinho dispara `produto.adicionado_carrinho`
- Remover do carrinho dispara `produto.removido_carrinho`
- Confirmar checkout dispara `carrinho.checkout_iniciado` e `pedido.criado`
- Confirmar split dispara `pedido.pagamento_confirmado`
- Marcar como enviado dispara `pedido.enviado`
- Falha de rede na API JMF CI não quebra o fluxo de adicionar ao carrinho (teste sem `Bus::fake()`, ver acima)

**Validação final:** suíte completa do projeto — **236 testes passando** (224 pré-existentes + 12 novos), zero regressões.

---

### Infraestrutura Técnica

**Dependências:**
- `jmf-system/customer-intelligence-sdk` — v1.0.0 (já instalado)
- Laravel Queue — para envio assíncrono de eventos
- Guzzle HTTP — para chamadas à API (incluído no SDK)

**Configuração de Ambiente:**
```env
JMF_CI_BASE_URL=http://179.198.115.221
JMF_CI_TOKEN=1|HvZ339GRlMLTetXCvOTv95e1XE7yFM5xveoydDUR46c15d38
JMF_CI_QUEUE_CONNECTION=sync    # sync para dev/teste, database/redis para prod
JMF_CI_TIMEOUT=2                 # timeout de 2 segundos para requisições
```

**Fila de Processamento:**
- Modo assíncrono: eventos são enfileirados e processados em background
- Retry automático: 3 tentativas com backoff [5s, 30s, 120s]
- Falhas são logadas e não bloqueiam a requisição do usuário

---

### Decisões de Produto

- **Rastreamento passivo:** eventos são enviados assincronamente via fila, sem impacto no tempo de resposta do cliente
- **Identificação de visitante:** SDK gerencia cookies `jmf_ci_visitor_id` e `jmf_ci_session_id` automaticamente (LGPD-compliant)
- **Conformidade LGPD:** nenhum dado pessoal é enviado sem consentimento explícito; apenas IDs de produtos e valores monetários
- **Integração transparente:** rastreamento funciona sem mudanças de UX ou breaking changes

---

*Próxima revisão: após conclusão dos 3 módulos (10.1, 10.2, 10.3) — Sprint 3 completa — Agosto de 2026*

---

## 🧩 Trilha CI — Internalização do Customer Intelligence (em andamento)

**Período:** Agosto de 2026 — em curso
**Objetivo estratégico:** transformar o Customer Intelligence de integração externa em módulo nativo da Feira Esquerda Livre, preservando todas as funcionalidades já entregues na Fase 10.
**Documentação técnica:** `docs/CUSTOMER_INTELLIGENCE_INTERNAL.md`

### Por que internalizar

A Fase 10 entregou o rastreamento funcionando, mas com uma característica que virou limitação: o Customer Intelligence é um **cliente de telemetria**, não um módulo de analytics. Cada ação de negócio vira uma requisição HTTP para uma plataforma externa; nenhum dado comportamental fica no banco da Feira, e o painel administrativo só mostra números enquanto aquele servidor de terceiro estiver no ar.

A decisão arquitetural é que **comportamento gerado na Feira Esquerda Livre pertence à Feira Esquerda Livre**.

| Hoje | Depois |
|---|---|
| Ação de negócio → job síncrono → HTTP para VPS externa | Ação de negócio → evento Laravel → fila → MySQL local |
| Painel lê 5 endpoints remotos; VPS fora do ar = painel zerado | Painel lê o próprio banco; funciona offline |
| `produto_id` e `pedido_id` soltos dentro de JSON | Referência real por `entity_type` / `entity_id` |
| Exige o repositório `../jmf-ci-sdk` clonado ao lado | `git clone` + `docker compose up -d` e pronto |
| Dado comportamental compartilhado com terceiro | Feira como controladora integral (ver LGPD) |

### Acompanhamento das fases

| Fase | Escopo | Status | Concluída em |
|---|---|---|---|
| **CI-01** | Auditoria e arquitetura | ✅ Concluída | 25/08/2026 |
| **CI-02** | Fundação do módulo interno | ✅ Concluída | 25/08/2026 |
| **CI-03** | Coleta: visitante e sessão (middleware, cookies, ServiceProvider) | ✅ Concluída | 25/08/2026 |
| **CI-04** | Escrita de eventos pela fila dedicada | ✅ Concluída | 25/08/2026 |
| **CI-05** | Migração das 7 chamadas de rastreamento | ✅ Concluída | 25/08/2026 |
| CI-06 | Dashboard lendo do banco local | ⬜ Não iniciada | — |
| CI-07 | Desativação do SDK externo | ⬜ Não iniciada | — |
| CI-08 | Limpeza de Composer, Docker e `.env` | ⬜ Não iniciada | — |
| CI-09 | Retenção, LGPD e documentação final | ⬜ Não iniciada | — |

> **Nota de numeração:** a auditoria CI-01 propôs originalmente 10 fases. Como a CI-02 acabou absorvendo a persistência e os Models — que estavam previstos numa fase própria —, o roteiro foi consolidado em 9. Esta tabela é a numeração válida.

### Evolução da suíte de testes

Nenhuma fase é considerada concluída com teste vermelho. O número nunca deve cair sem justificativa escrita.

| Marco | Testes | Asserções |
|---|---|---|
| Fim da Fase 10 | 236 | — |
| Ambiente Docker validado | 238 | 624 |
| Fim da CI-02 | 273 | 744 |
| Fim da CI-03 | 290 | 798 |
| Fim da CI-04 | 302 | 819 |
| Fim da CI-05 | **304** | **837** |

---

### ✅ CI-01 — Auditoria e arquitetura (Concluída)

**Objetivo:** saber exatamente o que existe antes de mexer em qualquer coisa. Fase inteiramente de leitura — nenhum arquivo alterado.

**O que a auditoria encontrou:**

| Achado | Consequência |
|---|---|
| O SDK não tem nenhum Model nem migration | Não há dado para migrar; a persistência é construção nova. Elimina a classe de risco mais pesada de uma internalização |
| As 10 views do painel (`resources/views/plugins/jmf-ci/`) já são do projeto e idênticas às do SDK | A camada visual já estava internalizada; o SDK não faz `loadViewsFrom()` |
| Cerca de 590 das 1.465 linhas do SDK são infraestrutura de rede | Job HTTP, cliente de leitura, logger e validador de configuração remota simplesmente desaparecem |
| `identify()` e `conversion()` nunca são chamados | Os "contatos" do painel são visitantes anônimos |
| Rotas do plugin e `JmfCiPluginRouteServiceProvider` não estão registrados | Código morto |
| Superfície de acoplamento: 9 arquivos usam `JmfSystem`, com 7 chamadas de `::track()` | Escopo da CI-05 |
| Dependência Docker do repositório vizinho: exatamente 2 linhas no `compose.yaml` | Escopo da CI-08 |

**Decisões tomadas ao fim da CI-01:**

| # | Decisão | Definição |
|---|---|---|
| 1 | Corte direto ou escrita dupla na migração das chamadas | **Corte direto** — dual-write dobra o custo por requisição e mantém vivo o acoplamento que se quer eliminar |
| 2 | Importar o histórico já acumulado na VPS | **Recomeçar**, dado o volume pequeno |
| 3 | Nome dos cookies de visitante | **Manter `jmf_ci_*`** — renomear zeraria a identidade de todos os visitantes conhecidos |
| 4 | Retenção de eventos brutos | **180 dias**, com agregado diário permanente |
| 5 | `expositor_impressions` (analytics nativo já existente) | **Manter separado** nesta migração |
| 6 | Nomenclatura pública do tracking | **Manter a forma da chamada**, tornando a CI-05 uma troca mecânica |

---

### ✅ CI-02 — Fundação do módulo interno (Concluída)

**Objetivo:** criar a fundação técnica do módulo — persistência, Models e caminho de gravação — sem ligar nada e sem tocar no SDK externo.

**Entregue:**

| Componente | Status |
|---|---|
| Módulo `app/CustomerIntelligence/` (529 linhas, 8 classes) | ✅ Concluído |
| 4 migrations aditivas (`ci_visitors`, `ci_sessions`, `ci_events`, `ci_daily_metrics`) | ✅ Concluído |
| 4 Models com relacionamentos, casts e UUID | ✅ Concluído |
| `CustomerIntelligenceService::record()` gravando no banco local | ✅ Concluído |
| `TrackCustomerEventJob` para gravação assíncrona | ✅ Concluído |
| `EventName` — os 7 eventos tipados em enum | ✅ Concluído |
| `PropertySanitizer` — minimização LGPD no caminho de escrita | ✅ Concluído |
| 35 testes novos (schema, Models, gravação) | ✅ Concluído |
| `docs/CUSTOMER_INTELLIGENCE_INTERNAL.md` | ✅ Concluído |

**Estrutura criada:**

```
app/CustomerIntelligence/
├── Enums/EventName.php                      os 7 eventos, tipados
├── Jobs/TrackCustomerEventJob.php           gravação assíncrona
├── Models/
│   ├── Visitor.php                          ci_visitors
│   ├── VisitorSession.php                   ci_sessions
│   ├── TrackedEvent.php                     ci_events
│   └── DailyMetric.php                      ci_daily_metrics
├── Services/CustomerIntelligenceService.php porta de entrada do módulo
└── Support/PropertySanitizer.php            redige dados sensíveis
```

**Banco criado (4 tabelas, todas aditivas):**

| Tabela | Responsabilidade | Chaves e índices |
|---|---|---|
| `ci_visitors` | Identidade anônima persistente | `visitor_uuid` unique · FK `user_id` nullOnDelete · índice `last_seen_at` |
| `ci_sessions` | Janela de navegação | `session_uuid` unique · FK `visitor_id` cascade · índice `started_at` |
| `ci_events` | Fato comportamental (append-only) | `event_uuid` unique · 3 FKs nullOnDelete · `(event_name, occurred_at)` · `(occurred_at)` · `(entity_type, entity_id)` |
| `ci_daily_metrics` | Agregado diário, retenção permanente | unique `(metric_date, metric_name, dimension_type, dimension_value)` |

**Decisões de arquitetura:**

- **Só existem as pastas que têm conteúdo.** Sem `Contracts/`, `DTOs/`, `Repositories/` ou `Exceptions/`: nenhuma teria consumidor nesta fase, e o projeto usa Services concretos com Eloquent direto.
- **Sem ServiceProvider ainda.** Nada precisa de binding, config merge ou middleware — o container resolve o Service por autowiring. Um provider vazio seria camada artificial. Ele nasce na CI-03.
- **Livewire fica fora do módulo.** `config/livewire.php` define `class_namespace => App\Livewire`; componentes fora desse namespace exigem registro manual — exatamente o que o `AppServiceProvider` faz hoje com os aliases `jmf-ci-*`. O painel continuará em `app/Livewire/Admin/CustomerIntelligence/`.
- **`ci_events` é append-only.** Um evento é um fato ocorrido, nunca editado: não existe `updated_at`.
- **Nomes sem ambiguidade.** `TrackedEvent` e não `Event`, porque `App\Models\Event` já é uma feira da agenda; `VisitorSession` e não `Session`, porque `sessions` é a tabela de sessão do Laravel.
- **UUID público + `id` interno.** O bigint auto incremental continua sendo a chave e o alvo das foreign keys; o UUID ordenado é o identificador público e mantém localidade de índice numa tabela que só cresce.
- **Dimensões de métrica são `NOT NULL` com default vazio.** No MySQL, valores `NULL` são distintos entre si em índice `UNIQUE` — com colunas nuláveis a chave única não impediria gravar a mesma métrica global duas vezes.

**O que a CI-02 deliberadamente não fez:** não migrou nenhuma das 7 chamadas, não gravou nenhum evento real, não removeu o SDK, não criou cookies nem middleware, não implementou expurgo nem agregadores. Um teste específico garante isso: navega por um produto, adiciona ao carrinho e afirma que `ci_events` continua vazia.

---

### ✅ CI-03 — Coleta: visitante e sessão (Concluída)

**Objetivo:** o módulo passa a resolver visitante e sessão a cada requisição web e gravá-los no banco local, convivendo com o middleware do SDK externo sem disputar cookies.

**Entregue:**

| Componente | Status |
|---|---|
| `CustomerIntelligenceServiceProvider` registrado em `bootstrap/providers.php` | ✅ Concluído |
| Middleware `TrackVisitorSession` anexado ao grupo `web` | ✅ Concluído |
| `Actions/ResolveVisitorSession` — regra de abertura e rotação de sessão | ✅ Concluído |
| `Support/VisitorContext` — visitante/sessão da requisição, `scoped` | ✅ Concluído |
| `config/customer-intelligence-internal.php` | ✅ Concluído |
| Vínculo automático visitante ↔ usuário autenticado | ✅ Concluído |
| Captura de landing, referrer e UTMs na abertura da sessão | ✅ Concluído |
| 17 testes novos | ✅ Concluído |

**O risco previsto e como foi tratado.** A auditoria classificou esta fase como MÉDIO por causa dos dois middlewares convivendo no grupo `web` — ambos emitindo os mesmos cookies. Duas medidas resolvem:

*Ordem determinística.* O middleware é anexado pelo `boot()` do provider do módulo, não por `bootstrap/app.php`. A configuração de bootstrap é aplicada antes de qualquer `boot()`, e providers da aplicação inicializam depois dos descobertos por pacote — então o middleware do módulo entra na pilha depois do middleware do SDK e enxerga o que ele já decidiu.

*Adoção do valor já enfileirado.* Numa primeira visita não há cookie na requisição e o SDK acabou de gerar um identificador. Em vez de gerar outro — o que faria o servidor remoto e o banco local conhecerem o mesmo visitante por dois nomes —, o middleware lê o valor via `Cookie::queued()`, API do próprio Laravel, sem acoplar a classes do SDK. Reenfileirar com o mesmo nome é seguro: o CookieJar indexa a fila por nome e caminho, então sai um único `Set-Cookie`.

Há teste automatizado para as duas coisas: a posição na pilha e a unicidade do cookie.

**Minimização de dados.** `landing_url` guarda apenas o caminho, sem query string; o referrer é reduzido a esquema, host e caminho. Nenhum IP e nenhum user-agent são coletados. Um teste navega com `?busca=segredo` e referrer contendo dado pessoal e confirma que nada disso é persistido.

**Fronteira preservada.** A coleta de visitante está ligada; a de eventos, não. `ci_events` continua vazia e os sete eventos seguem saindo pelo SDK externo — sem escrita dupla, como decidido na CI-01. Dois testes guardam essa fronteira.

**Validado no ambiente real:** três requisições com o mesmo navegador produziram um visitante, uma sessão, um cookie de cada nome e zero eventos.

---

### ✅ CI-04 — Escrita de eventos pela fila dedicada (Concluída)

**Objetivo:** dar ao módulo o caminho assíncrono de gravação, numa fila própria, sem que a requisição pague pela escrita.

**Entregue:**

| Componente | Status |
|---|---|
| `CustomerIntelligenceService::track()` — enfileira e devolve o controle | ✅ Concluído |
| Fila dedicada `customer-intelligence`, definida no próprio job | ✅ Concluído |
| Worker do Docker escutando a nova fila | ✅ Concluído |
| Captura de sessão, usuário e instante no despacho | ✅ Concluído |
| Bloco `queue` em `config/customer-intelligence-internal.php` | ✅ Concluído |
| 12 testes novos | ✅ Concluído |

**A ordem em `--queue` é prioridade, não lista.** O worker passou a declarar `--queue=default,email-marketing,customer-intelligence`, e o Laravel só olha a fila seguinte quando a anterior está vazia. Rastreamento fica por último de propósito: um pico de navegação nunca deve atrasar um e-mail de pedido.

**O erro clássico que ficou blindado.** Despachar para uma fila que ninguém escuta faz os eventos sumirem em silêncio. Um teste lê o `compose.yaml` e falha se a fila sair do `--queue` — a fiação entre a configuração da aplicação e a infraestrutura passou a ser verificada automaticamente.

**O que viaja com o job.** Sessão, usuário autenticado e o instante do fato são apurados no despacho, porque dentro do worker não existe cookie nem usuário logado para consultar. Por isso `occurred_at` e `created_at` divergem de propósito — o atraso da fila não desloca a história. O usuário capturado tem precedência sobre `Auth::id()` na gravação, já que o job pode rodar depois do logout; há teste para exatamente esse cenário.

**Fronteira preservada.** `track()` existe, funciona e está testado ponta a ponta, mas nenhuma ação de negócio o chama. `ci_events` continua vazia em uso normal e os sete eventos seguem no SDK externo — sem escrita dupla. Um teste navega por um produto, adiciona ao carrinho e afirma que nada foi despachado.

**Validado no ambiente real:** evento enfileirado pelo tinker caiu na fila `customer-intelligence`, o worker do Docker processou em 178 ms e a linha apareceu em `ci_events` com o morph para `Product` e `occurred_at` um segundo antes de `created_at`. A linha de validação foi removida em seguida, para que a invariante "sem escrita dupla" continue verificável.

---

### ✅ CI-05 — Migração das 7 chamadas de rastreamento (Concluída)

**Objetivo:** repontar as sete chamadas de rastreamento do SDK externo para o módulo interno. Corte direto, sem escrita dupla, conforme a decisão 1 da auditoria.

Esta era a fase de risco **ALTO** da trilha: as chamadas vivem em `CartService`, `OrderService` e `Checkout` — o coração da compra.

**Como o risco foi contido:**

*Uma fachada preservou a forma da chamada.* Nasceu `App\CustomerIntelligence\Facades\CustomerIntelligence`, cumprindo a decisão 6. A migração de cada ponto virou uma troca de `use` mais a substituição da string pelo enum — diff mecânico e revisável, em vez de reescrita.

*Os `try/catch` foram mantidos.* Uma falha de analytics continua sem poder derrubar um carrinho ou um pedido. Dois testes novos provam isso injetando um serviço que lança exceção e verificando que o item entra no carrinho e o pedido é criado assim mesmo.

*Os payloads não mudaram.* As propriedades de cada evento seguem idênticas às que o SDK enviava, o que mantém o histórico comparável.

**O que mudou para melhor:** produtos, pedidos e splits deixaram de viver apenas dentro do JSON de `properties` e viraram referência real em `entity_type`/`entity_id`. É o que permite perguntar "quantas visualizações este produto teve antes da primeira venda" sem vasculhar JSON. A entidade só é anexada quando o model já está em mãos, sem provocar consulta extra.

**Os testes mudaram de alvo.** `EventTrackingTest` verificava o despacho do `SendPayloadJob` do SDK; passou a verificar a linha que ficou em `ci_events`. Os dois testes de fronteira das fases anteriores — que afirmavam que navegar *não* dispara nada — foram invertidos: agora guardam a direção da fiação, verificando que navegar alimenta o módulo e cai na fila certa.

**Descoberta durante a validação.** O log mostra que o último envio bem-sucedido para a plataforma externa foi em **8 de agosto**. Todas as tentativas de 25 de agosto — 48 no total — falharam com **401, token inválido ou expirado**. O rastreamento externo estava quebrado em silêncio havia mais de duas semanas, e nada de valor se perdeu no corte. A suíte de testes também fazia chamadas HTTP reais à VPS a cada execução; isso acabou.

**Validado no ambiente real:** duas visitas ao mesmo produto geraram dois eventos em `ci_events`, ambos ligados ao mesmo visitante e à mesma sessão — a continuidade da CI-03 alimentando os eventos da CI-05 —, com referência ao `Product` e zero jobs falhos. Nenhum registro novo do SDK apareceu no log.

---

### Fases pendentes

| Fase | Objetivo | Risco previsto |
|---|---|---|
| **CI-06** | Componentes Livewire próprios consultando Eloquent no lugar do `JmfCiApiClient`; agregadores diários. | MÉDIO — agregação sem índice adequado trava o painel |
| **CI-07** | Cortar o envio remoto; remover registro Livewire duplicado e código morto. | MÉDIO — ponto sem volta para novos dados na VPS |
| **CI-08** | Remover pacote Composer, bloco `repositories` do tipo `path`, as 2 linhas do `compose.yaml` e as variáveis `JMF_CI_*`. | MÉDIO — `composer update` mexe no lock; conferir o diff |
| **CI-09** | Expurgo agendado dos 180 dias, comando de exclusão por titular, documentação final. Aposentar `docs/JMF_CI_INTEGRATION.md`. | BAIXO — mas exclusão é irreversível |

### Critério de conclusão da trilha

```bash
git clone projeto-feira-esquerda-livre
docker compose up -d
```

funcionando sem nenhum repositório JMF ao lado, com o painel de Inteligência de Cliente lendo dados reais do banco local e a suíte de testes verde.

---

## 🎯 Princípios Transversais de Desenvolvimento

Estes princípios se aplicam a todas as fases e devem guiar cada decisão de UX e arquitetura:

**Público 40+ First**
- Fonte mínima: 16px em todo o conteúdo; 18px para textos principais
- Botões e áreas de toque: mínimo 48×48px (Google Material guideline)
- Sem autocomplete, mapas interativos ou gestos complexos em funcionalidades principais
- Feedback claro e imediato para cada ação (toasts, alertas, indicadores visuais)

**Performance em Redes Lentas**
- Todas as imagens comprimidas no backend antes do armazenamento
- Formato WebP como padrão; fallback PNG para navegadores antigos
- Lazy loading em todas as imagens fora da viewport inicial
- Nenhuma biblioteca JavaScript de terceiros que supere 30KB gzipped

**Mobile First Absoluto**
- Design começa pelo layout 360px e escala para cima
- Breakpoints: 360 · 390 · 414 (mobile) → 768 (tablet) → 1024 · 1280 (desktop)
- Testar em dispositivos reais de entrada (Android 8+, 2GB RAM) a cada fase

**Segurança e LGPD**
- CPF/CNPJ armazenado sempre encriptado (`encrypt()` do Laravel)
- Dados pessoais dos compradores nunca repassados aos lojistas sem consentimento explícito
- Política de retenção de dados: cart_items anônimos expiram em 7 dias
- Logs de acesso ao painel admin retidos por 90 dias

---

---

## ✅ Fase 8 — AVA (Ambiente Virtual de Aprendizagem) (Concluída)

**Período:** Julho de 2026
**Objetivo estratégico:** transformar a plataforma em marketplace de cursos digitais, permitindo que lojistas vendam e gerenciem conteúdo educativo online e que alunos acessem, assistam e acompanhem seu progresso — tudo integrado ao fluxo de checkout existente.

### Módulo 8.1 — Infraestrutura AVA

| Componente | Status |
|---|---|
| Campo `is_digital` em `products` | ✅ Concluído |
| Tabelas `ava_courses`, `ava_modules`, `ava_lessons`, `ava_lesson_materials` | ✅ Concluído |
| Tabelas `ava_enrollments`, `ava_lesson_progress` | ✅ Concluído |
| Enum `AvaEnrollmentStatus` (Active/Expired/Cancelled/Refunded) | ✅ Concluído |
| Models em `App\Models\Ava\` com métodos de domínio | ✅ Concluído |
| Event `OrderSplitConfirmed` + Listener `HandleAvaEnrollmentOnSplitConfirmed` | ✅ Concluído |
| `AvaEnrollmentService::createFromOrderSplit()` — desacoplado do marketplace | ✅ Concluído |
| Mail `AvaEnrollmentConfirmedMail` enviado na matrícula | ✅ Concluído |
| Toggle `is_digital` no formulário do lojista + auto-criação do `AvaCourse` | ✅ Concluído |
| Checkout: skip de frete e endereço para carrinho 100% digital | ✅ Concluído |
| Painel `/minha-conta/aprendizado` — listagem de matrículas do aluno | ✅ Concluído |
| 10 testes automatizados cobrindo o fluxo de matrícula | ✅ Concluído |

### Módulo 8.2 — Course Builder & Player

| Componente | Status |
|---|---|
| `CursoIndex` — listagem dos cursos do lojista com status e métricas | ✅ Concluído |
| `CursoBuilder` — editor completo: configurações, módulos e aulas inline | ✅ Concluído |
| Configurações: nível, carga horária, acesso, drip, certificado, intro, requisitos | ✅ Concluído |
| Gerenciamento de módulos: criar, editar, excluir, reordenar | ✅ Concluído |
| Gerenciamento de aulas: criar, editar, excluir, reordenar (vídeo/texto/pdf/áudio) | ✅ Concluído |
| Publicação/despublicação de curso com um clique | ✅ Concluído |
| `CursoPlayer` — player do aluno com sidebar de índice, embed YouTube/Vimeo | ✅ Concluído |
| Progresso por aula: `started_at`, `completed_at`, barra de progresso | ✅ Concluído |
| "Marcar como concluída e avançar" com atualização de `completion_percent` | ✅ Concluído |
| Link "Meus Cursos" na sidebar do lojista | ✅ Concluído |
| Link "Começar/Continuar" no painel do aluno apontando para o player | ✅ Concluído |
| Testes: CursoIndex, CursoBuilder (módulos/aulas), CursoPlayer (marcação) | ✅ Concluído |

**Tabelas criadas:**
```
ava_courses          — product_id (unique), level, estimated_hours, access_duration_days, is_drip, certificate_enabled, intro_video_url, requirements, what_youll_learn, published_at
ava_modules          — course_id, title, description, sort_order, is_visible
ava_lessons          — module_id, title, description, content_type, video_url, video_provider, video_duration_sec, text_content, is_preview, is_visible, sort_order, drip_day
ava_lesson_materials — lesson_id, title, file_path, file_type, file_size_kb, sort_order (sem updated_at)
ava_enrollments      — user_id, course_id, order_split_id (nullable), status, enrolled_at, expires_at, completed_at, completion_percent, last_accessed_at
ava_lesson_progress  — enrollment_id, lesson_id, started_at, completed_at, watched_seconds, last_position_sec
```

**Arquitetura de desacoplamento:**
`OrderSplit::confirmar()` → dispara `OrderSplitConfirmed` → `HandleAvaEnrollmentOnSplitConfirmed` → `AvaEnrollmentService::createFromOrderSplit()` — o marketplace nunca importa código AVA diretamente.

### Módulo 8.3 — Materiais Complementares & Certificado

| Componente | Status |
|---|---|
| Upload de materiais por aula no `CursoBuilder` (`WithFileUploads`, até 20MB, pdf/pptx/docx/xlsx/zip/mp3/mp4) | ✅ Concluído |
| Download protegido via URL temporária assinada (`URL::temporarySignedRoute`, 15min TTL) | ✅ Concluído |
| `AvaMateriaisController` — valida matrícula ativa antes de servir o arquivo | ✅ Concluído |
| Lista de materiais por aula no `CursoPlayer` com link de download | ✅ Concluído |
| `AvaCertificateService` — gera PDF A4 paisagem via `barryvdh/laravel-dompdf` | ✅ Concluído |
| Certificado auto-gerado na primeira vez que o aluno atinge 100% | ✅ Concluído |
| `AvaCertificateMail` — email com PDF anexado ao concluir curso | ✅ Concluído |
| Não re-gera certificado se já existe (idempotente) | ✅ Concluído |
| Botão "Baixar Certificado" no player e no painel de aprendizado | ✅ Concluído |
| `AvaCertificadoController` — download sob demanda (re-gera se arquivo perdido) | ✅ Concluído |
| 10 testes: upload, delete, download protegido, assinatura, certificado, email, idempotência | ✅ Concluído |

---

## ✅ Fase 9 — API Mobile (Flutter) (Concluída — v1)

**Período:** Agosto de 2026
**Objetivo estratégico:** preparar o backend para receber o app mobile em Flutter (cliente comprador e lojista), expondo como API REST versionada o mesmo comportamento de negócio já validado no site — sem reescrever regras, reaproveitando os Services existentes (`CartService`, `OrderService`, `MercadoPagoService`, `Shipping\MelhorEnvioService`, `AvaEnrollmentService`).

**Contexto técnico:** até esta fase, a plataforma era 100% sessão/cookie via Livewire — não havia `routes/api.php`, autenticação de API, CORS nem testes/documentação de API. Foi trabalho greenfield, construído em cima da lógica de negócio já madura do site.

### O que foi entregue

| Componente | Status |
|---|---|
| `laravel/sanctum` instalado, tabela `personal_access_tokens`, trait `HasApiTokens` no `User` | ✅ Concluído |
| `routes/api.php` registrado em `bootstrap/app.php`, prefixo versionado `/api/v1` | ✅ Concluído |
| `config/cors.php` (`paths: api/*`, origem permissiva — autenticação é por Bearer token, não cookie) | ✅ Concluído |
| Autenticação: registrar, entrar (cliente e lojista no mesmo endpoint), sair, eu | ✅ Concluído |
| Catálogo público: produtos/serviços/cuidados, detalhe, lojas, categorias, agenda, rastreio, perguntas | ✅ Concluído |
| Carrinho (exige login), cotação de frete, checkout, pedidos, chat pós-pedido, endereços | ✅ Concluído |
| AVA — Meu Aprendizado: matrículas, conteúdo do curso, concluir aula, certificado | ✅ Concluído |
| Lojista: painel, loja, CRUD de produtos, pedidos recebidos, perguntas, exposição, cursos (listar/publicar) | ✅ Concluído |
| Comunidade (feed): listar, curtir, comentar, denunciar — leitura pública, interação exige login | ✅ Concluído |
| 52 testes de feature em `tests/Feature/Api/V1/` (401/403/caminho feliz/422 por grupo) | ✅ Concluído |
| `docs/API.md` — referência completa de rotas, envelope de resposta, erros e exemplos | ✅ Concluído |

---

### Módulo 9.1 — Infraestrutura & Autenticação

**Objetivo:** base técnica da API e login por token, sem depender de sessão/cookie do navegador.

**Decisão técnica — Sanctum em modo token pessoal (Bearer), não SPA/cookie:** o Flutter é um app separado, não uma SPA no mesmo domínio, então não há necessidade do modo "stateful" do Sanctum (cookies + CSRF). Cada login gera um token nomeado por dispositivo (`$user->createToken($deviceName)`), revogável individualmente no logout.

**Entregas:**
- `POST /auth/registrar` — cria cliente comprador (`role=user`), mesmas regras de validação do cadastro web.
- `POST /auth/entrar` — endpoint único para cliente e lojista; valida credenciais manualmente (sem `Auth::attempt`, que depende da sessão do guard `web`, indisponível em rota de API stateless).
- `POST /auth/sair` — revoga o token atual.
- `GET /auth/eu` — usuário autenticado, com papel, perfil de cliente e dados da loja (se lojista).
- Lojistas continuam sendo criados apenas via aprovação de solicitação (`/seja-um-expositor`, fluxo já existente) — a API não tem cadastro de lojista, só login.

---

### Módulo 9.2 — Catálogo, Carrinho, Checkout, Pedidos, Chat, Endereços e AVA (Cliente)

**Objetivo:** cobrir toda a jornada de compra do cliente comprador dentro do app.

**Decisão de produto confirmada:** o carrinho **exige login** no app (sem carrinho anônimo por dispositivo). Isso permitiu reaproveitar o `CartService` exatamente como já existia — `Auth::check()` resolve o carrinho pelo usuário autenticado via Sanctum sem nenhuma alteração no service.

**Entregas:**
- Catálogo público (produtos/serviços/cuidados, detalhe, lojas, categorias, agenda, rastreio) sem autenticação, espelhando as rotas web equivalentes.
- Perguntas de produto: listagem pública das respondidas, envio autenticado (mesmas regras de `ProductQandA`).
- Carrinho: adicionar, alterar quantidade, remover — via `CartService`, sem duplicar lógica.
- Cotação de frete: `POST /frete/cotacao` reaproveita **o mesmo** `ShippingController::quote` já usado pelo checkout web, registrado também em `routes/api.php`.
- Checkout: `POST /checkout` espelha `Checkout::confirmar()` (Livewire) — valida dados e endereço, chama `OrderService::createFromCart()` e, se Mercado Pago estiver ativo, `MercadoPagoService::createPreference()`, retornando a URL de pagamento no JSON.
- Pedidos: listagem, detalhe e geração/consulta do link de pagamento Mercado Pago.
- Chat pós-pedido: `GET/POST /pedidos/splits/{split}/mensagens` reaproveita a mesma regra de autorização do componente `OrderChat` (cliente dono do pedido OU lojista dono da loja do split) — endpoint compartilhado entre o app do cliente e o do lojista.
- Endereços: CRUD completo, mesmas regras de validação do `EnderecoForm`.
- AVA — Meu Aprendizado: matrículas com progresso, conteúdo do curso (módulos/aulas/materiais com link assinado), marcar aula concluída (gera certificado automaticamente ao chegar a 100%, mesma lógica de `AvaEnrollment::updateCompletionPercent()`) e download do certificado.

---

### Módulo 9.3 — Lojista

**Objetivo:** permitir que o lojista administre a loja a partir do celular, sem depender do painel web.

Rotas sob `/lojista`, protegidas por `auth:sanctum` + o middleware `lojista` já existente (`LojistaMiddleware`), reaproveitado tal como estava — inclusive a resposta 403 em JSON quando o request pede `Accept: application/json`.

**Entregas:**
- Painel: resumo de produtos e próximos eventos da loja.
- Perfil da loja: visualizar e atualizar (upload de logo/banner via multipart, com spoofing `_method=PUT` documentado — limitação conhecida do PHP com uploads em requisições PUT reais).
- Produtos: CRUD completo (produto/serviço/cuidado), até 4 imagens comprimidas via `ImageService`, FAQ embutido, criação automática de `AvaCourse` quando marcado como digital — mesmas regras do `ProdutoForm`.
- Pedidos recebidos: confirmar pagamento e marcar como enviado (com notificação por e-mail ao cliente), espelhando `LojistaPedidoIndex`.
- Perguntas: responder e alternar visibilidade, com contagem de pendentes/respondidas.
- Exposição na home: impressões da loja (total, 7 e 30 dias) e slot de destaque ativo, se houver.
- Cursos: listagem com status e alternância publicado/rascunho — **sem** o construtor completo (módulos/aulas/materiais), ver "fora do escopo" abaixo.

---

### Módulo 9.4 — Testes e Documentação

**Entregas:**
- `tests/Feature/Api/V1/` com 42 testes cobrindo os seis grupos de endpoints (autenticação, catálogo, carrinho/checkout, pedidos/chat/endereços, AVA, lojista), incluindo casos de 401 sem token, 403 de papel/dono incorreto, caminho feliz e 422 de validação.
- `docs/API.md`: convenções de envelope de resposta (recurso único → `data`, paginação → `data/links/meta`, respostas compostas → chaves próprias), formato de erros, upload de arquivos e tabela completa de rotas com exemplos.

---

### Módulo 9.5 — Comunidade (Feed) na API

**Objetivo estratégico:** o feed social é um diferencial de produto do app mobile — decisão de priorizar sua entrada na API mobile antes mesmo do carrinho/checkout do app, dado o valor de engajamento comunitário.

**Entregas:**
- `GET /feed` — publicações visíveis (`FeedPost::visible()`), com loja, imagens, contagem de curtidas/comentários e `liked_by_me` (calculado apenas quando autenticado, sem exigir login para navegar — mesmo princípio de acesso público do restante do catálogo).
- `GET /feed/{post}/comentarios` — comentários visíveis de uma publicação.
- `POST /feed/{post}/curtir` — alterna curtir/descurtir (autenticado), reaproveitando a mesma regra de unicidade (`feed_likes` unique por post+usuário) já usada pelo `FeedIndex` do site.
- `POST /feed/{post}/comentarios` — novo comentário (autenticado, máx. 500 caracteres).
- `POST /feed/{post}/denunciar` — denúncia (autenticada, uma por usuário/post, `firstOrCreate` incrementando `reported_count` só na primeira vez) — mesma lógica do componente Livewire público.
- Autorização replicada inline das Policies existentes (`FeedPostPolicy::interact`, `FeedCommentPolicy::create`): publicação precisa estar visível e usuário precisa estar ativo.
- **Publicar no feed continua exclusivo do lojista pelo site** — o app só consome (ver/curtir/comentar/denunciar), não cria publicações.
- 10 testes novos em `tests/Feature/Api/V1/FeedApiTest.php`.
- Correção incidental: `database/factories/UserFactory.php` não definia `is_active`, deixando o atributo `null` em memória logo após `create()` (o Eloquent não busca de volta o `DEFAULT true` do banco) — qualquer código checando `$user->is_active` direto num usuário recém-criado por factory (como `Sanctum::actingAs()` faz durante os testes) via essa lacuna. Corrigido definindo `is_active => true` explicitamente na factory.

---

### Fora do escopo desta fase (deliberado)

Cortes conscientes para manter a v1 da API enxuta e testável — candidatos a uma fase seguinte:

- **Construtor de curso AVA** (criar/editar módulos, aulas, materiais, reordenar) — CRUD aninhado grande com upload de arquivos.
- **Publicar no feed pelo app** — permanece exclusivo do painel do lojista no site.
- **Email marketing** — funcionalidade exclusiva do painel administrativo.
- **Painel administrativo** — permanece 100% web.
- **Recuperação de senha via API** — login e cadastro cobrem o essencial por enquanto.

---

*Documento atualizado em: 25 de agosto de 2026 — Versão 2.8*
*Status: Fases 1 a 10 concluídas. Ambiente de desenvolvimento em Docker concluído. Trilha CI em andamento — 5 de 9 fases. Os 7 eventos de negócio já são gravados pelo módulo interno; nenhum sai mais por HTTP.*
*Próxima revisão: após a CI-06 (dashboard lendo do banco local)*
*Itens pós-MVP planejados: OAuth por lojista, compra e geração de etiquetas, split automático completo via Mercado Pago, auditoria administrativa, recuperação de carrinho abandonado, integração SendGrid/SES para campanhas em larga escala, melhorias de escala operacional, construtor de curso AVA e publicação no feed pelo app.*
