# CAT-DOM-01 — Decisão de Domínio: Produto Mestre × Oferta do Expositor

> **Projeto:** Feira Esquerda Livre  
> **Trilha relacionada:** Catalog Intelligence  
> **Classificação:** Decisão comercial, de domínio e arquitetura  
> **Momento da decisão:** após CAT-04 e antes da CAT-05  
> **Status inicial:** APROVADA PARA AUDITORIA E PLANEJAMENTO  
> **Objetivo deste documento:** registrar permanentemente o contexto, a motivação, a decisão tomada, suas consequências e orientar a implementação da fase intermediária sem perder a razão histórica da escolha.

---

## 1. Contexto

A trilha Catalog Intelligence chegou ao final da CAT-04 com uma base de conhecimento e um motor determinístico de similaridade capazes de identificar conceitos associados aos produtos e encontrar itens semelhantes.

Durante a revisão da CAT-04 surgiu uma questão de domínio: um produto marcado como ativo pode continuar existindo mesmo quando o expositor que originalmente o cadastrou se torna inativo.

Inicialmente isso poderia ser interpretado como inconsistência entre a visibilidade do produto e o status do expositor. Após análise do negócio, ficou decidido que **isso não deve ser tratado automaticamente como bug**.

A Feira Esquerda Livre precisa distinguir a existência de um produto no catálogo da existência de uma oferta comercial feita por determinado expositor.

---

## 2. Decisão comercial

### 2.1 Princípio central

**Um produto não deixa necessariamente de existir porque o expositor que o cadastrou deixou a Feira.**

O ciclo de vida do produto e do conhecimento associado a ele é diferente do ciclo de vida comercial de cada expositor.

Exemplo:

- o Expositor A vende capas para celular;
- ele cadastra uma determinada capa no sistema;
- posteriormente o Expositor A deixa a Feira;
- o Expositor B entra na Feira e vende exatamente o mesmo produto;
- a saída do Expositor A não deve obrigar o sistema a eliminar, desativar ou recriar conceitualmente aquele produto;
- o que mudou foi **quem está oferecendo o produto**, e não necessariamente **qual é o produto**.

Portanto:

> **Produto é uma entidade de catálogo. Oferta é uma relação comercial entre produto e expositor.**

---

## 3. Outro exemplo: artesanato

Considere uma "Toalha de crochê para abajur".

O Expositor A pode ter sido o primeiro a cadastrar esse tipo de produto e posteriormente deixar a Feira. O conhecimento acumulado continua sendo útil:

- crochê;
- artesanato;
- decoração;
- feito à mão;
- possível uso em abajur;
- descrições anteriormente aprovadas;
- termos relacionados;
- produtos semelhantes.

Posteriormente, o Expositor B pode comercializar a mesma toalha ou um produto suficientemente equivalente.

O sistema deve ser capaz de reaproveitar o catálogo e o conhecimento existentes, sem depender da permanência do Expositor A.

---

## 4. Problema do modelo conceitual atual

A auditoria deve confirmar o estado exato do código antes de qualquer alteração, mas a hipótese de domínio é que o `Product` atual concentra responsabilidades que possuem ciclos de vida distintos:

```text
Product atual
├── identidade/catalogação do item
├── descrição
├── categoria
├── conhecimento
├── expositor
├── preço
├── estoque/disponibilidade
└── status comercial
```

Isso mistura duas perguntas diferentes:

1. **O que é este produto?**
2. **Quem está vendendo este produto, por quanto e em quais condições?**

A decisão desta fase é separar conceitualmente essas responsabilidades.

---

## 5. Modelo de domínio desejado

O domínio deverá evoluir para algo equivalente a:

```text
Product
Produto mestre / item de catálogo
        │
        ├── ProductOffer — Expositor A
        ├── ProductOffer — Expositor B
        └── ProductOffer — Expositor C
```

### 5.1 Product — produto mestre

Deve representar a identidade compartilhável do item no catálogo.

Exemplos de responsabilidades candidatas:

- nome;
- descrição curta;
- descrição completa;
- categoria;
- tipo do item;
- conhecimento associado;
- características catalogáveis que sejam realmente globais;
- relações utilizadas pela Catalog Intelligence.

O `Product` **não deve deixar de existir apenas porque um vendedor específico saiu da plataforma**.

### 5.2 ProductOffer — oferta comercial

Nome sugerido: `product_offers` / `ProductOffer`.

A auditoria pode recomendar outro nome se houver forte razão técnica ou semântica, mas não deve alterar o conceito sem justificar documentalmente.

A oferta representa a relação entre um produto e um expositor.

Responsabilidades candidatas:

- `product_id`;
- `expositor_id`;
- preço;
- estoque/disponibilidade;
- status ativo/inativo da oferta;
- eventuais condições comerciais específicas do vendedor;
- outros campos comprovadamente dependentes do expositor.

---

## 6. Exemplo comercial esperado

```text
products
------------------------------------------------
id: 15
name: Capa para celular
short_description: ...
description: ...
category_id: ...
```

```text
product_offers
------------------------------------------------
id: 101
product_id: 15
expositor_id: 8
price: 25.00
stock_quantity: 10
is_active: false
```

```text
product_offers
------------------------------------------------
id: 205
product_id: 15
expositor_id: 14
price: 29.90
stock_quantity: 30
is_active: true
```

Interpretação:

- o produto continua existindo;
- a oferta do Expositor A foi encerrada;
- o Expositor B continua oferecendo o mesmo produto;
- conhecimento, classificação e histórico do produto não precisam ser recriados.

---

## 7. Regra de propriedade

Esta decisão **não autoriza transferência silenciosa de propriedade entre expositores**.

A SEC-02 continua válida.

Enquanto o modelo atual existir, um lojista não pode editar, assumir ou alterar `expositor_id` de um registro pertencente a outro expositor.

A futura separação Produto × Oferta deve substituir essa ambiguidade por uma regra mais clara:

> O expositor é proprietário/gestor da sua **oferta**, não necessariamente da identidade global do **produto mestre**.

Nenhuma implementação desta fase pode reabrir o IDOR corrigido na SEC-02.

---

## 8. Relação com Catalog Intelligence

A decisão é especialmente importante para a inteligência.

A Catalog Intelligence deve aprender e reutilizar conhecimento associado ao produto mestre, não ficar limitada ao ciclo de vida de uma loja.

Exemplo:

```text
Produto mestre: Tapete de crochê
        │
        ├── Crochê
        ├── Artesanato
        ├── Decoração
        └── Feito à mão
```

