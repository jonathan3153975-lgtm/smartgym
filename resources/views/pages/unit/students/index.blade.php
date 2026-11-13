@extends('layouts.app')

@section('title', 'Alunos da unidade')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="card space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold">Alunos</h2>
                <p class="mt-1 text-sm text-text-secondary">Consulta, cadastro, acesso, plano e ficha dos alunos da unidade.</p>
            </div>

            @can('students.create')
                <button type="button" onclick="openStudentModal()" class="btn-primary self-start">
                    <i class="fas fa-plus mr-2"></i>
                    Novo aluno
                </button>
            @endcan
        </div>

        <form method="GET" action="{{ route('unit.students.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="label-field" for="student_search">Buscar</label>
                <input type="search" id="student_search" name="search" value="{{ request('search') }}" class="input-field"
                       placeholder="Nome, e-mail, CPF ou telefone">
            </div>
            <div>
                <label class="label-field" for="student_status">Status</label>
                <select id="student_status" name="status" class="input-field">
                    <option value="">Todos</option>
                    <option value="active" @selected(request('status') === 'active')>Ativos</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pendentes</option>
                </select>
            </div>
            <div>
                <label class="label-field" for="student_access">Acesso</label>
                <select id="student_access" name="access" class="input-field">
                    <option value="">Todos</option>
                    <option value="with" @selected(request('access') === 'with')>Com acesso</option>
                    <option value="without" @selected(request('access') === 'without')>Sem acesso</option>
                    <option value="temporary" @selected(request('access') === 'temporary')>Senha temporária</option>
                </select>
            </div>
            <div>
                <label class="label-field" for="student_assessment">Ficha</label>
                <select id="student_assessment" name="assessment" class="input-field">
                    <option value="">Todas</option>
                    <option value="pending" @selected(request('assessment') === 'pending')>Pendente</option>
                    <option value="alert" @selected(request('assessment') === 'alert')>Com alerta PAR-Q</option>
                    <option value="unreviewed" @selected(request('assessment') === 'unreviewed')>A verificar</option>
                </select>
            </div>
            <div>
                <label class="label-field" for="student_plan">Plano</label>
                <select id="student_plan" name="plan" class="input-field">
                    <option value="">Todos</option>
                    <option value="active" @selected(request('plan') === 'active')>Ativo</option>
                    <option value="none" @selected(request('plan') === 'none')>Sem plano</option>
                </select>
            </div>
            <div class="flex items-end gap-2 lg:col-span-6">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter mr-2"></i>
                    Filtrar
                </button>
                <a href="{{ route('unit.students.index') }}" class="btn-outline">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($students->isEmpty())
            <div class="py-10 text-center">
                <i class="fas fa-user-graduate mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhum aluno encontrado</p>
                <p class="mt-1 text-sm text-text-secondary">Ajuste os filtros ou cadastre um novo aluno.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Unidade</th>
                            <th>Acesso</th>
                            <th>Plano</th>
                            <th>Ficha</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php
                                $primaryUnit = $student->units->firstWhere('is_primary') ?? $student->units->first();
                                $activePlan = $student->subscriptions->first();
                                $assessment = $student->assessment;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('students.show', $student) }}" class="font-medium text-cyan hover:underline">
                                        {{ $student->name }}
                                    </a>
                                    <p class="text-xs text-text-secondary">{{ $student->email ?? 'Sem e-mail' }}</p>
                                </td>
                                <td class="text-sm">{{ $primaryUnit?->unit?->name ?? '—' }}</td>
                                <td>
                                    @if ($student->account)
                                        <span class="badge {{ $student->account->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $student->account->status === 'active' ? 'Ativo' : 'Inativo' }}
                                        </span>
                                        @if ($student->account->must_change_password)
                                            <span class="badge badge-warning">Senha temporária</span>
                                        @endif
                                    @else
                                        <span class="badge badge-warning">Sem acesso</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($activePlan)
                                        <span class="badge badge-success">{{ $activePlan->plan_name }}</span>
                                    @else
                                        <span class="badge badge-secondary">Sem plano</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($assessment)
                                        @if ($assessment->reviewed_at)
                                            <span class="badge badge-success">Verificada</span>
                                        @elseif ($assessment->parq_positive)
                                            <span class="badge badge-warning">PAR-Q</span>
                                        @else
                                            <span class="badge badge-info">Preenchida</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Pendente</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $student->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $student->status_label }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('students.show', $student) }}" class="p-2 text-text-secondary hover:text-cyan" title="Ver aluno">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('students.assessment', $student) }}" class="p-2 text-text-secondary hover:text-cyan" title="Ficha">
                                            <i class="fas fa-clipboard-check"></i>
                                        </a>
                                        <a href="{{ route('students.technical-sheet', $student) }}" class="p-2 text-text-secondary hover:text-cyan" title="Ficha técnica">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        @can('students.edit')
                                            <button type="button" onclick="openStudentModal({{ $student->id }})"
                                                    class="p-2 text-text-secondary hover:text-cyan" title="Editar aluno">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 border-t border-custom pt-4">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>

@include('pages.students._modal')

<script>
    window.afterStudentSaved = function () {
        window.location.reload();
    };
</script>
@endsection
