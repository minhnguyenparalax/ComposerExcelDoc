<?php

use App\Http\Controllers\ExcelController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\ExcelDocMappingController;
use Illuminate\Support\Facades\Route;


//Route Đọc Excel 
Route::get('/', [ExcelController::class, 'viewExcelFiles'])->name('file.index');
Route::post('/excel/add', [ExcelController::class, 'addExcel'])->name('excel.addExcel');
Route::delete('/excel/remove/{id}', [ExcelController::class, 'removeExcel'])->name('excel.removeExcel');
Route::get('/excel/read/{fileId}/{sheetId}', [ExcelController::class, 'readSheet'])->defaults('action', 'view')->name('excel.readSheet');
Route::get('/excel/select/{fileId}/{sheetId}', [ExcelController::class, 'readSheet'])->defaults('action', 'select')->name('excel.selectSheet');
 

//Route Đọc Doc
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

// Thêm: Route để xóa sheet
Route::post('/excel/remove-sheet', [ExcelController::class, 'removeSheet'])->name('excel.removeSheet');

// Thêm: Route để kiểm tra ánh xạ
Route::post('/mapping/check', [ExcelController::class, 'checkMapping'])->name('excel_doc_mapping.checkMapping');

// Thêm: Route để cập nhật primary_key
Route::post('/excel/mapping/set-primary-key', [ExcelDocMappingController::class, 'setPrimaryKey'])->name('excel_doc_mapping.setPrimaryKey');

// Thêm: Route cho ánh xạ và cập nhật doc_name
Route::post('/excel/mapping/map-field', [ExcelDocMappingController::class, 'mapField'])->name('excel_doc_mapping.mapField');
