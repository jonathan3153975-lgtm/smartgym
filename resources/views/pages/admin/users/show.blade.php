@extends('layouts.app')

@section('title', 'Usuário - '.$user->name)

@section('content')
<div class="container mx-auto max-w-5xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.users.index') }}" class="text-sm text-text-secondary hover:text-cyan transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para usuários
            </a>
            <h1 class="text-2xl font-bold mt-1 truncate">{{ $user->name }}</h1>
            <p class="text-text-secondary mt-1">{{ $user->email }}</p>
        </div>

        <button type="button" onclick="openUserModal({{ $user->id }})" class="btn-primary">
            <i class="fas fa-edit mr-2"></i>
            Editar usuário
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-circle-info text-cyan"></i>
                Dados do usuário
            </h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-text-secondary text-sm">Nome</dt>
                    <dd class="font-medium mt-1">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">E-mail</dt>
                    <dd class="font-medium mt-1">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">E-mail verificado</dt>
                    <dd class="font-medium mt-1">
                        {{ $user->email_verified_at ? $user->email_verified_at->format('d/m/Y H:i') : 'Não verificado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Cadastrado em</dt>
                    <dd class="font-medium mt-1">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="lg:col-span-2 card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-user-shield text-purple"></i>
                Vínculos organizacionais
            </h2>

            @if ($user->vinculos->count() > 0)
                <div class="space-y-3">
                    @foreach ($user->vinculos as $vinculo)
                        <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="font-medium">{{ $vinculo->role?->display_name ?? 'Perfil removido' }}</p>
                                    <p class="text-xs text-text-secondary">
                                        {{ ucfirst($vinculo->role?->level ?? '—') }}
                                    </p>
                                </div>
                                @if ($vinculo->status === 'active')
                                    <span class="badge badge-success self-start sm:self-auto">Ativo</span>
                                @else
                                    <span class="badge badge-secondary self-start sm:self-auto">Inativo</span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
                                <div>
                                    <span class="text-text-secondary text-xs block">Rede</span>
                                    {{ $vinculo->network?->name ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-text-secondary text-xs block">Região</span>
                                    {{ $vinculo->region?->name ?? '—' }}
                                </div>
                                <div>
                                    <span class="text-text-secondary text-xs block">Unidade</span>
                                    {{ $vinculo->unit?->name ?? '—' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10">
                    <i class="fas fa-link-slash text-3xl text-text-secondary mb-3"></i>
                    <p class="font-medium">Nenhum vínculo cadastrado</p>
                    <p class="text-sm text-text-secondary mt-1">Edite o usuário para vincular um perfil organizacional.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@include('pages.admin.users._modal')
@endsection