Esse conhecimento pode continuar útil mesmo que nenhum dos expositores que originalmente comercializaram o item permaneça ativo.

Assim:

```text
Catalog Intelligence
        ↓
     Product
        ↓
conhecimento reutilizável
```

Enquanto:

```text
Preço / estoque / disponibilidade / venda
        ↓
    ProductOffer
        ↓
     Expositor
```

---

## 9. Reutilização não significa cópia cega

O fato de um produto mestre existir não significa que todos os vendedores possuam exatamente o mesmo item físico, material, acabamento, medidas ou características.

A arquitetura precisa distinguir:

- produto realmente idêntico;
- variante;
- produto semelhante;
- produto apenas relacionado por conhecimento.

A fase intermediária não deve inventar automaticamente uma estratégia de deduplicação sem antes auditar os dados atuais.

Especialmente em artesanato, duas peças chamadas "Tapete de crochê" podem possuir diferenças relevantes.

Portanto, **não implementar merge automático destrutivo de produtos nesta fase**.

---

## 10. Fase intermediária obrigatória

Antes da atual CAT-05 — Assistente de Conteúdo — criar uma fase intermediária de domínio:

# CAT-DOM-01 — Produto Mestre e Ofertas

Ela deve aparecer no roadmap entre CAT-04 e CAT-05, sem renumerar silenciosamente as fases existentes.

Representação sugerida:

```text
CAT-04     ✅ Motor de similaridade
CAT-DOM-01 ⬜ Produto Mestre × Oferta do Expositor
CAT-05     ⬜ Assistente de conteúdo
```

O objetivo da CAT-DOM-01 é **auditar, desenhar, migrar com segurança e validar a separação entre identidade de catálogo e oferta comercial**, caso a auditoria confirme que essa é a evolução correta para o código real.

---

## 11. Subfases propostas

### CAT-DOM-01A — Auditoria completa do domínio atual

Status inicial: `PENDENTE`.

Auditar antes de alterar qualquer código:

- `products` e todas as migrations relacionadas;
- model `Product`;
- `ProdutoForm`;
- `ProdutoIndex`;
- API REST do lojista;
- catálogo público;
- página de produto;
- loja do expositor;
- carrinho;
- checkout;
- pedidos;
- itens de pedido;
- estoque;
- frete;
- compartilhamento;
- FAQs/perguntas;
- AVA/serviços/cuidados, se compartilharem `products`;
- Customer Intelligence;
- Catalog Intelligence;
- factories;
- seeders;
- testes;
- jobs/listeners/events;
- foreign keys e índices;
- qualquer código que use `product.expositor_id`, preço, estoque ou `is_active`.

Produzir uma matriz:

| Campo atual de Product | Global do produto? | Específico da oferta? | Ambíguo? | Consumidores | Destino proposto |
|---|---:|---:|---:|---|---|

Não presumir previamente onde cada coluna deve ficar.

### CAT-DOM-01B — Arquitetura e invariantes

Status inicial: `PENDENTE`.

Com base na auditoria, definir:

- responsabilidades finais de `Product`;
- responsabilidades finais de `ProductOffer`;
- cardinalidade;
- unicidade necessária;
- ciclo de vida;
- status;
- regras de visibilidade pública;
- regras para expositor inativo;
- regras para produto sem oferta ativa;
- relação com estoque;
- relação com preço;
- relação com carrinho e pedido;
- preservação histórica de pedidos;
- comportamento da API;
- comportamento da Catalog Intelligence.

Invariantes mínimas desejadas:

1. inativar expositor não destrói conhecimento global do produto;
2. uma oferta pertence a exatamente um expositor;
3. lojista não altera oferta de outro lojista;
4. produto mestre não troca de expositor porque o conceito deixa de pertencer ao expositor;
5. checkout resolve uma oferta comercial válida, não apenas um produto abstrato;
6. pedido histórico continua íntegro mesmo se oferta, produto ou expositor mudarem de estado;
7. Catalog Intelligence não perde conhecimento porque uma oferta foi desativada;
8. nenhuma migração destrutiva acontece sem plano de transição e prova dos dados.

### CAT-DOM-01C — Estratégia de migração

Status inicial: `PENDENTE`.

Estamos em validação do MVP, portanto **migrations são permitidas e este é o momento adequado para corrigir o domínio**, mas isso não autoriza perda desnecessária de dados.

Claude deve:

1. medir os dados atuais;
2. identificar duplicidades e dependências;
3. propor transformação dos registros atuais em produto + oferta;
4. definir migration/backfill apropriado;
5. manter migrations aditivas sempre que razoável;
6. evitar `migrate:fresh`;
7. preservar os dados de desenvolvimento existentes;
8. validar no MySQL real;
9. manter compatibilidade SQLite da suíte quando aplicável.

Se uma migration destrutiva for realmente necessária, **PARAR antes de executá-la** e apresentar justificativa, impacto e alternativa.

### CAT-DOM-01D — Implementação do modelo

Status inicial: `PENDENTE`.

Somente depois da arquitetura validada pela própria auditoria.

Possíveis entregáveis:

- `ProductOffer`;
- migration `product_offers`;
- relacionamentos Eloquent;
- constraints;
- índices;
- factories;
- seed/backfill;
- services/actions necessários.

Evitar lógica de negócio pesada em models.

### CAT-DOM-01E — Migração das superfícies comerciais

Status inicial: `PENDENTE`.

Migrar cuidadosamente os consumidores que realmente dependem da oferta:

- cadastro do lojista;
- preço;
- estoque;
- disponibilidade;
- catálogo público;
- carrinho;
- checkout;
- pedido;
- frete;
- API;
- demais superfícies encontradas na auditoria.

Não fazer busca/substituição mecânica de `Product` por `ProductOffer`.

Cada superfície deve responder conscientemente se está lidando com:

- identidade do produto;
- oferta;
- ambos.

### CAT-DOM-01F — Segurança e isolamento

Status inicial: `PENDENTE`.

Revalidar toda a SEC-02.

Criar testes explícitos garantindo, no mínimo:

- Expositor A não altera oferta de B;
- Expositor A não remove oferta de B;
- Expositor A não muda `expositor_id` de oferta existente;
- criação de oferta usa o expositor autenticado;
- acesso manipulado por URL/Livewire/API não atravessa tenant/expositor;
- produto mestre não vira caminho indireto para alterar dados comerciais de outro expositor;
- operações administrativas, se existirem, são autorizadas explicitamente.

### CAT-DOM-01G — Catalog Intelligence

