<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login, session started
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['income'] = $user['income'];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تسجيل الدخول</title>
<link rel="stylesheet" href="style/style.css">
</head>
<body>
  <h2>تسجيل الدخول</h2>

  <?php if ($error): ?>
    <p style="color: red;"><?= $error ?></p>
  <?php endif; ?>

  <form method="post">
    <label>البريد الإلكتروني:</label><br>
    <input type="email" name="email" required><br><br>

    <label>كلمة المرور:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">دخول</button>
  </form>

  <p>ما عندك حساب؟ <a href="register.php">أنشئ حساب</a></p>
  <p>نسيت كلمة المرور <a href="#"> </a></p>
</body>
</html>
