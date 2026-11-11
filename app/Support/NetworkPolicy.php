<?php

namespace App\Support;

use App\Models\Network;
use Illuminate\Validation\Rule;

/**
 * Organizational policies of a network.
 *
 * Each domain (people, plans, modalities, team and governance) defines the
 * scope in which its records exist, who may administer them and whether lower
 * levels may override what was defined above.
 */
class NetworkPolicy
{
    /**
     * Organizational levels used by the scope fields.
     *
     * @var array<string, string>
     */
    public const SCOPE_OPTIONS = [
        'network' => 'Toda a rede (global)',
        'region' => 'Por região',
        'unit' => 'Por unidade',
    ];

    /**
     * Who is allowed to administer a configuration domain.
     *
     * @var array<string, string>
     */
    public const MANAGEMENT_OPTIONS = [
        'network' => 'Administração da rede',
        'region' => 'Administração regional',
        'unit' => 'Administração da unidade',
        'inherited' => 'Seguir o escopo do cadastro',
    ];

    /**
     * How records are offered to the person (student) when choosing.
     *
     * @var array<string, string>
     */
    public const AVAILABILITY_OPTIONS = [
        'all' => 'Todas as unidades da rede',
        'region' => 'Somente as unidades da região',
        'unit' => 'Somente a unidade do cadastro',
    ];

