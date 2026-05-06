<?php
// TASK 6: Securely load environment variables
// __DIR__ helps PHP find the file in the current folder correctly
$env_path = __DIR__ . '/.env';

if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
} else {
    die(".env file not found! Please create it based on the instructions.");
}

$db_host = $env['DB_HOST'] ?? 'localhost';
$db_name = $env['DB_NAME'] ?? '';
$db_user = $env['DB_USER'] ?? '';
$db_pass = $env['DB_PASS'] ?? '';

// TASK 6: Secure connection using mysqli
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// --- Google API Setup ---
require_once 'vendor/autoload.php';

$google_client = new Google_Client();

// TASK 6: Loading API keys from .env instead of hardcoding
$google_client->setClientId($env['GOOGLE_CLIENT_ID']);
$google_client->setClientSecret($env['GOOGLE_CLIENT_SECRET']);

$google_client->setRedirectUri($env['GOOGLE_REDIRECT_URL']);

$google_client->addScope('email');
$google_client->addScope('profile');

$login_button = $google_client->createAuthUrl();


$github_client_id = $env['GITHUB_CLIENT_ID'];
$github_redirect_url = $env['GITHUB_REDIRECT_URL'];
$github_url = "https://github.com/login/oauth/authorize?client_id=" . $github_client_id . "&scope=user:email";
?>