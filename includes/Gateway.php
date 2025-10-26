<?php
namespace NowPayments\ForFluentCart;

use FluentCart\App\Modules\PaymentMethods\Core\AbstractPaymentGateway;
use FluentCart\App\Modules\PaymentMethods\Core\BaseGatewaySettings;
use FluentCart\App\Services\Payments\PaymentInstance;

if (!defined('ABSPATH')) {
    exit;
}

class Gateway extends AbstractPaymentGateway
{
    // Must match parent property type (array)
    public array $supportedFeatures = ['payment', 'webhook'];
    public BaseGatewaySettings $settings;

    public function __construct()
    {
        parent::__construct(
            new SettingsBase()
        );
    }

    public function meta(): array
    {
        return [
            'title' => 'NOWPayments',
            'route' => 'nowpayments',
            'slug'  => 'nowpayments',
            'label' => 'NOWPayments',
            'admin_title' => 'NOWPayments',
            'description' => __('NOWPayments integration', 'nowpayments-for-fluent-cart'),
            'logo' => '',
            'icon' => '',
            'status' => $this->settings->get('is_active') === 'yes',
            'brand_color' => '#ff6b00',
            'upcoming' => false,
            'supported_features' => $this->supportedFeatures
        ];
    }

    public function makePaymentFromPaymentInstance(PaymentInstance $paymentInstance)
    {
        return (new Processor())->createPayment($paymentInstance);
    }

    public function webHookPaymentMethodName()
    {
        return $this->getMeta('route');
    }

    public function handleIPN(): void
    {
        (new Webhook())->handle();
    }

    /**
     * Return order related info required by checkout.
     * Minimal implementation — returns basic payment args and intent-like data.
     */
    public function getOrderInfo($data)
    {
        // This is a minimal stub similar to other gateways. It should validate keys and return
        // necessary payment args for frontend integration.
        $publicKey = (new SettingsBase())->getPublicKey();

        if (empty($publicKey)) {
            wp_send_json([
                'status' => 'failed',
                'message' => __('No public key configured for NOWPayments', 'nowpayments-for-fluent-cart')
            ], 423);
        }

        $paymentArgs = [
            'public_key' => $publicKey,
        ];

        wp_send_json([
            'status' => 'success',
            'message' => __('Order info retrieved!', 'nowpayments-for-fluent-cart'),
            'payment_args' => $paymentArgs,
            'intent' => [
                'amount' => 0
            ]
        ], 200);
    }

    /**
     * Settings fields for the gateway. Minimal fields provided; extend as needed.
     */
    public function fields(): array
    {
        $webhook_url = add_query_arg('nowpayments_ipn', '1', home_url('/'));

        return [
            'notice' => [
                'value' => '<p>' . __('Enter your NOWPayments API keys and enable the gateway. Configure IPN secret keys for signature verification.', 'nowpayments-for-fluent-cart') . '</p>',
                'label' => __('Instructions', 'nowpayments-for-fluent-cart'),
                'type'  => 'html_attr'
            ],
            'is_active' => [
                'value' => $this->settings->get('is_active', 'no'),
                'label' => __('Enable NOWPayments', 'nowpayments-for-fluent-cart'),
                'type'  => 'checkbox'
            ],
            'sandbox' => [
                'value' => $this->settings->get('sandbox', 'no'),
                'label' => __('Use Sandbox', 'nowpayments-for-fluent-cart'),
                'type'  => 'checkbox'
            ],
            'live_api_key' => [
                'value' => $this->settings->get('live_api_key'),
                'label' => __('Live API Key', 'nowpayments-for-fluent-cart'),
                'type'  => 'password'
            ],
            'live_ipn_key' => [
                'value' => $this->settings->get('live_ipn_key'),
                'label' => __('Live IPN Secret Key', 'nowpayments-for-fluent-cart'),
                'type'  => 'text'
            ],
            'sandbox_api_key' => [
                'value' => $this->settings->get('sandbox_api_key'),
                'label' => __('Sandbox API Key', 'nowpayments-for-fluent-cart'),
                'type'  => 'password'
            ],
            'sandbox_ipn_key' => [
                'value' => $this->settings->get('sandbox_ipn_key'),
                'label' => __('Sandbox IPN Secret Key', 'nowpayments-for-fluent-cart'),
                'type'  => 'text'
            ],
            'webhook_url' => [
                'value' => $webhook_url,
                'label' => __('Webhook URL', 'nowpayments-for-fluent-cart'),
                'type'  => 'text',
                'visible' => 'no'
            ]
        ];
    }
}
