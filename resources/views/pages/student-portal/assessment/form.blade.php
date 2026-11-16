@extends('layouts.student')

@section('title', 'Ficha de avaliação')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">
                    {{ $isReassessment ? 'Reavaliação' : 'Avaliação inicial' }}
                </p>
                <h1 class="mt-1 text-2xl font-bold">
                    {{ $isReassessment ? 'Nova reavaliação' : 'Ficha de avaliação' }}
                </h1>
                <p class="mt-1 text-sm text-text-secondary">
                    @if ($isReassessment)
                        Os dados da última avaliação foram preenchidos como base. Atualize o que mudou desde então.
                    @else
                        Estas informações ajudam a equipe a montar um treino seguro e adequado ao seu objetivo.
                    @endif
                </p>
            </div>

            @if ($isReassessment)
                <span class="badge badge-warning self-start sm:self-auto">Reavaliação</span>
            @else
                <span class="badge badge-info self-start sm:self-auto">Pendente</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('student.portal.assessment.store', $network) }}" class="space-y-6">
        @csrf

        @include('pages.student-portal.assessment._form')

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ $isReassessment ? route('student.portal.assessment.show', $network) : route('student.portal.home', $network) }}"
               class="btn-outline justify-center">
                Cancelar
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane mr-2"></i>
                {{ $isReassessment ? 'Enviar reavaliação' : 'Enviar ficha de avaliação' }}
            </button>
        </div>
    </form>
</div>
@endsection
