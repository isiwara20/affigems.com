<?php
/**
 * Image Optimizer Service
 * 
 * Compresses and resizes uploaded images to reduce page load time.
 * - Converts to WebP format for better compression (with PNG/JPG fallback)
 * - Resizes to max dimensions based on usage context
 * - Maintains aspect ratio
 * - Applies JPEG/WebP quality compression
 */

class ImageOptimizer {

    /**
     * Optimize an uploaded image file in-place
     * 
     * @param string $filePath Absolute path to the uploaded image
     * @param int $maxWidth Maximum width in pixels
     * @param int $maxHeight Maximum height in pixels  
     * @param int $quality Compression quality (1-100)
     * @param bool $convertToWebp Whether to convert to WebP format
     * @return string|false New file path (may have changed extension) or false on failure
     */
    public static function optimize($filePath, $maxWidth = 1200, $maxHeight = 1200, $quality = 80, $convertToWebp = true) {
        if (!file_exists($filePath)) return false;
        
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) return false;
        
        $mime = $imageInfo['mime'];
        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];
        
        // Create image resource based on type
        $image = null;
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($filePath);
                break;
            default:
                return $filePath; // Unsupported type, return as-is
        }
        
        if (!$image) return $filePath;
        
        // Calculate new dimensions maintaining aspect ratio
        $newWidth = $origWidth;
        $newHeight = $origHeight;
        
        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratioW = $maxWidth / $origWidth;
            $ratioH = $maxHeight / $origHeight;
            $ratio = min($ratioW, $ratioH);
            $newWidth = (int)round($origWidth * $ratio);
            $newHeight = (int)round($origHeight * $ratio);
        }
        
        // Resize if needed
        if ($newWidth !== $origWidth || $newHeight !== $origHeight) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG/WebP
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $resized;
        }
        
        // Determine output format
        $outputPath = $filePath;
        
        if ($convertToWebp && function_exists('imagewebp')) {
            // Convert to WebP
            $outputPath = preg_replace('/\.(jpe?g|png|webp)$/i', '.webp', $filePath);
            imagewebp($image, $outputPath, $quality);
            
            // Delete original if different file
            if ($outputPath !== $filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        } else {
            // Save in original format with compression
            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($image, $outputPath, $quality);
                    break;
                case 'image/png':
                    // PNG quality is 0-9 (0 = no compression, 9 = max)
                    $pngQuality = (int)round((100 - $quality) / 11.1);
                    imagepng($image, $outputPath, min($pngQuality, 9));
                    break;
                case 'image/webp':
                    imagewebp($image, $outputPath, $quality);
                    break;
            }
        }
        
        imagedestroy($image);
        
        return $outputPath;
    }

    /**
     * Optimize a product image (main image)
     * Max 800px wide, quality 78
     */
    public static function optimizeProduct($filePath) {
        return self::optimize($filePath, 800, 800, 78, true);
    }

    /**
     * Optimize a product gallery image
     * Max 900px wide, quality 75
     */
    public static function optimizeGallery($filePath) {
        return self::optimize($filePath, 900, 900, 75, true);
    }

    /**
     * Optimize a category image
     * Max 600px wide, quality 78
     */
    public static function optimizeCategory($filePath) {
        return self::optimize($filePath, 600, 600, 78, true);
    }

    /**
     * Optimize a banner image for high visual fidelity.
     * - Always outputs WebP when supported.
     * - Preserves original dimensions (no downscaling).
     * - Uses near-lossless quality to avoid visible degradation.
     */
    public static function optimizeBanner($filePath, $quality = 96) {
        if (!file_exists($filePath)) return false;
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) return false;

        $origWidth = (int)$imageInfo[0];
        $origHeight = (int)$imageInfo[1];

        // Keep full source dimensions so banner quality is not reduced.
        return self::optimize($filePath, $origWidth, $origHeight, $quality, true);
    }

    /**
     * Resize/compress a static site image in its original format and write a WebP sidecar.
     */
    public static function optimizeStaticAsset($filePath, $maxWidth = 1200, $maxHeight = 1200, $quality = 80) {
        if (!file_exists($filePath)) {
            return false;
        }

        self::optimize($filePath, $maxWidth, $maxHeight, $quality, false);
        self::writeWebpSidecar($filePath, $quality);

        return $filePath;
    }

    private static function writeWebpSidecar($filePath, $quality = 80) {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }

        $image = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                return $filePath;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        if ($imageInfo['mime'] === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $filePath);
        imagewebp($image, $webpPath, $quality);
        imagedestroy($image);

        return $webpPath;
    }
}
