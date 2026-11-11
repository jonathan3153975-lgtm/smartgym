@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6 space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-text-secondary mt-1">Visão geral da sua academia</p>
        </div>
        @can('students.create')
            <a href="{{ route('students.create') }}" class="btn-accent">
                <i class="fas fa-plus mr-2"></i>
                Novo Aluno
            </a>
        @endcan
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-text-secondary text-sm font-medium">Total de Alunos</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['total_students'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: color-mix(in srgb, var(--color-primary) 15%, transparent);">
                    <i class="fas fa-users text-xl" style="color: var(--color-cyan-neon);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-text-secondary text-sm font-medium">Alunos Ativos</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['active_students'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: color-mix(in srgb, var(--color-success) 15%, transparent);">
                    <i class="fas fa-user-check text-xl" style="color: var(--color-green);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-text-secondary text-sm font-medium">Alunos Inativos</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['inactive_students'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: color-mix(in srgb, var(--color-danger) 15%, transparent);">
                    <i class="fas fa-user-slash text-xl" style="color: var(--color-red);"></i>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-text-secondary text-sm font-medium">Novos este Mês</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['new_this_month'] ?? '0' }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: color-mix(in srgb, var(--color-accent) 15%, transparent);">
                    <i class="fas fa-user-plus text-xl" style="color: var(--color-lime-green);"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions & Recent Students --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Quick Actions --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="card">
                <h3 class="font-semibold mb-4">Ações Rápidas</h3>
                <div class="space-y-2">
                    @can('students.create')
                        <a href="{{ route('students.create') }}"
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-surface-elevated transition-colors group border border-transparent hover:border-cyan">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, var(--color-primary) 15%, transparent);">
                                <i class="fas fa-user-plus" style="color: var(--color-cyan-neon);"></i>
                            </div>
                            <div>
                                <p class="font-medium">Cadastrar Aluno</p>
                                <p class="text-xs text-text-secondary">Novo cadastro de aluno</p>
                            </div>
                        </a>
                    @endcan

                    @can('students.index')
                        <a href="{{ route('students.index') }}"
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-surface-elevated transition-colors group border border-transparent hover:border-purple">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, var(--color-secondary) 15%, transparent);">
                                <i class="fas fa-list" style="color: var(--color-purple-neon);"></i>
                            </div>
                            <div>
                                <p class="font-medium">Listar Alunos</p>
                                <p class="text-xs text-text-secondary">Ver todos os alunos</p>
                            </div>
                        </a>
                    @endcan

                    @can('students.index')
                        <a href="{{ route('students.index') }}"
                           class="flex items-center gap-3 p-3 rounded-lg hover:bg-surface-elevated transition-colors group border border-transparent hover:border-lime">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, var(--color-accent) 15%, transparent);">
                                <i class="fas fa-download" style="color: var(--color-lime-green);"></i>
                            </div>
                            <div>
                                <p class="font-medium">Exportar Dados</p>
                                <p class="text-xs text-text-secondary">Baixar relatório</p>
                            </div>
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Network Info --}}
            @if (session('active_network'))
                <div class="card">
                    <h3 class="font-semibold mb-4">Informações da Rede</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-bg-secondary">
                            <i class="fas fa-sitemap text-cyan text-xl"></i>
                            <div>
                                <p class="font-medium">{{ session('active_network.name') }}</p>
                                <p class="text-xs text-text-secondary">{{ session('active_network.document') ?? 'Sem documento' }}</p>
                            </div>
                        </div>
                        @if (session('active_region'))
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-bg-secondary">
                                <i class="fas fa-map-marked-alt text-purple text-xl"></i>
                                <div>
                                    <p class="font-medium">{{ session('active_region.name') }}</p>
                                    <p class="text-xs text-text-secondary">Região ativa</p>
                                </div>
                            </div>
                        @endif
                        @if (session('active_unit'))
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-bg-secondary">
                                <i class="fas fa-dumbbell text-lime text-xl"></i>
                                <div>
                                    <p class="font-medium">{{ session('active_unit.name') }}</p>
                                    <p class="text-xs text-text-secondary">Unidade ativa</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Recent Students --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Alunos Recentes</h3>
                    @can('students.index')
                        <a href="{{ route('students.index') }}" class="text-sm text-cyan hover:underline">Ver todos</a>
                    @endcan
                </div>

                @if (isset($recentStudents) && $recentStudents->count() > 0)
                    <div class="table-container">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>CPF</th>
                                    <th>Unidade</th>
                                    <th>Status</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentStudents as $student)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-r from-cyan-400 to-purple-500">
                                                    @if ($student->avatar)
                                                        <img src="{{ $student->avatar }}" alt="" class="w-full h-full rounded-full object-cover">
                                                    @else
                                                        <span class="text-xs font-bold" style="color: var(--color-bg-primary);">
                                                            {{ strtoupper($student->name[0]) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-medium">{{ $student->name }}</p>
                                                    <p class="text-xs text-text-secondary">{{ $student->email ?? 'Sem e-mail' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-mono text-sm">{{ $student->cpf ?? 'Não informado' }}</td>
                                        <td>
                                            @if ($student->unit)
                                                <span class="text-sm">{{ $student->unit->name }}</span>
                                            @else
                                                <span class="text-text-secondary text-sm">Sem unidade</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($student->status === 'active')
                                                <span class="badge badge-success">Ativo</span>
                                            @else
                                                <span class="badge badge-danger">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @can('students.view', $student)
                                                    <a href="{{ route('students.show', $student) }}"
                                                       class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                                       title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('students.edit', $student)
                                                    <a href="{{ route('students.edit', $student) }}"
                                                       class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-user-graduate text-4xl text-text-secondary mb-4"></i>
                        <h4 class="text-lg font-medium mb-2">Nenhum aluno cadastrado</h4>
                        <p class="text-text-secondary mb-4">Comece cadastrando o primeiro aluno da sua academia</p>
                        @can('students.create')
                            <a href="{{ route('students.create') }}" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                Cadastrar Primeiro Aluno
                            </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection