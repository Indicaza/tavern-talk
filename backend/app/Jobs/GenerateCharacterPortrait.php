<?php

namespace App\Jobs;

use App\Models\Character;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCharacterPortrait implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $characterId) {}

    public function backoff(): array
    {
        return [15, 60, 120];
    }

    public function handle(): void
    {
        Log::info('portrait_job_start', ['id' => $this->characterId]);

        $ok = Character::generatePortraitById($this->characterId);

        Log::info('portrait_job_done', ['id' => $this->characterId, 'ok' => $ok]);

        if (! $ok) {
            $this->fail(new \RuntimeException('Portrait generation failed'));
        }
    }
}
