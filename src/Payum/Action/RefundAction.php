<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum\Action;

use MonextSyliusPlugin\Helpers\MonextClientHelper;
use MonextSyliusPlugin\Helpers\PaymentDetailsHelper;
use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Refund;
use Sylius\Component\Core\Model\PaymentInterface;

class RefundAction implements ActionInterface
{
    public function __construct(
        private MonextClientHelper $monextHelper,
    ) {
    }

    /**
     * @param mixed|Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        // Check status before trying to refund ?

        if (null !== $transactionId = PaymentDetailsHelper::getLastTransactionIdForRefund($payment)) {
            $refundApiResponse = $this->monextHelper->refundTransaction($transactionId, $payment);

            PaymentDetailsHelper::addPaymentDetails(
                $payment,
                $refundApiResponse
            );

            if (!isset($refundApiResponse['result']['title'])) {
                throw new \Exception(sprintf('ERROR: %s', $refundApiResponse['detail']));
            } elseif ('ACCEPTED' !== $refundApiResponse['result']['title']) {
                throw new \Exception(sprintf('ERROR: %s', $refundApiResponse['result']['detail']));
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Refund
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
