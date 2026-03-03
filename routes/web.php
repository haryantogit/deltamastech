<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/subagent-login', function() { auth()->loginUsingId(1); return redirect('/admin/laporan/pembelian-produk-per-vendor'); });

Route::get('/subagent-profit', function() { auth()->loginUsingId(1); return redirect('/admin/laporan/profitabilitas-tagihan'); });

Route::get('/subagent-periode', function() { auth()->loginUsingId(1); return redirect('/admin/laporan/pembelian-per-periode'); });
