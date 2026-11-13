<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAccessController extends Controller
{
    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_unless(TenantContext::current()->canAccessStudent($student), 404);

        $account = $student->account;

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('student_accounts', 'email')
                    ->where('network_id', $student->network_id)
                    ->ignore($account?->id),
            ],
            'password' => [
                $account ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data = [
            'network_id' => $student->network_id,
            'student_id' => $student->id,
            'email' => $validated['email'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
            $data['must_change_password'] = true;
        }

        if (! $account) {
            $data['email_verified_at'] = now();
        }

        StudentAccount::updateOrCreate(['student_id' => $student->id], $data);

        return back()->with(
            'success',
            $account ? 'Acesso do aluno atualizado com sucesso.' : 'Acesso do aluno criado com sucesso.'
        );
    }
}
