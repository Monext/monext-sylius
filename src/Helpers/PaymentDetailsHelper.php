<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

use Sylius\Component\Payment\Model\PaymentInterface;

class PaymentDetailsHelper
{
    public static function hasSessionToken(PaymentInterface $payment): bool
    {
        return null !== static::getSessionToken($payment);
    }

    public static function getSessionToken(PaymentInterface $payment): ?string
    {
        return static::getSessionTokenDetails($payment)[MonextResponseHelper::SESSION_KEY] ?? null;
    }

    public static function hasRedirectUrl(PaymentInterface $payment): bool
    {
        return null !== static::getRedirectUrl($payment);
    }

    public static function getRedirectUrl(PaymentInterface $payment): ?string
    {
        return static::getSessionTokenDetails($payment)[MonextResponseHelper::REDIRECT_URL_KEY] ?? null;
    }

    /**
     * @return array<mixed>|null
     */
    public static function getSessionTokenDetails(PaymentInterface $payment): ?array
    {
        if (!PaymentMethodHelper::isMonextPayment($payment)) {
            return null;
        }

        $details = $payment->getDetails();

        // Always return the latest details
        krsort($details);

        foreach ($details as $detail) {
            if (isset($detail[MonextResponseHelper::SESSION_KEY])) {
                return $detail;
            }
        }

        return null;
    }

    /**
     * When trying to refund a transaction, we must use the transactionId of the
     * AUTHORIZATION_AND_CAPTURE last transaction if the capture is automatic
     * AUTHORIZATION last transaction if the capture is manuel.
     *
     * If we try to refund a manual transaction with the transactionId of the CAPTURE it won't work
     */
    public static function getLastTransactionIdForRefund(PaymentInterface $payment): ?string
    {
        return static::getLastTransactionId($payment, ['AUTHORIZATION', 'AUTHORIZATION_AND_CAPTURE']);
    }

    /**
     * @param array<string> $transactionTypes
     */
    public static function getLastTransactionId(
        PaymentInterface $payment,
        array $transactionTypes = ['AUTHORIZATION', 'AUTHORIZATION_AND_CAPTURE', 'CAPTURE']
    ): ?string {
        if (!PaymentMethodHelper::isMonextPayment($payment)) {
            return null;
        }

        $details = $payment->getDetails();

        // Always check descending details
        krsort($details);

        foreach ($details as $detail) {
            $type = $detail[MonextResponseHelper::TRANSACTION_KEY][MonextResponseHelper::TYPE_KEY] ?? null;
            if (null !== $type && in_array($type, $transactionTypes, true)) {
                return $detail[MonextResponseHelper::TRANSACTION_KEY][MonextResponseHelper::ID_KEY];
            }
        }

        return null;
    }

    /**
     * @return array<mixed>|null
     */
    public static function getLastResponseDetails(PaymentInterface $payment): ?array
    {
        if (!PaymentMethodHelper::isMonextPayment($payment)) {
            return null;
        }

        $details = $payment->getDetails();

        // Always return the latest details
        krsort($details);

        return array_shift($details);
    }

    /**
     * @param array<mixed> $details
     */
    public static function addPaymentDetails(PaymentInterface $payment, array $details): void
    {
        if (!PaymentMethodHelper::isMonextPayment($payment)) {
            return;
        }

        // Add a new chunk of details
        $payment->setDetails(
            array_merge(
                $payment->getDetails(),
                [date('Y-m-d H:i:s') => MonextResponseHelper::parseResponse($details)]
            )
        );
    }
}
