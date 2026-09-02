# CAT-05F — Resiliência e fronteiras

> **Subfase de implementação.** Entrega o teste explícito que a regra 3 das
> invioláveis exige por escrito, e a captura de falha que o sustenta. Nenhuma
> migration, nenhum provider externo, nenhuma escrita.
>
> **Esta subfase reescreve a entrada da CAT-10 e acrescenta gates à CAT-06** —
> as duas são fases não-correntes, e o registro está nos §5 e §6.

Decisões que a governam:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Subfases que ela protege:
[`CAT_05D_LISTING_ASSISTANT.md`](CAT_05D_LISTING_ASSISTANT.md) ·
[`CAT_05E_ANTIALUCINACAO_E_MISSING_INFORMATION.md`](CAT_05E_ANTIALUCINACAO_E_MISSING_INFORMATION.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `49e9ea0b1ff09f6e4e60ac01bd13dbbd60e08a6e` |
| Working tree no início | Limpo |
| Suíte no início | 1116 passed · 3352 assertions · 0 failures |
| Data | 2026-09-01 |

**Resultado:** **1126 passed · 3372 assertions · 0 failures** em 713,36s.
`+10` testes, `+20` asserções, nenhuma regressão. Suíte executada do zero sobre
a versão final, com md5 conferidos antes e depois.

---

## 2. A regra que esta subfase implementa

> **3. Falha da inteligência não bloqueia cadastro.** Provider fora do ar, sem
> credencial, resposta inválida, timeout — o cadastro manual continua
> funcionando integralmente. **Essa propriedade terá teste explícito.**

A frase final estava sem cumprimento desde a CAT-01. Agora tem.

---

## 3. Capturar, não propagar (decisão)

**O assistente captura as duas chamadas ao motor da CAT-04 e nenhuma exceção sai
dele.**

**Por quê.** O assistente é a **única porta** que o cadastro conhece (§3.2). Se
ele propagasse, cada superfície futura precisaria do seu próprio `try/catch`, e
a primeira que esquecesse quebraria a regra 3 sem que nada acusasse. É o mesmo
raciocínio que fez a minimização morar no `ContextSanitizer` em vez de em quem
chama: **a garantia mora onde não dá para esquecê-la**.

A §6 já descrevia a postura esperada nas duas linhas que tem sobre falha —
*"degrada sem exceção"* e *"JSON quebrado não derruba cadastro"*.

### 3.1 Degradação parcial, não total

As duas etapas são capturadas **em separado**, e isso é a parte que importa:

| O que falha | O que sobra |
|---|---|
| Casamento (`MatchProductKnowledge`) | Nada a compor → `ListingSuggestion::vazia()`, pelo caminho que já existia |
| Similaridade (`FindSimilarProducts`) | **A sugestão inteira** — só `similarItems` fica vazio |
| As duas | Sugestão vazia, sem exceção |

Capturar as duas juntas jogaria fora um resultado bom por causa de um acessório
que falhou. A lista de itens semelhantes é acessório; o texto sugerido não é.

### 3.2 Registrado, não engolido em silêncio

`Log::warning('catalog-intelligence: assistente degradado', ['etapa', 'excecao',
'mensagem'])`.

O custo real de engolir exceção é que uma base de conhecimento quebrada vira
silenciosamente "nenhuma sugestão", e ninguém percebe. Por isso o `catch` **não
é vazio**: a etapa diz onde quebrou, a classe diz o quê.

---

## 4. O guarda de log, e por que ele existe

**Este ponto foi implementado diferente da letra da instrução, e a divergência
está registrada aqui de propósito.**

A instrução autorizava `Log::warning` com *"classe da exceção e mensagem, sem
qualquer conteúdo do lojista"*. As duas metades entram em conflito num caso
concreto: **`QueryException::getMessage()` interpola os bindings no SQL**. Uma
falha de banco no matcher gravaria em log, em texto puro, o nome e a descrição
que o lojista digitou.

E a dívida **C-2** diz exatamente que esse texto pode conter telefone ou e-mail
que ele escreveu na descrição. O vazamento aconteceria **no log, sem provider
externo nenhum** — enquanto a §5.3 é explícita: *"Sem registrar conteúdo
sensível em log."*

Foi implementada a **intenção**, não a letra:

```php
private function mensagemSegura(Throwable $falha): string
{
    if ($falha instanceof QueryException) {
        return 'QueryException SQLSTATE['.$falha->getCode().'] — SQL e bindings omitidos (§5.3)';
    }

    return $falha->getMessage();
}
```

O SQLSTATE é o que serve ao diagnóstico; o SQL fica de fora. Qualquer outra
exceção entra pela mensagem normal — uma `RuntimeException` do próprio módulo
não carrega dado de ninguém.

**Verificado por controle negativo:** revertendo só a linha que chama
`mensagemSegura()`, o teste `test_falha_de_banco_nao_grava_o_texto_do_lojista_em_log`
falha. O guarda faz trabalho real.

---

## 5. Dívida F-1 — não há sinal de modo degradado

**O problema.** Quem recebe uma `ListingSuggestion` **não consegue distinguir**
*"a base não conhece este item"* de *"a inteligência falhou"*. As duas situações
devolvem `vazia()`.

**Por que importa.** A §3.3 prevê que, no modo degradado, *"a UI informa o modo
degradado"*. Sem a distinção, a CAT-09 não tem como informar — ela veria o mesmo
objeto nos dois casos e diria "nenhuma sugestão" para uma falha de
infraestrutura.

**Por que não foi resolvido agora.** As três saídas foram avaliadas:

| | Custo |
|---|---|
| Acrescentar um campo à `ListingSuggestion` | **Reabre a forma da §3.4**, congelada na CAT-05D |
| Usar `missing_information` para carregar o aviso | Mistura "o que o lojista deve informar" com "status do sistema", estragando o campo que a CAT-05E acabou de tornar coerente |
| Devolver `vazia()` e só logar | A limitação acima — **escolhida** |

**Destino: CAT-06**, registrada como gate na entrada daquela fase. É quando
existe um segundo modo de falha real — provider fora do ar — e a distinção passa
a valer o campo.

---

## 6. Alterações em fases não-correntes

Mesmo destaque usado quando a CAT-05B revisou um teste da CAT-DOM-01 e quando a
CAT-05E alterou o `ContextSanitizer` da CAT-05C.

### 6.1 CAT-06 ganhou um quadro de gates

**C-2 deixou de ser "pertence a" e passou a ser "bloqueia".** A análise que
motivou a mudança: a ordem das fases é CAT-06 → 07 → 08 → 09 → **10**. Se C-2
fosse para a CAT-10, o provider entraria em operação na CAT-06 e passariam
**quatro fases** com texto de lojista saindo da aplicação sem redação — a
promessa da §5.1 seria falsa na prática, não só no papel. Adiar para a CAT-10
não é adiar até quando é exigível; é adiar até **depois**.

**F-1 entrou no mesmo quadro**, pelo motivo do §5.

### 6.2 CAT-10 teve a sua linha reescrita

A entrada dizia, literal: *"Teste explícito de que a falha da inteligência não
impede cadastro manual."* É a mesma coisa que esta subfase acabou de escrever —
duas fases não podem ser donas do mesmo teste.

A linha passou a dizer que a CAT-10 **verifica que a garantia continua valendo
com provider externo acoplado**, e que a autoria é da CAT-05F. A justificativa
de escrever antes: o acoplamento chega na CAT-09, e chegar lá sem a rede pronta
seria construir o acoplamento para só então descobrir se ele é seguro.

---

## 7. Timeout — fora de escopo, com justificativa

A §7 diz *"Sugestão que o lojista espera na tela | Síncrono, com timeout
curto"*. Mas essa linha está numa tabela sobre **o que vai para fila**, e o que
ela tem em mente é a chamada que pode pendurar: a externa.

**Não existe hoje nada que penda.** `MatchProductKnowledge` e
`FindSimilarProducts` são ≤3 consultas relacionais cada, sem laço, com contagem
travada por teste desde a CAT-04.

E o custo de impor limite agora seria desproporcional: Laravel não tem timeout
por consulta. Seria atributo de PDO no nível da conexão — **global, afetando
checkout, pedidos e todo o resto** — ou um wrapper próprio. Trocar um risco
global de configuração por um risco que não existe é mau negócio.

**Destino: CAT-06**, junto do provider, que é a chamada que de fato pode pendurar.

---

## 8. Cobertura de teste

10 casos novos em `ResilienciaDoAssistenteTest`, todos por fixture. O matcher e a
similaridade são substituídos no container por versões que sempre lançam.

| O que prova | Teste |
|---|---|
| Falha no casamento não lança | `test_falha_no_casamento_devolve_sugestao_vazia_em_vez_de_lancar` |
| **Degradação parcial** | `test_falha_na_similaridade_preserva_o_conhecimento` — a sugestão sobrevive, só o acessório se perde |
| As duas juntas não lançam | `test_as_duas_falhando_juntas_ainda_nao_lancam` |
| Falha não escreve no banco | `test_a_falha_nao_escreve_nada_no_banco` |
| A degradação é registrada | `test_a_degradacao_e_registrada_em_log` |
| A etapa é identificada | `test_a_etapa_da_falha_e_identificada` |
| **Log não vaza texto do lojista** | `test_falha_de_banco_nao_grava_o_texto_do_lojista_em_log` — falha sem o guarda |
| Exceção comum mantém a mensagem | `test_excecao_comum_mantem_a_mensagem_no_log` |
| **Cadastro conclui com o assistente quebrado** | `test_cadastro_conclui_com_o_assistente_quebrado` — o teste da regra 3 |
| A fronteira estrutural | `test_o_caminho_de_cadastro_nao_referencia_a_inteligencia` |

**Sobre o alcance do teste da regra 3.** Hoje ele prova a garantia **do lado do
assistente**, porque `SaveProductWithOffer`, `ProdutoForm` e `ProdutoController`
não têm uma linha sobre `CatalogIntelligence` — verificado. Ele passa a ter
dentes no dia em que a CAT-09 acoplar os dois: se alguém puser a sugestão no
caminho do salvamento sem proteção, é ali que quebra.

O último teste é o que torna essa fronteira **consciente**: se um dia o cadastro
passar a conhecer a inteligência, que seja por decisão, e não por um `use` que
ninguém notou.

---

## 9. Dívidas ao fim da subfase

| # | Item | Situação |
|---|---|---|
| **C-2** | Texto livre não é redigido | **Aberta** — agora **gate da CAT-06**, não fase de destino |
| **F-1** | Sem sinal de modo degradado | **Nova, aberta** — gate da CAT-06 |
| **P-1** | Backfill — 0 associações | **Aberta** — CAT-05H |
| **C-1** | `knownAttributes` por lista de proibição | **Aberta** — CAT-09 |
| **B-4** | Corpus de seeder | **Aberta** — CAT-05H |
| **G-1** | Sem superfície de curadoria | **Aberta** — CAT-08 |
| **E-1** | `KnowledgeTermType::Keyword` sem uso | **Aberta** |
| **P-4** | `keywords` por termo | ✅ Fechada na CAT-05E |

---

## 10. O que esta subfase deliberadamente não fez

Nenhuma migration, tabela, coluna ou tela. Nenhuma alteração em `Product`,
`ProductOffer`, `SaveProductWithOffer`, `ProductPolicy`, `ProdutoForm`,
`ProdutoController` — **e isso é o ponto**: a fronteira que ela protege é
justamente a de não tocá-los.

Nenhum provider externo. A forma do `ListingSuggestion` (§3.4) **não foi
reaberta**. Nenhum timeout, nenhuma configuração de PDO. As decisões congeladas
da CAT-05D e da CAT-05E não foram tocadas.

Pint rodou apenas nos dois arquivos desta subfase.

---

## 11. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05F-1** | O assistente **captura** exceção do motor e devolve `vazia()`, em vez de propagar | Regra 3 + §3.2: a garantia mora na única porta, onde ninguém pode esquecê-la |
| **D-CAT-05F-2** | Captura **em separado** por etapa — degradação parcial | Perder a sugestão inteira por causa da lista de semelhantes seria jogar fora o principal por causa do acessório |
| **D-CAT-05F-3** | `QueryException` entra no log pelo SQLSTATE, sem SQL nem bindings | `getMessage()` interpola os bindings e gravaria o texto do lojista — que a C-2 diz poder conter PII. §5.3 proíbe |
| **D-CAT-05F-4** | Sem sinal de modo degradado agora (dívida F-1) | As alternativas eram reabrir a forma congelada da §3.4 ou corromper o `missing_information` da CAT-05E |
| **D-CAT-05F-5** | **C-2 vira gate da CAT-06**, não item da CAT-10 | Pela ordem das fases, a CAT-10 vem quatro fases depois de o texto começar a sair |
| **D-CAT-05F-6** | Timeout fora de escopo, destino CAT-06 | Não há chamada que penda; a única forma de impor limite seria atributo global de PDO |
| **D-CAT-05F-7** | A CAT-05F escreve o teste da regra 3; a CAT-10 **herda a verificação** | Duas fases não podem ser donas do mesmo teste, e o acoplamento da CAT-09 precisa da rede pronta antes |

---

## 12. Situação

```text
CAT-05F — IMPLEMENTAÇÃO CONCLUÍDA · AGUARDANDO REVISÃO DO DIFF
```

Suíte verde em 1126 testes sobre a versão final. Sem commit, sem push.

**Próxima:** CAT-05G — testes, custo de consulta e segurança. Ela herda a
observação de custo do `lazy-load` em `ContextSanitizer::termosUteis()`,
registrada pela CAT-05E.
