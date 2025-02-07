<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Controller\Shop;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[AsController]
class MonextController
{
    public function __construct(
        private Environment $twig,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderPaymentMethodAction(Request $request): Response
    {
        $orderId = $request->attributes->getInt('orderId');
        /**
         * @var OrderInterface $order
         */
        $order = $this->orderRepository->find($orderId);
        /**
         * @var PaymentInterface $payment
         */
        $payment = $order->getLastPayment();

        try {
            return new Response(
                $this->twig->render(
                    '@MonextSyliusPlugin/Block/_displayLogos.html.twig', [
                        // This is a fake parameter, just to get rid - temp - of QA errors
                        'logos' => [$payment],
                    ]
                )
            );
        } catch (\InvalidArgumentException $exception) {
            return new Response('');
        }
    }
}
