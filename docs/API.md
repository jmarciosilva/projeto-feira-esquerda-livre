# API REST — Feira Esquerda Livre (app mobile Flutter)

API versionada consumida pelo app mobile em Flutter (cliente comprador e lojista). Toda a lógica de negócio é reaproveitada dos mesmos Services usados pelo site web (`CartService`, `OrderService`, `MercadoPagoService`, `Shipping\MelhorEnvioService`, `AvaEnrollmentService`) — o comportamento é equivalente ao do site, apenas exposto em JSON.

---

## Convenções gerais

| Item | Valor |
|---|---|
| Base URL | `https://SEU-DOMINIO/api/v1` |
| Autenticação | Bearer token (Laravel Sanctum — token pessoal, sem cookies/CSRF) |
| Header obrigatório | `Accept: application/json` em toda requisição |
| Header de auth | `Authorization: Bearer {token}` nas rotas protegidas |
| Formato de datas | ISO 8601 (UTC), serializado automaticamente pelo Laravel |
| Content-Type de escrita | `application/json`, exceto uploads (`multipart/form-data`) |

### Envelope das respostas

- **Endpoint que retorna um recurso único diretamente** (ex.: `GET /produtos/{id}`, `POST /enderecos`) → vem envelopado em `data`:
  ```json
  { "data": { "id": 1, "name": "..." } }
  ```
- **Endpoint que retorna uma listagem paginada** (ex.: `GET /produtos`, `GET /pedidos`) → formato padrão do Laravel:
  ```json
  { "data": [ ... ], "links": { "first": "...", "last": "...", "prev": null, "next": "..." }, "meta": { "current_page": 1, "last_page": 3, "total": 42, ... } }
  ```
- **Endpoints com resposta "composta"** (ex.: `POST /checkout`, `GET /carrinho`, `GET /lojas/{slug}`) → usam um envelope próprio documentado em cada seção (ex.: `{ "order": {...} }`, `{ "stores": [...], "total": ..., "count": ... }`), **sem** o wrapper `data` genérico.

### Erros

Segue o padrão Laravel:

| Situação | Status | Corpo |
|---|---|---|
| Não autenticado | `401` | `{ "message": "Unauthenticated." }` |
| Sem permissão (ex.: não é lojista, não é dono do recurso) | `403` | `{ "message": "..." }` |
| Recurso não encontrado | `404` | `{ "message": "..." }` |
| Validação | `422` | `{ "message": "...", "errors": { "campo": ["mensagem"] } }` |

### Upload de arquivos (logo, banner, imagens de produto)

PHP não popula uploads em requisições `PUT`/`PATCH` reais. Para atualizar `loja` ou `produtos` enviando novas imagens, envie **POST** com o campo `_method=PUT` (method spoofing padrão do Laravel) e `Content-Type: multipart/form-data`. Sem novas imagens, pode enviar um PUT comum com JSON.

---

