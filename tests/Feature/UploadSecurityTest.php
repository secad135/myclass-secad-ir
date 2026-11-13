<?php

namespace Tests\Feature;

use App\Exceptions\UploadRejectedException;
use App\Services\Uploads\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class UploadSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'uploads.disk' => 'local',
            'uploads.clamav.enabled' => false,
            'uploads.yara.enabled' => false,
            'uploads.virustotal.enabled' => false,
        ]);
    }

    public function test_rejects_dangerous_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;');

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('امنیتی');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_rejects_double_extension_even_with_valid_image(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.php.jpg', $this->pngBytes());

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('خطرناک');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_rejects_mime_mismatch(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $this->pngBytes());

        $this->expectException(UploadRejectedException::class);

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_rejects_image_with_embedded_php_payload(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'polyglot.png',
            $this->pngBytes() . '<?php system($_GET["c"]); ?>'
        );

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('جاسازی');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_rejects_pdf_with_active_content(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj\n<< /OpenAction << /S /JavaScript /JS (alert(1)) >> >>\nendobj\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('homework.pdf', $pdf);

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('PDF');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_rejects_zip_containing_executable_entry(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'zip_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('notes.txt', 'ok');
        $zip->addFromString('shell.php', '<?php echo 1;');
        $zip->close();

        $file = UploadedFile::fake()->createWithContent('bundle.zip', file_get_contents($path));
        @unlink($path);

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('آرشیو');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_stores_and_optimizes_valid_image(): void
    {
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $this->jpegBytes(3000, 2000));

        $stored = $this->service()->store($file, 'exercise_submission', 'exercise-submissions');

        $this->assertTrue($stored['optimized']);
        $this->assertSame('jpg', pathinfo($stored['path'], PATHINFO_EXTENSION));
        Storage::disk('local')->assertExists($stored['path']);
    }

    public function test_missing_scanner_does_not_block_when_not_required(): void
    {
        config([
            'uploads.clamav.enabled' => true,
            'uploads.clamav.required' => false,
            'uploads.clamav.binary' => '/nonexistent/clamscan',
        ]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $stored = $this->service()->store($file, 'exercise_submission', 'tmp');

        $this->assertNotEmpty($stored['path']);
    }

    public function test_missing_scanner_blocks_when_required(): void
    {
        config([
            'uploads.clamav.enabled' => true,
            'uploads.clamav.required' => true,
            'uploads.clamav.binary' => '/nonexistent/clamscan',
        ]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('در دسترس نیست');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    public function test_virustotal_only_setup_satisfies_required_scanner(): void
    {
        Http::fake([
            'www.virustotal.com/*' => Http::response([
                'data' => [
                    'attributes' => [
                        'last_analysis_stats' => ['malicious' => 0, 'suspicious' => 0],
                    ],
                ],
            ]),
        ]);

        config([
            'uploads.clamav.enabled' => true,
            'uploads.clamav.required' => true,
            'uploads.clamav.binary' => '/nonexistent/clamscan',
            'uploads.virustotal.enabled' => true,
            'uploads.virustotal.api_key' => 'test-key',
        ]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $stored = $this->service()->store($file, 'exercise_submission', 'tmp');

        $this->assertNotEmpty($stored['path']);
    }

    public function test_virustotal_malicious_verdict_blocks_upload(): void
    {
        Http::fake([
            'www.virustotal.com/*' => Http::response([
                'data' => [
                    'attributes' => [
                        'last_analysis_stats' => ['malicious' => 3, 'suspicious' => 1],
                    ],
                ],
            ]),
        ]);

        config([
            'uploads.clamav.enabled' => false,
            'uploads.virustotal.enabled' => true,
            'uploads.virustotal.api_key' => 'test-key',
        ]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $this->expectException(UploadRejectedException::class);
        $this->expectExceptionMessage('VirusTotal');

        $this->service()->store($file, 'exercise_submission', 'tmp');
    }

    private function service(): UploadService
    {
        return app(UploadService::class);
    }

    private function pngBytes(int $width = 40, int $height = 40): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 200));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function jpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 80, 40));
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
