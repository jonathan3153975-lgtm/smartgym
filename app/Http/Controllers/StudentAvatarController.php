<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentAvatarController extends Controller
{
    public function __invoke(Student $student): BinaryFileResponse
    {
        $avatar = $student->avatar;

        abort_unless(
            is_string($avatar) && str_starts_with($avatar, 'students/'.$student->network_id.'/'),
            404
        );

        $disk = Storage::disk('public');

        abort_unless($disk->exists($avatar), 404);

        return response()->file($disk->path($avatar), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
