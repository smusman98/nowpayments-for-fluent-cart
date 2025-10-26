<?php
namespace NowPayments\ForFluentCart;

if (!defined('ABSPATH')) {
    exit;
}

class API
{
    protected $api_base;
    protected $is_live = true;
    protected $api_key = '';

    public function __construct($api_key = '', $is_live = true)
    {
        $this->is_live = $is_live;
        $this->api_key = $api_key;

        if ($is_live) {
            $this->api_base = 'https://nowpayments.io';
        } else {
            $this->api_base = 'https://sandbox.nowpayments.io';
        }
    }

    /**
     * Build the off-site checkout redirect URL (same approach used in the Woo plugin)
     * The NOWPayments off-site expects a JSON payload in the `data` query param and the apiKey in the payload
     */
    public function off_page_checkout(array $parameters = [])
    {
        $parameters['apiKey'] = $this->api_key;
        $parameters = urlencode(json_encode($parameters));
        $redirect_url = "{$this->api_base}/payment?data={$parameters}";

        return $redirect_url;
    }

    /**
     * Optional: create invoice via API v1 invoice endpoint
     */
    public function createInvoice(array $data, $secretKey)
    {
        $url = ($this->is_live ? 'https://api.nowpayments.io/v1' : 'https://api-sandbox.nowpayments.io/v1') . '/invoice';
        $args = [
            'body' => wp_json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $secretKey
            ],
            'timeout' => 45
        ];

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($code >= 400) {
            return new \WP_Error('nowpayments_error', $body, ['code' => $code, 'body' => $data]);
        }

        return $data;
    }
}
