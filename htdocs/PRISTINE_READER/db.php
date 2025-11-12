<?php
$servername = "sql310.infinityfree.com"; // Or the specific host provided by InfinityFree
$username = "if0_39889135";;     // Your InfinityFree username
$password = "20iMPoj25601";          // Your InfinityFree password
$dbname = "if0_39889135_pristine_reader"; // Your InfinityFree database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>