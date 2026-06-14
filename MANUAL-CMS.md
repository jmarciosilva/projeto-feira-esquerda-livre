# Manual de Operação — CMS Feira Esquerda Livre

## Acesso ao Painel

- **URL:** `http://localhost/feira-esquerda-livre/admin`
- **Login:** `admin@feiraesquerdalivre.com.br`
- **Senha:** `Admin@2026!`

O painel exige autenticação e papel de administrador. Tentativas de acesso sem login redirecionam para a tela de login.

---

## Dashboard

A página inicial do painel (`/admin`) exibe:

- Contadores totais de Páginas, Banners, Posts, Eventos e Mídias
- Últimos 5 posts criados (com autor)
- Próximos 5 eventos ativos ordenados por data

---

## Módulos

### 1. Configurações Gerais `/admin/settings`

Configurações globais do site. Há somente um registro — editar salva sobre ele.

| Campo | Descrição |
|---|---|
| Nome do site | Título exibido no `<title>` e header |
| Descrição | Subtítulo / meta description global |
| WhatsApp | Número com DDD (ex: `11999990000`) |
| E-mail de contato | Exibido no footer |
| Instagram / Facebook / YouTube | URLs completas das redes |
| Endereço | Endereço físico da organização |
| Texto do rodapé | HTML ou texto simples |
| Modo manutenção | Liga/desliga página de manutenção |
| Logo | Imagem JPG/PNG/WebP — máx. 2 MB |
| Favicon | Ícone do browser — máx. 512 KB |

Clique em **Salvar** para confirmar. A logo e o favicon anteriores são deletados automaticamente do servidor ao substituir.

---

### 2. Páginas `/admin/pages`

Gerencia as páginas estáticas do portal.

**Listagem:** busca por título, ativação/desativação inline, link para edição.

**Criar/Editar:**

| Campo | Descrição |
|---|---|
| Título | Nome da página (obrigatório) |
| Slug | Gerado automaticamente a partir do título; editável |
| Meta Title | Título para SEO (máx. 255 chars) |
| Meta Description | Descrição para SEO (máx. 500 chars) |
| É a homepage? | Marca esta página como raiz do site |
| Ativo | Controla visibilidade pública |

O slug é gerado automaticamente enquanto a página ainda não foi salva. Após a primeira criação, o slug não é alterado automaticamente ao editar o título.

---

### 3. Banners `/admin/banners`

Carrossel ou destaques visuais da home.

**Listagem:** busca por título, toggle ativo/inativo inline, ordenação por `sort_order`.

**Criar/Editar:**

| Campo | Descrição |
|---|---|
| Título | Texto principal do banner (obrigatório) |
| Subtítulo | Texto secundário |
| Texto do botão | Label do CTA |
| Link do botão | URL completa (ex: `https://...`) |
| Ordem | Número inteiro — menor = aparece primeiro |
| Data início / fim | Período de exibição (opcional) |
| Ativo | Exibe ou oculta o banner |
| Imagem Desktop | JPG/PNG/WebP — máx. 4 MB — **obrigatória na criação** |
| Imagem Mobile | JPG/PNG/WebP — máx. 4 MB — opcional |

A imagem desktop é obrigatória ao criar um banner novo. Deixar o campo vazio em edição mantém a imagem atual.

---

### 4. Menus `/admin/menus`

Menus de navegação posicionados em diferentes áreas do site.

**Localizações disponíveis:**

| Valor | Exibição |
|---|---|
| `header` | Cabeçalho |
| `footer` | Rodapé |
| `sidebar` | Barra Lateral |
| `mobile` | Menu Mobile |

Os menus são criados pela listagem (`/admin/menus`). Para adicionar itens, clique em **Gerenciar** no menu desejado.

**Itens de menu:**

| Campo | Descrição |
|---|---|
| Título | Texto visível no link (máx. 100 chars) |
| URL | Caminho relativo (`/sobre`) ou absoluto (`https://...`) |
| Ícone | Classe CSS de ícone (ex: `fas fa-home`) |
| Abertura | `_self` (mesma aba) ou `_blank` (nova aba) |
| Ordem | Menor = aparece primeiro |
| Item pai | Define submenus (dropdown) |
| Ativo | Exibe ou oculta o item |

