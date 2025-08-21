<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NpcController extends Controller
{
    public function store(Request $request)
    {
        $prompt = $request->input('prompt');
        $withImage = $request->boolean('withImage', false);
        $tags = $request->input('tags', []);

        $attempts = 0;
        $maxAttempts = 3;
        $npcData = null;
        $errors = [];

        while ($attempts < $maxAttempts) {
            $attempts++;

            $response = Http::withToken(config('services.openai.key'))->post(
                config('services.openai.base').'/chat/completions',
                [
                    'model' => config('services.openai.model'),
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'npc_schema',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'race' => ['type' => 'string'],
                                    'subrace' => ['type' => ['string', 'null']],
                                    'class' => ['type' => 'string'],
                                    'level' => ['type' => 'integer'],
                                    'gender' => ['type' => 'string'],
                                    'age' => ['type' => 'integer'],
                                    'alignment' => ['type' => 'string'],
                                    'background' => ['type' => 'string'],
                                    'personality_type' => ['type' => 'string'],
                                    'bio' => ['type' => 'string'],
                                    'short_pitch' => ['type' => 'string'],
                                    'portrait_url' => ['type' => ['string', 'null']],
                                ],
                                'required' => [
                                    'name',
                                    'race',
                                    'subrace',
                                    'class',
                                    'level',
                                    'gender',
                                    'age',
                                    'alignment',
                                    'background',
                                    'personality_type',
                                    'bio',
                                    'short_pitch',
                                    'portrait_url',
                                ],
                            ],
                        ],
                    ],

                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a game NPC generator. Return strictly JSON that matches the schema.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]
            );

            if (! $response->successful()) {
                $errors[] = "OpenAI error (HTTP {$response->status()}): ".$response->body();

                continue;
            }

            $raw = json_decode($response->json('choices.0.message.content') ?? '', true);

            if ($this->validateNpcSchema($raw)) {
                $npcData = $raw;
                break;
            } else {
                $errors[] = "Invalid payload on attempt {$attempts}: ".json_encode($raw);
            }
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
            'portrait_url' => $npcData['portrait_url'] ?? null,
        ]);

        return response()->json($character, 201);
    }

    private function validateNpcSchema(?array $data): bool
    {
        if (! $data || ! is_array($data)) {
            return false;
        }

        $required = ['name', 'race', 'class', 'level', 'gender', 'age', 'alignment', 'background', 'personality_type', 'bio', 'short_pitch'];
        foreach ($required as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    private function userPrompt(?string $prompt): string
    {
        $guidance = $prompt ? "Guidance from user (optional): \"{$prompt}\"" : 'Guidance from user (optional): ""';

        return implode("\n", [
            'Create a single NPC for a 5e fantasy setting.',
            $guidance,
            'Return JSON exactly in this shape:',
            '{ identity, personality, stats, skills, appearance, inventory, tags, hooks }',
            '- identity: name, race, subrace (optional), class, level (1–5), gender, age, alignment (one of: LG, NG, CG, LN, N, CN, LE, NE, CE spelled out), background, is_pc (false), personality_type (MBTI like ISTJ)',
            '- personality: traits[], ideals[], bonds[], flaws[], bio, short_pitch',
            '- stats: str,dex,con,int,wis,cha (8–18)',
            '- skills: array of { name, proficient }',
            '- appearance: hair, eyes, skin, height_cm, weight_kg, features[], outfit, armor, weapon',
            '- inventory: array of { name, qty, value_gp }',
            '- tags: array of short strings',
            '- hooks: 1–3 plot hooks',
            'No additional fields. JSON only.',
        ]);
    }

    private function enforceRequiredForAllProperties(array $node): array
    {
        if (($node['type'] ?? null) === 'object') {
            $props = array_keys($node['properties'] ?? []);
            $node['required'] = $props;
            if (isset($node['properties'])) {
                foreach ($node['properties'] as $k => $v) {
                    $node['properties'][$k] = $this->enforceRequiredForAllProperties($v);
                }
            }
        }
        if (($node['type'] ?? null) === 'array' && isset($node['items'])) {
            $node['items'] = $this->enforceRequiredForAllProperties($node['items']);
        }
        foreach (['oneOf', 'anyOf', 'allOf'] as $comb) {
            if (isset($node[$comb]) && is_array($node[$comb])) {
                foreach ($node[$comb] as $i => $sub) {
                    $node[$comb][$i] = $this->enforceRequiredForAllProperties($sub);
                }
            }
        }

        return $node;
    }

    private function npcSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'identity' => [
                    'type' => 'object',
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
                        'is_pc' => ['type' => 'boolean'],
                        'personality_type' => ['type' => 'string'],
                    ],
                ],
                'personality' => [
                    'type' => 'object',
                    'properties' => [
                        'traits' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'ideals' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'bonds' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'flaws' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'bio' => ['type' => 'string'],
                        'short_pitch' => ['type' => 'string'],
                    ],
                ],
                'stats' => [
                    'type' => 'object',
                    'properties' => [
                        'str' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                        'dex' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                        'con' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                        'int' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                        'wis' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                        'cha' => ['type' => 'integer', 'minimum' => 8, 'maximum' => 18],
                    ],
                ],
                'skills' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'proficient' => ['type' => 'boolean'],
                        ],
                    ],
                    'maxItems' => 18,
                ],
                'appearance' => [
                    'type' => 'object',
                    'properties' => [
                        'hair' => ['type' => 'string'],
                        'eyes' => ['type' => 'string'],
                        'skin' => ['type' => 'string'],
                        'height_cm' => ['type' => 'integer', 'minimum' => 60, 'maximum' => 260],
                        'weight_kg' => ['type' => 'integer', 'minimum' => 20, 'maximum' => 300],
                        'features' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'outfit' => ['type' => 'string'],
                        'armor' => ['type' => 'string'],
                        'weapon' => ['type' => 'string'],
                    ],
                ],
                'inventory' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'qty' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 99],
                            'value_gp' => ['type' => ['number', 'integer'], 'minimum' => 0],
                        ],
                    ],
                    'maxItems' => 12,
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 30],
                    'maxItems' => 10,
                ],
                'hooks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'minLength' => 1],
                    'minItems' => 1,
                    'maxItems' => 3,
                ],
            ],
        ];
    }

    private function ensureAdditionalPropertiesFalse(array $node): array
    {
        if (isset($node['type']) && $node['type'] === 'object') {
            $node['additionalProperties'] = false;
            if (isset($node['properties']) && is_array($node['properties'])) {
                foreach ($node['properties'] as $k => $v) {
                    $node['properties'][$k] = $this->ensureAdditionalPropertiesFalse($v);
                }
            }
        }
        if (isset($node['type']) && $node['type'] === 'array' && isset($node['items'])) {
            $node['items'] = $this->ensureAdditionalPropertiesFalse($node['items']);
        }
        foreach (['oneOf', 'anyOf', 'allOf'] as $comb) {
            if (isset($node[$comb]) && is_array($node[$comb])) {
                foreach ($node[$comb] as $i => $sub) {
                    $node[$comb][$i] = $this->ensureAdditionalPropertiesFalse($sub);
                }
            }
        }

        return $node;
    }

    private function callOpenAIForJson(string $system, string $user, array $jsonSchema): ?array
    {
        $apiKey = env('OPENAI_API_KEY');
        $base = rtrim(env('OPENAI_API_BASE', 'https://api.openai.com/v1'), '/');
        $model = env('OPENAI_MODEL', 'gpt-4.1-mini');

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'npc_schema',
                    'strict' => true,
                    'schema' => $jsonSchema,
                ],
            ],
            'temperature' => 0.2,
        ];

        $resp = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->timeout(90)
            ->asJson()
            ->post($base.'/chat/completions', $payload);

        if ($resp->failed()) {
            \Log::error('openai_chat_completions_failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'openai' => 'OpenAI call failed: '.$resp->status().' '.$resp->body(),
            ]);
        }

        $body = $resp->json();
        $text = $body['choices'][0]['message']['content'] ?? null;
        if (! $text) {
            \Log::error('openai_chat_no_text', ['body' => $body]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'openai' => 'OpenAI returned no content',
            ]);
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            \Log::error('openai_chat_bad_json', ['text' => $text]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'openai' => 'OpenAI returned non-JSON',
            ]);
        }

        return $decoded;
    }

    private function maybeGeneratePortraitUrl(array $npc): ?string
    {
        $apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
        $base = rtrim(config('services.openai.base', env('OPENAI_API_BASE', 'https://api.openai.com/v1')), '/');
        $imageModel = config('services.openai.image_model', env('OPENAI_IMAGE_MODEL', 'gpt-image-1'));

        $identity = $npc['identity'] ?? [];
        $appearance = $npc['appearance'] ?? [];

        $prompt = sprintf(
            'Portrait, %s %s NPC, %s; %s, %s, skin %s; style: semi-realistic, studio headshot, soft rim light, neutral background; no watermark;',
            $identity['race'] ?? 'human',
            $identity['gender'] ?? 'person',
            $appearance['outfit'] ?? 'simple clothes',
            $appearance['hair'] ?? 'short hair',
            $appearance['eyes'] ?? 'brown eyes',
            $appearance['skin'] ?? 'light'
        );

        $resp = Http::withToken($apiKey)->post($base.'/images', [
            'model' => $imageModel,
            'prompt' => $prompt,
            'size' => '512x512',
            'response_format' => 'b64_json',
        ]);

        if ($resp->failed()) {
            return null;
        }

        $json = $resp->json();
        $b64 = $json['data'][0]['b64_json'] ?? null;
        if (! $b64) {
            return null;
        }

        $bin = base64_decode($b64);
        if (! $bin) {
            return null;
        }

        Storage::disk('public')->makeDirectory('portraits');
        $filename = 'portraits/'.Str::uuid()->toString().'.png';
        Storage::disk('public')->put($filename, $bin);

        return asset('storage/'.$filename);
    }
}
