<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_id = (int)$_POST['goal_id'];
    $amount = (float)$_POST['amount'];

    if ($amount > 0) {
        // Fetch the current saved amount for the goal
        $stmt = $pdo->prepare("SELECT current_saved FROM goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$goal_id, $user_id]);
        $goal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($goal) {
            $new_saved = $goal['current_saved'] + $amount;

            // Update the saved amount
            $stmt = $pdo->prepare("UPDATE goals SET current_saved = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$new_saved, $goal_id, $user_id]);
        }
    }
}

header('Location: dashboard.php');
exit;
?>
