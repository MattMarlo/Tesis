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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
            'telefono',
            'fecha_viaje',
            'email',
        ]);

        $data = Validator::make($payload, [
            'destino' => 'required|string',
            'cliente_nombre' => 'required|string',
            'fecha_viaje' => 'required|date',
            'telefono' => 'required|string',
            'email' => 'required|email',
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

            $email = mb_strtolower(trim($data['email']));
            $cliente = Cliente::where('email', $email)->first();
            if (!$cliente) {
                $nameParts = preg_split('/\s+/', trim($data['cliente_nombre']), 2);
                $cliente = Cliente::create([
                    'nombres' => $nameParts[0] ?? $data['cliente_nombre'],
                    'apellidos' => $nameParts[1] ?? '',
                    'email' => $email,
                    'telefono' => $data['telefono'] ?? '',
                    'documento' => '',
                    'estado' => 'inactivo',
                ]);
            }

            $preReserva = PreReserva::create([
                'cliente_nombre' => $data['cliente_nombre'],
                'email' => $email,
                'destino' => $data['destino'],
                'telefono' => $data['telefono'] ?? '',
                'cedula' => '',
                'fecha_viaje' => $data['fecha_viaje'],
                'fecha_reserva' => Carbon::now(),
                'origen' => 'n8n',
                'estado' => 'pendiente_contacto',
                'user_id' => null,
            ]);
            $this->notificarN8n($preReserva);
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
    public function notificarN8n(PreReserva $preReserva){
        $webhookUrl = 'http://passiontravelviajes.de:5678/webhook/webhook/notificar-prereserva';
        
        /*if(empty($webhookUrl)){
            Log::warning("la url del webhook de n8n no esta configurada ");
            return;
        }*/
        try{
            $payload=[
                'event'=>'prereserva.creada',
                'data'=>[
                    'id'=>$preReserva->id,
                    'cliente_nombre'=>$preReserva->cliente_nombre,
                    'email'=>$preReserva->email,
                    'telefono'=>$preReserva->telefono,
                    'destino'=>$preReserva->destino,
                    'fecha_viaje'=>$preReserva->fecha_viaje?->format('Y-m-d'),
                    'origen'=>$preReserva->origen,
                    'estado'=>$preReserva->estado,
                    //'created_at'=>$preReserva->created_at?->toIso8601String(),
                    'created_at' => $preReserva->created_at?->format('d/m/Y H:i'),
                ],
            ];
            Http::timeout(10)->post($webhookUrl,$payload);
            Log::info('Enviando a n8n', ['url' => $webhookUrl, 'payload' => $payload]);

        }catch(\Exception $e){
            Log::error('Error al notificar a n8n '.$e->getMessage());
        }

    }
    public function checkExistence(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email',
            'cedula' => 'nullable|string',
            'destino' => 'required|string',
        ]);

        $cliente = null;
        if (!empty($data['email'])) {
            $cliente = Cliente::where('email', mb_strtolower(trim($data['email'])))->first();
        }
        if (!$cliente && !empty($data['cedula'])) {
            $cliente = Cliente::where('documento', $data['cedula'])->first();
        }

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
            'email' => 'required|email',
            'cedula' => 'nullable|string',
            'fecha_viaje' => 'nullable|date',
            'telefono' => 'nullable|string',
            'estado' => 'required|in:pendiente_contacto,contactado,convertida,perdida',
        ]);

        $destino = Destino::where('pais', 'LIKE', '%' . $data['destino'] . '%')->first();
        if (!$destino) {
            return to_route('prereservas.index')->with('error', 'Destino no encontrado. No se puede actualizar la pre-reserva. error aqui');
        }

        $email = mb_strtolower(trim($data['email']));
        $cliente = Cliente::where('email', $email)->first();
        if (!$cliente) {
            $parts = preg_split('/\s+/', trim($data['cliente_nombre']), 2);
            $cliente = Cliente::create([
                'nombres' => $parts[0] ?? $data['cliente_nombre'],
                'apellidos' => $parts[1] ?? '',
                'email' => $email,
                'telefono' => $data['telefono'] ?? '',
                'documento' => $data['cedula'] ?? '',
                'estado' => empty($data['cedula']) ? 'inactivo' : 'activo',
            ]);
        } elseif (empty($cliente->documento) && !empty($data['cedula'])) {
            $cliente->documento = $data['cedula'];
            $cliente->estado = 'activo';
            $cliente->save();
        }

        $preReserva->fill([
            'cliente_nombre' => $data['cliente_nombre'],
            'email' => $email,
            'destino' => $data['destino'],
            'telefono' => $data['telefono'] ?? '',
            'cedula' => $data['cedula'] ?? '',
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
        // intentar encontrar cliente por email, luego por cédula y teléfono
        $cliente = null;
        if (!empty($pre->email)) {
            $cliente = Cliente::where('email', mb_strtolower(trim($pre->email)))->first();
        }
        if (!$cliente && !empty($pre->cedula)) {
            $cliente = Cliente::where('documento', $pre->cedula)->first();
        }
        if (!$cliente && !empty($pre->telefono)) {
            $cliente = Cliente::where('telefono', $pre->telefono)->first();
        }

        // si no existe cliente, crearlo en estado inactivo (faltan datos como cédula)
        if (!$cliente) {
            $parts = preg_split('/\s+/', trim($pre->cliente_nombre), 2);
            $cliente = Cliente::create([
                'nombres' => $parts[0] ?? $pre->cliente_nombre,
                'apellidos' => $parts[1] ?? '',
                'email' => $pre->email ?? '',
                'telefono' => $pre->telefono ?? '',
                'documento' => $pre->cedula ?? '',
                'estado' => empty($pre->cedula) ? 'inactivo' : 'activo',
            ]);
        }
        
        $destino = $this->findDestinoBySearch($pre->destino);
        if (!$destino) {
            return to_route('prereservas.index')->with('error','Destino no encontrado para convertir');
        }

        $datosReserva = [
            'cliente_id' => $cliente->id,
            'destino_id' => $destino->id,
            'user_id' => Auth::id(),
            'fecha_reserva' => Carbon::now()->toDateTimeString(),
            'fecha_viaje' => $pre->fecha_viaje,
            'precio_total_viaje' => 0,
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
