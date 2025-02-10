<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

class MonextResponseHelper
{
    public const ERROR_KEY = 'error';
    public const RESULT_KEY = 'result';
    public const TITLE_KEY = 'title';
    public const DETAIL_KEY = 'detail';
    public const TYPE_KEY = 'type';
    public const REDIRECT_URL_KEY = 'redirectURL';
    public const SESSION_KEY = 'session';
    public const SESSION_ID_KEY = 'sessionId';
    public const TRANSACTION_KEY = 'transaction';
    public const TRANSACTIONS_KEY = 'transactions';
    public const TRANSACTION_ID_KEY = 'transactionId';
    public const ID_KEY = 'id';

    /**
     * @param array<mixed> $response
     *
     * @return array<mixed>
     */
    public static function parseResponse(array $response): array
    {
        return [
            self::RESULT_KEY => self::getResult($response),
            self::SESSION_KEY => self::getSessionId($response),
            self::REDIRECT_URL_KEY => self::getRedirectUrl($response),
            self::TRANSACTION_KEY => self::getTransaction($response),
        ];
    }

    /**
     * @param array<mixed> $response
     */
    private static function getSessionId(array $response): ?string
    {
        return $response[self::SESSION_ID_KEY] ?? null;
    }

    /**
     * @param array<mixed> $response
     */
    private static function getRedirectUrl(array $response): ?string
    {
        return $response[self::REDIRECT_URL_KEY] ?? null;
    }

    /**
     * @param array<mixed> $response
     *
     * @return array<mixed>
     */
    private static function getResult(array $response): array
    {
        $result = [];

        // Standard case
        if (isset($response[self::RESULT_KEY])) {
            $result = array_intersect_key(
                $response[self::RESULT_KEY],
                [self::TITLE_KEY => null, self::DETAIL_KEY => null]
            );
        }
        // Error (500 API) case
        if (isset($response[self::ERROR_KEY])) {
            $result = array_intersect_key(
                $response,
                [self::TITLE_KEY => null, self::DETAIL_KEY => null]
            );
        }

        return $result;
    }

    /**
     * @param array<mixed> $response
     *
     * @return array<mixed>|null
     */
    private static function getTransaction(array $response): ?array
    {
        $transaction = null;

        if (isset($response[self::TRANSACTION_ID_KEY])) {
            $transaction = $response[self::TRANSACTION_ID_KEY];
        }

        if (isset($response[self::TRANSACTIONS_KEY])) {
            $transaction = array_shift($response[self::TRANSACTIONS_KEY]);
        }

        if (isset($response[self::TRANSACTION_KEY])) {
            $transaction = $response[self::TRANSACTION_KEY];
        }

        return $transaction;
    }
}
