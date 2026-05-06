<?php
include('config.php');
session_start(); // Start session at the beginning

$token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

if (!isset($token['error'])) {
    $google_client->setAccessToken($token['access_token']);
    $google_service = new Google_Service_Oauth2($google_client);
    $data = $google_service->userinfo->get();

    $name = $data['given_name'] ?? '';
    $email = $data['email'] ?? '';
    $image = '';

    if (!empty($data['picture'])) {
        $image_url = $data['picture'];
        $image_name = uniqid() . '.jpg';
        file_put_contents('uploaded_img/' . $image_name, file_get_contents($image_url));
        $image = $image_name;
    }

    // TASK 4: Check if user already exists to prevent duplicates
    $check_user = $conn->prepare("SELECT id FROM `user_form` WHERE email = ?");
    $check_user->bind_param("s", $email);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        // User exists, just get the ID
        $user_data = $result->fetch_assoc();
        $user_id = $user_data['id'];
    } else {
        // TASK 6: Using prepared statements for security
        $stmt = $conn->prepare("INSERT INTO `user_form` (name, email, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $image);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();
    }

    $_SESSION['user_id'] = $user_id;
    header('location:profile.php');
    exit();
} else {
    echo "Error: " . $token['error'];
}
?>