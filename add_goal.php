<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $target_amount = $_POST['target_amount'];
    $deadline = $_POST['deadline'];
    $current_saved = 0; // Initialize current saved amount to 0

    if ($target_amount <= 0) {
        $error = "المبلغ المستهدف يجب أن يكون أكبر من صفر.";
    } elseif (empty($title)) {
        $error = "يرجى إدخال اسم الهدف.";
    } elseif (empty($deadline)) {
        $error = "يرجى تحديد الموعد النهائي.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, target_amount, current_saved, deadline) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $target_amount, $current_saved, $deadline])) {
            $success = "تم إضافة الهدف المالي بنجاح.";
        } else {
            $error = "حدث خطأ أثناء إضافة الهدف.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>إضافة هدف مالي</title>
<link rel="stylesheet" href="style/style.css">
</head>
<body>
  <h2>إضافة هدف مالي جديد</h2>

  <?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
  <?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
  <?php endif; ?>

  <form method="post">
    <label>اسم الهدف:</label><br>
    <input type="text" name="title" required><br><br>

    <label>المبلغ المستهدف (ريال):</label><br>
    <input type="number" name="target_amount" step="0.01" required><br><br>

    <label>الموعد النهائي:</label><br>
    <input type="date" name="deadline" required><br><br>

    <button type="submit">إضافة الهدف</button>
  </form>

  <p><a href="dashboard.php">العودة للوحة التحكم</a></p>
</body>
</html>
