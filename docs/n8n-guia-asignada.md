# Notificación de guía asignado con n8n y WhatsApp

El sistema envía un `POST` a n8n cuando, desde **Preparación de
viajes**, ocurre una de estas acciones:

- se registra un guía nuevo para la operación;
- se registra un guía nuevo y se lo vincula con una actividad;
- se vincula un guía existente con una actividad de tipo guía.

Editar o eliminar un guía no vuelve a disparar la notificación. Si
n8n no está disponible, la asignación se conserva y el error queda
registrado en el log de Laravel.

## Variable de entorno

En el servidor de Laravel se puede conservar la URL predeterminada o
declarar una URL diferente en `.env`:

```dotenv
N8N_GUIDE_ASSIGNMENT_NOTIFICATION_URL=https://n8n.passiontravelviajes.de/webhook/guia-viaje-asignado
```

Después de cambiarla, refresca la configuración de Laravel:

```bash
php artisan config:clear
php artisan config:cache
```

Para desactivar temporalmente solo esta notificación, deja la variable
vacía. Esto no modifica los webhooks de pagos, boletos, habitaciones,
prerreservas, Telegram o WhatsApp.

## Crear el workflow en n8n

1. Crea un workflow nuevo sin editar los workflows existentes.
2. Agrega un nodo **Webhook** con método `POST` y path
   `guia-viaje-asignado`.
3. Configura el Webhook para responder inmediatamente con código 200.
   Así Laravel no necesita esperar a que termine el envío a WhatsApp.
4. Agrega un nodo **IF** y permite continuar únicamente cuando la
   expresión `{{$json.body.event}}` sea igual a
   `guia.viaje.asignado`.
5. Conecta el mismo nodo o credencial de WhatsApp que ya utiliza la
   instalación. El teléfono del cliente está en
   `{{$json.body.data.telefono}}`.
6. Mapea los datos del mensaje o de la plantilla de WhatsApp con las
   expresiones indicadas abajo.
7. Prueba primero con la **Test URL** de n8n. Para esa prueba cambia
   temporalmente la variable de entorno a la URL `/webhook-test/...`.
8. Cuando funcione, activa el workflow y vuelve a configurar la
   **Production URL**, que usa `/webhook/guia-viaje-asignado`.

Si WhatsApp exige una plantilla para iniciar la conversación, crea y
aprueba una plantilla con variables equivalentes a: cliente, código de
reserva, guía, teléfono del guía, fecha y punto de encuentro. Si existe
una conversación abierta que permita mensajes libres, puede usarse un
mensaje de texto normal según las reglas de la cuenta.

## Campos disponibles

El cuerpo que recibe n8n tiene esta estructura:

```json
{
  "event": "guia.viaje.asignado",
  "data": {
    "guia_id": 15,
    "reserva_id": 42,
    "codigo_reserva": "RES-2026-0042",
    "cliente": "Nombre del cliente",
    "email": "cliente@example.com",
    "telefono": "0999999999",
    "destino": "Lima",
    "nombre_paquete": "Lima cultural",
    "fecha_viaje": "2026-12-01",
    "nombre_guia": "Nombre del guía",
    "empresa_guia": "Operador local",
    "telefono_guia": "+51999999999",
    "correo_guia": "guia@example.com",
    "idiomas_guia": "Español e inglés",
    "ciudad_servicio": "Lima",
    "fecha_inicio": "2026-12-01",
    "fecha_fin": "2026-12-05",
    "punto_encuentro": "Plaza Mayor",
    "fecha_hora_encuentro": "2026-12-01T08:00:00-05:00",
    "servicios_incluidos": "Recorrido cultural",
    "estado_guia": "confirmado",
    "tarea_id": 80,
    "actividad": "Recorrido histórico por Lima",
    "descripcion_actividad": "Caminata por las dunas y tiempo para fotografías.",
    "dia_actividad": 1,
    "fecha_actividad": "2026-12-01",
    "hora_inicio_actividad": "08:30",
    "hora_fin_actividad": "09:30",
    "fecha_hora_inicio_actividad": "2026-12-01T08:30:00-05:00",
    "fecha_hora_fin_actividad": "2026-12-01T09:30:00-05:00",
    "ubicacion_actividad": "Centro histórico"
  }
}
```

