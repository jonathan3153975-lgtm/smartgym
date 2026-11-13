<?php

namespace Database\Seeders;

use App\Models\Network;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Region;
use App\Models\Role;
use App\Models\SaasPlan;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use App\Support\BrazilianDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SaasPlanSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            DeveloperUserSeeder::class,
            PeopleSeeder::class,
            ExerciseSeeder::class,
        ]);
    }
}

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        SaasPlan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Plano inicial para academias pequenas',
                'limits' => json_encode([
                    'max_networks' => 1,
                    'max_regions' => 3,
                    'max_units' => 5,
                    'max_users' => 20,
                    'max_students' => 500,
                ]),
                'features' => json_encode([
                    'students' => true,
                    'plans' => false,
                    'classes' => false,
                    'reservations' => false,
                    'workouts' => false,
                    'finance' => false,
                    'access_control' => false,
                    'hr' => false,
                    'expenses' => false,
                    'crm' => false,
                    'reports' => true,
                    'integrations' => false,
                    'white_label' => false,
                ]),
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ]
        );

        SaasPlan::firstOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'Plano para academias em crescimento',
                'limits' => json_encode([
                    'max_networks' => 1,
                    'max_regions' => 10,
                    'max_units' => 20,
                    'max_users' => 100,
                    'max_students' => 5000,
                ]),
                'features' => json_encode([
                    'students' => true,
                    'plans' => true,
                    'classes' => true,
                    'reservations' => true,
                    'workouts' => true,
                    'finance' => true,
                    'access_control' => false,
                    'hr' => true,
                    'expenses' => true,
                    'crm' => true,
                    'reports' => true,
                    'integrations' => true,
                    'white_label' => false,
                ]),
                'price' => 299.00,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ]
        );

        SaasPlan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Plano para grandes redes de academias',
                'limits' => json_encode([
                    'max_networks' => 10,
                    'max_regions' => 50,
                    'max_units' => 200,
                    'max_users' => 1000,
                    'max_students' => 50000,
                ]),
                'features' => json_encode([
                    'students' => true,
                    'plans' => true,
                    'classes' => true,
                    'reservations' => true,
                    'workouts' => true,
                    'finance' => true,
                    'access_control' => true,
                    'hr' => true,
                    'expenses' => true,
                    'crm' => true,
                    'reports' => true,
                    'integrations' => true,
                    'white_label' => true,
                ]),
                'price' => 999.00,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ]
        );
    }
}

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Platform/Admin permissions
            ['key' => 'admin.access', 'name' => 'Acessar painel administrativo', 'module' => 'admin', 'action' => 'access'],
            ['key' => 'networks.manage', 'name' => 'Gerenciar redes', 'module' => 'networks', 'action' => 'manage'],
            ['key' => 'networks.view', 'name' => 'Visualizar redes', 'module' => 'networks', 'action' => 'view'],
            ['key' => 'networks.create', 'name' => 'Criar redes', 'module' => 'networks', 'action' => 'create'],
            ['key' => 'networks.edit', 'name' => 'Editar redes', 'module' => 'networks', 'action' => 'edit'],
            ['key' => 'networks.delete', 'name' => 'Excluir redes', 'module' => 'networks', 'action' => 'delete'],

            ['key' => 'regions.manage', 'name' => 'Gerenciar regiões', 'module' => 'regions', 'action' => 'manage'],
            ['key' => 'regions.view', 'name' => 'Visualizar regiões', 'module' => 'regions', 'action' => 'view'],
            ['key' => 'regions.create', 'name' => 'Criar regiões', 'module' => 'regions', 'action' => 'create'],
            ['key' => 'regions.edit', 'name' => 'Editar regiões', 'module' => 'regions', 'action' => 'edit'],
            ['key' => 'regions.delete', 'name' => 'Excluir regiões', 'module' => 'regions', 'action' => 'delete'],

            ['key' => 'units.manage', 'name' => 'Gerenciar unidades', 'module' => 'units', 'action' => 'manage'],
            ['key' => 'units.view', 'name' => 'Visualizar unidades', 'module' => 'units', 'action' => 'view'],
            ['key' => 'units.create', 'name' => 'Criar unidades', 'module' => 'units', 'action' => 'create'],
            ['key' => 'units.edit', 'name' => 'Editar unidades', 'module' => 'units', 'action' => 'edit'],
            ['key' => 'units.delete', 'name' => 'Excluir unidades', 'module' => 'units', 'action' => 'delete'],

            ['key' => 'users.manage', 'name' => 'Gerenciar usuários', 'module' => 'users', 'action' => 'manage'],
            ['key' => 'users.view', 'name' => 'Visualizar usuários', 'module' => 'users', 'action' => 'view'],
            ['key' => 'users.create', 'name' => 'Criar usuários', 'module' => 'users', 'action' => 'create'],
            ['key' => 'users.edit', 'name' => 'Editar usuários', 'module' => 'users', 'action' => 'edit'],
            ['key' => 'users.delete', 'name' => 'Excluir usuários', 'module' => 'users', 'action' => 'delete'],
            ['key' => 'users.impersonate', 'name' => 'Emular usuários', 'module' => 'users', 'action' => 'impersonate'],

            ['key' => 'roles.manage', 'name' => 'Gerenciar perfis e permissões', 'module' => 'roles', 'action' => 'manage'],
            ['key' => 'roles.view', 'name' => 'Visualizar perfis', 'module' => 'roles', 'action' => 'view'],
            ['key' => 'roles.create', 'name' => 'Criar perfis', 'module' => 'roles', 'action' => 'create'],
            ['key' => 'roles.edit', 'name' => 'Editar perfis', 'module' => 'roles', 'action' => 'edit'],
            ['key' => 'roles.delete', 'name' => 'Excluir perfis', 'module' => 'roles', 'action' => 'delete'],

            ['key' => 'audit.view', 'name' => 'Visualizar logs de auditoria', 'module' => 'audit', 'action' => 'view'],
            ['key' => 'reports.view', 'name' => 'Visualizar relatórios globais', 'module' => 'reports', 'action' => 'view'],

            // Network level permissions
            ['key' => 'network.settings', 'name' => 'Configurações da rede', 'module' => 'network', 'action' => 'settings'],

            // Student permissions
            ['key' => 'students.view', 'name' => 'Visualizar alunos', 'module' => 'students', 'action' => 'view'],
            ['key' => 'students.create', 'name' => 'Cadastrar alunos', 'module' => 'students', 'action' => 'create'],
            ['key' => 'students.edit', 'name' => 'Editar alunos', 'module' => 'students', 'action' => 'edit'],
            ['key' => 'students.delete', 'name' => 'Inativar/Excluir alunos', 'module' => 'students', 'action' => 'delete'],
            ['key' => 'students.export', 'name' => 'Exportar alunos', 'module' => 'students', 'action' => 'export'],
            ['key' => 'students.import', 'name' => 'Importar alunos', 'module' => 'students', 'action' => 'import'],

            // People permissions
            ['key' => 'people.manage', 'name' => 'Gerenciar pessoas', 'module' => 'people', 'action' => 'manage'],
            ['key' => 'people.view', 'name' => 'Visualizar pessoas', 'module' => 'people', 'action' => 'view'],
            ['key' => 'people.create', 'name' => 'Criar pessoas', 'module' => 'people', 'action' => 'create'],
            ['key' => 'people.edit', 'name' => 'Editar pessoas', 'module' => 'people', 'action' => 'edit'],
            ['key' => 'people.delete', 'name' => 'Excluir pessoas', 'module' => 'people', 'action' => 'delete'],

            // Modalities permissions
            ['key' => 'modalities.manage', 'name' => 'Gerenciar categorias de modalidades', 'module' => 'modalities', 'action' => 'manage'],
            ['key' => 'modalities.view', 'name' => 'Visualizar modalidades', 'module' => 'modalities', 'action' => 'view'],
            ['key' => 'modalities.create', 'name' => 'Criar modalidades', 'module' => 'modalities', 'action' => 'create'],
            ['key' => 'modalities.edit', 'name' => 'Editar modalidades', 'module' => 'modalities', 'action' => 'edit'],
            ['key' => 'modalities.delete', 'name' => 'Excluir modalidades', 'module' => 'modalities', 'action' => 'delete'],

            // Exercises permissions
            ['key' => 'exercises.manage', 'name' => 'Gerenciar exercícios', 'module' => 'exercises', 'action' => 'manage'],

            // Membership plans permissions
            ['key' => 'membership_plans.view', 'name' => 'Visualizar planos', 'module' => 'membership_plans', 'action' => 'view'],
            ['key' => 'membership_plans.create', 'name' => 'Criar planos', 'module' => 'membership_plans', 'action' => 'create'],
            ['key' => 'membership_plans.edit', 'name' => 'Editar planos', 'module' => 'membership_plans', 'action' => 'edit'],
            ['key' => 'membership_plans.delete', 'name' => 'Excluir planos', 'module' => 'membership_plans', 'action' => 'delete'],

            // Future module permissions (placeholders)
            ['key' => 'plans.manage', 'name' => 'Gerenciar planos', 'module' => 'plans', 'action' => 'manage'],
            ['key' => 'classes.manage', 'name' => 'Gerenciar turmas', 'module' => 'classes', 'action' => 'manage'],
            ['key' => 'reservations.manage', 'name' => 'Gerenciar reservas', 'module' => 'reservations', 'action' => 'manage'],
            ['key' => 'workouts.manage', 'name' => 'Gerenciar treinos', 'module' => 'workouts', 'action' => 'manage'],
            ['key' => 'finance.manage', 'name' => 'Gerenciar financeiro', 'module' => 'finance', 'action' => 'manage'],
            ['key' => 'access_control.manage', 'name' => 'Gerenciar controle de acesso', 'module' => 'access_control', 'action' => 'manage'],
            ['key' => 'hr.manage', 'name' => 'Gerenciar RH', 'module' => 'hr', 'action' => 'manage'],
            ['key' => 'expenses.manage', 'name' => 'Gerenciar despesas', 'module' => 'expenses', 'action' => 'manage'],
            ['key' => 'crm.manage', 'name' => 'Gerenciar CRM', 'module' => 'crm', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['key' => $permission['key']],
                array_merge($permission, ['is_system' => true])
            );
        }
    }
}

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Platform level roles
        $platformAdmin = Role::firstOrCreate(
            ['name' => 'platform_admin', 'level' => 'platform'],
            [
                'display_name' => 'Administrador da Plataforma',
                'description' => 'Acesso total à plataforma (Desenvolvedor/Operador)',
                'is_system' => true,
            ]
        );

        $platformSupport = Role::firstOrCreate(
            ['name' => 'platform_support', 'level' => 'platform'],
            [
                'display_name' => 'Suporte da Plataforma',
                'description' => 'Acesso de suporte com emulação controlada',
                'is_system' => true,
            ]
        );

        // Network level roles
        $networkAdmin = Role::firstOrCreate(
            ['name' => 'network_admin', 'level' => 'network'],
            [
                'display_name' => 'Administrador da Rede',
                'description' => 'Administrador técnico/gerencial da rede',
                'is_system' => true,
            ]
        );

        $networkManager = Role::firstOrCreate(
            ['name' => 'network_manager', 'level' => 'network'],
            [
                'display_name' => 'Gerente da Rede',
                'description' => 'Perfil gerencial/executivo da rede',
                'is_system' => true,
            ]
        );

        // Region level roles
        $regionAdmin = Role::firstOrCreate(
            ['name' => 'region_admin', 'level' => 'region'],
            [
                'display_name' => 'Administrador Regional',
                'description' => 'Administrador de uma ou mais regiões',
                'is_system' => true,
            ]
        );

        $regionManager = Role::firstOrCreate(
            ['name' => 'region_manager', 'level' => 'region'],
            [
                'display_name' => 'Gerente Regional',
                'description' => 'Gerente de uma ou mais regiões',
                'is_system' => true,
            ]
        );

        // Unit level roles
        $unitAdmin = Role::firstOrCreate(
            ['name' => 'unit_admin', 'level' => 'unit'],
            [
                'display_name' => 'Administrador da Unidade',
                'description' => 'Administrador da academia/unidade',
                'is_system' => true,
            ]
        );

        $unitManager = Role::firstOrCreate(
            ['name' => 'unit_manager', 'level' => 'unit'],
            [
                'display_name' => 'Gerente da Unidade',
                'description' => 'Gerente operacional da unidade',
                'is_system' => true,
            ]
        );

        $receptionist = Role::firstOrCreate(
            ['name' => 'receptionist', 'level' => 'unit'],
            [
                'display_name' => 'Recepcionista',
                'description' => 'Atendimento e cadastro de alunos',
                'is_system' => true,
            ]
        );

        $instructor = Role::firstOrCreate(
            ['name' => 'instructor', 'level' => 'unit'],
            [
                'display_name' => 'Instrutor/Professor',
                'description' => 'Acesso a turmas e alunos da unidade',
                'is_system' => true,
            ]
        );

        $sales = Role::firstOrCreate(
            ['name' => 'sales', 'level' => 'unit'],
            [
                'display_name' => 'Consultor Comercial',
                'description' => 'Vendas, leads e relacionamento',
                'is_system' => true,
            ]
        );

        // Assign permissions to roles
        $allPermissions = Permission::all()->keyBy('key');

        // Platform Admin - all permissions
        $platformAdmin->permissions()->sync($allPermissions->pluck('id'));

        // Platform Support - view permissions + impersonate
        $platformSupport->permissions()->sync(
            $allPermissions->whereIn('key', [
                'admin.access',
                'networks.view',
                'regions.view',
                'units.view',
                'users.view',
                'users.impersonate',
                'roles.view',
                'audit.view',
                'reports.view',
            ])->pluck('id')
        );

        // Network Admin - manage network, regions, units, users, roles, students
        $networkAdmin->permissions()->sync(
            $allPermissions->whereIn('key', [
                'regions.manage',
                'units.manage',
                'people.manage',
                'people.view',
                'people.create',
                'people.edit',
                'people.delete',
                'users.manage',
                'roles.manage',
                'network.settings',
                'students.view',
                'students.create',
                'students.edit',
                'students.delete',
                'students.export',
                'students.import',
            ])->pluck('id')
        );

        // Network Manager - view network, regions, units, students, reports
        $networkManager->permissions()->sync(
            $allPermissions->whereIn('key', [
                'regions.view',
                'units.view',
                'people.view',
                'users.view',
                'students.view',
                'students.export',
                'reports.view',
            ])->pluck('id')
        );

        // Region Admin - manage units, users, students in region
        $regionAdmin->permissions()->sync(
            $allPermissions->whereIn('key', [
                'units.manage',
                'people.manage',
                'people.view',
                'people.create',
                'people.edit',
                'people.delete',
                'users.manage',
                'students.view',
                'students.create',
                'students.edit',
                'students.delete',
                'students.export',
            ])->pluck('id')
        );

        // Region Manager - view units, students in region
        $regionManager->permissions()->sync(
            $allPermissions->whereIn('key', [
                'units.view',
                'people.view',
                'users.view',
                'students.view',
                'students.export',
            ])->pluck('id')
        );

        // Unit Admin - manage users, students, settings in unit
        $unitAdmin->permissions()->sync(
            $allPermissions->whereIn('key', [
                'people.manage',
                'people.view',
                'people.create',
                'people.edit',
                'people.delete',
                'users.manage',
                'students.view',
                'students.create',
                'students.edit',
                'students.delete',
                'students.export',
                'students.import',
            ])->pluck('id')
        );

        // Unit Manager - view students, create/edit
        $unitManager->permissions()->sync(
            $allPermissions->whereIn('key', [
                'people.view',
                'students.view',
                'students.create',
                'students.edit',
                'students.export',
            ])->pluck('id')
        );

        // Receptionist - create/view students
        $receptionist->permissions()->sync(
            $allPermissions->whereIn('key', [
                'people.view',
                'people.create',
                'people.edit',
                'students.view',
                'students.create',
                'students.edit',
            ])->pluck('id')
        );

        // Instructor - view students
        $instructor->permissions()->sync(
            $allPermissions->whereIn('key', [
                'people.view',
                'students.view',
            ])->pluck('id')
        );

        // Sales - create/view students
        $sales->permissions()->sync(
            $allPermissions->whereIn('key', [
                'people.view',
                'people.create',
                'people.edit',
                'students.view',
                'students.create',
                'students.edit',
            ])->pluck('id')
        );

        // Modalities and membership plans are available to every network profile.
        // Edit/delete is additionally restricted by the record creation level.
        $businessPermissions = $allPermissions->whereIn('key', [
            'modalities.manage',
            'modalities.view',
            'modalities.create',
            'modalities.edit',
            'modalities.delete',
            'membership_plans.view',
            'membership_plans.create',
            'membership_plans.edit',
            'membership_plans.delete',
            'classes.manage',
        ])->pluck('id');

        foreach ([
            $networkAdmin,
            $networkManager,
            $regionAdmin,
            $regionManager,
            $unitAdmin,
            $unitManager,
            $receptionist,
            $instructor,
            $sales,
        ] as $role) {
            $role->permissions()->syncWithoutDetaching($businessPermissions);
        }

        // Workout management is restricted to management and instructor profiles.
        $workoutPermissions = $allPermissions->whereIn('key', ['workouts.manage'])->pluck('id');

        foreach ([
            $networkAdmin,
            $networkManager,
            $regionAdmin,
            $regionManager,
            $unitAdmin,
            $unitManager,
            $instructor,
        ] as $role) {
            $role->permissions()->syncWithoutDetaching($workoutPermissions);
        }

        // The exercise catalog is available to every staff profile (students only consume it).
        $exercisePermissions = $allPermissions->whereIn('key', ['exercises.manage'])->pluck('id');

        foreach ([
            $networkAdmin,
            $networkManager,
            $regionAdmin,
            $regionManager,
            $unitAdmin,
            $unitManager,
            $receptionist,
            $instructor,
            $sales,
        ] as $role) {
            $role->permissions()->syncWithoutDetaching($exercisePermissions);
        }
    }
}

