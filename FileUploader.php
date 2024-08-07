<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;

class FileUploader
{
    private $s3Client;
    private $bucket;
    private $baseUrl;

    public function __construct($endpoint, $accessKey, $secretKey, $bucket, $region = 'us-east-1')
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
        $this->bucket = $bucket;
        $this->baseUrl = rtrim($endpoint, '/') . '/' . $bucket . '/'; // Base URL for public access
    }

    public function uploadImage($filePath)
{
    $fileInfo = pathinfo($filePath);
    $timestamp = time();
    $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : 'jpg';
    $imageName = $timestamp . '.' . $extension; // Rename with timestamp and keep original extension

    // Get original file content
    $originalFileContent = file_get_contents($filePath);

    // Resize and upload images
    $thumb = $this->resizeAndCrop($filePath, 200, 200, true);
    $medium = $this->resizeImage($filePath, 512, 512);
    $large = $this->resizeImage($filePath, 1080, 1080);

    // Object keys for storage
    $originalKey = "images/original/{$imageName}";
    $thumbKey = "images/thumb/{$imageName}";
    $mediumKey = "images/medium/{$imageName}";
    $largeKey = "images/large/{$imageName}";

    // Upload original and resized images
    $this->uploadToMinio($originalFileContent, $originalKey);
    $this->uploadToMinio($thumb, $thumbKey);
    $this->uploadToMinio($medium, $mediumKey);
    $this->uploadToMinio($large, $largeKey);

    // Return object keys and URLs
    return [
        'original' => [
            'key' => $originalKey,
            'url' => $this->generatePermanentUrl($originalKey)
        ],
        'thumb' => [
            'key' => $thumbKey,
            'url' => $this->generatePermanentUrl($thumbKey)
        ],
        'medium' => [
            'key' => $mediumKey,
            'url' => $this->generatePermanentUrl($mediumKey)
        ],
        'large' => [
            'key' => $largeKey,
            'url' => $this->generatePermanentUrl($largeKey)
        ],
    ];
}


    public function uploadVideo($filePath)
    {
        $fileInfo = pathinfo($filePath);
        $timestamp = time();
        $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : 'mp4';
        $videoName = $timestamp . '.' . $extension; // Rename with timestamp and keep original extension

        // Upload video without resizing
        $videoData = file_get_contents($filePath);
        $videoKey = "videos/{$videoName}";
        $this->uploadToMinio($videoData, $videoKey);

        // Return object key and URL
        return [
            'key' => $videoKey,
            'url' => $this->generatePermanentUrl($videoKey)
        ];
    }

    private function resizeAndCrop($filePath, $width, $height, $crop = false)
    {
        $image = $this->createImageFromFile($filePath);
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $newImage = imagecreatetruecolor($width, $height);

        if ($crop) {
            $ratio = max($width / $origWidth, $height / $origHeight);
            $x = ($origWidth - $width / $ratio) / 2;
            $y = ($origHeight - $height / $ratio) / 2;
            $origWidth = $width / $ratio;
            $origHeight = $height / $ratio;
        } else {
            $ratio = min($width / $origWidth, $height / $origHeight);
            $newWidth = $origWidth * $ratio;
            $newHeight = $origHeight * $ratio;
            $x = 0;
            $y = 0;
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
        }

        imagecopyresampled($newImage, $image, 0, 0, $x, $y, $width, $height, $origWidth, $origHeight);
        ob_start();
        imagejpeg($newImage);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);
        imagedestroy($newImage);

        return $imageData;
    }

    private function resizeImage($filePath, $maxWidth, $maxHeight)
    {
        $image = $this->createImageFromFile($filePath);
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = $origWidth * $ratio;
        $newHeight = $origHeight * $ratio;

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        ob_start();
        imagejpeg($newImage);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);
        imagedestroy($newImage);

        return $imageData;
    }

    private function createImageFromFile($filePath)
    {
        $info = getimagesize($filePath);
        $type = $info[2];

        if ($type == IMAGETYPE_JPEG) {
            return imagecreatefromjpeg($filePath);
        } elseif ($type == IMAGETYPE_PNG) {
            return imagecreatefrompng($filePath);
        } elseif ($type == IMAGETYPE_GIF) {
            return imagecreatefromgif($filePath);
        } elseif ($type == IMAGETYPE_WEBP) {
            return imagecreatefromwebp($filePath);
        } else {
            throw new Exception("Unsupported image type");
        }
    }

    private function uploadToMinio($fileData, $objectName)
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $objectName,
            'Body' => $fileData,
            'ACL' => 'public-read', // Make the object publicly readable
        ]);
    }

    public function generatePermanentUrl($objectName)
    {
        // Generate a URL that is publicly accessible
        return $this->baseUrl . $objectName;
    }

    public function generatePresignedUrl($objectName, $expiry = '+1 hour')
    {
        $cmd = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $objectName,
        ]);

        $request = $this->s3Client->createPresignedRequest($cmd, $expiry);

        return (string)$request->getUri();
    }
}
