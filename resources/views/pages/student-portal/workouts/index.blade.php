@extends('layouts.student')

@section('title', 'Meus treinos')

@section('content')
<div class="space-y-6">
    @include('pages.student-portal.workouts._nav')

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

                <a href="{{ route('student.portal.workouts.log', [$network, $inProgress]) }}" class="btn-primary self-end sm:self-auto">
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
                    @if ($plan->goal_labels)
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($plan->goal_labels as $goalLabel)
                                <span class="badge badge-info">{{ $goalLabel }}</span>
                            @endforeach
                        </div>
                    @endif
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

    <section id="meus-treinos" class="scroll-mt-32 space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <i class="fas fa-layer-group text-cyan"></i>
                Meus treinos
            </h2>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="openPublicPlansModal()" class="btn-outline px-3 py-2 text-xs">
                    <i class="fas fa-search mr-2"></i>
                    Buscar públicos
                </button>
                <button type="button" onclick="openNewPlanModal()" class="btn-primary px-3 py-2 text-xs">
                    <i class="fas fa-plus mr-2"></i>
                    Criar treino
                </button>
            </div>
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
                            <span class="text-xs text-text-secondary">{{ $myPlan->copies_label }}</span>
                            <button type="button"
                                    onclick="togglePlanVisibility({{ $myPlan->id }})"
                                    class="text-xs font-semibold text-cyan hover:underline">
                                {{ $myPlan->isPublic() ? 'Tornar privado' : 'Tornar público' }}
                            </button>
                            @if ($myPlan->created_by === null)
                                <button type="button"
                                        onclick="deleteWorkoutPlan({{ $myPlan->id }}, @js($myPlan->name))"
                                        class="ml-auto rounded-lg p-1.5 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-red"
                                        title="Excluir treino" aria-label="Excluir treino">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
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
                <label class="label-field">Objetivos do treino</label>
                <div class="grid grid-cols-1 gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:grid-cols-2">
                    @foreach (\App\Models\StudentAssessment::GOALS as $goalValue => $goalLabel)
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="goals[]" value="{{ $goalValue }}">
                            <span>{{ $goalLabel }}</span>
                        </label>
                    @endforeach
                </div>
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

