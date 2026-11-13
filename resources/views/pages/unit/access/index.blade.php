@extends('layouts.app')

@section('title', 'Acessos da unidade')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
        <div class="card">
            <p class="text-sm text-text-secondary">Alunos</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['total'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Com acesso</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['with_access'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Sem acesso</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['without_access'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Senha temporária</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['temporary'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Acesso inativo</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['inactive'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Trials no mês</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['trials'] }}</p>
        </div>
    </div>

    <div class="card space-y-4">
        <div>
            <h2 class="text-xl font-bold">Acessos ao portal</h2>
            <p class="mt-1 text-sm text-text-secondary">Contas, senhas temporárias, últimos acessos e check-ins experimentais.</p>
        </div>

        <form method="GET" action="{{ route('unit.access.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label-field" for="access_search">Buscar aluno</label>
                <input type="search" id="access_search" name="search" value="{{ request('search') }}" class="input-field"
                       placeholder="Nome ou e-mail">
            </div>
            <div>
                <label class="label-field" for="access_filter">Acesso</label>
                <select id="access_filter" name="access" class="input-field" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="with" @selected($access === 'with')>Com acesso ativo</option>
                    <option value="without" @selected($access === 'without')>Sem acesso</option>
                    <option value="temporary" @selected($access === 'temporary')>Senha temporária</option>
                    <option value="inactive" @selected($access === 'inactive')>Acesso inativo</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-end gap-2">
                <button type="submit" class="btn-primary"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                <a href="{{ route('unit.access.index') }}" class="btn-outline">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($students->isEmpty())
            <div class="py-10 text-center">
                <i class="fas fa-key mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhum aluno encontrado</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>E-mail de acesso</th>
                            <th>Acesso</th>
                            <th>Senha</th>
                            <th>Último acesso</th>
                            <th>Ficha</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php($account = $student->account)
                            <tr>
                                <td class="font-medium">{{ $student->name }}</td>
                                <td class="text-sm">{{ $account?->email ?? $student->email ?? '—' }}</td>
                                <td>
                                    @if ($account)
                                        <span class="badge {{ $account->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $account->status === 'active' ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    @else
                                        <span class="badge badge-warning">Sem acesso</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($account?->must_change_password)
                                        <span class="badge badge-warning">Temporária</span>
                                    @else
                                        <span class="badge badge-secondary">Definida</span>
                                    @endif
                                </td>
                                <td class="text-sm">{{ $account?->last_login_at?->format('d/m/Y H:i') ?? 'Nunca acessou' }}</td>
                                <td>
                                    @if ($student->assessment)
                                        <span class="badge {{ $student->assessment->reviewed_at ? 'badge-success' : 'badge-info' }}">
                                            {{ $student->assessment->reviewed_at ? 'Verificada' : 'Preenchida' }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Pendente</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('students.show', $student) }}" class="btn-outline">
                                        <i class="fas fa-key mr-1"></i>
                                        Gerenciar acesso
                                    </a>
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

    <div class="card space-y-3">
        <h2 class="flex items-center gap-2 font-semibold">
            <i class="fas fa-bolt text-cyan"></i>
            Check-ins recentes
        </h2>

        @forelse ($recentCheckins as $checkin)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                <div>
                    <p class="font-medium">{{ $checkin->student?->name ?? 'Aluno' }}</p>
                    <p class="text-xs text-text-secondary">{{ $checkin->student?->email ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <span class="badge {{ $checkin->type === 'trial' ? 'badge-warning' : 'badge-info' }}">
                        {{ $checkin->type === 'trial' ? 'Experimental' : ucfirst($checkin->type) }}
                    </span>
                    <p class="text-xs text-text-secondary">{{ $checkin->checked_in_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        @empty
            <p class="py-4 text-center text-sm text-text-secondary">Nenhum check-in registrado.</p>
        @endforelse
    </div>
</div>
@endsection
