<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'chat_id',
        'role',
        'content',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (ChatMessage $m) {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
