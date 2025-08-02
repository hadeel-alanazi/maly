<?php
session_start();
require 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$income = $_SESSION['income'] ?? 0;

$stmt = $pdo->prepare("SELECT id,email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recommendations history
$stmt = $pdo->prepare("SELECT id, section1, section2, section3, created_at FROM recommendations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الملف الشخصي</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <style>
   body {
  background-color: #17123a; 
  color: #f7f7f8; 
  font-family: 'Changa', sans-serif; 
}

.profile-card {
  max-width: 700px;
  margin: 50px auto;
  border-radius: 16px; 
  overflow: hidden;
  background-color: #272953;
  box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
}

.card-header {
  background-color: #272953;
  color: #f7f7f8;
  border-bottom: 1px solid #000000ff;
}

.card-body {
  padding: 30px;
  color: #f7f7f8;
}

.card-footer {
  background-color: #272953;
  border-top: 1px solid #000000ff;
  padding: 20px 30px;
}

.btn-purple {
  background-color: #ffffffff;
  color: #17123a; 
  border: none;
  border-radius: 12px;
}

.btn-purple:hover {
  background-color: #fc9e8d; 
}

.btn-outline-purple {
  border: 1px solid #ffffffff;
  color: #ffffffff;
  background-color: transparent;
  border-radius: 12px;
}

.btn-outline-purple:hover {
  background-color: #fc9e8d;
  color: #ffffffff;
}
table {
  color: #f7f7f8;
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.table-light th {
  background-color: #272953;
  color: #f7f7f8;
  border-bottom: 2px solid #fc9e8d;
  padding: 16px;
  font-weight: bold;
  text-align: left;
  text-transform: uppercase;
  letter-spacing: 1px;
}

table td {
  background-color: #1e1e3f;
  padding: 14px 16px;
  border-bottom: 1px solid #34376a;
}

table tr:hover td {
  background-color: #fc9e8d;
  transition: background-color 0.3s ease;
}

hr {
  border-top: 1px solid #fc9e8d;
  opacity: 0.2;
  margin: 20px 0;
}

.modal-content {
  background-color: #272953;
  color: #f7f7f8;
  border-radius: 16px;
}

.modal-header {
  background-color: #17123a;
  border-bottom: 1px solid #ffffffff;
}

.modal-footer {
  border-top: 1px solid #000000ff;
}

.text-muted {
  color: #ccc !important;
}

  </style>
</head>
<body>

<div class="container">
  <div class="card profile-card">
    <div class="card-header">
      <h4 style="color:#fc9e8d;"></i> الملف الشخصي</h4>
    </div>
    <div class="card-body">
      <p><strong>الاسم:</strong> <?= htmlspecialchars($user_name) ?></p>
      <p><strong>الدخل الشهري:</strong> <?= number_format($income) ?> ريال</p>
      <?php if ($userData): ?>
        <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($userData['email']) ?></p>
      <?php endif; ?>

      <hr>
      <h5 class="mt-4" style="color:#fc9e8d;"><i class="bi bi-lightbulb" style="color:#fc9e8d;"></i> سجل التوصيات</h5>

      <?php if (count($recommendations) > 0): ?>
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              
              <th>تاريخ التوصية</th>
              <th>عرض</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recommendations as $index => $rec): ?>
              <tr>
                <td><?= date('Y-m-d H:i', strtotime($rec['created_at'])) ?></td>
                <td>
                  <button class="btn-purple " data-bs-toggle="modal" data-bs-target="#recommendationModal<?= $rec['id'] ?>">
                    عرض
                  </button>
                </td>
              </tr>

              <!-- Modal -->
              <div class="modal fade" id="recommendationModal<?= $rec['id'] ?>" tabindex="-1" aria-labelledby="recModalLabel<?= $rec['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="recModalLabel<?= $rec['id'] ?>"><i class="bi bi-lightbulb-fill"></i> التوصية المالية</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                      <h6 class="fw-bold" style="color:#fc9e8d;">الرأي العام:</h6>
                      <p><?= nl2br(htmlspecialchars($rec['section1'])) ?></p>
                      <hr>
                      <h6 class="fw-bold" style="color:#fc9e8d;">تحليل الأهداف والادخار:</h6>
                      <p><?= nl2br(htmlspecialchars($rec['section2'])) ?></p>
                      <hr>
                      <h6 class="fw-bold" style="color:#fc9e8d;">الخطة المقترحة:</h6>
                      <p><?= nl2br(htmlspecialchars($rec['section3'])) ?></p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-purple" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                  </div>
                </div>
              </div>

            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-muted">لا توجد توصيات محفوظة بعد.</p>
      <?php endif; ?>

    </div>
    <div class="card-footer text-end">
      <a href="dashboard.php" class="btn btn-outline-purple"><i class="bi bi-arrow-left"></i> العودة للوحة التحكم</a>
      <a href="logout.php" class="btn btn-outline-purple"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
    </div>
  </div>
</div>

</body>
</html>