    /**
     * Complete policy definition, used to render the form, validate the input
     * and build the defaults.
     *
     * @return array<string, array{title: string, description: string, icon: string, fields: array<string, array<string, mixed>>}>
     */
    public static function schema(): array
    {
        return [
            'pessoas' => [
                'title' => 'Pessoas e alunos',
                'description' => 'Define onde o cadastro de pessoas existe e como ele circula entre unidades e regiões.',
                'icon' => 'fa-user-graduate',
                'fields' => [
                    'scope' => self::select(
                        'Escopo do cadastro',
                        self::SCOPE_OPTIONS,
                        'network',
                        'Nível em que as pessoas são cadastradas e consultadas dentro da rede.'
                    ),
                    'management_scope' => self::select(
                        'Quem administra os cadastros',
                        self::MANAGEMENT_OPTIONS,
                        'network',
                        'Perfil responsável por criar, editar e inativar pessoas.'
                    ),
                    'share_across_units' => self::toggle(
                        'Compartilhar pessoas entre unidades',
                        true,
                        'Permite vincular a mesma pessoa a várias unidades sem duplicar o cadastro.'
                    ),
                    'primary_unit_required' => self::toggle(
                        'Exigir unidade principal',
                        true,
                        'Toda pessoa precisa de uma unidade de referência, além dos vínculos adicionais.'
                    ),
                    'transfer_allowed' => self::toggle(
                        'Permitir transferência entre unidades',
                        true,
                        'Possibilita mover o vínculo principal de uma unidade para outra mantendo o histórico.'
                    ),
                    'cpf_unique_scope' => self::select(
                        'Unicidade do CPF',
                        [
                            'network' => 'Único por rede',
                            'unit' => 'Único por unidade',
                        ],
                        'network',
                        'Evita cadastros duplicados dentro do escopo escolhido.'
                    ),
                ],
            ],
            'planos' => [
                'title' => 'Planos e matrículas',
                'description' => 'Define quem cria os planos, como eles chegam às unidades e o que o aluno pode contratar.',
                'icon' => 'fa-layer-group',
                'fields' => [
                    'scope' => self::select(
                        'Escopo dos planos',
                        self::SCOPE_OPTIONS,
                        'network',
                        'Nível em que os planos comerciais são definidos.'
                    ),
                    'management_scope' => self::select(
                        'Quem administra os planos',
                        self::MANAGEMENT_OPTIONS,
                        'network',
                        'Perfil responsável pela criação e manutenção dos planos.'
                    ),
                    'inherit_definitions' => self::toggle(
                        'Herdar planos dos níveis superiores',
                        true,
                        'Unidades e regiões passam a ver automaticamente os planos definidos acima.'
                    ),
                    'allow_local_plans' => self::toggle(
                        'Permitir planos locais',
                        false,
                        'Autoriza planos exclusivos de uma região ou unidade, além dos herdados.'
                    ),
                    'require_approval' => self::toggle(
                        'Exigir aprovação da rede',
                        true,
                        'Planos criados em níveis inferiores dependem de aprovação da rede antes de valer.'
                    ),
                    'price_by_unit' => self::toggle(
                        'Permitir preço diferenciado por unidade',
                        false,
                        'Cada unidade pode praticar um preço próprio sobre o mesmo plano.'
                    ),
                    'student_visibility' => self::select(
                        'Planos visíveis ao aluno',
                        self::AVAILABILITY_OPTIONS,
                        'unit',
                        'Restringe o catálogo apresentado ao aluno no momento da contratação.'
                    ),
                    'enforce_student_unit' => self::toggle(
                        'Restringir à unidade do aluno',
                        true,
                        'O aluno só contrata planos disponíveis na unidade em que está vinculado.'
                    ),
                ],
            ],
            'modalidades' => [
                'title' => 'Modalidades e serviços',
                'description' => 'Define como as modalidades são criadas e quais unidades podem oferecê-las.',
                'icon' => 'fa-dumbbell',
                'fields' => [
                    'scope' => self::select(
                        'Escopo das modalidades',
                        self::SCOPE_OPTIONS,
                        'network',
                        'Nível em que as modalidades e serviços são definidos.'
                    ),
                    'management_scope' => self::select(
                        'Quem administra as modalidades',
                        self::MANAGEMENT_OPTIONS,
                        'network',
                        'Perfil responsável pela criação e manutenção das modalidades.'
                    ),
                    'inherit_definitions' => self::toggle(
                        'Herdar modalidades dos níveis superiores',
                        true,
                        'As unidades recebem automaticamente as modalidades definidas acima.'
                    ),
                    'allow_local_modalities' => self::toggle(
                        'Permitir modalidades locais',
                        false,
                        'Autoriza modalidades exclusivas de uma região ou unidade.'
                    ),
                    'availability' => self::select(
                        'Disponibilidade nas unidades',
                        self::AVAILABILITY_OPTIONS,
                        'unit',
                        'Define quais unidades podem ofertar cada modalidade.'
                    ),
                    'allow_cross_unit' => self::toggle(
                        'Permitir aluno em modalidades de outras unidades',
                        false,
                        'O aluno pode frequentar modalidades fora da unidade em que está vinculado.'
                    ),
                ],
            ],
            'equipe' => [
                'title' => 'Equipe e usuários',
                'description' => 'Define o alcance dos vínculos da equipe e a autonomia das unidades na gestão de usuários.',
                'icon' => 'fa-users-gear',
                'fields' => [
                    'scope' => self::select(
                        'Escopo dos vínculos de equipe',
                        self::SCOPE_OPTIONS,
                        'network',
                        'Nível em que os vínculos de equipe são concedidos.'
                    ),
                    'management_scope' => self::select(
                        'Quem administra os usuários',
                        self::MANAGEMENT_OPTIONS,
                        'network',
                        'Perfil responsável por criar usuários e conceder vínculos.'
                    ),
                    'allow_multiple_vinculos' => self::toggle(
                        'Permitir múltiplos vínculos',
                        true,
                        'Um mesmo usuário pode atuar em mais de uma unidade ou região, com perfis diferentes.'
                    ),
                    'require_network_approval' => self::toggle(
                        'Exigir aprovação da rede',
                        false,
                        'Novos vínculos criados em níveis inferiores dependem de aprovação da administração da rede.'
                    ),
                    'unit_manages_users' => self::toggle(
                        'Permitir que a unidade gerencie seus usuários',
                        false,
                        'A unidade pode criar e editar usuários dentro do seu próprio escopo.'
                    ),
                ],
            ],
            'governanca' => [
                'title' => 'Governança e auditoria',
                'description' => 'Ajusta a autonomia das unidades, o uso de regiões e o rastreamento das alterações.',
                'icon' => 'fa-scale-balanced',
                'fields' => [
                    'unit_autonomy' => self::select(
                        'Autonomia das unidades',
                        [
                            'low' => 'Baixa — a rede define quase tudo',
                            'medium' => 'Média — a unidade ajusta o operacional',
                            'high' => 'Alta — a unidade administra o próprio ambiente',
                        ],
                        'low',
                        'Nível de liberdade das unidades para criar e alterar configurações.'
                    ),
                    'regions_enabled' => self::toggle(
                        'Usar regiões na estrutura',
                        true,
                        'Quando desativado, as unidades passam a ser vinculadas diretamente à rede.'
                    ),
                    'audit_policy_changes' => self::toggle(
                        'Registrar alterações de parâmetros',
                        true,
                        'Grava na trilha de auditoria quem alterou estas configurações e quando.'
                    ),
                ],
            ],
        ];
    }

