<?php

require_once 'vendor/autoload.php';
require 'FileUploader.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set the default timezone
date_default_timezone_set('Asia/Kolkata');

// Configuration details for Minio
$endpoint = $_ENV['AWS_ENDPOINT'];
$accessKey = $_ENV['AWS_ACCESS_KEY_ID'];
$secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'];
$region = $_ENV['AWS_DEFAULT_REGION'];
$bucket = $_ENV['AWS_BUCKET'];

// Create an instance of FileUploader
$fileUploader = new FileUploader($endpoint, $accessKey, $secretKey, $bucket, $region);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $filePath = $_FILES['image']['tmp_name'];

    // Upload image and get results
    $uploadedImages = $fileUploader->uploadImage($filePath);

    // Prepare results with presigned URLs
    $results = [];
    foreach ($uploadedImages as $size => $url) {
        if (is_array($url)) {
            // Handle if $url is an array, e.g., if there are additional data in the array
            $url = $url['url'] ?? ''; // Adjust based on actual data structure
        }

        // Ensure $url is a string before parsing
        if (is_string($url)) {
            // Extract object key from the URL
            $parsedUrl = parse_url($url);
            $key = basename($parsedUrl['path']);
            $presignedUrl = $fileUploader->generatePresignedUrl("images/{$size}/{$key}");
            $results[] = [
                'size' => $size,
                'key' => $key,
                'url' => $presignedUrl,
            ];
        }
    }

    // Return the results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
}
