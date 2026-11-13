@extends('layouts.app')

@section('title', 'Painel da unidade')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Alunos ativos</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['active_students'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $metrics['new_students'] }} novos neste mês</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Assinaturas ativas</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['active_subscriptions'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $metrics['pending_subscriptions'] }} aguardando pagamento</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Sem acesso ao portal</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['without_access'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">alunos sem conta</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Check-ins experimentais</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['trial_checkins'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">neste mês</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Fichas pendentes</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['assessment_pending'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">alunos sem avaliação</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Fichas a verificar</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['assessment_review'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">aguardando conferência</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Alertas PAR-Q</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['parq_alerts'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">fichas com resposta positiva</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Receita no mês</p>
            <p class="mt-1 text-2xl font-bold">
                R$ {{ number_format($subscriptionRevenue + $classRevenue, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">
                planos R$ {{ number_format($subscriptionRevenue, 2, ',', '.') }}
                &middot; aulas R$ {{ number_format($classRevenue, 2, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card space-y-4 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-calendar-day text-cyan"></i>
                    Turmas de hoje
                </h2>
                <a href="{{ route('unit.classes.index') }}" class="text-sm font-semibold text-cyan hover:underline">
                    Gerenciar
                </a>
            </div>

            @if ($classesToday->isEmpty())
                <p class="py-6 text-center text-sm text-text-secondary">Nenhuma turma programada para hoje.</p>
            @else
                <div class="space-y-2">
                    @foreach ($classesToday as $class)
                        @php($occupied = $bookingsToday->get($class->id)?->count() ?? 0)
                        <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium">{{ $class->name }}</p>
                                <p class="text-xs text-text-secondary">
                                    {{ $class->time_label }}
                                    &middot; {{ $class->modality?->name ?? 'Sem modalidade' }}
                                    @if ($class->teacher)
                                        &middot; {{ $class->teacher->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge {{ $occupied >= $class->vacancies ? 'badge-warning' : 'badge-success' }}">
                                    {{ $occupied }}/{{ $class->vacancies }} reservas
                                </span>
                                <span class="text-xs text-text-secondary">{{ max(0, $class->vacancies - $occupied) }} vagas</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-bolt text-cyan"></i>
                Ações rápidas
            </h2>
            <div class="grid grid-cols-1 gap-2">
                <a href="{{ route('unit.students.index') }}" class="btn-outline justify-start">
                    <i class="fas fa-user-plus mr-2"></i>
                    Cadastrar / consultar aluno
                </a>
                <a href="{{ route('unit.assessments.index') }}" class="btn-outline justify-start">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Verificar fichas
                </a>
                <a href="{{ route('unit.payments.index') }}" class="btn-outline justify-start">
                    <i class="fas fa-credit-card mr-2"></i>
                    Pagamentos e pendências
                </a>
                <a href="{{ route('unit.access.index') }}" class="btn-outline justify-start">
                    <i class="fas fa-key mr-2"></i>
                    Acessos e check-ins
                </a>
                <a href="{{ route('unit.classes.index') }}" class="btn-outline justify-start">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Turmas e reservas
                </a>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-user-plus text-cyan"></i>
                    Últimos alunos
                </h2>
                <a href="{{ route('unit.students.index') }}" class="text-sm font-semibold text-cyan hover:underline">Ver todos</a>
            </div>

            @forelse ($recentStudents as $student)
                <a href="{{ route('students.show', $student) }}"
                   class="flex items-center justify-between gap-3 rounded-xl border border-custom bg-bg-secondary p-3 transition-colors hover:border-cyan">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $student->name }}</p>
                        <p class="truncate text-xs text-text-secondary">{{ $student->email ?? 'Sem e-mail' }}</p>
                    </div>
                    <span class="badge {{ $student->account ? 'badge-success' : 'badge-warning' }}">
                        {{ $student->account ? 'Com acesso' : 'Sem acesso' }}
                    </span>
                </a>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhum aluno cadastrado.</p>
            @endforelse
        </section>

        <section class="card space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-calendar-check text-cyan"></i>
                    Próximas reservas
                </h2>
                <a href="{{ route('unit.classes.index') }}" class="text-sm font-semibold text-cyan hover:underline">Ver todas</a>
            </div>

            @forelse ($recentBookings as $booking)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $booking->student?->name ?? 'Aluno' }}</p>
                        <p class="truncate text-xs text-text-secondary">
                            {{ $booking->schoolClass?->name ?? 'Aula' }}
                            &middot; {{ $booking->booked_for?->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="badge {{ $booking->isConfirmed() ? 'badge-success' : 'badge-warning' }}">
                            {{ $booking->status_label }}
                        </span>
                        <span class="text-xs text-text-secondary">{{ $booking->type_label }}</span>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhuma reserva próxima.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
