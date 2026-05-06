<?php

include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
};

// Add User Form
echo "<h3>Add Admin User</h3>";
echo "<form action='' method='post'>";
echo "Name: <input type='text' name='name'><br>";
echo "Email: <input type='text' name='email'><br>";
echo "Password: <input type='text' name='password'><br>";
echo "<input type='submit' name='add_admin' value='Add User'>";
echo "</form>";



if(isset($_POST['add_admin']))  {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = 'admin';
    // Insert new admin user into the database
    $stmt = $conn->prepare("INSERT INTO user_form (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $user_type);
    if ($stmt->execute()) {
        echo "New record created successfully";
         header('location:profile.php');
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
