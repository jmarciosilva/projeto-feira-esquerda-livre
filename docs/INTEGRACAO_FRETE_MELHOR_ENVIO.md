# Integração de Frete Melhor Envio

## Variáveis de ambiente

```env
MELHOR_ENVIO_BASE_URL=https://sandbox.melhorenvio.com.br
MELHOR_ENVIO_TOKEN=
MELHOR_ENVIO_ENVIRONMENT=sandbox
```

O MVP usa uma conta única da plataforma Feira Esquerda Livre. Não há credenciais fixas no código. O token pode vir do `.env` ou das configurações administrativas já existentes para Melhor Envio.

## Fluxo de cálculo

1. O cliente acessa o checkout e escolhe entrega em casa.
2. O cliente informa ou confirma o CEP de destino.
3. O checkout agrupa os itens por loja.
4. Para cada loja, o sistema busca o CEP de origem em `expositores.zipcode`.
5. Para cada produto físico, o sistema envia peso, altura, largura, comprimento, valor e quantidade ao Melhor Envio.
6. O retorno é normalizado para o formato usado pelo checkout.
7. O cliente seleciona uma opção por loja e o total do pedido é atualizado com o frete selecionado.

## Estrutura criada

- `config/melhorenvio.php`: leitura das variáveis de ambiente.
- `app/Services/Shipping/MelhorEnvioService.php`: cálculo de frete, montagem do payload e tratamento de erro da API.
- `app/DTO/ShippingQuoteData.php`: retorno padronizado de cotação.
- `app/Http/Controllers/ShippingController.php`: endpoint HTTP para cotações.
- Migrations para origem da loja e dimensões dos produtos.
- Checkout Livewire com consulta, listagem e seleção de frete.

## Endpoint

`POST /shipping/quote`

Payload:

```json
{
  "store_id": 1,
  "destination_zipcode": "01001000",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

Resposta normalizada:

```json
{
  "success": true,
  "store_id": 1,
  "quotes": [
    {
      "service_id": "1",
      "company": "Correios",
      "service_name": "PAC",
      "price": 24.9,
      "delivery_time": 6,
      "currency": "BRL",
      "error_message": null
    }
  ]
}
```

## Limitações do MVP

- Usa uma única conta/token do Melhor Envio da plataforma.
- Não compra etiqueta.
- Não gera etiqueta.
- Não rastreia envio.
- Não faz OAuth por lojista.
- Não implementa split de frete.
- Produtos sem peso ou dimensões não bloqueiam o checkout; o sistema retorna mensagem clara e mantém o fluxo manual possível.

## Próximos passos

- Criar OAuth2 por lojista.
- Persistir cotações selecionadas em uma tabela `order_shippings`.
- Comprar e gerar etiquetas.
- Adicionar rastreamento e webhooks.
- Separar frete por loja para repasse financeiro quando o split estiver ativo.
