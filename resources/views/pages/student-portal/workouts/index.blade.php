@extends('layouts.student')

@section('title', 'Meus treinos')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Treinos</p>
                <h1 class="mt-1 text-2xl font-bold">Meus treinos</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Use um treino da equipe, copie um treino público ou crie o seu do zero.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="openNewPlanModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Criar treino
                </button>
                <a href="{{ route('student.portal.workouts.history', $network) }}" class="btn-outline">
                    <i class="fas fa-clock-rotate-left mr-2"></i>
                    Histórico
                </a>
                <a href="{{ route('student.portal.workouts.evolution', $network) }}" class="btn-outline">
                    <i class="fas fa-chart-line mr-2"></i>
                    Evolução
                </a>
            </div>
        </div>
    </div>

    @if ($inProgress)
        <div class="card" style="border-color: var(--color-warning);">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <i class="fas fa-hourglass-half mt-0.5 text-xl" style="color: var(--color-warning);"></i>
                    <div>
                        <p class="font-semibold">Você tem um treino em andamento</p>
                        <p class="mt-1 text-sm text-text-secondary">
                            {{ $inProgress->session_name }} &middot; iniciado em {{ $inProgress->started_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                </div>

                <a href="{{ route('student.portal.workouts.log', [$network, $inProgress]) }}" class="btn-primary self-start sm:self-auto">
                    <i class="fas fa-play mr-2"></i>
                    Continuar treino
                </a>
            </div>
        </div>
    @endif

    @if (! $plan)
        <div class="card py-10 text-center">
            <i class="fas fa-clipboard-list mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhum treino ativo</p>
            <p class="mt-1 text-sm text-text-secondary">
                Crie seu primeiro treino ou copie um treino público preparado pela equipe.
            </p>
            <button type="button" onclick="openNewPlanModal()" class="btn-primary mt-4">
                <i class="fas fa-plus mr-2"></i>
                Criar treino
            </button>
        </div>
    @else
        <div class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs text-text-secondary">Treino ativo</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $plan->name }}</h2>
                    <p class="mt-1 text-sm text-text-secondary">
                        @if ($plan->objective){{ $plan->objective }} &middot; @endif
                        {{ $plan->sessions->count() }} sessão(ões)
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 self-start">
                    <span class="badge badge-success">Ativo</span>
                    <span class="badge {{ $plan->isPublic() ? 'badge-success' : 'badge-secondary' }}">
                        {{ $plan->isPublic() ? 'Público' : 'Privado' }}
                    </span>
                </div>
            </div>

            @if ($plan->notes)
                <p class="mt-3 whitespace-pre-line text-sm text-text-secondary">{{ $plan->notes }}</p>
            @endif
        </div>

        @if ($plan->sessions->isEmpty())
            <div class="card py-10 text-center">
                <i class="fas fa-list-check mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Treino sem sessões cadastradas</p>
                <p class="mt-1 text-sm text-text-secondary">Abra o treino para adicionar sessões e exercícios.</p>
                <a href="{{ route('student.portal.workouts.plans.builder', [$network, $plan]) }}" class="btn-primary mt-4">
                    <i class="fas fa-list-check mr-2"></i>
                    Montar treino
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($plan->sessions as $session)
                    <article class="card flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs text-text-secondary">Sessão {{ $session->order }}</p>
                                <h3 class="mt-1 text-lg font-bold">{{ $session->name }}</h3>
                            </div>
                            <span class="badge badge-info whitespace-nowrap">{{ $session->exercises_count }} exercícios</span>
                        </div>

                        @if ($session->notes)
                            <p class="text-sm text-text-secondary">{{ $session->notes }}</p>
                        @endif

                        <a href="{{ route('student.portal.workouts.session', [$network, $session]) }}" class="btn-primary mt-auto">
                            <i class="fas fa-dumbbell mr-2"></i>
                            Ver treino
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <i class="fas fa-layer-group text-cyan"></i>
                Meus treinos
            </h2>
            <button type="button" onclick="openNewPlanModal()" class="text-sm font-semibold text-cyan hover:underline">
                Criar novo
            </button>
        </div>

        @if ($myPlans->isEmpty())
            <div class="card py-6 text-center text-sm text-text-secondary">Você ainda não criou ou recebeu treinos.</div>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($myPlans as $myPlan)
                    <article class="card space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ $myPlan->name }}</p>
                                <p class="text-xs text-text-secondary">{{ $myPlan->sessions_count }} sessão(ões)</p>
                            </div>
                            @if ($myPlan->isActive())
                                <span class="badge badge-success">Ativo</span>
                            @else
                                <span class="badge badge-secondary">Inativo</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge {{ $myPlan->isPublic() ? 'badge-success' : 'badge-secondary' }}">
                                {{ $myPlan->isPublic() ? 'Público' : 'Privado' }}
                            </span>
                            <button type="button"
                                    onclick="togglePlanVisibility({{ $myPlan->id }})"
                                    class="text-xs font-semibold text-cyan hover:underline">
                                {{ $myPlan->isPublic() ? 'Tornar privado' : 'Tornar público' }}
                            </button>
                        </div>

                        @if (! $myPlan->isActive())
                            <form method="POST" action="{{ route('student.portal.workouts.plans.activate', [$network, $myPlan]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-outline w-full">
                                    <i class="fas fa-bolt mr-2"></i>
                                    Ativar treino
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('student.portal.workouts.plans.builder', [$network, $myPlan]) }}" class="btn-outline w-full">
                            <i class="fas fa-edit mr-2"></i>
                            Abrir e editar
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <i class="fas fa-globe text-cyan"></i>
                Treinos públicos
            </h2>
            <span class="text-xs text-text-secondary">Criados pela equipe ou por outros alunos</span>
        </div>

        @if ($publicPlans->isEmpty())
            <div class="card py-6 text-center text-sm text-text-secondary">Nenhum treino público disponível.</div>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($publicPlans as $publicPlan)
                    <article class="card space-y-3">
                        <div>
                            <p class="font-semibold">{{ $publicPlan->name }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $publicPlan->student?->name ? 'Por '.$publicPlan->student->name : 'Treino da equipe' }}
                                &middot; {{ $publicPlan->sessions_count }} sessão(ões)
                            </p>
                            @if ($publicPlan->objective)
                                <p class="mt-1 text-xs text-text-secondary">{{ $publicPlan->objective }}</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('student.portal.workouts.plans.copy', [$network, $publicPlan]) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">
                                <i class="fas fa-copy mr-2"></i>
                                Copiar treino
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>

