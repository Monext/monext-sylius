<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum\Action;

use MonextSyliusPlugin\Helpers\MonextClientHelper;
use MonextSyliusPlugin\Helpers\PaymentDetailsHelper;
use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Cancel;
use Sylius\Component\Core\Model\PaymentInterface;

class CancelAction implements ActionInterface
{
    public function __construct(
        private MonextClientHelper $monextHelper,
    ) {
    }

    /**
     * @param mixed|Cancel $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        // Check status before trying to cancel ?
        if (null !== $transactionId = PaymentDetailsHelper::getLastTransactionId($payment)) {
            $cancelApiResponse = $this->monextHelper->cancelTransaction($transactionId, $payment);
            PaymentDetailsHelper::addPaymentDetails(
                $payment,
                $cancelApiResponse
            );

            if (!isset($cancelApiResponse['result']['title'])) {
                throw new \Exception(sprintf('ERROR: %s', $cancelApiResponse['detail']));
            } elseif ('ACCEPTED' !== $cancelApiResponse['result']['title']) {
                throw new \Exception(sprintf('ERROR: %s', $cancelApiResponse['result']['detail']));
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
