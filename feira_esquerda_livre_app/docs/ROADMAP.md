# 🗺️ Roadmap de Desenvolvimento — App Mobile (Flutter)

**Documento de Planejamento Estratégico**
**Versão:** 0.1 — Agosto de 2026
**Status geral do app:** planejamento. Nenhuma linha de código Flutter foi escrita ainda — este documento existe para alinhar o escopo e a ordem de construção antes de rodar `flutter create`. O backend que o app vai consumir **já está pronto** (API `/api/v1`, ver [`../../docs/API.md`](../../docs/API.md) e a Fase 9 do [`../../docs/ROADMAP.md`](../../docs/ROADMAP.md) do backend).

---

## Visão Geral das Fases

```
[FASE 1] Setup do Projeto & Autenticação
           ↓
[FASE 2] Catálogo & Loja Pública (sem login)
           ↓
[FASE 3] Carrinho & Checkout
           ↓
[FASE 4] Pedidos, Rastreio & Chat
           ↓
[FASE 5] AVA — Meu Aprendizado
           ↓
[FASE 6] Painel do Lojista
           ↓
[FASE 7] Qualidade, Testes & Publicação nas Lojas
```

Fases 1–5 cobrem a jornada do **cliente comprador**. Fase 6 cobre o **lojista**, reaproveitando toda a base técnica (autenticação, cliente HTTP, tema, componentes) construída nas fases anteriores — por isso vem depois, e não em paralelo.

---

## Marco de Referência — Backend já disponível

Não há trabalho de API pendente para as fases 1–6 abaixo. Todo endpoint necessário já existe e está documentado:

| Área do app | Endpoints já prontos no backend |
|---|---|
| Autenticação | `POST /auth/registrar`, `POST /auth/entrar`, `POST /auth/sair`, `GET /auth/eu` |
| Catálogo público | `GET /produtos\|servicos\|cuidados`, `GET /produtos/{id}`, `GET /lojas/{slug}`, `GET /categorias`, `GET /agenda`, `GET /rastreio/{code}` |
| Perguntas | `GET/POST /produtos/{id}/perguntas` |
| Carrinho | `GET /carrinho`, `POST/PATCH/DELETE /carrinho/itens` |
| Frete e checkout | `POST /frete/cotacao`, `POST /checkout` |
| Pedidos e chat | `GET /pedidos`, `GET /pedidos/{reference}`, `GET /pedidos/{reference}/pagar`, `GET/POST /pedidos/splits/{split}/mensagens` |
| Endereços | CRUD completo em `/enderecos` |
| AVA (cliente) | `GET /aprendizado`, `GET /aprendizado/{id}`, `POST .../aulas/{id}/concluir`, `GET .../certificado` |
| Lojista | `/lojista/painel`, `/lojista/loja`, `/lojista/produtos` (CRUD), `/lojista/pedidos`, `/lojista/perguntas`, `/lojista/exposicao`, `/lojista/cursos` |

Se alguma tela precisar de um dado que a API não retorna, é sinal para **voltar ao backend** e ajustar o endpoint/Resource correspondente antes de gambiarrar no app.

---

## ✅ Fase 1 — Setup do Projeto & Autenticação

**Objetivo estratégico:** ter uma base técnica sólida (tema, roteamento, cliente HTTP, sessão) antes de construir qualquer tela de produto, e entregar o primeiro fluxo ponta a ponta: cadastro/login funcionando contra o backend real.

### Entregas

| Componente | Descrição |
|---|---|
| Projeto Flutter criado nesta pasta (`flutter create .`) | Estrutura conforme `README.md` |
| Tema visual | Cores da marca (`#F4E294`, `#1a472a`, `#3D3000`), tipografia com tamanho mínimo 16px, componentes de botão com área de toque ≥ 48×48px (público 40+) |
| Cliente HTTP | Wrapper único (ex.: `dio`) com base URL configurável por `--dart-define`, interceptor que anexa `Authorization: Bearer` e trata `401` (desloga e redireciona ao login) |
| Armazenamento seguro do token | `flutter_secure_storage` — nunca token em texto puro |
| Roteamento | Guards por autenticação e por papel (`user` → área do cliente; `lojista` → área do lojista, ver Fase 6) |
| Tela de Login | Consome `POST /auth/entrar`; mesmo endpoint serve cliente e lojista — o app decide a navegação pós-login pelo campo `role` da resposta |
| Tela de Cadastro | Consome `POST /auth/registrar` (só cria `role=user` — cadastro de lojista continua sendo só pelo site, via aprovação) |
| Restauração de sessão | Ao abrir o app, tenta `GET /auth/eu` com o token salvo; se `401`, volta para o login |
| Logout | Consome `POST /auth/sair` e limpa o token local |

### Decisões de produto já tomadas (herdadas do backend)

