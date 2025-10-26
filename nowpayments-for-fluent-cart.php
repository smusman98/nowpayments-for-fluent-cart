<?php
/**
 * Plugin Name: NOWPayments for FluentCart
 * Description: Accept 300+ cryptocurrencies in FluentCart via NOWPayments.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoload classes if available (PSR-4 not assumed here). We'll include files directly.
require_once __DIR__ . '/includes/Gateway.php';
require_once __DIR__ . '/includes/SettingsBase.php';
require_once __DIR__ . '/includes/API.php';
require_once __DIR__ . '/includes/Processor.php';
require_once __DIR__ . '/includes/Webhook.php';

add_action('init', function () {
    // Register gateway with FluentCart GatewayManager if available
    if (class_exists('\FluentCart\App\Modules\PaymentMethods\Core\GatewayManager')) {
        $manager = \FluentCart\App\Modules\PaymentMethods\Core\GatewayManager::getInstance();
        // Ensure we don't overwrite if exists
        if (!\FluentCart\App\Modules\PaymentMethods\Core\GatewayManager::has('nowpayments')) {
            $gateway = new \NowPayments\ForFluentCart\Gateway();
            $manager->register('nowpayments', $gateway);
        }
    }
});

// Simple IPN routing when NOWPayments posts with ?nowpayments_ipn=1
add_action('parse_request', function ($wp) {
    if (isset($_GET['nowpayments_ipn']) && $_GET['nowpayments_ipn'] == '1') {
        (new \NowPayments\ForFluentCart\Webhook())->handle();
        exit;
    }
});

// Simple IPN endpoint: handle when ?nowpayments_ipn=1 is present
add_action('parse_request', function ($wp) {
    if (isset($wp->query_vars['nowpayments_ipn']) || isset($_GET['nowpayments_ipn'])) {
        // direct include and handle
        $h = new \NowPayments\ForFluentCart\Webhook();
        $h->handle();
        exit;
    }
});
