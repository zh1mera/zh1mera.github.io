<?php
// db/progress_functions.php

function getUserProgress($user_id) {
    global $pdo;

    $progress = [
        'language' => [],
        'difficulty' => [],
        'recent' => [],
        'streak' => 0
    ];

    try {        // Query to get detailed language progress with attempts and success rate
        $stmt = $pdo->prepare("
            SELECT 
                language,
                COUNT(*) as total_attempts,
                SUM(is_correct) as correct_attempts,
                ROUND(100.0 * SUM(is_correct) / COUNT(*), 2) AS success_rate,
                MAX(created_at) as last_attempt,
                MIN(CASE WHEN is_correct = 1 THEN attempts_before_success ELSE NULL END) as min_attempts_to_success
            FROM (
                SELECT 
                    *,
                    (SELECT COUNT(*) 
                     FROM progress p2 
                     WHERE p2.user_id = p1.user_id 
                     AND p2.language = p1.language 
                     AND p2.created_at <= p1.created_at) as attempts_before_success
                FROM progress p1
                WHERE user_id = ?
            ) as attempts_data
            GROUP BY language
            ORDER BY last_attempt DESC
        ");
        $stmt->execute([$user_id]);
        $progress['language'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query to get difficulty progress
        $stmt = $pdo->prepare("
            SELECT difficulty, 
                   COUNT(*) as total_attempts,
                   SUM(is_correct) as correct_attempts,
                   ROUND(100.0 * SUM(is_correct) / COUNT(*), 2) AS success_rate
            FROM progress
            WHERE user_id = ?
            GROUP BY difficulty
        ");
        $stmt->execute([$user_id]);
        $progress['difficulty'] = $stmt->fetchAll(PDO::FETCH_ASSOC);        // Get recent activity (last 10) with attempt counts
        $stmt = $pdo->prepare("
            SELECT 
                language, 
                difficulty, 
                is_correct, 
                created_at,
                (SELECT COUNT(*) FROM progress p2 
                 WHERE p2.user_id = p1.user_id 
                 AND p2.language = p1.language
                 AND p2.created_at <= p1.created_at) as attempt_number
            FROM progress p1
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $progress['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate streak
        if (!empty($progress['recent'])) {
            $streak = 0;
            $lastDate = null;
            $today = new DateTime('today');
            
            foreach ($progress['recent'] as $activity) {
                $activityDate = new DateTime($activity['created_at']);
                $activityDate->setTime(0, 0, 0);
                
                if ($lastDate === null) {
                    if ($activityDate >= $today) {
                        $streak = 1;
                    }
                    $lastDate = $activityDate;
                } else {
                    $diff = $lastDate->diff($activityDate);
                    if ($diff->days == 1) {
                        $streak++;
                        $lastDate = $activityDate;
                    } else {
                        break;
                    }
                }
            }
            $progress['streak'] = $streak;
        }

    } catch (PDOException $e) {
        error_log("Error fetching progress: " . $e->getMessage());
    }

    return $progress;
}

// Function to get current level for a language
function getCurrentLevel($user_id, $language) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts,
                   SUM(is_correct) as correct_answers
            FROM progress
            WHERE user_id = ? AND language = ?
        ");
        $stmt->execute([$user_id, $language]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no attempts yet, start at level 1
        if ($result['attempts'] == 0) {
            return 1;
        }
        
        // Calculate level based on correct answers
        // Each level requires 3 correct answers to unlock the next
        $level = floor($result['correct_answers'] / 3) + 1;
        
        // Cap at level 10
        return min($level, 10);
        
    } catch (PDOException $e) {
        error_log("Error getting current level: " . $e->getMessage());
        return 1;
    }
}
?>