- **Carrinho exige login** — não há fluxo de "convidado" no app. Reforça a importância de o login ser o primeiro fluxo bem construído.
- Sem recuperação de senha via API ainda — se o usuário esquecer a senha, o app deve orientar a fazer isso pelo site (fora do escopo desta fase, ver "Fora do escopo" no fim do documento).

---

## ✅ Fase 2 — Catálogo & Loja Pública (sem login)

**Objetivo estratégico:** permitir que qualquer visitante explore produtos, serviços e cuidados sem precisar se cadastrar — o cadastro só é pedido no momento de comprar ou perguntar.

### Entregas

| Componente | Descrição |
|---|---|
| Home / catálogo por eixo | Abas ou navegação para Produtos / Serviços / Cuidados, consumindo `GET /{eixo}` com paginação (scroll infinito) |
| Filtros | Busca por nome (`?busca=`) e categoria (`?categoria=`), com `GET /categorias?eixo=` para popular o filtro |
| Detalhe do produto | Galeria de imagens, descrição, preço formatado em R$, FAQ, perguntas já respondidas (`GET /produtos/{id}/perguntas`) |
| Pergunta ao lojista | Campo de pergunta exige login — se o visitante não estiver autenticado, direciona ao login/cadastro antes de enviar |
| Página da loja | `GET /lojas/{slug}` — banner, descrição, grade de produtos da loja |
| Agenda de feiras | Listagem (`GET /agenda`) com filtro por estado/mês/ano e detalhe (`GET /agenda/{slug}`) |
| Rastreio público | Campo para digitar o código de rastreio, consultando `GET /rastreio/{code}` sem exigir login |

**Decisão de UX (herdada do site):** sem gestos complexos, sem mapas interativos nesta fase — cards grandes, botões claros, "Ver mais" em vez de infinite scroll agressivo se o time preferir simplicidade sobre performance.

---

## ✅ Fase 3 — Carrinho & Checkout

**Objetivo estratégico:** fechar o primeiro ciclo de compra completo dentro do app.

### Entregas

| Componente | Descrição |
|---|---|
| Carrinho | `GET /carrinho`, agrupado por loja (mesma UX do site: seções por lojista, subtotal por loja) |
| Alterar quantidade / remover | `PATCH`/`DELETE /carrinho/itens/{id}` |
| Cotação de frete | `POST /frete/cotacao` por loja, ao escolher "Entrega" |
| Formulário de checkout | Nome, WhatsApp, e-mail opcional, tipo de entrega (retirada/entrega), seleção de endereço salvo |
| Confirmação do pedido | `POST /checkout` → tela de sucesso com resumo, número de referência |
| Pagamento Mercado Pago | Se `order.mercado_pago_checkout_url` vier preenchido, abrir em `WebView` ou navegador externo; ao voltar, consultar `GET /pedidos/{reference}` para refletir o status atualizado |

**Ponto de atenção técnico:** o app **não calcula frete, comissão nem total** — só exibe o que a API já retorna. Qualquer regra de precificação muda no backend, nunca no app.

---

## ✅ Fase 4 — Pedidos, Rastreio & Chat

**Objetivo estratégico:** dar visibilidade e canal de comunicação pós-compra, reduzindo mensagens de suporte fora da plataforma.

### Entregas

| Componente | Descrição |
|---|---|
| Meus Pedidos | `GET /pedidos` paginado, com status visual (aguardando pagamento / confirmado / concluído / cancelado) |
| Detalhe do pedido | `GET /pedidos/{reference}` — itens, splits por loja, endereço, forma de pagamento |
| Retomar pagamento | Botão "Pagar" quando `payment_status` ainda pendente, consultando `GET /pedidos/{reference}/pagar` |
| Chat por loja | `GET/POST /pedidos/splits/{split}/mensagens` — como a API não tem WebSocket, usar **polling** (ex.: a cada 5s enquanto a tela do chat estiver aberta, mesmo intervalo do `OrderChat` do site) |
| Endereços | Tela de CRUD completo (`/enderecos`), reaproveitável tanto no checkout quanto numa tela "Meus Endereços" |
| Rastreio autenticado | Link direto de dentro do pedido para o rastreio (mesma tela pública da Fase 2) |

---

## ✅ Fase 5 — AVA — Meu Aprendizado

**Objetivo estratégico:** permitir que quem comprou um produto digital (curso) consuma o conteúdo direto do celular.

### Entregas

| Componente | Descrição |
|---|---|
| Lista de matrículas | `GET /aprendizado` — cards com progresso (%) e status |
| Player do curso | `GET /aprendizado/{id}` — navegação por módulos/aulas; embed de vídeo (YouTube/Vimeo via `embed_url`) ou conteúdo em texto (`text_content`) |
| Marcar aula concluída | `POST .../aulas/{id}/concluir` — atualiza progresso local imediatamente (otimista) e sincroniza com a resposta |
| Materiais de apoio | Lista de arquivos por aula com `download_url` (URL assinada, 15 min) — abrir no navegador padrão do device ou baixar |
| Certificado | Ao completar 100%, liberar botão "Baixar Certificado" (`GET .../certificado`, PDF) |

