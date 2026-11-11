<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Region;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNetworkManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->platformAdmin = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();
        $this->actingAs($this->platformAdmin);
    }

    /** @test */
    public function test_admin_can_access_networks_panel(): void
    {
        $response = $this->get(route('admin.networks.index'));

        $response->assertOk();
        $response->assertSee('Cadastro de redes');
        $response->assertSee('Nova rede');
        $response->assertSee('SmartGym Demo');
    }

    /** @test */
    public function test_dynamic_search_returns_only_matching_networks(): void
    {
        Network::create([
            'name' => 'Rede Alpha',
            'slug' => 'rede-alpha',
            'responsible_name' => 'Maria Souza',
            'city' => 'São Paulo',
            'status' => 'active',
        ]);

        Network::create([
            'name' => 'Rede Beta',
            'slug' => 'rede-beta',
            'responsible_name' => 'João Lima',
            'city' => 'Curitiba',
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.networks.index', ['search' => 'Maria']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('Rede Alpha');
        $response->assertDontSee('Rede Beta');
    }

    /** @test */
    public function test_network_is_created_active_with_generated_slug(): void
    {
        $response = $this->postJson(route('admin.networks.store'), [
            'name' => 'Rede Nova',
            'city' => 'Porto Alegre',
            'state' => 'RS',
            'responsible_name' => 'Carlos Pereira',
            'responsible_cpf' => '123.456.789-00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $network = Network::where('name', 'Rede Nova')->firstOrFail();

        $this->assertSame('rede-nova', $network->slug);
        $this->assertSame('active', $network->status);
        $this->assertSame('Carlos Pereira', $network->responsible_name);
    }

    /** @test */
    public function test_network_can_be_updated_and_keeps_status(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $response = $this->putJson(route('admin.networks.update', $network), [
            'name' => 'SmartGym Demo Atualizada',
            'responsible_name' => 'Novo Responsável',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $network->refresh();

        $this->assertSame('SmartGym Demo Atualizada', $network->name);
        $this->assertSame('Novo Responsável', $network->responsible_name);
        $this->assertSame('active', $network->status);
    }

    /** @test */
    public function test_network_json_is_available_for_the_edit_modal(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $response = $this->getJson(route('admin.networks.show', $network));

        $response->assertOk();
        $response->assertJsonPath('id', $network->id);
        $response->assertJsonPath('name', $network->name);
        $response->assertJsonStructure([
            'name',
            'slug',
            'document',
            'plan_id',
            'regions_count',
            'units_count',
        ]);
    }

    /** @test */
    public function test_network_status_can_be_toggled(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $this->patchJson(route('admin.networks.toggle-status', $network))
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->assertSame('inactive', $network->fresh()->status);

        $this->patchJson(route('admin.networks.toggle-status', $network))
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->assertSame('active', $network->fresh()->status);
    }

    /** @test */
    public function test_show_page_lists_unlinked_units_before_regions(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $region = $network->regions()->firstOrFail();

        $unlinkedUnit = Unit::create([
            'network_id' => $network->id,
            'name' => 'Unidade Sem Região',
            'identifier' => 'SEMREGIAO01',
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.networks.show', $network));

        $response->assertOk();
        $response->assertSee('Unidades sem região');
        $response->assertSee($unlinkedUnit->name);
        $response->assertSee($region->name);

        $html = $response->getContent();
        $this->assertLessThan(
            mb_strpos($html, 'Região Sul'),
            mb_strpos($html, 'Unidade Sem Região')
        );
    }

    /** @test */
    public function test_region_is_created_active(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $response = $this->postJson(route('admin.regions.store'), [
            'network_id' => $network->id,
            'name' => 'Região Norte',
            'description' => 'Unidades do norte',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('regions', [
            'network_id' => $network->id,
            'name' => 'Região Norte',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_unit_is_created_active_and_linked_to_region(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $region = Region::where('network_id', $network->id)->firstOrFail();

        $response = $this->postJson(route('admin.units.store'), [
            'network_id' => $network->id,
            'region_id' => $region->id,
            'name' => 'Unidade Nova',
            'identifier' => 'UNID-NOVA-01',
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('units', [
            'network_id' => $network->id,
            'region_id' => $region->id,
            'name' => 'Unidade Nova',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_unit_cannot_be_linked_to_region_of_another_network(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $otherNetwork = Network::create([
            'name' => 'Outra Rede',
            'slug' => 'outra-rede',
            'status' => 'active',
        ]);

        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região de Outra Rede',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('admin.units.store'), [
            'network_id' => $network->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade Inválida',
            'identifier' => 'UNID-INVALIDA',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('region_id');
    }

    /** @test */
    public function test_network_admin_cannot_access_networks_panel(): void
    {
        $networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();

        $this->actingAs($networkAdmin)
            ->get(route('admin.networks.index'))
            ->assertForbidden();
    }
}
