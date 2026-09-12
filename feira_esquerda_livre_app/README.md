# Feira Esquerda Livre — App Mobile

App Flutter da Feira Esquerda Livre para dois perfis: **cliente comprador**
(catálogo, compra, pedidos, cursos) e **lojista** (gestão da loja). É um cliente
puro da API REST do backend Laravel deste mesmo repositório — nenhuma regra de
negócio (frete, comissão, estoque, certificado) é reimplementada aqui.

## Documentação

| Assunto | Onde |
|---|---|
| Estado das fases do app e próximos passos | [`../ROADMAP.md`](../ROADMAP.md), seção *API mobile e app Flutter* |
| Contrato da API — rotas, envelope de resposta, erros | [`../docs/API.md`](../docs/API.md) |
| Arquitetura do backend e decisões | [`../docs/ARCHITECTURE.md`](../docs/ARCHITECTURE.md) |
| Instalação do backend | [`../README.md`](../README.md) |

## Stack

Declarada em `pubspec.yaml` (Dart SDK `^3.11.4`):

| Camada | Pacote |
|---|---|
| Estado | `flutter_riverpod` |
| HTTP | `dio` (interceptor de `Authorization: Bearer`) |
| Rotas | `go_router` |
| Token | `flutter_secure_storage` |
| Modelos | `freezed` + `json_serializable` (`build_runner`) |
| Imagens | `cached_network_image` |
| Outros | `url_launcher`, `intl`, `google_fonts` (Nunito), `flutter_html` |
| Ícone e splash | `flutter_launcher_icons`, `flutter_native_splash` |

Código em `lib/` (`core/`, `features/`, `shared/`).

## Como rodar

Suba o backend primeiro (ver [`../README.md`](../README.md)). Depois:

```bash
cd feira_esquerda_livre_app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/api/v1
```

`API_BASE_URL` é lida em `lib/core/http/api_client.dart`. `10.0.2.2` é o host
visto de dentro do emulador Android; ajuste para o endereço e a porta em que o
backend responde (no Docker, a porta HTTP publicada é a de `DOCKER_HTTP_PORT`).

Regenerar ícone e splash depois de trocar a imagem da marca:

```bash
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```

## Princípios herdados do produto

Público 40+ (fontes grandes, toque ≥ 48×48px), redes lentas (cache de imagem,
paginação), identidade visual `#F4E294` / `#1a472a` / `#3D3000`, e LGPD (token
revogável por `POST /auth/sair`).
