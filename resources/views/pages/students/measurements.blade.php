@extends('layouts.app')

@section('title', 'Medidas - '.$student->name)

@section('content')
@php
    $measurements = $student->measurements;
    $latest = $measurements->first();
    $first = $measurements->last();
    $heightCm = $student->assessment?->height_cm !== null ? (float) $student->assessment->height_cm : null;
    $latestBmi = $latest?->bmiForHeight($heightCm);
    $totalDelta = $latest && $first && $latest->id !== $first->id
        ? (float) $latest->weight_kg - (float) $first->weight_kg
        : null;
@endphp

<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Medidas corporais</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('students.assessment', $student) }}" class="btn-outline">
                <i class="fas fa-clipboard-check mr-2"></i>
                Ficha de avaliação
            </a>
            <span class="badge badge-info self-center">{{ $measurements->count() }} registro(s)</span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Peso atual</p>
            <p class="mt-1 text-2xl font-bold">{{ $latest ? $latest->weight_label : '—' }}</p>
            <p class="mt-1 text-xs text-text-secondary">
                {{ $latest ? $latest->measured_at->format('d/m/Y') : 'Sem registros' }}
            </p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Variação total</p>
            <p class="mt-1 text-2xl font-bold">
                @if ($totalDelta !== null)
                    {{ $totalDelta > 0 ? '+' : '' }}{{ number_format($totalDelta, 2, ',', '.') }} kg
                @else
                    —
                @endif
            </p>
            <p class="mt-1 text-xs text-text-secondary">desde o primeiro registro</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">IMC</p>
            <p class="mt-1 text-2xl font-bold">{{ $latestBmi !== null ? number_format($latestBmi, 1, ',', '.') : '—' }}</p>
            <p class="mt-1 text-xs text-text-secondary">
                {{ $heightCm ? 'altura '.number_format($heightCm, 0, ',', '.').' cm' : 'Altura não informada na avaliação' }}
            </p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Gordura / cintura</p>
            <p class="mt-1 text-2xl font-bold">
                {{ $latest?->body_fat_percent ? $latest->body_fat_percent.'%' : '—' }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">
                {{ $latest?->waist_cm ? 'cintura '.$latest->waist_cm.' cm' : 'Cintura não informada' }}
            </p>
        </div>
    </div>

    <div class="card">
        @if ($measurements->isEmpty())
            <div class="py-10 text-center">
                <i class="fas fa-weight-scale mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhuma medida registrada</p>
                <p class="mt-1 text-sm text-text-secondary">O aluno pode registrar as medidas semanais pelo portal.</p>
            </div>
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
                            <th>Observações</th>
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
                                <td class="text-sm text-text-secondary">{{ $measurement->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
