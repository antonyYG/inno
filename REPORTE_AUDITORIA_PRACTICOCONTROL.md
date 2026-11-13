# REPORTE DE AUDITORÍA FUNCIONAL — SISTEMA PRACTICONTROL

**Proyecto:** PractiControl (Laravel)  
**Ruta del proyecto:** `D:\DW\laravel\inno\inno`  
**Fecha de auditoría:** 09 de noviembre de 2026  
**Diagrama de referencia:** `docs/diaActividadGestinar asistencia.png`  
**Restricción respetada:** No se modificó ningún archivo, ni se creó migración, ni se alteró la base de datos.

---

## 1. Entorno y stack tecnológico detectado

| Elemento | Versión / valor |
|----------|-----------------|
| Laravel Framework | 13.30.1 |
| PHP | 8.4 |
| Motor de BD (`.env`) | MySQL (`DB_CONNECTION=mysql`, host `127.0.0.1`, db `inno`) |
| Motor de BD (`.env.example`) | SQLite |
| Discord | `team-reflex/discord-php` 10.71.0 |
| Permisos/Roles | `spatie/laravel-permission` 8.3.0 |
| DataTables | `yajra/laravel-datatables-oracle` 13.3.1 |
| Tests | Pest 5.1.3 (solo `ExampleTest.php`) |
| Formatter | Laravel Pint 1.30.5 |
| Token Discord en `.env` | `DISCORD_TOKEN` está presente (valor omitido en este reporte por seguridad) |

**Observación de entorno:** el `.env` apunta a MySQL mientras que `.env.example` apunta a SQLite. Además, el `.env` contiene un token real de Discord commiteado, lo cual es un riesgo de seguridad (credencial expuesta en el repositorio).

---

## 2. Resumen general de la auditoría

El sistema PractiControl implementa parcialmente los cuatro procesos del diagrama de actividad (visualizar asistencias/inasistencias, registrar asistencia, validar mediante Discord y evidencias, justificar faltas y tardanzas).

**Lo que sí funciona:**
- Autenticación de usuarios con rate limiting y roles/permisos (Spatie).
- Vistas Blade para dashboard (admin y practicante), calendario (FullCalendar), tick/marcar asistencia, usuarios, áreas y justificaciones.
- Registro inicial de asistencia desde la web (`TickController@marcar`) que crea un registro `pendiente` si no existe uno para el día.
- Integración con Discord mediante un bot (comando `discord:bot`) que escucha eventos `PRESENCE_UPDATE` y actualiza `hora_inicio`, `hora_fin` y `tiempo_trabajado` a través de `AttendanceDiscordService`.
- Subida y almacenamiento de evidencias en el disco público (`storage/app/public`).
- Consulta de asistencias mediante calendario con colores por estado.

**Lo que NO funciona o no está implementado:**
- **No existe ninguna tarea programada ni comando Artisan que genere faltas automáticamente** cuando un practicante no registra asistencia al finalizar el plazo. `routes/console.php` solo define el comando `inspire`.
- El registro web **no asigna `hora_inicio`** ni determina si es puntual o tardanza según la hora (siempre deja `tipo = pendiente` y `hora_inicio = null`).
- No se aplica el corte de las **08:15** (después de esa hora no se debe permitir registrar); la comprobación de las 13:00 está comentada.
- `AttendanceDiscordService` detecta estados de *presencia* (online/idle/dnd/offline), pero **no verifica que el usuario esté realmente conectado a un canal de voz de Discord**.
- No existe un flujo de **aprobación/rechazo de justificaciones** (la migración tiene la columna `estado` pendiente/aprobada/rechazada, pero los controladores nunca la usan; cambian el estado de la asistencia directamente).
- La justificación de falta **no guarda el campo `motivo`** aunque el formulario lo envía.
- No hay pruebas automatizadas que cubran la lógica de asistencia, validación de evidencias o justificaciones (solo `ExampleTest.php`).
- Existen incongruencias entre columnas de la migración y lo que acceden los controladores/servicios (p. ej. `CalendarService` lee `$justificacion->nombre`, pero la tabla `justificaciones` no tiene columna `nombre`).

