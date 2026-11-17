# Forfait Pulse

## Run locally

- Requires PHP 8+ with PDO, PDO_SQLite and cURL enabled.
- No Composer, npm, or other application dependencies are required.
- Start the preview with `php -S 0.0.0.0:5000 router.php`.
- Local development uses SQLite in `storage/forfait-pulse.sqlite` (or `DB_SQLITE_PATH` for isolated tests).

## Production on Hostinger

Upload the project files directly into `public_html`. The root `index.php` is the customer home page and `webhook.php` remains at the site root.

Configure PHP 8+ with PDO MySQL and cURL. Set `DB_DRIVER=mysql`, `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` for the Hostinger database. Keep database credentials and the admin password hash outside version control. Configure the Bumxpress API key, API secret, and webhook signing secret in the hosting environment; use the production environment only after validating the merchant account and a real test transaction.

Set the Bumxpress webhook URL to `https://forfait-pulse-cm.top/webhook.php`. The endpoint verifies the signed raw request body and records event IDs to prevent duplicate processing.

The admin can be reached by rapidly clicking five times on step 3 in the customer “Comment ça marche” section, or by opening `/admin/login.php` directly. The shortcut is not a security control; admin access is protected by password verification, rate limiting, CSRF checks, and secure sessions.

Bundle delivery is manual: after the signed Bumxpress payin event confirms the exact amount, the order appears in the paid-order list. The admin fulfills the telecom bundle outside this site and then marks it delivered; the customer sees the delivered status in “Mes commandes”.

## Important operational notes

- Bumxpress processes mobile-money collections and merchant payouts; it does not provision Orange, MTN, or Camtel data bundles.
- Bumxpress documents quote-backed payins and payouts as asynchronous. Final payment state comes from signed webhooks, not the browser.
- The published API documentation does not expose a wallet-balance read endpoint. The admin dashboard links to the Bumxpress merchant portal rather than inventing a balance.
- Select `BUMXPRESS_ENVIRONMENT=sandbox` while using sandbox credentials. Production credentials and production mode can move real money.
- The admin payout form sends a real payout when production mode is configured and submitted. It requires a recipient first name, last name, email, phone, operator, and amount.
- In a Hostinger deployment, configure the secure values and database settings in hosting configuration, then register the webhook URL and signing secret in Bumxpress.
