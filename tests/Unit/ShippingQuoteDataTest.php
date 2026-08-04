<?php

namespace Tests\Unit;

use App\DTO\ShippingQuoteData;
use PHPUnit\Framework\TestCase;

class ShippingQuoteDataTest extends TestCase
{
    public function test_valid_quote_is_not_treated_as_error(): void
    {
        $quote = ShippingQuoteData::fromMelhorEnvio([
            'id' => 1,
            'name' => 'PAC',
            'price' => '24.90',
            'delivery_time' => 7,
            'company' => ['name' => 'Correios'],
        ]);

        $this->assertNull($quote->error_message);
        $this->assertSame(24.9, $quote->price);
    }

    public function test_quote_without_price_and_without_explicit_error_becomes_unavailable(): void
    {
        // Reproduz o caso real: a Melhor Envio devolve uma transportadora sem preco
        // valido (sem contrato ativo, por exemplo) mas sem preencher "error" no
        // formato que o extractErrorMessage reconhece.
        $quote = ShippingQuoteData::fromMelhorEnvio([
            'id' => 2,
            'name' => 'Expresso',
            'company' => ['name' => 'Jet'],
            'price' => null,
        ]);

        $this->assertNotNull($quote->error_message);
        $this->assertStringContainsString('Jet', $quote->error_message);
        $this->assertStringContainsString('indisponível', $quote->error_message);
        $this->assertNull($quote->price);
    }

    public function test_quote_with_zero_price_becomes_unavailable(): void
    {
        $quote = ShippingQuoteData::fromMelhorEnvio([
            'id' => 3,
            'name' => 'Sedex',
            'company' => ['name' => 'Correios'],
            'price' => 0,
        ]);

        $this->assertNotNull($quote->error_message);
        $this->assertNull($quote->price);
    }

    public function test_quote_with_no_company_or_service_name_gets_generic_message(): void
    {
        $quote = ShippingQuoteData::fromMelhorEnvio([]);

        $this->assertSame('Opção de frete indisponível para este pedido.', $quote->error_message);
    }

    public function test_price_is_nulled_when_api_sends_both_error_and_a_price(): void
    {
        $quote = ShippingQuoteData::fromMelhorEnvio([
            'id' => 5,
            'name' => 'PAC',
            'company' => ['name' => 'Correios'],
            'price' => '24.90',
            'error' => 'Peso excede o limite da transportadora.',
        ]);

        $this->assertSame('Peso excede o limite da transportadora.', $quote->error_message);
        $this->assertNull($quote->price);
    }

    public function test_explicit_error_from_api_is_preserved(): void
    {
        $quote = ShippingQuoteData::fromMelhorEnvio([
            'id' => 4,
            'name' => 'PAC',
            'company' => ['name' => 'Correios'],
            'error' => 'Peso excede o limite da transportadora.',
        ]);

        $this->assertSame('Peso excede o limite da transportadora.', $quote->error_message);
    }

    public function test_frenet_valid_quote_is_not_treated_as_error(): void
    {
        $quote = ShippingQuoteData::fromFrenet([
            'Carrier' => 'Correios',
            'ServiceCode' => '04510',
            'ServiceDescription' => 'PAC',
            'ShippingPrice' => '45.50',
            'DeliveryTime' => '5',
            'Error' => false,
            'Msg' => 'Cotação realizada com sucesso',
        ]);

        $this->assertNull($quote->error_message);
        $this->assertSame(45.5, $quote->price);
        $this->assertSame(5, $quote->delivery_time);
        $this->assertSame('Correios', $quote->company);
        $this->assertSame('PAC', $quote->service_name);
    }

    public function test_frenet_explicit_error_uses_msg(): void
    {
        $quote = ShippingQuoteData::fromFrenet([
            'Carrier' => 'Jadlog',
            'ServiceDescription' => '.Package',
            'Error' => true,
            'Msg' => 'CEP de origem não atendido.',
        ]);

        $this->assertSame('CEP de origem não atendido.', $quote->error_message);
        $this->assertNull($quote->price);
    }

    public function test_frenet_quote_without_price_and_without_error_flag_becomes_unavailable(): void
    {
        $quote = ShippingQuoteData::fromFrenet([
            'Carrier' => 'Loggi',
            'ServiceDescription' => 'Expresso',
            'ShippingPrice' => null,
            'Error' => false,
        ]);

        $this->assertNotNull($quote->error_message);
        $this->assertStringContainsString('Loggi', $quote->error_message);
        $this->assertNull($quote->price);
    }
}
