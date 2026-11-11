<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Person;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private Network $network;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();

        $this->actingAs($this->networkAdmin);
    }

    /** @test */
    public function test_network_admin_can_list_and_open_the_people_panel(): void
    {
        $this->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee('Cadastro de pessoas')
            ->assertSee('Maria Souza')
            ->assertSee('Academia Parceira');

        $person = Person::where('document_raw', '10000123366')->firstOrFail();

        $this->get(route('admin.people.show', $person))
            ->assertOk()
            ->assertSee('Maria Souza')
            ->assertSee('Cliente');
    }

    /** @test */
    public function test_network_admin_can_create_an_individual_client(): void
    {
        $this->postJson(route('admin.people.store'), [
            'person_type' => 'individual',
            'category' => 'client',
            'name' => 'João da Silva',
            'document' => '10000137073',
            'birth_date' => '1990-03-12',
            'email' => 'joao@exemplo.com',
            'phone' => '(41) 98888-7777',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('people', [
            'network_id' => $this->network->id,
            'name' => 'João da Silva',
            'person_type' => 'individual',
            'category' => 'client',
            'document' => '100.001.370-73',
            'document_raw' => '10000137073',
            'unit_id' => $this->unit->id,
            'created_by' => $this->networkAdmin->id,
        ]);
    }

    /** @test */
    public function test_company_fields_are_persisted_and_individual_fields_are_cleared(): void
    {
        $this->postJson(route('admin.people.store'), [
            'person_type' => 'company',
            'category' => 'client',
            'name' => 'Empresa Exemplo LTDA',
            'trade_name' => 'Empresa Exemplo',
            'document' => '11444777000161',
            'state_registration' => '1234567890',
            'birth_date' => '1990-01-01',
            'legal_representative_name' => 'Ana Responsável',
            'legal_representative_document' => '10000123366',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $person = Person::where('document_raw', '11444777000161')->firstOrFail();

        $this->assertSame('company', $person->person_type);
        $this->assertSame('Empresa Exemplo', $person->trade_name);
        $this->assertSame('11.444.777/0001-61', $person->document);
        $this->assertNull($person->birth_date);

        $this->postJson(route('admin.people.store'), [
            'person_type' => 'individual',
            'category' => 'staff',
            'name' => 'Pessoa Física Equipe',
            'document' => '10000109614',
            'trade_name' => 'Não deve persistir',
            'state_registration' => '999',
            'legal_representative_name' => 'Não deve persistir',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $individual = Person::where('document_raw', '10000109614')->firstOrFail();

        $this->assertNull($individual->trade_name);
        $this->assertNull($individual->state_registration);
        $this->assertNull($individual->legal_representative_name);
    }

    /** @test */
    public function test_invalid_and_duplicated_documents_are_rejected(): void
    {
        $this->postJson(route('admin.people.store'), [
            'person_type' => 'individual',
            'category' => 'client',
            'name' => 'Documento Inválido',
            'document' => '11111111111',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('document');

        $this->postJson(route('admin.people.store'), [
            'person_type' => 'individual',
            'category' => 'client',
            'name' => 'Duplicada',
            'document' => '10000123366',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('document');
    }

    /** @test */
    public function test_people_are_isolated_between_networks(): void
    {
        $otherNetwork = Network::create([
            'name' => 'Rede Externa',
            'slug' => 'rede-externa-pessoas',
            'status' => 'active',
        ]);

        $otherPerson = Person::factory()->create([
            'network_id' => $otherNetwork->id,
            'name' => 'Pessoa Externa',
        ]);

        $this->get(route('admin.people.index'))
            ->assertOk()
            ->assertDontSee('Pessoa Externa');

        $this->get(route('admin.people.show', $otherPerson))->assertNotFound();
        $this->putJson(route('admin.people.update', $otherPerson), [
            'person_type' => 'individual',
            'category' => 'client',
            'name' => 'Tentativa',
            'document' => '10000137073',
            'status' => 'active',
        ])->assertNotFound();
    }

    /** @test */
    public function test_unit_users_only_see_people_from_their_unit(): void
    {
        $unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->actingAs($unitAdmin);

        $networkPerson = Person::factory()->create([
            'network_id' => $this->network->id,
            'unit_id' => null,
            'name' => 'Pessoa da Rede',
        ]);

        $unitPerson = Person::factory()->create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Pessoa da Unidade',
        ]);

        $this->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee('Pessoa da Unidade')
            ->assertDontSee('Pessoa da Rede');

        $this->get(route('admin.people.show', $networkPerson))->assertNotFound();
        $this->get(route('admin.people.show', $unitPerson))->assertOk();

        $this->postJson(route('admin.people.store'), [
            'person_type' => 'individual',
            'category' => 'staff',
            'name' => 'Nova Pessoa da Unidade',
            'document' => '10000082236',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('people', [
            'document_raw' => '10000082236',
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    /** @test */
    public function test_person_with_linked_users_cannot_be_deleted(): void
    {
        $person = Person::factory()->staff()->create([
            'network_id' => $this->network->id,
        ]);

        $this->deleteJson(route('admin.people.destroy', $person))
            ->assertOk();

        $linkedPerson = Person::factory()->staff()->create([
            'network_id' => $this->network->id,
        ]);

        $user = User::factory()->create(['person_id' => $linkedPerson->id]);

        UserVinculo::create([
            'user_id' => $user->id,
            'network_id' => $this->network->id,
            'role_id' => Role::where('name', 'network_admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->deleteJson(route('admin.people.destroy', $linkedPerson))
            ->assertStatus(422);

        $this->assertDatabaseHas('people', ['id' => $linkedPerson->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    /** @test */
    public function test_user_creation_requires_a_registered_person(): void
    {
        $role = Role::where('name', 'network_admin')->firstOrFail();

        $this->postJson(route('admin.users.store'), [
            'name' => 'Usuário Sem Pessoa',
            'email' => 'sem.pessoa@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $this->network->id,
            'role_id' => $role->id,
            'vinculo_status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('person_id');

        $person = Person::factory()->staff()->create([
            'network_id' => $this->network->id,
        ]);

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário Com Pessoa',
            'email' => 'com.pessoa@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $this->network->id,
            'role_id' => $role->id,
            'vinculo_status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email' => 'com.pessoa@smartgym.com.br',
            'person_id' => $person->id,
        ]);
    }
}
