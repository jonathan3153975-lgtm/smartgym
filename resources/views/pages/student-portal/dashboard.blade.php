@extends('layouts.student')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="mt-1 text-text-secondary">Indicadores e atalhos do seu acesso</p>
    </div>

    <div class="card">
        <div class="py-10 text-center">
            <i class="fas fa-chart-pie mb-4 text-4xl text-text-secondary"></i>
            <h2 class="text-lg font-semibold">Dashboard em desenvolvimento</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-text-secondary">
                Em breve você acompanhará aqui seus planos, frequência, aulas e demais informações do seu acesso.
            </p>
            <a href="{{ route('student.portal.home', $network) }}" class="btn-outline mt-5 inline-flex">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para a home
            </a>
        </div>
    </div>
</div>
@endsection
