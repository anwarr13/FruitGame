<?php
session_start();
require_once 'db.php';

// 
if (!isset($_SESSION['game_started']) && isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username']; // ngan
    $_SESSION['score'] = 0; // iyang score
    $_SESSION['question_count'] = 0; // pila ka pangutana 
    $_SESSION['game_started'] = true; // mag sugod ug duwa
    $_SESSION['time_started'] = date('Y-m-d H:i:s'); // kung unsa siya oras ga sugod ug duwa
    $_SESSION['used_fruits'] = []; // para di balik2 ang mga fruits
}

// Game completion logic
function endGame($conn) {
    $timeEnded = date('Y-m-d H:i:s');
    $timeStarted = $_SESSION['time_started'];
    $duration = strtotime($timeEnded) - strtotime($timeStarted);
    $finalScore = $_SESSION['score']; // Store the final score
    $datePlayed = date('Y-m-d'); // Get current date
    
    $stmt = $conn->prepare("INSERT INTO players (username, score, time_started, time_ended, duration_seconds, date_played) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $_SESSION['username'], $_SESSION['score'], $_SESSION['time_started'], $timeEnded, $duration, $datePlayed);
    $stmt->execute();
    $stmt->close();
    
    session_destroy();
    session_start(); // Start a new session
    $_SESSION['final_score'] = $finalScore; // Save the final score in the new session
}

// Handle answer submission
if (isset($_POST['answer'])) {
    if ($_POST['answer'] == $_SESSION['correct_fruit']) {
        $_SESSION['score']++;
    }
    $_SESSION['question_count']++;
    
    if ($_SESSION['question_count'] >= 10) {
        endGame($conn);
        header('Location: index.php?show_results=1');
        exit();
    }
}

// Get high scores
$highScores = [];
$result = $conn->query("SELECT username, score, duration_seconds as time, DATE_FORMAT(date_played, '%m/%d/%Y') as date_played FROM players ORDER BY score DESC, duration_seconds ASC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_object()) {
        $highScores[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fruit Quiz Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .game-container {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .choice-btn {
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .choice-btn:hover {
            background-color: #e0e0e0;
        }
        .score {
            font-size: 24px;
            margin: 20px 0;
        }
        .high-scores {
            margin-top: 20px;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .question-count {
            font-size: 18px;
            margin-bottom: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <!-- Start Screen -->
        <div class="game-container">
            <h1>Welcome to Fruit Quiz Game</h1>
            <br>
            <br>
            <br>
            <br>
            <form method="POST">
                <input type="text" name="username" placeholder="Enter your name" required>
                <button type="submit">Start Game</button>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
            </form>
        </div>
        <br>
        <br>

        <em>Created by: Anwar Gwapo</em>

    <?php elseif (isset($_GET['show_results'])): ?>
        <!-- Results Screen -->
        <div class="game-container">
            <h1>Game Over!</h1>
            <p>Your final score: <?php echo isset($_SESSION['final_score']) ? $_SESSION['final_score'] : 0; ?>/10</p>
            <h2>High Scores</h2>
            <table>
                <tr>
                    <th>Date Played</th>
                    <th>Username</th>
                    <th>Score</th>
                    <th>Time (seconds)</th>
                </tr>
                <?php foreach ($highScores as $score): ?>
                <tr>
                    <td><?php echo htmlspecialchars($score->date_played); ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <br>
            <a href="index.php"><button>Play Again</button></a>
        </div>
    <?php else: ?>
        <!-- Game Screen -->
        <div class="game-container">
            <div class="score">Score: <?php echo $_SESSION['score']; ?></div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            <?php
            // Get all unused fruits first
            $usedFruitPlaceholders = str_repeat('?,', count($_SESSION['used_fruits']));
            $usedFruitPlaceholders = rtrim($usedFruitPlaceholders, ',');
            
            if (empty($_SESSION['used_fruits'])) {
                $query = "SELECT * FROM fruits ORDER BY RAND() LIMIT 1";
                $stmt = $conn->prepare($query);
            } else {
                $query = "SELECT * FROM fruits WHERE id NOT IN ($usedFruitPlaceholders) ORDER BY RAND() LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param(str_repeat('i', count($_SESSION['used_fruits'])), ...$_SESSION['used_fruits']);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $currentFruit = $result->fetch_object();
            $stmt->close();
            
            // Add current fruit to used fruits array
            $_SESSION['used_fruits'][] = $currentFruit->id;
            $_SESSION['correct_fruit'] = $currentFruit->name;
            
            // Get three random incorrect choices from unused fruits
            $usedFruitPlaceholders = str_repeat('?,', count($_SESSION['used_fruits']));
            $usedFruitPlaceholders = rtrim($usedFruitPlaceholders, ',');
            
            $query = "SELECT name FROM fruits WHERE id NOT IN ($usedFruitPlaceholders) ORDER BY RAND() LIMIT 3";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($_SESSION['used_fruits'])), ...$_SESSION['used_fruits']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $wrongChoices = [];
            while ($row = $result->fetch_object()) {
                $wrongChoices[] = $row->name;
            }
            $stmt->close();
            
            // If we don't have enough unused fruits for wrong choices, use used fruits
            if (count($wrongChoices) < 3) {
                $query = "SELECT name FROM fruits WHERE name != ? ORDER BY RAND() LIMIT ?";
                $stmt = $conn->prepare($query);
                $limit = 3 - count($wrongChoices);
                $stmt->bind_param("si", $currentFruit->name, $limit);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_object()) {
                    $wrongChoices[] = $row->name;
                }
                $stmt->close();
            }
            
            // Combine and shuffle choices
            $choices = array_merge([$currentFruit->name], $wrongChoices);
            shuffle($choices);
            ?>
            
            <img src="<?php echo htmlspecialchars($currentFruit->image_path); ?>" alt="Fruit" style="max-width: 300px;">
            
            <form method="POST" class="choices">
                <?php foreach ($choices as $choice): ?>
                <button type="submit" name="answer" value="<?php echo htmlspecialchars($choice); ?>" class="choice-btn">
                    <?php echo htmlspecialchars($choice); ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>