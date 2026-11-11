<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginVisualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function test_login_page_uses_the_system_logo_favicon_and_theme_switch(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/logo.png', false)
            ->assertSee('rel="icon"', false)
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('loginThemeToggle', false)
            ->assertSee('loginThemeIcon', false);
    }

    /** @test */
    public function test_other_guest_pages_use_the_same_brand_and_theme_switch(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('images/logo.png', false)
            ->assertSee('authThemeToggle', false)
            ->assertSee('rel="icon"', false);
    }

    /** @test */
    public function test_authenticated_layout_uses_the_system_favicon_and_brand_fallback(): void
    {
        $platformAdmin = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('images/logo.png', false)
            ->assertSee('rel="icon"', false);
    }
}
