<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk
    |--------------------------------------------------------------------------
    | New uploads are written to the private "local" disk and served through
    | the signed download route, so the web server never executes them.
    | Legacy files on the public disk are still readable through that route.
    */
    'disk' => env('UPLOAD_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Upload contexts
    |--------------------------------------------------------------------------
    | Every upload entry point has an explicit allow-list. Do not accept an
    | arbitrary file type just because a form allows it.
    */
    'contexts' => [

        'exercise_attachment' => [
            'max_kb' => 5120,
            'extensions' => [
                'pdf', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv', 'rtf',
                'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif',
                'zip', 'rar', '7z',
                'py', 'js', 'ts', 'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'sql', 'json', 'md', 'ipynb',
            ],
        ],

        'exercise_submission' => [
            'max_kb' => 10240,
            'extensions' => [
                'pdf', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv', 'rtf',
                'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif',
                'zip', 'rar', '7z',
                'py', 'js', 'ts', 'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'sql', 'json', 'md', 'ipynb',
            ],
        ],

        'question_import' => [
            'max_kb' => 5120,
            'extensions' => ['json', 'txt'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Actual MIME types allowed for each extension
    |--------------------------------------------------------------------------
    | The extension is what the user typed; the MIME type is what fileinfo
    | detects from the bytes. Both must agree.
    */
    'mime_map' => [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'avif' => ['image/avif', 'image/avif-sequence'],

        'pdf' => ['application/pdf'],

        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
        ],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        'odp' => ['application/vnd.oasis.opendocument.presentation', 'application/zip'],

        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv', 'text/comma-separated-values'],
        'rtf'  => ['text/rtf', 'application/rtf'],
        'md'   => ['text/plain', 'text/markdown'],
        'json' => ['application/json', 'text/plain'],
        'sql'  => ['text/plain', 'application/sql'],
        'py'   => ['text/plain', 'text/x-python', 'application/x-python'],
        'js'   => ['text/plain', 'application/javascript', 'text/javascript', 'application/x-javascript'],
        'ts'   => ['text/plain', 'application/typescript', 'text/typescript'],
        'java' => ['text/plain', 'text/x-java', 'text/x-java-source'],
        'c'    => ['text/plain', 'text/x-c'],
        'cpp'  => ['text/plain', 'text/x-c++', 'text/x-c'],
        'h'    => ['text/plain', 'text/x-c', 'text/x-c++'],
        'hpp'  => ['text/plain', 'text/x-c++', 'text/x-c'],
        'cs'   => ['text/plain', 'text/x-csharp'],
        'ipynb'=> ['application/json', 'text/plain'],

        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        'rar' => [
            'application/vnd.rar',
            'application/x-rar',
            'application/x-rar-compressed',
            'application/octet-stream',
        ],
        '7z'  => ['application/x-7z-compressed', 'application/octet-stream'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Always-dangerous extensions
    |--------------------------------------------------------------------------
    | These are blocked even if a future context accidentally allows them.
    */
    'dangerous_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht',
        'exe', 'com', 'bat', 'cmd', 'msi', 'scr', 'cpl', 'dll', 'sys', 'drv',
        'sh', 'bash', 'zsh', 'ps1', 'psm1', 'vbs', 'vbe', 'wsf', 'wsh', 'hta',
        'jar', 'war', 'ear', 'class', 'jsp', 'asp', 'aspx', 'cgi', 'pl',
        'html', 'htm', 'xhtml', 'svg', 'xml', 'xsl', 'xslt',
        'docm', 'xlsm', 'pptm', 'dotm', 'xlam', 'potm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image optimisation
    |--------------------------------------------------------------------------
    | Images are re-encoded with GD. Re-encoding removes EXIF/metadata and
    | makes it much harder to smuggle an executable payload inside an image.
    */
    'images' => [
        'enabled' => env('UPLOAD_IMAGE_OPTIMIZE', true),
        'max_width' => (int) env('UPLOAD_IMAGE_MAX_WIDTH', 2000),
        'max_height' => (int) env('UPLOAD_IMAGE_MAX_HEIGHT', 2000),
        'max_pixels' => (int) env('UPLOAD_IMAGE_MAX_PIXELS', 40000000),
        'jpeg_quality' => (int) env('UPLOAD_IMAGE_JPEG_QUALITY', 82),
        'webp_quality' => (int) env('UPLOAD_IMAGE_WEBP_QUALITY', 82),
        'avif_quality' => (int) env('UPLOAD_IMAGE_AVIF_QUALITY', 80),
        'png_compression' => (int) env('UPLOAD_IMAGE_PNG_COMPRESSION', 9),
        // "auto" keeps the original format; "webp" converts jpg/png/avif to WebP.
        'format' => env('UPLOAD_IMAGE_FORMAT', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF active content
    |--------------------------------------------------------------------------
    | PDFs containing JavaScript, launch actions or embedded files are rejected
    | by default. Set to false only if your users legitimately need them.
    */
    'pdf_block_active_content' => env('UPLOAD_PDF_BLOCK_ACTIVE_CONTENT', true),

    /*
    |--------------------------------------------------------------------------
    | ClamAV
    |--------------------------------------------------------------------------
    | ClamAV provides signature-based detection and must be kept updated with
    | freshclam. On cPanel/shared hosting it may not be available; in that case
    | configure VirusTotal below or ask the host to install ClamAV.
    */
    'clamav' => [
        'enabled' => env('CLAMAV_ENABLED', true),
        'required' => env('UPLOAD_SCAN_REQUIRED', false),
        // auto | daemon | standalone | off
        'mode' => env('CLAMAV_MODE', 'auto'),
        'binary' => env('CLAMAV_BINARY'),
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
        'max_scan_bytes' => (int) env('CLAMAV_MAX_SCAN_BYTES', 104857600),
    ],

    /*
    |--------------------------------------------------------------------------
    | YARA (optional heuristic rules)
    |--------------------------------------------------------------------------
    | Install yara and point YARA_RULES_PATH to a file or a directory.
    */
    'yara' => [
        'enabled' => env('YARA_ENABLED', false),
        'binary' => env('YARA_BINARY'),
        'rules_path' => env('YARA_RULES_PATH'),
        'timeout' => (int) env('YARA_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | VirusTotal (optional cloud lookup)
    |--------------------------------------------------------------------------
    | Only the SHA-256 hash is sent to VirusTotal by default. This gives access
    | to the verdicts of dozens of engines without uploading the actual file.
    | Files never uploaded to VirusTotal are marked as "unknown" and other
    | engines still decide.
    */
    'virustotal' => [
        'enabled' => env('VIRUSTOTAL_ENABLED', false),
        'api_key' => env('VIRUSTOTAL_API_KEY'),
        'timeout' => (int) env('VIRUSTOTAL_TIMEOUT', 10),
        'cache_ttl' => (int) env('VIRUSTOTAL_CACHE_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Heuristic rules
    |--------------------------------------------------------------------------
    */
    'heuristics' => [
        'max_archive_entries' => (int) env('UPLOAD_MAX_ARCHIVE_ENTRIES', 500),
        'max_archive_uncompressed_bytes' => (int) env('UPLOAD_MAX_ARCHIVE_UNCOMPRESSED', 209715200),
        'max_archive_ratio' => (int) env('UPLOAD_MAX_ARCHIVE_RATIO', 100),
    ],

];
