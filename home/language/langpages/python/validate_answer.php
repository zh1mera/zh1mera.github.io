<?php
session_start();
require_once '../../../../db/db_connect.php';

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

error_log("POST data received: " . print_r($_POST, true));

if (!isset($_POST['code']) || !isset($_POST['level'])) {
    error_log("Missing parameters: code=" . isset($_POST['code']) . ", level=" . isset($_POST['level']));
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

error_log("Processing submission for level " . $_POST['level']);

$userAnswer = trim($_POST['code']);
$level = (int)$_POST['level'];

// Get the correct answer from puzzle files based on level
function getCorrectAnswer($level) {
    error_log("Getting answer for level: " . $level);
    
    $filePath = "../../../../puzzles/beginner/python.txt";
    if ($level > 3) {
        $filePath = "../../../../puzzles/intermediate/python.txt";
    }
    if ($level > 6) {
        $filePath = "../../../../puzzles/professional/python.txt";
    }
    
    if (!file_exists($filePath)) {
        error_log("Puzzle file not found: " . $filePath);
        return null;
    }
    error_log("Using puzzle file: " . $filePath);
    
    $content = file_get_contents($filePath);
    $puzzles = [];
    $currentPuzzle = [];
    
    foreach(explode("\n", $content) as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '// filepath:') === 0) {
            continue;
        }

        if (preg_match('/^(Level \d+:|\d+\.)/', $line)) {
            if (!empty($currentPuzzle)) {
                $puzzles[] = $currentPuzzle;
            }
            $currentPuzzle = ['level' => preg_replace('/[^0-9]/', '', $line)];
        } elseif (strpos($line, 'Q:') === 0) {
            $currentPuzzle['question'] = trim(substr($line, 2));
        } elseif (strpos($line, 'A:') === 0 || strpos($line, 'Answer:') === 0) {
            $currentPuzzle['answer'] = trim(strpos($line, 'A:') === 0 ? substr($line, 2) : substr($line, 7));
        }
    }
    
    if (!empty($currentPuzzle)) {
        $puzzles[] = $currentPuzzle;
    }
    
    foreach ($puzzles as $puzzle) {
        if ($puzzle['level'] == $level) {
            return $puzzle['answer'];
        }
    }
    
    return null;
}

$correctAnswer = getCorrectAnswer($level);

if ($correctAnswer === null) {
    echo json_encode(['success' => false, 'message' => 'Could not find puzzle for this level']);
    exit;
}

// Log answers for debugging
error_log("User answer: '" . $userAnswer . "'");
error_log("Correct answer: '" . $correctAnswer . "'");

// First, record the attempt regardless of correctness
try {
    // Get user's difficulty level
    $stmt = $pdo->prepare("SELECT difficulty_level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found");
    }

    // Compare user's answer with correct answer (ignoring whitespace and case)
    $cleanUserAnswer = preg_replace('/\s+/', ' ', trim(strtolower($userAnswer)));
    $cleanCorrectAnswer = preg_replace('/\s+/', ' ', trim(strtolower($correctAnswer)));

    $isCorrect = ($cleanUserAnswer === $cleanCorrectAnswer);

    // Record the attempt
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, language, difficulty, is_correct, created_at)            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([
        $_SESSION['user_id'],
        'python',
        $user['difficulty_level'],
        $isCorrect
    ]);

    if ($isCorrect) {
        echo json_encode([
            'success' => true,
            'message' => '✨ Great job! You solved the puzzle correctly! Moving to the next level...',
            'redirect' => 'index.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '🤔 Almost there! Double-check your answer and try again.'
        ]);
    }
} catch (Exception $e) {
    error_log("Error processing submission: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your submission. Please try again.'
    ]);
}
?>
