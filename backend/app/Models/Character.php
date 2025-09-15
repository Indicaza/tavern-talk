<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Character extends Model
{
    protected $table = 'characters';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'is_pc',
        'owner_user_id',
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
        'str_score',
        'dex_score',
        'con_score',
        'int_score',
        'wis_score',
        'cha_score',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Generate and persist a portrait (inline). Returns true on success.
     */
    public static function generatePortraitById(string $id): bool
    {
        $c = static::find($id);
        if (! $c) {
            Log::warning('portrait_generate_missing_character', ['id' => $id]);

            return false;
        }
        if ($c->portrait_url) {
            Log::info('portrait_already_exists', ['id' => $id, 'url' => $c->portrait_url]);

            return true;
        }

        $base = rtrim(config('services.openai.base', env('OPENAI_API_BASE', 'https://api.openai.com/v1')), '/');
        $key = config('services.openai.key', env('OPENAI_API_KEY'));
        $imageModel = config('services.openai.image_model', env('OPENAI_IMAGE_MODEL', 'gpt-image-1'));

        $prompt = static::portraitPrompt($c);

        Log::info('portrait_generate_start', [
            'id' => $c->id,
            'model' => $imageModel,
            'base' => $base,
        ]);

        // Use the generations endpoint (this works on your account)
        $resp = Http::withToken($key)
            ->timeout(120)
            ->asJson()
            ->post($base.'/images/generations', [
                'model' => $imageModel,
                'prompt' => $prompt,
                // sizes supported per your error: 1024x1024, 1024x1536, 1536x1024, auto
                'size' => '1024x1024',
                'n' => 1,
                // no response_format param (your logs showed it's rejected)
            ]);

        if ($resp->failed()) {
            Log::warning('portrait_generate_failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            return false;
        }

        $json = $resp->json();
        $first = data_get($json, 'data.0');

        if (! $first) {
            Log::warning('portrait_generate_no_data', ['json' => $json]);

            return false;
        }

        $bin = null;

        if (isset($first['b64_json'])) {
            $bin = base64_decode($first['b64_json'], true);
            if ($bin === false) {
                Log::warning('portrait_b64_decode_failed');

                return false;
            }
            Log::info('portrait_generate_got_b64', ['id' => $c->id]);
        } elseif (isset($first['url'])) {
            $url = $first['url'];
            $img = Http::timeout(120)->get($url);
            if (! $img->successful()) {
                Log::warning('portrait_download_failed', ['status' => $img->status(), 'url' => $url]);

                return false;
            }
            $bin = $img->body();
            Log::info('portrait_generate_downloaded_url', ['id' => $c->id]);
        } else {
            Log::warning('portrait_generate_unknown_payload', ['first' => $first]);

            return false;
        }

        Storage::disk('public')->makeDirectory('portraits');
        $path = 'portraits/'.$c->id.'.png';
        Storage::disk('public')->put($path, $bin);

        $c->portrait_url = asset('storage/'.$path);
        $c->save();

        Log::info('portrait_generate_success', [
            'id' => $c->id,
            'url' => $c->portrait_url,
        ]);

        return true;
    }

    protected static function portraitPrompt(Character $c): string
    {
        $parts = array_filter([
            $c->name,
            trim($c->race.' '.($c->subrace ?? '')),
            $c->class,
            $c->gender,
            $c->age ? $c->age.' years old' : null,
            $c->alignment,
            $c->short_pitch,
        ]);

        return 'Oil-painted Renaissance portrait, dark moody chiaroscuro, painterly brushwork, dramatic shadows, 3/4 view, shoulders-up, soft rim light, neutral textured background, high detail, no text. '
            .implode(', ', $parts);
    }
}
