<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\NpcController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'time' => now()->toISOString(),
    ]);
});

// DB debug helper
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

// --- NPC routes ---
Route::post('/npcs', [NpcController::class, 'store']);
Route::get('/npcs', [NpcController::class, 'index']);
Route::delete('/npcs/{id}', [NpcController::class, 'destroy']);

// Legacy/example route (optional)
Route::get('/characters', fn () => \App\Models\Character::orderByDesc('created_at')->limit(10)->get());

// --- Chat routes ---
Route::get('/chats', [ChatController::class, 'index']);
Route::post('/chats', [ChatController::class, 'store']);
Route::get('/chats/{id}', [ChatController::class, 'show']);
Route::patch('/chats/{id}', [ChatController::class, 'update']);
Route::delete('/chats/{id}', [ChatController::class, 'destroy']);
Route::get('/chats/{id}/messages', [ChatController::class, 'messages']);
Route::post('/chats/{id}/messages', [ChatController::class, 'send']);
