# CAT-05B — Decisões de produto e contratos

> **Subfase de decisão.** O produto principal desta subfase é o que está escrito
> aqui: duas decisões de produto que a CAT-05A levantou como blockers, e os
> contratos que a CAT-05C em diante vai implementar. O único código alterado é o
> fechamento da dívida **M-17**, que já estava endereçada nominalmente a esta
> fase antes de a auditoria começar.

Auditoria que originou esta subfase:
[`CAT_05A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_05A_AUDITORIA_DE_RECONCILIACAO.md).
Decisões de domínio a que este documento se subordina:
[`CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `8c84517582f9914d354b3745e278d075d17c0d1b` |
| Working tree no início | Limpo |
| Suíte no início | 1048 passed · 3117 assertions · 0 failures |
| Data | 2026-09-01 |

Banco de desenvolvimento: MySQL 8.4, 75 produtos, 75 ofertas, 14 expositores,
28 conceitos, **0 associações** em `catalog_product_knowledge`, **0 expositores
inativos**, **0 ofertas inativas**.

O último número importa para entender a M-17: **nenhum dado do ambiente de
desenvolvimento manifestava o defeito.** Ele era invisível por sorte do dado, e
teria continuado invisível até a primeira loja sair da Feira.

---

## 2. D-CAT-05B-1 — A CAT-05 sugere, nunca aplica

**Contexto.** A especificação original da CAT-05 é anterior à CAT-DOM-02C. O
critério de sucesso da §8 do documento arquitetural termina em *"aplica
seletivamente → edita → salva normalmente"*, escrito quando `Product` não tinha
autoridade canônica e qualquer lojista com uma oferta editava a identidade do
item.

**Problema.** Hoje `name`, `short_description` e `description` são canônicos
(`Product::CAMPOS_CANONICOS`) e `product_offers` **não tem nenhuma coluna de
texto** — a auditoria confirmou coluna a coluna. Aplicar uma sugestão a um item
existente atravessa `ProductPolicy::updateCanonical` e pode terminar em
`SemAutoridadeCanonica`. A CAT-05 herdaria, sem ter pedido, uma decisão de
autorização e de UX.

**Alternativas.** (A) a fase aplica, e resolve a autoridade junto; (B) a fase
apenas sugere, e a aplicação fica com quem tem a tela; (C) a fase aplica só na
criação, onde não há atrito.

**Decisão. (B).** A CAT-05 termina no `ListingSuggestion`. Nenhum caminho da fase
escreve em `Product`, chama `SaveProductWithOffer` ou aciona
`ProductPolicy::updateCanonical`. A sugestão é sempre pré-visualização.

**Justificativa.** (A) faria a fase decidir, de passagem, o que acontece com o
lojista sem delegação canônica que pede uma sugestão para o próprio item — e
essa é uma pergunta de produto com resposta em tela, não em Action. (C) parece
econômica e é a pior: criaria dois comportamentos para a mesma sugestão, um na
criação e outro na edição, e a diferença só apareceria para o usuário no
momento em que ele fosse recusado.

A decisão também recoloca a fase onde a regra 2 da §1 já a colocava: **gerar
nunca é salvar**. O que muda é que agora isso é fronteira de fase, e não só
princípio.

**Consequências positivas.** A CAT-05 fica testável sem nenhuma escrita: "gerar
≠ salvar" vira asserção direta, não convenção. A fase não abre nenhuma
superfície de autorização nova, e a SEC-02 e a D-CAT-09 não são tocadas.

**Consequências negativas.** O critério de sucesso da §8 não é atingido pela
CAT-05 sozinha — ele atravessa CAT-05 + CAT-09. Precisa estar dito, e está.

**Riscos.** Baixo. O risco real seria o oposto: implementar aplicação agora e
descobrir na CAT-09 que a tela precisa de outra semântica.

**Impacto.** A **CAT-09** herda, explicitamente: o que a interface faz quando o
lojista sem delegação canônica aplica uma sugestão que muda `name`,
`short_description` ou `description`. As opções conhecidas — recusar antes de
mostrar, mostrar e recusar ao salvar, ou abrir caminho de proposta (G-3) — são
decisão daquela fase.

---

## 3. D-CAT-05B-2 — A similaridade só oferece o que está vigente

**Contexto.** `FindSimilarProducts` filtrava `products.is_active` e o seu
docblock afirmava que isso era *"o mesmo que qualquer visitante vê em
`/produtos` e `/loja/{slug}`"*.

**Problema.** Deixou de ser verdade na CAT-DOM-01, e a D-CAT-10 completou a
inversão: `products.is_active` passou a significar **validade canônica sob
curadoria**, não visibilidade. Quem responde pela visibilidade é
`ProductOffer::scopeVigente()` — oferta ativa, produto ativo e expositor ativo.
Um item com produto ativo e oferta desligada continuava sendo devolvido como
sugestão, e a sua página pública responde 404.

Isto não é achado novo: é a dívida **M-17**, registrada como *Aberta* na tabela
da CAT-DOM-02B, e endereçada nominalmente a esta fase pela CAT-DOM-01 — *"pode
devolver um item cuja página pública responde 404. **Relevante para a CAT-05**,
não para esta fase."*

**Alternativas.** (A) manter e documentar a limitação; (B) exigir vigência dos
dois lados, origem e vizinho; (C) exigir vigência apenas de quem é **oferecido**
como semelhante.

**Decisão. (C).** O filtro passou a ser
`Product::scopeComOfertaVigente()`, que delega ao escopo da oferta. A origem da
consulta **não** é filtrada.

**Justificativa.** (A) constrói a CAT-05 inteira sobre um motor que lê o eixo
errado, e a primeira loja que sair da Feira transforma isso em sugestão quebrada
na tela de outro lojista. (B) contradiz a **D-CAT-21**: um `Product` sem oferta
é preservado, ativo no catálogo interno e na Catalog Intelligence — filtrar os
dois lados faria o item que perdeu o vendedor perder também o acesso ao
conhecimento acumulado, que é exatamente o que a CAT-DOM-01 existiu para
impedir.

(C) separa as duas perguntas que estavam coladas: *quem pode pedir referência?*
— qualquer item do catálogo interno — e *quem pode ser oferecido como
referência?* — só o que alguém está vendendo. Sugerir o que ninguém vende é
sugerir um 404.

**Por que agora, e não numa subfase de implementação.** Não é escopo novo: é
dívida com o nome desta fase escrito nela desde a CAT-DOM-01. Construir
`ListingAssistant` sobre um motor que lê `is_active` como se fosse visibilidade
consolidaria o erro em mais uma camada, e a correção depois custaria as duas.

**A regra não foi reescrita.** A consulta pede o escopo do domínio em vez de
repetir "oferta ativa + produto ativo + expositor ativo". Repetir criaria a
segunda definição de vigência que a CAT-DOM-01 eliminou — e é assim que o
catálogo por eixo e a página da loja voltariam a divergir, agora com a
similaridade como terceira voz.

**Consequências positivas.** O docblock da classe voltou a ser verdadeiro. A
CAT-05C em diante constrói sobre um motor que lê o eixo certo. M-17 fecha.

**Consequências negativas.** O conjunto de vizinhos possíveis encolhe — no banco
atual, em nada (75 de 75 estão vigentes), mas encolherá no dia em que houver
loja inativa. É o comportamento pretendido.

**Riscos.** A vigência entra como subconsulta na varredura do pivot, não como
consulta a mais: o teste de contagem de consultas (≤ 3) continua passando sem
alteração.

**Impacto.** `app/CatalogIntelligence/Queries/FindSimilarProducts.php` e
`tests/Feature/CatalogIntelligence/ProductSimilarityTest.php`. Nada mais.

### 3.1 O que mudou em `FindSimilarProducts`

```diff
- ->where('p.is_active', true)
+ ->whereIn('pk.product_id', Product::query()->comOfertaVigente()->select('products.id'))
```

O `join` em `products` permanece, porque `p.category_id` continua sendo lido
para o peso de mesma categoria.

O bloco de docblock sobre alcance foi reescrito: a frase que prometia
equivalência com o que o visitante vê era falsa desde a CAT-DOM-01, e agora é
verdadeira. A contagem de consultas declarada no docblock dizia "duas" e sempre
foram três — os conceitos da origem, a varredura do pivot e a hidratação dos
produtos. Corrigido junto, porque estava dentro do bloco reescrito e contradizia
o teste que trava o limite em ≤ 3.

### 3.2 Cobertura de teste

`test_inactive_products_are_not_returned_as_similar` cobria **um** dos três eixos
da vigência — o único que o filtro antigo implementava. Foi substituído por um
caso com data provider que percorre os três, mais quatro testes:

| Teste | Eixo | Falhava antes da correção? |
|---|---|---|
| `…nao_e_sugerido…` / produto sem validade canônica | `products.is_active` | Não — já era coberto |
| `…nao_e_sugerido…` / oferta desligada pelo lojista | `product_offers.is_active` | **Sim** |
| `…nao_e_sugerido…` / expositor fora da Feira | `expositores.is_active` | **Sim** |
| `test_item_sem_oferta_alguma_nao_e_sugerido_como_semelhante` | sem oferta | **Sim** |
| `test_item_sem_oferta_continua_encontrando_semelhantes` | D-CAT-21, contrapartida | Não — é guarda de regressão |
| `test_perder_vigencia_nao_apaga_o_conhecimento_do_item` | D-CAT-21, conhecimento | Não — é guarda de regressão |

Os três que falhavam foram executados contra o código antigo antes da correção,
para confirmar que provam algo. Os dois últimos existem para o caso inverso: se
uma fase futura "melhorar" o filtro aplicando vigência também à origem, eles
falham.

### 3.3 Conflito com um invariante da CAT-DOM-01 (01G)

A suíte completa revelou o que os testes dirigidos não podiam revelar:
`ProdutoMestreOfertaTest::test_conhecimento_sobrevive_a_desativacao_da_oferta`,
da seção **01G da CAT-DOM-01**, afirmava duas coisas depois de desativar a
oferta do vizinho:

1. a linha em `catalog_product_knowledge` sobrevive — **continua verdadeira**;
2. `FindSimilarProducts` **continua devolvendo** o vizinho como semelhante.

A segunda asserção **é a M-17 congelada em teste**. A CAT-DOM-01 usou o retorno
da similaridade como prova de que o conhecimento sobrevivera, e ao fazê-lo
gravou como invariante o comportamento que a CAT-DOM-02B — escrita depois —
registraria como dívida e endereçaria a esta fase. As duas fases discordavam, e
a discordância estava dormindo porque nada a exercitava.

**Resolução.** A asserção foi revista, não removida, e o teste continua provando
o que dá nome a ele — agora pelos dois lados:

```php
// o item que perdeu o vendedor deixa de ser oferecido como referência…
$this->assertCount(0, app(FindSimilarProducts::class)($origem->product));
// …mas continua enxergando o catálogo, com o conhecimento intacto.
$this->assertCount(1, app(FindSimilarProducts::class)($vizinho->product));
```

A prova de sobrevivência do conhecimento ficou **mais forte** do que era: antes,
uma associação apagada e um filtro frouxo produziriam o mesmo `assertCount(1)`;
agora a persistência é verificada por `assertDatabaseHas` e o uso do
conhecimento é verificado pelo lado do item órfão, que é onde a D-CAT-21
realmente promete algo.

> **Esta subfase alterou um teste de outra fase.** Está registrado aqui de
> propósito. Reverter é uma linha, e a alternativa — manter a asserção antiga —
> significa manter a M-17 aberta e recusar a decisão D-CAT-05B-2.

---

## 4. De onde `ListingContext` lê

Decisão de contrato, consequência direta do §6(b) da auditoria: depois da
CAT-DOM-02, o item deixou de caber num objeto só.

**`Product` é a fonte, e a única.** `ListingContext` é montado a partir de
identidade de catálogo: `item_type`, `name`, `short_description`,
`description`, categoria e ancestrais. É o mesmo conjunto que
`ProductKnowledgeInput` já carrega desde a CAT-04, e não é coincidência — a
CAT-05 gera texto sobre *o que o item é*, que é exatamente o que `products`
guarda hoje.

**`ProductOffer` fica fora.** Preço, estoque, dimensões, modalidade, duração,
destaque e ordem não entram no contexto. Três motivos, em ordem de peso:

1. **A sugestão é do item, não da oferta.** Um texto que descreve o produto vale
   para qualquer expositor que venha a oferecê-lo; um texto que menciona preço
   ou prazo vale para um só, e envelhece na primeira alteração.
2. **É o que a §5.1 já exige.** A minimização proíbe dado de gestão no contexto.
   Com os doze espelhos removidos de `products` pela CAT-DOM-02H, a fronteira
   virou estrutural: o `ContextSanitizer` não precisa filtrar o que a tabela não
   tem mais.
3. **Multi-oferta.** No dia em que houver N ofertas, "a condição comercial do
   item" deixa de existir como singular. Um contexto que a carregasse teria de
   escolher uma oferta — e escolher vendedor é decisão de produto que esta fase
   não tem.

**Imagem e FAQ ficam fora nesta fase.** Ambas são conteúdo de oferta desde a
02D/02E, com fallback para o canônico. Não são sinal textual, e usá-las exigiria
decidir entre canônico e comercial (D-CAT-14, D-CAT-16) sem necessidade. Se uma
subfase futura precisar, decide então.

**Consequência prática.** `ListingContext` não recebe `ProductOffer` nem
`Expositor` no construtor. Um caminho que precise de oferta para montar contexto
é sinal de que a fase saiu do escopo.

---

## 5. Contratos que a CAT-05C em diante implementa

Declarados aqui, **não implementados nesta subfase**.

### 5.1 `ListingContext` — CAT-05C

DTO imutável, construído a partir de `Product` **ou** de campos soltos — o
cadastro em andamento é o caso que a §3B.5 já previa, e `ProductKnowledgeInput`
já resolve assim.

```text
itemType             produto | servico | cuidado
name
categoryPath         nome e ancestrais da categoria
existingShortDescription
existingDescription
knownAttributes      só o que foi informado, nunca inferido
knowledge            trechos de conceitos APROVADOS relevantes
similarItems         referências internas, já filtradas por vigência (D-CAT-05B-2)
```

Nunca models Eloquent completos, nunca relações carregadas por engano.

### 5.2 `ContextSanitizer` — CAT-05C

A minimização é responsabilidade dele e é aplicada **na construção** do
contexto, não confiada a quem chama (§5.1 do documento arquitetural).

Nunca entram: nome de usuário, e-mail, CPF, CNPJ, endereço, telefone, cookies,
`visitor_uuid`, `session_uuid`, IP, dado de pedido, e — acréscimo desta fase —
**qualquer campo de `ProductOffer`**.

### 5.3 `ListingSuggestion` — CAT-05D

Estruturado, nunca texto solto:

```json
{
    "suggested_name": "...",
    "short_description": "...",
    "description": "...",
    "keywords": [],
    "missing_information": [],
    "source": "internal | external",
    "confidence": 0.0
}
```

`missing_information` é o mecanismo antialucinação, detalhado na CAT-05E.

### 5.4 `ListingAssistant` — CAT-05D

Única porta que o cadastro conhece. Decide se o conhecimento interno basta; na
CAT-05 a resposta é sempre "basta", porque não há provider. **Não escreve**
(D-CAT-05B-1).

### 5.5 `CatalogAiProvider`, `FakeCatalogAiProvider`, `NullCatalogAiProvider`

```php
interface CatalogAiProvider
{
    public function isAvailable(): bool;
    public function suggest(ListingContext $context): ListingSuggestion;
}
```

O domínio não conhece OpenAI, Anthropic, Gemini nem nome de modelo. `Null`
devolve indisponibilidade sem lançar exceção; `Fake` é determinístico, para
teste.

**Estas três não são criadas na CAT-05.** A decisão nº 6 da CAT-01 — "contratos
+ Fake + Null desde o início" — é sobre não deixar a escolha comercial de
fornecedor bloquear as fases; ela não obriga a criar as classes antes de existir
quem as chame. A CAT-05 tem um caminho só, o interno, e um `NullCatalogAiProvider`
que ninguém injeta é o mesmo "provider vazio é estética" que a CAT-03 recusou ao
adiar o `ServiceProvider`. **Pertencem à CAT-06.**

### 5.6 Onde as classes moram

A §3.1 propõe `Services/`, `Contracts/`, `Providers/` e `DTO/` (singular). O
código usa `DTOs/` (plural) e não tem as outras três pastas. **Decisão:** seguir
o que existe — `DTOs/` para os dois DTOs, `Support/` para o `ContextSanitizer`,
`Actions/` para o assistente. `Contracts/` e `Providers/` nascem na CAT-06,
quando houver contrato e provider. A §3.1 é proposta da CAT-01, não compromisso.

---

## 6. Decisões ainda pendentes

Registradas para não serem tomadas por omissão dentro de uma subfase de
implementação.

| # | Pendência | Onde decide |
|---|---|---|
| **P-1** | **Executar o backfill** de `catalog_product_knowledge` (B-3). Hoje são **0 associações**, e `FindSimilarProducts` devolve vazio para todo item. Sem isso a CAT-05H não tem o que validar. O comando existe, com `--dry-run` | CAT-05D ou 05H, por decisão humana |
| **P-2** | O que o assistente faz quando **não há conhecimento nenhum** para o item: sugestão vazia, sugestão só com `missing_information`, ou recusa explícita | CAT-05D |
| **P-3** | Quantos itens semelhantes entram no contexto, e com que corte de score | CAT-05D |
| **P-4** | Se `keywords` sai de conceitos associados, de termos, ou dos dois | CAT-05E |
| **P-5** | Se a validação da CAT-05H é possível com corpus de seeder (B-4), ou se exige conteúdo real | CAT-05H |
| **P-6** | Correção das divergências **D-6** (tabela de dívidas contradiz o texto) e **D-7** (os dois roadmaps discordam sobre o status da CAT-DOM-02) | Decisão humana, fora da CAT-05 |
| **P-7** | A tabela de bloqueadores da CAT-DOM-02B ainda marca **M-17 como "Aberta"**. Fechada aqui, a linha ficou desatualizada — atualizá-la é editar o documento de outra fase | Decisão humana |

---

## 7. O que esta subfase deliberadamente não fez

Nenhuma migration, tabela, coluna, enum, model, DTO, provider, service,
controller, endpoint ou tela. Nenhuma alteração em `Product`, `ProductOffer`,
`SaveProductWithOffer`, `ProductPolicy`, `ProdutoForm`, `ProdutoController`,
AVA, checkout, estoque ou Customer Intelligence.

Dos arquivos de produção, **apenas um** foi tocado:
`FindSimilarProducts.php`. Dos de teste, dois — o da CAT-04 e, por consequência
direta da decisão, uma asserção do da CAT-DOM-01 (§3.3).

`ListingContext`, `ListingSuggestion`, `ContextSanitizer` e os três providers
**não foram criados** — são CAT-05C em diante.

O backfill **não foi executado**. Multi-oferta não foi habilitada. Nenhum gate
de multi-oferta foi fechado. Sem Pint global.

Este documento **decide**. A implementação começa na CAT-05C, e só mediante
autorização própria.

---

## 8. Decision log

| # | Decisão | Fecha |
|---|---|---|
| **D-CAT-05B-1** | A CAT-05 sugere e nunca aplica. `ListingSuggestion` é pré-visualização; nenhum caminho da fase escreve em `Product` ou aciona `ProductPolicy::updateCanonical`. A aplicação é CAT-09 | B-1 |
| **D-CAT-05B-2** | `FindSimilarProducts` passa a exigir oferta vigente de quem é oferecido como semelhante, reaproveitando `Product::scopeComOfertaVigente()`. A origem da consulta não é filtrada, preservando a D-CAT-21 | B-2, **M-17** |
| **D-CAT-05B-3** | `ListingContext` lê apenas identidade de catálogo (`Product`). `ProductOffer`, imagem e FAQ ficam fora do contexto nesta fase | §6(b) da CAT-05A |
| **D-CAT-05B-4** | `CatalogAiProvider`, `Fake` e `Null` pertencem à CAT-06, não à CAT-05: provider sem quem o injete é estética, pelo mesmo critério com que a CAT-03 adiou o `ServiceProvider` | Contratos |
| **D-CAT-05B-5** | As classes seguem a estrutura que existe (`DTOs/`, `Support/`, `Actions/`), não a proposta da §3.1. `Contracts/` e `Providers/` nascem na CAT-06 | D-10 da CAT-05A |
