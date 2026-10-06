<?php

use Illuminate\Support\Facades\Route;
use App\Query\DownloadPdfQuery;

Route::get('/', function () {
    return view('welcome');
});

// /img/* é servido direto pelo servidor web (arquivo estático), por isso o download passa por esta rota
Route::get('/download/{filename}', function (string $filename) {
    $filename = basename($filename);
    $path = public_path('img/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    DownloadPdfQuery::markAsDownloaded($filename);

    return response()->download($path, $filename, [
        'Content-Type' => 'application/pdf',
    ]);
})->name('pdf.download');
