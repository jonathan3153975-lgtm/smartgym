@extends('layouts.student')

@section('title', 'Minhas medidas')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Evolução corporal</p>
                <h1 class="mt-1 text-2xl font-bold">Minhas medidas</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Registre semanalmente seu peso e medidas para acompanhar sua evolução.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('student.portal.workouts.evolution', $network) }}" class="btn-outline">
                    <i class="fas fa-chart-line mr-2"></i>
                    Evolução dos treinos
                </a>
                <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-outline">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Ficha de avaliação
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Peso atual</p>
            <p class="mt-1 text-2xl font-bold">{{ $latest ? $latest->weight_label : '—' }}</p>
            <p class="mt-1 text-xs text-text-secondary">
                @if ($weightDelta !== null)
                    {{ $weightDelta > 0 ? '+' : '' }}{{ number_format($weightDelta, 2, ',', '.') }} kg desde a última
                @else
                    Primeiro registro
                @endif
            </p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Variação total</p>
            <p class="mt-1 text-2xl font-bold">
                @if ($weightDeltaTotal !== null)
                    {{ $weightDeltaTotal > 0 ? '+' : '' }}{{ number_format($weightDeltaTotal, 2, ',', '.') }} kg
                @else
                    —
                @endif
            </p>
            <p class="mt-1 text-xs text-text-secondary">desde o primeiro registro</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">IMC</p>
            <p class="mt-1 text-2xl font-bold">
                {{ $latestBmi !== null ? number_format($latestBmi, 1, ',', '.') : '—' }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">
                @if ($latestBmi !== null)
                    usando altura de {{ number_format($heightCm, 0, ',', '.') }} cm
                @else
                    <a href="{{ route('student.portal.assessment.show', $network) }}" class="text-cyan hover:underline">
                        Informe sua altura na ficha de avaliação
                    </a>
                @endif
            </p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Último registro</p>
            <p class="mt-1 text-2xl font-bold">{{ $latest ? $latest->measured_at->format('d/m') : '—' }}</p>
            <p class="mt-1 text-xs text-text-secondary">
                {{ $latest && $latest->body_fat_percent ? $latest->body_fat_percent.'% de gordura' : 'Gordura não informada' }}
            </p>
        </div>
    </div>

    @if ($chart['count'] > 0)
        <div class="card">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-chart-line text-cyan"></i>
                    Evolução do peso
                </h2>
                <span class="badge badge-info">últimos {{ $chart['count'] }} registros</span>
            </div>

            <svg viewBox="0 0 600 200" class="mt-4 w-full" role="img" aria-label="Gráfico de evolução do peso">
                <line x1="30" y1="30" x2="570" y2="30" stroke="var(--color-border)" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="30" y1="100" x2="570" y2="100" stroke="var(--color-border)" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="30" y1="170" x2="570" y2="170" stroke="var(--color-border)" stroke-width="1" stroke-dasharray="4 4" />

                <polyline
                    points="{{ collect($chart['points'])->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                    fill="none"
                    stroke="var(--color-primary)"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round" />

                @foreach ($chart['points'] as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="var(--color-primary)">
                        <title>{{ $point['label'] }}: {{ number_format($point['weight'], 2, ',', '.') }} kg</title>
                    </circle>
                @endforeach
            </svg>

            <div class="mt-2 flex items-center justify-between text-xs text-text-secondary">
                <span>{{ $chart['points'][0]['label'] }} · {{ number_format($chart['points'][0]['weight'], 1, ',', '.') }} kg</span>
                <span>{{ $chart['points'][count($chart['points']) - 1]['label'] }} · {{ number_format($chart['points'][count($chart['points']) - 1]['weight'], 1, ',', '.') }} kg</span>
            </div>
        </div>
    @endif

    <section class="card space-y-4">
        <div>
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-weight-scale text-cyan"></i>
                Registrar medidas
            </h2>
            <p class="mt-1 text-xs text-text-secondary">
                Registrar novamente a mesma data atualiza o registro existente.
            </p>
        </div>

        <form method="POST" action="{{ route('student.portal.measurements.store', $network) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="measured_at" class="label-field">Data <span class="text-red">*</span></label>
                    <input type="date" id="measured_at" name="measured_at" required max="{{ now()->toDateString() }}"
                           value="{{ old('measured_at', now()->toDateString()) }}" class="input-field">
                </div>

                <div>
                    <label for="weight_kg" class="label-field">Peso (kg) <span class="text-red">*</span></label>
                    <input type="number" step="0.1" id="weight_kg" name="weight_kg" required min="20" max="400"
                           value="{{ old('weight_kg') }}" class="input-field">
                </div>

                <div>
                    <label for="body_fat_percent" class="label-field">Gordura corporal (%)</label>
                    <input type="number" step="0.1" id="body_fat_percent" name="body_fat_percent" min="1" max="70"
                           value="{{ old('body_fat_percent') }}" class="input-field">
                </div>

                <div>
                    <label for="waist_cm" class="label-field">Cintura (cm)</label>
                    <input type="number" step="0.1" id="waist_cm" name="waist_cm" min="20" max="250"
                           value="{{ old('waist_cm') }}" class="input-field">
                </div>

                <div>
                    <label for="hip_cm" class="label-field">Quadril (cm)</label>
                    <input type="number" step="0.1" id="hip_cm" name="hip_cm" min="20" max="250"
                           value="{{ old('hip_cm') }}" class="input-field">
                </div>

                <div>
                    <label for="chest_cm" class="label-field">Tórax (cm)</label>
                    <input type="number" step="0.1" id="chest_cm" name="chest_cm" min="20" max="250"
                           value="{{ old('chest_cm') }}" class="input-field">
                </div>

                <div>
                    <label for="arm_cm" class="label-field">Braço (cm)</label>
                    <input type="number" step="0.1" id="arm_cm" name="arm_cm" min="10" max="100"
                           value="{{ old('arm_cm') }}" class="input-field">
                </div>

                <div>
                    <label for="thigh_cm" class="label-field">Coxa (cm)</label>
                    <input type="number" step="0.1" id="thigh_cm" name="thigh_cm" min="20" max="150"
                           value="{{ old('thigh_cm') }}" class="input-field">
                </div>

                <div>
                    <label for="calf_cm" class="label-field">Panturrilha (cm)</label>
                    <input type="number" step="0.1" id="calf_cm" name="calf_cm" min="10" max="100"
                           value="{{ old('calf_cm') }}" class="input-field">
                </div>
            </div>

            <div>
                <label for="notes" class="label-field">Observações</label>
                <input type="text" id="notes" name="notes" maxlength="1000" class="input-field"
                       value="{{ old('notes') }}" placeholder="Ex.: medida em jejum, pós-treino...">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Salvar medidas
                </button>
            </div>
        </form>
    </section>

    <section class="card space-y-4">
        <h2 class="flex items-center gap-2 font-semibold">
            <i class="fas fa-clock-rotate-left text-cyan"></i>
            Histórico de medidas
        </h2>

        @if ($measurements->isEmpty())
            <p class="py-6 text-center text-sm text-text-secondary">
                Nenhuma medida registrada ainda. Comece pelo formulário acima.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Peso</th>
                            <th>Variação</th>
                            <th>Gordura</th>
                            <th>Cintura</th>
                            <th>Quadril</th>
                            <th>Tórax</th>
                            <th>Braço</th>
                            <th>Coxa</th>
                            <th>Panturrilha</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($measurements as $measurement)
                            @php
                                $previousMeasurement = $measurements->get($loop->index + 1);
                                $delta = $previousMeasurement
                                    ? (float) $measurement->weight_kg - (float) $previousMeasurement->weight_kg
                                    : null;
                            @endphp
                            <tr>
                                <td class="font-medium">{{ $measurement->measured_at->format('d/m/Y') }}</td>
                                <td>{{ $measurement->weight_label }}</td>
                                <td class="{{ $delta !== null && $delta > 0 ? 'text-red' : 'text-green' }}">
                                    @if ($delta !== null)
                                        {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2, ',', '.') }} kg
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $measurement->body_fat_percent ? $measurement->body_fat_percent.'%' : '—' }}</td>
                                <td>{{ $measurement->waist_cm ? $measurement->waist_cm.' cm' : '—' }}</td>
                                <td>{{ $measurement->hip_cm ? $measurement->hip_cm.' cm' : '—' }}</td>
                                <td>{{ $measurement->chest_cm ? $measurement->chest_cm.' cm' : '—' }}</td>
                                <td>{{ $measurement->arm_cm ? $measurement->arm_cm.' cm' : '—' }}</td>
                                <td>{{ $measurement->thigh_cm ? $measurement->thigh_cm.' cm' : '—' }}</td>
                                <td>{{ $measurement->calf_cm ? $measurement->calf_cm.' cm' : '—' }}</td>
                                <td class="text-right">
                                    <form method="POST"
                                          action="{{ route('student.portal.measurements.destroy', [$network, $measurement]) }}"
                                          onsubmit="return confirm('Remover este registro de medidas?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                                title="Remover">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
