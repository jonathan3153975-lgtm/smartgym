<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCheckin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCheckinController extends Controller
{
    public function trial(Request $request, Network $network): RedirectResponse
    {
        /** @var StudentAccount|null $account */
        $account = Auth::guard('student')->user();

        abort_unless(
            $account instanceof StudentAccount && (int) $account->network_id === (int) $network->id,
            403
        );

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        if ($student->activeSubscription()) {
            return redirect()
                ->route('student.portal.home', $network)
                ->withErrors(['trial' => 'Você já possui um plano ativo.']);
        }

        if ($student->checkins()->where('type', StudentCheckin::TYPE_TRIAL)->exists()) {
            return redirect()
                ->route('student.portal.home', $network)
                ->withErrors(['trial' => 'Seu check-in experimental já foi utilizado.']);
        }

        $primaryUnit = $student->units()->where('is_primary', true)->first()
            ?? $student->units()->first();

        StudentCheckin::create([
            'network_id' => $network->id,
            'student_id' => $student->id,
            'unit_id' => $primaryUnit?->unit_id,
            'type' => StudentCheckin::TYPE_TRIAL,
            'checked_in_at' => now(),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('student.portal.home', $network)
            ->with('success', 'Check-in experimental registrado! Apresente-se na recepção para liberar o acesso.');
    }
}
