# 🗺️ Roadmap de Desenvolvimento — Feira Esquerda Livre

**Documento de Planejamento Estratégico**
**Versão:** 1.2 — Junho de 2026
**Status geral do projeto:** Fase 3 em andamento — três eixos do marketplace implementados

---

## Visão Geral das Fases

```
[FASE 1 ✅ CONCLUÍDA]
     CMS, Admin & Home
           ↓
[FASE 2 ✅ CONCLUÍDA]
  Lojistas & Agenda
           ↓
[FASE 3 🔄 EM ANDAMENTO]
  Catálogo & Três Eixos
           ↓
[FASE 4 ⏳ Semanas 10–13]
  Checkout & Pagamentos
           ↓
[FASE 5 ⏳ Semanas 14–17]
  Comunidade & Engajamento
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

## 🔄 Fase 3 — Catálogo de Produtos e Loja Pública (Em andamento)

**Período:** Semanas 7 a 9
**Objetivo estratégico:** Transformar o marketplace em utilidade real — produtos cadastráveis pelos lojistas e navegáveis pelo público com fluidez nos três eixos.

### O que foi entregue até agora

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

---

### Módulo 3.1 — CRUD de Produtos (Área do Lojista)

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
- Ordenação drag-and-drop para definir ordem de exibição (Livewire + SortableJS)
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

### Módulo 3.2 — Renderização das Lojas e Produtos (Visão do Cliente)

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

### Módulo 3.3 — Carrinho Multilojas (Livewire)

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

## 💳 Fase 4 — Checkout, Logística e Split de Pagamento

**Período estimado:** Semanas 10 a 13
**Objetivo estratégico:** A fase mais crítica do core do negócio — calcular frete, cobrar e distribuir o dinheiro automaticamente.

---

### Módulo 4.1 — Cálculo de Frete (Melhor Envio)

**Objetivo:** Cotação de frete automatizada por lojista no carrinho.

**Entregas:**

**4.1.1 — Integração com API Melhor Envio**
- Autenticação OAuth2 (token por lojista, não por plataforma)
- Cada lojista cadastra seu CEP de origem no painel da loja
- Cotação chamada no momento em que o cliente informa o CEP de entrega
- Exibe: transportadora, prazo e preço para cada loja do carrinho
- Modalidades exibidas: Correios PAC, Sedex, transportadoras privadas (quando disponíveis)
- Fallback: se a API falhar, exibe "Frete a combinar — fale com o lojista"

**4.1.2 — Seleção de Frete por Loja**
- Interface clara: o cliente escolhe a transportadora de cada loja separadamente
- Resumo final: frete total = soma dos fretes de cada loja

**Tabela nova:**
```
order_shippings
  - id, order_id, expositor_id, carrier, service_name
  - price, estimated_days, tracking_code
  - status (pending/shipped/delivered)
  - created_at, updated_at
```

---

### Módulo 4.2 — Checkout em Etapa Única (One Page Checkout)

**Objetivo:** Fluxo de compra mais simples possível para o público 40+.

**Entregas:**

**4.2.1 — Identificação do Comprador**
- Três opções de acesso (ordem de prioridade para o público 40+):
  1. **Link Mágico por WhatsApp** — telefone → recebe link de acesso (sem senha)
  2. **Google** — OAuth simplificado
  3. **E-mail + Senha** — tradicional, como alternativa

**4.2.2 — Formulário de Endereço**
- CEP com preenchimento automático via ViaCEP (API gratuita)
- Campos: CEP, Rua, Número, Complemento, Bairro, Cidade, Estado
- Sem campos desnecessários (não pedir CPF aqui — já foi no cadastro)
- Mapa opcional (mostrar pin apenas, não editar)

**4.2.3 — Resumo e Pagamento**
- Lista dos itens, subtotal por loja, frete por loja, total final
- Uma única opção destacada: **PIX**
  - Gerar QR Code exibido com tamanho grande (mín. 250×250px)
  - Botão "Copiar Código PIX" de largura total, altura mínima 60px
  - Countdown de 30 minutos com barra de progresso visual
  - Verificação automática de pagamento a cada 5 segundos (polling Livewire)
- Opção secundária (menor destaque): Cartão de crédito (até 12×)

---

### Módulo 4.3 — Split de Pagamento Mercado Pago

**Objetivo:** Cobrar o cliente uma vez e distribuir automaticamente para cada lojista, retendo a comissão da plataforma.

**Entregas:**

**4.3.1 — Configuração da Conta Mercado Pago**
- Conta principal: plataforma Feira Esquerda Livre (recebe tudo e redistribui)
- Cada lojista vincula sua conta Mercado Pago via OAuth no painel da loja
- Administrador define a taxa de comissão global (% configurável via CMS)

**4.3.2 — Lógica do Split**
- Pagamento único feito pelo cliente para a conta da plataforma
- Após confirmação do pagamento:
  - Cálculo: `valor_lojista = subtotal_loja + frete_loja − comissão_plataforma`
  - Repasse automático via API Marketplace do Mercado Pago
  - Prazo de repasse: D+2 (conforme política do Mercado Pago)
- Webhook de confirmação de pagamento → dispara os repasses

**4.3.3 — Painel de Recebimentos do Lojista**
- Histórico de pedidos com status: Aguardando Pagamento · Pago · Repassado · Cancelado
- Valor bruto, comissão retida, valor líquido recebido
- Filtro por mês

**Tabelas novas:**
```
orders
  - id, user_id, status, total_amount, payment_method
  - mp_payment_id, mp_status, pix_qrcode, pix_expiry_at
  - created_at, updated_at

order_items
  - id, order_id, product_id, expositor_id, quantity
  - unit_price, total_price

order_splits
  - id, order_id, expositor_id
  - gross_amount, commission_amount, net_amount
  - mp_transfer_id, transferred_at, status
```

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

## 📊 Resumo do Roadmap

| Fase | Período | Módulos | Entregável Principal |
|---|---|---|---|
| ✅ Fase 1 — Fundação | Semanas 1–3 | CMS · Admin · Home | Plataforma funcional com conteúdo dinâmico |
| ✅ Fase 2 — Lojistas & Agenda | Semanas 4–6 | 2.1 · 2.2 · 2.3 | Lojistas cadastráveis · Agenda pública navegável |
| 🔄 Fase 3 — Catálogo & Três Eixos | Semanas 7–9 | 3.1 · 3.2 · 3.3 | Estrutura dos três eixos concluída; loja pública e carrinho em desenvolvimento |
| ⏳ Fase 4 — Checkout & Pagamento | Semanas 10–13 | 4.1 · 4.2 · 4.3 | Venda completa com frete e split automático |
| ⏳ Fase 5 — Comunidade | Semanas 14–17 | 5.1 · 5.2 | Feed social + ferramentas de marketing |

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

*Documento atualizado em: 14 de junho de 2026*
*Próxima revisão: ao término da Fase 3 (catálogo público e carrinho)*
