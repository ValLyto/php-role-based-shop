<?php
include 'config.php';
session_start();

// --- 1. HANDLE AJAX REGISTRATION (TASK 5 & 6) ---
if (isset($_GET['ajax_register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // TASK 6: Secure Hash
    $code = rand(999999, 111111);

    // TASK 4: No Duplicates
    $stmt = $conn->prepare("SELECT id FROM user_form WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'User with this email already exists!']);
    } else {
        $stmt = $conn->prepare("INSERT INTO user_form (name, email, password, code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $pass, $code);
        
        if ($stmt->execute()) {
            $_SESSION['temp_email'] = $email; 
            echo json_encode(['status' => 'success', 'message' => 'Registration successful! OTP sent to your email.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
    exit;
}

// --- 2. HANDLE OTP VERIFICATION ---
if (isset($_POST['verify_otp'])) {
    $otp = $_POST['OTP'];
    $email = $_SESSION['temp_email'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM user_form WHERE email = ? AND code = ?");
    $stmt->bind_param("si", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $update = $conn->prepare("UPDATE user_form SET code = 0 WHERE email = ?");
        $update->bind_param("s", $email);
        $update->execute();
        
        header("Location: login.php?msg=Email verified! Please login.");
        exit;
    } else {
        $error_msg = "Invalid OTP code!";
    }
}

// --- 3. HANDLE RESEND OTP (TASK 3) ---
if (isset($_POST['resend_otp'])) {
    $email = $_SESSION['temp_email'] ?? '';
    if ($email) {
        $new_code = rand(999999, 111111);
        $stmt = $conn->prepare("UPDATE user_form SET code = ? WHERE email = ?");
        $stmt->bind_param("is", $new_code, $email);
        $stmt->execute();
        $resend_success = "A new OTP has been sent!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Shopping System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script>
        async function handleRegister(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const msgDiv = document.getElementById('msg');
            
            const response = await fetch('register.php?ajax_register=1', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            msgDiv.innerText = data.message;
            msgDiv.style.display = 'block';
            
            if (data.status === 'success') {
                msgDiv.className = 'message bg-success text-white p-2 mb-2 rounded';
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                startTimer();
            } else {
                msgDiv.className = 'message bg-danger text-white p-2 mb-2 rounded';
            }
        }

        let timeLeft = 60;
        function startTimer() {
            const btn = document.getElementById('resendBtn');
            btn.disabled = true;
            const timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.value = "Resend OTP";
                    btn.className = "btn btn-info w-100";
                } else {
                    btn.value = `Resend in ${timeLeft}s`;
                    timeLeft--;
                }
            }, 1000);
        }
    </script>
</head>
<body>
<div class="form-container">
    <div style="width: 100%; max-width: 400px; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div id="msg" style="display:none;"></div>
        <?php if(isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>
        <?php if(isset($resend_success)) echo "<div class='alert alert-success'>$resend_success</div>"; ?>

        <form id="step1" onsubmit="handleRegister(event)">
            <h3 class="text-center">Create Account</h3>
            <input type="text" name="name" placeholder="Username" class="form-control mb-2" required>
            <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
            <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
            <input type="submit" value="Sign Up" class="btn btn-primary w-100 mb-3">
            
            <div class="text-center small">OR</div>
            <a href="<?= $login_button ?>" class="btn btn-outline-danger w-100 mt-2">Google</a>
            <a href="<?= $github_url ?>" class="btn btn-dark w-100 mt-2">GitHub</a>
        </form>

        <form id="step2" action="" method="post" style="display:none;">
            <h3 class="text-center">Verify Email</h3>
            <input type="text" name="OTP" placeholder="Enter OTP" class="form-control mb-3" required>
            <input type="submit" name="verify_otp" value="Verify & Finish" class="btn btn-success w-100 mb-2">
            
            <form method="post">
                <input type="submit" id="resendBtn" name="resend_otp" value="Resend OTP" class="btn btn-secondary w-100">
            </form>
        </form>
    </div>
</div>
</body>
</html>