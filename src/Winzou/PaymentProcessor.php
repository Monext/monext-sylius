<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Winzou;

use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Core\Payum;
use Payum\Core\Request\Cancel;
use Payum\Core\Request\Capture;
use Payum\Core\Request\Refund;
use Sylius\Component\Payment\Model\PaymentInterface;

class PaymentProcessor
{
    public function __construct(
        private Payum $payum,
    ) {
    }

    public function cancel(PaymentInterface $payment): void
    {
        if (PaymentMethodHelper::isMonextPayment($payment)) {
            $this->payum->getGateway(PaymentMethodHelper::getGatewayName($payment))->execute(new Cancel($payment));
        }
    }

    public function refund(PaymentInterface $payment): void
    {
        if (PaymentMethodHelper::isMonextPayment($payment)) {
            $this->payum->getGateway(PaymentMethodHelper::getGatewayName($payment))->execute(new Refund($payment));
        }
    }

    public function complete(PaymentInterface $payment): void
    {
        if (PaymentMethodHelper::isMonextPayment($payment)) {
            $this->payum->getGateway(PaymentMethodHelper::getGatewayName($payment))->execute(new Capture($payment));
        }
    }
}
