<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Api;

/**
 * Specific interface for Monext API.
 */
interface ApiInterface
{
    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function createSession(array $payload): array;

    /**
     * @return array<mixed>
     */
    public function getSession(string $sessionId): array;

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function captureTransaction(string $transactionId, array $payload): array;

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function refundTransaction(string $transactionId, array $payload): array;

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function cancelTransaction(string $transactionId, array $payload): array;

    /**
     * @return array<mixed>
     */
    public function getTransaction(string $transactionId): array;
}
