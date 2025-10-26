# NOWPayments for FluentCart

This is a minimal scaffold plugin to integrate NOWPayments with FluentCart.

What this contains:
- Main plugin file which registers a gateway with FluentCart's GatewayManager
- `includes/Gateway.php` - gateway class extending FluentCart's AbstractPaymentGateway
- `includes/SettingsBase.php` - simple settings wrapper
- `includes/API.php` - minimal NOWPayments API helper (create invoice)
- `includes/Processor.php` - creates invoice and redirects to payment URL
- `includes/Webhook.php` - basic webhook handler mapping status to FluentCart Orders

Notes & next steps:
- Add proper settings UI and secure storage for API keys.
- Improve IPN verification using NOWPayments signature header.
- Add support for refunds, subscriptions, and more robust error handling.
- Test in a local WP + FluentCart environment.
