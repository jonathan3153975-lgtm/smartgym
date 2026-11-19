@extends('layouts.app')

@section('title', 'Nova avaliação - '.$student->name)

@section('content')
<div class="container mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.technical-sheet', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para a ficha
            </a>
            <h1 class="mt-1 text-2xl font-bold">Nova avaliação</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <span class="badge badge-info self-start sm:self-auto">Preenchida pela equipe</span>
    </div>

    @if ($assessment)
        <div class="card">
            <p class="text-sm text-text-secondary">
                <i class="fas fa-clock-rotate-left mr-1 text-cyan"></i>
                Os dados da última avaliação foram carregados como base. Atualize o que mudou desde então.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('students.assessments.store', $student) }}" class="space-y-6">
        @csrf

        @include('pages.student-portal.assessment._form')

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('students.technical-sheet', $student) }}" class="btn-outline justify-center">
                Cancelar
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar avaliação
            </button>
        </div>
    </form>
</div>
@endsection
