<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExerciseImageController extends Controller
{
    public function __invoke(Exercise $exercise): BinaryFileResponse
    {
        $image = $exercise->image;

        abort_unless(
            is_string($image) && str_starts_with($image, 'exercises/'.$exercise->network_id.'/'),
            404
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($image), 404);

        return response()->file($disk->path($image), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
