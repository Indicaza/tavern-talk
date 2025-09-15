<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NpcController extends Controller
{
    public function store(Request $request)
    {
        $prompt = trim((string) $request->input('prompt', ''));
        $isRandom = ($prompt === '');

        $attempts = 0;
        $maxAttempts = 3;
        $errors = [];
        $npcData = null;

        while ($attempts < $maxAttempts) {
            $attempts++;

            $payload = [
                'model' => config('services.openai.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a D&D 5e NPC generator. Return STRICT JSON that matches the schema. Level must be between 1 and 5. Stats should be plausible for the class and level (8–18 typical, no dumps below 6). Do not include any fields not in the schema.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $isRandom
                            ? 'Create a creative, memorable NPC for a 5e fantasy setting. Feel free to surprise me.'
                            : $prompt,
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'npc_schema',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'name' => ['type' => 'string', 'minLength' => 1],
                                'race' => ['type' => 'string', 'minLength' => 1],
                                'subrace' => ['type' => ['string', 'null']],
                                'class' => ['type' => 'string', 'minLength' => 1],
                                'level' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                                'gender' => ['type' => 'string'],
                                'age' => ['type' => 'integer', 'minimum' => 10, 'maximum' => 500],
                                'alignment' => ['type' => 'string'],
                                'background' => ['type' => 'string'],
                                'personality_type' => ['type' => 'string'],
                                'bio' => ['type' => 'string'],
                                'short_pitch' => ['type' => 'string'],
                                'appearance_desc' => ['type' => 'string'],
                                'stats' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'str' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                        'dex' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                        'con' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                        'int' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                        'wis' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                        'cha' => ['type' => 'integer', 'minimum' => 6, 'maximum' => 20],
                                    ],
                                    'required' => ['str', 'dex', 'con', 'int', 'wis', 'cha'],
                                ],
                            ],
                            'required' => [
                                'name', 'race', 'subrace', 'class', 'level', 'gender', 'age',
                                'alignment', 'background', 'personality_type', 'bio',
                                'short_pitch', 'appearance_desc', 'stats',
                            ],
                        ],
                    ],
                ],
                'temperature' => $isRandom ? 0.9 : 0.2,
            ];

            $response = Http::withToken(config('services.openai.key'))
                ->timeout(90)
                ->asJson()
                ->post(rtrim(config('services.openai.base'), '/').'/chat/completions', $payload);

            if (! $response->successful()) {
                $errors[] = "OpenAI error (HTTP {$response->status()}): ".$response->body();
                Log::error('npc_generate_openai_failed', ['status' => $response->status(), 'body' => $response->body()]);

                continue;
            }

            $raw = json_decode($response->json('choices.0.message.content') ?? '', true);

            if ($this->validateNpcSchema($raw)) {
                $npcData = $raw;
                break;
            }

            $errors[] = "Invalid payload on attempt {$attempts}: ".json_encode($raw);
            Log::warning('npc_generate_invalid_payload', ['attempt' => $attempts, 'raw' => $raw]);
        }

        if (! $npcData) {
            return response()->json([
                'message' => "Failed to generate valid NPC JSON after {$maxAttempts} attempts.",
                'errors' => $errors,
            ], 422);
        }

        $character = Character::create([
            'is_pc' => false,
            'owner_user_id' => null,
            'name' => $npcData['name'],
            'race' => $npcData['race'],
            'subrace' => $npcData['subrace'] ?? null,
            'class' => $npcData['class'],
            'level' => $npcData['level'],
            'gender' => $npcData['gender'],
            'age' => $npcData['age'],
            'alignment' => $npcData['alignment'],
            'background' => $npcData['background'],
            'personality_type' => $npcData['personality_type'],
            'bio' => $npcData['bio'],
            'short_pitch' => $npcData['short_pitch'],
            'portrait_url' => null,
            'str_score' => (int) data_get($npcData, 'stats.str', 10),
            'dex_score' => (int) data_get($npcData, 'stats.dex', 10),
            'con_score' => (int) data_get($npcData, 'stats.con', 10),
            'int_score' => (int) data_get($npcData, 'stats.int', 10),
            'wis_score' => (int) data_get($npcData, 'stats.wis', 10),
            'cha_score' => (int) data_get($npcData, 'stats.cha', 10),
        ]);

        $ok = false;
        try {
            $ok = \App\Models\Character::generatePortraitById($character->id);
            \Log::info('portrait_inline_attempt', ['character_id' => $character->id, 'ok' => $ok]);
        } catch (\Throwable $e) {
            \Log::error('portrait_inline_exception', ['character_id' => $character->id, 'error' => $e->getMessage()]);
        }

        $driver = config('queue.default', env('QUEUE_CONNECTION', 'sync'));
        if (! $ok && $driver !== 'sync') {
            \App\Jobs\GenerateCharacterPortrait::dispatch($character->id)->onQueue('portraits');
        }

        return response()->json(array_merge($character->toArray(), [
            'appearance_desc' => (string) ($npcData['appearance_desc'] ?? ''),
        ]), 201);
    }

    private function validateNpcSchema(?array $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        $required = [
            'name', 'race', 'subrace', 'class', 'level', 'gender', 'age',
            'alignment', 'background', 'personality_type', 'bio',
            'short_pitch', 'appearance_desc', 'stats',
        ];
        foreach ($required as $k) {
            if (! array_key_exists($k, $data)) {
                return false;
            }
        }

        $stats = $data['stats'] ?? null;
        if (! is_array($stats)) {
            return false;
        }

        foreach (['str', 'dex', 'con', 'int', 'wis', 'cha'] as $k) {
            if (! isset($stats[$k]) || ! is_int($stats[$k])) {
                return false;
            }
            if ($stats[$k] < 6 || $stats[$k] > 20) {
                return false;
            }
        }

        return true;
    }

    public function index()
    {
        return response()->json(Character::orderByDesc('created_at')->get());
    }

    public function show(string $id)
    {
        return response()->json(Character::findOrFail($id));
    }

    public function destroy(string $id)
    {
        $npc = Character::findOrFail($id);
        $npc->delete();

        return response()->json(['success' => true]);
    }
}
