<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum\Action;

use MonextSyliusPlugin\Helpers\MonextClientHelper;
use MonextSyliusPlugin\Helpers\PaymentDetailsHelper;
use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private MonextClientHelper $monextHelper,
    ) {
    }

    /**
     * @param mixed|Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        // Check status before trying to capture
        $this->gateway->execute($statusRequest = new GetStatus($payment));
        if ($statusRequest->isCaptured()) {
            return;
        }

        if (null !== $transactionId = PaymentDetailsHelper::getLastTransactionId($payment)) {
            $captureApiResponse = $this->monextHelper->captureTransaction($transactionId, $payment);
            PaymentDetailsHelper::addPaymentDetails(
                $payment,
                $captureApiResponse
            );

            if (!isset($captureApiResponse['result']['title'])) {
                throw new \Exception(sprintf('ERROR: %s', $captureApiResponse['detail']));
            } elseif ('ACCEPTED' !== $captureApiResponse['result']['title']) {
                throw new \Exception(sprintf('ERROR: %s', $captureApiResponse['result']['detail']));
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Capture
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
