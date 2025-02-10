<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

use Doctrine\Common\Collections\Collection;
use Payum\Core\Payum;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;
use Symfony\Component\Routing\RequestContext;

class MonextPayloadHelper
{
    public function __construct(
        private Payum $payum,
        private RequestContext $requestContext,
        private ?string $localNotifyHost,
        protected TaxRateResolverInterface $taxRateResolver
    ) {
        // Just in case, remove the scheme from the development notify host
        if (null !== $localNotifyHost && '' !== $localNotifyHost) {
            if (false !== filter_var($localNotifyHost, FILTER_VALIDATE_URL)) {
                $this->localNotifyHost = parse_url($localNotifyHost, PHP_URL_HOST);
            }
        } else {
            $this->localNotifyHost = '';
        }
    }

    /**
     * @return array<mixed>
     */
    public function getCaptureTransactionPayload(PaymentInterface $payment): array
    {
        $order = $payment->getOrder();

        return [
            'amount' => $order->getTotal(),
        ];
    }

    /**
     * For now, the refund amount is not adjustable, same as captured amount => total
     * One can change this behaviour here if needed.
     *
     * @return array<mixed>
     */
    public function getRefundTransactionPayload(PaymentInterface $payment): array
    {
        return $this->getCaptureTransactionPayload($payment);
    }

    /**
     * Cancel amount must be the same as captured amount => total ?
     * One can change this behaviour here if needed.
     *
     * @return array<mixed>
     */
    public function getCancelTransactionPayload(PaymentInterface $payment): array
    {
        return $this->getCaptureTransactionPayload($payment);
    }

    /**
     * @return array<mixed>
     */
    public function getCreateSessionPayload(PaymentInterface $payment, TokenInterface $securityToken): array
    {
        $order = $payment->getOrder();

        return [
            'pointOfSaleReference' => PaymentMethodHelper::getMonextPointOfSale($payment),
            'paymentMethod' => [
                'paymentMethodIDs' => $this->getPaymentMethodIds($payment),
            ],
            'payment' => [
                'paymentType' => 'ONE_OFF',
                'capture' => PaymentMethodHelper::getMonextCaptureType($payment),
            ],
            'order' => [
                'currency' => $order->getCurrencyCode(),
                'origin' => 'E_COM',
                'country' => $order->getShippingAddress()->getCountryCode(),
                'reference' => $order->getNumber(),
                'amount' => $order->getTotal(),
                'taxes' => $order->getTaxTotal(),
                'discount' => abs($order->getOrderPromotionTotal()),
                'items' => $this->prepareOrderItemsDetails($order->getItems()),
            ],
            'buyer' => [
                'legalStatus' => 'PRIVATE',
                'id' => $order->getCustomer()->getId(),
                'firstName' => $order->getCustomer()->getFirstName(),
                'lastName' => $order->getCustomer()->getLastName(),
                'email' => $order->getCustomer()->getEmail(),
                'mobile' => $order->getCustomer()->getPhoneNumber(),
                'birthDate' => $order->getCustomer()->getBirthday()?->format('Y-m-d'),
                'billingAddress' => [
                    'country' => $order->getBillingAddress()->getCountryCode(),
                    'firstName' => $order->getBillingAddress()->getFirstName(),
                    'lastName' => $order->getBillingAddress()->getLastName(),
                    'email' => $order->getCustomer()->getEmail(),
                    'mobile' => $order->getBillingAddress()->getPhoneNumber(),
                    'street' => $order->getBillingAddress()->getStreet(),
                    'city' => $order->getBillingAddress()->getCity(),
                    'zip' => $order->getBillingAddress()->getPostcode(),
                    'addressCreateDate' => $order
                    ->getBillingAddress()
                    ->getCreatedAt()
                    ->format(\DateTimeInterface::ATOM),
                ],
            ],
            'delivery' => [
                'charge' => $order->getShippingTotal(),
                'provider' => $order->getShipments()->last()->getMethod()->getName(),
                'address' => [
                    'country' => $order->getShippingAddress()->getCountryCode(),
                    'firstName' => $order->getShippingAddress()->getFirstName(),
                    'lastName' => $order->getShippingAddress()->getLastName(),
                    'email' => $order->getCustomer()->getEmail(),
                    'mobile' => $order->getShippingAddress()->getPhoneNumber(),
                    'street' => $order->getShippingAddress()->getStreet(),
                    'city' => $order->getShippingAddress()->getCity(),
                    'zip' => $order->getShippingAddress()->getPostcode(),
                    'addressCreateDate' => $order
                    ->getShippingAddress()
                    ->getCreatedAt()
                    ->format(\DateTimeInterface::ATOM),
                ],
            ],
            'threeDS' => ['challengeInd' => 'NO_PREFERENCE'],
            'returnURL' => $securityToken->getAfterUrl(),
            'notificationURL' => $this->getNotifyUrl($payment),
            'languageCode' => strtoupper(explode('_', $order->getLocaleCode())[0]),
        ];
    }

    /**
     * @param Collection<int, OrderItemInterface> $orderItems
     *
     * @return array<int, array<string, int|float|string>>
     */
    protected function prepareOrderItemsDetails(Collection $orderItems): array
    {
        $orderItemsFormatted = [];

        foreach ($orderItems as $item) {
            $rate = $this->taxRateResolver->resolve($item->getVariant());

            $taxons = $item->getProduct()->getTaxons()->toArray();
            $subCategoryOne = array_pop($taxons);
            $subCategoryTwo = array_pop($taxons);

            $orderItemsFormatted[] = [
                'reference' => substr($item->getProduct()->getCode(), 0, 30), // max code length 30 chars for Cofidis 😈
                'taxRate' => null !== $rate ? floor($rate->getAmountAsPercentage() * 100) : 0,
                'subCategory1' => (string) $subCategoryOne?->getName(),
                'subCategory2' => (string) $subCategoryTwo?->getName(),
                'price' => $item->getTotal(),
                'quantity' => $item->getQuantity(),
                'comment' => $item->getProduct()->getShortDescription(),
            ];
        }

        return $orderItemsFormatted;
    }

    protected function getNotifyUrl(PaymentInterface $payment): string
    {
        $backupHost = $this->requestContext->getHost();

        if ('' !== $this->localNotifyHost) {
            $this->requestContext->setHost($this->localNotifyHost);
        }

        $notifyUrl = $this->payum->getTokenFactory()->createNotifyToken(
            PaymentMethodHelper::getGatewayName($payment),
            $payment
        )->getTargetUrl();

        if ('' !== $this->localNotifyHost) {
            $this->requestContext->setHost($backupHost);
        }

        return $notifyUrl;
    }

    /**
     * @return array<string>
     */
    protected function getPaymentMethodIds(PaymentInterface $payment): array
    {
        $paymentMethodIds = PaymentMethodHelper::getMonextContractNumbers($payment);

        return null !== $paymentMethodIds ? explode(',', $paymentMethodIds) : [];
    }
}
