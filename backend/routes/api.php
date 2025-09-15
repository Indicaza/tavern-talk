<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\NpcController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'time' => now()->toISOString(),
    ]);
});

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

// NPCs
Route::post('/npcs', [NpcController::class, 'store']);
Route::get('/npcs', [NpcController::class, 'index']);
Route::get('/npcs/{id}', [NpcController::class, 'show']);
Route::delete('/npcs/{id}', [NpcController::class, 'destroy']);
Route::post('/npcs/{id}/portrait', [\App\Http\Controllers\NpcController::class, 'regeneratePortrait']);

// Chats
Route::get('/chats', [ChatController::class, 'index']);
Route::post('/chats', [ChatController::class, 'store']);
Route::get('/chats/{id}', [ChatController::class, 'show']);
Route::patch('/chats/{id}', [ChatController::class, 'update']);
Route::delete('/chats/{id}', [ChatController::class, 'destroy']);
Route::get('/chats/{id}/messages', [ChatController::class, 'messages']);
Route::post('/chats/{id}/messages', [ChatController::class, 'send']);