Status inicial: `PENDENTE`.

Adaptar apenas o necessário para que a inteligência opere sobre a identidade correta.

Verificar:

- `catalog_product_knowledge`;
- `MatchProductKnowledge`;
- `AssociateProductKnowledge`;
- `FindSimilarProducts`;
- `ProductKnowledgeInput`;
- `SimilarityScorer`;
- comando de associação.

Decisão desejada: conhecimento global deve sobreviver à inativação de uma oferta.

Não iniciar CAT-05, CAT-06 ou geração de conteúdo nesta fase.

### CAT-DOM-01H — Testes, dados reais e documentação

Status inicial: `PENDENTE`.

Executar:

- testes unitários/feature específicos;
- regressão SEC-02;
- regressão CAT-02/03/04;
- suíte completa;
- validação no MySQL real;
- `git diff --check`;
- auditoria do diff integral.

Validar cenários de negócio reais:

#### Cenário 1 — saída do vendedor

```text
Produto P
Oferta A ativa
Expositor A ativo

→ Expositor A fica inativo

Esperado:
Produto P continua existindo
Conhecimento continua existindo
Oferta A deixa de ser comercializável conforme regra definida
```

#### Cenário 2 — novo vendedor do mesmo produto

```text
Produto P existente
Expositor B ativo

→ B passa a vender P

Esperado:
criar/ativar Oferta B
não transferir Oferta A
não apagar histórico
não duplicar conhecimento sem necessidade
```

#### Cenário 3 — produtos apenas semelhantes

```text
A vende Tapete de crochê artesanal X
B vende Tapete de crochê artesanal Y
```

O sistema não deve fundir automaticamente X e Y apenas porque a similaridade é alta.

---

## 12. Atualização obrigatória dos documentos

Durante a CAT-DOM-01, atualizar:

- `docs/ROADMAP.md`;
- `docs/ROADMAP_CATALOG_INTELLIGENCE.md`;
- `docs/CATALOG_INTELLIGENCE.md`;
- `README.md`, quando a arquitetura implementada alterar a descrição pública/técnica do projeto;
- este próprio documento, adicionando uma seção de **Resultado da Implementação** sem apagar a motivação histórica acima.

Não substituir a decisão original por uma descrição apenas do estado final. O objetivo deste arquivo é permitir que uma manutenção futura responda:

> "Por que separamos produto e oferta?"

---

## 13. Regra de atualização de status

Cada subfase deve aparecer no roadmap com status explícito:

```text
CAT-DOM-01A — PENDENTE / EM ANDAMENTO / CONCLUÍDA / BLOQUEADA
CAT-DOM-01B — PENDENTE / EM ANDAMENTO / CONCLUÍDA / BLOQUEADA
...
CAT-DOM-01H — PENDENTE / EM ANDAMENTO / CONCLUÍDA / BLOQUEADA
```

Nunca marcar fase como concluída apenas porque o código foi escrito.

`CONCLUÍDA` exige testes, revisão e evidência correspondente.

---

## 14. Restrições

Durante CAT-DOM-01:

- NÃO iniciar CAT-05;
- NÃO integrar IA externa;
- NÃO criar embeddings/vector DB;
- NÃO implementar feedback da CAT-07;
- NÃO criar UI administrativa da CAT-08;
- NÃO alterar regras de consentimento GOV-01;
- NÃO implementar GOV-02;
- NÃO enfraquecer SEC-02;
- NÃO transferir registros entre expositores como atalho;
- NÃO fazer merge automático de produtos semelhantes;
- NÃO apagar histórico comercial para simplificar migration;
- NÃO executar `migrate:fresh`;
- NÃO fazer refatoração oportunista fora do escopo;
- NÃO executar Pint global;
- NÃO commitar nem fazer push sem autorização explícita.

---

## 15. Procedimento de execução para Claude

Ao receber este documento como instrução de implementação:

1. confirmar branch/commit/working tree;
2. executar baseline completo antes da primeira alteração;
3. registrar contagens relevantes do banco;
4. executar CAT-DOM-01A integralmente;
5. atualizar documentação com os achados reais;
6. confrontar a arquitetura proposta neste documento com o código encontrado;
7. ajustar detalhes técnicos quando necessário, preservando a decisão comercial central;
8. implementar por subfase;
9. atualizar o status imediatamente após cada subfase comprovadamente concluída;
10. rodar testes dirigidos durante o desenvolvimento;
11. validar migrations no MySQL real;
12. executar suíte completa ao final;
13. revisar todo o diff;
14. executar `git diff --check`;
15. apresentar relatório final;
16. **PARAR antes de commit e push**.

Claude não precisa pedir confirmação para decisões técnicas ordinárias cobertas por este documento.

Deve parar e solicitar decisão humana somente se encontrar algo que:

- contradiga a decisão comercial central;
- exija perda/destruição de dados;
- altere significativamente checkout/pagamento/pedidos históricos;
- exija mudança incompatível de API;
- crie ambiguidade real entre produto idêntico, variante e produto semelhante que não possa ser resolvida com segurança pela auditoria.

---

## 16. Relatório final obrigatório

Ao concluir, responder com:

```text
CAT-DOM-01 — Relatório final

BASELINE
COMMIT INICIAL
WORKING TREE INICIAL
CONTAGENS DO BANCO

CAT-DOM-01A — status + achados
CAT-DOM-01B — status + decisões
CAT-DOM-01C — status + migrations/backfill
CAT-DOM-01D — status + implementação
CAT-DOM-01E — status + superfícies migradas
CAT-DOM-01F — status + segurança/SEC-02
CAT-DOM-01G — status + Catalog Intelligence
CAT-DOM-01H — status + validação

MODELO ANTES
MODELO DEPOIS

MIGRATIONS
DADOS MIGRADOS
DADOS PRESERVADOS

TESTES
baseline → final
assertions
failures

SEC-02
CAT-02
CAT-03
CAT-04

ARQUIVOS CRIADOS
ARQUIVOS ALTERADOS

RISCOS / DÍVIDAS
DECISÕES TOMADAS

GIT DIFF --CHECK
GIT STATUS

RECOMENDAÇÃO:
CAT-DOM-01 PRONTA / NÃO PRONTA PARA COMMIT

CAT-05 iniciada: NÃO
```

---

## 17. Critério de aceite da decisão de domínio

A fase só pode ser considerada bem-sucedida se, ao final, o sistema conseguir representar sem ambiguidade conceitual o seguinte cenário:

