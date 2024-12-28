<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birdfeeder Video Uploader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        h1, h2 {
            color: #2E8B57;
        }
        form {
            margin-bottom: 30px;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
        }
        .frame {
            margin: 10px;
            text-align: center;
        }
        .frame img {
            width: 200px;
            height: auto;
            border: 2px solid #ccc;
            border-radius: 5px;
        }
        .caption {
            margin-top: 5px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>Upload Birdfeeder Video 1.01</h1>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="video" accept="video/mp4" required>
        <button type="submit">Upload Video</button>
    </form>

    <h2>Gallery</h2>
    <div class="gallery">
        <?php
        $framesDir = 'frames/';
        if (is_dir($framesDir)) {
            $files = array_diff(scandir($framesDir), array('.', '..')); // Ignore '.' and '..'
            foreach ($files as $file) {
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) { // Show only image files
                    echo "<div class='frame'>";
                    echo "<img src='{$framesDir}{$file}' alt='Frame'>";
                    echo "<div class='caption'>Example caption</div>";
                    echo "</div>";
                }
            }
        } else {
            echo "<p>No frames available.</p>";
        }
        ?>
    </div>
</body>
</html>