{{-- New plan modal --}}
<div id="newPlanModal" class="modal-overlay hidden" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-xl font-bold">Criar treino</h2>
            <button type="button" onclick="closeNewPlanModal()" class="rounded-lg p-2 text-text-secondary hover:bg-surface">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('student.portal.workouts.plans.store', $network) }}" class="space-y-4">
            @csrf

            <div>
                <label for="new_plan_name" class="label-field">Nome <span class="text-red">*</span></label>
                <input type="text" id="new_plan_name" name="name" required maxlength="255" class="input-field"
                       placeholder="Ex.: Meu treino de pernas">
            </div>

            <div>
                <label for="new_plan_objective" class="label-field">Objetivo</label>
                <input type="text" id="new_plan_objective" name="objective" maxlength="255" class="input-field"
                       placeholder="Ex.: hipertrofia, condicionamento...">
            </div>

            <div>
                <label for="new_plan_notes" class="label-field">Descrição</label>
                <textarea id="new_plan_notes" name="notes" rows="3" maxlength="2000" class="input-field"
                          placeholder="Como o treino deve ser executado?"></textarea>
            </div>

            <div>
                <label for="new_plan_visibility" class="label-field">Visibilidade</label>
                <select id="new_plan_visibility" name="visibility" class="input-field">
                    <option value="private">Privado (só eu vejo)</option>
                    <option value="public">Público (outros podem copiar)</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeNewPlanModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Criar treino
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('newPlanModal');
        const csrf = @json(csrf_token());

        window.openNewPlanModal = function () {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeNewPlanModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.togglePlanVisibility = function (planId) {
            fetch(`{{ route('student.portal.workouts.plans.visibility', [$network, '__ID__']) }}`.replace('__ID__', planId), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Não foi possível atualizar a visibilidade.');
                }

                window.location.reload();
            }).catch(function (error) {
                alert(error.message);
            });
        };

        modal?.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeNewPlanModal();
            }
        });
    })();
</script>
@endsection
