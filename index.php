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
$result = $conn->query("SELECT username, score, duration_seconds as time, DATE_FORMAT(date_played, '%m/%d/%Y') as date_played FROM players ORDER BY score DESC, duration_seconds ASC LIMIT 100");
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
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #45a049;
            --accent-color: #f4511e;
            --background-color: #f9f9f9;
            --text-color: #333;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .game-container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1 );
            margin-top: 30px;
            transition: transform 0.2s;
        }

        .game-container:hover {
            transform: translateY(-5px);
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px auto;
            max-width: 600px;
        }

        .choice-btn {
            padding: 15px 25px;
            font-size: 18px;
            cursor: pointer;
            background-color: white;
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            transition: all 0.3s ease;
            color: var(--primary-color);
            font-weight: 500;
        }

        .choice-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.02);
        }

        .score {
            font-size: 28px;
            margin: 25px 0;
            color: var(--primary-color);
            font-weight: bold;
        }

        .question-count {
            font-size: 20px;
            margin-bottom: 25px;
            color: #666;
            font-weight: 500;
        }

        .high-scores {
            margin-top: 30px;
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f5f5f5;
        }

        input[type="text"] {
            padding: 12px 20px;
            margin: 15px 0;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            width: 250px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button, .button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            font-weight: 500;
        }

        button:hover, .button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
            width: calc(<?php echo $_SESSION['question_count']; ?> * 10%);
        }

        .creator-credit {
            margin-top: 30px;
            font-style: italic;
            color: #666;
            font-size: 14px;
        }

        .game-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .game-title i {
            font-size: 2em;
            color: var(--primary-color);
        }

        .result-message {
            font-size: 24px;
            margin: 20px 0;
            padding: 15px;
            border-radius: 10px;
            background-color: #e8f5e9;
            color: var(--primary-color);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <!-- Start Screen -->
        <div class="game-container">
            <div class="game-title">
                <i class="fas fa-apple-alt"></i>
                <h1>Fruit Quiz Game</h1>
                <i class="fas fa-lemon"></i>
            </div>
            <p>Test your knowledge of fruits in this fun and challenging quiz!</p>
            <form method="POST" class="start-form">
                <input type="text" name="username" placeholder="Enter your name" required>
                <br>
                <button type="submit"><i class="fas fa-play"></i> Start Game</button>
            </form>
        </div>
        <p class="creator-credit">Developed by: Anwarr Jervis</p>
        <p class="creator-credit"> BSIT - 2B</p>
    <?php elseif (isset($_GET['show_results'])): ?>
        <!-- Results Screen -->
        <div class="game-container">
            <h1><i class="fas fa-trophy"></i> Game Over!</h1>
            <div class="result-message">
                Your final score: <?php echo isset($_SESSION['final_score']) ? $_SESSION['final_score'] : 0; ?>/10
            </div>
            <h2><i class="fas fa-star"></i> High Scores</h2>
            <table>
                <tr>
                    <th><i class="fas fa-calendar"></i>   Date Played</th>
                    <th><i class="fas fa-user"></i> Username</th>
                    <th><i class="fas fa-star"></i> Score</th>
                    <th><i class="fas fa-clock"></i> Time</th>
                </tr>
                <?php foreach ($highScores as $score): ?>
                <tr>
                    <td><?php echo htmlspecialchars($score->date_played); ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?> seconds</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <a href="index.php" class="button"><i class="fas fa-redo"></i> Play Again</a>
        </div>
    <?php else: ?>
        <!-- Game Screen -->
        <div class="game-container">
            <div class="score"><i class="fas fa-star"></i> Score: <?php echo $_SESSION['score']; ?></div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
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
            
            // Get three random incorrect answers
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
            
            // para di mag balik2 ang mga fruits function
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
            
            // combine and shuffle choices function
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