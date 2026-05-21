<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        try{
            $item=new User();
            $item->nombres=$request->nombres;
            $item->apellidos=$request->apellidos;
            $item->email=$request->email;
            $item->telefono=$request->telefono;
            $item->documento=$request->documento;
            $item->rol=$request->rol;
            $item->password=$request->password;
            $item->save();
            return to_route('usuarios')->with('success','Usuario agregado éxitosamente');
        }catch(Exception $e){
            return to_route('usuarios')->with('error','No se pudo agregar el usuario'.$e->getMessage());
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
