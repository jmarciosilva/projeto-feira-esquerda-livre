# CAT-05C — `ListingContext` e `ContextSanitizer`

> **Subfase de implementação.** Entrega o insumo do assistente de conteúdo e a
> fronteira que o protege. Nenhuma migration, nenhuma alteração em arquivo
> existente: três arquivos novos, e só.

Decisões que governam esta subfase:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md)
§4, §5.1 e §5.2. Auditoria que originou a trilha:
[`CAT_05A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_05A_AUDITORIA_DE_RECONCILIACAO.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `5681be5d440c1546ac6aa5284c780fe0b5c630fa` |
| Working tree no início | Limpo |
| `origin/main` | `5681be5` — sincronizado |
| Suíte no início | 1053 passed · 3127 assertions · 0 failures |
| Data | 2026-09-01 |

**Resultado:** **1079 passed · 3258 assertions · 0 failures** em 831,41s.
`+26` testes, `+131` asserções — exatamente o arquivo novo, nenhuma regressão.

A suíte foi executada **do zero sobre a versão final**, depois do último ajuste
de comentário. Uma execução anterior, idêntica no número, foi descartada por ser
anterior a esse ajuste: o protocolo exige verde na versão que vai a commit, e
não numa que se pareça com ela.

Banco de desenvolvimento sem alteração: 75 produtos, 75 ofertas, 14 expositores,
28 conceitos, **0 associações** em `catalog_product_knowledge`.

---

## 2. O que foi implementado

Três arquivos novos. Nenhum existente foi tocado.

| Arquivo | Linhas | Papel |
|---|---|---|
| `app/CatalogIntelligence/DTOs/ListingContext.php` | 271 | O insumo do assistente |
| `app/CatalogIntelligence/Support/ContextSanitizer.php` | 271 | A minimização, na construção |
| `tests/Feature/CatalogIntelligence/ListingContextTest.php` | 447 | 26 casos |

### 2.1 `ListingContext`

DTO imutável, construtor **privado**, campos fixos:

```text
itemType                  ItemType
name                      string
categoryPath              array<string>   ancestrais + a própria, do topo para baixo
existingShortDescription  ?string
existingDescription       ?string
knownAttributes           array<string, scalar>
knowledge                 array           conceitos aprovados, como texto
similarItems              array           referências internas, como texto
```

**Duas portas de construção.** `paraItemNovo()` monta contexto a partir de
campos soltos, sem nenhuma linha no banco — é o cadastro em andamento, o caso
que a §3B.5 já previa e que `ProductKnowledgeInput` resolveu na CAT-04 pelo
mesmo motivo. `deProduct()` lê um item existente, e o model **não é guardado**:
entra e sai como texto.

**Completado por cópia.** `knowledge` e `similarItems` não chegam pelo
construtor porque não são conhecidos quando o contexto nasce — quem os busca é
o `ListingAssistant`, na CAT-05D, e ele o faz *a partir* deste contexto.
`comConhecimento()` e `comSemelhantes()` devolvem instância nova. Um contexto
que mudasse depois de montado deixaria de ser reproduzível, e reproduzir a
entrada é o que permitirá auditar uma sugestão depois.

**A ponte com o motor da CAT-04.** `paraBuscaDeConhecimento()` devolve um
`ProductKnowledgeInput` em vez de remontar a lista de campos textuais. Montá-la
de novo criaria duas respostas para "o que é o texto deste item", e elas
divergiriam na primeira vez que uma das duas ganhasse um campo.

**`lacunas()`** devolve a leitura crua do que falta — resumo, descrição,
categoria, atributos, conhecimento. Não é a `missing_information` da sugestão,
que é da CAT-05E e fala a linguagem do lojista; existe para que a decisão "há
material suficiente?" seja tomada sobre um fato e não sobre um `empty()`
espalhado por quem consome.

### 2.2 A fronteira com a oferta (D-CAT-05B-3), e por que ela é estrutural

O construtor **não tem parâmetro** que aceite `ProductOffer` ou `Expositor`.
Não é disciplina, é ausência de caminho: para vazar dado comercial seria preciso
antes declarar o campo. `deProduct()` lê `item_type`, `name`,
`short_description`, `description` e a categoria — e nada de `offers`,
`ofertaVigente`, `expositor`, `images` ou `faqs`.

Três testes guardam isso por ângulos diferentes: um carrega `offers.expositor`
em memória e varre o JSON serializado atrás de preço, estoque, peso e expositor;
outro percorre por reflexão os tipos de todos os parâmetros da classe; o
terceiro percorre as propriedades da instância procurando qualquer `Model`.

### 2.3 `ContextSanitizer`

A minimização acontece **na construção** e não é confiada a quem chama. Se
morasse em quem monta o contexto, cada superfície nova — painel, API, comando,
job — teria a sua versão da regra, e a primeira que esquecesse um campo vazaria
sem que nada quebrasse.

| Método | O que faz |
|---|---|
| `atributos()` | Filtra `knownAttributes`: chave proibida sai, valor não escalar sai, valor vazio sai |
| `conhecimento()` | Reduz `KnowledgeCandidate` a `name`/`type`/`description`, **só o que está `approved`** |
| `semelhantes()` | Reduz `SimilarProduct` a `name`/`shared_concepts`/`reasons`; sem `product_id` |
| `texto()` | Resolve "não informado" × "informado em branco" numa coisa só |
| `campoEhProibido()` | Pergunta pública, para que fases seguintes não reimplementem a comparação |

**A lista de campos da oferta não é escrita aqui.** Vem de
`SaveProductWithOffer::CAMPOS_DA_OFERTA` e `::ESPELHOS_COMERCIAIS_LEGADOS`, que
é onde o domínio decide o que é condição de venda. Repetir os nomes criaria uma
segunda definição que envelheceria na primeira coluna nova de `product_offers`
— e o vazamento seria silencioso, porque o campo novo simplesmente não estaria
na cópia. Um teste percorre a lista inteira e exige que cada campo seja
recusado; outro exige que as duas constantes do domínio estejam contidas nela.

**Só conceito aprovado entra.** É a mesma regra que o matcher já aplica na
consulta, repetida de propósito: o contexto pode ser montado a partir de
candidatos vindos de outro caminho, e um conceito em rascunho influenciando o
texto sugerido a um lojista é exatamente o que a CAT-03 criou `status` para
impedir.

---

## 3. A correção whitelist → denylist

O primeiro docblock desta classe afirmava, em título de seção: *"Lista de
permissão, não lista de proibição — o filtro é **whitelist**"*.

**Isso não descrevia o código.** `atributos()` percorre o array recebido e
derruba as chaves que estão em `CAMPOS_PROIBIDOS`; tudo o que não está na lista
sobrevive. É uma denylist, e chamá-la de whitelist inverteria a leitura de risco
de quem confiasse no comentário — exatamente o tipo de erro que um comentário
seguro de si causa e um comentário ausente não causaria.

Corrigido para separar as duas proteções, que são de naturezas diferentes:

**O contexto inteiro é lista de permissão.** Campos fixos, construtor privado,
nenhum parâmetro para oferta ou expositor. `conhecimento()` e `semelhantes()`
**nomeiam** o que sai dos DTOs da CAT-04, e o resto do model fica para trás.
Para um campo novo entrar, alguém tem de declará-lo.

**`knownAttributes` é a exceção, e é lista de proibição.** Ele existe para
receber o que o formulário informou, e o domínio não tem vocabulário de
atributos — a CAT-02 deixou `material`, `technique`, `color`, `style` e `usage`
fora de `products` por serem multivalorados. Sem vocabulário fechado não há
whitelist a escrever: qualquer chave é potencialmente legítima.

A troca de um comentário por outro não mudou uma linha de comportamento. Mudou o
que o próximo leitor vai acreditar sobre o comportamento, que é o que estava
errado.

---

## 4. Dívidas registradas

### C-1 — `knownAttributes` é protegido por denylist

**O risco.** Denylist protege contra o que alguém lembrou de listar. Uma chave
sensível cujo nome não esteja em `ContextSanitizer::CAMPOS_PROIBIDOS` passa pelo
filtro e entra no contexto.

**As mitigações que existem hoje.** O valor precisa ser escalar — array, objeto
e model são recusados, o que fecha a porta por onde relação carregada entraria
inteira. A lista de campos da oferta vem do domínio e não de uma cópia local.
E quem preenche `knownAttributes` é código nosso, não payload aberto.

**A obrigação que essa última mitigação impõe — e que é a dívida propriamente
dita.** Quem for popular `knownAttributes` a partir de formulário, isto é a
**CAT-09**, deve **mapear campo a campo**:

```php
knownAttributes: ['material' => $this->material, 'tecnica' => $this->tecnica]
```

`$request->all()`, `$request->except([...])` e o array de propriedades do
Livewire entregues em bloco são **proibidos**. Um payload repassado inteiro
transforma cada campo novo de formulário em vazamento silencioso, porque
ninguém precisa lembrar de nada para que o vazamento aconteça — basta
acrescentar um campo à tela.

A regra está escrita por extenso em `ListingContext::paraItemNovo()`, ao lado do
parâmetro que a exige, e referida no cabeçalho do `ContextSanitizer`.
Deliberadamente **não** é um `TODO` solto no código.

**Como a dívida se fecha.** No dia em que existir vocabulário de atributos no
domínio, a lista vira de permissão e a obrigação deixa de importar.

**Destino:** CAT-09 (obrigação de implementação) · fase de atributos
estruturados (fechamento definitivo).

### C-2 — Texto livre não é redigido

**O risco.** O `ContextSanitizer` filtra **campos**. Ele não varre `name`,
`short_description` ou `description` procurando telefone, e-mail, CPF ou
endereço que o lojista tenha escrito dentro da própria descrição. "Chama no meu
zap 11 9xxxx-xxxx" entra no contexto inteiro.

**Por que fica fora nesta subfase.** O escopo da CAT-05C fala em campos
proibidos, e a redação de texto livre atua sobre conteúdo que o lojista
publicou de propósito no catálogo público — mexer nele é decisão de produto, não
de sanitização. Além disso, o contexto redigido divergiria do texto salvo no
produto, o que é aceitável para insumo mas precisa ser dito.

**Por que mesmo assim é dívida, e não "fora de escopo" e ponto.** A §5.1 do
documento arquitetural lista telefone e e-mail entre o que **nunca sai para
provider externo**. Essa promessa, hoje, só é integralmente verdadeira no nível
de campo. Enquanto não houver provider a diferença é teórica — o texto não sai
da aplicação —, mas ela deixa de ser teórica exatamente na CAT-06.

**Destino: em aberto.** As candidatas são CAT-05F (resiliência e fronteiras) e
CAT-10 (segurança e prompt injection). **Não decidido nesta subfase**, por
decisão explícita.

### P-1 — Backfill continua pendente, sem mudança

`catalog_product_knowledge` segue com **0 linhas** no banco de desenvolvimento.
`FindSimilarProducts` devolve coleção vazia para todo item real, e o assistente
da CAT-05D nasceria sem uma única referência interna se a passagem não for
executada.

Nada nesta subfase alterou isso, e **nenhum teste dela depende de dado real
associado**: todos os cenários são montados por factory e pelas Actions, como a
CAT-04 já fazia. A decisão de rodar o comando — que existe, com `--dry-run` —
continua humana e continua pendente, com destino CAT-05D ou CAT-05H.

---

## 5. Cobertura de teste

26 casos, 131 asserções. Os obrigatórios da subfase e onde estão:

| Exigido | Teste |
|---|---|
| Contexto só com nome, item não salvo | `test_contexto_de_item_ainda_nao_salvo_funciona_so_com_o_nome` — assertando também que montar contexto **não cria** produto |
| Contexto a partir de `Product` | `test_contexto_a_partir_de_produto_existente_le_a_identidade` |
| Nunca expõe campo da oferta, com oferta carregada | `test_contexto_nao_expoe_campo_da_oferta_mesmo_com_a_oferta_carregada`, `test_o_construtor_nao_aceita_oferta_nem_expositor`, `test_contexto_nao_guarda_model_eloquent` |
| Um teste por categoria de dado sensível | 5 casos: identidade pessoal · contato · endereço · rastreamento · pedido |
| Todo campo da oferta bloqueado | `test_sanitizer_bloqueia_todo_campo_da_oferta` (itera a lista) e `test_a_lista_de_campos_da_oferta_vem_da_save_product_with_offer` |
| `knownAttributes` nunca inferido | `test_atributo_nunca_e_inferido_do_texto_nem_do_conhecimento` — o texto menciona "crochê" e "algodão", os conceitos existem na base, e o campo continua `[]` |
| `similarItems` reaproveita `FindSimilarProducts`, sem item não vigente | `test_semelhantes_reaproveitam_o_find_similar_products`, `test_semelhante_sem_oferta_vigente_nao_entra_no_contexto` |

Complementares: grafia de chave proibida (`E-Mail`/`E_mail`/`EMAIL`), valor não
escalar, conceito rebaixado a `draft` depois do casamento, hierarquia de
categoria em três níveis, item sem categoria, texto em branco, imutabilidade das
cópias e a ponte com `ProductKnowledgeInput`.

---

## 6. O que esta subfase deliberadamente não fez

`ListingSuggestion`, `ListingAssistant`, `CatalogAiProvider`,
`FakeCatalogAiProvider` e `NullCatalogAiProvider` **não foram criados** — os
três últimos por D-CAT-05B-4, que os situa na CAT-06.

Nenhuma migration, tabela, coluna, enum ou tela. Nenhuma alteração em `Product`,
`ProductOffer`, `SaveProductWithOffer`, `ProductPolicy`, `ProdutoForm`,
`ProdutoController`, AVA, checkout, estoque ou Customer Intelligence. O
`CatalogIntelligenceServiceProvider` não mudou: as duas classes resolvem por
injeção de construtor, como o resto do módulo.

O backfill não foi executado. Pint rodou **apenas** nos três arquivos desta
subfase.

---

## 7. Observação de custo, para a CAT-05D

`ListingContext::caminhoDaCategoria()` sobe a hierarquia de `content_categories`
com **uma consulta por nível**, limitada a dez e protegida contra ciclo — nada
no banco impede um `parent_id` circular, e uma subida ingênua travaria a
requisição em vez de devolver contexto.

Hoje a taxonomia é rasa e o custo real é de uma a duas consultas. Se a CAT-05D
quiser travar contagem de consultas como a CAT-04 fez, isto precisa entrar na
conta — ou a categoria precisa chegar pré-carregada.

---

## 8. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05C-1** | `ListingContext` tem construtor **privado** e duas portas nomeadas (`paraItemNovo`, `deProduct`) | Sem construtor público não há caminho que contorne o `ContextSanitizer`; a minimização deixa de depender de quem chama |
| **D-CAT-05C-2** | `knowledge` e `similarItems` entram por cópia (`comConhecimento`, `comSemelhantes`), não pelo construtor | Quem os busca é o `ListingAssistant` da CAT-05D, *a partir* do contexto. Contexto que muda depois de montado deixa de ser reproduzível, e reproduzir a entrada é o que permite auditar a sugestão |
| **D-CAT-05C-3** | A vigência de `similarItems` **não** é reconferida pelo sanitizer | Já foi decidida em `FindSimilarProducts` (D-CAT-05B-2). Reconferir criaria a segunda definição de vigência que a CAT-DOM-01 eliminou |
| **D-CAT-05C-4** | `knownAttributes` fica protegido por **denylist**, com a obrigação C-1 declarada no código | Sem vocabulário de atributos no domínio não existe whitelist a escrever. A obrigação de mapear campo a campo é o que mantém o risco fechado até lá |
| **D-CAT-05C-5** | Redação de PII em texto livre fica **fora**, como dívida C-2 com destino em aberto | Atua sobre conteúdo publicado de propósito pelo lojista; é decisão de produto. Vira exigível na CAT-06, quando o texto passar a sair da aplicação |
| **D-CAT-05C-6** | `lacunas()` entra nesta subfase, embora não estivesse na especificação | A CAT-05E precisa de leitura crua do que falta; a alternativa seria `empty()` espalhado por quem consome |
| **D-CAT-05C-7** | A lista de campos da oferta é **importada** de `SaveProductWithOffer`, nunca copiada | Cópia envelheceria na primeira coluna nova de `product_offers`, e o vazamento seria silencioso |

---

## 9. Situação

```text
CAT-05C — IMPLEMENTAÇÃO CONCLUÍDA · AGUARDANDO REVISÃO DO DIFF
```

Suíte verde em 1079 testes sobre a versão final. Sem commit, sem push. A
subfase só passa a **concluída** depois da revisão humana do diff completo.

**Próxima:** CAT-05D — `ListingAssistant` interno, sem provider externo. Ela
herda P-1 como primeira pergunta a responder.
