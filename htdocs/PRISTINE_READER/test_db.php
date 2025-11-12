<?php
// Set the content type to plain text for clear output
header('Content-Type: text/plain');

// Turn on error reporting to see everything
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Attempting to connect to the database...\n\n";

// --- Database Credentials ---
// IMPORTANT: Make sure these are the EXACT credentials provided by your hosting service.
$servername = "sql310.infinityfree.com"; // Or the specific host provided by InfinityFree
$username = "if0_39889135";;     // Your InfinityFree username
$password = "20iMPoj25601";          // Your InfinityFree password
$dbname = "if0_39889135_pristine_reader"; // Your InfinityFree database name

// --- Connection Logic ---
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "✅ SUCCESS: Database connection is working correctly!";

    $conn->close();

} catch (Exception $e) {
    echo "❌ ERROR: Could not connect to the database.\n";
    echo "Server says: " . $e->getMessage() . "\n\n";
    echo "--- What to check ---\n";
    echo "1. Are the servername, username, password, and dbname in 'db.php' and this file correct?\n";
    echo "2. Have you created the 'pristine_reader' database?\n";
    echo "3. Is the database server running?";
}

?>