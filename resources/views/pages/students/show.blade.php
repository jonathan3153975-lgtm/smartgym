@extends('layouts.app')

@section('title', 'Aluno - '.$student->name)

@section('content')
@php
    $tab = request('tab', 'overview');
    $assessment = $student->assessment;
    $primaryUnit = $student->units->firstWhere('is_primary') ?? $student->units->first();
    $account = $student->account;
    $sources = [
        'walk_in' => 'Presencial',
        'online' => 'Online',
        'referral' => 'Indicação',
        'campaign' => 'Campanha',
        'partnership' => 'Parceria',
        'other' => 'Outro',
    ];
    $address = collect([
        trim(collect([$student->street, $student->number])->filter()->implode(', ')),
        $student->complement,
        $student->neighborhood,
        trim(collect([$student->city, $student->state])->filter()->implode(' - ')),
        $student->zip_code ? 'CEP '.$student->zip_code : null,
    ])->filter()->implode(' · ');
@endphp

<div class="p-4 sm:p-6 space-y-6">
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

            @can('students.edit')
                <button type="button" onclick="openStudentModal({{ $student->id }})" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i>
                    Editar aluno
                </button>
            @endcan
        </div>
    </div>

    @include('pages.students._tabs', [
        'student' => $student,
        'activeTab' => $tab === 'cadastro' ? 'cadastro' : 'overview',
    ])

    @if ($tab === 'cadastro')
        <div class="space-y-6">
            <section class="card space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-id-card text-cyan"></i>
                            Dados cadastrais
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">Informações completas do cadastro do aluno.</p>
                    </div>
                    @can('students.edit')
                        <button type="button" onclick="openStudentModal({{ $student->id }})" class="btn-outline self-start">
                            <i class="fas fa-edit mr-2"></i>
                            Editar cadastro
                        </button>
                    @endcan
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs text-text-secondary">Nome</p>
                        <p class="mt-1 font-medium">{{ $student->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Nome social</p>
                        <p class="mt-1 font-medium">{{ $student->social_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">CPF</p>
                        <p class="mt-1 font-medium">{{ $student->cpf ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Data de nascimento</p>
                        <p class="mt-1 font-medium">
                            {{ $student->birth_date?->format('d/m/Y') ?? '—' }}
                            @if ($student->age !== null)
                                <span class="text-sm text-text-secondary">({{ $student->age }} anos)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Sexo / gênero</p>
                        <p class="mt-1 font-medium">{{ $student->gender_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Origem do cadastro</p>
                        <p class="mt-1 font-medium">{{ $sources[$student->source] ?? ($student->source ?: 'Não informada') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Cadastrado em</p>
                        <p class="mt-1 font-medium">{{ $student->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        <p class="text-xs text-text-secondary">{{ $student->createdBy?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Última atualização</p>
                        <p class="mt-1 font-medium">{{ $student->updated_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        <p class="text-xs text-text-secondary">{{ $student->updatedBy?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Status</p>
                        <p class="mt-1">
                            @if ($student->status === 'active')
                                <span class="badge badge-success">Ativo</span>
                            @elseif ($student->status === 'pending')
                                <span class="badge badge-warning">Pendente</span>
                            @else
                                <span class="badge badge-secondary">Inativo</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if ($student->admin_notes)
                    <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                        <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Observações internas</p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ $student->admin_notes }}</p>
                    </div>
                @endif
            </section>

            <section class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-building text-purple"></i>
                    Unidades vinculadas
                </h2>

                @if ($student->units->isNotEmpty())
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
            </section>

            <section class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
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
                        <p class="mt-1 font-medium">{{ $address !== '' ? $address : '—' }}</p>
                    </div>
                </div>
            </section>

            @can('students.edit')
                @include('pages.students._access-form')
            @endcan
        </div>
    @else
        <div class="space-y-6">
            <section class="card space-y-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-key text-cyan"></i>
                        Acesso atual
                    </h2>
                    <span class="text-xs text-text-secondary">{{ now()->format('d/m/Y') }}</span>
                </div>

                @if ($plans !== [])
                    <div class="space-y-2">
                        @foreach ($plans as $plan)
                            <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge badge-success">Plano ativo</span>
                                    <span class="font-medium">{{ $plan['name'] }}</span>
                                    @if ($plan['ends_at'])
                                        <span class="text-xs text-text-secondary">Válido até {{ $plan['ends_at'] }}</span>
                                    @endif
                                </div>
                                <button type="button" onclick="openPlanDetailsModal({{ $plan['id'] }})" class="btn-outline self-start sm:self-auto">
                                    <i class="fas fa-circle-info mr-2"></i>
                                    Detalhes do plano
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-text-secondary">Sem plano ativo no momento.</p>
                @endif

                @if ($access['day_pass'] || $access['classes'] !== [] || $access['trial_checkin'])
                    <div class="flex flex-wrap gap-2 border-t border-custom pt-3">
                        @if ($access['day_pass'])
                            <span class="badge badge-info">Diária adquirida hoje</span>
                        @endif
                        @foreach ($access['classes'] as $class)
                            <span class="badge badge-info">Aula agendada hoje: {{ $class }}</span>
                        @endforeach
                        @if ($access['trial_checkin'])
                            <span class="badge badge-warning">Check-in experimental hoje</span>
                        @endif
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <section class="card space-y-4 md:col-span-4">
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
                            <dd class="mt-1 font-medium">{{ $sources[$student->source] ?? ($student->source ?: 'Não informada') }}</dd>
                        </div>
                    </dl>

                    @if ($student->admin_notes)
                        <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                            <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Observações</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ $student->admin_notes }}</p>
                        </div>
                    @endif
                </section>

                <div class="space-y-6 md:col-span-8">
                    <section class="card space-y-3">
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-location-dot text-purple"></i>
                            Unidade de referência
                        </h2>

                        @if ($primaryUnit)
                            <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                                <p class="font-medium">{{ $primaryUnit->unit?->name ?? 'Unidade removida' }}</p>
                                <p class="mt-1 text-xs text-text-secondary">
                                    {{ $primaryUnit->unit?->region?->name ?? 'Sem região' }}
                                    @if ($primaryUnit->starts_at)
                                        &middot; desde {{ $primaryUnit->starts_at->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                        @else
                            <p class="text-sm text-text-secondary">Nenhuma unidade de referência definida.</p>
                        @endif
                    </section>

                    <section class="card space-y-4">
                        <h2 class="flex items-center gap-2 font-semibold">
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
                                <p class="mt-1 font-medium">{{ $address !== '' ? $address : '—' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <section class="card space-y-4" id="avaliacao">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-clipboard-check text-cyan"></i>
                                Ficha de avaliação
                            </h2>
                            <p class="mt-1 text-xs text-text-secondary">
                                {{ $student->assessments_count }} avaliação(ões) registrada(s).
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 self-start">
                            @if ($assessment?->isCompleted())
                                <span class="badge badge-success">Concluída</span>
                                @if ($assessment->reviewed_at)
                                    <span class="badge badge-success">Verificada</span>
                                @else
                                    <span class="badge badge-warning">A verificar</span>
                                @endif
                            @elseif ($assessment)
                                <span class="badge badge-warning">Em preenchimento</span>
                            @else
                                <span class="badge badge-secondary">Não preenchida</span>
                            @endif
                        </div>
                    </div>

                    @if ($assessment?->reviewed_at)
                        <p class="text-xs text-text-secondary">
                            <i class="fas fa-user-check mr-1 text-green"></i>
                            Verificada por {{ $assessment->reviewedBy?->name ?? 'equipe' }}
                            em {{ $assessment->reviewed_at->format('d/m/Y H:i') }}.
                        </p>
                    @endif

                    @if ($assessment?->isCompleted())
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-secondary">Objetivo</dt>
                                <dd class="text-right font-medium">{{ $assessment->goal_label }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-secondary">Experiência</dt>
                                <dd class="text-right font-medium">{{ $assessment->experience_level_label }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-secondary">Frequência semanal</dt>
                                <dd class="text-right font-medium">{{ $assessment->training_frequency }}x</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-text-secondary">Enviada em</dt>
                                <dd class="text-right font-medium">{{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-text-secondary">Prontidão (PAR-Q)</dt>
                                <dd class="text-right">
                                    @if ($assessment->parq_positive)
                                        <span class="badge badge-warning">Atenção</span>
                                    @else
                                        <span class="badge badge-success">Sem alertas</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        @if ($assessment->trainer_notes)
                            <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                                <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Observação do treinador</p>
                                <p class="mt-1 whitespace-pre-line text-sm">{{ $assessment->trainer_notes }}</p>
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2 border-t border-custom pt-3">
                            @if (auth()->user()?->canVerifyAssessments())
                                <a href="{{ route('students.assessments.verify', [$student, $assessment]) }}" class="btn-primary">
                                    <i class="fas fa-user-check mr-2"></i>
                                    {{ $assessment->reviewed_at ? 'Revisar verificação' : 'Verificar avaliação' }}
                                </a>
                            @endif
                            <a href="{{ route('students.assessment', $student) }}" class="btn-outline">
                                <i class="fas fa-eye mr-2"></i>
                                Ver ficha completa
                            </a>
                        </div>
                    @else
                        <p class="text-sm text-text-secondary">
                            O aluno ainda não enviou a ficha de avaliação. Ela fica disponível no portal após a assinatura de um plano.
                        </p>
                    @endif
                </section>

                <section class="card space-y-4" id="medidas">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-weight-scale text-cyan"></i>
                                Medidas corporais
                            </h2>
                            <p class="mt-1 text-xs text-text-secondary">
                                Comparativo entre a avaliação atual e a anterior.
                            </p>
                        </div>
                    </div>

                    @if ($assessmentMetrics !== [])
                        <div class="overflow-x-auto">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>Medida</th>
                                        <th class="text-right">Atual</th>
                                        <th class="text-right">Variação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assessmentMetrics as $metric)
                                        <tr>
                                            <td class="font-medium">{{ $metric['label'] }}</td>
                                            <td class="text-right font-semibold">{{ $metric['value'] }}</td>
                                            <td class="text-right text-sm">
                                                @if ($metric['percent_label'])
                                                    <span class="{{ $metric['direction'] === 'up' ? 'text-red' : ($metric['direction'] === 'down' ? 'text-green' : 'text-text-secondary') }}">
                                                        @if ($metric['direction'] === 'up')
                                                            <i class="fas fa-arrow-up mr-1"></i>
                                                        @elseif ($metric['direction'] === 'down')
                                                            <i class="fas fa-arrow-down mr-1"></i>
                                                        @endif
                                                        {{ $metric['percent_label'] }}
                                                    </span>
                                                @else
                                                    <span class="text-text-secondary">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($previousAssessment)
                            <p class="text-xs text-text-secondary">
                                Comparado à avaliação de {{ $previousAssessment->submitted_at?->format('d/m/Y') ?? '—' }}.
                            </p>
                        @else
                            <p class="text-xs text-text-secondary">Primeira avaliação registrada, sem comparativo anterior.</p>
                        @endif
                    @else
                        <p class="text-sm text-text-secondary">
                            O aluno ainda não possui medidas registradas em ficha de avaliação.
                        </p>
                    @endif

                    @if ($latestMeasurement)
                        <p class="text-xs text-text-secondary">
                            Último registro de medidas corporais: {{ $latestMeasurement->measured_at->format('d/m/Y') }}
                            ({{ $latestMeasurement->weight_label }}).
                        </p>
                    @endif
                </section>
            </div>
        </div>
    @endif
</div>

@foreach ($plans as $plan)
    <div id="planDetailsModal-{{ $plan['id'] }}" data-plan-modal="{{ $plan['id'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="planDetailsTitle-{{ $plan['id'] }}">
        <div class="modal-content flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="planDetailsTitle-{{ $plan['id'] }}" class="text-xl font-bold">{{ $plan['name'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">Detalhes do acesso atual do aluno.</p>
                </div>
                <button type="button" onclick="closePlanDetailsModal({{ $plan['id'] }})"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Tipo</p>
                        <p class="mt-1 font-medium">{{ $plan['type'] }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Período</p>
                        <p class="mt-1 font-medium">{{ $plan['period'] }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Valor</p>
                        <p class="mt-1 font-medium">{{ $plan['price'] }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Validade</p>
                        <p class="mt-1 font-medium">{{ $plan['ends_at'] ?? 'Sem data final' }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Acessos/semana</p>
                        <p class="mt-1 font-medium">{{ $plan['weekly_accesses'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Acessos/dia</p>
                        <p class="mt-1 font-medium">{{ $plan['daily_checkins'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Check-in antecipado</p>
                        <p class="mt-1 font-medium">{{ $plan['checkin_advance_minutes'] ?? '—' }} min</p>
                    </div>
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <p class="text-xs text-text-secondary">Início</p>
                        <p class="mt-1 font-medium">{{ $plan['starts_at'] ?? '—' }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-custom p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Unidades acessíveis</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($plan['units'] as $unitName)
                            <span class="badge badge-info">{{ $unitName }}</span>
                        @empty
                            <span class="text-sm text-text-secondary">Não informado.</span>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-custom p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Modalidades</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($plan['modalities'] as $modalityName)
                            <span class="badge badge-secondary">{{ $modalityName }}</span>
                        @empty
                            <span class="text-sm text-text-secondary">Não informado.</span>
                        @endforelse
                    </div>
                </div>

                @if ($plan['includes_classes'])
                    <div class="rounded-xl border border-custom p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Turmas e dias</p>
                            <span class="badge badge-info">{{ count($plan['classes']) }} turma(s)</span>
                        </div>

                        @if ($plan['classes'] !== [])
                            <div class="mt-3 space-y-2">
                                @foreach ($plan['classes'] as $class)
                                    <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <p class="font-medium">{{ $class['name'] }}</p>
                                            <p class="text-xs text-text-secondary">
                                                {{ collect([$class['unit'], $class['modality']])->filter()->implode(' · ') }}
                                            </p>
                                        </div>
                                        @if ($class['schedules'] !== [])
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach ($class['schedules'] as $schedule)
                                                    <span class="badge badge-info">{{ $schedule }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mt-1 text-xs text-text-secondary">Sem horário cadastrado.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-text-secondary">Nenhuma turma disponível para as unidades/modalidades do plano.</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closePlanDetailsModal({{ $plan['id'] }})" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

@include('pages.students._modal')

<script>
    (function () {
        window.openPlanDetailsModal = function (id) {
            const modal = document.getElementById('planDetailsModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closePlanDetailsModal = function (id) {
            const modal = document.getElementById('planDetailsModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-plan-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closePlanDetailsModal(modal.dataset.planModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-plan-modal]:not(.hidden)').forEach(function (modal) {
                closePlanDetailsModal(modal.dataset.planModal);
            });
        });
    })();
</script>
@endsection
