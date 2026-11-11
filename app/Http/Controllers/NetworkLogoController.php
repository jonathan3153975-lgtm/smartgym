<?php

namespace App\Http\Controllers;

use App\Models\Network;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NetworkLogoController extends Controller
{
    /**
     * Stream the logo of a network from the public disk.
     *
     * Serving the image through the application avoids the requirement of a
     * working `public/storage` symlink and keeps the URL on the current host.
     */
    public function __invoke(Network $network): BinaryFileResponse
    {
        $logo = $network->settings['logo'] ?? null;

        abort_unless(
            is_string($logo) && str_starts_with($logo, 'networks/'.$network->id.'/'),
            404
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($logo), 404);

        return response()->file($disk->path($logo), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
