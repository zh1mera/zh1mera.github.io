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
$language = 'javascript';

// Get the puzzle for this level
function getPuzzleForLevel($level) {
    $filePath = "../../../../puzzles/beginner/javascript.txt";
    if ($level > 3) {
        $filePath = "../../../../puzzles/intermediate/javascript.txt";
    }
    if ($level > 6) {
        $filePath = "../../../../puzzles/professional/javascript.txt";
    }
    
    if (!file_exists($filePath)) {
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

        if (preg_match('/^(Level \d+:|\d+\.)/', $line)) {
            if (!empty($currentPuzzle)) {
                $puzzles[] = $currentPuzzle;
            }
            $currentPuzzle = ['level' => preg_replace('/[^0-9]/', '', $line)];
            $question = preg_replace('/^(Level \d+:|\d+\.)\s*/', '', $line);
            if ($question) {
                $currentPuzzle['question'] = $question;
            }
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
            return $puzzle;
        }
    }
    
    return $puzzles[0]; // Return first puzzle if no match found
}

$puzzle = getPuzzleForLevel($level);
$question = $puzzle ? $puzzle['question'] : 'No puzzle available for this level.';
$answer = $puzzle ? $puzzle['answer'] : '';
$_SESSION['current_answer'] = $answer;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Level <?php echo $level; ?> - BYTEMe</title>
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
            <div class="language-badge" style="background-color: #f7df1e;">JavaScript</div>
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