    /**
     * Build the validation rules for the policy fields.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::schema() as $domain => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $rules["policies.{$domain}.{$key}"] = $field['type'] === 'select'
                    ? ['nullable', Rule::in(array_keys($field['options']))]
                    : ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    /**
     * Default policy values, extracted from the schema.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::schema() as $domain => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $defaults[$domain][$key] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * Resolve the policy of a network, falling back to the defaults.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function forNetwork(?Network $network): array
    {
        $stored = $network?->settings['policies'] ?? [];

        return self::normalize($stored);
    }

    /**
     * Normalize an arbitrary (stored or validated) payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, array<string, mixed>>
     */
    public static function normalize(array $payload): array
    {
        $normalized = [];

        foreach (self::schema() as $domain => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $value = data_get($payload, "{$domain}.{$key}");

                if ($field['type'] === 'select') {
                    $normalized[$domain][$key] = is_string($value) && array_key_exists($value, $field['options'])
                        ? $value
                        : $field['default'];

                    continue;
                }

                $normalized[$domain][$key] = $value === null
                    ? (bool) $field['default']
                    : filter_var($value, FILTER_VALIDATE_BOOL);
            }
        }

        return $normalized;
    }

    /**
     * Merge the submitted values with the current policy of the network.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, array<string, mixed>>
     */
    public static function merge(array $validated, ?Network $network): array
    {
        $payload = data_get($validated, 'policies', []);

        $merged = self::forNetwork($network);

        foreach (self::schema() as $domain => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                if (! is_array($payload) || ! array_key_exists($domain, $payload) || ! array_key_exists($key, $payload[$domain])) {
                    continue;
                }

                $value = $payload[$domain][$key];

                if ($field['type'] === 'select') {
                    if (is_string($value) && array_key_exists($value, $field['options'])) {
                        $merged[$domain][$key] = $value;
                    }

                    continue;
                }

                $merged[$domain][$key] = filter_var($value, FILTER_VALIDATE_BOOL);
            }
        }

        return $merged;
    }

    /**
     * Read a single policy value of a network.
     */
    public static function get(?Network $network, string $domain, string $key, mixed $default = null): mixed
    {
        return data_get(self::forNetwork($network), "{$domain}.{$key}", $default);
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private static function select(string $label, array $options, string $default, string $help = ''): array
    {
        return [
            'type' => 'select',
            'label' => $label,
            'options' => $options,
            'default' => $default,
            'help' => $help,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function toggle(string $label, bool $default, string $help = ''): array
    {
        return [
            'type' => 'toggle',
            'label' => $label,
            'default' => $default,
            'help' => $help,
        ];
    }
}
