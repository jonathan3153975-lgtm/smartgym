@extends('layouts.app')

@section('title', 'Turmas e reservas')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="card space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold">Turmas e reservas</h2>
                <p class="mt-1 text-sm text-text-secondary">Ocupação das aulas, reservas do plano e aulas avulsas.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('unit.classes.index') }}" class="flex items-end gap-2">
                    <select name="weekday" class="input-field" onchange="this.form.submit()" aria-label="Dia da semana">
                        @foreach (\App\Models\SchoolClass::WEEKDAYS as $value => $label)
                            <option value="{{ $value }}" @selected((int) $weekday === (int) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                @can('classes.manage')
                    <a href="{{ route('admin.classes.index') }}" class="btn-outline">
                        <i class="fas fa-gear mr-2"></i>
                        Gerenciar turmas
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @can('classes.manage')
        <div class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-calendar-plus text-cyan"></i>
                Nova reserva
            </h2>

            <form method="POST" action="{{ route('unit.class-bookings.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                @csrf
                <div class="sm:col-span-2">
                    <label for="reservation_class" class="label-field">Turma <span class="text-red">*</span></label>
                    <select id="reservation_class" name="class_id" required class="input-field">
                        <option value="">Selecione a turma</option>
                        @foreach ($classOptions as $classOption)
                            <option value="{{ $classOption->id }}">
                                {{ $classOption->name }} ({{ $classOption->weekday_label }} · {{ $classOption->time_label }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reservation_student" class="label-field">Aluno <span class="text-red">*</span></label>
                    <select id="reservation_student" name="student_id" required class="input-field">
                        <option value="">Selecione o aluno</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reservation_type" class="label-field">Tipo <span class="text-red">*</span></label>
                    <select id="reservation_type" name="type" required class="input-field">
                        <option value="plan">Incluída no plano</option>
                        <option value="single">Aula avulsa paga na recepção</option>
                    </select>
                </div>
                <div class="sm:col-span-4 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-calendar-check mr-2"></i>
                        Registrar reserva
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="space-y-3">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
            <i class="fas fa-calendar-day text-cyan"></i>
            Aulas do dia selecionado
        </h2>

        @if ($classes->isEmpty())
            <div class="card py-8 text-center text-sm text-text-secondary">Nenhuma turma neste dia.</div>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach ($classes as $class)
                    @php
                        $nextDate = $nextDates[$class->id];
                        $occupied = $classBookings->get($class->id.'-'.$nextDate->format('Y-m-d'))?->count() ?? 0;
                    @endphp
                    <article class="card space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $class->name }}</p>
                                <p class="text-xs text-text-secondary">
                                    {{ $class->time_label }}
                                    &middot; {{ $class->modality?->name ?? 'Sem modalidade' }}
                                    @if ($class->teacher)
                                        &middot; {{ $class->teacher->name }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-text-secondary">{{ $nextDate->format('d/m/Y') }}</p>
                            </div>
                            <span class="badge {{ $occupied >= $class->vacancies ? 'badge-warning' : 'badge-success' }}">
                                {{ $occupied }}/{{ $class->vacancies }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-2 text-sm text-text-secondary">
                            <span>{{ max(0, $class->vacancies - $occupied) }} vaga(s) restante(s)</span>
                            @if ($class->drop_in_price)
                                <span>Avulsa: R$ {{ number_format((float) $class->drop_in_price, 2, ',', '.') }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card space-y-4">
        <h2 class="flex items-center gap-2 font-semibold">
            <i class="fas fa-ticket text-cyan"></i>
            Próximas reservas
        </h2>

        @if ($upcomingBookings->isEmpty())
            <p class="py-6 text-center text-sm text-text-secondary">Nenhuma reserva registrada.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Turma</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($upcomingBookings as $booking)
                            <tr>
                                <td class="font-medium">{{ $booking->student?->name ?? 'Aluno' }}</td>
                                <td class="text-sm">{{ $booking->schoolClass?->name ?? '—' }}</td>
                                <td class="text-sm">{{ $booking->booked_for?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-sm">{{ $booking->type_label }}</td>
                                <td class="text-sm">R$ {{ number_format((float) $booking->amount, 2, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $booking->isConfirmed() ? 'badge-success' : ($booking->status === 'pending' ? 'badge-warning' : 'badge-secondary') }}">
                                        {{ $booking->status_label }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @can('classes.manage')
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($booking->status === 'pending')
                                                <form method="POST" action="{{ route('unit.class-bookings.confirm', $booking) }}">
                                                    @csrf
                                                    <button type="submit" class="p-2 text-text-secondary hover:text-cyan" title="Confirmar pagamento">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if (in_array($booking->status, ['pending', 'confirmed'], true))
                                                <form method="POST" action="{{ route('unit.class-bookings.cancel', $booking) }}"
                                                      onsubmit="return confirm('Cancelar esta reserva e liberar a vaga?');">
                                                    @csrf
                                                    <button type="submit" class="p-2 text-text-secondary hover:text-red" title="Cancelar reserva">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
