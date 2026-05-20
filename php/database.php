<!-- ?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "web";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(503);
    die("Database connection unavailable. Please start MySQL in XAMPP, then refresh the page.");
}
? -->
<?php
$host = "localhost";
$user = "root";
$pass = "mine0520";
$dbname = "web";

$conn = new mysqli($host, $user, $pass, $dbname);
// ADD THIS LINE BELOW
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
