<?php
include 'config.php';
session_start();


$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header('location:login.php'); exit; }
if (isset($_GET['logout'])) { session_destroy(); header('location:login.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM `user_form` WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$fetch = $stmt->get_result()->fetch_assoc();


if (isset($_POST["add_to_cart"])) {
    if (!isset($_SESSION["shopping_cart"])) { $_SESSION["shopping_cart"] = []; }
    $item_id_list = array_column($_SESSION["shopping_cart"], "product_id");
    
    if (!in_array($_GET["id"], $item_id_list)) {
        $_SESSION["shopping_cart"][] = [
            'product_id'       => $_GET["id"],
            'product_name'     => $_POST["hidden_name"],
            'product_price'    => $_POST["hidden_price"],
            'product_quantity' => $_POST["quantity"]
        ];
    }
    header("Location: profile.php#cart"); exit;
}

if (isset($_GET["action"]) && $_GET["action"] == "delete_cart") {
    foreach ($_SESSION["shopping_cart"] as $keys => $values) {
        if ($values["product_id"] == $_GET["id"]) {
            unset($_SESSION["shopping_cart"][$keys]);
            $_SESSION["shopping_cart"] = array_values($_SESSION["shopping_cart"]);
        }
    }
    header("Location: profile.php#cart"); exit;
}

/* --- 3. AJAX И CRUD  --- */
if (isset($_GET['ajax_add_admin'])) {
    $name = $_POST['name']; $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user_form (name, email, password, user_type) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $name, $email, $pass);
    echo ($stmt->execute()) ? json_encode(['status' => 'success']) : json_encode(['status' => 'error']);
    exit;
}

if (isset($_GET['delete_product']) && $fetch['user_type'] != 'user') {
    $stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_product']);
    $stmt->execute();
    header("Location: profile.php"); exit;
}

if (isset($_POST['add_product']) && $fetch['user_type'] != 'user') {
    $desc = $_POST['description']; $price = $_POST['price'];
    $image = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], 'products_img/' . $image);
    $stmt = $conn->prepare("INSERT INTO product (description, price, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $desc, $price, $image);
    $stmt->execute();
    header("Location: profile.php"); exit;
}


