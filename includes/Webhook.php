<?php
namespace NowPayments\ForFluentCart;

use FluentCart\Api\Orders;

if (!defined('ABSPATH')) {
    exit;
}

class Webhook
{
    public function handle()
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (empty($data)) {
            status_header(400);
            echo 'invalid';
            exit;
        }
        // Verify signature header x-nowpayments-sig
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $received_sig = $headers['x-nowpayments-sig'] ?? $headers['X-NOWPayments-Sig'] ?? '';

        // Determine secret from settings (assume single global setting for now)
    $settings = new SettingsBase();
    $is_live = $settings->get('sandbox') !== 'yes';
    $secret = $is_live ? trim((string)$settings->get('live_ipn_key')) : trim((string)$settings->get('sandbox_ipn_key'));

        if ($secret && $received_sig) {
            $expected_sig = hash_hmac('sha512', $body, $secret);
            if (!hash_equals($expected_sig, $received_sig)) {
                status_header(403);
                echo 'Invalid IPN signature';
                exit;
            }
        }

        // Basic expected shape: {"payment_status":"finished","order_id":"..."}
        $status = $data['payment_status'] ?? $data['status'] ?? null;
        $orderId = $data['order_id'] ?? $data['order'] ?? null;

        if (!$orderId) {
            status_header(400);
            echo 'no order id';
            exit;
        }

        // Try to load order by id first, then by uuid
        $order = Orders::getById($orderId);
        if (!$order) {
            $order = Orders::getByHash($orderId);
        }

        if (!$order) {
            status_header(404);
            echo 'order not found';
            exit;
        }

        // Map NOWPayments payment_status to FluentCart payment status
        if (in_array($status, ['finished', 'paid', 'confirmed', 'succeeded'])) {
            $order->updatePaymentStatus(\FluentCart\App\Helpers\Status::PAYMENT_PAID);
            $order->updateStatus('status', \FluentCart\App\Helpers\Status::ORDER_PROCESSING);
        } elseif (in_array($status, ['canceled', 'failed'])) {
            $order->updatePaymentStatus(\FluentCart\App\Helpers\Status::PAYMENT_FAILED);
            $order->updateStatus('status', \FluentCart\App\Helpers\Status::ORDER_FAILED);
        } elseif ($status === 'refunded') {
            $order->updatePaymentStatus(\FluentCart\App\Helpers\Status::PAYMENT_REFUNDED);
            $order->updateStatus('status', \FluentCart\App\Helpers\Status::ORDER_CANCELED);
        }

        status_header(200);
        echo 'ok';
        exit;
    }
}
