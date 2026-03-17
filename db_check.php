<?php
require_once 'includes/db.php';

header('Content-Type: text/plain');

echo "Database Connection Debug\n";
echo "=========================\n\n";

$db_type = getenv('DB_TYPE') ?: 'mysql (default)';
$db_host = getenv('DB_HOST') ?: '127.0.0.1 (default)';
$db_name = getenv('DB_NAME') ?: 'pantherverse_db (default)';
$db_user = getenv('DB_USER') ?: 'root (default)';
$db_port = getenv('DB_PORT') ?: 'auto';
$db_url = getenv('DATABASE_URL') ? 'SET' : 'NOT SET';

echo "Variables:\n";
echo "DB_TYPE: $db_type\n";
echo "DB_HOST: $db_host\n";
echo "DB_NAME: $db_name\n";
echo "DB_USER: $db_user\n";
echo "DB_PORT: $db_port\n";
echo "DATABASE_URL: $db_url\n\n";

echo "Attempting Connection...\n";

try {
    $pdo = db();
    echo "SUCCESS! Connected to " . DB_TYPE . "\n";
    
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "Version: $version\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
