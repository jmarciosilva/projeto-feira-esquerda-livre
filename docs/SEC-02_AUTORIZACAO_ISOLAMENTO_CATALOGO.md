# SEC-02 --- Segurança, autorização e isolamento do catálogo por expositor

Você está trabalhando no projeto **Feira Esquerda Livre**.

A CAT-01 --- Auditoria e Arquitetura da Catalog Intelligence foi
concluída e commitada no commit:

`67f545c — CAT-01: auditoria e arquitetura da inteligência de catálogo`

Antes de iniciar qualquer implementação da CAT-02, devemos resolver uma
vulnerabilidade de autorização identificada durante a CAT-01.

Esta tarefa deve ser tratada como uma trilha própria de segurança:

**SEC-02 --- Autorização e isolamento do catálogo por expositor**

A SEC-02 é independente da Catalog Intelligence.

NÃO implemente CAT-02.\
NÃO implemente inteligência artificial.\
NÃO altere a arquitetura proposta da Catalog Intelligence, salvo
documentação estritamente necessária para registrar que a SEC-02 é
pré-requisito de segurança.

------------------------------------------------------------------------

## 1. Objetivo principal

Garantir por construção que:

> Um lojista/expositor somente pode visualizar, carregar, editar,
> alterar, excluir ou executar operações relacionadas aos produtos
> pertencentes ao próprio expositor.

O conhecimento do ID, slug, URL ou qualquer outro identificador de um
produto não pode permitir acesso ou mutação por outro lojista.

A SEC-02 deve eliminar possíveis vulnerabilidades de:

-   IDOR --- Insecure Direct Object Reference;
-   Broken Object Level Authorization;
-   edição de produto de outro expositor;
-   transferência indevida de propriedade;
-   remoção de imagem de produto alheio;
-   alteração de FAQ de produto alheio;
-   alteração de informações digitais/AVA de produto alheio;
-   manipulação de route-model binding;
-   acesso indireto por chamadas Livewire;
-   inconsistência de autorização entre aplicação Web e API mobile.

A solução deve aplicar **defesa em profundidade**.

Não considere suficiente colocar apenas um `authorize()` no `mount()`.

------------------------------------------------------------------------

## 2. Estado conhecido antes da SEC-02

A CAT-01 encontrou uma diferença importante entre Web e API.

### Livewire

O `ProdutoForm` recebe um `Product` através de route-model binding.

A auditoria encontrou risco de o componente carregar um produto
pertencente a outro expositor sem verificar sua propriedade.

Também foi identificado que o `save()` monta os dados usando o
`expositor_id` do usuário autenticado.

Isso pode criar o seguinte cenário:

``` text
Produto pertence ao Expositor A
        ↓
Expositor B conhece/manipula o identificador
        ↓
ProdutoForm carrega o produto
        ↓
não existe proteção suficiente de propriedade
        ↓
Expositor B salva
        ↓
produto pode ser alterado
        ↓
expositor_id pode passar a ser B
```

Isso deve ser considerado um risco de segurança até que os testes da
SEC-02 provem o contrário.

### API mobile

A API já possui proteção explícita semelhante a:

``` php
abort_unless(
    $product->expositor_id === $request->user()->expositor->id,
    403
);
```

e utiliza essa proteção em operações como:

-   show;
-   update;
-   destroy.

No update da API, o `expositor_id` original do produto é preservado.

### Padrão encontrado em outras áreas

Em partes sensíveis da área do lojista, o projeto frequentemente
restringe a query antes de recuperar o objeto:

``` php
OrderSplit::where('expositor_id', auth()->user()->expositor->id)
    ->findOrFail($splitId);
```

Esse padrão já existe no projeto e deve ser considerado durante a
decisão arquitetural da SEC-02.

------------------------------------------------------------------------

## 3. Regra fundamental de propriedade

A seguinte invariante deve existir ao final da SEC-02:

``` text
CREATE:
expositor_id = expositor autenticado

UPDATE:
expositor_id NÃO pode ser redefinido pelo formulário

DELETE:
somente proprietário pode executar

MUTAÇÕES AUXILIARES:
somente proprietário pode executar
```

Mais especificamente:

> Nenhuma operação de edição pode recalcular ou substituir
> `expositor_id` de um produto existente.

Em criação, o sistema pode determinar o proprietário:

``` text
expositor_id = expositor autenticado
```

Em edição, prefira que `expositor_id` nem participe do payload de
atualização.

Se tecnicamente necessário, preserve explicitamente o proprietário
original.

Nunca aceite `expositor_id` vindo do navegador, Livewire, request ou
payload externo como fonte de autoridade.

