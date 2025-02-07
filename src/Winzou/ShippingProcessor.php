<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Winzou;

use MonextSyliusPlugin\Helpers\PaymentMethodHelper;
use SM\Event\TransitionEvent;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;
use SM\SMException;
use Sylius\Component\Core\Model\OrderInterface;

class ShippingProcessor
{
    public function __construct(
        private StateMachineFactoryInterface $stateMachineFactory
    ) {
    }

    /**
     * @throws SMException
     */
    public function process(OrderInterface $order, TransitionEvent $event): void
    {
        if (PaymentMethodHelper::isMonextPayment($payment = $order->getLastPayment())) {
            // Registered event ?
            if (!in_array(
                $event->getTransition(),
                PaymentMethodHelper::getMonextManualCaptureTransitions($payment),
                true
            )
            ) {
                return;
            }

            $paymentStateMachine = $this->stateMachineFactory->get($payment, 'sylius_payment');

            if ($paymentStateMachine->can('complete')) {
                $paymentStateMachine->apply('complete');
            }
        }
    }
}
