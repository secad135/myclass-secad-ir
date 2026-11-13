<?php

namespace App\Services\Uploads;

use Illuminate\Support\Facades\Log;

/**
 * Re-encodes images with GD.
 *
 * Re-encoding is a security control as much as a storage control: it drops
 * EXIF/XMP/ICC payloads and any bytes appended after the image data, which is
 * where polyglot malware usually hides. It also resizes oversized photos and
 * compresses them so host disk usage stays low.
 */
class ImageOptimizer
{
    private const SUPPORTED = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    /**
     * @return array{path:string,extension:string,mime:string,width:int,height:int,size:int}|null
     */
    public function optimize(string $sourcePath, string $extension): ?array
    {
        $extension = strtolower($extension);

        if (!in_array($extension, self::SUPPORTED, true) || !function_exists('imagecreatefromstring')) {
            return null;
        }

        if ($extension === 'gif' && $this->isAnimatedGif($sourcePath)) {
            return null;
        }

        $contents = @file_get_contents($sourcePath);
        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        unset($contents);

        if ($image === false) {
            return null;
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width < 1 || $height < 1) {
                return null;
            }

            if (in_array($extension, ['jpg', 'jpeg'], true)) {
                $image = $this->applyExifOrientation($image, $sourcePath);
                $width = imagesx($image);
                $height = imagesy($image);
            }

            $image = $this->resize($image, $width, $height);
            $width = imagesx($image);
            $height = imagesy($image);

            [$targetExtension, $targetMime] = $this->targetFormat($extension);
            $tempPath = tempnam(sys_get_temp_dir(), 'upload_opt_');

            if ($tempPath === false) {
                return null;
            }

            $encoded = $this->encode($image, $tempPath, $targetExtension);

            if (!$encoded) {
                @unlink($tempPath);

                return null;
            }

            return [
                'path'      => $tempPath,
                'extension' => $targetExtension,
                'mime'      => $targetMime,
                'width'     => $width,
                'height'    => $height,
                'size'      => (int) filesize($tempPath),
            ];
        } catch (\Throwable $e) {
            Log::warning('Image optimisation failed', ['error' => $e->getMessage()]);

            return null;
        } finally {
            if (isset($image) && $image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
    }

    private function resize(\GdImage $image, int $width, int $height): \GdImage
    {
        $maxWidth = (int) config('uploads.images.max_width', 2000);
        $maxHeight = (int) config('uploads.images.max_height', 2000);

        if ($maxWidth <= 0 || $maxHeight <= 0) {
            return $image;
        }

        $ratio = min(1, $maxWidth / $width, $maxHeight / $height);

        if ($ratio >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($resized === false) {
            return $image;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function targetFormat(string $extension): array
    {
        $preferred = strtolower((string) config('uploads.images.format', 'auto'));

        if ($preferred === 'webp'
            && in_array($extension, ['jpg', 'jpeg', 'png', 'avif'], true)
            && function_exists('imagewebp')) {
            return ['webp', 'image/webp'];
        }

        if ($extension === 'avif' && !function_exists('imageavif')) {
            return ['jpg', 'image/jpeg'];
        }

        return match ($extension) {
            'jpg', 'jpeg' => ['jpg', 'image/jpeg'],
            'png'         => ['png', 'image/png'],
            'gif'         => ['gif', 'image/gif'],
            'webp'        => ['webp', 'image/webp'],
            'avif'        => ['avif', 'image/avif'],
            default       => ['jpg', 'image/jpeg'],
        };
    }

    private function encode(\GdImage $image, string $targetPath, string $extension): bool
    {
        return match ($extension) {
            'jpg'  => $this->encodeJpeg($image, $targetPath),
            'png'  => $this->encodePng($image, $targetPath),
            'gif'  => imagegif($image, $targetPath),
            'webp' => function_exists('imagewebp')
                ? imagewebp($image, $targetPath, (int) config('uploads.images.webp_quality', 82))
                : false,
            'avif' => function_exists('imageavif')
                ? imageavif($image, $targetPath, (int) config('uploads.images.avif_quality', 80))
                : false,
            default => false,
        };
    }

    private function encodeJpeg(\GdImage $image, string $targetPath): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            return false;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        $result = imagejpeg($canvas, $targetPath, (int) config('uploads.images.jpeg_quality', 82));
        imagedestroy($canvas);

        return $result;
    }

    private function encodePng(\GdImage $image, string $targetPath): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, $targetPath, (int) config('uploads.images.png_compression', 9));
    }

    private function isAnimatedGif(string $path): bool
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        return substr_count($contents, "\x21\xF9\x04") > 1;
    }
}
