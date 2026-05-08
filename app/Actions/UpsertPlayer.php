<?php

namespace App\Actions;

use App\Models\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpsertPlayer
{
    public function __invoke(string $name, ?UploadedFile $image = null): Player
    {
        $player = Player::firstOrCreate(['name' => $name]);

        if ($image !== null) {
            $disk = config('filesystems.default');
            if ($player->image_path) {
                Storage::disk($disk)->delete($player->image_path);
            }
            $player->image_path = $image->store('players', $disk);
            $player->save();
        }

        return $player->refresh();
    }
}
