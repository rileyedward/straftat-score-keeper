<?php

namespace App\Http\Controllers;

use App\Actions\UpsertPlayer;
use App\Enums\MatchMode;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $players = Player::query()
            ->when($q !== '', fn ($qb) => $qb->where('name', 'like', '%'.$q.'%'))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'image_path']);

        return response()->json([
            'players' => $players->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image_url' => $p->image_url,
            ])->values(),
        ]);
    }

    public function store(Request $request, UpsertPlayer $upsert): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:players,name'],
        ]);

        $player = $upsert($data['name']);

        return response()->json([
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'image_url' => $player->image_url,
            ],
        ]);
    }

    public function show(Player $player): Response
    {
        $stats = [
            'matches_played' => $player->matchParticipants()->count(),
            'tournaments_played' => $player->tournaments()->count(),
        ];

        return Inertia::render('Players/Show', [
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'image_url' => $player->image_url,
            ],
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:players,name,'.$player->id],
        ]);

        $player->update($data);

        return back();
    }

    public function uploadImage(Request $request, Player $player): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048'],
        ]);

        $disk = config('filesystems.default');
        if ($player->image_path) {
            Storage::disk($disk)->delete($player->image_path);
        }
        $player->image_path = $request->file('image')->store('players', $disk);
        $player->save();

        return back();
    }
}