Itens filhos aparecem aninhados sob o item pai. A exclusão de um item é confirmada antes de executada.

---

### 5. Mídias `/admin/media`

Biblioteca central de arquivos.

**Upload:** arraste arquivos ou clique para selecionar. Formatos aceitos: `jpg`, `jpeg`, `png`, `gif`, `webp`, `svg`, `pdf`, `mp4` — máx. **10 MB por arquivo**. É possível enviar múltiplos arquivos de uma vez.

**Filtros:** busca por nome de arquivo, filtro por tipo (imagem, PDF, vídeo).

**Exclusão:** remove o arquivo tanto do banco de dados quanto do disco.

> Os arquivos ficam em `storage/app/public/` e são servidos via link simbólico em `public/storage/`.

---

### 6. Posts / Notícias `/admin/posts`

Conteúdo editorial do portal.

**Tipos de conteúdo:**

| Tipo | Uso |
|---|---|
| `post` | Post genérico / artigo |
| `news` | Notícia |
| `campaign` | Campanha |

**Status:**

| Status | Comportamento |
|---|---|
| `draft` | Rascunho — não visível publicamente |
| `published` | Publicado — visível conforme `published_at` |
| `archived` | Arquivado — retirado de circulação |

**Criar/Editar:**

| Campo | Descrição |
|---|---|
| Título | Obrigatório |
| Slug | Auto-gerado; editável |
| Categoria | Lista de categorias ativas |
| Resumo | Texto curto de chamada |
| Conteúdo | Corpo completo do post (HTML) |
| Meta Title / Description | SEO |
| Tipo | post / news / campaign |
| Status | draft / published / archived |
| Data de publicação | Define quando o post fica público |
| Ativo | Toggle geral de visibilidade |
| Imagem de capa | JPG/PNG — máx. 4 MB |

O slug é gerado automaticamente ao digitar o título (somente na criação).

---

### 7. Eventos `/admin/events`

Agenda de eventos da organização.

**Criar/Editar:**

| Campo | Descrição |
|---|---|
| Título | Obrigatório |
| Slug | Auto-gerado a partir do título |
| Descrição | Texto completo do evento |
| Estado | Sigla UF (ex: `SP`) |
| Cidade | Nome da cidade |
| Endereço | Logradouro completo |
| Latitude / Longitude | Coordenadas para mapa (numérico) |
| Data início | Obrigatória — data e hora |
| Data fim | Opcional — deve ser >= data início |
| URL de inscrição | Link externo de cadastro |
| Ativo | Controla exibição no portal |
| Imagem | JPG/PNG — máx. 4 MB |

Eventos com `is_active = true` e `start_date >= hoje` aparecem nos "próximos eventos" do Dashboard.

---

## Regras Gerais de Uso

**Confirmação de exclusão:** todos os módulos exibem um diálogo de confirmação antes de deletar. Confirme apenas quando tiver certeza — a ação é irreversível.

**Toggle ativo/inativo:** disponível direto na listagem de Banners e Posts sem precisar abrir o formulário.

**Imagens:** ao substituir uma imagem em qualquer módulo, a imagem anterior é excluída automaticamente do servidor.

**Slug único:** slugs duplicados causam erro de validação. Se o título já existir, ajuste o slug manualmente antes de salvar.

**Paginação:** listagens exibem 15 itens por página (Mídias: 24). Use a busca para filtrar.

---

## Ambiente e Infraestrutura

| Item | Valor |
|---|---|
| Framework | Laravel 12 + Livewire 3 |
| Banco de dados | MySQL 8 — `feira_esquerda_livre` |
| Servidor local | XAMPP + Apache |
| Raiz do projeto | `D:\projeto-feira-esquerda-livre\feira-esquerda-livre` |
| Symlink htdocs | `C:\xampp\htdocs\feira-esquerda-livre` |
| Storage público | `public/storage` → `storage/app/public` |

Para iniciar o ambiente local: abra o XAMPP Control Panel e inicie **Apache** e **MySQL**.

Para rodar o compilador de assets em desenvolvimento:

```bash
cd D:\projeto-feira-esquerda-livre\feira-esquerda-livre
npm run dev
```
