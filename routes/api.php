<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerateImage;
use App\Http\Controllers\RecoverAllFamilias;
use App\Http\Controllers\RecoverFamilias;
use App\Http\Controllers\RecoverPdf;
use App\Http\Controllers\RecoverPdfByStore;
use App\Http\Controllers\RecoverPdfTemplate;
//use App\Http\Controllers\RecoverPdfTemplatesByStore;
use App\Http\Controllers\RecoverPromotions;
use App\Http\Controllers\RecoverTemplates;
use App\Http\Controllers\RecoverTemplatesProducts;
use App\Http\Controllers\RecoverTypePage;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::post('generate-image', GenerateImage::class);
Route::post('print', [GenerateImage::class, 'print']);
Route::get('recoverFamilias', RecoverFamilias::class);
Route::get('recoverAllFamilias', RecoverAllFamilias::class);
Route::get('recoverPdf', RecoverPdf::class);
Route::get('recoverPdfByStore', RecoverPdfByStore::class);
Route::get('recoverPdfTemplate', RecoverPdfTemplate::class);
//Route::get('recoverPdfTemplateByStore', RecoverPdfTemplatesByStore::class);
Route::get('recoverPromotions', RecoverPromotions::class);
Route::get('recoverTemplates', RecoverTemplates::class);
Route::get('recoverTemplatesProducts', RecoverTemplatesProducts::class);
Route::get('recoverTypePage', RecoverTypePage::class);