> Um produto pode continuar pertencendo ao catálogo e à memória da Feira mesmo quando o expositor que primeiro o comercializou saiu; outro expositor pode oferecer o mesmo produto sem assumir ou sobrescrever a oferta anterior, e o conhecimento acumulado continua disponível para a Catalog Intelligence.

Ao mesmo tempo:

> Produtos apenas semelhantes não devem ser fundidos automaticamente, ofertas permanecem isoladas por expositor e pedidos históricos permanecem íntegros.

---

## 18. Registro da razão histórica

Esta decisão foi tomada **antes da CAT-05**, quando se percebeu que construir o assistente de conteúdo sobre um modelo em que `Product` representa simultaneamente produto e vendedor poderia consolidar uma ambiguidade de domínio.

O exemplo decisivo foi simples:

> Um expositor A vende capas para celular e deixa a Feira. Um expositor B entra depois e vende a mesma capa. A capa não deixou de existir porque A saiu; mudou apenas quem a oferece.

O mesmo raciocínio vale para itens de artesanato reutilizáveis como referência de catálogo e conhecimento.

Por isso, decidiu-se interromper temporariamente a sequência CAT-04 → CAT-05 e introduzir a **CAT-DOM-01**, tratando primeiro a separação entre **produto mestre** e **oferta do expositor**.

Essa escolha pretende evitar que futuras funcionalidades de inteligência, recomendação e geração de conteúdo fiquem estruturalmente acopladas ao vendedor que originalmente cadastrou o item.

---

## 19. CAT-DOM-01A — Resultado da auditoria (2026-08-27)

> Esta seção registra **o que o código realmente é hoje**, antes de qualquer
> alteração. Ela não substitui as seções 1 a 18: a motivação comercial acima
> continua sendo a razão da fase.

### 19.1 Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| Commit | `3cab7e2` — CAT-04 |
| Working tree | Limpa (apenas este documento, não rastreado) |
| Containers | 8 no ar (`app`, `nginx`, `mysql`, `phpmyadmin`, `redis`, `node`, `queue`, `mailpit`) |
| **Suíte** | **577 passed · 1568 assertions · 0 failures** |
| Duração | 592,07s no container `app` |

### 19.2 Contagens do banco de desenvolvimento (MySQL real)

| Tabela | Registros |
|---|---:|
| `products` | 75 (28 produto · 24 serviço · 23 cuidado) |
| `expositores` | 14 |
| `orders` | 0 |
| `order_items` | 0 |
| `cart_items` | 0 |
| `catalog_knowledge_terms` | 30 |
| `catalog_product_knowledge` | 0 |
| `product_questions` | 0 |
| `product_faqs` | 0 |

Medições que decidem a estratégia de migração:

| Medição | Resultado | Consequência |
|---|---:|---|
| Produtos sem `expositor_id` | 0 | Todo registro atual gera exatamente uma oferta |
| Produtos sem `price` | 0 | Backfill de preço não tem caso nulo a tratar |
| Produtos inativos | 0 | Nenhum estado ambíguo de status a interpretar |
| Nomes repetidos **entre lojas** | 0 | **Nenhuma deduplicação é necessária nem permitida** (§9) |
| Produtos ativos de expositor inativo | 0 | O risco do §19.5-B é estrutural, não presente nos dados |
| Pedidos e itens de carrinho existentes | 0 | Não há histórico comercial em risco no ambiente de dev |

> Conclusão da medição: o backfill correto é **1 produto → 1 oferta**, sem
> fusão, sem escolha heurística e sem perda. A fase cria a *capacidade* de um
> produto mestre ter várias ofertas; ela **não** faz nenhum produto existente
> passar a ser compartilhado. Compartilhamento efetivo exige curadoria de
> identidade (idêntico × variante × semelhante) e fica fora desta fase por
> força do §9.

### 19.3 Estado atual de `products`

26 colunas, `AUTO_INCREMENT=171`, FKs para `expositores` (`nullOnDelete`) e
`content_categories` (`nullOnDelete`), `slug` **UNIQUE global**.

### 19.4 Matriz de campos (§11, CAT-DOM-01A)

| Campo atual de `Product` | Global do produto? | Específico da oferta? | Ambíguo? | Consumidores | Destino proposto |
|---|:--:|:--:|:--:|---|---|
| `item_type` | ✔ | | | catálogo por eixo, `scopeDoEixo`, `isShippable`, formulário | `products` |
| `name` | ✔ | | | tudo; base do matcher CAT-04 | `products` |
| `slug` | ✔ | | | rota `/loja/{loja}/{produto}` | `products` (unique global mantido) |
| `short_description` | ✔ | | | cards, API, matcher (CAT-02) | `products` |
| `description` | ✔ | | | página do item, matcher | `products` |
| `category_id` | ✔ | | | filtros, peso de categoria no `SimilarityScorer` | `products` |
| `expositor_id` | | ✔ | | `ProdutoIndex`/`ProdutoForm`, API lojista, loja pública, share, frete, `CartService` | **`product_offers.expositor_id`** |
| `price` | | ✔ | | `CartService::add` → `price_snapshot`, checkout, `ProductResource`, cards | **`product_offers.price`** |
| `price_type` | | ✔ | | serviços e cuidados | **`product_offers`** |
| `has_stock` | | ✔ | | formulário, API, vitrine | **`product_offers`** |
| `stock_quantity` | | ✔ | | formulário, API | **`product_offers`** |
| `weight` `height` `width` `length` | | ✔ | | `ValidatesShippableItems`, Melhor Envio, Frenet | **`product_offers`** — quem embala e despacha é o expositor |
| `sort_order` | | ✔ | | ordenação da vitrine da loja e do catálogo | **`product_offers`** |
| `is_featured` | | ✔ | | `Product::featured()` na home | **`product_offers`** — destaque é da vitrine de quem vende |
| `is_active` | ✔ | ✔ | ⚠ | catálogo, loja, API, `FindSimilarProducts` | **desdobra em dois**: `products.is_active` (item publicável no catálogo) + `product_offers.is_active` (oferta comercializável) |
| `modality` | | | ⚠ | serviços e cuidados: presencial × online | oferta — modalidade é como *aquele* prestador atende |
| `duration_min` | | | ⚠ | serviços e cuidados | oferta — pelo mesmo motivo |
| `is_digital` | ✔ | | ⚠ | dispensa frete; cria `AvaCourse` | `products` — muda a natureza do item, não a condição de venda |
| `images` / `image_path` | | | ⚠ | cards, imagem de compartilhamento, API | `products` **nesta fase**; ver dívida D-2 |

