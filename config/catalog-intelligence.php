<?php

/*
|--------------------------------------------------------------------------
| Catalog Intelligence
|--------------------------------------------------------------------------
|
| Configuração do módulo de inteligência de catálogo (app/CatalogIntelligence).
|
| Este arquivo é de **política, não de conexão**. A D-CAT-06B-2 e a entrada da
| CAT-06 no roadmap proíbem, nominalmente, que ele ganhe chave de credencial,
| nome de fornecedor, endpoint ou segredo — a fase entrega contrato, Fake, Null,
| limiar, redator e guard, e nenhum texto sai da aplicação ao fim dela.
|
| Se um dia houver fornecedor real, a credencial dele mora em config/services.php
| como a de qualquer integração, e não aqui.
|
*/

return [

    /*
    | Limiar de fallback — "há conhecimento suficiente?"
    |
    | `ListingContext::lacunas()` enumera exatamente cinco lacunas possíveis:
    | short_description, description, category, attributes e knowledge. Este
    | número é a partir de quantas delas o conhecimento interno deixa de ser
    | considerado suficiente.
    |
    | A comparação é `>=`: com o limiar em 3, três lacunas já são insuficientes.
    | O caso de borda é testado dos dois lados em SuggestionPolicyTest.
    |
    | ## Por que 3, e não 1 ou 5
    |
    | Com 1, qualquer item sem categoria cairia em fallback — e categoria é
    | escolha do lojista, que consulta externa nenhuma resolve. Com 5, só um
    | item sobre o qual não se sabe absolutamente nada acionaria o fallback, e
    | aí não há nem nome de onde partir.
    |
    | 3 de 5 é o ponto em que o assistente interno passa a ter menos material do
    | que falta. Não é constante sagrada: é o número que a CAT-06G vai validar
    | contra os 75 itens reais, do mesmo modo que a CAT-05H validou o resto.
    |
    | Vale como piso de decisão, nunca como autorização de gasto: quem decide
    | *consultar* é a CAT-06G, e o custo por consulta é a dívida B-6, ainda em
    | aberto.
    */
    'fallback' => [
        'minimum_gaps' => (int) env('CATALOG_AI_MINIMUM_GAPS', 3),
    ],

];
