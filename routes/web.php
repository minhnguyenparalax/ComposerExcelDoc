<?php

use App\Http\Controllers\ExcelController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\ExcelDocMappingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExcelController::class, 'viewExcelFiles'])->name('file.index');
Route::post('/excel/add', [ExcelController::class, 'addExcel'])->name('excel.addExcel');
Route::delete('/excel/remove/{id}', [ExcelController::class, 'removeExcel'])->name('excel.removeExcel');
Route::get('/excel/read/{fileId}/{sheetId}', [ExcelController::class, 'readSheet'])->defaults('action', 'view')->name('excel.readSheet');
Route::get('/excel/select/{fileId}/{sheetId}', [ExcelController::class, 'readSheet'])->defaults('action', 'select')->name('excel.selectSheet');

Route::get('/files', [DocController::class, 'index'])->name('file.index');


Route::post('/doc/add', [DocController::class, 'addDoc'])->name('doc.addDoc');
Route::post('/doc/remove', [DocController::class, 'removeDoc'])->name('doc.removeDoc');
Route::get('/doc/read/{docId}', [DocController::class, 'readDoc'])->name('doc.readDoc');
Route::post('/excel/create-table-and-insert-data/{fileId}/{sheetId}', [ExcelController::class, 'createTableAndInsertData'])->name('excel.createTableAndInsertData');
Route::get('/doc/select/{docId}', [App\Http\Controllers\DocController::class, 'selectDoc'])->name('doc.selectDoc');
Route::get('/mapping/fields/{variableId}', [ExcelDocMappingController::class, 'getFields'])->name('excel_doc_mapping.getFields');

// Route để lưu ánh xạ
Route::post('/mapping/store', [ExcelDocMappingController::class, 'storeMapping'])->name('excel_doc_mapping.storeMapping');

// Thêm: Route để xóa ánh xạ
Route::post('/mapping/delete', [ExcelDocMappingController::class, 'deleteMapping'])->name('excel_doc_mapping.deleteMapping');
