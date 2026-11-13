<?php

namespace App\Http\Controllers;

use App\Models\Modality;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ModalityImageController extends Controller
{
    public function __invoke(Modality $modality): BinaryFileResponse
    {
        $image = $modality->image;

        abort_unless(
            is_string($image) && str_starts_with($image, 'modalities/'.$modality->network_id.'/'),
            404
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($image), 404);

        return response()->file($disk->path($image), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
