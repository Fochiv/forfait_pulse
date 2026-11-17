<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = env_value('DB_DRIVER', 'sqlite');
    if ($driver === 'mysql') {
        $host = env_value('DB_HOST');
        $name = env_value('DB_NAME');
        $user = env_value('DB_USER');
        $password = env_value('DB_PASSWORD');
        if ($host === '' || $name === '' || $user === '') {
            throw new RuntimeException('La base MySQL est sélectionnée, mais DB_HOST, DB_NAME ou DB_USER manque.');
        }
        $charset = env_value('DB_CHARSET', 'utf8mb4');
        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset={$charset}",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } else {
        $storage = __DIR__ . '/../storage';
        if (!is_dir($storage) && !mkdir($storage, 0750, true) && !is_dir($storage)) {
            throw new RuntimeException('Impossible de créer le dossier de stockage SQLite.');
        }
        $sqlitePath = env_value('DB_SQLITE_PATH', $storage . '/forfait-pulse.sqlite');
        $sqliteDir = dirname($sqlitePath);
        if (!is_dir($sqliteDir) && !mkdir($sqliteDir, 0750, true) && !is_dir($sqliteDir)) {
            throw new RuntimeException('Impossible de créer le dossier de la base SQLite.');
        }
        $pdo = new PDO(
            'sqlite:' . $sqlitePath,
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    initialize_database($pdo, $driver);
    return $pdo;
}

function initialize_database(PDO $pdo, string $driver): void
{
    $id = $driver === 'mysql' ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $text = $driver === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
    $longText = $driver === 'mysql' ? 'LONGTEXT' : 'TEXT';
    $integer = $driver === 'mysql' ? 'BIGINT' : 'INTEGER';
    $date = $driver === 'mysql' ? 'DATETIME' : 'TEXT';

    $pdo->exec("CREATE TABLE IF NOT EXISTS bundles (
        id {$id}, operator_code VARCHAR(20) NOT NULL, name VARCHAR(120) NOT NULL,
        volume_label VARCHAR(80) NOT NULL, validity_days INTEGER NOT NULL, price INTEGER NOT NULL,
        description VARCHAR(255) NOT NULL DEFAULT '', is_active INTEGER NOT NULL DEFAULT 1,
        sort_order INTEGER NOT NULL DEFAULT 0, created_at {$date} NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id {$id}, public_ref VARCHAR(40) NOT NULL UNIQUE, tracking_token_hash VARCHAR(64) NOT NULL,
        customer_name VARCHAR(160) NOT NULL, customer_phone VARCHAR(32) NOT NULL,
        payment_phone VARCHAR(32) NOT NULL, customer_email VARCHAR(190) NOT NULL,
        operator_code VARCHAR(20) NOT NULL, bundle_id {$integer} NULL, bundle_name VARCHAR(120) NOT NULL,
        volume_label VARCHAR(80) NOT NULL, validity_days INTEGER NOT NULL, amount INTEGER NOT NULL,
        payment_operator VARCHAR(24) NOT NULL, payment_status VARCHAR(24) NOT NULL DEFAULT 'initiating',
        delivery_status VARCHAR(24) NOT NULL DEFAULT 'waiting', provider_customer_id VARCHAR(80) NULL,
        provider_quote_id VARCHAR(80) NULL, provider_payin_id VARCHAR(80) NULL UNIQUE,
        provider_action_json {$longText} NULL, customer_action_json {$longText} NULL,
        failure_reason VARCHAR(255) NULL, created_at {$date} NOT NULL, paid_at {$date} NULL, delivered_at {$date} NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
        id {$id}, public_ref VARCHAR(40) NOT NULL UNIQUE, customer_id VARCHAR(80) NULL,
        quote_id VARCHAR(80) NULL, provider_payout_id VARCHAR(80) NULL UNIQUE,
        recipient_name VARCHAR(160) NOT NULL, recipient_phone VARCHAR(32) NOT NULL,
        recipient_email VARCHAR(190) NOT NULL, operator_code VARCHAR(24) NOT NULL,
        amount INTEGER NOT NULL, status VARCHAR(24) NOT NULL DEFAULT 'initiating',
        failure_reason VARCHAR(255) NULL, created_at {$date} NOT NULL, completed_at {$date} NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id {$id}, customer_name VARCHAR(100) NOT NULL, message VARCHAR(600) NOT NULL,
        rating INTEGER NOT NULL DEFAULT 5, is_visible INTEGER NOT NULL DEFAULT 0, created_at {$date} NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY, setting_value {$text} NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_attempts (
        id {$id}, ip_hash VARCHAR(64) NOT NULL, attempted_at {$date} NOT NULL, succeeded INTEGER NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS webhook_events (
        id {$id}, event_id VARCHAR(120) NOT NULL UNIQUE, event_type VARCHAR(80) NOT NULL,
        received_at {$date} NOT NULL, payload_hash VARCHAR(64) NOT NULL
    )");

    $orderColumns = $driver === 'mysql'
        ? array_column($pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_ASSOC), 'Field')
        : array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('payment_phone', $orderColumns, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN payment_phone VARCHAR(32) NULL');
        $pdo->exec("UPDATE orders SET payment_phone = customer_phone WHERE payment_phone IS NULL OR payment_phone = ''");
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM bundles')->fetchColumn();
    if ($count === 0) {
        $seed = [
            ['orange', '5 Go', '5 Go', 30, 1100, 'Internet mobile Orange', 10],
            ['orange', '7 Go', '7 Go', 30, 1600, 'Internet mobile Orange', 20],
            ['orange', '11 Go', '11 Go', 30, 2200, 'Internet mobile Orange', 30],
            ['orange', '15 Go', '15 Go', 30, 3300, 'Internet mobile Orange', 40],
            ['orange', 'Illimité 15 jours', 'Illimité', 15, 5500, 'Internet illimité Orange', 50],
            ['orange', 'Illimité 30 jours', 'Illimité', 30, 10500, 'Internet illimité Orange', 60],
            ['mtn', '5 Go', '5 Go', 30, 1200, 'Internet mobile MTN', 10],
            ['mtn', '8 Go', '8 Go', 30, 1700, 'Internet mobile MTN', 20],
            ['mtn', '12 Go', '12 Go', 30, 2300, 'Internet mobile MTN', 30],
            ['mtn', '18 Go', '18 Go', 30, 3100, 'Internet mobile MTN', 40],
            ['mtn', 'Illimité 15 jours', 'Illimité', 15, 5400, 'Internet illimité MTN', 50],
            ['mtn', 'Illimité 30 jours', 'Illimité', 30, 9500, 'Internet illimité MTN', 60],
            ['camtel', 'blue One S', 'Illimité · 1 Mb/s', 7, 2000, '4 000 unités d’appel · 100 SMS', 10],
            ['camtel', 'blue One M', 'Illimité · 2 Mb/s', 30, 3000, '6 000 unités d’appel · 200 SMS', 20],
            ['camtel', 'blue One L', 'Illimité · 3 Mb/s', 30, 5000, '10 000 unités d’appel · 300 SMS', 30],
            ['camtel', 'blue One XL', 'Illimité · 4 Mb/s', 30, 10000, '20 000 unités d’appel · 400 SMS', 40],
            ['camtel', 'blue One XXL', 'Illimité · 5 Mb/s', 30, 20000, '40 000 unités d’appel · 500 SMS', 50],
        ];
        $stmt = $pdo->prepare('INSERT INTO bundles (operator_code, name, volume_label, validity_days, price, description, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($seed as $bundle) {
            $stmt->execute([...$bundle, date('Y-m-d H:i:s')]);
        }
    }
}
