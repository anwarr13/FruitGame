<?php
session_start();
require_once 'db.php';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fruit_name = $_POST['fruit_name'];
    $upload_dir = 'images/';
    
    // Create images directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Handle file upload
    if (isset($_FILES['fruit_image'])) {
        $file = $_FILES['fruit_image'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check if it's an image
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_types)) {
            // Create safe filename
            $safe_filename = strtolower($fruit_name) . '.' . $file_extension;
            $target_path = $upload_dir . $safe_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Insert into database
                $image_path = $target_path;
                $stmt = $conn->prepare("INSERT INTO fruits (name, image_path) VALUES (?, ?)");
                $stmt->bind_param("ss", $fruit_name, $image_path);
                
                if ($stmt->execute()) {
                    $message = "Fruit image uploaded successfully!";
                } else {
                    $error = "Error inserting into database: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error = "Error moving uploaded file.";
            }
        } else {
            $error = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
        }
    }
}

// Get existing fruits
$fruits = [];
$result = $conn->query("SELECT * FROM fruits ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fruits[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fruit Quiz Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .fruit-list {
            margin-top: 20px;
        }
        .fruit-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
        }
        .fruit-item img {
            max-width: 100px;
            margin-right: 20px;
        }
        .back-link {
            margin-top: 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fruit Quiz Admin</h1>
        
        <?php if (isset($message)): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fruit_name">Fruit Name:</label>
                <input type="text" id="fruit_name" name="fruit_name" required>
            </div>
            
            <div class="form-group">
                <label for="fruit_image">Fruit Image:</label>
                <input type="file" id="fruit_image" name="fruit_image" required accept="image/*">
            </div>
            
            <button type="submit">Upload Fruit</button>
        </form>
        
        <div class="fruit-list">
            <h2>Existing Fruits</h2>
            <?php foreach ($fruits as $fruit): ?>
            <div class="fruit-item">
                <img src="<?php echo htmlspecialchars($fruit['image_path']); ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                <div>
                    <strong><?php echo htmlspecialchars($fruit['name']); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <a href="index.php" class="back-link">Back to Game</a>
    </div>
</body>
</html>