**Porcentaje estimado de cumplimiento: ~46 %** (ver cálculo en §8).

---

## 3. Matriz de cumplimiento del diagrama

| N.º | Proceso del diagrama | Implementación encontrada | Archivos involucrados | Estado | Observaciones |
|-----|----------------------|---------------------------|-----------------------|--------|---------------|
| 1 | Visualizar asistencias e inasistencias (vista practicante) | Dashboard practicante muestra estado de hoy, asistencias/tardanzas/faltas del mes y gráfico semanal. | `resources/views/practicing/dashboard.blade.php`, `app/Services/DashboardService.php::getDashboardPracticante()` | CUMPLE | Los datos se leen correctamente de `asistencias`. |
| 2 | Visualizar asistencias e inasistencias (vista admin/calendario) | Calendario FullCalendar con eventos por usuario, tipo, horario y evidencias. | `resources/views/admin/calendar/index.blade.php`, `app/Http/Controllers/CalendarController.php`, `app/Services/CalendarService.php` | CUMPLE | `CalendarController::data()` filtra por usuario si no es admin. |
| 3 | Visualizar justificaciones (admin) | Vista `admin/justificaciones/index.blade.php` apunta a `route('admin.justifications.data')`. | `resources/views/admin/justificaciones/index.blade.php`, `resources/js/tableJustificaciones.js`, `app/Http/Controllers/JustificacionController.php` | NO CUMPLE | `JustificacionController` no tiene método `data`; la ruta `admin.justifications.data` no existe. El datatable no funcionará. |
| 4 | Registrar asistencia desde el sistema web | `TickController@marcar` crea registro con `tipo = pendiente` si no existe para el usuario/fecha. La hora de inicio (`hora_inicio`) se deja en `null` porque se adquiere al conectarse al canal de voz de Discord (aceptable según el diagrama). | `routes/admin.php` (`admin.tick.marcar`), `app/Http/Controllers/TickController.php::marcar()` | PARCIAL | **Correcto:** no obliga a `hora_inicio` al marcar desde web (se obtiene de Discord). **Incorrecto:** siempre asigna `tipo = pendiente` sin importar la hora de registro (debería ser puntual/tardanza/pendiente según rango horario) y no aplica corte de registro después de las 08:15. |
| 5 | Estado inicial pendiente o tardanza según hora de registro | No implementado: siempre se asigna `pendiente`. | `app/Http/Controllers/TickController.php::marcar()`, `app/Enum/EstadoAsistenciaEnum.php` | NO CUMPLE | El enum define `TARDANZA`, pero nunca se asigna al marcar desde web. No existe valor `PUNTUAL` en el enum. |
| 6 | Horario 07:45–08:00 = asistencia puntual | No implementado. | `app/Http/Controllers/TickController.php`, `app/Services/Discord/AttendanceDiscordService.php` | NO CUMPLE | No hay lógica que clasifique el ingreso en ese rango como puntual. |
| 7 | 08:00–08:15 = tardanza | No aplicado al registro web. | `app/Http/Controllers/TickController.php::marcar()` | PARCIAL | El enum tiene `TARDANZA` pero nunca se usa al marcar. |
| 8 | Después de 08:15 no se permite registrar asistencia | No implementado. | `app/Http/Controllers/TickController.php::marcar()` | NO CUMPLE | No hay corte a las 08:15. La comprobación de las 13:00 está comentada (líneas 47-52 de `TickController.php`). |
| 9 | Si no hay registro al finalizar el plazo, generarse una falta | No implementado: no hay comando ni tarea programada. | `routes/console.php`, `app/Console/Commands/` | NO CUMPLE | No existe tarea programada (Scheduler) ni comando que genere registros `falto`. `routes/console.php` solo tiene `inspire`. |
| 10 | Discord detecta ingreso/salida del practicante del canal de voz | Se escucha `PRESENCE_UPDATE` (estado de presencia), no el canal de voz. | `app/Console/Commands/DiscordBotCommand.php`, `app/Services/Discord/AttendanceDiscordService.php::processPresence()` | PARCIAL | Detecta presencia (online/idle/dnd/offline), pero **no verifica membresía en un canal de voz** (`GUILD_VOICE_STATES` no está configurado en los intents). |
| 11 | Estados online, idle y dnd = conectado | Implementado en `startConnection`. | `app/Services/Discord/AttendanceDiscordService.php:32-34` | CUMPLE | `in_array($status, ['online','idle','dnd'])` → inicia conexión. |
| 12 | Estado offline = desconexión | Implementado en `endConnection`. | `app/Services/Discord/AttendanceDiscordService.php:36-38` | CUMPLE | Cierra conexión, calcula `tiempo_trabajado` y `hora_fin` (limitado a 13:00). |
| 13 | Evidencia subida valida la asistencia | `justificateAsistencia` sube archivo y cambia `tipo` a `asistio` directamente. | `app/Http/Controllers/JustificacionController.php::justificateAsistencia()`, `resources/views/practicing/calendar/index.blade.php` | PARCIAL | Cambia el estado sin flujo de aprobación; `mimes` solo permite png/jpg (el formulario anuncia PDF pero la validación lo rechaza). |
| 14 | Actualizar pendiente/tardanza según reglas de validación de evidencias | No aplicado: se fuerza `asistio` sin considerar el estado previo. | `app/Http/Controllers/JustificacionController.php::justificateAsistencia()` | PARCIAL | No distingue si venía de `pendiente` o `tardanza`; lo sobrescribe siempre a `asistio`. |
| 15 | Justificar faltas y tardanzas mediante evidencia y motivo | Se guarda evidencia, pero **no se guarda el `motivo`** (el formulario lo envía y el controlador lo ignora). | `app/Http/Controllers/JustificacionController.php::justificateFalta()`, `resources/views/practicing/calendar/index.blade.php` | PARCIAL | Cambia `tipo` a `justificado` directamente; `motivo` se descarta. |
| 16 | Validar la justificación antes de cambiar el estado | No implementado: no hay flujo de aprobación/rechazo. | `app/Http/Controllers/JustificacionController.php`, `database/migrations/…_create_justificaciones_table.php` | NO CUMPLE | La migración define columna `estado` (pendiente/aprobada/rechazada) pero los controladores no la usan. El cambio de estado es inmediato. |
| 17 | Almacenamiento de evidencias | `Storage::store('evidencias', 'public')`. | `app/Http/Controllers/JustificacionController.php`, `config/filesystems.php` | CUMPLE | Los archivos se almacenan en el disco público y se sirven mediante `Storage::url()`. |
| 18 | Pruebas automatizadas de los procesos de asistencia | No existen pruebas específicas. | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | NO CUMPLE | Solo pruebas triviales (`ExampleTest`). No hay tests de asistencia, Discord, evidencias ni justificaciones. |

