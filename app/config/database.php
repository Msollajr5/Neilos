<?php
// ============================================================
// Neilos Partner Portal — Portable Configuration & Database Bootstrap
// ============================================================

// 1. Zero-dependency .env loader
if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $filePath): void {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Locate project root and load .env if present
$projectRoot = dirname(__DIR__, 2);
loadEnvFile($projectRoot . '/.env');

// Helper to get environment variable with fallback
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'empty' || $val === '(empty)') return '';
        if ($val === 'null' || $val === '(null)') return null;
        return $val;
    }
}

// Configure error display according to APP_DEBUG / APP_ENV
if (env('APP_DEBUG', false) === false || env('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// 2. Database Connection Constants
define('DB_HOST',    env('DB_HOST', 'localhost'));
define('DB_PORT',    env('DB_PORT', '3306'));
define('DB_NAME',    env('DB_NAME', 'neilos_portal'));
define('DB_USER',    env('DB_USER', 'neilosne_tz'));
define('DB_PASS',    env('DB_PASS', 'NeilosTZ@2026'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// 3. Dynamic and Portable Application Paths & URL
if (!defined('ROOT_DIR'))   define('ROOT_DIR', $projectRoot);
if (!defined('APP_DIR'))    define('APP_DIR', ROOT_DIR . '/app');
if (!defined('PUBLIC_DIR')) define('PUBLIC_DIR', ROOT_DIR . '/public');
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', PUBLIC_DIR . '/uploads/');

// Dynamic APP_URL auto-detection if not specified in .env
if (!defined('APP_URL')) {
    $configuredUrl = env('APP_URL');
    if (!empty($configuredUrl)) {
        define('APP_URL', rtrim($configuredUrl, '/'));
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir    = str_replace('\\', '/', dirname($script));
        $base   = ($dir === '/' || $dir === '.') ? '' : $dir;
        define('APP_URL', rtrim("{$scheme}://{$host}{$base}", '/'));
    }
}

define('APP_NAME',           env('APP_NAME', 'Neilos Partner Portal'));
define('UPLOAD_URL',         APP_URL . '/uploads/');
define('MAX_FILE_SIZE',      (int)env('UPLOAD_MAX_SIZE', 10 * 1024 * 1024)); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'csv', 'doc', 'docx']);

define('USD_TZS_RATE', (float)env('USD_TZS_RATE', 2585));
define('VAT_RATE', (float)env('VAT_RATE', 0.18));
define('MAX_NRC_AMOUNT', (float)env('MAX_NRC_AMOUNT', 100000000.0));
define('MAX_MRC_AMOUNT', (float)env('MAX_MRC_AMOUNT', 500000000.0));
define('MIN_NRC_AMOUNT', (float)env('MIN_NRC_AMOUNT', 0.0));
define('MIN_MRC_AMOUNT', (float)env('MIN_MRC_AMOUNT', 0.0));
define('KAM_LIST', ['Gloria Entebbe', 'Michael Corss']);

define('SESSION_LIFETIME',          (int)env('SESSION_LIFETIME', 3600 * 8));
define('SESSION_IDLE_TIMEOUT',      (int)env('SESSION_IDLE_TIMEOUT', 900));
define('PASSWORD_EXPIRY_DAYS',      (int)env('PASSWORD_EXPIRY_DAYS', 90));
define('PASSWORD_EXPIRY_WARN_DAYS', (int)env('PASSWORD_EXPIRY_WARN_DAYS', 14));

$pdo = null;

function getDB(): PDO {
    global $pdo;

    if ($pdo !== null) {
        try {
            $pdo->query('SELECT 1');
        } catch (PDOException $e) {
            $pdo = null;
        }
    }

    if ($pdo === null) {
        $socketPath = '/opt/lampp/var/mysql/mysql.sock';
        if (file_exists($socketPath)) {
            $dsn = 'mysql:unix_socket=' . $socketPath . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=28800, interactive_timeout=28800, time_zone='+03:00'",
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $runQuiet = function($sql) use ($pdo) {
                try { $pdo->exec($sql); } catch (Throwable $e) {}
            };

            $runQuiet("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'Partner'");
            $runQuiet("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER password");
            $runQuiet("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_first_login TINYINT(1) NOT NULL DEFAULT 0");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN user_id INT UNSIGNED NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN service_id VARCHAR(100) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN capacity INT UNSIGNED NULL DEFAULT 0");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN pop_location VARCHAR(200) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN customer_contact_name VARCHAR(200) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN customer_contact_phone VARCHAR(50) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN customer_contact_email VARCHAR(200) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN site_category VARCHAR(100) NULL");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN service_type VARCHAR(100) NOT NULL DEFAULT 'FTTH'");
            $runQuiet("ALTER TABLE orders MODIFY COLUMN status VARCHAR(100) NOT NULL DEFAULT 'Feasibility Review'");
        } catch (PDOException $e) {
            http_response_code(500);
            $dbErrMsg = env('APP_DEBUG', false) ? ('<p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>') : '';
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Database Connection Error</h2><p>Could not connect to the database. Please contact the system administrator.</p>' . $dbErrMsg . '</div>');
        }
    }
    return $pdo;
}
