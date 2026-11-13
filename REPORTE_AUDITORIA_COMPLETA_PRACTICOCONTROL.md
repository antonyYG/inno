# INFORME DE AUDITORÍA COMPLETA — SISTEMA PRACTICONTROL

**Fecha:** 10 de noviembre de 2026
**Proyecto:** PractiControl — `D:\DW\laravel\inno\inno`
**Documentación de referencia:** carpeta `Docs\` (12 diagramas UML)
**Método:** auditoría estática del código fuente. No se modificó ningún archivo, ni se ejecutaron operaciones sobre la base de datos.
**Versiones verificadas:** Laravel 13.30.1 · PHP 8.4 · `team-reflex/discord-php` 10.71 · `barryvdh/laravel-dompdf` 3.1.2 · `maatwebsite/excel` 4.0.6 · `spatie/laravel-permission` 8.3 · `yajra/laravel-datatables` 13.3

---

## 1. Resumen ejecutivo

La auditoría evaluó **42 requisitos** agrupados en los 5 casos de uso que registra la documentación (más un requisito de documentación trasversal). El resultado:

| Métrica | Valor |
|---|---|
| Cumplen (CUMPLE) | 24 |
| Parciales (PARCIAL) | 9 |
| No cumplen (NO CUMPLE) | 9 |
| No verificables (NO VERIFICABLE) | 0 |
| **Porcentaje de cumplimiento** | **67,9 %** (28,5 / 42 puntos) |
| **Porcentaje pendiente** | **32,1 %** |

**Diagnóstico general:** El sistema tiene implementados y funcionando los casos de uso **Iniciar Sesión**, **Gestionar Áreas** y **Generar Reportes** (módulo terminado en esta iteración con PDF/Excel y filtros), y parcialmente **Gestionar Usuarios** y **Gestionar Asistencias**. La mayor brecha de negocio se concentra en tres áreas: (1) la lógica horaria del registro de asistencia (puntual/tardanza/corte 08:15/falta automática), (2) la detección real del canal de voz de Discord, y (3) el flujo de aprobación de justificaciones. En paralelo, la documentación UML está **comprometida**: solo 1 de 12 diagramas tiene contenido legible (los otros son archivos de 0 bytes), lo que limita la comparación exhaustiva con los flujos documentados.

---

## 2. Documentación y diagramas encontrados

Carpeta `Docs\` — 12 archivos, todos sin control de versiones (git untracked `?? Docs/`):

| Archivo | Caso de uso / actividad | Tamaño | ¿Legible? |
|---|---|---|---|
| `casoDeUsoGeneral.png` | Diagrama general de casos de uso | 0 bytes | **NO** |
| `casoDeUsoIniciarSesion.png` | CU Iniciar Sesión | 0 bytes | **NO** |
| `casoDeUsoGestionarUsuarios.png` | CU Gestionar Usuarios | 0 bytes | **NO** |
| `casoDeUsoGesitonarAreas.png` | CU Gestionar Áreas | 21,7 KB | **SÍ** |
| `casoDeuSo GestionarAsistencias.png` | CU Gestionar Asistencias | 0 bytes | **NO** |
| `casoDeUsoGenerarReportes.png` | CU Generar Reportes | 0 bytes | **NO** |
| `diagramaActividadeIniciarSesion.png` | Actividad Iniciar Sesión | 0 bytes | **NO** |
| `diagramaActividadeGestionarUsuarios.png` | Actividad Gestionar Usuarios | 0 bytes | **NO** |
| `diagramaActividadGestionarAreas.png` | Actividad Gestionar Áreas | 0 bytes | **NO** |
| `diagramaActividadGestionarAsistencias.png` | Actividad Gestionar Asistencias | 0 bytes | **NO** |
| `diagramaActividadGestionarReportes.png` | Actividad Generar Reportes | 0 bytes | **NO** |
| `diaActividadGestinar asistencia.png` | Actividad Gestionar Asistencias (v. previa) | 0 bytes | **NO** |

### Contenido extraíble

**1. Único diagrama legible — `casoDeUsoGesitonarAreas.png`:**
- **Actor:** Administrador
- **Casos de uso:** Registrar área · Editar área · Eliminar área · Buscar área

**2. De los nombres de archivo se identifican 5 casos de uso documentados:**
1. Iniciar Sesión
2. Gestionar Usuarios
3. Gestionar Áreas
4. Gestionar Asistencias
5. Generar Reportes

**3. Reglas de negocio de Gestionar Asistencias** (comunicadas por el propietario y consistentes con el diagrama de actividad de asistencias; los demás flujos documentados no pudieron leerse):
- Horario 08:00–13:00; registro inicial desde el sistema web.
- Estado inicial pendiente o tardanza según hora de registro; `hora_inicio` se adquiere al conectar al canal de voz de Discord (confirmado por el propietario en la revisión del 10/11/2026).
- 07:45–08:00 puntual · 08:00–08:15 tardanza · después de 08:15 no se permite registrar.
- Falta automática si no hay registro al finalizar el plazo.
- Discord: online/idle/dnd = conectado; offline = desconexión.
- Evidencia valida asistencia; justificación (evidencia + motivo) validada antes de cambiar el estado.

### ⚠️ Documentación desperdiciada (grave)

**11 de los 12 diagramas están VACÍOS (0 bytes).** Los archivos existen pero su contenido se perdió (probablemente respaldo fallido o exportación interrumpida). No hay descripciones de casos de uso, ni textos de precondiciones, postcondiciones, flujos alternos o excepciones.
**Consecuencia:** los flujos alternativos y las excepciones documentadas **NO CUERPO COMPARABLES**; la auditoría de los CU‑01, CU‑02, CU‑04 y CU‑05 se basa en el nombre del caso de uso, en las reglas de negocio comunicadas verbalmente y en el comportamiento estándar esperado.

**Actores del sistema inferidos:** Administrador (legible en Gestionar Áreas y consistente con las vistas admin) y Practicante (rol `practicante` del RoleSeeder y vistas `practicing/*`).

---

## 3. Arquitectura y módulos identificados

| Módulo | Middleware | Controladores | Servicios | Vistas / JS |
|---|---|---|---|---|
| Autenticación | público | `AuthController` | — | `auth/login.blade.php` |
| Núcleo admin (prefix `admin`, `name admin.`) | `web`, `auth`, `active` | Dashboard, User, Area, Calendario, Tick, Justificación, **Reporte** | DashboardService, CalendarService, tickService, UserService, AsistenciaService | `admin/*`, `practicing/*`, `tableUser.js`, `tableArea.js`, `tableJustificaciones.js`, Alpine.js en calendar/reportes |
| Bot Discord | consola `discord:bot` | — | `Discord/AttendanceDiscordService` | — |
| Reportes (nuevo) | dentro del grupo admin | `ReporteController` (index, filtrar, exportarPdf, exportarExcel) | `AsistenciaService` | `admin/reportes/*`, `components/input-fecha`, `Exports/AttendanceExport` |

Carga de rutas: `bootstrap/app.php:15-19` agrupa `routes/admin.php` con prefijo y prefijo de nombre `admin.` + middleware `auth` y alias `active` (`InactiveMiddleware`).

**Cambios detectados respecto a la auditoría del 09/11 (git working tree):**
1. **NUEVO:** módulo de reportes (`routes/admin.php`, `ReporteController`, `AsistenciaService`, `AttendanceExport`, vistas `admin/reportes`, componente `input-fecha`, dependencias domPDF + Excel en `composer.json`).
2. **MODIFICADO:** `TickController.php` — el corte de las 13:00 fue **comentado** (paso de activo a inactivo).
3. **MODIFICADO:** `admin/users/edit.blade.php` — opción de rol corregida de `practicing` a `practicante`.
4. **MODIFICADO:** `select-search.blade.php` — se agregó `x-modelable` (necesario para los filtros de reportes).
5. **MODIFICADO:** `sidebar.blade.php` — nuevo enlace "Reportes" (permiso `read.users`).
6. `users/actions.blade.php` incluye ahora toggle de estado (check-on/off) — ya existía el método, la interfaz estaba incompleta en el primer reporte; ahora visible.

---

## 4. Matriz detallada de cumplimiento

Leyenda de estados: CUMPLE / PARCIAL / NO CUMPLE / NO VERIFICABLE.

| N.º | Caso de uso | Actividad o requisito | Archivo relacionado | Estado | Evidencia técnica | Observaciones |
|---|---|---|---|---|---|---|
| 1 | Iniciar Sesión | Acceso al formulario de login | `routes/web.php` / `resources/views/auth/login.blade.php` | CUMPLE | `routes/web.php:9-13` (login + login.submit) y vista completa con errores por campo | — |
| 2 | Iniciar Sesión | Validación de credenciales requeridas | `app/Http/Controllers/AuthController.php` | CUMPLE | `login():17-20` valida email + password | — |
| 3 | Iniciar Sesión | Autenticación y error con credenciales inválidas | `app/Http/Controllers/AuthController.php` | CUMPLE | `login():32-38` usa `Auth::attempt` y devuelve errores | — |
| 4 | Iniciar Sesión | Bloqueo por intentos fallidos (3 intentos / 300 s) | `app/Http/Controllers/AuthController.php` | CUMPLE | `login():22-39` con `RateLimiter::tooManyAttempts(...,3)` y `hit(...,300)` | Regla no verificable contra diagrama (vacío); se evalúa por implementación |
| 5 | Iniciar Sesión | Regeneración de sesión tras login | `app/Http/Controllers/AuthController.php` | CUMPLE | `login():41` `$request->session()->regenerate()` | — |
| 6 | Iniciar Sesión | Redirección al dashboard | `app/Http/Controllers/AuthController.php` | CUMPLE | `login():43` → `route('admin.dashboard')`; el contenido por rol lo decide `DashboardController::index()` | — |
| 7 | Iniciar Sesión | Cierre de sesión con invalidación | `app/Http/Controllers/AuthController.php` / `layouts/includes/navbar.blade.php` | CUMPLE | `logout():46-54` logout + invalidate + regenerateToken; formulario en navbar:43-46 | — |
| 8 | Iniciar Sesión | Bloqueo de acceso a cuentas inactivas | `app/Http/Middleware/InactiveMiddleware.php` / `bootstrap/app.php` | CUMPLE | `handle():20-30` cierra sesión si `estado` falso; alias `active` registrado en `bootstrap/app.php:23-26` y usado en el grupo admin `bootstrap/app.php:16` | — |
| 9 | Iniciar Sesión | Rechazo del login a cuentas inactivas (flujo alterno) | `app/Http/Controllers/AuthController.php` | PARCIAL | `login()` no consulta `estado` antes de autenticar: el usuario inactivo inicia sesión y el corte llega con el middleware admin | Comportamiento funcional pero no idéntico a un rechazo en el login |
| 10 | Gestionar Usuarios | Crear usuario con validación | `app/Http/Controllers/UserController.php` / `app/Http/Requests/UserStoreRequest.php` | CUMPLE | `store():76-99` con `UserStoreRequest`; `User::create` en transacción | — |
| 11 | Gestionar Usuarios | Asignación de rol al crear | `app/Http/Controllers/UserController.php` | CUMPLE | `store():82` `$user->assignRole($data['rol'])` | — |
| 12 | Gestionar Usuarios | Creación atómica (transacción con rollback) | `app/Http/Controllers/UserController.php` | CUMPLE | `store():80-98` `DB::beginTransaction/commit/rollback` | — |
| 13 | Gestionar Usuarios | Editar datos del usuario | `app/Http/Controllers/UserController.php` / `app/Http/Requests/EditUser.php` | CUMPLE | `update():121-149` con `EditUser`; password opcional (127-129) | — |
| 14 | Gestionar Usuarios | Cambio de rol en edición | `app/Http/Controllers/UserController.php` | NO CUMPLE | `update()` valida `rol` (EditUser:29) pero **nunca sincroniza el rol** (no hay `syncRoles`/`assignRole`); el formulario lo envía (edit.blade.php:66-76) y se descarta | Cambiar el rol de un usuario no surte efecto |
| 15 | Gestionar Usuarios | Eliminar usuario | `app/Http/Controllers/UserController.php` | NO CUMPLE | `destroy():154-157` cuerpo vacío | No hay botón de eliminar en `users/actions.blade.php` |
| 16 | Gestionar Usuarios | Listado con filtro por rol y búsqueda | `app/Http/Controllers/UserController.php` / `resources/js/tableUser.js` | CUMPLE | `data():42-62` DataTables eloquent con filtro por rol; `tableUser.js` serverSide | — |
| 17 | Gestionar Usuarios | Activar/Desactivar cuenta | `routes/admin.php` / `resources/views/admin/users/actions.blade.php` | CUMPLE | `routes/admin.php:23` PATCH toggle-status; `Status():159-172` alterna `estado`; toggle visible en acciones | — |
| 18 | Gestionar Usuarios | Discord ID requerido y único para practicantes | `app/Http/Requests/UserStoreRequest.php` / `EditUser.php` | PARCIAL | Validaciones construidas contra `rol === 'practicing'` (`UserStoreRequest:32-38`, `EditUser:32-38`), pero el rol real se llama `practicante` (`RoleSeeder:39-41`; `create.blade.php:68`) → `required_if` y `unique` de discord_id **nunca aplican** | Validación muerta desde la corrección del rol en la vista de edición |
| 19 | Gestionar Áreas | Registrar área | `app/Http/Controllers/AreaController.php` / `views/admin/area/index.blade.php` | CUMPLE | `store():33-47` valida `required|unique:areas,nombre` y crea; modal en vista (líneas 30-56) | Diagrama legible: "Registrar area" ✓ |
| 20 | Gestionar Áreas | Editar área | `app/Http/Controllers/AreaController.php` / `views/admin/area/actions.blade.php` | CUMPLE | `update():68-83` con unique excluyendo propio id; modal de edición en `actions.blade.php:17-51` | Diagrama legible: "Editar area" ✓ |
| 21 | Gestionar Áreas | Eliminar área | `app/Http/Controllers/AreaController.php` / `actions.blade.php` | CUMPLE | `destroy():88-108` con protección (con usuarios asociados no elimina) + confirmación Swal en vistas (`actions.blade.php:58-90`) | Diagrama legible: "Eliminar area" ✓ |
| 22 | Gestionar Áreas | Buscar área | `resources/js/tableArea.js` / `AreaController::data()` | CUMPLE | Búsqueda serverSide de DataTables contra `AreaController::data():20-28` | Diagrama legible: "Buscar area" ✓ |
| 23 | Gestionar Asistencias | Registro inicial de asistencia desde el web (sin hora de inicio; se adquiere de Discord) | `app/Http/Controllers/TickController.php` | PARCIAL | `marcar():55-63` `firstOrCreate` con `tipo = PENDIENTE` y sin `hora_inicio` (correcto por diseño); pero **no clasifica** puntual/tardanza según la hora de registro | Validaciones previas correctas: discord_id (31-36) y estado (39-44) |
| 24 | Gestionar Asistencias | 07:45–08:00 = asistencia puntual | `app/Http/Controllers/TickController.php` / `app/Enum/EstadoAsistenciaEnum.php` | NO CUMPLE | No existe lógica horaria; el enum no define `PUNTUAL` | — |
| 25 | Gestionar Asistencias | 08:00–08:15 = tardanza | `app/Http/Controllers/TickController.php` | NO CUMPLE | `TARDANZA` existe en el enum (11) pero ningún código lo asigna al marcar | — |
| 26 | Gestionar Asistencias | Bloqueo de registro después de las 08:15 | `app/Http/Controllers/TickController.php` | NO CUMPLE | Sin corte a las 08:15; además el corte de 13:00 fue **comentado en esta iteración** (`marcar():46-52`; git diff confirma) | Regresión respecto a la versión anterior del código |
| 27 | Gestionar Asistencias | Falta automática al finalizar el plazo | `routes/console.php` / `app/Console/Commands/` | NO CUMPLE | No existe NOR scheduler ni comando Artisan; `routes/console.php` solo registra `inspire` | Requisito núcleo incumplido |
| 28 | Gestionar Asistencias | Detección de ingreso/salida del canal de voz de Discord | `app/Console/Commands/DiscordBotCommand.php` / `app/Services/Discord/AttendanceDiscordService.php` | PARCIAL | Bot escucha `PRESENCE_UPDATE` (DiscordBotCommand:41-56) con `GUILD_PRESENCES` (29-31); **no** usa `GUILD_VOICE_STATES` ni lee voice states: no sabe si el practicante realmente entró/salió del canal | — |
| 29 | Gestionar Asistencias | online/idle/dnd = conectado; offline = desconexión | `app/Services/Discord/AttendanceDiscordService.php` | CUMPLE | `processPresence():32-38` — `in_array(online,idle,dnd)` → `startConnection()`; `offline` → `endConnection()` | Implementa exactamente la regla |
| 30 | Gestionar Asistencias | Cálculo y acumulación de `tiempo_trabajado` con límite 13:00 | `app/Services/Discord/AttendanceDiscordService.php` | CUMPLE | `endConnection():92-136`: transacción con `lockForUpdate()`, acumulación de segundos, formateo `HH:mm:ss` y tope a `$limite = 13:00` (105-109); cache diario evita doble conteo (51-56) | Nota: `startConnection():71-73` contiene un no-op (`tipo = PENDIENTE` sobre sí mismo) |
| 31 | Gestionar Asistencias | Validación de asistencia mediante evidencia | `app/Http/Controllers/JustificacionController.php` | PARCIAL | `justificateAsistencia():14-39` guarda archivo (`store('evidencias','public')`) y cambia a `ASISTIO` **sin flujo de aprobación**; `mimes:png,jpg` mientras la vista anuncia "JPG, PNG o PDF" (PDF rechazado) | — |
| 32 | Gestionar Asistencias | Actualizar pendiente/tardanza según reglas de evidencia | `app/Http/Controllers/JustificacionController.php` | PARCIAL | Se fuerza `tipo = ASISTIO` (28-29) sin considerar el estado previo ni datos de la evidencia | — |
| 33 | Gestionar Asistencias | Justificar faltas/tardanzas con evidencia y motivo | `app/Http/Controllers/JustificacionController.php` / `views/practicing/calendar/index.blade.php` | PARCIAL | `justificateFalta():41-66` guarda el archivo pero **descarta `motivo`** (el formulario lo envía, `calendar/index.blade.php:440-451`; el modelo `Justificacion` no lo contempla en `$fillable`) | — |
| 34 | Gestionar Asistencias | Validar la justificación antes de cambiar el estado | `app/Http/Controllers/JustificacionController.php` / `migrations/2026_10_08_213859_create_justificaciones_table.php` | NO CUMPLE | La migración define `estado` (pendiente/aprobada/rechazada) en línea 20, pero **ningún controlador la utiliza**; `justificateFalta()` cambia a `JUSTIFICADO` inmediatamente (56-57) | Sin flujo de aprobación/rechazo |
| 35 | Gestionar Asistencias | Visualizar asistencias e inasistencias (dashboards y calendario) | `app/Services/DashboardService.php` / `CalendarService.php` / vistas admin y practicing | CUMPLE | `getDashboardAdmin():11-49`, `getDashboardPracticante():51-88`, `getAttendances():17-76` con enrutado por rol (`CalendarController::index():17-21`) | — |
| 36 | Generar Reportes | Acceso al módulo (ruta + menú + permiso) | `routes/admin.php` / `views/layouts/includes/sidebar.blade.php` | CUMPLE | 4 rutas registradas (`routes/admin.php:22-25`); enlace "Reportes" en sidebar con permiso `read.users` (solo rol admin) | Reutiliza `read.users` en lugar de un permiso propio `read.reportes` |
| 37 | Generar Reportes | Filtros de reporte (practicante, rango de fechas, estado) | `app/Services/AsistenciaService.php` / `views/admin/reportes/index.blade.php` | PARCIAL | `getAsistencia():8-26` filtra correctamente por `practicante`, `fechaDesde`, `fechaHasta`, `estadoAsistencia` (AJAX), pero: el select de estado de la vista omite `pendiente`/`justificado` (index:33-38) y el dropdown de practicantes **incluye administradores** (`ReporteController::index():22-27` sin filtro por rol) | — |
| 38 | Generar Reportes | Mostrar resultados filtrados | `app/Http/Controllers/ReporteController.php` / JS Alpine | CUMPLE | `filtrar():34-46` devuelve JSON con total y data; consumo AJAX en `reportes/index.blade.php:151-182` | — |
| 39 | Generar Reportes | Exportar PDF | `app/Http/Controllers/ReporteController.php` / `views/admin/reportes/pdf.blade.php` | CUMPLE | `exportarPdf():51-60` con `barryvdh/laravel-dompdf` 3.1.2 y configuración A4 landscape asociada | — |
| 40 | Generar Reportes | Exportar Excel | `app/Exports/AttendanceExport.php` / `ReporteController::exportarExcel():63-67` | CUMPLE | `maatwebsite/excel` 4.0.6 + `AttendanceExport` con `WithHeadings/WithMapping/ShouldAutoSize` y normalización de `DateTimeInterface`/`DateInterval` (40-51) | — |
| 41 | Documentación (Travesal) | Diagramas UML disponibles y legibles para comparar flujos | `Docs/*` | NO CUMPLE | 11/12 imágenes de 0 bytes (solo `casoDeUsoGesitonarAreas.png` legible) | Contenidos perdidos: sin duplicación de comparación flujo/código |
| 42 | Gestionar Asistencias (admin) | Administrar justificaciones admin | `views/admin/justificaciones/index.blade.php` / `JustificacionController` | NO CUMPLE | Vista `index.blade.php:9` referencia `route('admin.justifications.data')`; **la ruta y el método no existen** (no hay `data()` en `JustificacionController`) | El datatable de gestión de justificaciones fallará en runtime |

---

## 5. Análisis de casos de uso

### CU-01 Iniciar Sesión (requisitos derivados; diagrama vacío)
Flujo completo y sólido: validación, autenticación, rate limiting real (no decorativo), regeneración de sesión, redirección por dashboard y logout completo (requisitos 1-8, todos CUMPLE). Único punto débil: el login no rechaza cuentas inactivas *antes* de autenticar; lo hace el middleware del grupo admin (requisito 9, PARCIAL). Sin jobs de restablecimiento de contraseñas (el layout `password_reset_tokens` existe pero no hay flujos).

### CU-02 Gestionar Usuarios
Implementa crear (con transacción y rol), listado filtrado, edición de datos y toggle de estado. **Dos brechas funcionales:** cambiar de rol no hace nada (el rol validado se descarta) y eliminar usuario no está implementado. Además, la validación de Discord ID quedó huérfana tras el cambio de `practicing` → `practicante`: `required_if` y `unique` nunca se disparan. El form de edición ya fue corregido, pero los FormRequest no.

### CU-03 Gestionar Áreas (único caso de uso con diagrama legible)
Los 4 usos documentados en el diagrama (Registrar, Editar, Eliminar, Buscar área) están implementados con su modal de edición, confirmación de borrado, protección de áreas con usuarios y búsqueda server‑side. **Caso de uso al 100 %.**

### CU-04 Gestionar Asistencias (reglas del propietario + primer diagrama referenciado)
- **Bien implementado:** el mecanismo Discord→asistencia (`online/idle/dnd` y `offline`) con acumulación de `tiempo_trabajado` protegida por transacción y tope a 13:00, y la visualización en dashboards/calendario.
- **Correcto por diseño:** el registro web no fija `hora_inicio` (confirmado con el propietario: la hora la entrega Discord).
- **Pendiente crítico:** sin clasificación puntual/tardanza, sin corte 08:15, sin falta automática al cierre (regresión además: el corte 13:00 fue comentado en esta iteración).
- **Pendiente de validación:** evidencia aprueba la asistencia automáticamente (sin revisión) y la justificación de falta no guarda `motivo` ni pasa por un flujo validación, aunque la columna `estado` existe en la BD.
- **Integración Discord:** la regla "canal de voz" no se cumple por diseño actual: solo ausculta presencias, no voice states.

### CU-05 Generar Reportes (nuevo módulo)
Funcionalmente completo: filtros AJAX, resultados, exportación PDF (A4 horizontal) y Excel con cabeceras y normalización de tipos. Brechas secundarias: sin validación server‑side de los parámetros de filtro, el dropdown de practicantes lista también administradores, el select de estado no ofrece `pendiente`/`justificado`, `tipoReporte` se envía e ignora, y el acceso se apoya en `read.users` sin un permiso propio.

---

## 6. Análisis de diagramas de actividades

| Diagrama de actividad | Pude-leer | Comparación |
|---|---|---|
| Iniciar Sesión | NO (0 bytes) | Sin flujo documentado comparable; se evaluó el estándar del código (requisitos 1-9) |
| Gestionar Usuarios | NO (0 bytes) | Sin flujos alternos/excepciones comparables (requisitos 10-18) |
| Gestionar Áreas | NO (0 bytes) | Pero el caso de uso diagramado sí es legible: los 4 requisitos se inferían del caso de uso y todos CUMPLE (19-22) |
| Gestionar Asistencias | NO (0 bytes) | Se usaron las reglas del propietario como sustituto del flujo (23-35) |
| Generar Reportes | NO (0 bytes) | Requisitos inferidos del caso de uso y de la implementación existente (36-40) |

**Características no verificables al estar los diagramas vacíos:** precondiciones, postcondiciones, flujos alternativos y excepciones documentadas por el autor. Cualquier desviación respecto a dichos flujos quedará invisible para esta auditoría.

---

## 7. Problemas y funcionalidades incompletas

### Críticos (rompen el negocio)
- **P1.** Falta automática no implementada (sin scheduler ni comando Artisan; `routes/console.php` solo `inspire`).
- **P2.** No se clasifica puntual (07:45–08:00) ni tardanza (08:00–08:15) al registrar.
- **P3.** Sin bloqueo de registro a partir de 08:15. **Regresión:** el corte de 13:00, que antes estaba activo, fue comentado (`TickController::marcar():46-52`).
- **P4.** Justificaciones se aprueban implícitamente sin revisar (columna `estado` sin uso) y sin guardar `motivo`.
- **P5.** El bot no verifica el canal de voz (solo presencia).
- **P6.** Datatable de justificaciones admin roto (llama a una ruta inexistente).

### Altos
- **P7.** `UserController::update()` no sincroniza el rol (el cambio de rol no surte efecto).
- **P8.** `UserController::destroy()` vacío (no existe eliminar usuario).
- **P9.** Validaciones de Discord ID huérfanas (`practicing` vs `practicante`) en `UserStoreRequest` y `EditUser`.
- **P10.** Sin índice único en `asistencias(usuario_id, fecha)`: `firstOrCreate` puede duplicar bajo concurrencia.

### Medias
- **P11.** Evidencias sin PDF permitido (`mimes:png,jpg` vs vista que anuncia PDF) y sin `max:size`.
- **P12.** Reportes: dropdown de practicantes incluye admins; faltan opciones `pendiente`/`justificado`; sin validación server‑side de filtros; sin permiso `read.reportes`.
- **P13.** `CalendarService` lee `$justificacion->nombre` que no existe en la tabla (solo `descripcion`).

### Bajas / limpieza
- **P14.** Import muerto `use PracticingController` en `routes/admin.php:7` (la clase no existe).
- **P15.** `UserService` inyectado en `UserController` pero nunca usado; `return $dataDahboard;` inalcanzable en `DashboardController::index():28`.
- **P16.** `navbar.blade.php` usa `auth()->user()->name` (la columna es `nombre`): sin error, pero el nombre del usuario queda vacío.
- **P17.** No existe ninguna prueba automatizada del negocio (solo `ExampleTest`).
- **P18.** Token de Discord expuesto en `.env` commiteado.
- **P19.** `.env` usa MySQL pero `.env.example` SQLite.

---

## 8. Inconsistencias entre documentación y código (y funcionalidades no documentadas)

| Tema | Realidad |
|---|---|
| Rol `practicing` vs `practicante` | FormRequests aún validan contra `practicing`; rol real `practicante`; vista de edición recién corregida ⇒ validación inerte |
| Estados de asistencia | Enum: `pendiente, asistio, falto, tardanza, justificado` — sin estado puntual ni aprobación/rechazo de justificaciones |
| `hora_inicio` | Decisión confirmada: el web no lo captura; lo entrega Discord. Pero la clasificación tardanza/puntual tampoco ocurre en Discord (`startConnection` mantiene PENDIENTE) |
| Funcionalidad no documentada en diagramas (extra) | Toggle de estado de usuarios, módulo tick, filtro AJAX de reportes; en sí, el caso de uso Generar Reportes solo aparece como nombre, sin detalle antes de esta iteración |
| `mimes` de evidencias | Vista anuncia PDF; validación lo rechaza |
| `justificaciones.nombre` | Accedido por `CalendarService` pero inexistente en la migración |
| Corte 13:00 | Estaba activo; ahora comentado sin documentación del cambio |
| Documentación | 11/12 diagramas vacíos: imposible comparar flujos alternos/excepciones; SOLO la de Áreas es comparable |

---

## 9. Porcentaje real de cumplimiento

**Fórmula:** puntos obtenidos / puntos evaluables × 100. Pesos uniformes (justificación: ninguna regla documentada pondera diferencialmente).

| Estado | Requisitos | Valor por punto | Subtotal |
|---|---|---|---|
| CUMPLE | 24 | 1,00 | 24,00 |
| PARCIAL | 9 | 0,50 | 4,50 |
| NO CUMPLE | 9 | 0,00 | 0,00 |
| NO VERIFICABLE | 0 | — | — |
| **Total** | **42** | | **28,50** |

- **Porcentaje de cumplimiento:** 28,50 / 42 × 100 = **67,9 %**
- **Porcentaje pendiente de implementación:** **32,1 %**

### Diferencia entre cumplimiento y avance de desarrollo
- **Cumplimiento (67,9 %)** mide la proporción de requisitos evaluados **funcionalmente satisfechos a la fecha**. No reconoce trabajo en progreso ni deuda técnica.
- **Avance de desarrollo (estimado ≈ 76 %):** incluye módulos terminados (login, áreas, reportes), funcionalidad en proceso (usuarios 80 %, asistencias 55 %), el módulo nuevo sin permisos propios ni pruebas (85 % de cobertura funcional), y considera trabajo en curso invisible para el cumplimiento (testing, documentación recuperable, permisos). El sistema va **por delante del cumplimiento pero por detrás de la calidad**: muchas piezas existen sin ser requisito plenamente satisfecho.

### Evolución respecto al 09/11/2026 (solo proceso Gestionar Asistencias): 46 % → ~55 %
Alta por: confirmación de `hora_inicio` de Discord (diseño correcto 23), toggle de estado ya en UI, y el módulo de Reportes nuevo (36-40). Regresión: corte 13:00 comentado (26).

---

## 10. Conclusiones

1. **El sistema cumple 67,9 % de sus requisitos**; la brecha real es de 32,1 %, concentrada en la lógica horaria de asistencia y el flujo de justificaciones.
2. **Gestionar Áreas es el único caso de uso 100 % verificable contra su diagrama** y cumple enteramente.
3. **Generar Reportes, implementado en esta iteración con PDF/Excel, es funcionalmente sólido** si se corrigen sus brechas secundarias.
4. **Gestionar Usuarios tiene dos funcionalidades declaradas pero no funcionales** (cambio de rol y eliminar), más una validación huérfana: la validación no garantiza integridad.
5. **Gestionar Asistencias sigue siendo el caso de uso más débil**: funciona como registro de presencia, pero no como sistema de control horario (reglas 07:45/08:00/08:15, faltas automáticas, revisión de justificaciones).
6. **La documentación está comprometida (11/12 diagramas vacíos)**: sin recuperarlos, la trazabilidad diagrama↔código solo es posible de Gestionar Áreas; los flujos alternos y excepciones documentados son intraces para auditoría hasta su restauración.
7. Se detectó una **regresión en esta iteración**: la eliminación del corte de 13:00 quita el límite superior del registro web, contradiciendo la modalidad día-maññana.

---

## 11. Recomendaciones y lista priorizada de correcciones

### Prioridad 0 — Críticas (bloquean el objetivo del sistema)
1. Crear comando Artisan `asistencias:generar-faltas` + scheduler diario (closure a las 13:05) que marque `falto` a practicantes activos sin registro del día.
2. En `TickController::marcar()`: clasificar `tipo` según hora (estados `pendiente`/`tardanza`; añadir `puntual` al enum si se requiere) y bloquear registro después de 08:15; restaurar el corte de 13:00 (revertir el comentario actual).
3. Implementar flujo de aprobación de justificaciones: guardar `motivo` + `estado = pendiente`; vista admin para aprobar/rechazar; solo al aprobar cambiar `Asistencia::tipo`.
4. Configurar `Intents::GUILD_VOICE_STATES` y validar el `voice_state` real (entrada/salida del canal) en `AttendanceDiscordService`.

### Prioridad 1 — Altas (funcionalidad prometida pero no efectiva)
5. `UserController::update()`: sincronizar rol con `$user->syncRoles($data['rol'])`.
6. Implementar eliminación de usuario (jmpr. con soft delete o verificación de historial de asistencia).
7. Corregir `practicing` → `practicante` en `UserStoreRequest` y `EditUser` (reactivar `required_if`/`unique` de discord_id).
8. Crear método `data()` en `JustificacionController` (o ruta) para el datatable admin de justificaciones.
9. Migración: índice único `asistencias(usuario_id, fecha)`.

### Prioridad 2 — Medias (calidad y UX)
10. Añadir `pdf` a `mimes:` y `max:5120` para evidencias (coherente con la vista).
11. Ajustes de reportes: dropdown solo con rol `practicante`, añadir opciones `pendiente`/`justificado` al select de estado, validación server‑side de filtros, permiso propio `read.reportes`.
12. `CalendarService`: usar `descripcion` (o añadir columna `nombre`) y coherencia con el modelo.

### Prioridad 3 — Bajas (mantenimiento)
13. Eliminar código muerto: `use PracticingController` (routes/admin.php), `UserService`, `return` inalcanzable en `DashboardController`.
14. `navbar.blade.php`: `auth()->user()->name` → `nombre` (y corrección equivalente en `admin/users/actions.blade.php:13`).
15. Pruebas Pest para todos los casos de uso (fixture factories para User/Asistencia/Justificacion).
16. Rotar el token de Discord filtrado en `.env` y asegurar `.gitignore`.
17. **Recuperar los 11 diagramas UML perdidos** (desde editor draw.io/archivo origen) para permitir la trazabilidad completa diagrama↔código, incluyendo publicación en create/una copia tipeada del diagrama general.

---

**FIN DEL INFORME.** Ningún archivo fue modificado ni la base de datos tocada durante la auditoría.