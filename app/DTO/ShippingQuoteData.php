<?php

namespace App\DTO;

class ShippingQuoteData
{
    public function __construct(
        public readonly ?string $service_id,
        public readonly ?string $company,
        public readonly ?string $service_name,
        public readonly ?float $price,
        public readonly ?int $delivery_time,
        public readonly string $currency = 'BRL',
        public readonly ?string $error_message = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromMelhorEnvio(array $data): self
    {
        $price = $data['custom_price'] ?? $data['price'] ?? null;
        $deliveryTime = $data['custom_delivery_time'] ?? $data['delivery_time'] ?? null;
        $company = is_array($data['company'] ?? null)
            ? ($data['company']['name'] ?? null)
            : ($data['company'] ?? null);
        $serviceName = isset($data['name']) ? (string) $data['name'] : null;

        $resolvedPrice = is_numeric($price) ? round((float) $price, 2) : null;
        $errorMessage = self::extractErrorMessage($data);
        $hasValidPrice = $resolvedPrice !== null && $resolvedPrice > 0;

        // A API pode devolver uma transportadora sem preço valido (ex: sem contrato
        // ativo para essa rota) sem preencher "error" num formato que reconhecemos.
        // Nunca deixamos essa opcao virar um botao selecionavel em branco ou R$ 0,00.
        if ($errorMessage === null && ! $hasValidPrice) {
            $label = trim(($company ?: '').' '.($serviceName ?: ''));
            $errorMessage = $label !== ''
                ? "{$label}: frete indisponível para este pedido."
                : 'Opção de frete indisponível para este pedido.';
        }

        // Se virou erro, o preco nunca deve ser exibido/usado - mesmo que a API
        // tenha mandado um numero junto com o erro.
        if ($errorMessage !== null) {
            $resolvedPrice = null;
        }

        return new self(
            service_id: isset($data['id']) ? (string) $data['id'] : null,
            company: $company ? (string) $company : null,
            service_name: $serviceName,
            price: $resolvedPrice,
            delivery_time: is_numeric($deliveryTime) ? (int) $deliveryTime : null,
            error_message: $errorMessage,
        );
    }

    public static function error(string $message): self
    {
        return new self(
            service_id: null,
            company: null,
            service_name: null,
            price: null,
            delivery_time: null,
            error_message: $message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service_id' => $this->service_id,
            'company' => $this->company,
            'service_name' => $this->service_name,
            'price' => $this->price,
            'delivery_time' => $this->delivery_time,
            'currency' => $this->currency,
            'error_message' => $this->error_message,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function extractErrorMessage(array $data): ?string
    {
        $error = $data['error'] ?? null;

        if (is_string($error)) {
            return $error;
        }

        if (is_array($error)) {
            return implode(' ', array_filter(array_map('strval', $error)));
        }

        return isset($data['errors']) && is_string($data['errors']) ? $data['errors'] : null;
    }
}
