<?php

interface PaymentGatewayInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function isConfigured(): bool;

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function buildCheckoutContext(array $context): array;

    /**
     * @param array<string,mixed> $request
     */
    public function verifyNotify(array $request): bool;

    /**
     * @param array<string,mixed> $request
     */
    public function verifyReturn(array $request): bool;

    public function queryStatus(string $orderNumber): string;
}
