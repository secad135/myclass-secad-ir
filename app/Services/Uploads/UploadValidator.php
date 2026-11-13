<?php

namespace App\Services\Uploads;

use App\Exceptions\UploadRejectedException;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Static analysis of an upload before it is written to permanent storage.
 *
 * The checks here are intentionally strict: extension allow-list, real MIME
 * detection, magic-byte verification, polyglot/embedded-payload detection,
 * archive inspection (zip bombs, zip-slip, macros) and PDF active-content
 * detection. Signature based scanners live in MalwareScanner.
 */
class UploadValidator
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    private const ZIP_EXTENSIONS = ['zip', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'];

    private const OFFICE_EXTENSIONS = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'];

    private const TEXT_EXTENSIONS = [
        'txt', 'csv', 'rtf', 'md', 'json', 'sql', 'py', 'js', 'ts',
        'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'ipynb',
    ];

    private const IMAGE_TYPES = [
        'jpg'  => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'png'  => IMAGETYPE_PNG,
        'gif'  => IMAGETYPE_GIF,
        'webp' => IMAGETYPE_WEBP,
        'avif' => IMAGETYPE_AVIF,
    ];

    private const MAGIC_BYTES = [
        'jpg'  => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'png'  => ["\x89PNG\r\n\x1A\n"],
        'gif'  => ['GIF87a', 'GIF89a'],
        'webp' => ['RIFF'],
        'avif' => ['ftypavif', 'ftypavis'],
        'pdf'  => ['%PDF-'],
        'zip'  => ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"],
        'docx' => ["PK\x03\x04"],
        'xlsx' => ["PK\x03\x04"],
        'pptx' => ["PK\x03\x04"],
        'odt'  => ["PK\x03\x04"],
        'ods'  => ["PK\x03\x04"],
        'odp'  => ["PK\x03\x04"],
        'rar'  => ["Rar!\x1A\x07"],
        '7z'   => ["7z\xBC\xAF\x27\x1C"],
    ];

    /**
     * Payload markers that must never appear inside an image, PDF or office
     * document. Plain text/code files are exempt because they are served as
     * text/plain and cannot execute.
     */
    /**
     * Strong markers safe to search in the whole binary file. They are long
     * enough that random compressed image data will not contain them.
     */
    private const EMBEDDED_PAYLOAD_MARKERS = [
        '<?php', '<script', '<html', '<iframe', '#!/bin/',
    ];

    /**
     * Short markers are only searched in image metadata and in bytes appended
     * after the image end. Searching them in compressed pixels produces false
     * positives ("<%" alone appears hundreds of times in a normal photo).
     */
    private const SHORT_PAYLOAD_MARKERS = [
        '<?php', '<?=', '<script', '<svg',
    ];

    private const PDF_ACTIVE_CONTENT_PATTERNS = [
        '/\/JavaScript\b/i',
        '/\/JS\s*[\(<\[\/]/i',
        '/\/Launch\b/i',
        '/\/EmbeddedFile\b/i',
        '/\/OpenAction\b/i',
        '/\/RichMedia\b/i',
        '/\/XFA\b/i',
    ];

    private const RTF_ACTIVE_CONTENT = ['\objupdate', '\objdata', '\object'];

    /**
     * @return array{extension:string,mime:string,size:int,sha256:string,path:string,original_name:string}
     */
    public function validate(UploadedFile $file, string $context): array
    {
        $contextConfig = config("uploads.contexts.{$context}");

        if (!is_array($contextConfig)) {
            throw new UploadRejectedException('نوع آپلود تعریف نشده است.');
        }

        $path = $file->getRealPath();

        if (!$path || !is_file($path)) {
            throw new UploadRejectedException('فایل آپلودشده قابل خواندن نیست.');
        }

        $size = (int) $file->getSize();
        if ($size <= 0) {
            throw new UploadRejectedException('فایل خالی مجاز نیست.');
        }

        $maxBytes = (int) ($contextConfig['max_kb'] ?? 5120) * 1024;
        if ($size > $maxBytes) {
            throw new UploadRejectedException('حجم فایل بیش از حد مجاز است.');
        }

        $originalName = (string) $file->getClientOriginalName();
        if ($originalName === '' || str_contains($originalName, "\0")) {
            throw new UploadRejectedException('نام فایل نامعتبر است.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $extension = rtrim(trim($extension), '.');

        if ($extension === '') {
            throw new UploadRejectedException('فایل باید پسوند داشته باشد.');
        }

        $this->assertExtensionAllowed($extension, $originalName, $contextConfig);

        $mime = $this->detectMime($path);
        $this->assertMimeAllowed($extension, $mime);
        $this->assertMagicBytes($path, $extension);
        $this->inspectContent($path, $extension);

        return [
            'extension'     => $extension,
            'mime'          => $mime,
            'size'          => $size,
            'sha256'        => hash_file('sha256', $path) ?: '',
            'path'          => $path,
            'original_name' => $originalName,
        ];
    }

    private function assertExtensionAllowed(string $extension, string $originalName, array $contextConfig): void
    {
        $dangerous = array_map('strtolower', config('uploads.dangerous_extensions', []));
        $allowed = array_map('strtolower', $contextConfig['extensions'] ?? []);

        if (in_array($extension, $dangerous, true)) {
            throw new UploadRejectedException('این نوع فایل به دلایل امنیتی مجاز نیست.');
        }

        if (!in_array($extension, $allowed, true)) {
            throw new UploadRejectedException('پسوند فایل برای این بخش مجاز نیست.');
        }

        // "shell.php.jpg" or "payload.phtml.png" must not slip through.
        $parts = array_map('strtolower', explode('.', $originalName));
        array_pop($parts);

        foreach ($parts as $part) {
            if (in_array(rtrim(trim($part), '.'), $dangerous, true)) {
                throw new UploadRejectedException('نام فایل شامل پسوند خطرناک است.');
            }
        }
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : false;

        if ($finfo) {
            finfo_close($finfo);
        }

        return is_string($mime) ? strtolower($mime) : 'application/octet-stream';
    }

    private function assertMimeAllowed(string $extension, string $mime): void
    {
        $allowed = array_map('strtolower', config("uploads.mime_map.{$extension}", []));

        if ($allowed === [] || !in_array($mime, $allowed, true)) {
            throw new UploadRejectedException('نوع واقعی فایل با پسوند آن مطابقت ندارد.');
        }
    }

    private function assertMagicBytes(string $path, string $extension): void
    {
        $signatures = self::MAGIC_BYTES[$extension] ?? null;

        if ($signatures === null) {
            return;
        }

        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new UploadRejectedException('فایل قابل خواندن نیست.');
        }

        $head = (string) fread($handle, 32);
        fclose($handle);

        foreach ($signatures as $signature) {
            if (str_starts_with($head, $signature)) {
                return;
            }
        }

        // WebP/AVIF keep their marker a few bytes after "RIFF"/size.
        if ($extension === 'webp' && str_starts_with($head, 'RIFF') && str_contains($head, 'WEBP')) {
            return;
        }

        if ($extension === 'avif' && str_contains($head, 'ftypavi')) {
            return;
        }

        throw new UploadRejectedException('ساختار داخلی فایل با پسوند آن هم‌خوانی ندارد.');
    }

    private function inspectContent(string $path, string $extension): void
    {
        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            $this->inspectImage($path, $extension);
        }

        if ($extension === 'pdf') {
            $this->inspectPdf($path);
        }

        if ($extension === 'rtf') {
            $this->inspectRtf($path);
        }

        if (in_array($extension, self::ZIP_EXTENSIONS, true)) {
            $this->inspectZip($path, $extension);
        }

        if (in_array($extension, self::TEXT_EXTENSIONS, true)) {
            $this->inspectText($path, $extension);
        }
    }

    private function inspectImage(string $path, string $extension): void
    {
        $info = @getimagesize($path);

        if (!is_array($info) || !isset($info[0], $info[1], $info[2])) {
            throw new UploadRejectedException('تصویر معتبر نیست.');
        }

        $expectedType = self::IMAGE_TYPES[$extension] ?? null;
        if ($expectedType !== null && (int) $info[2] !== $expectedType) {
            throw new UploadRejectedException('نوع تصویر با پسوند آن مطابقت ندارد.');
        }

        $maxPixels = (int) config('uploads.images.max_pixels', 40_000_000);
        if (($info[0] * $info[1]) > $maxPixels) {
            throw new UploadRejectedException('ابعاد تصویر بیش از حد مجاز است.');
        }

        if ($this->streamContainsAny($path, self::EMBEDDED_PAYLOAD_MARKERS)) {
            throw new UploadRejectedException('تصویر حاوی کد اجرایی جاسازی‌شده است.');
        }

        $metadata = $this->imageMetadata($path, $extension);
        if ($metadata !== '' && $this->containsAny($metadata, self::SHORT_PAYLOAD_MARKERS)) {
            throw new UploadRejectedException('متادیتای تصویر حاوی کد اجرایی جاسازی‌شده است.');
        }

        $trailing = $this->imageTrailingBytes($path, $extension);
        if ($trailing !== '' && $this->containsAny($trailing, self::SHORT_PAYLOAD_MARKERS)) {
            throw new UploadRejectedException('تصویر حاوی کد اجرایی جاسازی‌شده است.');
        }
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function imageMetadata(string $path, string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => $this->jpegMetadata($path),
            'png'         => $this->pngMetadata($path),
            'webp'        => $this->webpMetadata($path),
            default       => '',
        };
    }

    private function jpegMetadata(string $path): string
    {
        $handle = fopen($path, 'rb');

        if (!$handle) {
            return '';
        }

        $blob = '';

        try {
            fread($handle, 2); // SOI

            while (!feof($handle)) {
                $prefix = fread($handle, 1);

                if ($prefix !== "\xFF") {
                    break;
                }

                $marker = fread($handle, 1);

                while ($marker === "\xFF") {
                    $marker = fread($handle, 1);
                }

                if ($marker === '') {
                    break;
                }

                $code = ord($marker);

                // EOI or start of scan: compressed pixel data begins here.
                if ($code === 0xD9 || $code === 0xDA) {
                    break;
                }

                if ($code === 0xD8) {
                    continue;
                }

                $lengthBytes = fread($handle, 2);

                if (strlen($lengthBytes) < 2) {
                    break;
                }

                $length = unpack('n', $lengthBytes)[1];

                if ($length < 2) {
                    break;
                }

                $data = $length > 2 ? (string) fread($handle, $length - 2) : '';

                if (($code >= 0xE0 && $code <= 0xEF) || $code === 0xFE) {
                    $blob .= $data;
                }
            }
        } finally {
            fclose($handle);
        }

        return $blob;
    }

    private function pngMetadata(string $path): string
    {
        $handle = fopen($path, 'rb');

        if (!$handle) {
            return '';
        }

        $blob = '';

        try {
            fread($handle, 8); // signature

            while (!feof($handle)) {
                $header = fread($handle, 8);

                if (strlen($header) < 8) {
                    break;
                }

                $length = unpack('N', substr($header, 0, 4))[1];
                $type = substr($header, 4, 4);

                if ($length > 10_000_000) {
                    break;
                }

                $data = $length > 0 ? (string) fread($handle, $length) : '';
                fread($handle, 4); // CRC

                if (in_array($type, ['tEXt', 'iTXt', 'zTXt', 'eXIf', 'iCCP'], true)) {
                    $blob .= $data;
                }

                if ($type === 'IEND') {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return $blob;
    }

    private function webpMetadata(string $path): string
    {
        $handle = fopen($path, 'rb');

        if (!$handle) {
            return '';
        }

        $blob = '';

        try {
            if (strlen((string) fread($handle, 12)) < 12) {
                return '';
            }

            while (!feof($handle)) {
                $chunk = fread($handle, 8);

                if (strlen($chunk) < 8) {
                    break;
                }

                $type = substr($chunk, 0, 4);
                $length = unpack('V', substr($chunk, 4, 4))[1];

                if ($length > 10_000_000) {
                    break;
                }

                $data = $length > 0 ? (string) fread($handle, $length) : '';

                if ($length % 2 === 1) {
                    fread($handle, 1);
                }

                if (in_array($type, ['EXIF', 'XMP '], true)) {
                    $blob .= $data;
                }
            }
        } finally {
            fclose($handle);
        }

        return $blob;
    }

    private function imageTrailingBytes(string $path, string $extension): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return '';
        }

        return match ($extension) {
            'jpg', 'jpeg' => $this->afterLast($contents, "\xFF\xD9"),
            'png'         => $this->afterLast($contents, 'IEND', 4),
            'gif'         => '',
            'webp'        => $this->webpTrailingBytes($contents),
            default       => '',
        };
    }

    private function afterLast(string $contents, string $needle, int $skip = 0): string
    {
        $position = strrpos($contents, $needle);

        if ($position === false) {
            return '';
        }

        return substr($contents, $position + strlen($needle) + $skip);
    }

    private function webpTrailingBytes(string $contents): string
    {
        if (strlen($contents) < 12) {
            return '';
        }

        $size = unpack('V', substr($contents, 4, 4))[1];
        $end = 8 + $size;

        return strlen($contents) > $end ? substr($contents, $end) : '';
    }

    private function inspectPdf(string $path): void
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new UploadRejectedException('فایل PDF قابل خواندن نیست.');
        }

        $head = (string) fread($handle, 1024);
        fclose($handle);

        if (!str_contains($head, '%PDF-')) {
            throw new UploadRejectedException('ساختار فایل PDF معتبر نیست.');
        }

        if (config('uploads.pdf_block_active_content', true)
            && $this->streamMatchesAny($path, self::PDF_ACTIVE_CONTENT_PATTERNS)) {
            throw new UploadRejectedException('PDF حاوی محتوای فعال یا اسکریپت است و پذیرفته نمی‌شود.');
        }
    }

    private function inspectRtf(string $path): void
    {
        if ($this->streamContainsAny($path, self::RTF_ACTIVE_CONTENT)) {
            throw new UploadRejectedException('فایل RTF حاوی آبجکت جاسازی‌شده است و پذیرفته نمی‌شود.');
        }
    }

    private function inspectZip(string $path, string $extension): void
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new UploadRejectedException('فایل فشرده معتبر نیست یا رمزگذاری شده است.');
        }

        try {
            $maxEntries = (int) config('uploads.heuristics.max_archive_entries', 500);
            $maxBytes = (int) config('uploads.heuristics.max_archive_uncompressed_bytes', 200 * 1024 * 1024);
            $maxRatio = (int) config('uploads.heuristics.max_archive_ratio', 100);
            $dangerous = array_map('strtolower', config('uploads.dangerous_extensions', []));

            if ($zip->numFiles > $maxEntries) {
                throw new UploadRejectedException('تعداد فایل‌های داخل آرشیو بیش از حد مجاز است.');
            }

            $totalBytes = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if (!is_array($stat)) {
                    continue;
                }

                $name = str_replace('\\', '/', (string) $stat['name']);

                if (str_contains($name, '..') || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
                    throw new UploadRejectedException('آرشیو شامل مسیر ناامن است.');
                }

                $entryExtension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

                if (in_array($entryExtension, $dangerous, true)) {
                    throw new UploadRejectedException('آرشیو شامل فایل اجرایی یا اسکریپت است.');
                }

                if (preg_match('#(^|/)embeddings/#i', $name)
                    && in_array($entryExtension, ['bin', 'exe', 'dll', 'ole', 'pkg', 'scr'], true)) {
                    throw new UploadRejectedException('سند شامل آبجکت اجرایی جاسازی‌شده است.');
                }

                if (in_array($extension, self::OFFICE_EXTENSIONS, true) && str_ends_with($name, '.rels')) {
                    $rels = $zip->getFromIndex($i);

                    if ($rels !== false && preg_match('/TargetMode\s*=\s*["\']External["\']/i', $rels)) {
                        throw new UploadRejectedException('سند شامل ارجاع خارجی (قالب راه‌دور) است.');
                    }
                }

                $uncompressed = (int) ($stat['size'] ?? 0);
                $compressed = (int) ($stat['comp_size'] ?? 0);
                $totalBytes += $uncompressed;

                if ($totalBytes > $maxBytes) {
                    throw new UploadRejectedException('حجم بازشده آرشیو بیش از حد مجاز است.');
                }

                if ($compressed > 0 && $uncompressed > 0 && ($uncompressed / $compressed) > $maxRatio) {
                    throw new UploadRejectedException('نسبت فشرده‌سازی آرشیو مشکوک است.');
                }
            }

            if (in_array($extension, self::OFFICE_EXTENSIONS, true)) {
                foreach (['word/vbaProject.bin', 'xl/vbaProject.bin', 'ppt/vbaProject.bin'] as $macro) {
                    if ($zip->locateName($macro) !== false) {
                        throw new UploadRejectedException('سند حاوی ماکرو است و پذیرفته نمی‌شود.');
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function inspectText(string $path, string $extension): void
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new UploadRejectedException('فایل قابل خواندن نیست.');
        }

        $head = (string) fread($handle, 8192);
        fclose($handle);

        if (str_contains($head, "\0")) {
            throw new UploadRejectedException('فایل متنی حاوی داده باینری است.');
        }

        if (in_array($extension, ['json', 'txt', 'csv', 'md', 'sql', 'py', 'js', 'ts', 'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'ipynb'], true)
            && !mb_check_encoding($head, 'UTF-8')) {
            throw new UploadRejectedException('کدگذاری فایل متنی باید UTF-8 باشد.');
        }
    }

    /**
     * Scan a file in chunks for any of the given byte markers.
     */
    private function streamContainsAny(string $path, array $needles, int $maxBytes = 20 * 1024 * 1024): bool
    {
        $handle = fopen($path, 'rb');

        if (!$handle) {
            throw new UploadRejectedException('فایل قابل خواندن نیست.');
        }

        $overlap = max(array_map('strlen', $needles)) - 1;
        $tail = '';
        $read = 0;

        try {
            while (!feof($handle) && $read < $maxBytes) {
                $chunk = (string) fread($handle, 1024 * 1024);

                if ($chunk === '') {
                    break;
                }

                $read += strlen($chunk);
                $buffer = $tail . $chunk;

                foreach ($needles as $needle) {
                    if (str_contains($buffer, $needle)) {
                        return true;
                    }
                }

                $tail = substr($buffer, -$overlap);
            }
        } finally {
            fclose($handle);
        }

        return false;
    }

    /**
     * Scan a file in chunks with regular expressions.
     */
    private function streamMatchesAny(string $path, array $patterns, int $maxBytes = 20 * 1024 * 1024): bool
    {
        $handle = fopen($path, 'rb');

        if (!$handle) {
            throw new UploadRejectedException('فایل قابل خواندن نیست.');
        }

        $tail = '';
        $read = 0;

        try {
            while (!feof($handle) && $read < $maxBytes) {
                $chunk = (string) fread($handle, 1024 * 1024);

                if ($chunk === '') {
                    break;
                }

                $read += strlen($chunk);
                $buffer = $tail . $chunk;

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $buffer) === 1) {
                        return true;
                    }
                }

                $tail = substr($buffer, -64);
            }
        } finally {
            fclose($handle);
        }

        return false;
    }
}
