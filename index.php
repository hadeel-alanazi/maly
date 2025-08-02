<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>مرحباً بك في المساعد المالي الذكي</title>

  <!-- CSS -->
  <link rel="stylesheet" href="style/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    body {
      background-image: url('style/bg.jpg');
      background-size: cover;
      background-position: center center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      margin: 0;
      font-family: 'Changa', sans-serif;
      text-align: center;
    }

    h1, p {
      color: #17123a;
    }

    .logo-img {
      width: 300px;
      height: auto;
    }

    .center-content {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .links a {
      color: #17123a;
      text-decoration: none;
      margin: 0 10px;
      font-weight: bold;
    }

    .links a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="center-content">
    <!-- logo -->
    <div class="logo-container">
      <img src="style/logo.png" alt="شعار المساعد المالي الذكي" class="logo-img animate__animated animate__fadeInDown">
    </div>

    <!-- welcome text -->
    <div class="mt-4">
      <h1 class="animate__animated animate__fadeInDown">أهلاً بك في المساعد المالي الذكي</h1>
      <p class="animate__animated animate__fadeInUp animate__delay-1s">
        ساعد نفسك في إدارة ميزانيتك وتحقيق أهدافك المالية بسهولة.
      </p>
    </div>

    <!-- login/register links  -->
    <div class="links animate__animated animate__fadeInUp animate__delay-2s mt-3">
      <a href="login.php">تسجيل الدخول</a> |
      <a href="register.php">إنشاء حساب جديد</a>
    </div>
  </div>
</body>
</html>
