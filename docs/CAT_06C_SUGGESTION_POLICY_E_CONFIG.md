# CAT-06C — `SuggestionPolicy` e o config do módulo

> **Primeira subfase de código da CAT-06.** Entrega o limiar de fallback e a
> política que o lê. **Nenhum provider, nenhuma credencial, nenhum prompt** — e
> a política sequer sabe o que é um provider, o que é justamente o que permite
> testá-la sem nenhum dublê.

Decisões a que esta subfase se subordina:
[`CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md)
§4.1 e §4.2. Auditoria da fase:
[`CAT_06A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_06A_AUDITORIA_DE_RECONCILIACAO.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `960ece7003b350eb40fb87dc601237818a1e73fc` |
| Working tree no início | Limpo |
| Suíte no início | 1139 passed · 4028 assertions · 0 failures |
| Data | 2026-09-03 |

---

## 2. O gatilho que disparou — e por que ele disparou certo

**Este é o achado que mais mexe em trabalho já commitado, e vem primeiro por
isso.**

`FronteiraDePromptTest`, escrito na CAT-05G, travava **duas** classes no mesmo
laço:

```php
foreach (['PromptGuard', 'SuggestionPolicy'] as $classe) {
    $this->assertFalse(class_exists("App\\CatalogIntelligence\\Support\\{$classe}"), ...);
}
```

Criar a `SuggestionPolicy` nesta subfase **derruba esse teste**. A CAT-06A §7
previa o gatilho disparando na **06F**, com o `PromptGuard`; ninguém notou que a
`SuggestionPolicy`, criada na **06C**, o dispara três subfases antes.

### O que foi feito, e por quê

A trava existia para *"obrigar a chegada da classe a ser uma decisão
consciente"*. A chegada agora **é** uma decisão consciente: `D-CAT-06B-3`, sob
revisão. **O gatilho cumpriu sua função** — disparou exatamente quando devia.

A resposta certa a um gatilho que dispara corretamente é **registrar a decisão e
soltar aquela lingueta**, não contornar o teste. Então:

- **`PromptGuard` continua travado.** O S-1 segue aberto e nada nesta subfase o
  toca.
- **A lingueta da `SuggestionPolicy` não foi apagada** — foi **substituída pela
  garantia que ela representava**. Antes: *"a política não existe"*. Agora:
  `test_a_suggestion_policy_existe_e_nao_conhece_provider` — *"a política existe
  e não sabe o que é um provider"*.
- **As duas varreduras de módulo continuam intactas.**
  `test_nenhum_arquivo_do_modulo_monta_prompt_ou_fala_com_provider` e
  `test_nenhuma_classe_do_modulo_depende_de_cliente_http` passaram a valer sobre
  os arquivos novos sem alteração alguma.

A substância de segurança do gate S-1 não foi tocada. O que mudou foi uma
precondição de *nomenclatura* que já tinha vencido.

> **Alteração em arquivo de fase anterior, sinalizada.** `FronteiraDePromptTest`
> é da CAT-05G. Diff completo no relatório da subfase; os 4 testes originais
> continuam passando, agora 5 com o substituto. `CAT_05G_*.md` **não foi
> reescrito**: ele registra o que aquela fase entregou, e era verdade.

---

## 3. `config/catalog-intelligence.php`

Arquivo novo, com **uma única chave**:

```php
'fallback' => [
    'minimum_gaps' => (int) env('CATALOG_AI_MINIMUM_GAPS', 3),
],
```

Registrado em `CatalogIntelligenceServiceProvider::register()` com
`mergeConfigFrom()` — mesmo padrão de `CustomerIntelligenceServiceProvider`,
verificado antes de escrever e não inventado. `mergeConfigFrom` e não
`publishes`: o arquivo é da aplicação e versionado, não um stub a copiar.

### O merge é redundante para resolução, e isso ficou registrado

O `register()` recebeu, na revisão, uma correção que vale registrar aqui porque
ela desmente uma justificativa plausível e errada.

A primeira redação do docblock afirmava que o merge *"garante que o limiar
exista mesmo se alguém apagar a chave"*. **É falso.** O Laravel já carrega todo
arquivo em `config/`, e `config('catalog-intelligence.…')` resolveria sem o
merge — verificado empiricamente com `orders.php` e `frenet.php`, que nenhum
provider mescla e ainda assim resolvem. Mesclar o **mesmo arquivo** que já foi
carregado não cria fonte nova: apagar a chave do arquivo a remove das duas.

Quem garante o limiar na ausência da chave é o `LIMIAR_PADRAO = 5` da própria
`SuggestionPolicy`, no `config()` do ponto de uso — e é ele que está testado.

O `mergeConfigFrom` **fica**, por dois motivos que continuam válidos: simetria
com o módulo irmão, e tornar explícita no registro do módulo a dependência
daquele arquivo. O que mudou foi só a justificativa escrita, não o código.

> **Por que isso está num documento de subfase.** Uma justificativa errada num
> docblock é pior que docblock nenhum: ela sobrevive à revisão, é citada como
> fonte e vira premissa de quem vier depois. O erro foi encontrado **antes do
> commit**, ao verificar a própria afirmação em vez de confiar nela — e a
> correção exigiu suíte nova sobre a versão final, pela mesma regra da CAT-05C:
> comentário alterado não é exceção.

### É arquivo de política, não de conexão

A **D-CAT-06B-2** proíbe nominalmente credencial, fornecedor, endpoint e
segredo. A proibição não ficou só na prosa:
`test_o_config_nao_carrega_credencial_nem_fornecedor` serializa o config inteiro
e falha se `key`, `secret`, `token`, `endpoint`, `url`, `openai`, `anthropic` ou
`gemini` aparecerem em qualquer profundidade. Um acréscimo distraído no futuro
quebra o teste, não passa despercebido.

### Por que 3

Com 5 lacunas possíveis:

- **1** faria qualquer item sem categoria cair em fallback — e categoria é
  escolha do lojista, que consulta externa nenhuma resolve.
- **5** exigiria não se saber absolutamente nada sobre o item, e aí não há nem
  nome de onde partir.
- **3** é o ponto em que falta mais material do que existe.

Não é constante sagrada: é o número que a **06G** valida contra os 75 itens
reais, como a CAT-05H fez com o resto. Fica registrado como decisão revisável,
com o motivo escrito no próprio arquivo de config.

---

## 4. A assinatura, e as duas decisões dentro dela

```php
public function __invoke(ListingContext $contexto): KnowledgeSufficiency
```

### 4.1 Recebe o `ListingContext`, não o array de `lacunas()`

As duas formas foram consideradas. Recebendo o array já calculado, qualquer
chamador poderia passar uma lista **fabricada**, e a garantia de fonte única
valeria por convenção — exatamente o tipo de invariante que se perde no primeiro
refactor apressado.

Recebendo o contexto, **a política é quem pergunta**, e a fonte única passa a ser
estrutural. `test_a_politica_nao_reconta_lacunas` a trava na fonte: o arquivo
chama `->lacunas()` **exatamente uma vez** e não menciona nenhuma das cinco
propriedades cruas de onde `lacunas()` deriva.

> **Por que esse teste é estrutural e não comportamental.** `ListingContext` é
> `final` com construtor privado — não há como espionar a chamada por subclasse
> ou dublê. A varredura de fonte é o mesmo instrumento que
> `FronteiraDePromptTest` já usa no módulo, e é honesto sobre o que prova: que a
> classe não tem uma segunda fonte escrita nela, não que o runtime a impeça.

### 4.2 Devolve enum de três casos, não booleano

**Esta é a decisão de produto da subfase**, e ela contraria a formulação da
CAT-06B §4.1, que falava em "limiar" como se a saída fosse binária.

`ListingGap::podeSerPreenchidaPelaSugestao()` — que já existia — diz que só
**duas** das cinco lacunas se resolvem escrevendo texto: resumo e descrição. As
outras três dependem de uma pessoa: categoria é escolha do lojista, atributo é
fato que só ele sabe, conhecimento é trabalho da curadoria.

Então "insuficiente" esconde dois casos que pedem ações **opostas**:

| Situação | O que fazer |
|---|---|
| Falta **texto** | Texto é o que uma inteligência externa produz — consultar se justifica |
| Falta **fato** | Fato nenhuma inteligência produz: ela **inventa** |

Consultar um modelo externo sobre o material de uma peça é o caminho mais curto
para uma medida alucinada entrar no catálogo. A regra 1 das invioláveis manda o
contrário — *"na dúvida: omitir e pedir a informação ao lojista"* —, e a CAT-05E
construiu `missing_information` inteira sobre esse princípio.

Um booleano forçaria a **06G** a redescobrir a distinção **no momento de gastar
dinheiro com a consulta**, que é o pior momento possível para descobri-la.

Daí `KnowledgeSufficiency`, com três casos:

| Caso | Significado |
|---|---|
| `Sufficient` | A base basta. Caminho normal e mais barato |
| `ExternalMayHelp` | Falta texto — **único** veredito que autoriza a 06G a considerar fallback |
| `AwaitsMerchant` | Falta o que só o lojista tem — consultar seria pagar por invenção |

> **Se preferir booleano, é reversível.** Colapsar os três casos em
> `bool $suficiente` custa remover o enum e dois testes. A informação de *por
> que* não é binário fica registrada aqui de qualquer forma.

### 4.3 Nomenclatura, decidida aqui e não antes

`KnowledgeSufficiency` é nome **desta subfase**, pelo critério que a CAT-06B §2.4
fixou: quem cria o arquivo é quem o batiza. Não veio da lista da §3.1 —
verificado que dos quatro enums que aquela lista nomeia, **nenhum existe**.

Casos em PascalCase inglês (`Sufficient`, `ExternalMayHelp`, `AwaitsMerchant`) e
método em português (`justificaConsultaExterna()`), seguindo `ListingGap`,
`MatchType` e `SuggestionSource`.

---

## 5. Cobertura de teste

`tests/Feature/CatalogIntelligence/SuggestionPolicyTest.php` — **11 casos**, sem
`RefreshDatabase`, sem migration, sem dublê. Que o arquivo rode sem banco é a
prova operacional da D-CAT-06B-3: uma política que conhecesse provider precisaria
de um `Fake` que só nasce na 06D.

| Caso | O que fixa |
|---|---|
| `test_item_sem_nenhuma_lacuna_e_suficiente` | Extremo inferior |
| `test_item_com_todas_as_lacunas_e_insuficiente` | Extremo superior |
| `test_uma_lacuna_a_menos_que_o_limiar_ainda_e_suficiente` | Borda − 1 |
| `test_no_limiar_exato_ja_e_insuficiente` | Borda exata (`>=`) |
| `test_uma_lacuna_a_mais_que_o_limiar_e_insuficiente` | Borda + 1 |
| `test_o_limiar_vem_do_config_e_a_politica_reage_a_ele` | Mesmo contexto, dois limiares, dois vereditos |
| `test_o_arquivo_de_config_existe_e_traz_o_limiar` | O config foi mesclado pelo provider |
| `test_o_config_nao_carrega_credencial_nem_fornecedor` | D-CAT-06B-2, executável |
| `test_lacuna_de_texto_justifica_consulta_externa` | `ExternalMayHelp` |
| `test_lacuna_que_so_o_lojista_preenche_nao_justifica_consulta` | `AwaitsMerchant` |
| `test_a_politica_nao_reconta_lacunas` | Fonte única, na fonte |

Mais **1 caso novo** em `FronteiraDePromptTest`:
`test_a_suggestion_policy_existe_e_nao_conhece_provider`, que substitui a
lingueta removida.

### Controles negativos

Os três testes que carregam invariante foram verificados **falhando sem o que
guardam** — o arquivo foi revertido e conferido por `md5sum` após cada um
(`7ef1d134796d297afd0025a09f74ab47`, idêntico ao original nos três retornos):

| Controle | Sabotagem | Resultado |
|---|---|---|
| **A** | Limiar `3` fixo no código, ignorando o config | `test_o_limiar_vem_do_config` **falhou** na linha 168 |
| **B** | `$contexto->knowledge === []` lido direto | `test_a_politica_nao_reconta_lacunas` **falhou** na linha 285 |
| **C** | `use Illuminate\Support\Facades\Http` acrescentado | `test_a_suggestion_policy_existe_e_nao_conhece_provider` **falhou** na linha 164 |

O controle C revelou de passagem um defeito na primeira versão do teste: ele
varria o **texto inteiro** do arquivo atrás de marcas como `Provider`, que
aparecem em prosa — o docblock da política fala de provider o tempo todo,
justamente para explicar que não conhece nenhum. A asserção foi trocada para
percorrer as **importações** com `preg_match_all`, comparando contra uma lista de
permissão. É a mesma lição que `MARCAS_DE_PROMPT` já registrava no módulo:
varredura de prosa dá falso positivo na primeira frase honesta.

---

## 6. O que esta subfase deliberadamente não fez

- **Nenhum `Contracts/`, `Providers/`, `NullCatalogAiProvider` ou
  `FakeCatalogAiProvider`** — 06D.
- **Nenhum `FreeTextRedactor`** — 06E. **Nenhum `PromptGuard`** — 06F.
- **A `SuggestionPolicy` não foi ligada a `GenerateListingSuggestion`.** Ela
  nasce testável isoladamente; o acoplamento ao fallback real é 06G, depois que
  redator e guard existirem (**D-CAT-06B-6**).
- **Nenhum gate foi fechado.** C-2, F-1 e S-1 seguem abertos.
- **`CAT_05G_*.md` não foi reescrito** — a alteração no teste daquela fase está
  registrada aqui, na §2.

---

## 7. Decision log

| # | Decisão | Fecha |
|---|---|---|
| **D-CAT-06C-1** | `SuggestionPolicy::__invoke()` recebe o `ListingContext` inteiro, não o array de `lacunas()`: com o array, a fonte única valeria por convenção; com o contexto, é estrutural | Assinatura, §4.1 |
| **D-CAT-06C-2** | O veredito é o enum `KnowledgeSufficiency` de **três** casos, não booleano. "Insuficiente" esconde dois casos opostos, e só um deles justifica consulta externa — o outro pede ao lojista, pela regra 1 das invioláveis | Assinatura, §4.2 |
| **D-CAT-06C-3** | O limiar é lido **no ponto de uso** via `config()`, não capturado no construtor — padrão de `TrackingPolicy`; valor capturado envelheceria sob Octane e tornaria o config decorativo | §3 |
| **D-CAT-06C-4** | `minimum_gaps` **3**, com o motivo escrito no próprio config e revalidação prevista na 06G contra os 75 itens reais | §3 |
| **D-CAT-06C-5** | A lingueta da `SuggestionPolicy` em `FronteiraDePromptTest` é **substituída pela garantia que representava**, não apagada. `PromptGuard` continua travado | §2 |
| **D-CAT-06C-6** | O padrão de segurança do limiar ausente é **5** (o mais conservador), para que config sumido nunca ligue fallback sozinho | §3 |

---

## 8. Encerramento

A CAT-06C entrega o limiar e a política que o lê. Nenhum gate foi fechado, e
nenhum texto chegou mais perto de sair da aplicação: a política decide **se
valeria** consultar, e não há para onde consultar.

O achado da §2 é o que merece revisão com mais atenção — não pelo tamanho do
diff, que é pequeno, mas porque toca a trava de um gate de segurança escrito por
outra fase.
