<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Character extends Model
{
    protected $table = 'characters';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
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
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
