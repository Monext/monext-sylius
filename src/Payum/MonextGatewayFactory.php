<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Payum;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class MonextGatewayFactory extends GatewayFactory
{
    public const FACTORY_NAME = 'monext';
    public const FACTORY_TITLE = 'Monext';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => self::FACTORY_NAME,
                'payum.factory_title' => self::FACTORY_TITLE,
            ]
        );
    }
}
