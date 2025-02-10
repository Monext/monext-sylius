<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

use MonextSyliusPlugin\Api\MonextClient;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;

class MonextClientHelper
{
    public function __construct(
        private MonextClient $monextClient,
        private MonextPayloadHelper $monextPayloadHelper,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function getSession(string $sessionToken, PaymentInterface $payment): array
    {
        $this->configureClient($payment);

        return $this->monextClient->getSession($sessionToken);
    }

    /**
     * @return array<mixed>
     */
    public function createSession(PaymentInterface $payment, TokenInterface $securityToken): array
    {
        $this->configureClient($payment);

        return $this->monextClient->createSession(
            $this->monextPayloadHelper->getCreateSessionPayload($payment, $securityToken)
        );
    }

    /**
     * @return array<mixed>
     */
    public function getTransaction(string $transactionId, PaymentInterface $payment): array
    {
        $this->configureClient($payment);

        return $this->monextClient->getTransaction($transactionId);
    }

    /**
     * @return array<mixed>
     */
    public function captureTransaction(string $transactionId, PaymentInterface $payment): array
    {
        $this->configureClient($payment);

        return $this->monextClient->captureTransaction(
            $transactionId,
            $this->monextPayloadHelper->getCaptureTransactionPayload($payment)
        );
    }

    /**
     * @return array<mixed>
     */
    public function refundTransaction(string $transactionId, PaymentInterface $payment): array
    {
        $this->configureClient($payment);

        return $this->monextClient->refundTransaction(
            $transactionId,
            $this->monextPayloadHelper->getRefundTransactionPayload($payment)
        );
    }

    /**
     * @return array<mixed>
     */
    public function cancelTransaction(string $transactionId, PaymentInterface $payment): array
    {
        $this->configureClient($payment);

        return $this->monextClient->cancelTransaction(
            $transactionId,
            $this->monextPayloadHelper->getCancelTransactionPayload($payment)
        );
    }

    private function configureClient(PaymentInterface $payment): void
    {
        if (false === MonextClient::isConfigured()) {
            MonextClient::configure(
                PaymentMethodHelper::getMonextInshopBaseUrl($payment),
                PaymentMethodHelper::getMonextInshopAuthorization($payment)
            );
        }
    }
}
