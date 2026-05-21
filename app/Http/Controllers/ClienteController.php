<?php

namespace App\Http\Controllers;
use App\Models\Cliente;
use App\Services\ClienteService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Exception;
use InvalidArgumentException;

class ClienteController extends Controller
{
    public function __construct(protected ClienteService $clienteService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $titulo = "clientes";
        $clientes=Cliente::all();
        return view('modules.clientes.index',compact('titulo','clientes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $titulo = "crear clientes";
        return view('modules.clientes.create',compact('titulo'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $datos = [
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'documento' => $request->documento,
                'estado' => $request->estado,
            ];

            $this->clienteService->guardarCliente($datos);
            return to_route('clientes')->with('success','Cliente agregado éxitosamente');
        }catch(InvalidArgumentException $e){
            return to_route('clientes')->with('error',$e->getMessage());
        }catch(Exception $e){
            return to_route('clientes')->with('error','No se pudo agregar el cliente: '.$e->getMessage());
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
        $titulo ="editar disciplinas";
        $clientes=Cliente::find($id);
        return view('modules.clientes.edit',compact('clientes','titulo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $datos = [
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'documento' => $request->documento,
                'estado' => $request->estado,
            ];

            $this->clienteService->actualizarCliente($id, $datos);
            return to_route('clientes')->with('success','Cliente actualizado correctamente');
        }catch(InvalidArgumentException $e){
            return to_route('clientes')->with('error',$e->getMessage());
        }catch(Exception $e){
            return to_route('clientes')->with('error','No se pudo editar: '.$e->getMessage());
        }
    }

    public function buscarPorDocumento(Request $request)
    {
        $request->validate([
            'documento' => 'required|string|max:50',
        ]);

        $cliente = Cliente::where('documento', $request->documento)->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún cliente con esa cédula.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'nombres' => $cliente->nombres ?? $cliente->nombre,
                'apellidos' => $cliente->apellidos ?? $cliente->apellido,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'documento' => $cliente->documento,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $cliente=Cliente::find($id);
            $cliente->delete();
            return to_route('clientes')->with('sucess','El cliente se ha eliminado correctamente');
        }catch(Exception $e){
            return to_route('clientes')->with('error','No se ha podido eliminar al cliente'.$e->getMessage());
        }
    }
}
