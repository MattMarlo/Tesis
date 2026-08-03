# Integración del asistente de Telegram con n8n

Laravel es la fuente oficial de destinos, cupos, tarifas y prerreservas. El agente de IA no debe calcular precios ni escribir directamente en la base de datos.

## Configuración

Definir en `.env`:

```dotenv
TELEGRAM_BOT_USERNAME=ReservasPassionTravelBot
N8N_WEBHOOK_SECRET=otro_secreto_largo_y_aleatorio
N8N_NOTIFICATION_URL=https://n8n.example/webhook/notificar-prereserva
```

Ejecutar `php artisan migrate`. Las rutas de Telegram no requieren encabezado
de autenticación; conservan limitación de solicitudes y validación de datos.
En cada HTTP Request Tool puede enviarse:

```text
Accept: application/json
Content-Type: application/json
```

## Tools disponibles

- `GET /api/telegram/destinos`: paquetes publicados, futuros y con cupo.
- `GET /api/telegram/destinos/{id}`: ficha completa oficial.
- `POST /api/telegram/cupos`: recibe `destino_id` y `cantidad_personas`.
- `POST /api/telegram/cotizar`: recibe `destino_id` y `viajeros[].fecha_nacimiento`.
- `POST /api/telegram/prerreservas`: crea la solicitud sólo tras la confirmación del cliente.

El landing genera enlaces `https://t.me/BOT?start=destino_ID`. El nodo inicial debe convertir mensajes y callbacks en `chat_id`, `text`, `callback_data` y una `referencia_externa` estable. La llave de memoria debe ser el `chat_id`.

## Secuencia del agente

1. Ante `/start destino_ID`, llamar a la ficha del destino.
2. Mostrar exclusivamente datos devueltos por Laravel.
3. Ofrecer botones `reservar:ID` y `otros_destinos`.
4. Consultar cupos antes de recopilar viajeros.
5. Pedir tipo individual o grupal; para grupos, familiar o independiente.
6. Recopilar los datos de cada viajero y seleccionar un líder adulto. Los grupos familiares también requieren responsable de pago adulto.
7. Cotizar mediante la tool; no hacer operaciones aritméticas en el modelo.
8. Mostrar el resumen y solicitar el callback exacto `confirmar_prerreserva`.
9. Sólo entonces llamar a `POST /prerreservas` con `acepta_condiciones: true`.
10. Informar éxito únicamente si la respuesta contiene `success: true`.

Botones recomendados: `individual`, `grupal`, `familiar`, `independiente`, `confirmar_prerreserva`, `corregir_datos` y `cancelar`.

## Contrato de creación

Para una solicitud individual se admite `cliente`; para una grupal se usa `integrantes`. Cada viajero incluye nombres, apellidos, tipo y número de documento, fecha de nacimiento y nacionalidad. El viajero principal también requiere correo, celular y contacto de emergencia. Se aceptan `lider_indice` y `responsable_pago_indice` basados en cero.

Laravel valida documentos distintos, cédula ecuatoriana, vigencia documental, edades en la fecha del viaje, acompañamiento de menores, cupos, duplicados, líder y responsable adultos. `referencia_externa` hace idempotente la operación.

La respuesta crea una **prerreserva pendiente de contacto**, nunca una reserva definitiva. La conversión permanece bajo revisión administrativa.
