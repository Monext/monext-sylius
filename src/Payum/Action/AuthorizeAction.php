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
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\RenderTemplate;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * We use the authorize Action below to display the Monext  payment widget.
 */
class AuthorizeAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private MonextClientHelper $monextHelper,
    ) {
    }

    /**
     * @param mixed|Authorize $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        // Do we have already a session token ?
        $createNewToken = true;
        if (PaymentDetailsHelper::hasSessionToken($payment)) {
            $createNewToken = false;
            // Is it expired ?
            $this->gateway->execute($statusRequest = new GetStatus($payment));

            if ($statusRequest->isExpired()
                || $statusRequest->isCanceled()
                || $statusRequest->isFailed()
                || $statusRequest->isUnknown()
            ) {
                $createNewToken = true;
            }
        }

        // Get a new session token
        if ($createNewToken) {
            $createSessionResult = $this->monextHelper->createSession($payment, $request->getToken());
            PaymentDetailsHelper::addPaymentDetails(
                $payment,
                $createSessionResult
            );

            if (isset($createSessionResult['error']) && true === $createSessionResult['error']) {
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);
            }
        }

        /*
         * Generate a sessionToken and
         * mode REDIRECT : redirect to the Monext pages
         * mode INSHOP : display the payment widget
         */
        if (PaymentMethodHelper::isMonextRedirectPayment($payment) && PaymentDetailsHelper::hasRedirectUrl($payment)) {
            throw new HttpRedirect(PaymentDetailsHelper::getRedirectUrl($payment));
        }

        // Render the payment widget
        $renderTemplate = new RenderTemplate(
            '@MonextSyliusPlugin/Action/_displayWidget.html.twig', [
                'monext_token' => PaymentDetailsHelper::getSessionToken($payment),
                'is_prod' => PaymentMethodHelper::isMonextProd($payment),
                'order' => $payment->getOrder(),
                'cancel_url' => sprintf('%s&cancel_payment_request=true', $request->getToken()->getAfterUrl()),
            ]
        );

        $this->gateway->execute($renderTemplate);

        $headers = [
            'Cache-Control' => 'no-store, no-cache, max-age=0, post-check=0, pre-check=0',
            'X-Status-Code' => 200,
            'Pragma' => 'no-cache',
        ];

        $symfonyResponse = new Response($renderTemplate->getResult());

        throw new HttpResponse($symfonyResponse->getContent(), 200, $headers);
    }

    public function supports($request): bool
    {
        return $request instanceof Authorize
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