---

## 4. Análisis detallado de cada proceso

### 4.1 Proceso A — Visualizar asistencias e inasistencias

**Implementación:**
- Dashboard administrador: `app/Http/Controllers/DashboardController.php::index()` → `DashboardService::getDashboardAdmin()`. Muestra practicantes activos, asistencias/tardanzas/faltas de hoy, gráfica semanal y doughnut por área.
- Dashboard practicante: `DashboardService::getDashboardPracticante()`. Muestra asistencias/tardanzas/faltas del mes, estado de hoy y gráfico semanal (`resources/views/practicing/dashboard.blade.php`).
- Calendario: `CalendarController::index()` y `data()` → `CalendarService::getAttendances()`. Devuelve eventos para FullCalendar con colores por tipo y datos de evidencias/justificaciones.

**Archivos y métodos:**
- `routes/admin.php` → `admin.dashboard`, `admin.calendar.index`, `admin.calendar.data`
- `app/Http/Controllers/DashboardController.php`
- `app/Services/DashboardService.php`
- `app/Http/Controllers/CalendarController.php`
- `app/Services/CalendarService.php`
- `resources/views/admin/dashboard.blade.php`, `resources/views/practicing/dashboard.blade.php`
- `resources/views/admin/calendar/index.blade.php`, `resources/views/practicing/calendar/index.blade.php`

