<?php

use Illuminate\Support\Facades\Route;
use App\Query\DownloadPdfQuery;

Route::get('/', function () {
    return view('welcome');
});

$downloadPdf = function (string $filename) {
    $filename = basename($filename);
    $path = public_path('img/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    DownloadPdfQuery::markAsDownloaded($filename);

    return response()->download($path, $filename, [
        'Content-Type' => 'application/pdf',
    ]);
};

Route::get('/download/{filename}', $downloadPdf)->name('pdf.download');

// Links antigos (notificações já enviadas); o nginx encaminha /img/*.pdf para o Laravel
Route::get('/img/{filename}', $downloadPdf)->where('filename', '.+\.pdf');