Relações que hoje penduram em `product_id`:

| Relação | Natureza | Destino proposto |
|---|---|---|
| `catalog_product_knowledge` | conhecimento de catálogo | **`products`** — já está correto; nada a mudar (§8) |
| `product_faqs` | texto autoral do lojista | `products` nesta fase; dívida D-2 |
| `product_questions` | pergunta do cliente na página da loja, respondida pelo lojista | `products` nesta fase; o isolamento hoje é `whereHas('product', expositor_id)` e precisa acompanhar a oferta |
| `ava_courses` | conteúdo autoral de um vendedor | `products` nesta fase (1:1 garantido pelo backfill); dívida D-2 |
| `cart_items` | já grava `expositor_id` e `price_snapshot` | acrescentar `product_offer_id` nullable |
| `order_items` | já grava `expositor_id`, `product_name`, `unit_price` | acrescentar `product_offer_id` nullable — **o histórico já é snapshot e permanece íntegro** |
| `ci_events` | morph `entity_type`/`entity_id` | nada a mudar |

### 19.5 Achados

**A — O histórico comercial já está protegido por snapshot.** `cart_items` grava
`expositor_id` e `price_snapshot`; `order_items` grava `expositor_id`,
`product_name`, `unit_price` e `total_price`. A invariante 6 do §11B já é
atendida pelo desenho atual: nenhum pedido depende do estado vivo do produto.
Acrescentar `product_offer_id` é aditivo e não reabre risco.

**B — Visibilidade pública é incoerente entre superfícies.** O catálogo por eixo
(`/produtos`, `/servicos`, `/cuidados`) filtra apenas `products.is_active` e
**não** olha o status do expositor; já `/loja/{slug}` e `/loja/{slug}/{produto}`
exigem `expositor.is_active`. Um item de loja inativa aparece na listagem e dá
404 ao ser clicado. O mesmo vale para `Product::featured()` na home. Este é o
achado que motivou a fase, e a regra correta é decisão de produto — tratada na
CAT-DOM-01B.

**C — `expositor_id` é `nullOnDelete`.** Excluir um expositor deixa produtos
órfãos, ativos e listados no catálogo, com página de loja inalcançável. É a
mesma incoerência de B, por outro caminho.

**D — `FindSimilarProducts` filtra `p.is_active` mas não o expositor.** É
coerente com a decisão do §8 (conhecimento é global), mas pode devolver um item
cuja página pública responde 404. Relevante para a CAT-05, não para esta fase.

**E — A regra de cadastro está duplicada.** `ProdutoForm::save()` (Livewire) e
`ProdutoController::buildData()` (API) montam o mesmo array de campos em dois
lugares — dívida #2 do roadmap da trilha. Dividir produto e oferta **dobraria**
essa duplicação; por isso a CAT-DOM-01D extrai uma action compartilhada em vez
de repetir a divisão nos dois pontos.

**F — SEC-02 está implementada em três camadas independentes**, e todas as três
migram junto: `ProdutoForm::guardOwnership()` (chamado em `mount`, `save` e
`removeImage`), `ProdutoController::authorizeProduct()` (API) e o escopo
`Product::where('expositor_id', ...)` em `ProdutoIndex`. `expositor_id` fica
deliberadamente fora do `$data` de update nos dois pontos — essa proteção
precisa ser reproduzida literalmente em `product_offers`.

**G — O conhecimento já está no lugar certo.** `catalog_product_knowledge`
aponta para `products`, nunca para expositor. A invariante 7 do §11B já é
estruturalmente satisfeita: desativar uma oferta não toca no pivot. A
CAT-DOM-01G tende a ser fase de **verificação**, não de reescrita.

### 19.6 Dívidas identificadas

| # | Dívida | Severidade | Tratamento |
|---|---|---|---|
| D-1 | Colunas comerciais permanecem em `products` depois da separação | Média | Migration aditiva agora; remoção é destrutiva e exige decisão humana (§15) |
| D-2 | Imagens, FAQs, perguntas e curso AVA são autorais do vendedor mas ficam no produto mestre | Média | Inofensivo enquanto o backfill for 1:1; vira problema real na primeira fusão de produtos |
| D-3 | Regra de cadastro duplicada entre Livewire e API | Média | Action compartilhada na CAT-DOM-01D |

---

## 20. CAT-DOM-01B — Arquitetura e invariantes (2026-08-27)

### 20.1 Decisões humanas tomadas antes do desenho

| # | Questão | Decisão |
|---|---|---|
| H-1 | Item de expositor inativo nas superfícies públicas | **Sai das vitrines.** A oferta deixa de ser listada e de ser comprável em `/produtos`, `/servicos`, `/cuidados` e nos destaques da home. O produto mestre e o conhecimento permanecem no banco e voltam a aparecer quando outro expositor criar uma oferta vigente. |
| H-2 | Colunas comerciais legadas em `products` | **Permanecem, sem remoção nesta fase.** Nenhuma migration destrutiva é executada. A limpeza fica registrada como dívida D-1. |

H-1 é a implementação literal do Cenário 1 do §11H e resolve o achado §19.5-B.

### 20.2 Responsabilidades finais

```text
Product — identidade de catálogo
  item_type · name · slug · short_description · description
  category_id · images · image_path · is_digital · is_active

ProductOffer — relação comercial
  product_id · expositor_id · price · price_type · modality · duration_min
  weight · height · width · length · has_stock · stock_quantity
  is_active · is_featured · sort_order
```

`modality` e `duration_min` ficam na oferta porque descrevem **como aquele
prestador atende** — o mesmo cuidado pode ser presencial em uma loja e online em
outra. `is_digital` fica no produto porque muda a natureza do item (dispensa
frete, gera curso), não a condição de venda.

### 20.3 Cardinalidade, unicidade e ciclo de vida

| Regra | Definição |
|---|---|
| Cardinalidade | `Product` 1..N `ProductOffer`; toda oferta pertence a **exatamente um** expositor |
| Unicidade | `unique(product_id, expositor_id)` — um expositor tem no máximo uma oferta por produto |
| `product_id` | FK `cascadeOnDelete` — oferta sem produto não significa nada |
| `expositor_id` | FK `cascadeOnDelete` — excluir o expositor apaga suas ofertas, **não** os produtos; o catálogo e o conhecimento sobrevivem, e é exatamente o §19.5-C resolvido |
| Cardinalidade real nesta fase | **1:1.** O backfill não funde nada e o cadastro cria produto + oferta juntos |

