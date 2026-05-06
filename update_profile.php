<?php
include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

if(isset($_POST['update_profile'])){

   $update_name = $_POST['update_name'];
   $update_email = $_POST['update_email'];

   $stmt = $conn->prepare("UPDATE `user_form` SET name = ?, email = ? WHERE id = ?");
   $stmt->bind_param("ssi", $update_name, $update_email, $user_id);
   $stmt->execute();
   $stmt->close();

   // Get current password hash
   $stmt = $conn->prepare("SELECT password FROM `user_form` WHERE id = ?");
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();
   $current_hash = $user['password'];
   $stmt->close();

   $old_pass_input = $_POST['update_pass'];
   $new_pass = $_POST['new_pass'];
   $confirm_pass = $_POST['confirm_pass'];

   if(!empty($old_pass_input) || !empty($new_pass) || !empty($confirm_pass)){
      if(!(password_verify($old_pass_input, $current_hash) || md5($old_pass_input) === $current_hash)){
         $message[] = 'Old password not matched!';
      }elseif($new_pass != $confirm_pass){
         $message[] = 'Confirm password not matched!';
      }else{
         $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
         $stmt = $conn->prepare("UPDATE `user_form` SET password = ? WHERE id = ?");
         $stmt->bind_param("si", $hashed_new_pass, $user_id);
         $stmt->execute();
         $stmt->close();
         $message[] = 'Password updated successfully!';
      }
   }

   $update_image = $_FILES['update_image']['name'];
   $update_image_size = $_FILES['update_image']['size'];
   $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
   $update_image_folder = 'uploaded_img/'.$update_image;

   if(!empty($update_image)){
      if($update_image_size > 2000000){
         $message[] = 'Image is too large!';
      }else{
         $stmt = $conn->prepare("UPDATE `user_form` SET image = ? WHERE id = ?");
         $stmt->bind_param("si", $update_image, $user_id);
         if($stmt->execute()){
            move_uploaded_file($update_image_tmp_name, $update_image_folder);
         }
         $stmt->close();
         $message[] = 'Image updated successfully!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile - TechHub</title>
   <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
   
<div class="form-container wide">

   <?php
      $select = mysqli_query($conn, "SELECT * FROM `user_form` WHERE id = '$user_id'") or die('query failed');
      if(mysqli_num_rows($select) > 0){
         $fetch = mysqli_fetch_assoc($select);
      }
   ?>

   <form action="" method="post" enctype="multipart/form-data">
      <h2 style="text-align: center; margin-bottom: 25px; color: var(--dark);">Update Profile</h2>

      <?php
         if(isset($message)){
            foreach($message as $msg){
               
               $bg = (strpos(strtolower($msg), 'success') !== false) ? '#dcfce7' : '#fee2e2';
               $color = (strpos(strtolower($msg), 'success') !== false) ? '#166534' : '#ef4444';
               echo '<div style="background: '.$bg.'; color: '.$color.'; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500;">'.$msg.'</div>';
            }
         }
      ?>

      <div style="text-align: center; margin-bottom: 30px;">
         <?php
            if($fetch['image'] == ''){
               echo '<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-soft); box-shadow: var(--shadow);">';
            }else{
               echo '<img src="uploaded_img/'.$fetch['image'].'" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-soft); box-shadow: var(--shadow);">';
            }
         ?>
      </div>
      
      <div style="display: flex; flex-wrap: wrap; gap: 25px;">
         
         <div style="flex: 1; min-width: 250px;">
            <label style="display:block; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">Username:</label>
            <input type="text" name="update_name" value="<?php echo htmlspecialchars($fetch['name']); ?>" class="box" style="margin-top:0;">
            
            <label style="display:block; margin-top: 15px; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">Email Address:</label>
            <input type="email" name="update_email" value="<?php echo htmlspecialchars($fetch['email']); ?>" class="box" style="margin-top:0;">
            
            <label style="display:block; margin-top: 15px; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">Update Picture:</label>
            <input type="file" name="update_image" accept="image/jpg, image/jpeg, image/png" class="box" style="margin-top:0; padding: 9px 16px;">
         </div>

         <div style="flex: 1; min-width: 250px;">
            <input type="hidden" name="old_pass" value="<?php echo $fetch['password']; ?>">
            
            <label style="display:block; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">Old Password:</label>
            <input type="password" name="update_pass" placeholder="Enter previous password" class="box" style="margin-top:0;">
            
            <label style="display:block; margin-top: 15px; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">New Password:</label>
            <input type="password" name="new_pass" placeholder="Enter new password" class="box" style="margin-top:0;">
            
            <label style="display:block; margin-top: 15px; margin-bottom: 5px; font-size: 13px; color: var(--slate); font-weight: 600;">Confirm Password:</label>
            <input type="password" name="confirm_pass" placeholder="Confirm new password" class="box" style="margin-top:0;">
         </div>

      </div>

      <div style="display: flex; gap: 15px; margin-top: 35px;">
         <button type="submit" name="update_profile" class="btn btn-primary" style="flex: 1;">Update Profile</button>
         <a href="profile.php" class="btn btn-danger" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Go Back</a>
      </div>
   </form>

</div>

</body>
</html>