<?php
namespace NowPayments\ForFluentCart;

use FluentCart\App\Services\Payments\PaymentInstance;
use FluentCart\Api\Orders;

if (!defined('ABSPATH')) {
    exit;
}

class Processor
{
    public function createPayment(PaymentInstance $paymentInstance)
    {
        $order = $paymentInstance->order;
        $settings = new SettingsBase();

    $is_live = $settings->get('sandbox') !== 'yes';
    $api_key = $is_live ? $settings->get('live_api_key') : $settings->get('sandbox_api_key');

        // Build parameters similar to Woo version
        // Convert transaction total from stored cents to decimal amount (e.g., 1290 -> 12.90)
        $amount_decimal = \FluentCart\App\Helpers\Helper::toDecimalWithoutComma($paymentInstance->transaction->total);

        $parameters = [
            'dataSource' => 'fluent_cart',
            'ipnURL' => add_query_arg('nowpayments_ipn', '1', home_url('/')),
            'paymentCurrency' => strtoupper($paymentInstance->transaction->currency),
            'successURL' => $this->getSuccessUrl($paymentInstance->transaction),
            'cancelURL' => $this->getCancelUrl($paymentInstance->transaction),
            'orderID' => $order->id ?? $order->uuid,
            'customerName' => $order->first_name ?? '',
            'customerEmail' => $order->email ?? '',
            // Send amount in decimal currency units (not cents). Format with 8 decimals like the Woo plugin.
            'paymentAmount' => number_format($amount_decimal, 8, '.', ''),
        ];

        // Add items if available
        try {
            $items = $order->order_items ?? [];
            if (!empty($items)) {
                $products = [];
                foreach ($items as $item) {
                    $p = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array)$item;

                    // Convert common price fields stored in cents to decimal currency units.
                    $priceFields = ['unit_price', 'subtotal', 'line_total', 'cost', 'tax_amount', 'shipping_charge', 'discount_total'];
                    foreach ($priceFields as $f) {
                        if (isset($p[$f]) && is_numeric($p[$f])) {
                            // use Helper to convert from cents to decimal with two decimals, then format to 8 decimals
                            $val = \FluentCart\App\Helpers\Helper::toDecimalWithoutComma($p[$f]);
                            $p[$f] = number_format($val, 8, '.', '');
                        }
                    }

                    // Also ensure quantity is cast to int
                    if (isset($p['quantity'])) {
                        $p['quantity'] = (int)$p['quantity'];
                    }

                    $products[] = $p;
                }
                $parameters['products'] = $products;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $api = new API($api_key, $is_live);
        $redirect_url = $api->off_page_checkout($parameters);

        // When checkout is initiated via AJAX (FluentCart place order), do not perform
        // a server-side redirect (which makes the admin-ajax request follow a cross-origin
        // redirect and triggers CORS problems). Instead return an array that will be
        // JSON-encoded by FluentCart and handled on the frontend (the frontend will
        // perform a normal browser navigation to the returned URL).
        if ($redirect_url) {
            // Return multiple keys for compatibility with different frontends:
            // - 'status' + 'redirect_to' is what FluentCart's frontend checks for
            // - 'result' + 'redirect' mirrors WooCommerce/legacy shape
            return [
                'status'     => 'success',
                'redirect_to'=> $redirect_url,
                'result'     => 'success',
                'redirect'   => $redirect_url,
            ];
        }

        return new \WP_Error('nowpayments_no_redirect', 'No redirect URL returned');
    }

    protected function getSuccessUrl($transaction)
    {
        return home_url('/');
    }

    protected function getCancelUrl($transaction)
    {
        return home_url('/');
    }
}
