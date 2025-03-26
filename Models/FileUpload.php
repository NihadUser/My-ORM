<?php

trait FileUpload
{
    public static function imageUpload($item, $id)
    {
        $fileTmpPath = $item['tmp_name'];
        $fileName = $item['name'];
        $mimes = ['jpg', 'jpeg', 'png'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!isset($fileName) || !in_array($fileExtension, $mimes)) {
            return ['success' => false, 'error' => 'Invalid file or unsupported format.'];
        }

        $newFileName = $id . '_' . uniqid() . '.' . $fileExtension;

        $baseDir = '../../images/';
        $folders = date('Y') . '-' . date('m');
        $uploadDirectory = $baseDir . $folders;

        if (!is_dir($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0777, true)) {
                return ['success' => false, 'error' => 'Failed to create directories.'];
            }
        }

        $uploadPath = $uploadDirectory . '/' . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $uploadPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }

        try {
            self::compressImage($uploadPath, $uploadPath, 25); // 25% quality
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true, 'image' => $folders . '/' . $newFileName];
    }

    public static function compressImage($source, $destination, $quality)
    {
        $imageInfo = getimagesize($source);
        $mime = $imageInfo['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                imagejpeg($image, $destination, $quality);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                $quality = round(9 - ($quality / 100 * 9)); // Adjust PNG quality
                imagepng($image, $destination, $quality);
                break;
            default:
                throw new Exception("Unsupported file format.");
        }

        if (isset($image)) {
            imagedestroy($image);
        }
    }

}