<?php
include 'config.php';
session_start();

// TASK 1 & 4: GitHub Authentication Logic
if (isset($_GET['code'])) {
    $env = parse_ini_file('.env');
    
    // 1. Exchange temporary code for an Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://github.com/login/oauth/access_token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $env['GITHUB_CLIENT_ID'],
        'client_secret' => $env['GITHUB_CLIENT_SECRET'],
        'code' => $_GET['code'],
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = json_decode(curl_exec($ch), true);
    
    if (isset($response['access_token'])) {
        // 2. Get User Info from GitHub API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/user");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $response['access_token'],
            'User-Agent: PHP-Login-System'
        ]);
        $user_data = json_decode(curl_exec($ch), true);
        
        // GitHub doesn't always provide public email, so we craft one or get it
        $email = $user_data['email'] ?? ($user_data['login'] . '@github.com');
        $name = $user_data['name'] ?? $user_data['login'];

        // TASK 4: Check if user already exists (Prevent Duplicates)
        $stmt = $conn->prepare("SELECT id FROM user_form WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists, log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
        } else {
            // New user, register them automatically
            $ins = $conn->prepare("INSERT INTO user_form (name, email, user_type) VALUES (?, ?, 'user')");
            $ins->bind_param("ss", $name, $email);
            $ins->execute();
            $_SESSION['user_id'] = $ins->insert_id;
        }
        
        header("Location: profile.php");
        exit();
    }
} else {
    header("Location: login.php");
}
?>