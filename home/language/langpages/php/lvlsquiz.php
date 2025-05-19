<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../../login.php");
    exit();
}

require_once '../../../../db/db_connect.php';
require_once '../../../../db/progress_functions.php';

// Get level from URL parameter
$level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
$language = 'php';

// Get the puzzle for this level
function getPuzzleForLevel($level) {
    // Determine which file to use based on level
    $originalLevel = $level; // Store original level for debugging
    $adjustedLevel = $level; // This will be the adjusted level number
    
    if ($level <= 3) {
        $filePath = "../../../../puzzles/beginner/php.txt";
        // Level stays the same for beginner (1-3)
    } elseif ($level <= 6) {
        $filePath = "../../../../puzzles/intermediate/php.txt";
        // Adjust level number for intermediate file (4-6 becomes 1-3)
        $adjustedLevel = $level - 3;
    } else {
        $filePath = "../../../../puzzles/professional/php.txt";
        // Adjust level number for professional file (7-10 becomes 1-4)
        $adjustedLevel = $level - 6;
    }
    
    error_log("Original level: " . $originalLevel . ", Adjusted level: " . $adjustedLevel . ", File: " . $filePath);
    
    if (!file_exists($filePath)) {
        error_log("Puzzle file not found: " . $filePath);
        return null;
    }
    
    $content = file_get_contents($filePath);
    $puzzles = [];
    $currentPuzzle = [];
      foreach(explode("\n", $content) as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '// filepath:') === 0) {
            continue;
        }
        
        // If line starts with a number
        if (preg_match('/^\d+/', $line)) {
            if (!empty($currentPuzzle)) {
                $puzzles[] = $currentPuzzle;
            }
            $levelNum = preg_replace('/[^0-9]/', '', $line);
            $currentPuzzle = ['level' => $levelNum];
            // Extract question (everything after the number and any separators)
            $question = preg_replace('/^\d+\.?\s*/', '', $line);
            if ($question) {
                $currentPuzzle['question'] = $question;
            }
        } elseif (strpos($line, 'Q:') === 0) {
            $currentPuzzle['question'] = trim(substr($line, 2));
        } elseif (strpos($line, 'A:') === 0 || strpos($line, 'Answer:') === 0) {
            $currentPuzzle['answer'] = trim(strpos($line, 'A:') === 0 ? substr($line, 2) : substr($line, 7));
        }
    }
      if (!empty($currentPuzzle) && isset($currentPuzzle['level']) && isset($currentPuzzle['question'])) {
        $puzzles[] = $currentPuzzle;
    }
      error_log("Puzzles array: " . print_r($puzzles, true));
    
    // Look for puzzle with matching adjusted level
    foreach ($puzzles as $puzzle) {
        if (isset($puzzle['level']) && $puzzle['level'] == $adjustedLevel) {
            error_log("Found matching puzzle for level " . $adjustedLevel);
            return $puzzle;
        }
    }
    
    error_log("No puzzle found for level " . $adjustedLevel);
    return null;
}

$puzzle = getPuzzleForLevel($level);

// Debug information
error_log("Level requested: " . $level);
error_log("Puzzle found: " . print_r($puzzle, true));

if (!$puzzle || !isset($puzzle['question']) || !isset($puzzle['level'])) {
    $puzzle = [
        'level' => $level,
        'question' => 'No puzzle available for this level.',
        'answer' => ''
    ];
}

$question = $puzzle['question'];
$answer = isset($puzzle['answer']) ? $puzzle['answer'] : '';
$_SESSION['current_answer'] = $answer;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Level <?php echo $level; ?> - BYTEMe</title>
    <link rel="stylesheet" href="../../../../assets/css/style.css">
    <link rel="stylesheet" href="../../../css/home.css">
    <link rel="stylesheet" href="../../../challenges/css/challenges.css">
    <style>
        .challenge-content {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        textarea {
            width: 100%;
            min-height: 200px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
            resize: vertical;
        }
        .button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .submit-btn, .reset-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            min-width: 100px;
        }
        .submit-btn {
            background: #4CAF50;
            color: white;
        }
        .reset-btn {
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .result-container.success {
            background: #e8f5e9;
            color: #2e7d32;
            display: block;
        }
        .result-container.error {
            background: #ffebee;
            color: #c62828;
            display: block;
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <button class="nav-logo" onclick="window.location.href='../../../index.php'">
            BYTEMe
        </button>
        <div class="nav-links">
            <a href="../../../challenges/index.php">Daily Challenges</a>
            <a href="../../index.php" class="active">Languages</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="../../../../admin.php">Admin</a>
            <?php endif; ?>
        </div>        <div class="nav-profile">
            <a href="../../../profile.php" class="nav-btn">Profile</a>
            <a href="../../../../logout.php" class="nav-btn logout">Logout</a>
        </div>
    </nav>

    <main class="challenge-container">
        <div class="challenge-header">
            <button onclick="window.location.href='index.php'" style="padding: 8px 15px; margin-bottom: 10px; border: none; border-radius: 5px; background: #f0f0f0; cursor: pointer;">← Back</button>
            <h1>Level <?php echo $level; ?></h1>
            <div class="language-badge" style="background-color: #777BB3;">PHP</div>
        </div>
        
        <div class="challenge-description">
            <p><?php echo htmlspecialchars($question); ?></p>
        </div>

        <div class="challenge-content">
            <form id="answerForm" onsubmit="return false;">
                <textarea id="codeInput" name="code" placeholder="Write your code here..."></textarea>
                <div class="button-container">
                    <button type="submit" id="submitBtn" class="submit-btn">Submit</button>
                    <button type="button" id="resetBtn" class="reset-btn">Reset</button>
                </div>
            </form>
        </div>

        <div id="result" class="result-container"></div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var codeInput = document.getElementById('codeInput');
            var answerForm = document.getElementById('answerForm');
            
            document.getElementById('resetBtn').addEventListener('click', function() {
                codeInput.value = '';
                document.getElementById('result').innerHTML = '';
                document.getElementById('result').className = 'result-container';
            });

            answerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const code = codeInput.value;
                if (!code.trim()) {
                    const resultDiv = document.getElementById('result');
                    resultDiv.innerHTML = 'Please enter your answer.';
                    resultDiv.className = 'result-container error';
                    return;
                }
                  fetch('validate_answer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'code=' + encodeURIComponent(code) + '&level=<?php echo $level; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = data.message;
                    resultDiv.className = 'result-container ' + (data.success ? 'success' : 'error');
                    
                    if (data.success) {
                        // Disable input and buttons on success
                        codeInput.disabled = true;
                        submitBtn.disabled = true;
                        document.getElementById('resetBtn').disabled = true;
                        
                        // Show success animation
                        resultDiv.style.animation = 'pulse 1s';
                        
                        // Redirect after showing success message
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    } else {
                        // Re-enable submit button and shake the result div
                        submitBtn.disabled = false;
                        resultDiv.style.animation = 'shake 0.5s';
                    }
                })
                .catch(() => {
                    resultDiv.innerHTML = '⚠️ Oops! Something went wrong. Please try again.';
                    resultDiv.className = 'result-container error';
                    submitBtn.disabled = false;
                    resultDiv.style.animation = 'shake 0.5s';
                });
            });
        });
    </script>
</body>
</html>
