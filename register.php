<?php
require 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $income   = $_POST['income'];

    // Check that the email is not already used
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $error = "البريد الإلكتروني مستخدم مسبقًا.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, income) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $income])) {
            $success = "تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.";
        } else {
            $error = "حدث خطأ أثناء إنشاء الحساب.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب</title>
<link rel="stylesheet" href="style/style.css">
</head>
<body>
  <h2>إنشاء حساب جديد</h2>

  <?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
  <?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
  <?php endif; ?>

  <form method="post">
    <label>الاسم:</label><br>
    <input type="text" name="name" required><br><br>

    <label>البريد الإلكتروني:</label><br>
    <input type="email" name="email" required><br><br>

    <label>كلمة المرور:</label><br>
    <input type="password" name="password" required><br><br>

    <label>الدخل الشهري (ريال):</label><br>
    <input type="number" name="income" required><br><br>

    <button type="submit">إنشاء الحساب</button>
  </form>

  <p>لديك حساب؟ <a href="login.php">تسجيل الدخول</a></p>
</body>
</html>
