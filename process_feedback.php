<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'tester') {
    header("Location: index.php");
    exit();
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback_text = $_POST['feedback_text'];

    $stmt = $pdo->prepare("INSERT INTO feedback_test (text) VALUES (?)");

    if ($stmt->execute([$feedback_text])) {
        header("Location: feedback.php?status=success");
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
} else {
    header("Location: index.php");
    exit();
}
?>