### 20.4 Regra de visibilidade pública (H-1)

```text
oferta vigente  ⇔  offer.is_active
                 ∧ expositor.is_active
                 ∧ product.is_active

produto visível nas vitrines  ⇔  existe ao menos uma oferta vigente
```

Aplicada em: catálogo por eixo, destaques da home, vitrine da loja, página do
item, API de catálogo, carrinho e frete. **Não** aplicada à Catalog Intelligence
— §20.8.

Um produto sem nenhuma oferta vigente continua existindo, continua indexado pelo
conhecimento e **não aparece** em nenhuma superfície comercial.

### 20.5 Invariantes (§11B) e onde cada uma é garantida

| # | Invariante | Garantia |
|---|---|---|
| 1 | Inativar expositor não destrói conhecimento global | `catalog_product_knowledge` referencia `products`; nada em cascata alcança o pivot |
| 2 | Uma oferta pertence a exatamente um expositor | `expositor_id` NOT NULL + `unique(product_id, expositor_id)` |
| 3 | Lojista não altera oferta de outro lojista | `expositor_id` fora do `$data` de update; guard reproduzido nas três camadas do §19.5-F |
| 4 | Produto mestre não troca de expositor | O produto deixa de ter dono; a propriedade vive na oferta |
| 5 | Checkout resolve uma oferta válida | `cart_items.product_offer_id`; carrinho recusa oferta não vigente |
| 6 | Pedido histórico permanece íntegro | `order_items` já grava snapshot de nome, preço e expositor (§19.5-A) |
| 7 | Conhecimento sobrevive à desativação de oferta | O pivot não olha oferta nem expositor |
| 8 | Nenhuma migração destrutiva sem plano e prova | Migrations aditivas; H-2 proíbe remoção nesta fase |

### 20.6 Compatibilidade de contratos

Nenhum contrato externo muda — o app Flutter não precisa de nova versão:

- `ProductResource` continua expondo `price`, `has_stock`, `stock_quantity`,
  `weight`, `height`, `width`, `length`, `is_featured` — lidos **da oferta**;
- `/api/v1/lojista/produtos` continua recebendo os mesmos campos e passa a
  gravar produto + oferta na mesma transação;
- `POST /shipping/quote` continua recebendo `store_id` + `product_id[]`, e
  resolve internamente a oferta `(product_id, store_id)`;
- as rotas `/loja/{loja}` e `/loja/{loja}/{produto}` mantêm as mesmas URLs:
  o par expositor + slug do produto passa a resolver **uma oferta**.

`products.slug` continua UNIQUE global — é a identidade do item de catálogo, e a
URL já é qualificada pela loja.

### 20.7 Escrita: um único ponto, espelho consistente

A dívida D-3 (regra de cadastro duplicada entre `ProdutoForm` e a API) é
resolvida na 01D com uma action compartilhada, que grava produto e oferta na
mesma transação. Como H-2 mantém as colunas comerciais em `products`, essa
action **também as atualiza em espelho**, para que nenhuma coluna do banco
guarde valor mentiroso enquanto a remoção não vier.

> O espelho é válido **enquanto a cardinalidade real for 1:1**. No dia em que um
> segundo expositor ofertar o mesmo produto mestre, ele perde sentido e as
> colunas legadas precisam ser removidas. Isso está registrado na dívida D-1 e
> é pré-requisito de qualquer fase futura de curadoria de identidade.

Leitura é sempre da oferta. Nenhuma superfície volta a ler preço, estoque,
dimensões ou `expositor_id` de `products`.

### 20.8 Catalog Intelligence

Nada muda de estrutura. `catalog_product_knowledge`, `MatchProductKnowledge`,
`AssociateProductKnowledge`, `ProductKnowledgeInput` e `SimilarityScorer` já
operam sobre identidade de catálogo — nome, resumo, descrição e categoria — e
nenhum deles lê preço, estoque ou dono.

`FindSimilarProducts` filtra `p.is_active`, que continua sendo o status do
**produto**: a similaridade não é afetada pela desativação de uma oferta, que é
o comportamento desejado pelo §8. A 01G verifica isso com teste, sem reescrita.

---

## 21. CAT-DOM-01C — Migração executada

Três migrations, todas **aditivas**. Nenhuma coluna removida, nenhum registro
apagado, `migrate:fresh` não executado.

| Migration | O que faz |
|---|---|
| `2026_08_27_100001_create_product_offers_table` | Cria `product_offers` com `unique(product_id, expositor_id)`, índices de vitrine e FKs `cascadeOnDelete` |
| `2026_08_27_100002_backfill_product_offers_from_products` | 1 produto → 1 oferta, via `insertOrIgnore` em blocos de 200. Produto órfão (sem `expositor_id`) não gera oferta |
| `2026_08_27_100003_add_product_offer_id_to_commercial_tables` | `product_offer_id` nullable em `cart_items` e `order_items`, `nullOnDelete`, com backfill por `(product_id, expositor_id)` |

**Resultado no MySQL real:**

| Verificação | Resultado |
|---|---:|
| Ofertas criadas | 75 |
| Produtos cobertos | 75 de 75 |
| Produtos sem oferta | 0 |
| Lojas com oferta | 14 |
| Divergências entre produto e oferta em preço, expositor, estoque, status, destaque, tipo de preço, modalidade e peso | **0** |

`expositor_id` em `product_offers` é `cascadeOnDelete` de propósito: excluir um
expositor apaga as ofertas dele e **não toca nos produtos**. O item permanece no
catálogo, o conhecimento permanece associado a ele, e ele apenas deixa de ter
quem o venda — a decisão comercial desta fase, em uma linha de schema.

## 22. CAT-DOM-01D — Modelo implementado

| Arquivo | Papel |
|---|---|
| `app/Models/ProductOffer.php` | Oferta; `scopeVigente()` concentra a regra de visibilidade |
| `app/Models/Product.php` | `offers()`, `ofertaVigente()`, `scopeComOfertaVigente()`, `scopeOrdenadoPelaVitrine()`; `expositor()` marcada `@deprecated` |
| `app/Actions/Catalog/SaveProductWithOffer.php` | Único ponto que transforma um cadastro em produto + oferta |
| `database/factories/ProductOfferFactory.php` | Ofertas de teste, com estados `inativa()`, `destacada()`, `comLogistica()` |
| `database/factories/ProductFactory.php` | `configure()` cria a oferta junto; `semOferta()` produz o item órfão |
| `database/seeders/Concerns/SincronizaOfertaDoItem.php` | Mantém a oferta alinhada nos seeders idempotentes |

