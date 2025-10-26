<?php
/**
 * Plugin Name: NOWPayments for FluentCart
 * Description: Accept 300+ cryptocurrencies in FluentCart via NOWPayments.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required WordPress functions if not already loaded
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Check if FluentCart is active before loading anything
if (!is_plugin_active('fluent-cart/fluent-cart.php') && !class_exists('FluentCart\App\App')) {
    // Add admin notice if we're in admin area
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>NOWPayments for FluentCart:</strong> This plugin requires FluentCart to be installed and activated.';
        echo '</p></div>';
    });
    return;
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
