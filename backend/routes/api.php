<?php

use App\Http\Controllers\NpcController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'time' => now()->toISOString(),
    ]);
});

// NPC CRUD
Route::post('/npcs', [NpcController::class, 'store']);
Route::get('/npcs', [NpcController::class, 'index']);
Route::delete('/npcs/{id}', [NpcController::class, 'destroy']);

// Legacy/example route (keep if needed)
Route::get('/characters', fn () => \App\Models\Character::orderByDesc('created_at')->limit(10)->get());

// DB debug
Route::get('/_dbdebug', function () {
    $host = Config::get('database.connections.pgsql.host');
    $dns = gethostbyname($host);
    try {
        DB::connection()->getPdo();
        $ok = true;
        $err = null;
    } catch (\Throwable $e) {
        $ok = false;
        $err = $e->getMessage();
    }

    return response()->json([
        'env.DB_HOST' => env('DB_HOST'),
        'config.pgsql.host' => $host,
        'dns(host)' => $dns,
        'can_connect' => $ok,
        'error' => $err,
    ]);
});
