<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum\Action;

use MonextSyliusPlugin\Helpers\MonextClientHelper;
use MonextSyliusPlugin\Helpers\MonextResponseHelper;
use MonextSyliusPlugin\Helpers\PaymentDetailsHelper;
use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GetStatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private MonextClientHelper $monextHelper,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param mixed|GetStatus $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getModel();

        /**
         * @var Request $httpRequest
         */
        $httpRequest = $this->requestStack->getCurrentRequest();

        // Handle user cancel request from inshop payment page here
        $cancelPayment = (bool) $httpRequest->query->get('cancel_payment_request');
        if ($cancelPayment) {
            $this->gateway->execute(new Cancel($payment));
            // Whatever is the result of the previous call, mark the request as cancelled here
            $request->markCanceled();

            return;
        }

        // Dans l'url "authorize", le token est dans 'paylinetoken'
        // Dans l'url "notify", le token est dans 'token'
        $sessionToken = $httpRequest->query->get('paylinetoken', $httpRequest->query->get('token'));

        if (null === $sessionToken) {
            $sessionToken = PaymentDetailsHelper::getSessionToken($payment);
        }
        // Always call the "getSession" to retrieve session OR transaction details
        if (null !== $sessionToken) {
            PaymentDetailsHelper::addPaymentDetails(
                $payment,
                $this->monextHelper->getSession(
                    $sessionToken,
                    $payment
                )
            );
        } else {
            // Handle this
            throw new LogicException('Unable to retrieve session (token) details.');
        }

        // Extract the freshly added details/Status/information
        $lastResponseDetails = PaymentDetailsHelper::getLastResponseDetails($payment);
        $transaction = $lastResponseDetails[MonextResponseHelper::TRANSACTION_KEY] ?? null;
        $session = $lastResponseDetails[MonextResponseHelper::SESSION_KEY] ?? null;
        $title = $lastResponseDetails[MonextResponseHelper::RESULT_KEY][MonextResponseHelper::TITLE_KEY] ?? null;
        $type = $transaction[MonextResponseHelper::TYPE_KEY] ?? null;

        if (null !== $transaction) {
            if ('AUTHORIZATION' === $type) {
                if ('ACCEPTED' === $title) {
                    $request->markAuthorized();

                    return;
                }
                if ('CANCELLED' === $title || 'REFUSED' === $title) {
                    $request->markCanceled();
                    // Adding error in details so the payum controller override will set the right message.
                    PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.refused']]]);

                    return;
                }

                if ('ERROR' === $title) {
                    $request->markCanceled();
                    // Adding error in details so the payum controller override will set the right message.
                    PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);

                    return;
                }
            }
            // For reference, used with redirection behaviour but maybe usefull later
            // In fact, it is usefull with Klarna method
            if ('AUTHORIZATION_AND_CAPTURE' === $type) {
                if ('CANCELLED' === $title || 'REFUSED' === $title) {
                    PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.refused']]]);
                    $request->markCanceled();

                    return;
                }
                if ('ACCEPTED' === $title) {
                    $request->markCaptured();

                    return;
                }

                if ('ERROR' === $title) {
                    $request->markCanceled();
                    // Adding error in details so the payum controller override will set the right message.
                    PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);

                    return;
                }
            }
            if ('CAPTURE' === $type) {
                if ('ACCEPTED' === $title) {
                    $request->markCaptured();

                    return;
                }
            }
            // Paypal send this ?
            if ('ORDER' === $type) {
                if ('PENDING_RISK' === $title) {
                    PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.refused']]]);
                    $request->markCanceled();

                    return;
                }
            }
            // Paypal send this ?
            if (null === $type) {
                if ('INPROGRESS' === $title) {
                    $request->markNew();

                    return;
                }
            }
        }

        if (null !== $session) {
            if ('INPROGRESS' === $title || 'ACCEPTED' === $title) {
                $request->markNew();

                return;
            }
            if ('CANCELLED' === $title || 'REFUSED' === $title) {
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);
                $request->markExpired();

                return;
            }

            if ('ERROR' === $title) {
                $request->markCanceled();
                // Adding error in details so the payum controller override will set the right message.
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);

                return;
            }
        }

        // Last chance, error case ?
        if (null === $transaction && null === $session) {
            if ('CANCELLED' === $title) {
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);
                $request->markCanceled();

                return;
            }
            if ('REFUSED' === $title) {
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);
                $request->markExpired();

                return;
            }

            if ('ERROR' === $title) {
                $request->markCanceled();
                // Adding error in details so the payum controller override will set the right message.
                PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);

                return;
            }
        }

        PaymentDetailsHelper::addPaymentDetails($payment, ['result' => ['title' => 'ERROR', 'detail' => ['msg' => 'monext.return.error']]]);
        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatus
            && $request->getModel() instanceof PaymentInterface
            && PaymentMethodHelper::isMonextPayment($request->getFirstModel());
    }
}