A dívida D-3 (regra de cadastro duplicada entre Livewire e API) foi resolvida:
as duas superfícies continuam montando o mesmo array plano de sempre, e a
divisão entre identidade e condição de venda acontece uma vez só, na action.

## 23. CAT-DOM-01E — Superfícies migradas

| Superfície | O que passou a ler a oferta |
|---|---|
| Catálogo por eixo, home | `Product::doEixo()` e `featured()` exigem oferta vigente; card mostra preço e loja da oferta |
| Vitrine da loja | Lista `ProductOffer` do expositor |
| Página do item | A URL `loja + slug` resolve **uma oferta**; preço, estoque e relacionados vêm dela |
| Painel do lojista | `ProdutoIndex` lista ofertas; `ProdutoForm` edita a oferta do lojista autenticado |
| API de catálogo e de loja | Mesmo JSON de sempre, lido da oferta |
| API do lojista | CRUD via `SaveProductWithOffer` |
| Carrinho | `CartService::add(ProductOffer)`; `cart_items.product_offer_id`; a linha do carrinho é a oferta |
| Pedido | `order_items.product_offer_id`; snapshots preservados |
| Frete | Peso, dimensões e valor segurado vêm da oferta, nos dois provedores |
| Imagem de compartilhamento | Recebe a oferta — a arte mostra preço e loja |
| Perguntas, cursos AVA | Isolamento por `whereHas('offers')`, não mais por `products.expositor_id` |

**Mudança de comportamento deliberada:** excluir um item no painel ou pela API
remove a **oferta**, não o produto. O item continua no catálogo, com descrições,
imagens e conhecimento, pronto para quando alguém voltar a oferecê-lo.

Nenhum contrato externo mudou: `/api/v1` mantém os mesmos campos, e
`POST /shipping/quote` continua recebendo `store_id` + `product_id`.

## 24. CAT-DOM-01F — Segurança

A SEC-02 mudou de alvo, não de rigor. Onde se perguntava *"este produto é
seu?"*, pergunta-se *"você tem uma oferta sobre ele?"*. As três camadas do
§19.5-F migraram juntas, e `expositor_id` continua fora de todo update.

Provas novas em `tests/Feature/ProdutoMestreOfertaTest.php`:

- lojista não abre nem edita oferta alheia pelo formulário;
- lojista não remove oferta alheia pela listagem;
- **produto compartilhado não dá acesso à oferta alheia** — com duas ofertas
  sobre o mesmo produto mestre, cada lojista só alcança a sua;
- payload da API não escolhe o dono na criação nem transfere na edição;
- `unique(product_id, expositor_id)` impede oferta duplicada.

Os 21 testes da `CatalogoIsolamentoTest` continuam passando sem afrouxamento.

## 25. CAT-DOM-01G — Catalog Intelligence

Nenhuma alteração foi necessária, como a auditoria previu: o pivot
`catalog_product_knowledge` sempre apontou para `products`, e nenhum componente
do módulo lê preço, estoque ou dono.

Verificado com o motor da CAT-04 sobre os 75 itens reais — **75 analisados, 45
com evidência direta, 30 sem**, idêntico ao baseline da CAT-04. E com teste:
desativar a oferta de um item não remove o conhecimento dele nem o tira dos
resultados de `FindSimilarProducts`.

## 26. CAT-DOM-01H — Validação

| | Baseline | Final |
|---|---:|---:|
| Testes | 577 passed | **590 passed** |
| Assertions | 1568 | **1609** |
| Falhas | 0 | **0** |

Os três cenários de negócio do §11H estão cobertos por teste:

| Cenário | Prova |
|---|---|
| 1 — saída do vendedor | Produto e conhecimento sobrevivem; o item sai das vitrines; catálogo e página da loja passam a concordar |
| 2 — novo vendedor do mesmo produto | Segunda oferta criada sem transferir, sobrescrever ou apagar a primeira |
| 3 — apenas semelhantes | Itens de mesmo nome em lojas diferentes continuam sendo produtos distintos |

Validação no MySQL real: `/`, `/produtos`, `/servicos`, `/cuidados`,
`/loja/{slug}` e `/loja/{slug}/{produto}` respondem 200 com os dados de
desenvolvimento; `/api/v1/produtos` e `/api/v1/lojas/{slug}` devolvem o mesmo
JSON de antes, com preço e dimensões vindos da oferta.

## 27. Dívidas em aberto ao fim da fase

| # | Dívida | Situação |
|---|---|---|
| D-1 (**BLOCKER BEFORE MULTI-OFFER**) | Colunas comerciais permanecem em `products` | **Decisão humana H-2**: ficam, sem remoção. A `SaveProductWithOffer` as mantém em espelho para que nenhuma coluna guarde valor diferente do que a oferta cobra. O espelho só vale enquanto a cardinalidade real for 1:1 — antes da primeira fusão de produtos, as colunas precisam sair |
| D-2 (**BLOCKER BEFORE MULTI-OFFER**) | Imagens, FAQs, perguntas e curso AVA ficam no produto mestre, e campos de identidade são graváveis por qualquer ofertante | Inofensivo enquanto o backfill for 1:1; vira problema real quando dois expositores compartilharem um produto — ver §29.5 |
| D-3 | ~~Regra de cadastro duplicada~~ | **Resolvida** pela `SaveProductWithOffer` |

## 28. O que esta fase deliberadamente não fez

Nenhum produto existente passou a ser compartilhado: a fase entrega a
**capacidade** de um item de catálogo ter várias ofertas. Decidir que dois
registros são o mesmo item — e não variantes ou peças apenas semelhantes — é
curadoria humana, e o §9 a mantém fora daqui.

Também não foram tocados: CAT-05, IA externa, embeddings, GOV-01/GOV-02, regras
de consentimento, ou qualquer coisa que enfraqueça a SEC-02.

---

## 29. Revisão pré-commit (2026-08-27)

A implementação foi submetida a uma revisão dirigida antes do commit. A suíte
verde não foi aceita como prova suficiente: a revisão procurou fonte de verdade
trocada, divergência de espelho, escrita não atômica, IDOR novo e histórico de
pedido quebrável.

### 29.1 Achados corrigidos

