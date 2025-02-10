<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Helpers;

class ConfigHelper
{
    public const FIELD_API_KEY = 'api_key';
    public const FIELD_ENVIRONMENT = 'environment';
    public const FIELD_CAPTURE_TYPE = 'capture_type';
    public const FIELD_POINT_OF_SALE = 'point_of_sale';
    public const FIELD_INTEGRATION_TYPE = 'behavior';
    public const FIELD_CONTRACTS_NUMBERS = 'contracts_numbers';
    public const FIELD_MANUAL_CAPTURE_TRANSITION = 'manual_capture_transition';
    public const FIELD_USER_AUTHORIZE = 'use_authorize';

    public const FIELD_VALUE_ENVIRONMENT_SANDBOX = 'https://api-sandbox.retail.monext.com/v1/';
    public const FIELD_VALUE_ENVIRONMENT_PRODUCTION = 'https://api.retail.monext.com/v1/';

    public const FIELD_VALUE_CAPTURE_TYPE_AUTO = 'AUTOMATIC';
    public const FIELD_VALUE_CAPTURE_TYPE_MANUAL = 'MANUAL';

    public const FIELD_VALUE_INTEGRATION_TYPE_REDIRECT = 'REDIRECT';
    public const FIELD_VALUE_INTEGRATION_TYPE_INSHOP = 'INSHOP';
}
