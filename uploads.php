<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $image_name = basename($_FILES['image']['name']);
    $target_dir = "uploads/";
    $target_path = $target_dir . $image_name;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $command = escapeshellcmd("python ocr_processor.py " . escapeshellarg($target_path));
        $ocr_output = shell_exec($command);
        $plate_number = trim($ocr_output);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Review & Save Info</title>
            <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
            <style>
                body {
                    background-color: #0a0a0a;
                    color: #00ffe1;
                    font-family: 'Orbitron', monospace;
                    padding: 20px;
                }

                .form-box {
                    background: rgba(0, 255, 255, 0.07);
                    border: 1px solid #00ffe1;
                    border-radius: 10px;
                    max-width: 600px;
                    margin: 40px auto;
                    padding: 24px;
                    box-shadow: 0 0 20px #00ffe1;
                }

                h2 {
                    text-align: center;
                    margin-bottom: 20px;
                }

                label {
                    display: block;
                    margin-top: 15px;
                    font-size: 14px;
                }

                input[type="text"] {
                    width: 100%;
                    padding: 10px;
                    margin-top: 5px;
                    background: #000;
                    color: #00ffe1;
                    border: 1px solid #00ffe1;
                    border-radius: 6px;
                }

                input[type="submit"] {
                    margin-top: 20px;
                    width: 100%;
                    padding: 12px;
                    background: transparent;
                    border: 2px solid #00ffe1;
                    color: #00ffe1;
                    border-radius: 8px;
                    font-size: 16px;
                    text-transform: uppercase;
                    cursor: pointer;
                }

                input[type="submit"]:hover {
                    background-color: #00ffe1;
                    color: #000;
                }

                .plate {
                    font-size: 20px;
                    border-right: 2px solid #00ffe1;
                    overflow: hidden;
                    white-space: nowrap;
                    width: 0;
                    animation: typewriter 2s steps(30, end) forwards;
                }

                @keyframes typewriter {
                    from { width: 0; }
                    to { width: 100%; }
                }

                img {
                    display: block;
                    max-width: 100%;
                    border: 2px solid #00ffe1;
                    margin: 20px auto 10px;
                    border-radius: 6px;
                }
            </style>
        </head>
        <body>

        <div class="form-box">
            <h2>Step 2: Review and Submit Details</h2>
            <form action="save.php" method="POST">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>

                <label>Phone Number:</label>
                <input type="text" name="phone_number" required>

                <label>ID Card Number:</label>
                <input type="text" name="id_card_number" required>

                <label>Detected Plate Number:</label>
                <div class="plate"><?= htmlspecialchars($plate_number) ?></div>
                <input type="hidden" name="plate_number" value="<?= htmlspecialchars($plate_number) ?>">

                <input type="hidden" name="plate_image_path" value="<?= htmlspecialchars($target_path) ?>">
                <input type="submit" value="Save to Database">
            </form>

            <img src="<?= htmlspecialchars($target_path) ?>" alt="Uploaded Plate Image">
        </div>

        </body>
        </html>
        <?php
    } else {
        echo "<p style='color:red'>❌ Failed to upload image.</p>";
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Upload License Plate</title>
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
        <style>
            body {
                background-color: #0a0a0a;
                color: #00ffe1;
                font-family: 'Orbitron', monospace;
                padding: 20px;
                text-align: center;
            }

            .form-box {
                background: rgba(0, 255, 255, 0.07);
                border: 1px solid #00ffe1;
                border-radius: 10px;
                max-width: 500px;
                margin: 60px auto;
                padding: 24px;
                box-shadow: 0 0 20px #00ffe1;
            }

            input[type="file"] {
                background: #000;
                color: #00ffe1;
                border: 1px solid #00ffe1;
                padding: 10px;
                width: 100%;
                margin-top: 10px;
                border-radius: 6px;
            }

            input[type="submit"] {
                margin-top: 20px;
                width: 100%;
                padding: 12px;
                background: transparent;
                border: 2px solid #00ffe1;
                color: #00ffe1;
                text-transform: uppercase;
                border-radius: 8px;
                cursor: pointer;
            }

            input[type="submit"]:hover {
                background-color: #00ffe1;
                color: #000;
            }
        </style>
    </head>
    <body>
        <div class="form-box">
            <h2>Step 1: Upload a License Plate Image</h2>
            <form action="uploads.php" method="POST" enctype="multipart/form-data">
                <label>Select Image:</label>
                <input type="file" name="image" accept="image/*" required><br>
                <input type="submit" value="Upload and Detect Plate">
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
