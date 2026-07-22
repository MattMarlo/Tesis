<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $titulo='Administrar usuarios';
        $usuarios= User::all();
        return view('modules.usuarios.index',compact('titulo','usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $titulo='Crear usuarios';
        return view('modules.usuarios.create',compact('titulo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'documento' => ['required', 'string', 'max:30', 'unique:users,documento'],
            'rol' => ['required', 'in:admin,agente'],
            'password' => ['required', 'string', 'min:6'],
        ];

        $datos = $request->validate($rules);

        try {
            $usuario = new User();
            $usuario->nombres = $datos['nombres'];
            $usuario->apellidos = $datos['apellidos'];
            $usuario->email = $datos['email'];
            $usuario->telefono = $datos['telefono'];
            $usuario->documento = $datos['documento'];
            $usuario->rol = $datos['rol'];
            $usuario->password = Hash::make($datos['password']);
            $usuario->save();

            return to_route('usuarios')->with('success', 'Usuario agregado éxitosamente');
        } catch (Exception $e) {
            return to_route('usuarios')->with('error', 'No se pudo agregar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $titulo = 'Editar usuario';
        $usuario = User::findOrFail($id);
        return view('modules.usuarios.edit', compact('titulo','usuario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        $rules = [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,'.$id],
            'telefono' => ['required', 'string', 'max:20'],
            'documento' => ['required', 'string', 'max:30', 'unique:users,documento,'.$id],
            'rol' => ['required', 'in:admin,agente'],
            'password' => ['nullable', 'string', 'min:6'],
        ];

        $datos = $request->validate($rules);

        try {
            $usuario->nombres = $datos['nombres'];
            $usuario->apellidos = $datos['apellidos'];
            $usuario->email = $datos['email'];
            $usuario->telefono = $datos['telefono'];
            $usuario->documento = $datos['documento'];
            $usuario->rol = $datos['rol'];

            if (!empty($datos['password'])) {
                $usuario->password = Hash::make($datos['password']);
            }

            $usuario->save();

            return to_route('usuarios')->with('success', 'Usuario actualizado éxitosamente');
        } catch (Exception $e) {
            return to_route('usuarios')->with('error', 'No se pudo actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $usuario = User::findOrFail($id);
            $usuario->delete();
            return to_route('usuarios')->with('success','Usuario eliminado éxitosamente');
        }catch(Exception $e){
            return to_route('usuarios')->with('error','No se pudo eliminar el usuario: '.$e->getMessage());
        }
    }
}
