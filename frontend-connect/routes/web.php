<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', function () {
    return view('auth.login');
});


Route::get('/superadmin', function () {
    return view('superadmin.index');
});

Route::get('/superadmin/locales', function () {
    return view('superadmin.locales');
});

Route::get('/superadmin/admins', function () {
    return view('superadmin.admins');
});

Route::get('/superadmin/reportes', function () {
    return view('superadmin.reportes');
});

Route::get('/superadmin/rides', function () {
    return view('superadmin.rides');
});

Route::get('/admin', function () {
    return view('admin.index');
});

Route::get('/admin/usarios', function () {
    return view('admin.usuarios');
});
Route::get('/admin/categorias', function () {
    return view('admin.categorias');
});

Route::get('/admin/subcategorias', function () {
    return view('admin.subcategorias');
});


Route::get('/admin/productos', function () {
    return view('admin.productos');
});

Route::get('/admin/inventario', function () {
    return view('admin.inventario');
});

Route::get('/admin/caja', function () {
    return view('admin.caja');
});

Route::get('/admin/contabilidad', function () {
    return view('admin.contabilidad');
});

Route::get('/admin/gastos', function () {
    return view('admin.gastos');
});

Route::get('/pruebas/sri', function () {
    return view('pruebas.sri');
});
