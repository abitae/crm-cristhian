<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_not_publicly_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'Nuevo', 'email' => 'nuevo@example.com', 'password' => 'password', 'password_confirmation' => 'password'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }
}
