<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $table = 'chats';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'npc_id',
        'title',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Chat $chat) {
            if (empty($chat->id)) {
                $chat->id = (string) Str::uuid();
            }
        });
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'npc_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }
}
