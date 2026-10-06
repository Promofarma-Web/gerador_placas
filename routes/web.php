<?php

use Illuminate\Support\Facades\Route;
use App\Query\DownloadPdfQuery;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php

Route::get('/img/{filename}', function ($filename) {
    $path = public_path('img/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    DownloadPdfQuery::markAsDownloaded($filename);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
    ]);
});
