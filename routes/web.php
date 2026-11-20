<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => config('app.name'),
    'docs' => url('/docs/api'),
    'health' => url('/up'),
]));

Route::view('/docs/swagger', 'docs.swagger')
    ->middleware(RestrictedDocsAccess::class)
    ->name('docs.swagger');
