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

        [$resolvedPrice, $errorMessage] = self::resolvePriceAndError(
            $price,
            self::extractErrorMessage($data),
            $company,
            $serviceName,
        );

        return new self(
            service_id: isset($data['id']) ? (string) $data['id'] : null,
            company: $company ? (string) $company : null,
            service_name: $serviceName,
            price: $resolvedPrice,
            delivery_time: is_numeric($deliveryTime) ? (int) $deliveryTime : null,
            error_message: $errorMessage,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromFrenet(array $data): self
    {
        $company = $data['Carrier'] ?? null;
        $serviceName = $data['ServiceDescription'] ?? null;
        $apiError = ((bool) ($data['Error'] ?? false))
            ? (is_string($data['Msg'] ?? null) && $data['Msg'] !== '' ? $data['Msg'] : 'Frete indisponível para esta transportadora.')
            : null;

        [$resolvedPrice, $errorMessage] = self::resolvePriceAndError(
            $data['ShippingPrice'] ?? null,
            $apiError,
            $company,
            $serviceName,
        );

        return new self(
            service_id: isset($data['ServiceCode']) ? (string) $data['ServiceCode'] : null,
            company: $company ? (string) $company : null,
            service_name: $serviceName ? (string) $serviceName : null,
            price: $resolvedPrice,
            delivery_time: is_numeric($data['DeliveryTime'] ?? null) ? (int) $data['DeliveryTime'] : null,
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
     * Resolve o preço e a mensagem de erro de forma consistente entre provedores:
     * uma cotação sem preço válido (ausente, zero ou negativo) e sem erro explícito
     * da API vira uma mensagem de indisponibilidade — nunca um botão em branco ou
     * "R$ 0,00" selecionável. Quando há erro, o preço nunca é exibido.
     *
     * @return array{0: ?float, 1: ?string}
     */
    private static function resolvePriceAndError(
        mixed $rawPrice,
        ?string $apiErrorMessage,
        mixed $company,
        mixed $serviceName,
    ): array {
        $resolvedPrice = is_numeric($rawPrice) ? round((float) $rawPrice, 2) : null;
        $hasValidPrice = $resolvedPrice !== null && $resolvedPrice > 0;

        $errorMessage = $apiErrorMessage;

        if ($errorMessage === null && ! $hasValidPrice) {
            $label = trim(($company ?: '').' '.($serviceName ?: ''));
            $errorMessage = $label !== ''
                ? "{$label}: frete indisponível para este pedido."
                : 'Opção de frete indisponível para este pedido.';
        }

        if ($errorMessage !== null) {
            $resolvedPrice = null;
        }

        return [$resolvedPrice, $errorMessage];
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
