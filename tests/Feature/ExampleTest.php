<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_ends_an_existing_session_and_requires_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('login'))
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

        $this->assertGuest();
    }
}
