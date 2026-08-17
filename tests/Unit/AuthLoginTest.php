<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AuthLoginTest extends TestCase
{
    public function test_usuario_activo_puede_iniciar_sesion_con_credenciales_validas(): void
    {
        $usuario = $this->crearUsuarioSimulado(true);
        $this->simularBusquedaDeUsuario($usuario);

        Hash::shouldReceive('check')
            ->once()
            ->with('ClaveSegura123', 'hash-prueba')
            ->andReturnTrue();

        Auth::shouldReceive('login')
            ->once()
            ->with($usuario);

        $request = $this->crearRequest([
            'email' => 'usuario@example.com',
            'password' => 'ClaveSegura123',
        ]);
        $idSesionAnterior = $request->session()->getId();

        $respuesta = (new AuthController())->login($request);

        $this->assertInstanceOf(RedirectResponse::class, $respuesta);
        $this->assertSame(route('main'), $respuesta->getTargetUrl());
        $this->assertNotSame($idSesionAnterior, $request->session()->getId());
        $this->assertSame(
            'Bienvenido al sistema, Usuario.',
            $request->session()->get('success')
        );
    }

    public function test_login_normaliza_mayusculas_del_correo(): void
    {
        $usuario = $this->crearUsuarioSimulado(true);
        $this->simularBusquedaDeUsuario(
            $usuario,
            'usuario@example.com'
        );

        Hash::shouldReceive('check')
            ->once()
            ->andReturnTrue();

        Auth::shouldReceive('login')
            ->once()
            ->with($usuario);

        $request = $this->crearRequest([
            'email' => 'USUARIO@EXAMPLE.COM',
            'password' => 'ClaveSegura123',
        ]);

        $respuesta = (new AuthController())->login($request);

        $this->assertSame(route('main'), $respuesta->getTargetUrl());
    }

    public function test_contrasena_incorrecta_no_inicia_sesion(): void
    {
        $usuario = $this->crearUsuarioSimulado(true);
        $this->simularBusquedaDeUsuario($usuario);

        Hash::shouldReceive('check')
            ->once()
            ->with('ClaveIncorrecta123', 'hash-prueba')
            ->andReturnFalse();

        Auth::shouldReceive('login')->never();

        $request = $this->crearRequest([
            'email' => 'usuario@example.com',
            'password' => 'ClaveIncorrecta123',
        ]);

        $respuesta = (new AuthController())->login($request);

        $this->assertSame(
            '/login',
            parse_url($respuesta->getTargetUrl(), PHP_URL_PATH)
        );
        $this->assertTrue(
            $request->session()->get('errors')->has('email')
        );
        $this->assertSame(
            'usuario@example.com',
            $request->session()->getOldInput('email')
        );
    }

    public function test_usuario_inexistente_no_inicia_sesion(): void
    {
        $this->simularBusquedaDeUsuario(
            null,
            'inexistente@example.com'
        );

        Hash::shouldReceive('check')->never();
        Auth::shouldReceive('login')->never();

        $request = $this->crearRequest([
            'email' => 'inexistente@example.com',
            'password' => 'ClaveSegura123',
        ]);

        $respuesta = (new AuthController())->login($request);

        $this->assertSame(
            '/login',
            parse_url($respuesta->getTargetUrl(), PHP_URL_PATH)
        );
        $this->assertTrue(
            $request->session()->get('errors')->has('email')
        );
    }

    public function test_usuario_inactivo_no_inicia_sesion(): void
    {
        $usuario = $this->crearUsuarioSimulado(false);
        $this->simularBusquedaDeUsuario($usuario);

        Hash::shouldReceive('check')
            ->once()
            ->andReturnTrue();

        Auth::shouldReceive('login')->never();

        $request = $this->crearRequest([
            'email' => 'usuario@example.com',
            'password' => 'ClaveSegura123',
        ]);

        $respuesta = (new AuthController())->login($request);

        $this->assertSame(
            '/login',
            parse_url($respuesta->getTargetUrl(), PHP_URL_PATH)
        );
        $this->assertTrue(
            $request->session()->get('errors')->has('email')
        );
    }

    public function test_login_valida_correo_y_contrasena(): void
    {
        Auth::shouldReceive('login')->never();

        $request = $this->crearRequest([
            'email' => 'correo-invalido',
            'password' => '',
        ]);

        try {
            (new AuthController())->login($request);
            $this->fail('Se esperaba una excepción de validación.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('email', $excepcion->errors());
            $this->assertArrayHasKey('password', $excepcion->errors());
        }
    }

    private function crearRequest(array $datos): Request
    {
        $request = Request::create(
            '/login',
            'POST',
            $datos,
            [],
            [],
            [
                'HTTP_REFERER' => route('login'),
            ]
        );

        $sesion = $this->app->make('session')->driver();
        $sesion->start();
        $request->setLaravelSession($sesion);
        $this->app->instance('request', $request);

        return $request;
    }

    private function crearUsuarioSimulado(bool $activo): object
    {
        return new class ($activo) {
            public string $nombres = 'Usuario';

            public string $password = 'hash-prueba';

            public function __construct(private readonly bool $activo)
            {
            }

            public function estaActivo(): bool
            {
                return $this->activo;
            }
        };
    }

    private function simularBusquedaDeUsuario(
        ?object $usuario,
        string $correo = 'usuario@example.com'
    ): void {
        $consulta = Mockery::mock();
        $consulta->shouldReceive('first')
            ->once()
            ->andReturn($usuario);

        Mockery::mock('alias:App\\Models\\User')
            ->shouldReceive('where')
            ->once()
            ->with('email', $correo)
            ->andReturn($consulta);
    }
}
