# 🗺️ Roadmap de Desenvolvimento — Feira Esquerda Livre

**Documento de Planejamento Estratégico**
**Versão:** 1.7 — Julho de 2026
**Status geral do projeto:** Fase 4 evoluída após o MVP manual — checkout e pagamento manual seguem operacionais; a cotação inicial de frete via Melhor Envio foi implementada para o MVP usando conta única da plataforma, enquanto compra de etiqueta, rastreamento, OAuth por lojista e split de frete continuam planejados para fases futuras. A Fase 6 de governança administrativa foi implementada com gestão de usuários internos, perfis de acesso, permissões por módulo, proteção de rotas e bloqueio de ações críticas no backend.

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
[FASE 4 ✅ MVP + COTAÇÃO DE FRETE]
  Checkout, cotação inicial de frete & pagamentos manuais
           ↓
[FASE 5 ⏳ Semanas 14–17]
  Comunidade & Engajamento
          ↓
[FASE 6 ✅ CONCLUÍDA]
  Usuários internos, perfis & permissões
```

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

## ✅ Fase 4 — Checkout, Logística e Split de Pagamento (MVP com cotação inicial de frete)

**Período:** Semanas 10 a 13
**Objetivo estratégico:** A fase mais crítica do core do negócio — calcular frete, cobrar e distribuir o dinheiro automaticamente.

**Decisão de produto (16/06/2026):** para validar o fluxo completo de compra com o Gerente de Produto e o cliente o mais rápido possível,
a Fase 4 foi entregue inicialmente como um **MVP 100% manual**: frete combinado diretamente entre cliente e lojista
(WhatsApp) e pagamento via PIX/dados bancários do próprio lojista, confirmado manualmente.

**Atualização técnica (01/07/2026):** a integração inicial com o Melhor Envio foi implementada para cálculo de frete
no checkout. O MVP usa uma conta única da plataforma Feira Esquerda Livre, com credenciais via `.env`/configuração,
busca CEP de origem por loja, envia peso e dimensões dos produtos e retorna opções padronizadas para seleção no checkout.
Compra de etiqueta, geração de etiqueta, rastreamento, OAuth por lojista, split de frete e painel financeiro permanecem
fora do escopo atual.

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

### Módulo 4.3 — Split de Pagamento Mercado Pago — ⏳ Adiado (config pronta)

**Objetivo:** Cobrar o cliente uma vez e distribuir automaticamente para cada lojista, retendo a comissão da plataforma.

**Status atual:** como o pagamento ainda é manual (cliente paga direto na chave PIX/conta do lojista), não há
cobrança centralizada para "splitar". O que já existe:
- Campo de comissão da plataforma (%) configurável em `/admin/settings/checkout`
- `OrderSplit` calcula e registra `gross_amount`, `commission_amount` e `net_amount` por loja para fins de relatório,
  mesmo sem retenção real
- Campos de credenciais do Mercado Pago (Public Key, Access Token, sandbox) já salvos na mesma tela de configuração
- Confirmação manual do recebimento pelo lojista em `/minha-loja/pedidos` (equivalente ao status "Pago" do fluxo
  original, só que registrado manualmente em vez de via webhook)

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

## ✅ Fase 6 — Governança Administrativa, Usuários Internos e Permissões

**Objetivo estratégico:** profissionalizar a operação interna da plataforma, permitindo que a Feira Esquerda Livre tenha administradores, gerentes, supervisores e editores com acessos controlados, auditáveis e coerentes com suas responsabilidades.

**Status:** implementada em julho de 2026.

**Entregas realizadas:**
- Pacote `spatie/laravel-permission` instalado e configurado
- Roles iniciais criadas para administrador, gerente, supervisor, editor, lojista e cliente
- Permissões base versionadas em seeder idempotente
- Área `/admin/usuarios` para gestão de usuários internos
- Área `/admin/perfis-acesso` para configuração de permissões por perfil
- Menu administrativo renderizado conforme permissões
- Rotas administrativas protegidas com middleware `can:*`
- Componentes Livewire administrativos protegendo ações críticas no backend
- Testes automatizados para acesso por perfil, URL direta e ações Livewire bloqueadas

**Pendências futuras fora da Fase 6:** auditoria detalhada de alterações administrativas, logs de alteração de permissões e possível campo auxiliar de contexto de usuário para relatórios.

**Princípio de arquitetura:** separar claramente os três universos de usuários:
- **Cliente:** comprador da plataforma, com acesso à área de conta, endereços e pedidos.
- **Lojista:** dono de loja/expositor, com acesso à área `/minha-loja`.
- **Equipe interna:** usuários operacionais da Feira, com acesso ao painel `/admin` conforme perfil e permissões.

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

**Próxima decisão técnica:** definir se a separação será feita apenas por roles/permissões ou se haverá campos auxiliares como `user_type`/`context` para facilitar filtros e relatórios.

---

## 📊 Resumo do Roadmap

| Fase | Período | Módulos | Entregável Principal |
|---|---|---|---|
| ✅ Fase 1 — Fundação | Semanas 1–3 | CMS · Admin · Home | Plataforma funcional com conteúdo dinâmico |
| ✅ Fase 2 — Lojistas & Agenda | Semanas 4–6 | 2.1 · 2.2 · 2.3 | Lojistas cadastráveis · Agenda pública navegável |
| ✅ Fase 3 — Catálogo & Três Eixos | Semanas 7–9 | 3.1 · 3.2 · 3.3 | Três eixos, CRUD de produtos, loja pública e carrinho multilojas em produção |
| ✅ Fase 4 — Checkout & Pagamento | Semanas 10–13 | 4.1 · 4.2 · 4.3 | MVP com cotação inicial de frete via Melhor Envio; pagamento/split automáticos seguem em fases futuras |
| ⏳ Fase 5 — Comunidade | Semanas 14–17 | 5.1 · 5.2 | Feed social + ferramentas de marketing |
| ✅ Fase 6 — Governança Admin | Concluída | 6.1 · 6.2 · 6.3 · 6.4 · 6.5 | Usuários internos, perfis de acesso, permissões e proteção real do painel |

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

*Documento atualizado em: 1º de julho de 2026*
*Próxima revisão: ao priorizar auditoria administrativa, OAuth por lojista, compra/geração de etiquetas, rastreamento, split de frete ou ao término da Fase 5 (comunidade)*
