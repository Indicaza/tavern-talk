<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $q = Chat::query()
            ->with(['npc:id,name,portrait_url,class,level,race,alignment'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'npc_id' => 'required|string|exists:characters,id',
            'title' => 'nullable|string|max:160',
        ]);

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'npc_id' => $validated['npc_id'],
            'title' => $validated['title'] ?? null,
            'last_message_at' => now(),
        ]);

        ChatMessage::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'role' => 'system',
            'content' => 'system-initialized',
        ]);

        return response()->json($chat->load('npc'), 201);
    }

    public function show(string $id)
    {
        $chat = Chat::with(['npc'])->findOrFail($id);

        return response()->json($chat);
    }

    public function update(Request $request, string $id)
    {
        $chat = Chat::findOrFail($id);
        $validated = $request->validate([
            'title' => 'nullable|string|max:160',
        ]);
        $chat->update(['title' => $validated['title'] ?? null]);

        return response()->json($chat);
    }

    public function destroy(string $id)
    {
        $chat = Chat::findOrFail($id);
        $chat->messages()->delete();
        $chat->delete();

        return response()->json(['success' => true]);
    }

    public function messages(string $id)
    {
        $chat = Chat::with('npc')->findOrFail($id);
        $msgs = ChatMessage::where('chat_id', $chat->id)
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'chat_id' => $m->chat_id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => $m->created_at?->toISOString(),
                ];
            });

        return response()->json(['chat' => $chat, 'messages' => $msgs]);
    }

    public function send(Request $request, string $id)
    {
        $chat = Chat::with('npc')->findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|min:1',
        ]);

        $userMsg = ChatMessage::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        $recent = ChatMessage::where('chat_id', $chat->id)
            ->orderByDesc('created_at')
            ->take(12)
            ->get()
            ->reverse()
            ->values();

        $history = [];
        foreach ($recent as $m) {
            if ($m->role === 'system') {
                continue;
            }
            $history[] = [
                'role' => $m->role === 'npc' ? 'assistant' : $m->role,
                'content' => $m->content,
            ];
        }

        $system = $this->systemPromptForNpc($chat->npc);

        $apiKey = env('OPENAI_API_KEY');
        $base = rtrim(env('OPENAI_API_BASE', 'https://api.openai.com/v1'), '/');
        $model = env('OPENAI_MODEL', 'gpt-4.1-mini');

        $payload = [
            'model' => $model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $system]],
                $history,
                [['role' => 'user', 'content' => $validated['message']]]
            ),
            'temperature' => 0.7,
        ];

        $resp = Http::withToken($apiKey)
            ->timeout(90)
            ->asJson()
            ->post($base.'/chat/completions', $payload);

        if ($resp->failed()) {
            $text = '…the winds are fickle today. Try again.';
        } else {
            $text = $resp->json('choices.0.message.content') ?? '';
        }

        $npcMsg = ChatMessage::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'role' => 'npc',
            'content' => $text,
        ]);

        $chat->update(['last_message_at' => now()]);

        return response()->json([
            'user' => [
                'id' => $userMsg->id,
                'content' => $userMsg->content,
                'created_at' => $userMsg->created_at?->toISOString(),
            ],
            'npc' => [
                'id' => $npcMsg->id,
                'content' => $npcMsg->content,
                'created_at' => $npcMsg->created_at?->toISOString(),
            ],
        ]);
    }

    private function systemPromptForNpc(Character $c): string
    {
        $parts = [
            'You are an NPC in a D&D 5e style world. Stay in character. Be concise and vivid.',
            'If asked out-of-character questions, answer briefly then return to roleplay.',
            'Format short paragraphs. Avoid markdown tables.',
            'Name: '.$c->name,
            'Race: '.$c->race.($c->subrace ? ' ('.$c->subrace.')' : ''),
            'Class: '.$c->class.' Level: '.$c->level,
            'Gender: '.$c->gender.' Age: '.$c->age,
            'Alignment: '.$c->alignment,
            'Background: '.$c->background,
            'Personality: '.$c->personality_type,
            'Pitch: '.$c->short_pitch,
            'Bio: '.$c->bio,
        ];

        return implode("\n", $parts);
    }
}
