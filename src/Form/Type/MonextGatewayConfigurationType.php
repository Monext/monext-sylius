<?php

declare(strict_types=1);

namespace MonextSyliusPlugin\Form\Type;

use MonextSyliusPlugin\Helpers\ConfigHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class MonextGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            ConfigHelper::FIELD_API_KEY, TextType::class, [
                'label' => 'monext.form.api_key',
                'required' => true,
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_ENVIRONMENT, ChoiceType::class, [
                'label' => 'monext.form.environment.label',
                'required' => true,
                'choices' => [
                    'monext.form.environment.values.sandbox' => ConfigHelper::FIELD_VALUE_ENVIRONMENT_SANDBOX,
                    'monext.form.environment.values.production' => ConfigHelper::FIELD_VALUE_ENVIRONMENT_PRODUCTION,
                ],
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_POINT_OF_SALE, TextType::class, [
                'label' => 'monext.form.point_of_sale',
                'required' => true,
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_INTEGRATION_TYPE, ChoiceType::class, [
                'label' => 'monext.form.integration_type.label',
                'required' => true,
                'choices' => [
                    'monext.form.integration_type.values.redirect' => ConfigHelper::FIELD_VALUE_INTEGRATION_TYPE_REDIRECT,
                    'monext.form.integration_type.values.inshop' => ConfigHelper::FIELD_VALUE_INTEGRATION_TYPE_INSHOP,
                ],
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_CONTRACTS_NUMBERS, TextType::class, [
                'label' => 'monext.form.contracts_numbers.label',
                'help' => 'monext.form.contracts_numbers.description',
                'help_html' => true,
                'required' => false,
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_CAPTURE_TYPE, ChoiceType::class, [
                'label' => 'monext.form.capture_type.label',
                'choices' => [
                    'monext.form.capture_type.values.automatic' => ConfigHelper::FIELD_VALUE_CAPTURE_TYPE_AUTO,
                    'monext.form.capture_type.values.manual' => ConfigHelper::FIELD_VALUE_CAPTURE_TYPE_MANUAL,
                ],
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_MANUAL_CAPTURE_TRANSITION, TextType::class, [
                'label' => 'monext.form.manual_capture_transition.label',
                'help' => 'monext.form.manual_capture_transition.description',
                'required' => false,
            ]
        );

        $builder->add(
            ConfigHelper::FIELD_USER_AUTHORIZE, HiddenType::class, [
                'data' => true, 'attr' => ['readonly' => true],
            ]
        );
    }
}
