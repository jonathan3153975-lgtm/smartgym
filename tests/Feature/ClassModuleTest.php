<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Network;
use App\Models\Region;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Region $region;

    private Unit $unit;

    private Unit $otherUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->region = Region::where('network_id', $this->network->id)->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();
    }

    /** @test */
    public function test_network_admin_can_create_a_class_with_a_teacher_from_the_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'teacher_id' => $this->unitAdmin->id,
            'name' => 'Musculação Manhã',
            'weekday' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $class = SchoolClass::where('name', 'Musculação Manhã')->firstOrFail();

        $this->assertSame($this->network->id, $class->network_id);
        $this->assertSame($this->unit->id, $class->unit_id);
        $this->assertSame($this->unitAdmin->id, $class->teacher_id);
        $this->assertSame('Segunda-feira', $class->weekday_label);
        $this->assertSame('07:00 - 08:00', $class->time_label);
        $this->assertSame(10, $class->vacancies);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Musculação Manhã')
            ->assertSee('Segunda-feira');
    }

    /** @test */
    public function test_teacher_must_be_linked_to_the_class_unit(): void
    {
        $instructor = User::factory()->create(['name' => 'Professor da Outra Unidade']);

        UserVinculo::create([
            'user_id' => $instructor->id,
            'network_id' => $this->network->id,
            'unit_id' => $this->otherUnit->id,
            'role_id' => Role::where('name', 'instructor')->firstOrFail()->id,
            'vacancies' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'teacher_id' => $instructor->id,
            'name' => 'Turma Inválida',
            'weekday' => 2,
            'start_time' => '09:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('teacher_id');
    }

    /** @test */
    public function test_unit_is_required_and_unit_admin_is_limited_to_their_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'name' => 'Sem unidade',
            'weekday' => 2,
            'start_time' => '09:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('unit_id');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->otherUnit->id,
            'name' => 'Turma da Outra Unidade',
            'weekday' => 3,
            'start_time' => '10:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertOk();

        $this->actingAs($this->unitAdmin);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertDontSee('Turma da Outra Unidade');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->otherUnit->id,
            'name' => 'Turma Bloqueada',
            'weekday' => 4,
            'start_time' => '11:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('unit_id');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma da Minha Unidade',
            'weekday' => 5,
            'start_time' => '18:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertOk();
    }

    /** @test */
    public function test_class_can_be_updated_and_removed(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma Editável',
            'weekday' => 6,
            'start_time' => '08:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertOk();

        $class = SchoolClass::where('name', 'Turma Editável')->firstOrFail();

        $this->putJson(route('admin.classes.update', $class), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma Atualizada',
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '10:30',
            'vacancies' => 10,
            'status' => 'inactive',
        ])->assertOk();

        $class = $class->fresh();

        $this->assertSame('Turma Atualizada', $class->name);
        $this->assertSame('Domingo', $class->weekday_label);
        $this->assertSame('09:00 - 10:30', $class->time_label);
        $this->assertSame('inactive', $class->status);

        $this->deleteJson(route('admin.classes.destroy', $class))
            ->assertOk();

        $this->assertSoftDeleted('classes', ['id' => $class->id]);
    }

    /** @test */
    public function test_class_can_be_linked_to_a_modality_available_in_the_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Funcional',
        ]);
        $modality->units()->attach($this->unit->id);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Funcional Manhã',
            'weekday' => 2,
            'start_time' => '07:00',
            'vacancies' => 15,
            'status' => 'active',
        ])->assertOk();

        $class = SchoolClass::where('name', 'Funcional Manhã')->firstOrFail();

        $this->assertSame($modality->id, $class->modality_id);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Funcional Manhã')
            ->assertSee('Funcional');
    }

    /** @test */
    public function test_modality_must_be_available_in_the_class_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Spinning',
        ]);
        $modality->units()->attach($this->otherUnit->id);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Spinning Inválido',
            'weekday' => 3,
            'start_time' => '08:00',
            'vacancies' => 10,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('modality_id');
    }
}
