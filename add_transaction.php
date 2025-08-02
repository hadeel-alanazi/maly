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
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $date = $_POST['date'] ?: date('Y-m-d'); // If the date is not specified, use today's date
    if ($amount <= 0) {
        $error = "المبلغ يجب أن يكون أكبر من صفر.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category, amount, date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $category, $amount, $date])) {
            $success = "تم إضافة المصروف بنجاح.";
        } else {
            $error = "حدث خطأ أثناء إضافة المصروف.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>إضافة مصروف جديد</title>
<link rel="stylesheet" href="style/style.css">
</head>
<body>
  <h2>إضافة مصروف جديد</h2>

  <?php if ($success): ?>
    <p style="color: green;"><?= $success ?></p>
  <?php elseif ($error): ?>
    <p style="color: red;"><?= $error ?></p>
  <?php endif; ?>

  <form method="post">
    <label>الفئة:</label><br>
    <select name="category" required>
      <option value="">اختر فئة</option>
      <option value="طعام">طعام</option>
      <option value="تسوق">تسوق</option>
      <option value="فواتير">فواتير</option>
      <option value="ترفيه">ترفيه</option>
      <option value="نقل">نقل</option>
      <option value="أخرى">أخرى</option>
    </select><br><br>

    <label>المبلغ (ريال):</label><br>
    <input type="number" name="amount" step="0.01" required><br><br>

    <label>التاريخ:</label><br>
    <input type="date" name="date"><br><br>

    <button type="submit">إضافة المصروف</button>
  </form>

  <p><a href="dashboard.php">العودة للوحة التحكم</a></p>
</body>
</html>
