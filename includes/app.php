<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

const PAYIN_FEE_RATE = 0.02;
const PAYOUT_FEE_RATE = 0.015;

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function payout_net_amount(int $budgetAmount): int
{
    return max(0, (int) round($budgetAmount * (1 - PAYOUT_FEE_RATE), 0, PHP_ROUND_HALF_UP));
}

function money(int|float|string $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
}

function operator_name(string $operator): string
{
    return match ($operator) {
        'orange' => 'Orange',
        'mtn' => 'MTN',
        'camtel' => 'Camtel',
        default => 'Forfait',
    };
}

function operator_class(string $operator): string
{
    return match ($operator) {
        'orange' => 'operator-orange',
        'mtn' => 'operator-mtn',
        'camtel' => 'operator-camtel',
        default => '',
    };
}

function operator_logo(string $operator): string
{
    return match ($operator) {
        'orange' => 'logo-orange.png',
        'mtn' => 'logo-mtn.png',
        'camtel' => 'logo-camtel.jpg',
        default => 'logo.png',
    };
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Votre session a expiré. Revenez à la page précédente et réessayez.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function admin_required(): void
{
    if (empty($_SESSION['admin_authenticated']) || time() - (int) ($_SESSION['last_admin_activity'] ?? 0) > 1800) {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_since'], $_SESSION['last_admin_activity']);
        redirect('/admin/login.php');
    }
    $_SESSION['last_admin_activity'] = time();
}

function language(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    return ($_SESSION['lang'] ?? 'fr') === 'en' ? 'en' : 'fr';
}

function lang_url(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    return '?' . http_build_query($params);
}

function whatsapp_url(): string
{
    return 'https://wa.me/237678519844';
}

function normalize_cameroon_phone(string $input): string
{
    $digits = preg_replace('/\D+/', '', trim($input)) ?? '';
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '237')) {
        return '+' . $digits;
    }
    return '+237' . ltrim($digits, '0');
}

function current_order_lookup_phone(): ?string
{
    $phone = $_SESSION['order_lookup_phone'] ?? null;
    $lookedUpAt = (int) ($_SESSION['order_lookup_at'] ?? 0);
    if (!is_string($phone) || $phone === '' || $lookedUpAt === 0 || time() - $lookedUpAt > 1200) {
        unset($_SESSION['order_lookup_phone'], $_SESSION['order_lookup_at']);
        return null;
    }
    return $phone;
}

function order_matches_phone_lookup(array $order): bool
{
    $phone = current_order_lookup_phone();
    if ($phone === null) {
        return false;
    }
    foreach ([$order['customer_phone'] ?? '', $order['payment_phone'] ?? ''] as $orderPhone) {
        if (is_string($orderPhone) && $orderPhone !== '' && hash_equals($orderPhone, $phone)) {
            return true;
        }
    }
    return false;
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'receipt' => '<path d="M4 3h16v18l-4-2-4 2-4-2-4 2z"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'arrow' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/>',
        'truck' => '<path d="M3 6h12v12H3z"/><path d="M15 10h4l3 3v5h-7z"/><circle cx="7.5" cy="19" r="1.5"/><circle cx="18.5" cy="19" r="1.5"/>',
        'headset' => '<path d="M3 13v-1a9 9 0 0 1 18 0v1"/><path d="M5 13h2v7H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2Zm14 0h-2v7h2a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2Z"/><path d="M17 20a5 5 0 0 1-5 2h-1"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>',
        'wallet' => '<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 9h18M16 15h2"/>',
        'chart' => '<path d="M4 19V5m0 14h17"/><path d="m7 15 4-4 3 2 6-7"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/>',
        'logout' => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>',
    ];
    $body = $paths[$name] ?? $paths['grid'];
    return '<svg aria-hidden="true" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function order_status_label(array $order): array
{
    if (($order['payment_status'] ?? '') === 'paid' && ($order['delivery_status'] ?? '') === 'delivered') {
        return ['Livrée', 'status-delivered'];
    }
    if (($order['payment_status'] ?? '') === 'paid') {
        return ['Paiement confirmé · en attente de livraison', 'status-paid'];
    }
    if (($order['payment_status'] ?? '') === 'failed') {
        return ['Paiement échoué', 'status-failed'];
    }
    return ['Paiement en attente', 'status-pending'];
}

function remember_order(string $id, string $token): void
{
    $_SESSION['customer_orders'] ??= [];
    $_SESSION['customer_orders'][$id] = $token;
    if (count($_SESSION['customer_orders']) > 20) {
        $_SESSION['customer_orders'] = array_slice($_SESSION['customer_orders'], -20, null, true);
    }
}
