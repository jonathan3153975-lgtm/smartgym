<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        if (Gate::allows('students.view') && TenantContext::current()->networkId()) {
            $students = TenantContext::current()
                ->studentsQuery()
                ->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('cpf', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            foreach ($students as $student) {
                $results[] = [
                    'type' => 'student',
                    'label' => $student->name,
                    'subtitle' => $student->email ?: ($student->cpf ?: 'Cadastro de aluno'),
                    'url' => route('students.show', $student),
                    'icon' => 'fa-user-graduate',
                    'actions' => [
                        ['label' => 'Contratos', 'url' => route('students.contracts', $student)],
                    ],
                ];
            }
        }

        foreach ($this->modules() as $module) {
            if (! $this->canAccessModule($module)) {
                continue;
            }

            $haystack = mb_strtolower($module['label'].' '.implode(' ', $module['keywords']));

            if (mb_stripos($haystack, mb_strtolower($query)) === false) {
                continue;
            }

            $results[] = [
                'type' => 'module',
                'label' => $module['label'],
                'subtitle' => 'Módulo / ação',
                'url' => route($module['route']),
                'icon' => $module['icon'],
                'actions' => [],
            ];
        }

        return response()->json([
            'results' => array_slice($results, 0, 10),
        ]);
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private function canAccessModule(array $module): bool
    {
        if (empty($module['abilities'])) {
            return true;
        }

        foreach ($module['abilities'] as $ability) {
            if (Gate::allows($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-tachometer-alt', 'abilities' => [], 'keywords' => ['inicio', 'painel', 'dashboard']],
            ['label' => 'Painel da unidade', 'route' => 'unit.dashboard', 'icon' => 'fa-building-user', 'abilities' => ['unit.panel'], 'keywords' => ['unidade', 'operacao', 'recepcao', 'painel da unidade']],
            ['label' => 'Alunos', 'route' => 'students.index', 'icon' => 'fa-user-graduate', 'abilities' => ['students.view'], 'keywords' => ['aluno', 'alunos', 'cadastro de alunos', 'clientes']],
            ['label' => 'Fichas de avaliação', 'route' => 'unit.assessments.index', 'icon' => 'fa-clipboard-check', 'abilities' => ['unit.panel'], 'keywords' => ['ficha', 'fichas', 'avaliacao', 'par-q']],
            ['label' => 'Pagamentos da unidade', 'route' => 'unit.payments.index', 'icon' => 'fa-credit-card', 'abilities' => ['unit.panel'], 'keywords' => ['pagamento', 'pagamentos', 'financeiro', 'pendencia', 'inadimplencia']],
            ['label' => 'Acessos da unidade', 'route' => 'unit.access.index', 'icon' => 'fa-key', 'abilities' => ['unit.panel'], 'keywords' => ['acesso', 'acessos', 'senha', 'check-in', 'checkin']],
            ['label' => 'Turmas e reservas', 'route' => 'unit.classes.index', 'icon' => 'fa-calendar-check', 'abilities' => ['unit.panel'], 'keywords' => ['turma', 'turmas', 'reserva', 'reservas', 'aulas']],
            ['label' => 'Cadastro de turmas', 'route' => 'admin.classes.index', 'icon' => 'fa-chalkboard', 'abilities' => ['classes.manage'], 'keywords' => ['turma', 'turmas', 'aulas', 'horarios']],
            ['label' => 'Modalidades', 'route' => 'admin.modalities.index', 'icon' => 'fa-dumbbell', 'abilities' => ['modalities.view'], 'keywords' => ['modalidade', 'modalidades', 'categoria']],
            ['label' => 'Planos comerciais', 'route' => 'admin.membership-plans.index', 'icon' => 'fa-layer-group', 'abilities' => ['membership_plans.view'], 'keywords' => ['plano', 'planos', 'mensalidade', 'diaria']],
            ['label' => 'Exercícios', 'route' => 'admin.exercises.index', 'icon' => 'fa-person-running', 'abilities' => ['exercises.manage'], 'keywords' => ['exercicio', 'exercicios', 'equipamento']],
            ['label' => 'Treinos', 'route' => 'admin.workout-plans.index', 'icon' => 'fa-clipboard-list', 'abilities' => ['workouts.manage'], 'keywords' => ['treino', 'treinos', 'plano de treino']],
            ['label' => 'Unidades', 'route' => 'admin.units.index', 'icon' => 'fa-building', 'abilities' => ['units.manage'], 'keywords' => ['unidade', 'unidades', 'academia']],
            ['label' => 'Regiões', 'route' => 'admin.regions.index', 'icon' => 'fa-map-marked-alt', 'abilities' => ['regions.manage'], 'keywords' => ['regiao', 'regioes']],
            ['label' => 'Pessoas', 'route' => 'admin.people.index', 'icon' => 'fa-address-book', 'abilities' => ['people.view'], 'keywords' => ['pessoa', 'pessoas', 'contatos']],
            ['label' => 'Usuários', 'route' => 'admin.users.index', 'icon' => 'fa-users', 'abilities' => ['users.manage'], 'keywords' => ['usuario', 'usuarios', 'equipe']],
            ['label' => 'Perfis e permissões', 'route' => 'admin.roles.index', 'icon' => 'fa-user-shield', 'abilities' => ['roles.manage'], 'keywords' => ['perfil', 'perfis', 'permissoes', 'cargo']],
            ['label' => 'Configurações da rede', 'route' => 'admin.network.settings.edit', 'icon' => 'fa-gear', 'abilities' => ['network.settings'], 'keywords' => ['configuracao', 'configuracoes', 'tema', 'mercado pago', 'pagamentos']],
            ['label' => 'Redes', 'route' => 'admin.networks.index', 'icon' => 'fa-sitemap', 'abilities' => ['networks.manage'], 'keywords' => ['rede', 'redes', 'tenant']],
            ['label' => 'Planos SaaS', 'route' => 'admin.plans.index', 'icon' => 'fa-cubes', 'abilities' => ['plans.manage'], 'keywords' => ['plano saas', 'planos saas', 'assinatura da plataforma']],
            ['label' => 'Meu perfil', 'route' => 'profile.edit', 'icon' => 'fa-user', 'abilities' => [], 'keywords' => ['perfil', 'minha conta', 'senha']],
        ];
    }
}
