<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum\Action;

use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Bundle\PayumBundle\Reply\HttpResponse;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param mixed|Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        // This action will update the payment object (if needed)
        $this->gateway->execute(new GetStatus($payment));

        // No need to perform other action, Payum will return an empty 204 response
        // In fact, it's better for Monext to return an empty 200 response, otherwise it won't stop notifying
        throw new HttpResponse(new Response());
    }

    public function supports($request): bool
    {
        return $request instanceof Notify
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
