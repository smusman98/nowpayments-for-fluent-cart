<?php
namespace NowPayments\ForFluentCart;

use FluentCart\App\Modules\PaymentMethods\Core\BaseGatewaySettings;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsBase extends BaseGatewaySettings
{
    /**
     * The meta key handler used by FluentCart to store gateway settings.
     * Must match FluentCart's expected pattern: fluent_cart_payment_settings_{slug}
     */
    public $methodHandler = 'fluent_cart_payment_settings_nowpayments';
    public function __construct()
    {
        parent::__construct();
    }

    public function getPublicKey()
    {
        return $this->get('public_key');
    }

    public function getSecretKey()
    {
        return $this->get('secret_key');
    }

    /**
     * Minimal implementation to satisfy BaseGatewaySettings abstract methods.
     * Real implementation should use FluentCart store settings storage.
     */
    /**
     * Return all settings or a specific key. Signature matches BaseGatewaySettings.
     * @param string $key
     * @return mixed
     */
    public function get($key = '')
    {
        // If parent provided a concrete implementation, prefer it (but avoid calling abstract methods)
        $parent = get_parent_class($this);
        if ($parent && method_exists($parent, 'get')) {
            try {
                $rm = new \ReflectionMethod($parent, 'get');
                if (!$rm->isAbstract()) {
                    return parent::get($key);
                }
            } catch (\ReflectionException $e) {
                // ignore and fallback to local implementation
            }
        }

        if ($key === '') {
            return $this->settings;
        }

        return $this->settings[$key] ?? null;
    }

    public function getMode()
    {
        $val = $this->get('payment_mode');
        return $val ?? 'test';
    }

    public function isActive()
    {
        $val = $this->get('is_active');
        return ($val ?? 'no') === 'yes';
    }

    /**
     * Provide default settings required by BaseGatewaySettings constructor.
     * FluentCart expects static::getDefaults() to exist.
     */
    public static function getDefaults(): array
    {
        return [
            'is_active' => 'no',
            'payment_mode' => 'test',
            'public_key' => '',
            'secret_key' => ''
        ];
    }
}
