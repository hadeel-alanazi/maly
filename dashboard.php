<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$income = $_SESSION['income'];

$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear = $_GET['year'] ?? date('Y');

$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions WHERE user_id = ? GROUP BY category");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ?");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_spent = 0;
$expenses = [];
foreach ($transactions as $t) {
    $total_spent += $t['total'];
    $expenses[] = [
        'category' => $t['category'],
        'amount' => $t['total'],
        'percentage' => round(($t['total'] / $income) * 100, 1)
    ];
}

$remaining = $income - $total_spent;

$goals_with_progress = [];
foreach ($goals as $goal) {
    $progress = 0;
    if ($goal['target_amount'] > 0) {
        $progress = round(($goal['current_saved'] / $goal['target_amount']) * 100, 1);
    }

    $goals_with_progress[] = [
        'id' => $goal['id'],
        'title' => $goal['title'],
        'target_amount' => $goal['target_amount'],
        'current_saved' => $goal['current_saved'],
        'deadline' => $goal['deadline'],
        'progress' => $progress
    ];
}

// Prepare the prompt for GPT
$prompt = "أنت خبير مالي محترف. أمامك بيانات حقيقية من مستخدم حول دخله، مصروفاته، وأهدافه المالية.\n" .
          "لا تقم بإعادة عرض هذه البيانات، ولا تستخدم أي تنسيق خاص (مثل * أو # أو الإيموجي).\n\n" .
          "البيانات:\n\n" .
          "الدخل الشهري: $income ريال\n" .
          "تفاصيل المصاريف:\n";

foreach ($expenses as $e) {
    $prompt .= "- {$e['category']}: {$e['amount']} ريال ({$e['percentage']}%)\n";
}


$prompt = "أنت خبير مالي محترف. عندك بيانات حقيقية من مستخدم عن دخله، مصروفاته، وأهدافه المالية.\n" .
          "لا تعرض البيانات نفسها، ولا تستخدم تنسيقات مثل * أو # أو الإيموجي.\n\n" .

          "اللي نبيه منك هو تحليل ذكي ومباشر، مقسم إلى 3 أقسام واضحة كالتالي:\n\n" .

          "1. رأيك العام:\n" .
          "- تكلم بشكل عام عن التوازن بين الدخل والمصاريف.\n" .
          "- هل الوضع الحالي مطمئن أو لا؟ واشرح باختصار.\n\n" .

          "2. تحليل الأهداف والادخار:\n" .
          "- هل المصاريف بتأخر الوصول للأهداف؟\n" .
          "- الادخار الحالي كافي أو لا؟\n" .
          "- هل نحتاج نرفع الادخار أو نغير الأهداف؟\n\n" .

          "3. خطة مختصرة للتحسين:\n" .
          "- عطنا خطوات عملية واقعية تساعد نحقق الأهداف بدون ضغط.\n" .
          "- حدد مصروفات ممكن تخف.\n" .
          "- توقعك للشهر الجاي.\n\n" .

          "اكتب باللهجة العامية المهذبة، وخل الكلام مختصر (ما يتجاوز 500 حرف).\n" .
          "ابدأ مباشرة بدون مقدمات رسمية. خلك واقعي، ووضح الزبدة بدون تكرار.\n\n" .

          "البيانات:\n\n" .
          "الدخل الشهري: $income ريال\n" .
          "تفاصيل المصاريف:\n";

foreach ($expenses as $e) {
    $prompt .= "- {$e['category']}: {$e['amount']} ريال ({$e['percentage']}%)\n";
}

$prompt .= "\nالأهداف المالية:\n";
foreach ($goals_with_progress as $g) {
    $prompt .= "- {$g['title']}: الهدف = {$g['target_amount']} ريال، المدخر = {$g['current_saved']} ريال، التاريخ = {$g['deadline']}\n";
}


    $apiKey = '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'; // حط هنا مفتاح API الخاص بك

$data = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "أنت خبير مالي ذكي تحلل البيانات وتقدم توصيات مفصلة."],
        ["role" => "user", "content" => $prompt]
    ],
    "max_tokens" => 500,
    "temperature" => 0.7
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$ai_error = '';
$recommendation = '';


