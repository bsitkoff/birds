<?php
// upload.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define upload and frame directories using local paths
$uploadDir = __DIR__ . '/uploads/';
$frameDir = __DIR__ . '/frames/';

// Create the directories if they don't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
if (!file_exists($frameDir)) {
    mkdir($frameDir, 0755, true);
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $video = $_FILES['video'];

    // Check for upload errors
    if ($video['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed with error code " . $video['error']);
    }

    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($video['tmp_name']);
    if ($mime !== 'video/mp4') {
        die("Invalid file format. Please upload an MP4 video.");
    }

    // Generate a unique name for the uploaded video
    $videoName = uniqid('video_', true) . '.mp4';
    $uploadPath = $uploadDir . $videoName;

    // Move the uploaded file to the upload directory
    if (!move_uploaded_file($video['tmp_name'], $uploadPath)) {
        die("Failed to move uploaded file.");
    }

    // Log successful upload
    file_put_contents('debug_log.txt', "Uploaded file moved to $uploadPath\n", FILE_APPEND);

    // Define the output frame image path
    $frameName = uniqid('frame_', true) . '.jpg';
    $framePath = $frameDir . $frameName;

    // Define the path to FFmpeg
    $ffmpegPath = '/opt/homebrew/bin/ffmpeg'; // Adjust for your system

    // Check if FFmpeg exists
    if (!file_exists($ffmpegPath)) {
        unlink($uploadPath); // Cleanup the uploaded file
        die("FFmpeg not found at path: $ffmpegPath");
    }

    // Generate the FFmpeg command to extract a frame
   $command = "env HOME=/Users/bridget /opt/homebrew/bin/ffmpeg -y -i " . escapeshellarg($uploadPath) . " -ss 00:00:00 -frames:v 1 -update 1 " . escapeshellarg($framePath) . " 2>&1";

    // Execute the FFmpeg command
    exec($command, $output, $return_var);

    // Log FFmpeg output and status
    file_put_contents('debug_log.txt', "Executing FFmpeg Command: $command\n", FILE_APPEND);
    file_put_contents('debug_log.txt', "Output:\n" . implode("\n", $output) . "\n", FILE_APPEND);
    file_put_contents('debug_log.txt', "Return Status: $return_var\n", FILE_APPEND);

    // Check if FFmpeg command was successful
    if ($return_var !== 0) {
        unlink($uploadPath); // Cleanup the uploaded file
        die("FFmpeg failed to extract frame from video. Check debug_log.txt for details.");
    }

    // Log successful frame extraction
    file_put_contents('debug_log.txt', "Frame successfully extracted to $framePath\n", FILE_APPEND);

    // Optionally, cleanup the uploaded video if no longer needed
     unlink($uploadPath);

    // Redirect back to the main page
    header("Location: index.php");
    exit();
} else {
    // If accessed without POST data
    header("Location: index.php");
    exit();
}
?>