class PeopleSeeder extends Seeder
{
    /**
     * Valid CPF numbers used to seed staff and client records.
     *
     * @var array<int, string>
     */
    private const CPF_POOL = [
        '10000013773',
        '10000027480',
        '10000041122',
        '10000054887',
        '10000068594',
        '10000082236',
        '10000095990',
        '10000109614',
    ];

    public function run(): void
    {
        $users = User::with('vinculos')->orderBy('id')->get();

        foreach ($users as $index => $user) {
            if ($user->person_id) {
                continue;
            }

            $vinculo = $user->vinculos->firstWhere('status', 'active') ?? $user->vinculos->first();
            $document = self::CPF_POOL[$index % count(self::CPF_POOL)];

            $person = Person::firstOrCreate(
                [
                    'network_id' => $vinculo?->network_id,
                    'document_raw' => $document,
                ],
                [
                    'network_id' => $vinculo?->network_id,
                    'unit_id' => $vinculo?->unit_id,
                    'person_type' => 'individual',
                    'category' => 'staff',
                    'name' => $user->name,
                    'document' => BrazilianDocument::formatCpf($document),
                    'birth_date' => null,
                    'email' => $user->email,
                    'status' => 'active',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );

            $user->forceFill(['person_id' => $person->id])->save();
        }

        $network = Network::where('slug', 'smartgym-demo')->first();
        $unit = Unit::where('identifier', 'UNID001')->first();

        if (! $network || ! $unit) {
            return;
        }

        Person::firstOrCreate(
            [
                'network_id' => $network->id,
                'document_raw' => '10000123366',
            ],
            [
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'person_type' => 'individual',
                'category' => 'client',
                'name' => 'Maria Souza',
                'document' => BrazilianDocument::formatCpf('10000123366'),
                'birth_date' => '1992-05-18',
                'email' => 'maria.souza@exemplo.com',
                'phone' => '(41) 99999-1000',
                'whatsapp' => '(41) 99999-1000',
                'city' => 'Curitiba',
                'state' => 'PR',
                'status' => 'active',
            ]
        );

        Person::firstOrCreate(
            [
                'network_id' => $network->id,
                'document_raw' => '11222333000181',
            ],
            [
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'person_type' => 'company',
                'category' => 'client',
                'name' => 'Academia Parceira LTDA',
                'trade_name' => 'Academia Parceira',
                'document' => BrazilianDocument::formatCnpj('11222333000181'),
                'state_registration' => '1234567890',
                'legal_representative_name' => 'Carlos Pereira',
                'email' => 'contato@academiaparceira.com.br',
                'phone' => '(41) 3333-2000',
                'status' => 'active',
            ]
        );
    }
}

class DeveloperUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create the developer user as specified in the project document
        $developer = User::firstOrCreate(
            ['email' => 'jonathan3153975@gmail.com'],
            [
                'name' => 'Jonathan (Desenvolvedor)',
                'password' => Hash::make('Jonathan315'),
                'email_verified_at' => now(),
            ]
        );

