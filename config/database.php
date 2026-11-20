<?php
// XAMPP defaults: user root, empty password.
define('DB_HOST', 'localhost');
define('DB_NAME', 'pc_mastery_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/pc_mastery');          // change if the folder name differs
define('APP_NAME', 'PC Mastery');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Import database/pc_mastery_db.sql and check config/database.php.');
        }
    }
    return $pdo;
}