| # | Achado | Severidade | Correção |
|---|---|---|---|
| R-1 | **A home lia o espelho legado.** Os três blocos de destaque de `welcome.blade.php` usavam `$product->price`, `$item->price_type` e a relação `Product::expositor` — a página mais visível do site lendo colunas que deixaram de ser fonte de verdade. Como as consultas passaram a carregar `ofertaVigente.expositor`, a leitura de `->expositor` ainda produzia um N+1 de até 34 consultas por render | **HIGH** | Os três blocos passaram a ler `$product->ofertaVigente`; o N+1 desapareceu junto |
| R-2 | **A lista de cursos do lojista lia `$product->price`** | MEDIUM | `CursoIndex` passou a carregar a oferta do lojista e a view lê o preço dela |
| R-3 | **"Ver Minha Loja" apontava para o expositor legado** do produto (`ProdutoForm`), e não para a loja de quem edita | MEDIUM | Passou a usar `$offer->expositor` |
| R-4 | **`ProdutoIndex::toggleActive` escrevia oferta e espelho fora de transação.** Falha na segunda escrita deixaria oferta inativa e `products.is_active` verdadeiro — exatamente a divergência que o espelho existe para evitar | **HIGH** | Envolvido em `DB::transaction` |
| R-5 | **`CartService::reassignSession` comparava `product_offer_id` com `null`.** Em SQL, `= NULL` nunca casa: um item de carrinho anterior à fase, sem oferta gravada, deixaria de ser mesclado no login e viraria linha duplicada | MEDIUM | `whereNull` explícito quando a oferta é nula |

Depois das correções, **nenhuma superfície comercial lê preço, estoque,
dimensões ou dono de `products`**.

### 29.2 Leitores remanescentes do legado

Três telas do AVA ainda exibem `Product::expositor` — certificado PDF, e-mail de
certificado e "Meu Aprendizado" — sempre como *autor do curso*, nunca para
autorizar nada:

```text
resources/views/ava/certificado-pdf.blade.php
resources/views/emails/ava/certificate.blade.php
resources/views/livewire/cliente/ava/aprendizado-index.blade.php
```

Ficam como estão **de propósito**: o curso pende de `products` (dívida D-2), e
decidir a que oferta um curso pertence é decisão de produto que esta fase não
tomou. O comportamento é idêntico ao de antes da fase.

### 29.3 Teste controlado do espelho

```text
UPDATE direto em ProductOffer.price  →  999.99
products.price                       →  0.01   (inalterado)
```

O espelho **não é bidirecional nem automático**: ele é mantido pela
`SaveProductWithOffer`, pelo `toggleActive` e pelo trait dos seeders — os três
caminhos de escrita que a aplicação realmente usa. Escrita direta no model, em
código futuro, não propaga.

É por isso que R-1 era HIGH e não cosmético: enquanto a home lesse o espelho,
qualquer escrita direta em oferta produziria preço errado na vitrine. Sem
leitores, o espelho passa a ser apenas um registro morto, e sua remoção deixa de
ter risco.

### 29.4 Integridade no MySQL real

| Invariante | Esperado | Encontrado |
|---|---:|---:|
| `products` | 75 | 75 |
| `product_offers` | 75 | 75 |
| Produtos sem oferta | 0 | 0 |
| Ofertas sem produto | 0 | 0 |
| Ofertas sem expositor | 0 | 0 |
| Produtos com 2 ofertas | 0 | 0 |
| Divergência em preço, estoque, `has_stock`, expositor, status, destaque, ordem, tipo de preço, modalidade, duração, peso, altura, largura e comprimento | 0 em cada | **0 em cada** |

Chaves estrangeiras conferidas no schema real: `product_offers` → `products` e
→ `expositores` em `CASCADE` (intencional, §20.3); `order_items.product_offer_id`
→ `SET NULL` (histórico preservado).

### 29.5 Multi-oferta: estrutura pronta, funcionalidade não exposta

> **Hoje dois expositores podem vender o mesmo `Product`? NÃO.**

O schema suporta (`unique(product_id, expositor_id)` admite N ofertas por
produto), mas **nenhum caminho da aplicação cria uma segunda oferta sobre um
produto existente**: `SaveProductWithOffer` só cria oferta junto com um produto
novo, e não há tela nem endpoint de "passar a vender um item do catálogo".
Coberto por teste — dois lojistas cadastrando o mesmo nome produzem **dois
produtos**, não duas ofertas.

Quando um teste força o cenário compartilhado, aparece o limite real: **um
expositor com oferta sobre o produto consegue alterar nome, descrição e imagens
do produto mestre**, afetando o outro. Está registrado em teste, e é o motivo de
D-2 bloquear multi-oferta.

### 29.6 Dívidas com condição de liberação

| # | Dívida | Classificação |
|---|---|---|
| D-1 | Colunas comerciais legadas em `products`, mantidas em espelho | **BLOCKER BEFORE MULTI-OFFER** — com N ofertas por produto, `products.price`, `stock_quantity` e `expositor_id` deixam de ter significado; sem leitores desde a revisão, a remoção é segura e barata |
| D-2 | Identidade autoral no produto mestre — imagens, FAQs, perguntas, curso AVA — e campos de identidade graváveis por qualquer ofertante | **BLOCKER BEFORE MULTI-OFFER** — exige autoria por oferta ou curadoria antes de dois expositores compartilharem um produto |
| D-3 | Regra de cadastro duplicada entre Livewire e API | **RESOLVIDA** — `SaveProductWithOffer` é o único ponto de escrita; provado pelo teste de espelho, que exercita o formulário e verifica os dois lados |

### 29.7 Riscos preexistentes confirmados (fora do escopo desta fase)

Nenhum destes foi introduzido pela CAT-DOM-01, e nenhum foi alterado por ela:

| Risco | Situação |
|---|---|
| `order_items.expositor_id` é `CASCADE`: excluir um expositor apaga os itens de pedido dele | Preexistente desde a criação da tabela. Merece item próprio — a integridade do histórico não deveria depender do cadastro do vendedor |
| Estoque nunca é decrementado nem validado no checkout | Preexistente; `stock_quantity` é informativo. A fase apenas mudou onde o número mora |
| `products.slug` é UNIQUE global e o cadastro não desambigua | Preexistente; dois lojistas cadastrando o mesmo nome colidem no slug. Relevante para multi-oferta |

### 29.8 Suíte após a revisão

| | Baseline | Antes da revisão | Depois |
|---|---:|---:|---:|
| Testes | 577 | 590 | **594** |
| Assertions | 1568 | 1609 | **1626** |
| Falhas | 0 | 0 | **0** |

Os quatro testes novos provam espelho consistente após salvar, oferta e espelho
alternando juntos, item de pedido legível depois de a oferta ser removida, e
multi-oferta não alcançável pelo cadastro.