------------------------------------------------------------------------

## 4. Antes de alterar código --- baseline obrigatório

Antes de qualquer alteração:

1.  Execute:

``` bash
git status
git log -5 --oneline
git diff --check
```

2.  Confirme:

-   branch atual;
-   HEAD atual;
-   working tree;
-   relação com `origin/main`.

3.  Confirme que o commit da CAT-01 está presente:

``` text
67f545c
```

ou que existe commit posterior contendo essa alteração.

4.  Suba/confirme o ambiente Docker conforme a documentação atual do
    projeto.

5.  Execute a suíte completa.

O último baseline conhecido antes desta trilha era:

``` text
455 passed
1.318 assertions
0 failures
```

Não presuma que esse número continua sendo o baseline.

**O resultado obtido agora é o baseline oficial da SEC-02.**

Registre no `docs/ROADMAP.md`:

``` text
SEC-02 baseline:
- commit:
- testes:
- assertions:
- failures:
- data:
```

Se o baseline estiver vermelho ANTES das alterações:

**PARE.**

Reporte a falha e não misture correção preexistente com SEC-02.

------------------------------------------------------------------------

## 5. Criar trilha SEC-02 no ROADMAP

Utilize o arquivo existente:

`docs/ROADMAP.md`

Não crie um novo roadmap de segurança salvo se houver justificativa
técnica realmente forte.

Crie uma seção:

### SEC-02 --- Autorização e isolamento do catálogo por expositor

A trilha deve possuir exatamente estas fases macro:

``` text
[ ] SEC-02A — Auditoria das superfícies de acesso
[ ] SEC-02B — Autorização do ProdutoForm
[ ] SEC-02C — Imutabilidade de propriedade e proteção das mutações
[ ] SEC-02D — Consistência Web/API
[ ] SEC-02E — Testes de IDOR e regressão
[ ] SEC-02F — Hardening final, documentação e validação
```

Estados permitidos:

``` text
NÃO INICIADA
EM ANDAMENTO
CONCLUÍDA
BLOQUEADA
```

Cada fase deve possuir:

-   objetivo;
-   risco;
-   arquivos envolvidos;
-   implementação realizada;
-   testes relacionados;
-   critérios de aceite;
-   resultado;
-   status.

Uma fase somente pode ser marcada como `CONCLUÍDA` depois que seus
critérios forem comprovados.

------------------------------------------------------------------------

## 6. SEC-02A --- Auditoria das superfícies de acesso

### Objetivo

Mapear TODAS as superfícies pelas quais um lojista pode acessar ou
modificar um produto.

Nesta fase, priorize auditoria.

Não saia imediatamente alterando `ProdutoForm`.

Audite pelo menos:

### Rotas

-   criação de produto;
-   edição;
-   visualização;
-   exclusão;
-   operações Livewire;
-   endpoints API;
-   qualquer endpoint auxiliar relacionado a produto.

### Livewire

Audite:

-   `ProdutoForm`;
-   `ProdutoIndex`;
-   componentes relacionados;
-   métodos públicos Livewire;
-   `mount()`;
-   `save()`;
-   `removeImage()`;
-   exclusão;
-   FAQs;
-   AVA/produto digital;
-   qualquer método que receba ID de produto, imagem, FAQ ou entidade
    relacionada.

### API

Audite:

`App\Http\Controllers\Api\V1\Lojista\ProdutoController`

e qualquer outro controller relacionado.

### Views

Procure:

-   links de edição;
-   links de exclusão;
-   IDs enviados para Livewire;
-   slugs;
-   parâmetros manipuláveis;
-   botões que executem métodos públicos.

### Models e autorização

Verifique:

-   `Product`;
-   relações com `Expositor`;
-   `ProductFaq`;
-   AVA;
-   Policies existentes;
-   Gates existentes;
-   middleware da área do lojista;
-   padrão de autorização utilizado no projeto.

### Testes

Procure testes existentes relacionados a:

-   produto;
-   lojista;
-   expositor;
-   API;
-   autorização;
-   acesso negado;
-   403;
-   404;
-   route-model binding.

### Entrega da SEC-02A

Produza uma tabela no relatório:

  Superfície   Operação   Proteção atual   Risco   Ação
  ------------ ---------- ---------------- ------- ------

Classifique cada ocorrência como:

``` text
PROTEGIDA
VULNERÁVEL
PROTEÇÃO PARCIAL
NÃO APLICÁVEL
```

### Parada arquitetural

Após a auditoria, decida tecnicamente entre:

1.  query escopada por `expositor_id`;
2.  `ProductPolicy`;
3.  combinação dos dois.