**Ponto de atenção:** `download_url` expira em 15 minutos — não cachear esse link; buscar de novo (`GET /aprendizado/{id}`) se o usuário demorar para abrir.

---

## ✅ Fase 6 — Painel do Lojista

**Objetivo estratégico:** permitir que o lojista administre a loja no dia a dia sem precisar abrir o site em um desktop — especialmente útil durante uma feira física.

**Pré-requisito:** toda a base técnica das Fases 1–5 (auth, cliente HTTP, tema, componentes de formulário) é reaproveitada — esta fase é essencialmente "mais uma área do mesmo app", não um app separado.

### Entregas

| Componente | Descrição |
|---|---|
| Roteamento pós-login | Se `user.role == "lojista"`, a navegação inicial vai para a área do lojista em vez do catálogo |
| Painel | `GET /lojista/painel` — total de produtos, próximos eventos |
| Perfil da loja | `GET/PUT /lojista/loja`, incluindo upload de logo/banner (multipart — ver nota de `_method=PUT` no `docs/API.md` do backend) |
| Produtos | CRUD completo (`/lojista/produtos`), formulário com campos condicionais por eixo (produto/serviço/cuidado), upload de até 4 imagens, FAQ embutido |
| Pedidos recebidos | `GET /lojista/pedidos`, ação "Confirmar Pagamento" e modal "Marcar como Enviado" (transportadora, código de rastreio, data) |
| Perguntas | `GET /lojista/perguntas` com contadores pendente/respondida, responder e alternar visibilidade |
| Exposição na home | `GET /lojista/exposicao` — impressões totais/7/30 dias, gráfico simples |
| Cursos | `GET /lojista/cursos`, alternar publicado/rascunho — **sem** construtor de curso (fora do escopo, ver abaixo) |

---

## ✅ Fase 7 — Qualidade, Testes & Publicação nas Lojas

**Objetivo estratégico:** sair de "funciona na minha máquina" para um app publicável e mantível.

### Entregas

| Componente | Descrição |
|---|---|
| Testes de widget/unit | Fluxos críticos: login, adicionar ao carrinho, checkout, marcar aula concluída |
| Estados de erro e vazio | Sem internet, timeout, 401 (sessão expirada), listas vazias — telas dedicadas, não apenas mensagens genéricas |
| Loading states | Skeletons ou indicadores consistentes em todas as listas/telas assíncronas |
| Identidade do app | Ícone, splash screen, nome, cores de status bar consistentes com a marca |
| Build de release | Android (`.aab` para Play Store) e, se aplicável, iOS (`.ipa` para App Store) |
| Checklist de publicação | Política de privacidade (link para a já existente do site), permissões declaradas (câmera/galeria só se usadas), versionamento semântico |

---

## 📊 Resumo do Roadmap

| Fase | Entregável Principal | Depende de endpoint novo no backend? |
|---|---|---|
| Fase 1 — Setup & Autenticação | Login/cadastro funcionando, sessão persistida | Não |
| Fase 2 — Catálogo & Loja Pública | Navegação completa sem login | Não |
| Fase 3 — Carrinho & Checkout | Primeira compra ponta a ponta | Não |
| Fase 4 — Pedidos, Rastreio & Chat | Acompanhamento pós-compra | Não |
| Fase 5 — AVA | Consumo de curso digital no app | Não |
| Fase 6 — Painel do Lojista | Gestão da loja pelo celular | Não |
| Fase 7 — Qualidade & Publicação | App nas lojas | Não |

Todas as fases usam endpoints já existentes — nenhuma delas está bloqueada por trabalho pendente no backend.

---

## Fora do escopo (por enquanto)

Mesmo corte já documentado na Fase 9 do roadmap do backend, refletido aqui para o app:

- **Construtor de curso AVA** (criar/editar módulos, aulas, materiais) — o lojista cria cursos pelo site; o app só lista e publica/despublica.
- **Feed/Comunidade** (posts, curtidas, comentários).
- **Recuperação de senha** dentro do app — orientar o usuário a usar o site enquanto o endpoint não existir no backend.
- **Notificações push** — não avaliado ainda; entraria como uma fase própria (exigiria FCM/APNs e endpoints novos no backend para registrar device tokens).
- **Modo offline** — todas as telas assumem conexão ativa nesta primeira versão.

---

*Documento criado em: 1º de agosto de 2026 — Versão 0.1*
*Próxima revisão: ao concluir a Fase 1 (setup + autenticação funcionando contra o backend real).*
