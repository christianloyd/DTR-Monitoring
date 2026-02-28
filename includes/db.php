<?php
// ============================================================
// includes/db.php  — PDO Connection
// ============================================================

// ============================================================
// 🚀 PRODUCTION — Replace the values below when deploying.
//    Copy your hosting panel's MySQL credentials here and
//    comment out the "Local Development" block beneath.
// ============================================================
//
// define('DB_HOST',    'sql307.infinityfree.com');   // MySQL Hostname
// define('DB_NAME',    'if0_41269751_XXX');           // MySQL Database Name
// define('DB_USER',    'if0_41269751');               // MySQL Username
// define('DB_PASS',    'YOUR_PASSWORD_HERE');         // MySQL Password
// define('DB_CHARSET', 'utf8mb4');
//
// ============================================================
// 💻 LOCAL DEVELOPMENT (XAMPP defaults) — comment out for production
// ============================================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'ojt_dtr_system');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Default XAMPP password (empty)
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
