<?php

namespace App\Http\Controllers;
use App\Models\Cliente;
use App\Services\ClienteService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Exception;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    public function __construct(protected ClienteService $clienteService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $titulo = "clientes";
        $query = Cliente::query();
    
        // Si hay un documento en la URL, filtrar
        if ($request->filled('documento')) {
            $documento = $request->documento;
            $query->where('documento', 'LIKE', "%$documento%");
        }
        
        $clientes = $query->get(); // o paginate(10)
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
        try {
            /*$request->validate([
                'nombres'   => 'required|string|max:250',
                'apellidos' => 'required|string|max:250',
                'email'     => 'required|email|max:50|unique:clientes,email',
                'telefono'  => 'required|string|max:20',
                'documento' => 'required|string|max:50|unique:clientes,documento',
                'estado'    => 'required|in:activo,inactivo',
                'archivo'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 👈 VALIDACIÓN AQUÍ
            ]);*/
           
            $datos = [
                'nombres'   => $request->nombres,
                'apellidos' => $request->apellidos,
                'email'     => $request->email,
                'telefono'  => $request->telefono,
                'documento' => $request->documento,
                'estado'    => $request->estado,
                'archivo'  => $request->archivo,
            ];
             // 3️ Subir archivo correctamente
            if ($request->hasFile('archivo')) {
                $ruta = $request->file('archivo')->store('clientes', 'public');
                $datos['archivo'] = $ruta;
                
            } 
            //$this->clienteService->guardarCliente($datos);
            $cliente=$this->clienteService->guardarCliente($datos);
            if (isset($datos['archivo'])) {
                $cliente->archivo = $datos['archivo'];
                $cliente->save();
                
            }
            //Si la petición espera json (desde el modal principal)
            if($request->expectsJson()){
                return response()->json([
                    'success'=>true,
                    'message'=>'Cliente creado exitosamente.',
                    'cliente'=> [
                        'id' => $cliente->id,
                        'nombres' => $cliente->nombres,
                        'apellidos' =>$cliente->apellidos,
                        'email'=>$cliente->email,
                        'telefono'=>$cliente->telefono,
                        'documento'=>$cliente->documento,
                        'archivo'   => $cliente->archivo ? Storage::url($cliente->archivo) : null,
                    ]
                ]);
            };

            return to_route('clientes')->with('success', 'Su cliente fue guardado exitosamente.');

        } catch (\InvalidArgumentException $e) {
            //return to_route('clientes')->with('error', $e->getMessage());
            if($request->expectsJson()){
                return response()->json(['success'=>false,'message'=>$e->getMessage()],422);
            }
            return to_route('clientes')->with('error', $e->getMessage());

        } catch (\Exception $e) {
            //return to_route('clientes')->with('error', 'No se pudo agregar el cliente: ' . $e->getMessage());
            if($request->expectsJson()){
                return response()->json(['success'=>false,'message'=>'No se pudo agregar el cliente: '.$e->getMessage()],500);
            };
            return to_route('clientes')->with('error','No se pudo agregar al cliente: ' . $e->getMessage());
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
              // Buscar el cliente
             $cliente = Cliente::findOrFail($id);
            // Si se sube un nuevo archivo, validar y guardar
            if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
                if ($cliente->archivo && Storage::disk('public')->exists($cliente->archivo)) {
                    Storage::disk('public')->delete($cliente->archivo);
                }

                $ruta = $request->file('archivo')->store('clientes', 'public');
                if ($ruta) {
                    $datos['archivo'] = $ruta;
                }
            }

            $this->clienteService->actualizarCliente($id, $datos);
            return to_route('clientes')->with('success','Cliente actualizado correctamente');
        }catch(InvalidArgumentException $e){
            return to_route('clientes')->with('error',$e->getMessage());
        }catch(Exception $e){
            return to_route('clientes')->with('error','No se pudo editar: '.$e->getMessage());
        }
    }
    /*public function update(Request $request, string $id)
    {
        try {
            Log::info(' INICIO UPDATE - Cliente ID: ' . $id);

            // Buscar cliente
            $cliente = Cliente::findOrFail($id);

            // Datos base (sin archivo)
            $datos = [
                'nombres'   => $request->nombres,
                'apellidos' => $request->apellidos,
                'email'     => $request->email,
                'telefono'  => $request->telefono,
                'documento' => $request->documento,
                'estado'    => $request->estado,
            ];

            // --------------------------------------------
            // PROCESAR ARCHIVO (SOLO SI SE SUBE)
            // --------------------------------------------
            $tieneArchivo = $request->hasFile('archivo');

            if ($tieneArchivo) {
                $file = $request->file('archivo');

                Log::info(' Archivo recibido en edición:', [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize() . ' bytes',
                    'mime'   => $file->getMimeType(),
                    'valid'  => $file->isValid() ? 'SÍ' : 'NO',
                ]);

                // Si el archivo no es válido, lanzar excepción
                if (!$file->isValid()) {
                    throw new Exception('El archivo subido no es válido o está corrupto.');
                }

                // Eliminar archivo anterior si existe
                if ($cliente->archivo && Storage::disk('public')->exists($cliente->archivo)) {
                    Storage::disk('public')->delete($cliente->archivo);
                    Log::info(' Archivo anterior eliminado: ' . $cliente->archivo);
                }

                // Intentar guardar el nuevo archivo
                try {
                    $ruta = $file->store('clientes', 'public');
                    Log::info(' Ruta devuelta por store(): ' . ($ruta ?: 'null'));

                    if ($ruta) {
                        // Verificar que realmente exista en el disco
                        if (Storage::disk('public')->exists($ruta)) {
                            Log::info(' El archivo existe físicamente en el disco.');
                            $datos['archivo'] = $ruta;
                        } else {
                            Log::error(' La ruta fue devuelta pero el archivo NO existe en el disco.');
                            throw new Exception('El archivo no se guardó correctamente en el servidor.');
                        }
                    } else {
                        Log::error(' store() devolvió false o null.');
                        throw new Exception('Error al guardar el archivo en el servidor.');
                    }
                } catch (\Exception $e) {
                    Log::error(' Excepción al guardar archivo: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                Log::info(' No se recibió archivo en la edición (se mantiene el actual)');
            }
            Log::info(' Datos a enviar al servicio:', $datos);

            $this->clienteService->actualizarCliente($id, $datos);

            Log::info(' Cliente actualizado correctamente en la base de datos');

            return to_route('clientes')->with('success', 'Cliente actualizado correctamente');

        } catch (InvalidArgumentException $e) {
            Log::error(' Error de validación (InvalidArgumentException): ' . $e->getMessage());
            return to_route('clientes')->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error(' Error general en update: ' . $e->getMessage());
            return to_route('clientes')->with('error', 'No se pudo editar: ' . $e->getMessage());
        }
    }*/

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
        try {
            $cliente = Cliente::find($id);
            
            if (!$cliente) {
                return to_route('clientes')->with('error', 'El cliente no existe o ya fue eliminado.');
            }
            // Eliminar archivo asociado si existe
            if ($cliente->archivo && Storage::disk('public')->exists($cliente->archivo)) {
                Storage::disk('public')->delete($cliente->archivo);
            }

            $cliente->delete();
            
            return to_route('clientes')->with('success', 'Su cliente fue eliminado exitosamente.');
            
        } catch (Exception $e) {
            return to_route('clientes')->with('error', 'No se ha podido eliminar al cliente porque tiene una reserva o pago realizado: ' );
        }
    }
}
