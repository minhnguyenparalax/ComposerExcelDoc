<?php

use App\Http\Controllers\ExcelController;
use App\Http\Controllers\DocController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExcelController::class, 'viewExcelFiles'])->name('file.index');
Route::post('/excel/add', [ExcelController::class, 'addExcel'])->name('excel.addExcel');
Route::delete('/excel/remove/{id}', [ExcelController::class, 'removeExcel'])->name('excel.removeExcel');
Route::get('/excel/read/{fileId}/{sheetId}', [ExcelController::class, 'readSheet'])->name('excel.readSheet');
Route::post('/doc/add', [DocController::class, 'addDoc'])->name('doc.addDoc');
Route::post('/doc/remove', [DocController::class, 'removeDoc'])->name('doc.removeDoc');
Route::get('/doc/read/{docId}', [DocController::class, 'readDoc'])->name('doc.readDoc');