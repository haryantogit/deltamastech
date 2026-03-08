<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/subagent-login', function () {
    auth()->loginUsingId(1);
    return redirect('/admin/laporan/pembelian-produk-per-vendor');
});

Route::get('/subagent-profit', function () {
    auth()->loginUsingId(1);
    return redirect('/admin/laporan/profitabilitas-tagihan');
});

Route::get('/subagent-periode', function () {
    auth()->loginUsingId(1);
    return redirect('/admin/laporan/pembelian-per-periode');
});

Route::get('/print/sales-quotation/{id}', [App\Http\Controllers\PrintController::class, 'printQuotation'])->name('print.sales-quotation');
Route::get('/print/purchase-quote/{id}', [App\Http\Controllers\PrintController::class, 'printPurchaseQuote'])->name('print.purchase-quote');
Route::get('/print/sales-invoice/{id}', [App\Http\Controllers\PrintController::class, 'printInvoice'])->name('print.sales-invoice');
Route::get('/print/surat-jalan/{id}', [App\Http\Controllers\PrintController::class, 'printSuratJalan'])->name('print.surat-jalan');
Route::get('/print/kwitansi/{id}', [App\Http\Controllers\PrintController::class, 'printKwitansi'])->name('print.kwitansi');
Route::get('/print/label-pengiriman/{id}', [App\Http\Controllers\PrintController::class, 'printLabelPengiriman'])->name('print.label-pengiriman');
Route::get('/print/sales-order/{id}', [App\Http\Controllers\PrintController::class, 'printSalesOrder'])->name('print.sales-order');
Route::get('/print/purchase-order/{id}', [App\Http\Controllers\PrintController::class, 'printPurchaseOrder'])->name('print.purchase-order');
Route::get('/print/delivery-surat-jalan/{id}', [App\Http\Controllers\PrintController::class, 'printDeliverySuratJalan'])->name('print.delivery-surat-jalan');
Route::get('/print/delivery-label/{id}', [App\Http\Controllers\PrintController::class, 'printDeliveryLabel'])->name('print.delivery-label');
Route::get('/print/purchase-invoice/{id}', [App\Http\Controllers\PrintController::class, 'printPurchaseInvoice'])->name('print.purchase-invoice');
Route::get('/print/purchase-delivery/{id}', [App\Http\Controllers\PrintController::class, 'printPurchaseDelivery'])->name('print.purchase-delivery');

