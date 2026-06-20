<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreReserva;
use App\Models\Cliente;
use App\Models\Destino;
use App\Services\ReservaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PreReservaController extends Controller
{
    protected ReservaService $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    private function normalizeSearchPattern(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return '%'.str_replace(' ', '%', $value).'%';
    }

    private function findDestinoBySearch(string $value)
    {
        $pattern = $this->normalizeSearchPattern($value);

        return Destino::whereRaw('LOWER(pais) LIKE ?', [$pattern])
            ->orWhereRaw('LOWER(etiqueta) LIKE ?', [$pattern])
            ->first();
    }

    // Endpoint público para n8n
    public function storeFromWebhook(Request $request)
    {
        $payload = $request->only([
            'destino', 
            'cliente_nombre', 
            'cedula', 
            'telefono' ,
            'fecha_viaje'
        ]);

        $data = Validator::make($payload, [
            'destino' => 'required|string',
            'cliente_nombre' => 'required|string',
            'cedula' => 'required|string',
            'fecha_viaje' => 'required|date',
            'telefono' => 'nullable|string',
            'email' => 'nullable|string',
            'monto_depositado' => 'nullable|numeric'
        ])->validate();

        DB::beginTransaction();
        try {
            $destino = $this->findDestinoBySearch($data['destino']);
            if (!$destino) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Destino no encontrado. n8n debe enviar un destino existente.',
                ], 422);
            }

            $cliente = Cliente::where('documento', $data['cedula'])->first();
            if (!$cliente) {
                $parts = preg_split('/\s+/', trim($data['cliente_nombre']), 2);
                $nombres = $parts[0] ?? $data['cliente_nombre'];
                $apellidos = $parts[1] ?? '';

                $cliente = Cliente::create([
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'email' => $data['email'] ?? '',
                    'telefono' => $data['telefono'] ?? '',
                    'documento' => $data['cedula'],
                    'estado' => 'activo'
                ]);
            }

            $preReserva = PreReserva::create([
                'cliente_nombre' => $data['cliente_nombre'],
                'destino' => $data['destino'],
                'telefono' => $data['telefono'] ?? '',
                'cedula' => $data['cedula'],
                'fecha_viaje' => $data['fecha_viaje'],
                'fecha_reserva' => Carbon::now(),
                'origen' => 'n8n',
                'estado' => 'pendiente_contacto',
                'user_id' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'pre_reserva' => $preReserva,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkExistence(Request $request)
    {
        $data = $request->validate([
            'cedula' => 'required|string',
            'destino' => 'required|string',
        ]);

        $cliente = Cliente::where('documento', $data['cedula'])->first();
        $destino = $this->findDestinoBySearch($data['destino']);

        return response()->json([
            'cliente' => [
                'exists' => (bool) $cliente,
                'data' => $cliente ? [
                    'nombres' => $cliente->nombres,
                    'apellidos' => $cliente->apellidos,
                    'email' => $cliente->email,
                    'telefono' => $cliente->telefono,
                ] : null,
            ],
            'destino' => [
                'exists' => (bool) $destino,
                'data' => $destino ? [
                    'pais' => $destino->pais,
                    'precio' => $destino->precio,
                    'dias' => $destino->dias,
                    'capacidad' => $destino->capacidad,
                ] : null,
            ],
        ]);
    }

    // UI: listar pre-reservas
    public function index()
    {
        $preReservas = PreReserva::orderBy('created_at','desc')->get();
        return view('modules.pre_reservas.index', compact('preReservas'));
    }

    // UI: formulario crear
    public function edit($id)
    {
        $preReserva = PreReserva::findOrFail($id);
        return view('modules.pre_reservas.edit', compact('preReserva'));
    }

    public function update(Request $request, $id)
    {
        $preReserva = PreReserva::findOrFail($id);

        $data = $request->validate([
            'destino' => 'required|string',
            'cliente_nombre' => 'required|string',
            'cedula' => 'required|string',
            'fecha_viaje' => 'nullable|date',
            'telefono' => 'nullable|string',
            'estado' => 'required|in:pendiente_contacto,contactado,convertida,perdida',
        ]);

        $destino = Destino::where('pais', 'LIKE', '%' . $data['destino'] . '%')->first();
        if (!$destino) {
            return to_route('prereservas.index')->with('error', 'Destino no encontrado. No se puede actualizar la pre-reserva. error aqui');
        }

        $cliente = Cliente::where('documento', $data['cedula'])->first();
        if (!$cliente) {
            $parts = preg_split('/\s+/', trim($data['cliente_nombre']), 2);
            $nombres = $parts[0] ?? $data['cliente_nombre'];
            $apellidos = $parts[1] ?? '';

            Cliente::create([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'email' => '',
                'telefono' => $data['telefono'] ?? '',
                'documento' => $data['cedula'],
                'estado' => 'activo',
            ]);
        }

        $preReserva->fill([
            'cliente_nombre' => $data['cliente_nombre'],
            'destino' => $data['destino'],
            'telefono' => $data['telefono'] ?? '',
            'cedula' => $data['cedula'],
            'fecha_viaje' => $data['fecha_viaje'] ?? null,
            'estado' => $data['estado'],
        ]);

        $preReserva->save();

        return to_route('prereservas.index')->with('success', 'Pre-reserva actualizada correctamente');
    }

    // Convertir pre-reserva a reserva (acción rápida desde UI)
    public function convertToReserva($id)
    {
        $pre = PreReserva::findOrFail($id);
        // intentar encontrar cliente por cedula
        $cliente = Cliente::where('documento', $pre->cedula)->first();
        if (!$cliente) {
            return to_route('prereservas.index')->with('error','Cliente no encontrado para convertir');
        }
        
        $destino = Destino::where('pais', $pre->destino)->first();
        if (!$destino) {
            return to_route('prereservas.index')->with('error','Destino no encontrado para convertir');
        }

        $datosReserva = [
            'cliente_id' => $cliente->id,
            'destino_id' => $destino->id,
            'user_id' => Auth::id(),
            'fecha_reserva' => Carbon::now()->toDateTimeString(),
            'fecha_viaje' => $pre->fecha_viaje,
            'precio_total_viaje' => $destino->precio ?? 0,
            'monto_depositado' => 0,
        ];

        $codigo = $this->reservaService->guardarIndividual($datosReserva);
        $reservaId = DB::table('reservas')->where('codigo_reserva', $codigo)->value('id');
        if ($reservaId) {
            $pre->reserva_id = $reservaId;
            $pre->estado = 'convertida';
            $pre->user_id = Auth::id();
            $pre->save();
        }

        return to_route('prereservas.index')->with('success','Pre-reserva convertida: '.$codigo);
    }

    // Eliminar pre-reserva
    public function destroy($id)
    {
        $preReserva = PreReserva::findOrFail($id);
        $preReserva->delete();
        return to_route('prereservas.index')->with('success', 'Pre-reserva eliminada correctamente');
    }
}
