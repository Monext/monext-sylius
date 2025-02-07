<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Api;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

class MonextClient implements ApiInterface
{
    private const SESSIONS_ENDPOINT = '/v1/checkout/payments/sessions';
    private const TRANSACTIONS_ENDPOINT = '/v1/checkout/transactions';
    private const TRANSACTIONS_CAPTURE_ENDPOINT = '/v1/checkout/transactions/%s/captures';
    private const TRANSACTIONS_CANCEL_ENDPOINT = '/v1/checkout/transactions/%s/cancels';
    private const TRANSACTIONS_REFUND_ENDPOINT = '/v1/checkout/transactions/%s/refunds';
    private static string $baseUrl = '';
    private static string $authorization = '';

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public static function isConfigured(): bool
    {
        return '' !== self::$baseUrl && '' !== self::$authorization;
    }

    public static function configure(string $baseUrl, string $authorization): void
    {
        self::$baseUrl = $baseUrl;
        self::$authorization = $authorization;
    }

    /**
     * @return array<mixed>
     */
    public function getSession(string $sessionId): array
    {
        try {
            $response = $this->getOptionatedClient()->get(
                sprintf('%s/%s', self::SESSIONS_ENDPOINT, $sessionId)
            );

            return json_decode((string) $response->getBody(), true);
        } catch (\Exception|GuzzleException $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function createSession(array $payload): array
    {
        try {
            $response = $this->getOptionatedClient()->post(
                self::SESSIONS_ENDPOINT,
                self::getBody($payload)
            );

            $result = json_decode((string) $response->getBody(), true);

            // Because invalid API credentials returns a 200.
            if (200 === $response->getStatusCode() && isset($result['title']) && 'Unauthorized' === $result['title']) {
                throw new \Exception((string) $response->getBody());
            }

            return $result;
        } catch (\Exception|GuzzleException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function captureTransaction(string $transactionId, array $payload): array
    {
        try {
            $response = $this->getOptionatedClient()->post(
                sprintf(self::TRANSACTIONS_CAPTURE_ENDPOINT, $transactionId),
                self::getBody($payload)
            );

            return json_decode((string) $response->getBody(), true);
        } catch (\Exception|GuzzleException $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function refundTransaction(string $transactionId, array $payload): array
    {
        try {
            $response = $this->getOptionatedClient()->post(
                sprintf(self::TRANSACTIONS_REFUND_ENDPOINT, $transactionId),
                self::getBody($payload)
            );

            return json_decode((string) $response->getBody(), true);
        } catch (\Exception|GuzzleException $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    public function cancelTransaction(string $transactionId, array $payload): array
    {
        try {
            $response = $this->getOptionatedClient()->post(
                sprintf(self::TRANSACTIONS_CANCEL_ENDPOINT, $transactionId),
                self::getBody($payload)
            );

            return json_decode((string) $response->getBody(), true);
        } catch (\Exception|GuzzleException $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @return array<mixed>
     */
    public function getTransaction(string $transactionId): array
    {
        try {
            $response = $this->getOptionatedClient()->get(
                sprintf('%s/%s', self::TRANSACTIONS_ENDPOINT, $transactionId)
            );

            return json_decode((string) $response->getBody(), true);
        } catch (\Exception|GuzzleException $exception) {
            return $this->handleException($exception);
        }
    }

    private function getOptionatedClient(): GuzzleHttpClient
    {
        // Client must be configured at this point !
        if (!static::isConfigured()) {
            throw new \RuntimeException('Client must be configured at this point !');
        }

        return new GuzzleHttpClient(
            [
                'base_uri' => self::$baseUrl,
                'headers' => [
                    'Authorization' => 'Basic '.self::$authorization,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }

    /**
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    private static function getBody(array $payload): array
    {
        return [
            'body' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<mixed>
     */
    private function handleServerException(ServerException $exception): array
    {
        return array_merge(
            [
                'error' => $exception->getMessage(),
                'statusCode' => $exception->getCode(),
            ], json_decode($exception->getResponse()->getBody()->getContents(), true)
        );
    }

    /**
     * @return array<mixed>
     */
    private function handleException(\Exception $exception): array
    {
        $this->logger->error(
            sprintf(
                '[MONEXT] Error during call to API: %s',
                $exception->getMessage()
            )
        );

        if ($exception instanceof ServerException) {
            return $this->handleServerException($exception);
        }

        return [
            'error' => true,
            'statusCode' => 500,
            'title' => 'ERROR',
            'detail' => $exception->getMessage(),
        ];
    }
}
