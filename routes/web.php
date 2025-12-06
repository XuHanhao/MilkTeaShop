<?php

use Illuminate\Support\Facades\Route;

// Helper function: Check if the path is a static resource file
if (!function_exists('isStaticResource')) {
    function isStaticResource($path) {
        $staticExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'json', 'webp', 'mp4', 'mp3'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $staticExtensions);
    }
}

// Helper function: Get MIME type for a file
if (!function_exists('getMimeType')) {
    function getMimeType($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'css' => 'text/css',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'json' => 'application/json',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

// Helper function: Return static file response with correct MIME type
if (!function_exists('serveStaticFile')) {
    function serveStaticFile($filePath) {
        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404);
        }
        
        $mimeType = getMimeType($filePath);
        $content = file_get_contents($filePath);
        
        // Add charset for JavaScript and CSS
        if (in_array($mimeType, ['application/javascript', 'text/css'])) {
            $mimeType .= '';
        }
        
        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}

// Handle static resources at root path (e.g., /assets/...) - must be before other routes
Route::get('/assets/{path}', function ($path) {
    // First try to find in admin/assets directory
    $filePath = public_path('admin/assets/' . $path);
    if (file_exists($filePath) && is_file($filePath)) {
        return serveStaticFile($filePath);
    }
    
    // Then try to find in h5/assets directory
    $filePath = public_path('h5/assets/' . $path);
    if (file_exists($filePath) && is_file($filePath)) {
        return serveStaticFile($filePath);
    }
    
    abort(404);
})->where('path', '.*');

// Admin panel routes: /admin and /admin/* access admin application
Route::prefix('admin')->group(function () {
    Route::get('/{any?}', function ($any = null) {
        // If $any is not empty, check if it's a static resource request first
        if ($any) {
            $filePath = public_path('admin/' . $any);
            // Check if it's a static resource file type, then check if file exists
            if (isStaticResource($any) && file_exists($filePath) && is_file($filePath)) {
                return serveStaticFile($filePath);
            }
            // If not a static resource or file doesn't exist, continue to return HTML (for Vue Router)
        }
        
        // Return admin panel index.html
        $adminIndexPath = public_path('admin/index.html');
        
        if (!file_exists($adminIndexPath)) {
            abort(404, 'Admin application not found.');
        }
        
        $content = file_get_contents($adminIndexPath);
        
        return response($content, 200, [
            'Content-Type' => 'text/html',
        ]);
    })->where('any', '.*');
});

// H5 mobile application route: root path accesses H5 application
Route::get('/', function () {
    $h5IndexPath = public_path('h5/index.html');
    
    if (!file_exists($h5IndexPath)) {
        abort(404, 'H5 application not found.');
    }
    
    $content = file_get_contents($h5IndexPath);
    
    return response($content, 200, [
        'Content-Type' => 'text/html',
    ]);
});

// H5 mobile application other routes: support Vue Router frontend routes
Route::get('/{any}', function ($any) {
    // Explicitly exclude assets path (should be handled by /assets/{path} route)
    if (str_starts_with($any, 'assets/') || $any === 'assets') {
        abort(404);
    }
    
    // Exclude admin and api routes
    if (str_starts_with($any, 'admin') || str_starts_with($any, 'api')) {
        abort(404);
    }
    
    // Check if it's a static resource request
    if (isStaticResource($any)) {
        $filePath = public_path('h5/' . $any);
        return serveStaticFile($filePath);
    }
    
    $h5IndexPath = public_path('h5/index.html');
    
    if (!file_exists($h5IndexPath)) {
        abort(404, 'H5 application not found.');
    }
    
    $content = file_get_contents($h5IndexPath);
    
    return response($content, 200, [
        'Content-Type' => 'text/html',
    ]);
})->where('any', '^(?!api|assets).*$');
