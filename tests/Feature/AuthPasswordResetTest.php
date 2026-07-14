<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPasswordResetTest extends TestCase
{
    public function test_forgot_password_page_is_available(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Recuperar Contraseña');
    }
}