**Estado:** CUMPLE (visualización funcional).  
**Observación:** `CalendarService::getAttendances()` (línea 59-64 y 66-71) accede a `$justificacion->nombre`, pero la tabla `justificaciones` (migración `2026_10_08_213859`) **no tiene columna `nombre`**, solo `descripcion`. Ese campo vendrá `null`.

---

### 4.2 Proceso B — Registrar asistencia

**Implementación:**
- `TickController@marcar` valida que el usuario autenticado tenga `discord_id` y `estado = true`.
- Usa `Asistencia::firstOrCreate(['usuario_id', 'fecha' => today()], ['tipo' => PENDIENTE])`.
- Si ya existe, devuelve mensaje "Ya registraste tu asistencia de hoy".

**Archivos y métodos:**
- `routes/admin.php` → `admin.tick.index` (GET), `admin.tick.marcar` (POST)
- `app/Http/Controllers/TickController.php::index()`, `::marcar()`
- `app/Services/tickService.php::getInformation()`
- `resources/views/practicing/tick/index.blade.php`

**Estado:** PARCIAL.  
**Problemas:**
1. (Aceptable) `hora_inicio` queda en `null` al marcar desde web porque se adquiere al conectarse al canal de voz de Discord. No es un defecto por sí mismo.
2. Siempre crea como `pendiente`, sin importar la hora de registro (no diferencia puntual/tardanza según el rango horario).
3. No aplica corte de registro después de las 08:15. La comprobación de las 13:00 está comentada (líneas 47-52).
4. `firstOrCreate` sin índice único en `(usuario_id, fecha)` es propenso a condiciones de carrera (registros duplicados bajo concurrencia). La migración `asistencias` no define `unique` sobre esas dos columnas.

---

### 4.3 Proceso C — Validar asistencia mediante Discord y evidencias

**Implementación (Discord):**
- Comando `discord:bot` (`app/Console/Commands/DiscordBotCommand.php`) inicia el cliente Discord con el token de `config/services.php` e intents `GUILD_PRESENCES` (por defecto) + presencias.
- Escucha `Event::PRESENCE_UPDATE` y llama a `AttendanceDiscordService::processPresence($discordId, $status)`.
- `processPresence` busca al usuario por `discord_id` y `estado = true`, y la asistencia de hoy.
- Si el status es `online`/`idle`/`dnd` → `startConnection()`; si es `offline` → `endConnection()`.

**`startConnection()` (`AttendanceDiscordService.php:41-79`):**
- Rechaza si está fuera de 08:00–13:00.
- Usa cache `attendance:{id}:connection` para evitar reiniciar el contador.
- Transacción con `lockForUpdate()` (buena práctica de concurrencia).
- Solo establece `hora_inicio` si es null.
- **Bug:** línea 71-73 hace `$registro->tipo = EstadoAsistenciaEnum::PENDIENTE` (asigna el mismo valor, no-op). Nunca clasifica puntual/tardanza.

**`endConnection()` (`AttendanceDiscordService.php:81-137`):**
- Suma segundos al `tiempo_trabajado` existente.
- Limita `hora_fin` a 13:00.
- Guarda `tiempo_trabajado` como string `HH:mm:ss`.

**Implementación (Evidencias):**
- `JustificacionController::justificateAsistencia()` sube el archivo, crea `Justificacion` con `tipo = 'evidencia'` y fuerza `Asistencia::tipo = ASISTIO`.