if (isset($_POST['submit_review'])) {
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $rating = 5;
    $stmt->bind_param("iiis", $_POST['product_id'], $user_id, $rating, $_POST['comment']);
    $stmt->execute();
    header("Location: profile.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TechHub Barrhead - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        /* UI  */
        .cart-badge { background: #ef4444; color: white; border-radius: 50%; padding: 2px 7px; font-size: 10px; position: absolute; top: -5px; right: -5px; }
        .review-box { background: #f8fafc; padding: 10px; border-radius: 12px; margin-bottom: 8px; border: 1px solid #f1f5f9; font-size: 12px; text-align: left; }
        .review-author { font-weight: 700; color: #2563eb; font-size: 11px; }
        .total-row { background: #f1f5f9; font-weight: 800; font-size: 16px; }
        .ai-toggle { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; cursor: pointer; box-shadow: 0 10px 25px rgba(37,99,235,0.4); z-index: 1000; transition: 0.3s; }
        .ai-widget { position: fixed; bottom: 100px; right: 30px; width: 350px; height: 480px; background: white; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.2); display: none; flex-direction: column; z-index: 999; overflow: hidden; border: 1px solid #e2e8f0; }
        .ai-header { background: #1e293b; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .ai-body { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
        .ai-message { padding: 10px 14px; border-radius: 15px; font-size: 13px; max-width: 85%; line-height: 1.4; }
        .ai-message.bot { background: white; align-self: flex-start; border: 1px solid #e2e8f0; color: #334155; }
        .ai-message.user { background: #2563eb; color: white; align-self: flex-end; }
        .ai-input-area { padding: 12px; border-top: 1px solid #eee; display: flex; gap: 8px; background: white; }
        .ai-input-area input { flex: 1; padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 25px; outline: none; font-size: 13px; }
    </style>
</head>
<body>

<div class="profile-layout">
    <aside class="sidebar">
        <div class="profile-card">
            <img src="<?php echo $fetch['image'] ? 'uploaded_img/'.$fetch['image'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; ?>" class="profile-img">
            <h3 style="margin-top:15px;"><?php echo htmlspecialchars($fetch['name']); ?></h3>
            <p style="text-transform: uppercase; font-size: 10px; font-weight: 700; color: #64748b;">Role: <?php echo $fetch['user_type']; ?></p>
            
            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 12px;">
                <a href="update_profile.php" class="btn btn-primary" style="text-decoration:none;"><i class="fas fa-user-cog"></i> Edit Profile</a>
                
                <?php if($fetch['user_type'] == 'user'): ?>
                <a href="#cart" class="btn btn-success" style="text-decoration:none; background: #10b981; position: relative;">
                    <i class="fas fa-shopping-basket"></i> My Cart 
                    <?php if(!empty($_SESSION["shopping_cart"])) echo '<span class="cart-badge">'.count($_SESSION["shopping_cart"]).'</span>'; ?>
                </a>
                <?php endif; ?>

                <a href="profile.php?logout=1" class="btn btn-danger" style="text-decoration:none;"><i class="fas fa-power-off"></i> Logout</a>
            </div>
        </div>
    </aside>

    <main class="content-area">
        
        <?php if ($fetch['user_type'] == 'user' && !empty($_SESSION["shopping_cart"])): ?>
            <div id="cart" class="content-card" style="border-top: 4px solid #10b981; animation: slideUp 0.4s ease;">
                <h4><i class="fas fa-shopping-cart"></i> My Shopping Cart</h4>
                <table class="table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach($_SESSION["shopping_cart"] as $item): 
                            $subtotal = $item["product_quantity"] * $item["product_price"];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo $item["product_name"]; ?></td>
                            <td><?php echo $item["product_quantity"]; ?></td>
                            <td>£<?php echo $item["product_price"]; ?></td>
                            <td>£<?php echo number_format($subtotal, 2); ?></td>
                            <td><a href="profile.php?action=delete_cart&id=<?php echo $item["product_id"]; ?>" class="text-danger"><i class="fas fa-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" align="right">Grand Total:</td>
                            <td>£<?php echo number_format($total, 2); ?></td>
                            <td><button class="btn btn-success btn-sm" onclick="alert('Order received! We will contact you shortly.')">Checkout</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($fetch['user_type'] == 'owner'): ?>
            <div class="content-card" style="border-left: 4px solid var(--primary);">
                <h4><i class="fas fa-user-shield"></i> Add Admin Account (AJAX)</h4>
                <form onsubmit="handleAdminAdd(event)" style="display:flex; gap:10px; margin-top:15px;">
                    <input type="text" name="name" placeholder="Name" class="box" style="margin:0;" required>
                    <input type="email" name="email" placeholder="Email" class="box" style="margin:0;" required>
                    <input type="password" name="password" placeholder="Pass" class="box" style="margin:0;" required>
                    <button type="submit" class="btn btn-primary" style="width:auto; margin:0;">Create</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($fetch['user_type'] != 'user'): ?>
            <div class="content-card">
                <h4><i class="fas fa-boxes"></i> Inventory Management</h4>
                <form action="" method="post" enctype="multipart/form-data" style="display:flex; gap:10px; margin: 20px 0;">
                    <input type="text" name="description" placeholder="Model Name" class="box" style="flex:2; margin:0;" required>
                    <input type="number" step="0.01" name="price" placeholder="Price £" class="box" style="flex:1; margin:0;" required>
                    <input type="file" name="image" class="box" style="flex:1; margin:0; padding: 10px;" required>
                    <button type="submit" name="add_product" class="btn btn-success" style="width:auto; margin:0;">Add</button>
                </form>
                <table class="table">
                    <thead><tr><th>Img</th><th>Model</th><th>Price</th><th>Latest Review</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php $p_q = mysqli_query($conn, "SELECT * FROM product");
                        while($p = mysqli_fetch_assoc($p_q)): 
                            $pid = $p['id'];
                        ?>
                            <tr>
                                <td><img src="products_img/<?php echo $p['image']; ?>" width="45" style="border-radius:8px;"></td>
                                <td><?php echo $p['description']; ?></td>
                                <td>£<?php echo $p['price']; ?></td>
                                <td>
                                    <?php
                                    $rev_q = mysqli_query($conn, "SELECT r.comment, u.name FROM reviews r JOIN user_form u ON r.user_id = u.id WHERE r.product_id = '$pid' ORDER BY r.id DESC LIMIT 1");
                                    if($r = mysqli_fetch_assoc($rev_q)) echo '<span style="font-size:11px;"><b>'.$r['name'].':</b> '.$r['comment'].'</span>';
                                    else echo '<span style="color:#ccc;">No reviews</span>';
                                    ?>
                                </td>
                                <td><a href="profile.php?delete_product=<?php echo $pid; ?>" class="text-danger"><i class="fas fa-trash-alt"></i></a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            
            <div class="content-card">
                <h4 style="margin-bottom:25px;"><i class="fas fa-shopping-bag"></i> Available Phones</h4>
                <div class="product-grid">
                    <?php $p_q = mysqli_query($conn, "SELECT * FROM product");
                    while($p = mysqli_fetch_assoc($p_q)): 
                        $pid = $p['id'];
                    ?>
                        <div class="product-item" style="display:flex; flex-direction:column; justify-content:space-between;">
                            <div>
                                <img src="products_img/<?php echo $p['image']; ?>" style="max-height:180px;">
                                <h5 style="margin:15px 0 5px 0;"><?php echo $p['description']; ?></h5>
                                <p style="font-weight:700; font-size:20px; color:var(--primary); margin-bottom:15px;">£<?php echo $p['price']; ?></p>
                            </div>
                            
                            <div>
                                <form method="post" action="profile.php?id=<?php echo $pid; ?>" style="margin-bottom:20px;">
                                    <input type="hidden" name="hidden_name" value="<?php echo $p['description']; ?>">
                                    <input type="hidden" name="hidden_price" value="<?php echo $p['price']; ?>">
                                    <input type="number" name="quantity" value="1" min="1" class="box" style="width:65px; display:inline-block; margin:0 5px 0 0;">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary" style="padding:10px 15px; width:auto;"><i class="fas fa-cart-plus"></i> Buy</button>
                                </form>

                                <div style="border-top:1px solid #f1f5f9; padding-top:15px;">
                                    <?php
                                    $rev_user_q = mysqli_query($conn, "SELECT r.comment, u.name FROM reviews r JOIN user_form u ON r.user_id = u.id WHERE r.product_id = '$pid' ORDER BY r.id DESC LIMIT 2");
                                    while($rev = mysqli_fetch_assoc($rev_user_q)): ?>
                                        <div class="review-box">
                                            <div class="review-author"><?php echo $rev['name']; ?></div>
                                            <div class="review-text"><?php echo $rev['comment']; ?></div>
                                        </div>
                                    <?php endwhile; ?>
                                    
                                    <form method="post" style="display:flex; gap:5px; margin-top:10px;">
                                        <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                        <input type="text" name="comment" placeholder="Review..." class="box" style="font-size:11px; margin:0; border-radius:15px;" required>
                                        <button type="submit" name="submit_review" class="btn btn-dark" style="width:auto; padding:5px 12px; border-radius:15px;">OK</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<div class="ai-toggle" onclick="toggleChat()"><i class="fas fa-robot"></i></div>
<div class="ai-widget" id="aiWidget">
    <div class="ai-header">
        <span><i class="fas fa-bolt"></i> TechHub Smart Assistant</span>
        <i class="fas fa-times" onclick="toggleChat()" style="cursor:pointer;"></i>
    </div>
    <div class="ai-body" id="aiBody">
        <div class="ai-message bot">Hello! I'm your Barrhead shop assistant. Ask me about our prices or products!</div>
    </div>
    <div class="ai-input-area">
        <input type="text" id="aiInput" placeholder="Type here..." onkeypress="if(event.key==='Enter') sendAI()">
        <button class="btn btn-primary" onclick="sendAI()" style="width:40px; height:40px; border-radius:50%; padding:0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
function toggleChat() { const w = document.getElementById('aiWidget'); w.style.display = (w.style.display === 'flex') ? 'none' : 'flex'; }

async function handleAdminAdd(e) {
    e.preventDefault();
    const res = await fetch('profile.php?ajax_add_admin=1', { method: 'POST', body: new FormData(e.target) });
    const data = await res.json();
    alert(data.status === 'success' ? 'Admin Created!' : 'Error');
    if(data.status === 'success') location.reload();
}

async function sendAI() {
    const input = document.getElementById('aiInput'); const body = document.getElementById('aiBody');
    const msg = input.value.trim(); if(!msg) return;
    body.innerHTML += `<div class="ai-message user">${msg}</div>`; input.value = '';
    const loaderId = 'L' + Date.now();
    body.innerHTML += `<div class="ai-message bot" id="${loaderId}">... typing</div>`;
    body.scrollTop = body.scrollHeight;
    try {
        const res = await fetch('ai_response.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'message=' + encodeURIComponent(msg) });
        const data = await res.json(); document.getElementById(loaderId).innerText = data.reply;
    } catch (e) { document.getElementById(loaderId).innerText = "AI is busy. Try again in 1 min."; }
    body.scrollTop = body.scrollHeight;
}
</script>

</body>
</html>