Não introduza `ProductPolicy` apenas por preferência arquitetural.

Use Policy se ela realmente:

-   centralizar autorização;
-   reduzir duplicação Web/API;
-   tornar as regras mais difíceis de contornar;
-   combinar com Laravel e com o projeto atual.

Se uma combinação Policy + query escopada oferecer defesa em
profundidade sem complexidade excessiva, ela pode ser preferível.

Documente a decisão.

SEC-02A só pode ser concluída depois dessa auditoria.

------------------------------------------------------------------------

## 7. SEC-02B --- Autorização do ProdutoForm

### Objetivo

Eliminar o IDOR no carregamento do produto.

Garanta que:

``` text
lojista A → produto A → permitido
lojista B → produto A → negado
```

A proteção não pode depender apenas de o link não aparecer na interface.

Considere ataque direto à rota real de edição encontrada no projeto.

Teste também manipulação do route-model binding.

### Requisitos

Ao carregar produto existente:

-   usuário precisa estar autenticado;
-   precisa possuir expositor válido;
-   produto precisa pertencer ao expositor;
-   caso contrário, acesso deve ser negado.

Escolha conscientemente entre `403` e `404`.

Se o padrão atual do projeto favorecer não revelar a existência de
recursos alheios, `404` pode ser preferível.

Não mude o padrão de toda a aplicação nesta tarefa.

Documente a escolha.

------------------------------------------------------------------------

## 8. SEC-02C --- Imutabilidade de propriedade e proteção das mutações

Esta é a parte mais importante da trilha.

O `mount()` protegido NÃO é proteção suficiente em Livewire.

Cada mutação sensível precisa ser segura mesmo considerando chamadas
Livewire manipuladas.

Audite e proteja individualmente:

``` text
save()
removeImage()
delete()/destroy(), se existir
FAQ
AVA
produto digital
upload de imagem
remoção de imagem
qualquer método público relacionado
```

### Save

Para CREATE:

``` text
expositor_id = expositor autenticado
```

Para UPDATE:

`expositor_id` não deve ser recalculado.

Idealmente:

``` php
$product->update([
    // campos editáveis
    // expositor_id NÃO entra aqui
]);
```

A autorização deve ocorrer antes de:

-   update;
-   sincronização de FAQ;
-   sincronização AVA;
-   manipulação destrutiva de imagens.

### Imagens

Um lojista não pode:

-   remover imagem de produto alheio;
-   substituir imagem de produto alheio;
-   provocar exclusão física do arquivo de outro expositor.

Observe especialmente a ordem:

``` text
AUTORIZAR
    ↓
realizar I/O destrutivo
```

Nunca:

``` text
apagar arquivo
    ↓
descobrir que não tinha autorização
```

### FAQ

A alteração das FAQs deve ocorrer somente depois de a propriedade do
produto estar validada.

### AVA

Se produto digital cria/sincroniza entidades relacionadas, a autorização
do produto pai deve ocorrer ANTES da mutação.

------------------------------------------------------------------------

## 9. SEC-02D --- Consistência Web/API

A API já possui proteção.

Não reescreva a API sem necessidade.

Faça uma revisão comparativa:

``` text
WEB              API
create           store
edit/save        update
delete           destroy
view             show
```

A regra de propriedade deve ser semanticamente equivalente.

Se ProductPolicy for introduzida e fizer sentido reutilizá-la na API,
faça a migração cuidadosamente e com testes.

Caso contrário, preserve a implementação da API.

O importante é a invariante, não uniformidade estética.

### Regra obrigatória

Não reduza a segurança da API para fazê-la parecer com o Livewire.

É o Livewire vulnerável que precisa alcançar ou superar o nível de
proteção da API.

------------------------------------------------------------------------

## 10. SEC-02E --- Testes de IDOR e regressão

Crie testes específicos de segurança.

Não considere testes genéricos de CRUD suficientes.

Crie pelo menos dois expositores independentes:

``` text
Expositor A
  └── Lojista A
       └── Produto A

Expositor B
  └── Lojista B
       └── Produto B
```

### Casos positivos

Teste, quando essas operações existirem:

``` text
A cria produto A
A abre produto A
A edita produto A
A altera imagem A
A altera FAQ A
```

### Casos negativos obrigatórios

#### IDOR por URL

``` text
B conhece ID do produto A
B tenta abrir edição de A
→ negado
```

#### IDOR por Livewire

``` text
B tenta montar componente com Product A
→ negado
```

#### Save manipulado

``` text
B tenta executar save() sobre Product A
→ negado
```

Depois:

``` text
Product A continua pertencendo a A
dados continuam inalterados
```