        // Assign platform_admin role
        $platformAdminRole = Role::where('name', 'platform_admin')->where('level', 'platform')->first();
        if ($platformAdminRole) {
            UserVinculo::firstOrCreate(
                [
                    'user_id' => $developer->id,
                    'network_id' => null, // Platform level - no specific network
                    'role_id' => $platformAdminRole->id,
                ],
                [
                    'status' => 'active',
                    'granted_by' => $developer->id,
                ]
            );
        }

        // Create a default network for testing
        $plan = SaasPlan::where('slug', 'enterprise')->first();
        $network = Network::firstOrCreate(
            ['slug' => 'smartgym-demo'],
            [
                'name' => 'SmartGym Demo',
                'document' => '00.000.000/0001-00',
                'status' => 'active',
                'plan_id' => $plan?->id,
                'settings' => json_encode([
                    'primary_color' => '#00E5FF',
                    'secondary_color' => '#A855F7',
                    'accent_color' => '#C6FF00',
                ]),
            ]
        );

        // Create a region
        $region = Region::firstOrCreate(
            ['network_id' => $network->id, 'name' => 'Região Sul'],
            [
                'description' => 'Unidades da região sul',
                'status' => 'active',
            ]
        );

        // Create units
        $unit1 = Unit::firstOrCreate(
            ['identifier' => 'UNID001'],
            [
                'network_id' => $network->id,
                'region_id' => $region->id,
                'name' => 'SmartGym Matriz',
                'address' => 'Av. Principal, 1000 - Centro',
                'zip_code' => '80000-000',
                'street' => 'Av. Principal',
                'number' => '1000',
                'neighborhood' => 'Centro',
                'city' => 'Curitiba',
                'state' => 'PR',
                'phone' => '(41) 3000-0000',
                'email' => 'matriz@smartgym.com.br',
                'status' => 'active',
            ]
        );