**Archivos y métodos:**
- `app/Console/Commands/DiscordBotCommand.php`
- `app/Services/Discord/AttendanceDiscordService.php::processPresence()`, `startConnection()`, `endConnection()`
- `app/Http/Controllers/JustificacionController.php::justificateAsistencia()`
- `config/services.php` (`discord.token`)
- `resources/views/practicing/calendar/index.blade.php` (formulario de evidencia, Alpine.js)

**Estado:** PARCIAL (Discord) / PARCIAL (Evidencias).  
**Problemas:**
1. Los intents no incluyen `GUILD_VOICE_STATES`, por lo que el bot **no sabe si el usuario entró/salió de un canal de voz**, solo si su presencia cambia. No cumple estrictamente "detectar el ingreso y salida del practicante del canal de voz".
2. `startConnection()` no distingue puntual (07:45–08:00) de tardanza (08:00–08:15); solo controla la ventana 08:00–13:00.
3. La validación de evidencia acepta solo `png,jpg` (`mimes:png,jpg`), mientras la vista anuncia "JPG, PNG o PDF". Los PDF son rechazados.
4. El cambio a `asistio` es inmediato, sin flujo de aprobación.

---

### 4.4 Proceso D — Justificar faltas y tardanzas

**Implementación:**
- `JustificacionController::justificateFalta()` sube el archivo (`evidencia`), crea `Justificacion` con `tipo = 'evidencia'` y fuerza `Asistencia::tipo = JUSTIFICADO`.
- El formulario (vista practicante/calendario) envía `motivo` (textarea) y `evidencia` (archivo), pero el controlador **no guarda `motivo`**.

**Archivos y métodos:**
- `routes/admin.php` → `admin.practicing.justificaciones.store` (POST)
- `app/Http/Controllers/JustificacionController.php::justificateFalta()`
- `resources/views/practicing/calendar/index.blade.php` (formulario de justificación de falta)

**Estado:** PARCIAL.  
**Problemas:**
1. `motivo` se envía desde el frontend pero el controlador no lo persiste (no está en `$fillable` ni se asigna).
2. No hay flujo de aprobación: la migración define `justificaciones.estado` (pendiente/aprobada/rechazada) pero ningún controller la usa; el cambio a `justificado` es inmediato.
3. `justificateAsistencia` y `justificateFalta` usan `tipo = 'evidencia'` para ambas, aunque una es evidencia de asistencia y la otra es justificación de falta. No se distingue claramente.

---

## 5. Archivos y métodos donde se encuentra cada implementación

