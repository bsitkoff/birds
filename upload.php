<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$uploadDir = __DIR__ . '/uploads/';
$frameDir = __DIR__ . '/frames/';

// Ensure the directories exist
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
if (!file_exists($frameDir)) mkdir($frameDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $video = $_FILES['video'];

    // Debug upload process
    file_put_contents('debug_log.txt', "Upload Started\n", FILE_APPEND);
    file_put_contents('debug_log.txt', "Uploaded File: " . print_r($video, true), FILE_APPEND);

    // Validate uploaded file
    if ($video['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed with error code " . $video['error']);
    }

    if (!is_uploaded_file($video['tmp_name'])) {
        die("Invalid uploaded file.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ($finfo->file($video['tmp_name']) !== 'video/mp4') {
        die("Invalid file type. Please upload an MP4 video.");
    }

    // Save uploaded file
    $uploadPath = $uploadDir . uniqid('video_', true) . '.mp4';
    if (!move_uploaded_file($video['tmp_name'], $uploadPath)) {
        die("Failed to save uploaded file.");
    }
    file_put_contents('debug_log.txt', "Uploaded file saved to: $uploadPath\n", FILE_APPEND);

    // Get video duration using FFprobe
    $ffprobePath = '/opt/homebrew/bin/ffprobe';
    $durationCommand = "$ffprobePath -i " . escapeshellarg($uploadPath) . " -show_entries format=duration -v quiet -of csv=p=0";
    $videoDuration = floatval(trim(shell_exec($durationCommand)));
    file_put_contents('debug_log.txt', "Video Duration: $videoDuration seconds\n", FILE_APPEND);

    if (!$videoDuration || $videoDuration <= 0.0) {
        die("Failed to fetch video duration. The file may not be valid.");
    }

    // Frame sampling logic
    $ffmpegPath = '/opt/homebrew/bin/ffmpeg';
    $numSamples = 5; // Number of frames to extract
    $tempFrames = [];
    $chosenFramePath = null;

    for ($i = 1; $i <= $numSamples; $i++) {
        // Generate timestamps
        $timestamp = round(($i / ($numSamples + 1)) * $videoDuration, 3);

        // File path for the extracted frame
        $tempFramePath = $frameDir . uniqid("temp_frame_", true) . ".jpg";

        // Run FFmpeg command to extract frame
        $command = "$ffmpegPath -y -i " . escapeshellarg($uploadPath) . " -ss $timestamp -frames:v 1 -update 1 " . escapeshellarg($tempFramePath);
        $ffmpegOutput = shell_exec($command . " 2>&1"); // Redirect FFmpeg errors to output for logging
        file_put_contents('debug_log.txt', "FFmpeg Command: $command\nFFmpeg Output: $ffmpegOutput\n", FILE_APPEND);

        // Check if the frame extraction was successful
        if (file_exists($tempFramePath)) {
            $tempFrames[$tempFramePath] = filesize($tempFramePath);
        }
    }

    // Select the largest frame based on file size
    if (!empty($tempFrames)) {
        arsort($tempFrames);
        $chosenFramePath = array_key_first($tempFrames); // Largest frame
        $finalFramePath = $frameDir . uniqid("chosen_frame_", true) . '.jpg'; // Unique filename
        rename($chosenFramePath, $finalFramePath); // Save with a unique name for multiple uploads
    }

    // Cleanup temporary frames
    foreach ($tempFrames as $frame => $size) {
        if ($frame !== $chosenFramePath) {
            unlink($frame);
        }
    }

    // Handle case where no frames were extracted
    if (!$chosenFramePath) {
        unlink($uploadPath); // Delete the uploaded file
        die("No suitable frames were extracted from the video.");
    }

    file_put_contents('debug_log.txt', "Final Chosen Frame: $finalFramePath\n", FILE_APPEND);

    // Optional: Delete the uploaded video after processing
    // unlink($uploadPath);

    // Redirect back to the homepage
    header("Location: index.php");
    exit();

} else {
    header("Location: index.php");
    exit();
}
?>