        $unit2 = Unit::firstOrCreate(
            ['identifier' => 'UNID002'],
            [
                'network_id' => $network->id,
                'region_id' => $region->id,
                'name' => 'SmartGym Filial',
                'address' => 'Rua das Flores, 500 - Batel',
                'zip_code' => '80420-000',
                'street' => 'Rua das Flores',
                'number' => '500',
                'neighborhood' => 'Batel',
                'city' => 'Curitiba',
                'state' => 'PR',
                'phone' => '(41) 3000-1111',
                'email' => 'filial@smartgym.com.br',
                'status' => 'active',
            ]
        );

        // Create a network admin user
        $networkAdmin = User::firstOrCreate(
            ['email' => 'admin@smartgym.com.br'],
            [
                'name' => 'Admin da Rede',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
            ]
        );

        $networkAdminRole = Role::where('name', 'network_admin')->where('level', 'network')->first();
        if ($networkAdminRole) {
            UserVinculo::firstOrCreate(
                [
                    'user_id' => $networkAdmin->id,
                    'network_id' => $network->id,
                    'role_id' => $networkAdminRole->id,
                ],
                [
                    'status' => 'active',
                    'granted_by' => $developer->id,
                ]
            );
        }

        // Create a unit admin user
        $unitAdmin = User::firstOrCreate(
            ['email' => 'gerente@smartgym.com.br'],
            [
                'name' => 'Gerente da Unidade',
                'password' => Hash::make('Gerente123!'),
                'email_verified_at' => now(),
            ]
        );

        $unitAdminRole = Role::where('name', 'unit_admin')->where('level', 'unit')->first();
        if ($unitAdminRole) {
            UserVinculo::firstOrCreate(
                [
                    'user_id' => $unitAdmin->id,
                    'network_id' => $network->id,
                    'unit_id' => $unit1->id,
                    'role_id' => $unitAdminRole->id,
                ],
                [
                    'status' => 'active',
                    'granted_by' => $developer->id,
                ]
            );
        }

        // Create a receptionist user
        $receptionist = User::firstOrCreate(
            ['email' => 'recepcao@smartgym.com.br'],
            [
                'name' => 'Recepcionista',
                'password' => Hash::make('Recepcao123!'),
                'email_verified_at' => now(),
            ]
        );

        $receptionistRole = Role::where('name', 'receptionist')->where('level', 'unit')->first();
        if ($receptionistRole) {
            UserVinculo::firstOrCreate(
                [
                    'user_id' => $receptionist->id,
                    'network_id' => $network->id,
                    'unit_id' => $unit1->id,
                    'role_id' => $receptionistRole->id,
                ],
                [
                    'status' => 'active',
                    'granted_by' => $developer->id,
                ]
            );
        }
    }
}