Cuando el guía se registra para toda la operación sin una actividad
específica, los campos de tarea llegan como `null`.

Expresiones útiles para el nodo de WhatsApp:

```text
{{$json.body.data.cliente}}
{{$json.body.data.codigo_reserva}}
{{$json.body.data.nombre_guia}}
{{$json.body.data.telefono_guia}}
{{$json.body.data.fecha_hora_encuentro}}
{{$json.body.data.punto_encuentro}}
{{$json.body.data.actividad}}
{{$json.body.data.descripcion_actividad}}
{{$json.body.data.fecha_actividad}}
{{$json.body.data.fecha_hora_inicio_actividad}}
```

## Configurar el nodo Edit Fields

Después del Webhook, agrega un nodo **Edit Fields (Set)** y elige
**Manual Mapping**. Crea estos campos; la columna de la derecha debe
estar en modo **Expression**:

| Campo de salida | Expresión |
| --- | --- |
| `numero_reserva` | `{{$json.body.data.codigo_reserva}}` |
| `nombre_cliente` | `{{$json.body.data.cliente}}` |
| `correo_cliente` | `{{$json.body.data.email}}` |
| `telefono_cliente` | `{{$json.body.data.telefono}}` |
| `nombre_paquete` | `{{$json.body.data.nombre_paquete}}` |
| `destino` | `{{$json.body.data.destino}}` |
| `fecha_viaje` | `{{$json.body.data.fecha_viaje}}` |
| `nombre_guia` | `{{$json.body.data.nombre_guia}}` |
| `empresa_guia` | `{{$json.body.data.empresa_guia}}` |
| `telefono_guia` | `{{$json.body.data.telefono_guia}}` |
| `correo_guia` | `{{$json.body.data.correo_guia}}` |
| `idiomas_guia` | `{{$json.body.data.idiomas_guia}}` |
| `estado_guia` | `{{$json.body.data.estado_guia}}` |
| `ciudad_servicio` | `{{$json.body.data.ciudad_servicio}}` |
| `punto_encuentro` | `{{$json.body.data.punto_encuentro}}` |
| `fecha_hora_encuentro` | `{{$json.body.data.fecha_hora_encuentro}}` |
| `actividad` | `{{$json.body.data.actividad}}` |
| `descripcion_actividad` | `{{$json.body.data.descripcion_actividad}}` |
| `fecha_actividad` | `{{$json.body.data.fecha_actividad}}` |
| `hora_inicio_actividad` | `{{$json.body.data.hora_inicio_actividad}}` |
| `hora_fin_actividad` | `{{$json.body.data.hora_fin_actividad}}` |
| `ubicacion_actividad` | `{{$json.body.data.ubicacion_actividad}}` |

Mantén activada la opción **Keep Only Set Fields** si quieres que el
nodo siguiente reciba únicamente estos nombres simplificados. Después
de Edit Fields, las expresiones del nodo de WhatsApp serán, por ejemplo,
`{{$json.telefono_cliente}}`, `{{$json.nombre_guia}}` y
`{{$json.descripcion_actividad}}`.

## Mensaje sugerido

```text
Hola {{cliente}}. Para tu reserva {{codigo_reserva}} se asignó al guía
{{nombre_guia}}. Puedes contactarlo al {{telefono_guia}}. El encuentro
será el {{fecha_hora_encuentro}} en {{punto_encuentro}}.
```

Antes del nodo de WhatsApp, valida que `telefono` tenga el código de
país y el formato exigido por el proveedor. También conviene agregar en
n8n una rama de error o reintento para que una falla de WhatsApp quede
visible sin pedirle a Laravel que repita la asignación.
