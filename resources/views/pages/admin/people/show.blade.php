@extends('layouts.app')

@section('title', 'Pessoa - '.$person->name)

@section('content')
<div class="container mx-auto max-w-5xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.people.index') }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para pessoas
            </a>
            <h1 class="mt-1 truncate text-2xl font-bold">{{ $person->name }}</h1>
            <p class="mt-1 text-text-secondary">
                {{ $person->document_label }} {{ $person->document ?? '—' }}
                &middot; {{ $person->person_type_label }}
                &middot; {{ $person->category_label }}
            </p>
        </div>

        @can('people.edit')
            <button type="button" onclick="openPersonModal({{ $person->id }})" class="btn-primary">
                <i class="fas fa-edit mr-2"></i>
                Editar pessoa
            </button>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card">
            <h2 class="mb-4 flex items-center gap-2 font-semibold">
                <i class="fas {{ $person->isCompany() ? 'fa-building' : 'fa-user' }} text-cyan"></i>
                Dados da pessoa
            </h2>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-text-secondary">Tipo</dt>
                    <dd class="mt-1 font-medium">{{ $person->person_type_label }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">Categoria</dt>
                    <dd class="mt-1">
                        <span class="badge {{ $person->isStaff() ? 'badge-info' : 'badge-secondary' }}">
                            {{ $person->category_label }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-text-secondary">{{ $person->document_label }}</dt>
                    <dd class="mt-1 font-medium">{{ $person->document ?? '—' }}</dd>
                </div>

                @if ($person->isCompany())
                    <div>
                        <dt class="text-sm text-text-secondary">Nome fantasia</dt>
                        <dd class="mt-1 font-medium">{{ $person->trade_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-text-secondary">Inscrição estadual</dt>
                        <dd class="mt-1 font-medium">{{ $person->state_registration ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-text-secondary">Responsável legal</dt>
                        <dd class="mt-1 font-medium">{{ $person->legal_representative_name ?? '—' }}</dd>
                        <dd class="text-sm text-text-secondary">{{ $person->legal_representative_document ?? '' }}</dd>
                    </div>
                @else
                    <div>
                        <dt class="text-sm text-text-secondary">Data de nascimento</dt>
                        <dd class="mt-1 font-medium">{{ $person->birth_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm text-text-secondary">Unidade de referência</dt>
                    <dd class="mt-1 font-medium">{{ $person->unit?->name ?? 'Sem unidade' }}</dd>
                </div>
                @if ($person->network)
                    <div>
                        <dt class="text-sm text-text-secondary">Rede</dt>
                        <dd class="mt-1 font-medium">{{ $person->network->name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-text-secondary">Status</dt>
                    <dd class="mt-1">
                        @if ($person->status === 'active')
                            <span class="badge badge-success">Ativa</span>
                        @else
                            <span class="badge badge-secondary">Inativa</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($person->notes)
                <div class="mt-5 rounded-lg border border-custom bg-bg-secondary p-3">
                    <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Observações</p>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ $person->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h2 class="mb-4 flex items-center gap-2 font-semibold">
                    <i class="fas fa-address-book text-purple"></i>
                    Contato e endereço
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-text-secondary">E-mail</p>
                        <p class="mt-1 font-medium">{{ $person->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">Telefone</p>
                        <p class="mt-1 font-medium">{{ $person->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary">WhatsApp</p>
                        <p class="mt-1 font-medium">{{ $person->whatsapp ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-text-secondary">Endereço</p>
                        @php
                            $address = collect([
                                trim(collect([$person->street, $person->number])->filter()->implode(', ')),
                                $person->complement,
                                $person->neighborhood,
                                trim(collect([$person->city, $person->state])->filter()->implode(' - ')),
                                $person->zip_code ? 'CEP '.$person->zip_code : null,
                            ])->filter()->implode(' · ');
                        @endphp
                        <p class="mt-1 font-medium">{{ $address !== '' ? $address : '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-user-shield text-lime"></i>
                        Usuários vinculados
                    </h2>
                    <span class="badge badge-info">{{ $person->users->count() }}</span>
                </div>

                @if ($person->users->count() > 0)
                    <div class="space-y-3">
                        @foreach ($person->users as $user)
                            @php($vinculo = $user->vinculos->firstWhere('status', 'active') ?? $user->vinculos->first())
                            <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-text-secondary">{{ $user->email }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($vinculo?->role)
                                            <span class="badge badge-secondary">{{ $vinculo->role->display_name }}</span>
                                        @endif
                                        @can('users.manage')
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated"
                                               title="Ver usuário">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <i class="fas fa-link-slash mb-3 text-3xl text-text-secondary"></i>
                        <p class="font-medium">Nenhum usuário vinculado</p>
                        <p class="mt-1 text-sm text-text-secondary">
                            Ao cadastrar um usuário, selecione esta pessoa para criar o vínculo de acesso.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('pages.admin.people._modal')
@endsection