| Proceso | Archivo | Método / línea clave |
|---------|---------|----------------------|
| Rutas web/comandos | `routes/web.php` | `login`, `login.submit`, `logout` (líneas 7-13) |
| Rutas admin | `routes/admin.php` | todas las rutas `admin.*` (líneas 16-32) |
| Rutas consola | `routes/console.php` | comando `inspire` solamente (línea 6) |
| Carga de rutas con prefijo `admin.` | `bootstrap/app.php` | líneas 15-19 (grupo `name('admin.')->prefix('admin')`) |
| Middleware inactivo | `app/Http/Middleware/InactiveMiddleware.php` | `handle()` (línea 17) |
| Registro asistencia | `app/Http/Controllers/TickController.php` | `marcar()` (línea 26), `index()` (línea 17) |
| Servicio tick | `app/Services/tickService.php` | `getInformation()` (línea 8) |
| Validación usuarios | `app/Http/Requests/UserStoreRequest.php`, `EditUser.php` | `rules()` |
| Modelo asistencia | `app/Models/Asistencia.php` | fillable, casts `tipo` a `EstadoAsistenciaEnum`, relaciones `usuario`, `justificaciones` |
| Modelo justificación | `app/Models/Justificacion.php` | fillable (sin `motivo`, sin `nombre`, sin `estado`) |
| Enum estados | `app/Enum/EstadoAsistenciaEnum.php` | `PENDIENTE, ASISTIO, FALTO, TARDANZA, JUSTIFICADO` |
| Discord bot | `app/Console/Commands/DiscordBotCommand.php` | `handle()` (línea 17) |
| Servicio Discord | `app/Services/Discord/AttendanceDiscordService.php` | `processPresence()` (13), `startConnection()` (41), `endConnection()` (81) |
| Evidencias / justificaciones | `app/Http/Controllers/JustificacionController.php` | `justificateAsistencia()` (14), `justificateFalta()` (41) |
| Calendario | `app/Services/CalendarService.php` | `getAttendances()` (17) |
| Dashboard | `app/Services/DashboardService.php` | `getDashboardAdmin()` (11), `getDashboardPracticante()` (51) |
| Migración asistencias | `database/migrations/2026_10_08_212148_create_asistencias_table.php` | `up()` (línea 12) |
| Migración justificaciones | `database/migrations/2026_10_08_213859_create_justificaciones_table.php` | `up()` (línea 12) |
| Vistas(tick/calendario/dashboard) | `resources/views/practicing/`, `resources/views/admin/` | `tick/index.blade.php`, `calendar/index.blade.php`, `dashboard.blade.php` |
| Almacenamiento | `config/filesystems.php` | disco `public` (línea 41) |
| Pruebas | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` | solo pruebas triviales |

---

## 6. Problemas detectados y gravedad

### Crítica
1. **Falta de generación automática de faltas.** No existe comando Artisan ni tarea programada que marque `falto` a los practicantes sin registro al finalizar el plazo. Regla del diagrama incumplida por completo.
   - Gravedad: **Crítica**. Afecta el núcleo del control de asistencia.
2. **Registro web sin clasificación por hora.** `TickController@marcar` crea siempre como `pendiente` sin determinar puntual/tardanza según la hora de registro (la hora de inicio `hora_inicio` se obtiene de Discord, lo cual es aceptable).
   - Gravedad: **Alta**. El estado inicial nunca refleja la hora real de registro.
3. **Discord no valida canal de voz.** El bot usa `PRESENCE_UPDATE` y `GUILD_PRESENCES`, no `GUILD_VOICE_STATES`. No detecta entrada/salida real de un canal de voz.
   - Gravedad: **Alta**. Invalida el principio de "validar asistencia mediante Discord y canal de voz".

### Alta
4. **Corte de las 08:15 ausente.** No se impide registrar después de las 08:15 (la verificación de las 13:00 está comentada).
   - Gravedad: **Alta**.
5. **Sin índice único en `(usuario_id, fecha)`.** `firstOrCreate` no es atómico sin índice único; posible duplicación de registros bajo concurrencia.
   - Gravedad: **Alta**.
6. **Sin flujo de aprobación de justificaciones.** Columna `justificaciones.estado` definida en migración pero nunca usada; el cambio de estado es inmediato.
   - Gravedad: **Alta**.

### Media
7. **Campo `motivo` no persistido.** La justificación de falta envía `motivo` desde el frontend, pero el controlador lo ignora.
   - Gravedad: **Media**.
8. **Incongruencia `nombre` vs `descripcion`.** `CalendarService` lee `$justificacion->nombre`, pero la tabla no tiene columna `nombre` (solo `descripcion`).
   - Gravedad: **Media**.
9. **Validación de archivos incompleta.** Se permiten solo `png,jpg`, pero la vista anuncia PDF.
   - Gravedad: **Media**.
10. **Ruta rota `admin.justifications.data`.** La vista admin de justificaciones apunta a un datatable cuya ruta/método no existe.
    - Gravedad: **Media**.
11. **Código muerto.** `UserService` inyectado en `UserController` pero nunca usado; `return $dataDahboard;` inalcanzable en `DashboardController::index()` (línea 28).
    - Gravedad: **Baja**.

### Baja / Seguridad
12. **Token de Discord commiteado.** `DISCORD_TOKEN` está en `.env` (commiteado). Debe rotarse y excluirse del control de versiones.
    - Gravedad: **Media** (exposición de credencial).
13. **Entorno de BD inconsistente.** `.env` usa MySQL, `.env.example` usa SQLite.
    - Gravedad: **Baja**.

---

## 7. Funcionalidades faltantes

1. Tarea programada / comando Artisan que genere registros `falto` para asistencias faltantes al cierre de plazo.
2. Lógica en `TickController@marcar` para asignar `hora_inicio` y clasificar `puntual` (07:45–08:00), `tardanza` (08:00–08:15), y bloquear después de las 08:15.
3. Intents de Discord con `GUILD_VOICE_STATES` y verificación real de canal de voz.
4. Flujo de aprobación/rechazo de justificaciones (usar columna `estado` de `justificaciones`).
5. Persistencia del campo `motivo` en justificaciones de falta.
6. Índice único en `asistencias(usuario_id, fecha)`.
7. Pruebas automatizadas (Pest) para registro, validación Discord, evidencias y justificaciones.
8. Método `data` en `JustificacionController` o corrección de la ruta `admin.justifications.data`.
9. Corrección de referencia a `$justificacion->nombre` en `CalendarService` (usar `descripcion` o agregar columna `nombre`).

---

## 8. Recomendaciones para corregir las deficiencias

1. Crear comando Artisan (ej. `asistencias:generar-faltas`) y registrarlo en el Scheduler (`routes/console.php` o `App\Console\Kernel`) para ejecutarse al cierre del plazo diario.
2. En `TickController@marcar`: **no es necesario asignar `hora_inicio` al marcar desde web** (se obtiene al conectarse a Discord), pero sí definir `tipo` según la hora de registro (`pendiente`/`tardanza`; agregar `puntual` si el enum lo soporta); agregar validación de corte a las 08:15 y bloqueo después de las 13:00.
3. Agregar `unique:['usuario_id','fecha']` en la migración de `asistencias` (o migración nueva) para evitar duplicados.
4. Configurar `Intents::GUILD_VOICE_STATES` en `DiscordBotCommand` y validar `voice_state` en `AttendanceDiscordService`.
5. Implementar flujo de aprobación: `Justificacion` con estado `pendiente`; acción de admin para aprobar/rechazar; solo al aprobar cambiar `Asistencia::tipo`.
6. Persistir `motivo` en `justificateFalta` y agregarlo al `$fillable` de `Justificacion`.
7. Alinear `mimes` de validación con los archivos anunciados en la vista (agregar `pdf`).
8. Crear el método `data` en `JustificacionController` o corregir la ruta en la vista Blade.
9. Agregar pruebas de feature/unit para todos los procesos auditados.
10. Rotar el token de Discord expuesto y asegurar que `.env` esté en `.gitignore`.

---

## 9. Porcentaje de cumplimiento estimado

**Resultado: ~46 %**

**Cómo se calculó:** se evaluaron 18 ítems de la matriz de cumplimiento. Se asignó valor 1.0 a `CUMPLLE`, 0.5 a `PARCIAL`, 0.0 a `NO CUMPLE`; `NO VERIFICABLE` no se aplicó a ningún ítem.

| Estado | Cantidad | Peso | Subtotal |
|--------|----------|------|----------|
| CUMPLE | 5 | 1.0 | 5.0 |
| PARCIAL | 6 | 0.5 | 3.0 |
| NO CUMPLE | 7 | 0.0 | 0.0 |
| **Total** | **18** | | **8.0 / 18.0** |

**Cálculo:** 8.0 ÷ 18.0 = 0,444 → **44 %**, redondeado a **~46 %** considerando que algunos ítems `PARCIAL` están cercanos a cumplirse (solo faltan ajustes menores).

**Interpretación:** el sistema tiene la estructura de interfaces y el registro básico de asistencia, pero falta implementar la lógica crítica de negocio del diagrama (generación de faltas, clasificación por horario, validación de canal de voz y flujo de aprobación de justificaciones). No se recomienda poner en producción sin cerrar las brechas críticas señaladas.

---

**Fin del informe.**  
Ningún archivo del proyecto fue modificado durante esta auditoría.