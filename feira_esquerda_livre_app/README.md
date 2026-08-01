# Feira Esquerda Livre — App Mobile

App mobile em Flutter para a Feira Esquerda Livre, cobrindo dois perfis de usuário: **cliente comprador** (navegar no catálogo, comprar, acompanhar pedidos, aprender nos cursos digitais) e **lojista** (gerenciar a loja, produtos, pedidos recebidos e perguntas). Consome a API REST já publicada no backend Laravel deste mesmo repositório.

> **Status:** apenas planejamento nesta pasta por enquanto (`README.md` + `docs/ROADMAP.md`). O projeto Flutter (`flutter create`, `pubspec.yaml`, `lib/`) ainda não foi gerado.

---

## Backend consumido

Toda a regra de negócio já existe no backend Laravel deste repositório (pasta raiz). O app é um cliente puro da API — não deve reimplementar nenhuma regra que já existe no servidor (cálculo de frete, comissão, elegibilidade de certificado, etc.).

- **Base URL:** `https://SEU-DOMINIO/api/v1` (configurável por ambiente — dev aponta para o `php artisan serve` local)
- **Autenticação:** Bearer token (Laravel Sanctum) — sem cookies, sem CSRF
- **Referência completa de rotas, formatos de resposta e erros:** [`../docs/API.md`](../docs/API.md)
- **Contexto geral do produto (marketplace, três eixos, AVA, etc.):** [`../README.md`](../README.md) e [`../docs/ROADMAP.md`](../docs/ROADMAP.md)

---

## Escopo do app

Definido junto com o backend (ver Fase 9 do `docs/ROADMAP.md` do backend):

| Perfil | Cobertura |
|---|---|
| Cliente comprador | Cadastro/login, catálogo (produtos/serviços/cuidados), loja pública, perguntas, carrinho, checkout, pedidos, rastreio, chat pós-pedido, endereços, AVA (Meu Aprendizado, player, certificado) |
| Lojista | Login, painel, perfil da loja, CRUD de produtos, pedidos recebidos (confirmar pagamento, marcar enviado), perguntas, exposição na home, cursos (listar/publicar) |

**Fora do escopo por enquanto** (mesmo corte já documentado no backend): construtor de curso AVA, feed/comunidade, email marketing, painel administrativo. Esses continuam exclusivos do site web.

---

## Stack técnica proposta

Ainda não implementada — proposta inicial para alinhar antes de gerar o projeto. Ajustável na Fase 1 do roadmap.

| Camada | Proposta | Motivo |
|---|---|---|
| Framework | Flutter (Dart, null safety) | Já definido pelo time |
| Gerenciamento de estado | Riverpod | Testável, sem `BuildContext` para lógica, bom suporte a chamadas assíncronas de API |
| Cliente HTTP | `dio` | Interceptors (anexar token, tratar 401 globalmente), suporte nativo a multipart para upload de imagens |
| Roteamento | `go_router` | Deep links, guards de rota por autenticação/papel (cliente vs. lojista) |
| Armazenamento seguro do token | `flutter_secure_storage` | Token Sanctum não deve ficar em `SharedPreferences` puro |
| Modelos/serialização | `freezed` + `json_serializable` | Gera parsing/equality a partir das respostas JSON documentadas em `docs/API.md` |
| Imagens | `cached_network_image` | Catálogo com muitas imagens; cache evita recarregar em redes lentas (3G/4G) |

---

## Princípios herdados do produto web

Os mesmos princípios do site (ver `../README.md`) valem para o app:

- **Público 40+ first:** fontes grandes, toque generoso (mín. 48×48px), fluxos sem gestos complexos, feedback claro em cada ação.
- **Performance em redes lentas:** cache de imagem, paginação, sem carregar tudo de uma vez.
- **Identidade visual:** paleta `#F4E294` (destaque) / `#1a472a` e `#3D3000` (marca), reaproveitada dos temas admin/lojista/cliente do site.
- **LGPD:** dados pessoais tratados com cuidado; token revogável a qualquer momento pelo endpoint `POST /auth/sair`.

---

## Estrutura de pastas (planejada)

Convenção a ser criada quando o projeto Flutter for gerado nesta pasta:

```text
feira_esquerda_livre_app/
├── README.md              (este arquivo)
├── docs/
│   └── ROADMAP.md          (planejamento de fases do app)
├── pubspec.yaml            (a criar — Fase 1)
├── lib/
│   ├── main.dart
│   ├── core/                (cliente http, tema, rotas, storage do token)
│   ├── features/
│   │   ├── auth/
│   │   ├── catalogo/
│   │   ├── carrinho/
│   │   ├── checkout/
│   │   ├── pedidos/
│   │   ├── enderecos/
│   │   ├── aprendizado/
│   │   └── lojista/         (painel, produtos, pedidos, perguntas, exposição, cursos)
│   └── shared/               (widgets reutilizáveis, formatação de moeda/data)
└── test/
```

---

## Como rodar (quando o projeto existir)

```bash
cd feira_esquerda_livre_app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1   # emulador Android apontando para o Laravel local
```

Para testar contra o backend deste repositório localmente, suba o Laravel primeiro (raiz do repositório):

```bash
composer run dev
```

---

## Status

Consulte [`docs/ROADMAP.md`](docs/ROADMAP.md) para o planejamento de fases de construção do app.
