<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded files as forced downloads with hardening headers.
 *
 * Files are never served inline, so an uploaded HTML/SVG/PDF cannot run
 * scripts in the application origin even if it somehow slipped through.
 */
class FileDownloadController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $path = ltrim($path, '/');

        abort_if($path === '' || str_contains($path, '..') || str_contains($path, "\0"), 404);

        $disk = Storage::disk((string) config('uploads.disk', 'local'));

        if (!$disk->exists($path)) {
            $legacy = Storage::disk('public');
            abort_unless($legacy->exists($path), 404);
            $disk = $legacy;
        }

        return $disk->download($path, basename($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