#### Transferência de propriedade

Tente explicitamente provocar:

``` text
productA.expositor_id = expositorB
```

pelo fluxo vulnerável anterior.

Resultado:

``` text
expositor_id permanece A
```

#### Imagem

``` text
B tenta removeImage() do produto A
→ negado
→ arquivo/metadado permanece
```

#### FAQ

``` text
B tenta modificar FAQ do produto A
→ negado
→ FAQ permanece
```

#### API

Teste:

``` text
GET produto alheio
PUT produto alheio
DELETE produto alheio
```

Todos devem falhar conforme o contrato da API.

#### Listagem

``` text
Lojista A não recebe Produto B na própria listagem.
```

#### Mass assignment / payload

Se aplicável, envie:

``` json
{
  "expositor_id": "<outro expositor>"
}
```

e prove que o valor não é utilizado como autoridade.

------------------------------------------------------------------------

## 11. ProductFactory

A CAT-01 encontrou ausência de `ProductFactory`.

Se os testes da SEC-02 ficarem excessivamente repetitivos por causa
disso, você ESTÁ autorizado a criar uma `ProductFactory`.

Mas:

-   faça apenas se melhorar concretamente os testes;
-   mantenha defaults mínimos e válidos;
-   não antecipe campos da CAT-02;
-   não crie `short_description`;
-   não crie tags;
-   não crie embeddings;
-   não crie dados de inteligência.

Se criada, documente que foi antecipada por necessidade de testes da
SEC-02 e continua reutilizável pela CAT-04.

------------------------------------------------------------------------

## 12. SEC-02F --- Hardening final

Após todas as correções, faça uma nova busca global por operações sobre
`Product` dentro da área do lojista.

Procure especialmente:

``` text
Product::find
Product::findOrFail
Product::where
Product $product
$product->update
$product->delete
removeImage
ProductFaq
AvaCourse
expositor_id
```

A busca deve incluir pelo menos:

``` text
app/
routes/
resources/
tests/
```

Classifique qualquer ocorrência relevante restante.

Não deixe superfície conhecida sem decisão.

------------------------------------------------------------------------

## 13. Testes por fase

Não espere SEC-02F para descobrir regressões.

Após cada fase funcional:

``` text
SEC-02B
→ testes direcionados

SEC-02C
→ testes direcionados

SEC-02D
→ testes Web/API

SEC-02E
→ suíte de segurança completa
```

Na SEC-02F execute obrigatoriamente:

``` bash
php artisan test
```

dentro do ambiente Docker conforme o padrão atual do projeto.

Compare:

``` text
BASELINE SEC-02
versus
RESULTADO FINAL
```

Nenhum teste existente pode ser:

-   removido;
-   ignorado;
-   flexibilizado artificialmente;
-   alterado apenas para esconder regressão.

------------------------------------------------------------------------

## 14. Banco de dados

SEC-02 é prioritariamente uma correção de autorização.

Não espero migration.

Se durante a auditoria surgir necessidade REAL de alteração de banco:

**PARE e justifique antes.**

Não crie migration apenas para solucionar autorização.

NÃO execute:

``` text
migrate:fresh
db:wipe
drop
reset destrutivo
```

O banco de desenvolvimento deve ser preservado.

------------------------------------------------------------------------

## 15. Catalog Intelligence

NÃO iniciar:

``` text
CAT-02
CAT-03
CAT-04
...
```

Não implementar:

-   IA;
-   short_description;
-   keywords;
-   tags;
-   similaridade;
-   embeddings;
-   base de conhecimento;
-   provider externo;
-   ListingAssistant.

A única relação permitida com a trilha CAT é documentação:

``` text
CAT-02 depende da conclusão da SEC-02.
```

------------------------------------------------------------------------

## 16. Documentação

Atualize obrigatoriamente:

`docs/ROADMAP.md`

com:

``` text
SEC-02A — status
SEC-02B — status
SEC-02C — status
SEC-02D — status
SEC-02E — status
SEC-02F — status
```

Registre:

-   vulnerabilidade encontrada;
-   superfície afetada;
-   decisão arquitetural;
-   comportamento anterior;
-   comportamento corrigido;
-   testes criados;
-   baseline;
-   resultado final;
-   garantia de isolamento.

Atualize `docs/CATALOG_INTELLIGENCE.md` apenas se for necessário
registrar SEC-02 como pré-requisito concluído para a CAT-02.

Não transforme documentação em diário de implementação.

Documente o estado final.

------------------------------------------------------------------------

## 17. Git

Durante toda a SEC-02:

NÃO faça commit automaticamente.\
NÃO faça push.\
NÃO altere branch sem necessidade.