if (curl_errno($ch)) {
    $ai_error = curl_error($ch);
} else {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        $recommendation = $result['choices'][0]['message']['content'];
    } else {
        $ai_error = 'لم يتم الحصول على رد من الذكاء الاصطناعي.';
    } 
}

if (!empty($recommendation)) {
    // Split the text based on "1. ", "2. ", or "3. " followed by the end of the line
    $sections = preg_split('/\n[1-3]\.\s+/u', $recommendation, -1, PREG_SPLIT_NO_EMPTY);

    $section1 = isset($sections[0]) ? trim($sections[0]) : '';
    $section2 = isset($sections[1]) ? trim($sections[1]) : '';
    $section3 = isset($sections[2]) ? trim($sections[2]) : '';
}



curl_close($ch);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recommendation'])) {
    $s1 = $_POST['section1'] ?? '';
    $s2 = $_POST['section2'] ?? '';
    $s3 = $_POST['section3'] ?? '';

    //save the recommendation to the database
    try {
        $stmt = $pdo->prepare("INSERT INTO recommendations (user_id, section1, section2, section3) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $s1, $s2, $s3]);
        $success_message = "✅ تم حفظ التوصية بنجاح!";
    } catch (PDOException $e) {
        $ai_error = "❌ فشل الحفظ: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>لوحة التحكم - مالي</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Changa&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #17123a;
      color: #f7f7f8;
      font-family: 'Changa', sans-serif;
    }
    .card {
      background-color: #272953;
      color: #f7f7f8;
      border: none;
      border-radius: 16px;
    }
    .dashboard-header {
       background-color: #272953;
      color: #f7f7f8;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
      text-align: center;
    }
    .dashboard-header h2 {
      font-size: 1rem;
      font-weight: 600;
    }
    .dashboard-header form select,
    .dashboard-header form button {
      border-radius: 12px;
      border: 1px solid #ffffffff;
      background-color: #272953;
      color:#f7f7f8;
      
    }

    select.colorform,
    .w-auto {
  background-color: #272953;
  color: #ffffff;
  border: 1px solid #ffffff;
  padding-right: 2.5rem;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg fill='white' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
}

    .dashboard-header .btn-primary {
      background-color: #fff;
      color: #17123a;
      border-radius: 12px;
    }
    .dashboard-stats .card {
      text-align: center;
      padding: 20px;
      height: 100%;
    }
    .recommendation-card {
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
    }
    .recommendation-section-title {
      font-weight: bold;
      margin-bottom: 0.5rem;
      color: #fc9e8d;
    }
    .progress {
      background-color: #e9ecef;
      height: 26px;
    }
    .progress-bar {
      line-height: 26px;
    }

  .row.align-equal {
    display: flex;
    gap: 1rem;
  }
  .row.align-equal > div {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .row.align-equal > div > .card,
  .row.align-equal > div > .dashboard-stats {
    flex-grow: 1;
  }
  .dashboard-stats .card {
    padding: 0.5rem 1rem;
    text-align: center;
  }
  .btn{
    background-color: #f7f7f8;
      color:#17123a ;
      border-radius: 12px;
    
  }

  .btnbtn {
  border-radius: 12px;
  border: 1px solid #ffffffff;
  background-color: #272953;
  color: #f7f7f8;
  text-decoration: none;
  padding: 10px 20px; 
}


  .user-link i {
    font-size: 2rem;
    color: #fc9e8d;  
  }

  .username {
    font-size: 1.5rem;
    color: #fc9e8d;    
    font-weight: bold;
  }

  .user-link:hover i,
  .user-link:hover .username {
    color: #d0d4ff; 
  }

  
  </style>
</head>
<body>
<div class="container py-4">
 <div class="dashboard-header d-flex justify-content-between align-items-center px-3 py-2" >
 <!-- profile - on the right -->
  <div class="user-profile d-flex align-items-center gap-2">
    <a href="profile.php" class="text-decoration-none d-flex align-items-center gap-2 user-link text-white">
      <i class="bi bi-person-circle fs-4"></i>
      <div class="d-flex flex-column align-items-end">
        <h6 class="mb-0">مرحبًا بك</h6>
        <span class="username"><?= htmlspecialchars($user_name) ?></span>
      </div>
    </a>
  </div> 
 

  <!-- Filters - Center -->
  <div class="filters text-center">
    <p class="mb-2 text-white">نظرة شاملة على دخلك، مصاريفك، وأهدافك المالية</p>
    <form method="GET" class="d-flex justify-content-center align-items-center flex-wrap gap-2">
      <select name="month" class="form-select w-auto">
        <?php for ($m = 1; $m <= 12; $m++): $val = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
          <option value="<?= $val ?>" <?= $selectedMonth == $val ? 'selected' : '' ?>><?= $val ?></option>
        <?php endfor; ?>
      </select>
      <select name="year" class="form-select w-auto colorform">
        <?php $currentYear = date('Y'); for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
          <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button type="submit" class="btn btn-primary">تطبيق الفلتر</button>
    </form>
  </div>

  <!--logo - on the left -->
  <div class="logo">
    <img src="style/logo.png" alt="شعار الموقع" style="height: 100px;">
</div>



</div>

 <div class="row align-equal">
  <!-- Recommendations on the left -->
  <div class="col-md-4 d-flex flex-column">
    <div class="card p-3 recommendation-card flex-grow-1">
      <div class="card-header mb-2">توصية المساعد المالي الذكي</div>
      <div class="card-body">
        <div class="recommendation-section mb-2">
          <div class="recommendation-section-title">الرأي العام:</div>
          <p><?= nl2br(htmlspecialchars($section1)) ?></p>
        </div>
        <div class="recommendation-section mb-2">
          <div class="recommendation-section-title">تحليل الأهداف والادخار:</div>
          <p><?= nl2br(htmlspecialchars($section2)) ?></p>
        </div>
        <div class="recommendation-section">
          <div class="recommendation-section-title">الخطة المقترحة:</div>
          <p><?= nl2br(htmlspecialchars($section3)) ?></p>
        </div>
        <!-- chart -->
<div class="card mb-4">
  <div class="card-header text-center">مقارنة المصروفات: الوضع الحالي مقابل المثالي</div>
  <div class="card-body">
    <canvas id="comparisonChart" height="150"></canvas>
  </div>
</div>

      </div>
    </div>
  </div>

  <!-- Chart and goals on the right -->
  <div class="col-md-8 d-flex flex-column">
    <!-- Income and Expenses Statistics -->
    <div class="row g-2 mb-3 dashboard-stats">
      <div class="col-md-4">
        <div class="card py-2 px-3 text-center">
          <div class="small" >الدخل الشهري</div>
          <div class="fs-5 fw-bold" style="color:#fc9e8d;"><?= number_format($income, 2) ?> ريال</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card py-2 px-3 text-center">
          <div class="small">إجمالي المصاريف</div>
          <div class="fs-5 fw-bold" style="color:#fc9e8d;"><?= number_format($total_spent, 2) ?> ريال</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card py-2 px-3 text-center">
          <div class="small">المتبقي من الدخل</div>
          <div class="fs-5 fw-bold" style="color:#fc9e8d;"><?= number_format($remaining, 2) ?> ريال</div>
        </div>
      </div>
    </div>

    <!-- chart -->
    <div class="card mb-4 flex-grow-1">
      <div class="card-header text-center">تفاصيل المصاريف لشهر <?= $selectedMonth ?>/<?= $selectedYear ?></div>
      <div class="card-body">
        <canvas id="expensesChart" height="200"></canvas>
      </div>
    </div>

    <!-- goals -->
    <div class="card goal-section mb-4 flex-grow-1" style="overflow-y: auto;">
      <div class="card-header">الأهداف المالية</div>
      <div class="card-body">
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if (!empty($ai_error)): ?>
          <div class="alert alert-danger"><?= $ai_error ?></div>
        <?php endif; ?>

        <?php if (count($goals) === 0): ?>
          <p class="text-center text-muted">لا توجد أهداف مالية محددة بعد.</p>
        <?php else: ?>
          <?php foreach ($goals_with_progress as $goal): 
            $progressBarClass = 'progress-bar';
            if ($goal['progress'] < 50) {
              $progressBarClass .= ' bg-danger';
            } elseif ($goal['progress'] < 90) {
              $progressBarClass .= ' bg-warning text-dark';
            } else {
              $progressBarClass .= ' bg-success';
            }

            $progress = $goal['progress'];
            $current = number_format($goal['current_saved'], 2);
            $target = number_format($goal['target_amount'], 2);
          ?>
            <div class="mb-4 text-center">
              <h6><?= htmlspecialchars($goal['title']) ?></h6>
              <div class="d-flex justify-content-center align-items-center mb-2" style="gap: 12px;">
                <div class="progress" style="height: 26px; width: 60%; background-color: #e9ecef; position: relative;">
                  <div class="<?= $progressBarClass ?>" role="progressbar"
                       style="width: <?= $progress ?>%; position: relative; z-index: 2;">
                    <span class="d-block small px-2"
                          style="position: absolute; left: 5px; top: 3px;">
                      <?= $current ?> ريال
                    </span>
                  </div>
                </div>
                <span class="small" style="min-width: 80px; color:white;">
                  <?= $target ?> ريال
                </span>
              </div>
              <small class="d-block mb-2">الموعد النهائي: <?= htmlspecialchars($goal['deadline']) ?></small>
              <form action="update_goal.php" method="POST" class="d-flex justify-content-center align-items-center gap-2 flex-wrap mt-2">
                <input type="hidden" name="goal_id" value="<?= (int)$goal['id'] ?>">
                <input type="number" step="0.01" min="0.01" name="amount"
                       class="form-control form-control-sm text-center"
                       style="max-width: 120px;" placeholder="مبلغ" required>
                <button type="submit" class="btn btn-sm" style="background-color: #fc9e8d; color: white;">إضافة</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>




  <div class="text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
    <a href="add_transaction.php" class="btn ">
      <i data-lucide="plus-circle"></i> أضف مصروف
    </a>
    <a href="add_goal.php" class="btn">
      <i data-lucide="target"></i> أضف هدف مالي
    </a>
    <a href="logout.php" class="btnbtn ">تسجيل الخروج</a>
  </div>

  <footer class="text-center mt-5">
    أصالتنا لاقتصادنا لمستقبلنا – المساعد المالي الذكي ©️ 2025
  </footer>
</div>
<script>
  lucide.createIcons();
  const expensesData = {
    labels: <?= json_encode(array_column($transactions, 'category')) ?>,
    datasets: [{
      label: 'المصاريف (ريال)',
      data: <?= json_encode(array_map(fn($t) => floatval($t['total']), $transactions)) ?>,
      backgroundColor: ['#fc9e8d', '#ffb4a8', '#4987ddff', '#8380dc', '#a1a5ff', '#b39ddb', '#6c5ce7', '#a29bfe'],
      borderRadius: 10,
      borderWidth: 0
    }]
  };
  const config = {
    type: 'bar',
    data: expensesData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { color: '#ffffff' } },
        x: { ticks: { color: '#ffffff' } }
      },
      plugins: { legend: { display: false } }
    }
  };
  new Chart(document.getElementById('expensesChart').getContext('2d'), config);
</script>

<canvas id="comparisonChart" height="120"></canvas>
<script>
  const currentSpending = <?= floatval($total_spent) ?>;
  const idealSpending = <?= floatval($income * 0.7) ?>;

  const comparisonData = {
    labels: ['المصاريف'],
    datasets: [
      {
        label: 'الوضع الحالي',
        data: [currentSpending],
        backgroundColor: '#fc9e8d',
        borderRadius: 8
      },
      {
        label: 'الوضع المثالي',
        data: [idealSpending],
        backgroundColor: '#8380dc',
        borderRadius: 8
      }
    ]
  };

  const comparisonConfig = {
    type: 'bar',
    data: comparisonData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { color: '#ffffff' } },
        x: { ticks: { color: '#ffffff' } }
      },
      plugins: {
        legend: {
          labels: {
            color: '#ffffff'
          }
        }
      }
    }
  };

  new Chart(document.getElementById('comparisonChart').getContext('2d'), comparisonConfig);
</script>

</body>
</html>
