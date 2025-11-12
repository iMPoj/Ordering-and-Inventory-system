<?php
// This tells the server to show all errors directly on the page.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";
echo "<p>This script will check if the server can connect to your database using the credentials from your db_connect.php file.</p>";
echo "<hr>";

// These are the credentials from your db_connect.php file
$host = 'sql310.infinityfree.com';
$db   = 'if0_39889135_inventory_db';
$user = 'if0_39889135';
$pass = '20iMPoj25601'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<p>Attempting to connect with PDO...</p>";

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "<p style='color:green; font-weight:bold;'>SUCCESS: Connection to the database was successful!</p>";
} catch (\PDOException $e) {
     echo "<p style='color:red; font-weight:bold;'>CRITICAL ERROR: Connection failed.</p>";
     echo "<p><strong>Error Message:</strong> " . $e->getMessage() . "</p>";
     echo "<p>Please double-check your password and database name in your db_connect.php file. This error is the reason your application is crashing.</p>";
}
?>