Ao final quero primeiro uma revisão pré-commit.

Execute:

``` bash
git status --short
git diff --stat
git diff --check
git diff
```

Se houver arquivos staged:

``` bash
git diff --cached --check
```

Verifique que não entraram:

``` text
.env
.env.backup
*.log
*.bak
*.sql
tokens
credenciais
arquivos temporários
artefatos de teste
```

------------------------------------------------------------------------

## 18. Critérios obrigatórios de conclusão

A SEC-02 somente pode ser considerada concluída se TODOS forem
verdadeiros:

``` text
[ ] SEC-02A auditou todas as superfícies relevantes
[ ] ProdutoForm não carrega produto alheio
[ ] save() não altera produto alheio
[ ] removeImage() não altera produto alheio
[ ] FAQ não pode ser alterada por outro expositor
[ ] AVA/relações não podem ser alteradas por outro expositor
[ ] expositor_id é imutável durante edição
[ ] CREATE associa produto ao expositor autenticado
[ ] API continua protegida
[ ] listagem permanece isolada por expositor
[ ] testes explícitos de IDOR existem
[ ] tentativa de transferência de propriedade está testada
[ ] tentativa de acesso direto está testada
[ ] tentativa Livewire está testada
[ ] tentativa API está testada
[ ] nenhuma regressão funcional
[ ] suíte completa verde
[ ] docs/ROADMAP.md atualizado
[ ] CAT-02 não foi iniciada
[ ] git diff --check limpo
```

------------------------------------------------------------------------

## 19. Relatório final obrigatório

Ao concluir, responda exatamente com uma estrutura equivalente a:

### SEC-02 --- Relatório final

#### Baseline

``` text
Commit:
Testes:
Assertions:
Failures:
```

#### SEC-02A --- Auditoria

Tabela:

  Superfície   Antes   Depois   Proteção
  ------------ ------- -------- ----------

#### SEC-02B --- ProdutoForm

Explique:

-   proteção do mount;
-   resposta para recurso alheio;
-   decisão 403 × 404.

#### SEC-02C --- Mutações

Informe separadamente:

``` text
save:
removeImage:
FAQ:
AVA:
expositor_id:
```

#### SEC-02D --- Web/API

Explique como as duas superfícies ficaram consistentes.

#### SEC-02E --- Segurança

Liste os ataques simulados e seus resultados.

Inclua obrigatoriamente:

``` text
IDOR por URL:
IDOR Livewire:
save manipulado:
transferência de expositor:
remoção de imagem:
alteração de FAQ:
API show:
API update:
API destroy:
```

#### SEC-02F --- Hardening

Informe buscas realizadas e qualquer ocorrência restante.

#### Testes

``` text
Baseline:
Final:
Novos testes:
Assertions:
Failures:
```

#### Banco

Confirme:

``` text
migration criada: SIM/NÃO
migrate:fresh: NÃO
dados preservados: SIM
```

#### Catalog Intelligence

Confirme explicitamente:

``` text
CAT-02 iniciada: NÃO
```

#### Roadmap

Apresente:

``` text
SEC-02A — CONCLUÍDA
SEC-02B — CONCLUÍDA
SEC-02C — CONCLUÍDA
SEC-02D — CONCLUÍDA
SEC-02E — CONCLUÍDA
SEC-02F — CONCLUÍDA
```

Se alguma não estiver concluída, SEC-02 inteira NÃO pode ser declarada
concluída.

#### Git

Apresente:

``` text
git status --short
git diff --stat
git diff --check
```

#### Recomendação

Finalize obrigatoriamente com uma das duas frases:

``` text
SEC-02 PRONTA PARA REVISÃO PRÉ-COMMIT.
```

ou:

``` text
SEC-02 NÃO ESTÁ PRONTA PARA COMMIT.
```

Explique objetivamente qualquer bloqueio.

------------------------------------------------------------------------

## 20. Regra de autonomia

Você tem autonomia para executar SEC-02A → SEC-02F sem solicitar
aprovação entre as subfases, desde que:

-   permaneça estritamente dentro da SEC-02;
-   não haja decisão de produto;
-   não seja necessária migration inesperada;
-   não exista risco de perda de dados;
-   não seja necessário alterar regra comercial;
-   não seja necessário reduzir segurança existente;
-   não seja necessário iniciar CAT-02.

Se encontrar uma dessas situações:

**PARE e reporte.**

Fora dessas situações, avance autonomamente pelas seis subfases.

O objetivo é terminar a SEC-02 com evidência técnica suficiente para que
possamos revisar o diff e decidir o commit com segurança.
