<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Exception;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $titulo='grupos';
        $grupos=Grupo::all();
        return view('modules.grupos.index',compact('titulo','grupos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $titulo = "crear grupo";
        return view('modules.grupos.create', compact('titulo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $grupo = new Grupo();
            $grupo->nombre_grupo = $request->nombre_grupo;
            $grupo->descripcion = $request->descripcion;
            $grupo->save();
            return to_route('grupos')->with('success', 'Grupo agregado exitosamente');
        } catch (Exception $e) {
            return to_route('grupos')->with('error', 'No se pudo agregar el grupo: ' . $e->getMessage());
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
        $titulo = "editar grupo";
        $grupo = Grupo::find($id);
        return view('modules.grupos.edit', compact('grupo', 'titulo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $grupo = Grupo::find($id);
            $grupo->nombre_grupo = $request->nombre_grupo;
            $grupo->descripcion = $request->descripcion;
            $grupo->save();
            return to_route('grupos')->with('success', 'Grupo editado correctamente');
        } catch (Exception $e) {
            return to_route('grupos')->with('error', 'No se pudo editar el grupo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $grupo = Grupo::find($id);
            $grupo->delete();
            return to_route('grupos')->with('success', 'El grupo se ha eliminado correctamente');
        } catch (Exception $e) {
            return to_route('grupos')->with('error', 'No se ha podido eliminar el grupo: ' . $e->getMessage());
        }
    }
}
