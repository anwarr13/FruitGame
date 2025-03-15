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
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --accent-color: #e74c3c;
            --background-color: #ecf0f1;
            --text-color: #2c3e50;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
            text-align: center;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .game-container {
            background-color: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin: 20px auto;
            transition: all 0.3s ease;
            max-width: 800px;
            position: relative;
            overflow: hidden;
        }

        .game-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .game-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        h1 {
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 2.8em;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }

        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px auto;
            max-width: 700px;
            padding: 0 20px;
        }

        .choice-btn {
            padding: 20px 30px;
            font-size: 18px;
            cursor: pointer;
            background-color: white;
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            transition: all 0.3s ease;
            color: var(--primary-color);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .choice-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
            z-index: 0;
        }

        .choice-btn:hover {
            color: white;
            transform: scale(1.02);
            box-shadow: var(--shadow);
        }

        .choice-btn:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .choice-btn span {
            position: relative;
            z-index: 1;
        }

        .score {
            font-size: 32px;
            margin: 25px 0;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .score i {
            color: #f1c40f;
            animation: pulse 2s infinite;
        }

        .question-count {
            font-size: 22px;
            margin-bottom: 25px;
            color: #7f8c8d;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .progress-container {
            margin: 30px 0;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background-color: #eee;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress {
            height: 100%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            transition: width 0.5s ease;
            width: calc(<?php echo $_SESSION['question_count']; ?> * 10%);
            border-radius: 10px;
            position: relative;
        }

        .progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255,255,255,0.1) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255,255,255,0.1) 50%,
                rgba(255,255,255,0.1) 75%,
                transparent 75%
            );
            background-size: 30px 30px;
            animation: progress-animation 1s linear infinite;
        }

        @keyframes progress-animation {
            0% { background-position: 0 0; }
            100% { background-position: 30px 0; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 30px 0;
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        th {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        input[type="text"] {
            padding: 15px 25px;
            margin: 20px 0;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-size: 18px;
            width: 300px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
            background-color: white;
        }

        button, .button {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        button::before, .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }

        button:hover::before, .button:hover::before {
            left: 100%;
        }

        button:hover, .button:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .creator-credit {
            margin-top: 40px;
            font-style: italic;
            color: #95a5a6;
            font-size: 16px;
            text-shadow: 1px 1px 1px rgba(255, 255, 255, 0.5);
        }

        .game-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .game-title i {
            font-size: 2.5em;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .result-message {
            font-size: 28px;
            margin: 30px 0;
            padding: 25px;
            border-radius: 15px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: var(--primary-color);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .result-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                45deg,
                rgba(255,255,255,0.1) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255,255,255,0.1) 50%,
                rgba(255,255,255,0.1) 75%,
                transparent 75%
            );
            background-size: 20px 20px;
            animation: shine 1s linear infinite;
        }

        @keyframes shine {
            0% { background-position: 0 0; }
            100% { background-position: 20px 0; }
        }

        .welcome-text {
            font-size: 20px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .start-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
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
            <p class="welcome-text">Challenge yourself with our exciting fruit quiz! Test your knowledge of different fruits and compete for the highest score. Are you ready to begin?</p>
            <form method="POST" class="start-form">
                <input type="text" name="username" placeholder="Enter your name" required>
                <button type="submit"><i class="fas fa-play"></i> Start Game</button>
            </form>
        </div>
        <p class="creator-credit">Designed with <i class="fas fa-heart" style="color: #e74c3c;"></i> by Anwar Gwapo</p>

    <?php elseif (isset($_GET['show_results'])): ?>
        <!-- Results Screen -->
        <div class="game-container">
            <h1><i class="fas fa-trophy"></i> Game Over!</h1>
            <div class="result-message">
                <i class="fas fa-star"></i> Your final score: <?php echo isset($_SESSION['final_score']) ? $_SESSION['final_score'] : 0; ?>/10
            </div>
            <h2><i class="fas fa-crown"></i> High Scores</h2>
            <table>
                <tr>
                    <th><i class="fas fa-calendar"></i> Date</th>
                    <th><i class="fas fa-user"></i> Player</th>
                    <th><i class="fas fa-star"></i> Score</th>
                    <th><i class="fas fa-clock"></i> Time</th>
                </tr>
                <?php foreach ($highScores as $score): ?>
                <tr>
                    <td><?php echo htmlspecialchars($score->date_played); ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?>s</td>
                </tr>
                <?php endforeach; ?>
            </table>
            <a href="index.php" class="button"><i class="fas fa-redo"></i> Play Again</a>
        </div>

    <?php else: ?>
        <!-- Game Screen -->
        <div class="game-container">
            <div class="score">
                <i class="fas fa-star"></i>
                Score: <?php echo $_SESSION['score']; ?>
            </div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
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