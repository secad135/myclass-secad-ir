<?php

namespace App\Services\Uploads;

use App\Exceptions\UploadRejectedException;
use App\Models\UploadScan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    public function __construct(
        private readonly UploadValidator $validator,
        private readonly MalwareScanner $scanner,
        private readonly ImageOptimizer $images,
    ) {
    }

    /**
     * Validate and scan a file without storing it permanently.
     *
     * @return array{extension:string,mime:string,size:int,sha256:string,path:string,original_name:string}
     */
    public function inspect(UploadedFile $file, string $context): array
    {
        $info = $this->validator->validate($file, $context);

        if ($this->isKnownInfected($info['sha256'])) {
            $this->record($info, $context, 'infected', ['این فایل قبلاً مخرب تشخیص داده شده است.'], []);

            throw new UploadRejectedException('این فایل قبلاً به عنوان فایل مخرب شناسایی شده است.');
        }

        $scan = $this->scanner->scan($info['path'], $info['sha256']);

        $this->record(
            $info,
            $context,
            $scan['clean'] ? 'clean' : 'infected',
            $scan['reasons'],
            $scan['engines']
        );

        if (!$scan['clean']) {
            throw new UploadRejectedException(
                'فایل مخرب شناسایی شد و آپلود متوقف شد. ' . implode(' ', $scan['reasons']),
                ['reasons' => $scan['reasons'], 'engines' => $scan['engines']]
            );
        }

        if ($scan['unavailable'] && config('uploads.clamav.required', false)) {
            throw new UploadRejectedException(
                'سرویس بررسی بدافزار در دسترس نیست و به دلیل تنظیمات امنیتی، آپلود موقتاً غیرفعال است.'
            );
        }

        return $info;
    }

    /**
     * Validate, scan, sanitise (images) and store an upload.
     *
     * @return array{path:string,original_name:string,size:int,mime:string,sha256:string,optimized:bool,saved_bytes:int}
     */
    public function store(UploadedFile $file, string $context, string $directory): array
    {
        $info = $this->inspect($file, $context);

        $sourcePath = $info['path'];
        $extension = $info['extension'];
        $mime = $info['mime'];
        $optimized = false;
        $savedBytes = 0;

        if (in_array($extension, self::IMAGE_EXTENSIONS, true) && config('uploads.images.enabled', true)) {
            $result = $this->images->optimize($sourcePath, $extension);

            if ($result !== null) {
                $sourcePath = $result['path'];
                $extension = $result['extension'];
                $mime = $result['mime'];
                $optimized = true;
                $savedBytes = max(0, $info['size'] - $result['size']);
            }
        }

        $diskName = (string) config('uploads.disk', 'public');
        $disk = Storage::disk($diskName);
        $name = Str::random(40) . '.' . $extension;
        $targetPath = trim($directory, '/') . '/' . $name;

        $stream = @fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw new UploadRejectedException('فایل برای ذخیره‌سازی آماده نیست.');
        }

        try {
            $stored = $disk->put($targetPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($optimized) {
                @unlink($sourcePath);
            }
        }

        if (!$stored) {
            throw new UploadRejectedException('ذخیره فایل روی سرور ناموفق بود.');
        }

        return [
            'path'          => $targetPath,
            'original_name' => $info['original_name'],
            'size'          => (int) $disk->size($targetPath),
            'mime'          => $mime,
            'sha256'        => $info['sha256'],
            'optimized'     => $optimized,
            'saved_bytes'   => $savedBytes,
        ];
    }

    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        $diskName = (string) config('uploads.disk', 'public');
        Storage::disk($diskName)->delete($path);

        // Files uploaded before UPLOAD_DISK was switched to the private disk.
        if ($diskName !== 'public') {
            Storage::disk('public')->delete($path);
        }
    }

    private function isKnownInfected(string $sha256): bool
    {
        if ($sha256 === '') {
            return false;
        }

        try {
            return UploadScan::query()
                ->where('sha256', $sha256)
                ->where('verdict', 'infected')
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('Upload blocklist lookup failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function record(array $info, string $context, string $verdict, array $reasons, array $engines): void
    {
        try {
            UploadScan::create([
                'sha256'        => $info['sha256'],
                'original_name' => mb_substr($info['original_name'], 0, 255),
                'extension'     => $info['extension'],
                'mime'          => $info['mime'],
                'size'          => $info['size'],
                'context'       => $context,
                'user_id'       => auth()->id(),
                'verdict'       => $verdict,
                'reasons'       => $reasons,
                'engines'       => $engines,
                'ip'            => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Upload scan audit failed', ['error' => $e->getMessage()]);
        }
    }
}