{{-- Public plans search modal --}}
<div id="publicPlansModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="publicPlansTitle">
    <div class="modal-content modal-content-lg flex max-h-[90vh] flex-col">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="publicPlansTitle" class="text-xl font-bold">Buscar treinos públicos</h2>
            <button type="button" onclick="closePublicPlansModal()" class="rounded-lg p-2 text-text-secondary hover:bg-surface"
                    aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="publicPlansForm" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="label-field" for="public_goal">Objetivo</label>
                <select id="public_goal" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\StudentAssessment::GOALS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label-field" for="public_muscle">Grupo muscular</label>
                <select id="public_muscle" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-search mr-2"></i>
                    Pesquisar
                </button>
            </div>
        </form>

        <div id="publicPlansResults" class="mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
            <p class="py-8 text-center text-sm text-text-secondary">Use os filtros e clique em Pesquisar.</p>
        </div>

        <div class="mt-3 flex justify-end border-t border-custom pt-3">
            <button type="button" onclick="closePublicPlansModal()" class="btn-outline">Fechar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('newPlanModal');
        const csrf = @json(csrf_token());
        const publicPlansModal = document.getElementById('publicPlansModal');
        const publicPlansForm = document.getElementById('publicPlansForm');
        const publicPlansResults = document.getElementById('publicPlansResults');
        const publicGoal = document.getElementById('public_goal');
        const publicMuscle = document.getElementById('public_muscle');
        const planRoutes = {
            search: @json(route('student.portal.workouts.plans.search', $network)),
            copy: @json(route('student.portal.workouts.plans.copy', [$network, '__ID__'])),
            destroy: @json(route('student.portal.workouts.plans.destroy', [$network, '__ID__'])),
        };

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

        function submitPlanAction(action, planId, method) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action.replace('__ID__', planId);
            form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">'
                + (method ? '<input type="hidden" name="_method" value="' + method + '">' : '');
            document.body.appendChild(form);
            form.submit();
        }

        function confirmPlanAction(options, onConfirm) {
            if (typeof Swal === 'undefined') {
                if (window.confirm(options.text)) {
                    onConfirm();
                }

                return;
            }

            Swal.fire(options).then(function (result) {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        }

        function copyWorkoutPlan(planId, name) {
            confirmPlanAction({
                title: 'Copiar treino?',
                text: 'Uma cópia privada de "' + name + '" será adicionada aos seus treinos.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Copiar',
                cancelButtonText: 'Cancelar',
            }, function () {
                submitPlanAction(planRoutes.copy, planId);
            });
        }

        window.deleteWorkoutPlan = function (planId, name) {
            confirmPlanAction({
                title: 'Excluir treino?',
                text: 'O treino "' + name + '" será removido definitivamente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            }, function () {
                submitPlanAction(planRoutes.destroy, planId, 'DELETE');
            });
        };

        window.openPublicPlansModal = function () {
            publicPlansModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            if (!publicPlansResults.dataset.loaded) {
                searchPublicPlans();
            }
        };

        window.closePublicPlansModal = function () {
            publicPlansModal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                }[character];
            });
        }

        function renderPlanDetails(plan) {
            if (!plan.sessions || !plan.sessions.length) {
                return '<p class="text-sm text-text-secondary">Treino sem sessões cadastradas.</p>';
            }

            return plan.sessions.map(function (session) {
                const exercises = (session.exercises || []).map(function (exercise) {
                    return '<li class="flex items-center justify-between gap-2 py-1 text-sm">'
                        + '<span class="truncate">' + escapeHtml(exercise.name) + '</span>'
                        + '<span class="shrink-0 text-xs text-text-secondary">' + exercise.sets + 'x' + exercise.reps + '</span>'
                        + '</li>';
                }).join('');

                return '<div>'
                    + '<p class="text-sm font-semibold">' + escapeHtml(session.name) + '</p>'
                    + '<ul class="mt-1 divide-y divide-custom">' + exercises + '</ul>'
                    + '</div>';
            }).join('');
        }

        function renderPublicPlans(plans) {
            if (!plans.length) {
                publicPlansResults.innerHTML = '<p class="py-8 text-center text-sm text-text-secondary">Nenhum treino encontrado.</p>';
                return;
            }

            publicPlansResults.innerHTML = '';

            plans.forEach(function (plan) {
                const article = document.createElement('article');
                article.className = 'rounded-xl border border-custom bg-bg-secondary p-3';

                const goals = (plan.goals || [])
                    .map((goal) => '<span class="badge badge-info">' + escapeHtml(goal) + '</span>')
                    .join(' ');

                article.innerHTML = '<div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">'
                    + '<div class="min-w-0">'
                    + '<p class="truncate font-semibold">' + escapeHtml(plan.name) + '</p>'
                    + '<p class="text-xs text-text-secondary">' + escapeHtml(plan.author)
                    + ' &middot; ' + plan.sessions_count + ' sessão(ões) &middot; ' + escapeHtml(plan.copies_label) + '</p>'
                    + (plan.objective ? '<p class="mt-1 text-xs text-text-secondary">' + escapeHtml(plan.objective) + '</p>' : '')
                    + (goals ? '<div class="mt-2 flex flex-wrap gap-1">' + goals + '</div>' : '')
                    + '</div>'
                    + '<div class="flex shrink-0 gap-2">'
                    + '<button type="button" data-view-plan class="btn-outline px-3 py-1.5 text-xs"><i class="fas fa-eye mr-1"></i>Ver</button>'
                    + '<button type="button" data-copy-plan class="btn-primary px-3 py-1.5 text-xs"><i class="fas fa-copy mr-1"></i>Copiar</button>'
                    + '</div>'
                    + '</div>'
                    + '<div data-plan-details class="mt-3 hidden space-y-3 border-t border-custom pt-3"></div>';

                article.querySelector('[data-view-plan]').addEventListener('click', function () {
                    const details = article.querySelector('[data-plan-details]');

                    if (!details.dataset.loaded) {
                        details.innerHTML = renderPlanDetails(plan);
                        details.dataset.loaded = 'true';
                    }

                    details.classList.toggle('hidden');
                });

                article.querySelector('[data-copy-plan]').addEventListener('click', function () {
                    copyWorkoutPlan(plan.id, plan.name);
                });

                publicPlansResults.appendChild(article);
            });
        }

        function searchPublicPlans() {
            const params = new URLSearchParams();

            if (publicGoal.value) {
                params.set('goal', publicGoal.value);
            }

            if (publicMuscle.value) {
                params.set('muscle_group', publicMuscle.value);
            }

            publicPlansResults.innerHTML = '<p class="py-8 text-center text-sm text-text-secondary"><i class="fas fa-spinner fa-spin mr-2"></i>Buscando treinos...</p>';

            fetch(planRoutes.search + (params.toString() ? '?' + params.toString() : ''), {
                headers: {
                    'Accept': 'application/json',
                },
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Não foi possível buscar os treinos.');
                }

                return response.json();
            }).then(function (data) {
                publicPlansResults.dataset.loaded = 'true';
                renderPublicPlans(data.data || []);
            }).catch(function (error) {
                publicPlansResults.innerHTML = '<p class="py-8 text-center text-sm text-text-secondary">' + escapeHtml(error.message) + '</p>';
            });
        }

        publicPlansForm?.addEventListener('submit', function (event) {
            event.preventDefault();
            searchPublicPlans();
        });

        publicPlansModal?.addEventListener('click', function (event) {
            if (event.target === publicPlansModal) {
                closePublicPlansModal();
            }
        });
    })();
</script>
@endsection
