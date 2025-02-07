<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\PaymentInterface;

class PaymentMethodHelper
{
    public static function getGatewayName(PaymentInterface $payment): string
    {
        /**
         * @var PaymentMethodInterface $paymentMethod
         */
        $paymentMethod = $payment->getMethod();

        return $paymentMethod->getGatewayConfig()->getGatewayName();
    }

    public static function getFactoryName(PaymentInterface $payment): string
    {
        /**
         * @var PaymentMethodInterface $paymentMethod
         */
        $paymentMethod = $payment->getMethod();

        return $paymentMethod->getGatewayConfig()->getFactoryName();
    }

    public static function isMonextPayment(PaymentInterface $payment): bool
    {
        return 'monext' === static::getFactoryName($payment);
    }

    public static function isMonextInshopPayment(PaymentInterface $payment): bool
    {
        if (!static::isMonextPayment($payment)) {
            return false;
        }

        return ConfigHelper::FIELD_VALUE_INTEGRATION_TYPE_INSHOP
            === static::extractConfigValue($payment, ConfigHelper::FIELD_INTEGRATION_TYPE);
    }

    // If useful later, remove comments
    public static function isMonextRedirectPayment(PaymentInterface $payment): bool
    {
        if (!static::isMonextPayment($payment)) {
            return false;
        }

        return ConfigHelper::FIELD_VALUE_INTEGRATION_TYPE_REDIRECT
            === static::extractConfigValue($payment, ConfigHelper::FIELD_INTEGRATION_TYPE);
    }

    public static function getMonextInshopBaseUrl(PaymentInterface $payment): ?string
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return static::extractConfigValue($payment, ConfigHelper::FIELD_ENVIRONMENT);
    }

    public static function getMonextInshopAuthorization(PaymentInterface $payment): ?string
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return static::extractConfigValue($payment, ConfigHelper::FIELD_API_KEY);
    }

    public static function getMonextPointOfSale(PaymentInterface $payment): ?string
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return static::extractConfigValue($payment, ConfigHelper::FIELD_POINT_OF_SALE);
    }

    public static function getMonextCaptureType(PaymentInterface $payment): ?string
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return static::extractConfigValue($payment, ConfigHelper::FIELD_CAPTURE_TYPE);
    }

    /**
     * @return array<mixed>|null
     */
    public static function getMonextManualCaptureTransitions(PaymentInterface $payment): ?array
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return explode(',', static::extractConfigValue($payment, ConfigHelper::FIELD_MANUAL_CAPTURE_TRANSITION));
    }

    public static function getMonextContractNumbers(PaymentInterface $payment): ?string
    {
        if (!static::isMonextPayment($payment)) {
            return null;
        }

        return static::extractConfigValue($payment, ConfigHelper::FIELD_CONTRACTS_NUMBERS);
    }

    protected static function extractConfigValue(PaymentInterface $payment, string $configKey): ?string
    {
        /**
         * @var PaymentMethodInterface $paymentMethod
         */
        $paymentMethod = $payment->getMethod();

        return $paymentMethod->getGatewayConfig()->getConfig()[$configKey] ?? null;
    }
}
