@extends('layouts.app')

@section('title', 'Aluno - '.$student->name)

@section('content')
<div class="container mx-auto max-w-5xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-4">
            @if ($student->avatar_url)
                <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}"
                     class="h-16 w-16 flex-shrink-0 rounded-full border border-custom object-cover">
            @else
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full text-xl font-bold"
                     style="background: var(--color-info-soft); color: var(--color-info);">
                    {{ strtoupper(mb_substr($student->name, 0, 1)) }}
                </div>
            @endif
            <div class="min-w-0">
                <a href="{{ route('students.index') }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar para alunos
                </a>
                <h1 class="mt-1 truncate text-2xl font-bold">{{ $student->name }}</h1>
                <p class="mt-1 text-text-secondary">
                    @if ($student->social_name)
                        {{ $student->social_name }} &middot;
                    @endif
                    {{ $student->cpf ?? 'CPF não informado' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($student->status === 'active')
                <span class="badge badge-success">Ativo</span>
            @elseif ($student->status === 'pending')
                <span class="badge badge-warning">Pendente</span>
            @else
                <span class="badge badge-secondary">Inativo</span>
            @endif

            <a href="{{ route('students.technical-sheet', $student) }}" class="btn-outline">
                <i class="fas fa-chart-line mr-2"></i>
                Ficha técnica
            </a>

            @can('students.edit')
                <button type="button" onclick="openStudentModal({{ $student->id }})" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i>
                    Editar aluno
                </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-id-card text-cyan"></i>
                Dados pessoais
            </h2>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-text-secondary">Nome social</dt>
                    <dd class="mt-1 font-medium">{{ $student->social_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">CPF</dt>
                    <dd class="mt-1 font-medium">{{ $student->cpf ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">Data de nascimento</dt>
                    <dd class="mt-1 font-medium">
                        {{ $student->birth_date?->format('d/m/Y') ?? '—' }}
                        @if ($student->age !== null)
                            <span class="text-sm text-text-secondary">({{ $student->age }} anos)</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">Sexo / gênero</dt>
                    <dd class="mt-1 font-medium">{{ $student->gender_label }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">Origem do cadastro</dt>
                    <dd class="mt-1 font-medium">
                        @php
                            $sources = [
                                'walk_in' => 'Presencial',
                                'online' => 'Online',
                                'referral' => 'Indicação',
                                'campaign' => 'Campanha',
                                'partnership' => 'Parceria',
                                'other' => 'Outro',
                            ];
                        @endphp
                        {{ $sources[$student->source] ?? ($student->source ?: 'Não informada') }}
                    </dd>
                </div>
            </dl>

            @if ($student->admin_notes)
                <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Observações</p>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $student->admin_notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h2 class="mb-4 flex items-center gap-2 font-semibold">
                    <i class="fas fa-building text-purple"></i>
                    Unidades vinculadas
                </h2>

                @if ($student->units->count() > 0)
                    <div class="space-y-2">
                        @foreach ($student->units as $studentUnit)
                            <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium">{{ $studentUnit->unit?->name ?? 'Unidade removida' }}</p>
                                    <p class="text-xs text-text-secondary">
                                        {{ $studentUnit->unit?->region?->name ?? 'Sem região' }}
                                        @if ($studentUnit->starts_at)
                                            &middot; desde {{ $studentUnit->starts_at->format('d/m/Y') }}
                                        @endif
                                    </p>
                                </div>
                                @if ($studentUnit->is_primary)
                                    <span class="badge badge-info self-start sm:self-auto">Principal</span>
                                @else
                                    <span class="badge badge-secondary self-start sm:self-auto">Adicional</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-text-secondary">Nenhuma unidade vinculada.</p>
                @endif
            </div>

            <div class="card">
                <h2 class="mb-4 flex items-center gap-2 font-semibold">
                    <i class="fas fa-address-book text-cyan"></i>
                    Contato e endereço
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-text-secondary">E-mail</p>
                        <p class="mt-1 font-medium">{{ $student->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Telefone</p>
                        <p class="mt-1 font-medium">{{ $student->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">WhatsApp</p>
                        <p class="mt-1 font-medium">{{ $student->whatsapp ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Contato de emergência</p>
                        <p class="mt-1 font-medium">{{ $student->emergency_contact_name ?? '—' }}</p>
                        @if ($student->emergency_contact_phone)
                            <p class="text-sm text-text-secondary">
                                {{ $student->emergency_contact_phone }}
                                @if ($student->emergency_contact_relationship)
                                    &middot; {{ $student->emergency_contact_relationship }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-text-secondary">Endereço</p>
                        @php
                            $address = collect([
                                trim(collect([$student->street, $student->number])->filter()->implode(', ')),
                                $student->complement,
                                $student->neighborhood,
                                trim(collect([$student->city, $student->state])->filter()->implode(' - ')),
                                $student->zip_code ? 'CEP '.$student->zip_code : null,
                            ])->filter()->implode(' · ');
                        @endphp
                        <p class="mt-1 font-medium">{{ $address !== '' ? $address : '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php($assessment = $student->assessment)

    <div class="card">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-clipboard-check text-cyan"></i>
                    Ficha de avaliação
                </h2>
                <p class="mt-1 text-xs text-text-secondary">
                    {{ $student->assessments_count }} avaliação(ões) registrada(s) pelo aluno no portal.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start">
                @if ($assessment?->isCompleted())
                    <span class="badge badge-success">Concluída</span>
                    <a href="{{ route('students.assessment', $student) }}" class="btn-outline">
                        <i class="fas fa-eye mr-2"></i>
                        Ver ficha completa
                    </a>
                @elseif ($assessment)
                    <span class="badge badge-warning">Em preenchimento</span>
                @else
                    <span class="badge badge-secondary">Não preenchida</span>
                @endif
            </div>
        </div>

        @if ($assessment?->isCompleted())
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs text-text-secondary">Objetivo</p>
                    <p class="mt-1 font-medium">{{ $assessment->goal_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Experiência</p>
                    <p class="mt-1 font-medium">{{ $assessment->experience_level_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Frequência semanal</p>
                    <p class="mt-1 font-medium">{{ $assessment->training_frequency }}x</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">IMC</p>
                    <p class="mt-1 font-medium">{{ $assessment->bmi ? number_format($assessment->bmi, 1, ',', '.') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Enviada em</p>
                    <p class="mt-1 font-medium">{{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs text-text-secondary">Prontidão para atividade física</p>
                    <p class="mt-1">
                        @if ($assessment->parq_positive)
                            <span class="badge badge-warning">Atenção: respostas positivas no PAR-Q</span>
                        @else
                            <span class="badge badge-success">Sem alertas no PAR-Q</span>
                        @endif
                    </p>
                </div>
            </div>
        @else
            <p class="text-sm text-text-secondary">
                O aluno ainda não enviou a ficha de avaliação. Ela fica disponível no portal após a assinatura de um plano.
            </p>
        @endif
    </div>

    <div class="card">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-weight-scale text-cyan"></i>
                    Medidas corporais
                </h2>
                <p class="mt-1 text-xs text-text-secondary">Último registro semanal feito pelo aluno no portal.</p>
            </div>

            <a href="{{ route('students.measurements', $student) }}" class="btn-outline self-start">
                <i class="fas fa-chart-line mr-2"></i>
                Ver histórico
            </a>
        </div>

        @if ($latestMeasurement)
            @php($measurementHeight = $student->assessment?->height_cm !== null ? (float) $student->assessment->height_cm : null)
            @php($measurementBmi = $latestMeasurement->bmiForHeight($measurementHeight))
            <div class="grid grid-cols-2 gap-4 text-sm lg:grid-cols-5">
                <div>
                    <p class="text-xs text-text-secondary">Data</p>
                    <p class="mt-1 font-medium">{{ $latestMeasurement->measured_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Peso</p>
                    <p class="mt-1 font-medium">{{ $latestMeasurement->weight_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">IMC</p>
                    <p class="mt-1 font-medium">{{ $measurementBmi ? number_format($measurementBmi, 1, ',', '.') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Gordura</p>
                    <p class="mt-1 font-medium">{{ $latestMeasurement->body_fat_percent ? $latestMeasurement->body_fat_percent.'%' : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Cintura</p>
                    <p class="mt-1 font-medium">{{ $latestMeasurement->waist_cm ? $latestMeasurement->waist_cm.' cm' : '—' }}</p>
                </div>
            </div>
        @else
            <p class="text-sm text-text-secondary">
                O aluno ainda não registrou medidas corporais.
            </p>
        @endif
    </div>

    @php($account = $student->account)

    <div class="card">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-key text-lime"></i>
                    Acesso do aluno
                </h2>
                <p class="mt-1 text-xs text-text-secondary">
                    Credenciais utilizadas pelo aluno no portal da rede.
                </p>
                <p class="mt-1 text-xs" style="color: var(--color-warning);">
                    A senha definida aqui é temporária: o aluno deverá alterá-la no primeiro acesso.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 self-start">
                @if ($account)
                    <span class="badge {{ $account->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                        {{ $account->status === 'active' ? 'Acesso ativo' : 'Acesso inativo' }}
                    </span>
                    @if ($account->must_change_password)
                        <span class="badge badge-warning">Senha temporária</span>
                    @endif
                @else
                    <span class="badge badge-warning">Sem acesso</span>
                @endif
            </div>
        </div>

        @if ($account)
            <div class="mb-4 grid grid-cols-1 gap-3 rounded-lg border border-custom bg-bg-secondary p-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs text-text-secondary">E-mail de acesso</p>
                    <p class="mt-1 font-medium">{{ $account->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Último acesso</p>
                    <p class="mt-1 font-medium">
                        {{ $account->last_login_at?->format('d/m/Y H:i') ?? 'Nunca acessou' }}
                    </p>
                </div>
            </div>
        @endif

        <form action="{{ route('students.access.update', $student) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="access_email" class="label-field">E-mail de acesso <span class="text-red">*</span></label>
                    <input type="email" id="access_email" name="email" required maxlength="255" class="input-field"
                           value="{{ old('email', $account?->email ?? $student->email) }}">
                    @error('email')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="access_status" class="label-field">Status do acesso <span class="text-red">*</span></label>
                    <select id="access_status" name="status" required class="input-field">
                        <option value="active" @selected(old('status', $account?->status ?? 'active') === 'active')>Ativo</option>
                        <option value="inactive" @selected(old('status', $account?->status ?? 'active') === 'inactive')>Inativo</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="access_password" class="label-field">
                        Senha {{ $account ? '' : '*' }}
                    </label>
                    <input type="password" id="access_password" name="password" class="input-field"
                           autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                    @if ($account)
                        <p class="mt-1 text-xs text-text-secondary">Deixe em branco para manter a senha atual.</p>
                    @endif
                    @error('password')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="access_password_confirmation" class="label-field">Confirmar senha</label>
                    <input type="password" id="access_password_confirmation" name="password_confirmation" class="input-field"
                           autocomplete="new-password">
                </div>
            </div>

            <div class="flex justify-end border-t border-custom pt-4">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    {{ $account ? 'Atualizar acesso' : 'Criar acesso' }}
                </button>
            </div>
        </form>
    </div>
</div>

@include('pages.students._modal')
@endsection
