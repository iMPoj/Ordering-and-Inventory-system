<?php
/**
 * Database connection script for InfinityFree.
 * This file establishes a connection to the MySQL database using PDO (PHP Data Objects).
 */

// --- Database Configuration ---
$host = 'sql310.infinityfree.com';
$db   = 'if0_39889135_inventory_db';
$user = 'if0_39889135';
$pass = '20iMPoj25601'; // Your correct password
$charset = 'utf8mb4';

// --- Data Source Name (DSN) ---
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// --- PDO Connection Options ---
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Establish the Connection ---
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     http_response_code(500);

     // This message will now be shown if credentials are wrong
     echo json_encode([
       'success' => false, 
       'message' => 'Database connection failed. Please check credentials in db_connect.php'
     ]);
     
     exit;
}
?>