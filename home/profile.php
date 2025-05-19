<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../db/db_connect.php';
require_once '../db/progress_functions.php';

// Fetch user data and progress
try {
    $stmt = $pdo->prepare("SELECT username, email, difficulty_level, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Get user progress data
    $progressData = getUserProgress($_SESSION['user_id']);    // Calculate total challenges completed and languages explored
    $totalChallenges = 0;
    $languagesExplored = [];

    foreach ($progressData['language'] as $lang) {
        $totalChallenges += $lang['correct_attempts']; // Only count successful attempts
        $languagesExplored[] = $lang['language'];
    }

    $languagesCount = count(array_unique($languagesExplored));
    $currentStreak = $progressData['streak'];

    // Define difficulty level descriptions
    $difficultyDescriptions = [
        'beginner' => 'Perfect for those just starting their coding journey.',
        'intermediate' => 'For developers with basic programming knowledge.',
        'professional' => 'For experienced developers seeking advanced challenges.'
    ];    // Define available programming languages
    $languages = [
        'Python' => [
            'description' => 'A versatile, beginner-friendly language perfect for automation and data science.',
            'color' => '#3572A5'
        ],
        'JavaScript' => [
            'description' => 'The language of the web, essential for front-end and back-end development.',
            'color' => '#F7DF1E'
        ],
        'Java' => [
            'description' => 'A powerful, object-oriented language used in enterprise and Android development.',
            'color' => '#B07219'
        ],
        'PHP' => [
            'description' => 'A popular server-side scripting language, widely used in web development.',
            'color' => '#4F5D95'
        ]
    ];
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Fetch user progress data
$progressData = getUserProgress($_SESSION['user_id']);

// Get total challenges completed
$totalChallenges = 0;
$languagesExplored = [];

foreach ($progressData['language'] as $lang) {
    $totalChallenges += $lang['total_attempts'];
    $languagesExplored[] = $lang['language'];
}

$languagesCount = count(array_unique($languagesExplored));

// Get current streak (implement streak logic here)
$currentStreak = 0;  // Will be updated when streak tracking is implemented
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>Profile - BYTEMe</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/profile.css">    <script src="../assets/js/main.js"></script>
    <script src="js/home.js" defer></script>
</head>
<body>
    <nav class="main-nav">
        <button class="nav-logo" onclick="window.location.href='index.php'">BYTEMe</button>
        <div class="nav-links">
            <a href="challenges/index.php">Daily Challenges</a>
            <a href="language/index.php">Languages</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="../admin.php">Admin</a>
            <?php endif; ?>
        </div>        <div class="nav-profile">
            <a href="profile.php" class="nav-btn active">Profile</a>
            <a href="../logout.php" class="nav-btn logout">Logout</a>
        </div>
    </nav>    <main class="profile-container">
        <section class="profile-section user-info" style="margin-top: 1100px;">
            <div class="info-card">
                <h2>Account Information</h2>
                <div class="user-details">
                    <h1 class="username"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="email"><i class="email-icon"></i><?php echo htmlspecialchars($user['email']); ?></p>                    <div class="level-info">
                        <span class="level-badge"><?php echo ucfirst(htmlspecialchars($user['difficulty_level'])); ?></span>
                        <p class="level-description"><?php echo htmlspecialchars($difficultyDescriptions[$user['difficulty_level']]); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-section languages">
            <h2>Language Proficiency</h2>
            <div class="languages-grid">                <?php foreach ($languages as $name => $info): 
                    // Find progress percentage for this language
                    $progress = 0;
                    $attempts = 0;
                    $correct = 0;
                    foreach ($progressData['language'] as $lang) {
                        if (strtolower($lang['language']) === strtolower($name)) {
                            $attempts = $lang['total_attempts'];
                            $correct = $lang['correct_attempts'];
                            $progress = $attempts > 0 ? round(($correct / $attempts) * 100) : 0;
                            break;
                        }
                    }
                ?>
                <div class="language-card">                    <div class="language-header" style="background-color: <?php echo htmlspecialchars($info['color']); ?>">
                        <h3><?php echo htmlspecialchars($name); ?></h3>
                        <span class="progress-text"><?php echo $progress; ?>%</span>
                    </div>                        <div class="language-info">
                        <p><?php echo htmlspecialchars($info['description']); ?></p>
                        <div class="progress-stats">
                            <span class="stat">✓ Completed: <?php echo $correct; ?></span>
                            <span class="stat">🎯 Attempts: <?php echo $attempts; ?></span>
                            <span class="stat">⚡ Success Rate: <?php echo $progress; ?>%</span>
                            <?php if (isset($lang['min_attempts_to_success'])): ?>
                            <span class="stat">🏆 Best Run: <?php echo $lang['min_attempts_to_success']; ?> attempts</span>
                            <?php endif; ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <?php if ($attempts > 0): ?>
                        <div class="last-attempt">
                            Last attempt: <?php echo date('M j, Y', strtotime($lang['last_attempt'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="profile-section">
            <h2>Your Progress</h2>
            <div class="progress-stats">
                <div class="stat-card">
                    <h3>Challenges Completed</h3>
                    <p class="stat-number"><?php echo $totalChallenges; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Languages Explored</h3>
                    <p class="stat-number"><?php echo $languagesCount; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Current Streak</h3>
                    <p class="stat-number"><?php echo $currentStreak; ?> days</p>
                </div>            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>BYTEMe</p>
    </footer>
</body>
</html>
