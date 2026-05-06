<?php
include 'config.php';
session_start();


$info_message = $_GET['msg'] ?? '';

if(isset($_POST['submit'])){
   $email = $_POST['email'];
   $password = $_POST['password'];

   // TASK 6: Prepared Statement 
   $stmt = $conn->prepare("SELECT * FROM `user_form` WHERE email = ?");
   $stmt->bind_param("s", $email);
   $stmt->execute();
   $result = $stmt->get_result();

   if($result->num_rows > 0){
      $row = $result->fetch_assoc();
      
      // TASK 6: Verifying the hashed password (support both md5 and password_hash)
      if(password_verify($password, $row['password']) || md5($password) === $row['password']){
         // Rehash if using old md5
         if(md5($password) === $row['password']){
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE user_form SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hash, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
         }
         $_SESSION['user_id'] = $row['id'];
         header('location:profile.php');
         exit();
      } else {
         $message[] = 'Incorrect email or password!';
      }
   } else {
      $message[] = 'Incorrect email or password!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Sign In - TechHub</title>
   <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
      .btn-social {
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 10px;
         font-size: 14px;
         font-weight: 600;
         padding: 12px;
         border-radius: 12px;
         margin-bottom: 12px;
         border: 1px solid #e2e8f0;
         background: #fff;
         color: #333;
         cursor: pointer;
         transition: 0.3s;
         width: 100%;
      }
      .btn-social:hover { opacity: 0.8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
      .btn-github { background-color: #24292e; color: #fff; border: none; }
      .divider {
         display: flex;
         align-items: center;
         text-align: center;
         margin: 25px 0;
         color: #64748b;
         font-size: 13px;
         font-weight: 600;
      }
      .divider::before, .divider::after {
         content: '';
         flex: 1;
         border-bottom: 1px solid #e2e8f0;
      }
      .divider:not(:empty)::before { margin-right: 1em; }
      .divider:not(:empty)::after { margin-left: 1em; }
   </style>
</head>
<body>
   
<div class="form-container">
   <form action="" method="post">
      <h2 style="text-align: center; margin-bottom: 25px; color: var(--dark);">Sign In</h2>
      
      <?php

      if(!empty($info_message)){
          echo '<div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 14px; font-weight: 500;">'.htmlspecialchars($info_message).'</div>';
      }

   
      if(isset($message)){
         foreach($message as $msg){
            echo '<div style="background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 14px; font-weight: 500;">'.$msg.'</div>';
         }
      }
      ?>

      <input type="email" name="email" placeholder="Email Address" class="box" required>
      <input type="password" name="password" placeholder="Password" class="box" required>
      <button type="submit" name="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Login Now</button>

      <div class="divider">OR</div>

      <div class="social-buttons">
         <button onclick="window.location = '<?php echo $login_button ?? '#'; ?>'" type="button" class="btn-social btn-google">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" width="18">
            Continue with Google
         </button>

         <button onclick="window.location = '<?php echo $github_url ?? '#'; ?>'" type="button" class="btn-social btn-github">
            <i class="fab fa-github" style="font-size: 18px;"></i>
            Continue with GitHub
         </button>
      </div>

      <p style="text-align: center; margin-top: 25px; font-size: 14px; color: var(--slate);">
         Don't have an account? <br> 
         <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-block; margin-top: 5px;">Register here</a>
      </p>
   </form>
</div>

</body>
</html>