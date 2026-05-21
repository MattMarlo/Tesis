<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DestinoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $titulo='Destinos';
        $destinos = Destino::all();
        return view('modules.destinos.index',compact('titulo','destinos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $titulo='crear destinos';
        return view('modules.destinos.create',compact('titulo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('imagen')) {
            $ruta = $request->file('imagen')->store('destinos', 'public');
        }
        try{
            $destino = new Destino();
            $destino->etiqueta=$request->etiqueta;
            $destino->pais=$request->pais;
            $destino->precio=$request->precio;
            $destino->dias=$request->dias;
            $destino->capacidad=$request->capacidad;
            $destino->imagen=$ruta;
            $destino->save();
            return to_route('destinos')->with('success','El destino se ha creado correctamente');
        }catch(Exception $e){
            return to_route('destinos')->with('error','No se pudo crear el destino'.$e->getMessage());
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
        $titulo="editar destinos";
        $destinos= Destino::find($id);
        return view('modules.destinos.edit',compact('titulo','destinos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        try{
            $destino = Destino::find($id);
            $destino->etiqueta=$request->etiqueta;
            $destino->pais=$request->pais;
            $destino->precio=$request->precio;
            $destino->dias=$request->dias;
            $destino->capacidad=$request->capacidad;
            if($request->hasFile('imagen')){
                //eliminamos la imgen anteror
                if($destino->imagen){
                    Storage::disk('public')->delete($destino->imagen);
                }
                // guardamos la nueva imagen
                $destino->imagen=$request->file('imagen')->store('destinos','public');
            }
            $destino->save();
            return to_route('destinos')->with('sucess','se ha editado correctamente');
        }catch(Exception $e){
            return to_route('destinos')->with('error','No se pudo crear el Destino'.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try{
            $destino=Destino::find($id);
            $destino->delete();
            return to_route('destinos')->with('sucess','Se ha eliminado exitosamente');
        }catch(Exception $e){
            return to_route('destinos')->with('error','no se ha podido eliminar el destino'.$e->getMessage());
        }
    }
}
