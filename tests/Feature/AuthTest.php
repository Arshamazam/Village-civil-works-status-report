<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('PRSWSSP');
    }

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@prmsc.gov.pk',
            'password' => 'Admin@12345',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => 'field.t01@prmsc.gov.pk',
            'password' => 'Field@12345',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_five_failed_attempts_locks_account_for_15_minutes(): void
    {
        $user = User::where('email', 'field.t01@prmsc.gov.pk')->first();

        // 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'email' => 'field.t01@prmsc.gov.pk',
                'password' => 'WrongPassword',
            ]);
        }

        $user->refresh();
        $this->assertEquals(4, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);

        // 5th failed attempt locks
        $response = $this->post('/login', [
            'email' => 'field.t01@prmsc.gov.pk',
            'password' => 'WrongPassword',
        ]);

        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        // 6th attempt with correct password while locked still fails
        $response = $this->post('/login', [
            'email' => 'field.t01@prmsc.gov.pk',
            'password' => 'Field@12345',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_force_password_change_redirects_user(): void
    {
        $user = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $user->update(['force_password_change' => true]);

        $response = $this->post('/login', [
            'email' => 'field.t01@prmsc.gov.pk',
            'password' => 'Field@12345',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/password/force-change');
    }
}
