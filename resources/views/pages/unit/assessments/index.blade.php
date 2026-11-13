@extends('layouts.app')

@section('title', 'Fichas da unidade')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="card space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold">Fichas de avaliação</h2>
                <p class="mt-1 text-sm text-text-secondary">Acompanhe o preenchimento, alertas do PAR-Q e verificação das fichas.</p>
            </div>
            <span class="badge badge-info">{{ $students->total() }} aluno(s)</span>
        </div>

        <form method="GET" action="{{ route('unit.assessments.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label-field" for="assessment_search">Buscar aluno</label>
                <input type="search" id="assessment_search" name="search" value="{{ request('search') }}" class="input-field"
                       placeholder="Nome do aluno">
            </div>
            <div>
                <label class="label-field" for="assessment_filter">Situação</label>
                <select id="assessment_filter" name="filter" class="input-field" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="pending" @selected($filter === 'pending')>Sem ficha</option>
                    <option value="done" @selected($filter === 'done')>Preenchidas</option>
                    <option value="alert" @selected($filter === 'alert')>Com alerta PAR-Q</option>
                    <option value="unreviewed" @selected($filter === 'unreviewed')>A verificar</option>
                    <option value="reviewed" @selected($filter === 'reviewed')>Verificadas</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-end gap-2">
                <button type="submit" class="btn-primary"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                <a href="{{ route('unit.assessments.index') }}" class="btn-outline">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($students->isEmpty())
            <div class="py-10 text-center">
                <i class="fas fa-clipboard-check mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhum aluno encontrado</p>
                <p class="mt-1 text-sm text-text-secondary">Ajuste os filtros para consultar as fichas.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Última avaliação</th>
                            <th>Objetivo</th>
                            <th>Peso / IMC</th>
                            <th>PAR-Q</th>
                            <th>Verificação</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php($assessment = $student->assessment)
                            <tr>
                                <td>
                                    <a href="{{ route('students.show', $student) }}" class="font-medium text-cyan hover:underline">
                                        {{ $student->name }}
                                    </a>
                                    <p class="text-xs text-text-secondary">{{ $student->email ?? 'Sem e-mail' }}</p>
                                </td>
                                <td class="text-sm">
                                    {{ $assessment?->submitted_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-sm">{{ $assessment?->goal_label ?? '—' }}</td>
                                <td class="text-sm">
                                    @if ($assessment)
                                        {{ $assessment->weight_kg ? $assessment->weight_kg.' kg' : '—' }}
                                        @if ($assessment->bmi)
                                            <span class="block text-xs text-text-secondary">IMC {{ number_format($assessment->bmi, 1, ',', '.') }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($assessment)
                                        <span class="badge {{ $assessment->parq_positive ? 'badge-warning' : 'badge-success' }}">
                                            {{ $assessment->parq_positive ? 'Com alerta' : 'Sem alerta' }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Sem ficha</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($assessment?->reviewed_at)
                                        <span class="badge badge-success">Verificada</span>
                                        <p class="text-xs text-text-secondary">
                                            {{ $assessment->reviewedBy?->name ?? 'Equipe' }}
                                            &middot; {{ $assessment->reviewed_at->format('d/m/Y') }}
                                        </p>
                                    @elseif ($assessment)
                                        <span class="badge badge-warning">A verificar</span>
                                    @else
                                        <span class="badge badge-secondary">Aguardando aluno</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        @if ($assessment)
                                            <a href="{{ route('students.assessment', $student) }}" class="btn-outline" title="Ver ficha">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" action="{{ route('unit.assessments.review', $assessment) }}">
                                                @csrf
                                                <button type="submit" class="btn-outline" title="{{ $assessment->reviewed_at ? 'Remover verificação' : 'Verificar ficha' }}">
                                                    <i class="fas {{ $assessment->reviewed_at ? 'fa-rotate-left' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('students.show', $student) }}" class="btn-outline" title="Ver aluno">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('students.technical-sheet', $student) }}" class="btn-outline" title="Ficha técnica">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
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
@endsection