## Autenticação — `/auth`

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/auth/registrar` | não | Cadastra cliente comprador (`role=user`). Body: `name, email, whatsapp, password, password_confirmation, device_name?` |
| POST | `/auth/entrar` | não | Login único para cliente e lojista. Body: `email, password, device_name?` |
| POST | `/auth/sair` | sim | Revoga o token atual |
| GET | `/auth/eu` | sim | Usuário autenticado, com `role`, `marketplace_status` e `expositor` (se lojista) |

Resposta de `registrar`/`entrar`:
```json
{ "user": { "id": 1, "name": "...", "email": "...", "role": "user", "role_label": "Cliente", "is_active": true }, "token": "1|xxxxxxxx..." }
```

Lojistas **não se cadastram** por aqui — a conta é criada quando a administração aprova a solicitação em `/seja-um-expositor` (fluxo já existente no site). Eles fazem login pelo mesmo `POST /auth/entrar`.

---

## Catálogo público (sem autenticação)

| Método | Rota | Descrição |
|---|---|---|
| GET | `/produtos` \| `/servicos` \| `/cuidados` | Catálogo paginado por eixo. Filtros: `?busca=&categoria=` |
| GET | `/produtos/{id}` | Detalhe do item (imagens, FAQ, loja) |
| GET | `/lojas` | Lojas ativas, paginado (destaques primeiro) |
| GET | `/lojas/{slug}` | Perfil público da loja + produtos ativos |
| GET | `/categorias?eixo=produto\|servico\|cuidado` | Categorias do eixo |
| GET | `/agenda` | Próximas feiras. Filtros: `?estado=&mes=&ano=` |
| GET | `/agenda/{slug}` | Detalhe do evento + expositores confirmados |
| GET | `/rastreio/{trackingCode}` | Rastreio público de uma entrega |
| GET | `/noticias` | Notícias/posts publicados, paginado, mais recentes primeiro |
| GET | `/noticias/{slug}` | Notícia completa (corpo em HTML) + até 3 relacionadas do mesmo tipo |
| GET | `/contato` | WhatsApp e e-mail públicos de contato da plataforma |
| POST | `/contato` | Mesmo fluxo do formulário de contato do site, sem sair do app. Body: `name, email, phone?, subject, message` |
| POST | `/seja-um-expositor` | Mesmo fluxo do formulário "Seja um Expositor" do site. Body: `nome_loja, responsavel, cpf_cnpj, whatsapp, email, instagram_url, facebook_url?, pix_tipo, pix_chave, banco_nome?, banco_agencia?, banco_conta?, banco_tipo_conta?, descricao?, eixos?[]` |
| GET | `/produtos/{id}/perguntas` | Perguntas já respondidas e visíveis |
| POST | `/produtos/{id}/perguntas` | **Requer login.** Body: `question` (5–500 chars) · `product_offer_id` (opcional, ver abaixo) |
| POST | `/frete/cotacao` | Cotação de frete (mesmo endpoint do checkout web). Body: `store_id, destination_zipcode, items[{product_id, quantity}]` |
| GET | `/feed` | Publicações visíveis da comunidade (posts dos lojistas), paginado. `liked_by_me` só é calculado quando autenticado |
| GET | `/feed/{post}/comentarios` | Comentários visíveis de uma publicação |

> **`product_offer_id` — contexto comercial da pergunta (CAT-DOM-02E).**
> A pergunta passou a registrar **em que oferta foi feita**, porque quem responde
> é o lojista dela. O campo é **opcional**, e continuará opcional enquanto
> multi-oferta estiver desabilitada — nenhum cliente existente quebra.
>
> ```text
> informado          → validado contra o produto da rota; de outro produto, 422
> ausente + 1 oferta → resolvido pela cardinalidade determinística
> ausente + 0 ou >1  → 422 com erro em `product_offer_id`
> ```
>
> A oferta **nunca** é inferida: nada de primeira oferta, expositor do produto ou
> oferta mais barata. Quando a aplicação habilitar multi-oferta, uma fase futura
> pode reavaliar tornar o campo obrigatório.
>
> O campo `faqs` do produto passou a trazer a **FAQ da oferta** exibida, e não a
> FAQ canônica do catálogo; `images` e `main_image_url` seguem a mesma regra,
> com fallback para a imagem canônica quando a oferta não tem uma.

---

## Cliente autenticado (`Authorization: Bearer`)

### Carrinho

| Método | Rota | Descrição |
|---|---|---|
| GET | `/carrinho` | `{ "stores": [{ "expositor_id", "expositor_name", "subtotal", "items": [...] }], "total", "count" }` |
| POST | `/carrinho/itens` | Body: `product_id, quantity?` (default 1) |
| PATCH | `/carrinho/itens/{item}` | Body: `quantity` (0 remove o item) |
| DELETE | `/carrinho/itens/{item}` | Remove o item |

### Comunidade (feed)

| Método | Rota | Descrição |
|---|---|---|
| POST | `/feed/{post}/curtir` | Alterna curtir/descurtir. Resposta: `{ "liked": bool, "likes_count": int }` |
| POST | `/feed/{post}/comentarios` | Body: `content` (máx. 500 chars) |
| POST | `/feed/{post}/denunciar` | Body: `reason` (máx. 500 chars). Uma denúncia por usuário/post — repetir não duplica |

Carrinho **exige login** no app — não existe carrinho anônimo por dispositivo nesta versão.

### Checkout e pedidos

| Método | Rota | Descrição |
|---|---|---|
| POST | `/checkout` | Cria o pedido a partir do carrinho. Body: `customer_name, customer_whatsapp, customer_email?, delivery_type (retirada\|entrega), customer_address_id? (obrigatório se entrega e houver item físico), shipping_total?, shipping_note?`. Resposta: `{ "order": {...} }`. Se Mercado Pago estiver ativo, `order.mercado_pago_checkout_url` já vem preenchido |
| GET | `/pedidos` | Pedidos do usuário autenticado (paginado) |
| GET | `/pedidos/{reference}` | Detalhe do pedido, com itens e splits por loja |
| GET | `/pedidos/{reference}/pagar` | Gera/retorna a URL de pagamento Mercado Pago: `{ "checkout_url": "..." }` |
| GET | `/pedidos/splits/{split}/mensagens` | Histórico do chat daquele split (marca como lidas as mensagens da outra parte) |
| POST | `/pedidos/splits/{split}/mensagens` | Envia mensagem. Body: `body` (máx. 2000 chars). Acesso: cliente dono do pedido OU lojista dono da loja do split |

### Endereços

| Método | Rota | Descrição |
|---|---|---|
| GET | `/enderecos` | Lista os endereços salvos |
| POST | `/enderecos` | Body: `label, cep, rua, numero, complemento?, bairro, cidade, estado (UF)` |
| PUT | `/enderecos/{id}` | Mesmos campos do POST |
| DELETE | `/enderecos/{id}` | Remove (`204 No Content`) |

### AVA — Meu Aprendizado

| Método | Rota | Descrição |
|---|---|---|
| GET | `/aprendizado` | Matrículas do aluno, com progresso |
| GET | `/aprendizado/{enrollment}` | `{ "enrollment": {...}, "modules": [{ "lessons": [{ "embed_url", "text_content", "is_completed", "materials": [{"download_url"...}] }] }] }` |
| POST | `/aprendizado/{enrollment}/aulas/{lesson}/concluir` | Marca aula concluída e recalcula progresso. Ao chegar a 100%, gera certificado automaticamente |
| GET | `/aprendizado/{enrollment}/certificado` | Download do PDF (`403` se o curso ainda não foi concluído) |

Materiais de aula usam URL assinada temporária (`download_url`, 15 min) já pronta no JSON — não precisa de outro endpoint autenticado.

---

## Lojista (`Authorization: Bearer` + conta com papel `lojista` e loja ativa) — prefixo `/lojista`

| Método | Rota | Descrição |
|---|---|---|
| GET | `/lojista/painel` | Resumo: total de produtos, próximos eventos da loja |
| GET | `/lojista/loja` | Perfil da loja |
| PUT | `/lojista/loja` | Atualiza perfil. Campos de arquivo: `logo`, `banner` (ver seção de upload acima) |
| GET | `/lojista/produtos` | Produtos/serviços/cuidados da loja (paginado) |
| POST | `/lojista/produtos` | Cria item. Campos: `item_type, name, price?, weight/height/width/length (só produto), price_type/modality/duration_min (só serviço/cuidado), category_id?, has_stock?, stock_quantity?, is_active?, is_featured?, is_digital?, images[] (até 4, multipart), faqs[{question,answer}]?` |
| GET | `/lojista/produtos/{id}` | Detalhe |
| PUT | `/lojista/produtos/{id}` | Atualiza. Campos extras: `remove_image_indexes[]` (índices das imagens atuais a excluir) |
| DELETE | `/lojista/produtos/{id}` | Remove (`204 No Content`) |
| GET | `/lojista/pedidos` | Pedidos recebidos pela loja. Filtro: `?status=` |
| PATCH | `/lojista/pedidos/{split}/confirmar-pagamento` | Marca o split como pago |
| PATCH | `/lojista/pedidos/{split}/marcar-enviado` | Body: `carrier, tracking_code, shipped_at (data)`. Notifica o cliente por e-mail |
| GET | `/lojista/perguntas` | Perguntas dos produtos da loja. Filtro: `?filter=pending\|answered`. Inclui `meta.pending_count`/`meta.answered_count` |
| PATCH | `/lojista/perguntas/{id}/responder` | Body: `answer` |
| PATCH | `/lojista/perguntas/{id}/visibilidade` | Alterna visibilidade pública da pergunta |
| GET | `/lojista/exposicao` | Impressões da loja na home (`on_home: false` se não estiver em destaque/rotação) |
| GET | `/lojista/cursos` | Cursos digitais da loja, com status de publicação |
| PATCH | `/lojista/cursos/{course}/publicar` | Alterna publicado/rascunho |

---

## Fora do escopo desta versão da API

Cortes deliberados para manter a primeira entrega enxuta — podem virar uma fase seguinte:

- **Construtor de curso AVA** (criar/editar módulos, aulas, materiais, reordenar) — hoje só listagem e publicar/despublicar.
- **Publicar no feed pelo app** — só o lojista publica (pelo site); o app consome (ver, curtir, comentar, denunciar).
- **Email marketing** — não é uma funcionalidade de app mobile.
- **Painel administrativo** — permanece exclusivo do site web.
- **Recuperação de senha via API** — ainda não implementada.

---

## Testes automatizados

`tests/Feature/Api/V1/` cobre autenticação, catálogo, carrinho/checkout, pedidos/chat/endereços, AVA e todos os endpoints de lojista (401 sem token, 403 papel/dono errado, caminho feliz, 422 de validação). Rodar com:

```bash
php artisan test --filter=Api
```
