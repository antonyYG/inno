# REPORTE DE AUDITORÍA TÉCNICA Y FUNCIONAL COMPLETA — PRACTICONTROL
**Tercera auditoría · 16 de noviembre de 2026 · CRUD AGENCY S.A.C.**

> Auditoría de **solo lectura**. No se modificó, creó ni eliminó ningún archivo del proyecto.
> Metodología: lectura de código fuente completo, verificación de rutas → controladores → servicios → BD → vistas, y verificación directa del estado de la base de datos MySQL (`inno`, consultas SELECT/SHOW únicamente).

---

## 1. RESUMEN EJECUTIVO

PractiControl avanzó de forma significativa desde la auditoría del 10-nov (cumplimiento 67.9 %): se incorporaron los **5 módulos de innovación** (seguimiento, reporte de evidencias, certificados, auditoría administrativa y notificaciones) y el flujo de revisión de evidencias/justificaciones quedó completamente funcional, verificado en navegador con datos reales.

**Estado por concepto (fórmula detallada en §12):**

| Concepto | Avance |
|---|---|
| Funcional (63 requisitos) | **84.9 %** |
| Técnico (15 criterios) | **66.7 %** |
| Innovación (5 mejoras) | **70.0 %** |
| **Global ponderado** | **≈ 76.8 %** |

**Lo más sólido:** el ciclo completo evidencia/justificación (envío → validación → estados → auditoría → notificación) es coherente de extremo a extremo; la acumulación de horas por Discord es robusta (deduplicación con caché, bloqueo de fila, límites 08:00–13:00); la auditoría administrativa y las notificaciones funcionan con datos reales en BD.

**Las debilidades reales (verificados en código y BD):**

1. **La clasificación automática no existe**: nada del código asigna `asistio` o `tardanza`. Solo el administrador puede convertir una asistencia en `asistio` (al validar evidencia). El registro marcado hoy por juan quedó **`pendiente` con 00:01:46 acumulados** y seguirá pendiente para siempre si no se sube evidencia. La columna `tardanza` solo existe gracias al seeder.
2. **Corte de 13:00 comentado y sin scheduler**: no hay faltas automáticas ni cierre de jornada (igual que en la auditoría anterior).
3. **El seguimiento de prácticas está parcialmente desconectado**: `fecha_inicio`, `fecha_fin` y `horas_requeridas` existen en BD (valores NULL y 360 por defecto, verificado), pero **ningún formulario permite establecerlos** (create/edit no tienen esos campos, los FormRequest no los validan y el atributo `#[Fillable]` de User ni siquiera incluye `horas_requeridas`). El estado "vencido" es inalcanzable desde la interfaz.
4. **Bug visible en la navbar**: usa `auth()->user()->name`, pero la columna es `nombre` → el nombre del usuario y su inicial se muestran vacíos en todas las páginas.
5. **Validaciones muertas**: los FormRequest siguen validando contra el rol `'practicing'`, que no existe (el rol real es `'practicante'`) → las reglas `required_if:rol,practicing` **nunca se disparan**: se puede crear un practicante sin Discord ID ni área.
6. **Sin pruebas automatizadas** (siguen siendo solo los 2 `ExampleTest` triviales) y el token de Discord sigue expuesto en `.env`.

---

## 2. ARQUITECTURA ENCONTRADA

```
routes/web.php        → login/logout (AuthController, rate limiting 3 intentos)
routes/admin.php      → grupo web+auth+active, prefijo /admin, nombre admin.*
                        Dashboard, Users, Areas, Reportes (asistencias + evidencias),
                        Calendario, Tick, Revisión, Certificado, Auditorías, Notificaciones

Controladores (15):
  AuthController      login con RateLimiter (3 intentos/300s) + logout seguro
  DashboardController delega 100 % en DashboardService (admin / practicante)
  UserController      CRUD + Status (toggle) + getCertificate (PhpWord)
  AreaController      CRUD con FiltersScope
  TickController      registro inicial de asistencia (pendiente)
  CalendarController  data del calendario + 4 endpoints de revisión (transacción + auditoría)
  JustificacionController  subida evidencia/justificación + notificación a admins
  ReporteController   reportes asistencia + evidencias (DOMPDF + Maatwebsite)
  AuditoriaController dataTable de activity_log (log_name='auditoria')
  NotificacionController  data + marcarLeida (Laravel database notifications)

Servicios:
  DashboardService    indicadores + seguimiento (calcularSeguimiento, America/Lima)
  CalendarService     mapeo del calendario (colores, estados, evidencias)
  AttendanceDiscordService  núcleo Discord: presence → acumulación de tiempo
  tickService         info de tick/asistencia semanal
  AuditoriaService    envoltorio de spatie/activitylog
  ReporteEvidenciaService  filtros de evidencias para reportes
  UserService         duplicado de UserController@store — SIN USO real (código muerto)
  AsistenciaService   filtros de asistencias para reportes

Discord (DiscordBotCommand): solo PRESENCE_UPDATE → processPresence()

Modelos: User (#[Fillable] attribute, HasRoles, Notifiable), Asistencia (enum estado),
         Justificacion, Area (ScopedBy FiltersScope). Casteos correctos.

BD (verificada): usuarios (fecha_inicio, fecha_fin, horas_requeridas=360, discord_id unique),
                 asistencias (16), justificaciones (17), activity_log (7 con log 'auditoria'),
                 notifications (1), roles admin/practicante, permisos spatie.

Vistas: Blade + Alpine.js + Tailwind + FullCalendar + DataTables (js por módulo vía @vite)
        + SweetAlert2 + Flowbite. Layout compartido con navbar (campana) y sidebar (permisos).

Middleware: auth, active (InactiveMiddleware cierra sesión de cuentas inactivas),
            can:read/create/edit.users en UserController.
```

**Observaciones de arquitectura:** la separación controlador/servicio está bien aplicada en Dashboard, Calendar y Discord. Quedan restos: `UserService` duplica lógica de `store` sin ser usado; `JustificacionController` tiene comentado en bloque un index/data antiguos; la carpeta de justificaciones en disco quedó duplicada (`justificacion/` y `justificaciones/`, verificado en `storage/app/public`).

---

## 3. MÓDULOS AUDITADOS

### A. Gestión de usuarios

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| A.1 Registro de practicantes | **TIBIO** | `UserController@store` + `UserStoreRequest` |
| A.2 Edición de información | **TIBIO** | `UserController@update` + `EditUser` + `admin/users/edit` |
| A.3 Activación / desactivación | **SÓLIDO** | `Status()` + `admin.users.toggle-status` + `InactiveMiddleware` |
| A.4 Asignación de roles y permisos | **TIBIO** | `assignRole` en store; middleware `can:*` |
| A.5 Validación de datos | **TIBIO** | `UserStoreRequest` / `EditUser` |
| A.6 Relación con áreas | **SÓLIDO** | FK `area_id` → areas, `x-select-search` |
| A.7 Asociación de cuenta Discord | **TIBIO** | `discord_id` (único en BD) |

- **A.1/A.5:** el formulario y la BD usan el rol `'practicante'`, pero las reglas validan `'practicing'` (`required_if:rol,practicing`, `Rule::unique` condicionado). Al no coincidir, **nunca se ejecutan**: un practicante puede crearse sin `discord_id` ni `area_id`. La unicidad de `discord_id` se protege solo a nivel de BD (error genérico en el `catch`, no mensaje de validación). `password` en store es solo `required` (sin `min:8`).
- **A.2:** `update()` no sincroniza roles (`syncRoles` ausente): cambiar el rol en el formulario de edición **no tiene efecto**. Los campos `horas_requeridas`, `fecha_inicio`, `fecha_fin` no existen en el formulario ni en las validaciones → son imposibles de establecer (impacto directo en el seguimiento, ver módulo D). El atributo `#[Fillable]` de `User` omite `horas_requeridas` (la edición masiva la descartaría incluso si se enviara).
- **A.4:** correcto al crear; incompleto al editar. `destroy()` está vacío (no hay eliminación; la desactivación sí funciona y es la vía correcta para este alcance).
- **Lo bueno:** transacciones en store/update con rollback, `#[Hidden]` para password, rate limiting en login, middleware de permisos real.

### B. Control de asistencia

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| B.1 Registro inicial de asistencia | **SÓLIDO** | `TickController@marcar` (`firstOrCreate` → pendiente) |
| B.2 Validación del horario (corte 13:00) | **FALTANTE** | `TickController` líneas 46–51 **comentadas** |
| B.3 Clasificación asistió / tardanza | **FALTANTE** | ninguna asignación de `TARDANZA` ni auto-`ASISTIO` en el código |
| B.4 Registro automático de faltas | **FALTANTE** | `routes/console.php` solo tiene `inspire` (sin scheduler) |
| B.5 Integración Discord (presencia) | **SÓLIDO** (con nota) | `DiscordBotCommand` + `AttendanceDiscordService` |
| B.6 Detección de conexiones/desconexiones | **SÓLIDO** | `startConnection` / `endConnection` |
| B.7 Acumulación de horas | **SÓLIDO** | `endConnection`: lockForUpdate + suma de segundos |
| B.8 Restricción de horario 08:00–13:00 | **SÓLIDO** | clamp en `startConnection` y `endConnection` |
| B.9 Evitar registros duplicados | **SÓLIDO** | `Cache::add("attendance:{id}:connection")` |
| B.10 Desconexiones inesperadas | **TIBIO** | caché en BD sobrevive reinicios del bot |
| B.11 Manejo de errores del bot | **TIBIO** | re-lanza tras restaurar caché; sin log ni monitoreo |
| B.12 Registro previo obligatorio | **SÓLIDO** | `processPresence` retorna si no hay asistencia del día |

- **B.5:** el bot funcionó con datos reales hoy mismo (asistencia id 16 con `hora_inicio 09:24:50` y tiempo acumulado proviene de una conexión real de Discord). La continuidad del servicio depende de mantener el proceso `discord:bot` corriendo manualmente — el mecanismo de larga duración en producción queda como **NO VERIFICADO**.
- **B.6/B.7/B.9:** la implementación es correcta y no se contabiliza tiempo duplicado; se acumulan múltiples conexiones y el cierre restaurado tras error evita pérdida de datos.
- **B.2/B.3/B.4 (el bloque pendiente más importante del sistema):** hoy, un practicante que marca asistencia y se conecta a Discord pero no sube evidencia queda `pendiente` con tiempo acumulado indefinidamente; nadie lo convierte en `asistio`/`tardanza`; y los días sin registro nunca se marcan `falto` automáticamente. El dashboard del administrador solo cuenta `asistio`/`tardanza`/`falto`, por lo que los pendientes quedan invisibles para los indicadores.
- Nota menor: las constantes `HORA_INICIO = '10:00:00'` / `HORA_FIN = '13:00:00'` de `AttendanceDiscordService` **no se usan**; los límites reales (08:00/13:00) están puestos a mano en `setTime(8,0,0)` / `setTime(13,0,0)`.

### C. Gestión de evidencias y justificaciones

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| C.1 Envío de evidencias | **SÓLIDO** | `JustificacionController@justificateAsistencia` |
| C.2 Envío de justificaciones | **SÓLIDO** | `JustificacionController@justificateFalta` |
| C.3 Validación de archivos | **SÓLIDO** | jpg/png/pdf, 5 MB, duplicados, propiedad |
| C.4 Almacenamiento | **SÓLIDO** | `storage/app/public/evidencias|justificaciones` |
| C.5 Visualización de documentos | **SÓLIDO** | CalendarService (URL de Storage) + drawer practicante |
| C.6 Aprobación/rechazo + observación | **SÓLIDO** | 4 endpoints de `CalendarController` (transacción + auditoría) |
| C.7 Restricción de solicitudes duplicadas | **SÓLIDO** | verificación en ambos controladores |
| C.8 Historial de estados | **SÓLIDO** | `estado_revision` en calendario + estados en DB |

Verificado en vivo en la auditoría previa (13-nov) y re-verificado el código hoy: aceptar evidencia → `asistio` (incluye tardanza si aplica), rechazar → `falto` definitivo sin posibilidad de re-sube, justificación aprobada → `justificado`, con observación opcional en todos los endpoints. Los tres estados se pintan correctamente en el calendario del practicante (falta definitiva, justificada, asistida). **Este módulo es el núcleo mejor resuelto del sistema.**

### D. Seguimiento de prácticas

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| D.1 Horas acumuladas | **SÓLIDO** | `TIME_TO_SEC(tiempo_trabajado)` sobre asistio+tardanza |
| D.2 Horas restantes | **SÓLIDO** | `calcularSeguimiento()` |
| D.3 Porcentaje de cumplimiento | **SÓLIDO** | `min(100, round(...), 2)` |
| D.4 Fechas de inicio y finalización | **TIBIO** | se muestran, pero **no se pueden establecer por UI** |
| D.5 Estado de prácticas | **TIBIO** | en_curso/completado funcionan; `vencido` inalcanzable |
| D.6 Consistencia de los cálculos | **SÓLIDO** | misma fórmula en admin, practicante y certificado |
| D.7 Presentación a admin y practicante | **SÓLIDO** | tablas/tarjetas en ambos dashboards |

- Cálculos correctos y consistentes (la suma de BD con `TIME_TO_SEC` coincide con lo que se muestra). El diseño de `calcularSeguimiento` es adecuado y simple.
- **D.4/D.5 (hallazgo central de esta mejora de innovación):** verificado en BD — los 2 usuarios tienen `fecha_inicio = NULL`, `fecha_fin = NULL`, `horas_requeridas = 360` (default). No existe formulario, campo ni validación para cambiarlos; además `#[Fillable]` omite `horas_requeridas`. Consecuencia: todo practicante muestra fechas vacías, `horas_requeridas` fija en 360 y estado `en_curso` o `completado` — nunca `vencido`. La mejora #1 funciona **solo si se edita la BD a mano**.

### E. Reportes

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| E.1 Reporte de asistencias | **SÓLIDO** | `ReporteController@filtrar` + vista con pestaña |
| E.2 Reporte de evidencias + filtros | **TIBIO** | `ReporteEvidenciaService` + `filtrarEvidencias` |
| E.3 Exportación PDF/Excel de evidencias | **TIBIO** | `exportarEvidenciasPdf/Excel` + `EvidenceExport` |
| E.4 Consistencia con la base de datos | **SÓLIDO** | consultas con `whereDate`, `with('asistencia.usuario')` |

- E.2/E.3: el código está completo y bien estructurado (filtros por practicante/fechas/estado/estado de asistencia, estadísticas, exportación con cabeceras correctas), pero **no se ejecutó en esta auditoría** (no se abrió la pestaña de reportes de evidencias en el navegador): se clasifica como TIBIO pendiente de una verificación en ejecución. No se cuenta como demostrado hasta probarlo.
- E.1: verificado en auditorías previas con datos reales.

### F. Certificados

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| F.1 Validación de horas requeridas | **SÓLIDO** | `getCertificate` bloquea con mensaje si no se cumplieron |
| F.2 Cálculo correcto de horas | **SÓLIDO** | misma agregación que el seguimiento |
| F.3 Información del practicante | **SÓLIDO** | nombre, área, dni/carrera/institución/semestre |
| F.4 Fechas de prácticas | **TIBIO** | usa min/max de asistencias, no `fecha_inicio/fin` |
| F.5 Generación del documento | **TIBIO** | PhpWord + `plantillas/certificado.docx` (existe, verificado) |
| F.6 Restricción para no cumplidos | **SÓLIDO** | backend + botón deshabilitado en `admin/users/actions` |

- F.5: la plantilla existe en `storage/app/plantillas/certificado.docx` y el código es correcto, pero **no se generó un certificado en esta auditoría** → no verificado en ejecución.
- F.4: aceptable para el alcance (las fechas de asistencias válidas son un buen proxy), pero si se setean `fecha_inicio/fin` del usuario sería más preciso.
- Detalle: el botón de certificado es un enlace directo; los campos opcionales (dni, carrera, institución, semestre) no se pueden capturar desde la UI (quedan con placeholders `____`).

### G. Auditoría administrativa

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| G.1 Registro de acciones | **SÓLIDO** | `AuditoriaService` + spatie/activitylog (7 registros reales) |
| G.2 Identificación del administrador | **SÓLIDO** | `causer` (session-based) |
| G.3 Identificación del practicante | **SÓLIDO** | `subject` + resolución en `AuditoriaController@data` |
| G.4 Estado anterior / nuevo | **SÓLIDO** | propiedades `datos_anteriores`/`datos_nuevos` |
| G.5 Fecha y hora | **SÓLIDO** | `created_at` de activity_log |
| G.6 Registro de aprobaciones/rechazos | **SÓLIDO** | 4 eventos distintos en BD (verificado: validada, aprobada, rechazada ×2 tipos) |
| G.7 Integridad del historial | **SÓLIDO** | activity_log es append-only; llamadas dentro de transacciones |

- Todos los eventos de revisión (aprobada/rechazada) están registrados en BD con evento y descripción. La tabla del módulo (`tableAuditorias.js`, vista, rutas) funciona.
- Nota menor técnica: 1 registro tiene `event = NULL` (llamada antigua sin evento) y `data()` resuelve el practicante con cargas perezosas por fila (N+1 leve, ver §10).

### H. Notificaciones

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| H.1 Notificación por nueva evidencia | **SÓLIDO** | `Notification::send` a usuarios con rol admin (BD, verificada) |
| H.2 Notificación por nueva justificación | **SÓLIDO** | misma vía en `justificateFalta` |
| H.3 Contador de no leídas | **SÓLIDO** | `unreadNotifications()->count()` |
| H.4 Marcado como leído | **SÓLIDO** | `NotificacionController@marcarLeida` (scoped al usuario) |
| H.5 Actualización de notificaciones | **SÓLIDO** | polling cada 15 s (`notificaciones.js`) |
| H.6 Prevención de duplicadas | **SÓLIDO** | 1 notificación por envío; el bloqueo de duplicados evita repeticiones |
| H.7 Seguridad y autorización | **SÓLIDO** | rutas protegidas por auth+active; lectura/escritura limitada al usuario |

- Verificado en BD: existe 1 notificación real dirigida al admin (id 1), tipo `SolicitudPracticaNotification`, con marcado de leída funcionando.
- Detalle menor: la campana se muestra también a los practicantes (que nunca reciben notificaciones). No es un fallo de seguridad (su lista siempre estará vacía), pero es UI innecesaria y algo confusa.

### I. Paneles de control

| Funcionalidad | Estado | Evidencia |
|---|---|---|
| I.1 Dashboard del administrador | **SÓLIDO** | indicadores del día, gráfico semanal, áreas, tabla de seguimiento |
| I.2 Dashboard del practicante | **SÓLIDO** | indicadores personales + seguimiento propio |
| I.3 Indicadores coherentes con BD | **SÓLIDO** | conteos agrupados por tipo con `America/Lima` |
| I.4 Gráficos | **SÓLIDO** | Chart.js sobre `asistenciaSemana` |
| I.5 Adaptación a roles | **SÓLIDO** | `DashboardController@index` según rol; sidebar por permisos |

- El seguimiento (horas acumuladas/restantes, %, estado) está bien presentado en ambos dashboards y sale de un único servicio, por lo que no puede divergir entre vistas.
- Implicación de B.3: los indicadores del día solo cuentan asistió/tardanza/falto — los registros `pendiente` (como el de hoy) no aparecen en ningún indicador.

---

## 4. MATRIZ GENERAL DE FUNCIONALIDADES Y ESTADOS

| # | Módulo / funcionalidad | Estado |
|---|---|---|
| A.1 | Registro de practicantes | TIBIO |
| A.2 | Edición de información | TIBIO |
| A.3 | Activación / desactivación | SÓLIDO |
| A.4 | Asignación de roles y permisos | TIBIO |
| A.5 | Validación de datos de usuario | TIBIO |
| A.6 | Relación con áreas | SÓLIDO |
| A.7 | Asociación de cuenta Discord | TIBIO |
| B.1 | Registro inicial de asistencia | SÓLIDO |
| B.2 | Validación del horario (corte 13:00) | FALTANTE |
| B.3 | Clasificación asistió / tardanza | FALTANTE |
| B.4 | Faltas automáticas | FALTANTE |
| B.5 | Integración Discord (presencia) | SÓLIDO * |
| B.6 | Detección conexiones/desconexiones | SÓLIDO |
| B.7 | Acumulación de horas | SÓLIDO |
| B.8 | Restricción horario 08:00–13:00 | SÓLIDO |
| B.9 | Evitar registros duplicados | SÓLIDO |
| B.10 | Desconexiones inesperadas | TIBIO |
| B.11 | Manejo de errores del bot | TIBIO |
| B.12 | Registro previo obligatorio | SÓLIDO |
| C.1–C.8 | Evidencias y justificaciones (8 ítems) | SÓLIDO (×8) |
| D.1 | Horas acumuladas | SÓLIDO |
| D.2 | Horas restantes | SÓLIDO |
| D.3 | Porcentaje de cumplimiento | SÓLIDO |
| D.4 | Fechas inicio / fin | TIBIO |
| D.5 | Estado de prácticas | TIBIO |
| D.6 | Consistencia de cálculos | SÓLIDO |
| D.7 | Presentación admin / practicante | SÓLIDO |
| E.1 | Reporte de asistencias | SÓLIDO |
| E.2 | Reporte de evidencias + filtros | TIBIO * |
| E.3 | Exportación PDF/Excel evidencias | TIBIO * |
| E.4 | Consistencia con BD | SÓLIDO |
| F.1 | Validación de horas requeridas | SÓLIDO |
| F.2 | Cálculo de horas | SÓLIDO |
| F.3 | Datos del practicante | SÓLIDO |
| F.4 | Fechas de prácticas | TIBIO |
| F.5 | Generación del documento | TIBIO * |
| F.6 | Restricción para no cumplidos | SÓLIDO |
| G.1–G.7 | Auditoría administrativa (7 ítems) | SÓLIDO (×7) |
| H.1–H.7 | Notificaciones (7 ítems) | SÓLIDO (×7) |
| I.1–I.5 | Paneles de control (5 ítems) | SÓLIDO (×5) |

\* Sólidos en diseño/código pero con ejecución no verificada en esta auditoría (Discord de larga duración, reportes de evidencias, generación de certificado). Para la demo, conviene probarlos una vez antes de presentarlos.

**Total: 63 requisitos — 47 SÓLIDO · 13 TIBIO · 3 FALTANTE**

---

## 5. FUNCIONALIDADES SÓLIDAS (47)

Destacan por valor de demostración:

1. **Ciclo completo de evidencias/justificaciones** (C.1–C.8) — probado en navegador con los 3 escenarios (falta definitiva, justificada, asistida).
2. **Acumulación de tiempo por Discord** (B.1, B.5–B.9, B.12) — con deduplicación por caché, bloqueo de fila, límites de horario y requiere registro previo.
3. **Auditoría administrativa** (G.1–G.7) — con datos reales de quién aprobó/rechazó qué y cuándo.
4. **Notificaciones administrativas** (H.1–H.7) — con contador, panel, marcado de leída y polling.
5. **Seguimiento: cálculo de horas/%/estado** (D.1–D.3, D.6–D.7) — idéntico en los 3 puntos donde se usa.
6. **Dashboards por rol** (I.1–I.5).
7. **Gestión básica de usuarios**: toggle de estado + middleware de inactivos + permisos por controlador (A.3, A.6).
8. **Reporte de asistencias** (E.1) y **bloqueo de certificados sin horas** (F.1, F.6).

## 6. FUNCIONALIDADES TIBIAS (13)

| # | Funcionalidad | Problema principal | Qué falta para sólida |
|---|---|---|---|
| A.1/A.5 | Registro + validación de usuarios | reglas contra rol `'practicing'` inexistente → required_if muertos; password sin min:8 en store | corregir `'practicing'`→`'practicante'`; min:8 |
| A.2 | Edición | no sincroniza rol; sin campos de seguimiento | `syncRoles`; añadir campos al form/validación |
| A.4 | Roles | no editable tras crear | `syncRoles` en update |
| A.7 | Discord | unicidad solo por BD (error genérico) | regla unique activa con mensaje claro |
| B.10 | Desconexiones inesperadas | si el bot muere en línea, el tiempo solo se cuenta al llegar el siguiente evento offline | aceptable documentar; opcionalmente cerrar por timeout |
| B.11 | Errores del bot | re-lanza sin log centralizado | try/catch con `Log::error` |
| D.4/D.5 | Fechas y estado de prácticas | campos ineditables → siempre null / en_curso | campos en formulario + validaciones |
| E.2/E.3 | Reporte evidencias + exportación | código completo pero sin verificación en ejecución | probar una vez con datos reales |
| F.4 | Fechas del certificado | usa min/max de asistencias | usar fecha_inicio/fin si existen |
| F.5 | Generación certificado | no ejecutado en esta auditoría | generar uno de prueba |

## 7. FUNCIONALIDADES FALTANTES (3)

| # | Funcionalidad | Evidencia | Impacto |
|---|---|---|---|
| B.2 | Corte de 13:00 para registrar asistencia | `TickController` líneas 46–51 comentadas | se puede marcar asistencia a cualquier hora |
| B.3 | Clasificación automática asistió/tardanza | ninguna asignación de estos estados fuera de la revisión manual del admin y el seeder | registros `pendiente` con tiempo acumulado quedan congelados; los indicadores no los ven |
| B.4 | Faltas automáticas | `routes/console.php` sin scheduler; ningún comando programado | los días sin registro nunca se marcan `falto` |

Estas tres son **una sola pieza**: el cierre automático de la jornada. Sin ella, la automatización de la asistencia queda dependiente del administrador. Es el único hueco grande que queda del alcance original.

## 8. FUNCIONALIDADES QUE NO NECESITAN MEJORAS (NO MEJORAR)

- **C.4/C.5** Visualización y almacenamiento de documentos (URLs de Storage funcionan; no agregar galerías ni previews sofisticados).
- **H.5** Polling de 15 s (no migrar a websockets/Echo; es suficiente y simple).
- **I.4** Gráfico semanal Chart.js (no agregar más gráficos sin requisito).
- **A.3** Toggle de activación (no implementar delete real de usuarios; la desactivación es la vía correcta).
- **B.9** Deduplicación con `Cache::add` (funciona; no cambiar a Redis ni tablas de seguimiento).
- **C.8** Historial de estados en el calendario (no agregar versionado de evidencias).

## 9. FUNCIONALIDADES NO VERIFICADAS

| Ítem | Motivo |
|---|---|
| Continuidad del bot Discord en larga duración (B.5) | El bot demostró funcionar hoy (datos reales en BD), pero su operación continua requiere proceso manual vivo; no se puede garantizar en esta auditoría |
| Reporte de evidencias en ejecución (E.2/E.3) | Código revisado completo; no se abrió la pestaña ni se exportó PDF/Excel en esta auditoría |
| Generación real de certificado (F.5) | Plantilla presente y código correcto; no se generó un docx de prueba |

Estos ítems no se computan como demostrados en los porcentajes de innovación/demanda de prueba (los E y F se computan como TIBIO/50 % por código completo; el bot se computa SÓLIDO porque su efecto ya está en BD de hoy, con la advertencia de operación).

## 10. ERRORES TÉCNICOS ENCONTRADOS

**Críticos**

1. **Validaciones muertas por rol incorrecto** (`EditUser.php`, `UserStoreRequest.php`): `'practicing'` no existe → `required_if` de `discord_id`/`area_id` nunca se aplica. Un practicante puede quedar sin Discord ID (y por tanto sin contabilización de tiempo) sin ningún aviso.
2. **Clasificación/cierre automático ausente** (B.2–B.4): comportamiento esperado del alcance (§1 del pliego) simplemente no implementado.
3. **Seguimiento inalimentable por UI**: `#[Fillable]` de `User` omite `horas_requeridas`; formularios y validaciones no incluyen `fecha_inicio`/`fecha_fin`/`horas_requeridas` (verificado en vistas `create`/`edit`).

**Altos**

4. **Navbar rota**: `auth()->user()->name` (la columna es `nombre`) → nombre e inicial vacíos en todas las páginas (la deprecación `mb_substr(null, …)` ya aparece en logs al renderizar).
5. **`update()` no sincroniza roles**: cambiar el rol en edición es un no-op silencioso.
6. **Sin índice único `asistencias(usuario_id, fecha)`** (verificado con `SHOW INDEX`): solo existe el FK. `firstOrCreate` + carreras de eventos podría crear dos asistencias del mismo día.
7. **Token de Discord real en `.env`** comprometido en el repositorio (visible en el diff de git). Debería rotarse y `.env.example` mantenerse sin token.
8. **Sin pruebas automatizadas** (solo `ExampleTest`): ningún comportamiento del sistema está protegido contra regresiones.

**Medios**

9. `AuditoriaController@data`: resuelve practicante con cargas perezosas por fila (`$a->subject->usuario`, `$a->subject->asistencia->usuario`) → N+1 al crecer el historial (hoy: 7 filas, sin impacto real).
10. `destroy()` vacío y `UserService` sin uso (código muerto); `index`/`data` de JustificacionController comentados en bloque.
11. Constantes `HORA_INICIO`/`HORA_FIN` sin usar y límites 08:00/13:00 repetidos a mano en dos métodos (`AttendanceDiscordService`).
12. Mensajes de error exponen `$e->getMessage()` al usuario en UserController (store/update).
13. Carpeta de disco duplicada: `public/justificacion` y `public/justificaciones` conviven.
14. Certificado: `File::ensureDirectoryExists` + descarga temporal es correcto, pero los campos opcionales (dni/carrera/institución/semestre) no se capturan desde la UI.

**Bajos**

15. `AsistenciaJustificacionSeeder` escribe en `public/justificaciones` mientras otro código pudo escribir en `public/justificacion` (origen de la duplicación del punto 13).
16. `EditUser`/vista edit: `x-input-error for="name"` y `for="area"` con nombres que no coinciden con los campos reales.
17. Zona horaria: correcta (`app.timezone = America/Lima`, verificado); `DashboardService` pasa `'America/Lima'` explícito redundante pero inofensivo.

## 11. EVALUACIÓN DE LAS MEJORAS DE INNOVACIÓN

¿Resuelve PractiControl el problema de CRUD AGENCY? **Sí, con una condición.** El registro manual desapareció (Discord + tick), la permanencia es objetiva (presencia + evidencia validada), la acumulación de horas es automática y confiable, y el trabajo administrativo (revisar, auditar, notificarse) está cubierto. La condición: falta el **cierre automático de jornada** para que el sistema se sostenga solo.

| Mejora | ¿Aporta valor real? | Estado | Comentario honesto |
|---|---|---|---|
| 1. Seguimiento de prácticas | **Sí** — es la que más se nota en demo (horas, %, estado en ambos dashboards) | TIBIO (70 % de lo esperado: cálculos 100 %, alimentación de datos 0 %) | Sin campos editables, las fechas y el estado vencido no se demuestran |
| 2. Reporte de evidencias | **Sí** — historial filtrable + PDF/Excel es exactamente lo que un área de RR.HH. pide | TIBIO (código completo, sin prueba de ejecución) | Solo falta correrlo una vez y corregir lo que aparezca |
| 3. Certificados | **Sí** — cierre natural del ciclo, con bloqueo honesto de los no cumplidos | TIBIO (código completo, plantilla existe, sin prueba de ejecución) | Falta probar generación y capturar dni/carrera desde la UI |
| 4. Auditoría administrativa | **Sí** — trazabilidad completa de quién aprobó/rechazó | SÓLIDO | El mejor implementado de los 5 |
| 5. Notificaciones administrativas | **Sí** — el admin se entera al instante de cada envío | SÓLIDO | Campana visible a practicantes es detalle cosmético |

**Mejoras que agregan complejidad sin valor (no confundir cantidad con innovación):** ninguna de las 5 implementadas es sobre-ingeniería — están bien dimensionadas. El riesgo de sobre-ingeniería está en las tentaciones futuras (voice states de Discord, websockets, email), que se listan en §14.

## 12. PORCENTAJE REAL DE AVANCE

**Fórmula:** cada requisito puntúa SÓLIDO=100, TIBIO=50, FALTANTE=0; los NO VERIFICADO en ejecución se computan a 50 si el código está completo y revisado (E.2, E.3, F.5) y se declaran explícitamente como no demostrados. `% = Σ(puntajes) / nº requisitos`.

**Avance funcional (63 requisitos del §4):**
47 SÓLIDO × 100 + 13 TIBIO × 50 + 3 FALTANTE × 0 = 5 350 / 6 300 → **84.9 %**

**Avance de innovación (5 mejoras del pliego):**
Auditoría 100 + Notificaciones 100 + Seguimiento 50 + Reporte evidencias 50 + Certificados 50 = 350 / 500 → **70.0 %**

**Avance técnico (15 criterios, §10 como base):**
SÓLIDO (6): transacciones, seguridad de rutas/permisos, middleware, protección de sesión/login, fechas/zona horaria, integridad de estados de revisión.
TIBIO (8): arquitectura (restos muertos), excepciones, N+1, validaciones servidor, exposición de información (token), protección de archivos de evidencia (quedan públicamente servibles vía /storage — limitación aceptable del alcance local), rendimiento, configuración de producción.
FALTANTE (1): pruebas automatizadas.
= (600 + 400) / 1 500 → **66.7 %**

**Avance global ponderado:** 0.5 × 84.9 + 0.3 × 70.0 + 0.2 × 66.7 ≈ **76.8 %**
(pesos: funcional 50 %, innovación 30 %, técnico 20 % — el objetivo del proyecto es demostrar funcionalidad e innovación, no exhaustividad técnica)

**Lectura honesta:** el 84.9 % funcional es real y demostrable, pero ~10 puntos de ese número dependen del administrador haciendo manualmente lo que el alcance dice que el sistema debe hacer solo (clasificación, faltas, corte de jornada). El techo natural de la demo actual es ~85 %; superar el 90 % exige únicamente las 3 funcionalidades faltantes de B y los 3 hallazgos críticos de §10.

## 13. MEJORAS RECOMENDADAS Y PRIORIZADAS

### A. MEJORAS NECESARIAS

| # | Mejora | Problema que soluciona | Beneficio | Complejidad | Prioridad | Archivos |
|---|---|---|---|---|---|---|
| 1 | Cierre de jornada: descomentar cutoff 13:00 + scheduler (faltas automáticas + clasificación asistio/tardanza de pendientes con tiempo) | Los 3 FALTANTE de B; los pendientes congelados y días sin registro | El sistema se sostiene solo: sin esta pieza la demo tiene un agujero visible | **Media** | **Crítica** | `TickController`, `routes/console.php`, nuevo comando o `Schedule` |
| 2 | Campos de seguimiento en usuarios: añadir `fecha_inicio`, `fecha_fin`, `horas_requeridas` a create/edit + validaciones + `#[Fillable]` + corregir `'practicing'`→`'practicante'` + `syncRoles` en update | Seguimiento/D.4-D.5 y hallazgos críticos 1 y 3 | La mejora #1 de innovación se puede configurar y se puede demostrar "vencido" | **Baja** | **Crítica** | `EditUser`, `UserStoreRequest`, `admin/users/create|edit.blade.php`, `User.php`, `UserController` |
| 3 | Navbar: `name` → `nombre` (2 líneas) | Nombre del usuario vacío en todo el sistema (error visible en demo) | Pulir la presentación | **Mínima** | **Crítica** | `layouts/includes/navbar.blade.php` |
| 4 | Campana de notificaciones solo para admins | Practicantes ven una campana que nunca suena | Coherencia de UI | **Mínima** | Alta | `navbar.blade.php` (+ `@can` o rol) |
| 5 | Índice único `asistencias(usuario_id, fecha)` | Posibles asistencias duplicadas por carrera de eventos | Integridad de datos | **Mínima** | Alta | migración nueva |
| 6 | Pruebas Pest mínimas (login, tick, revisión evidencia, seguimiento, certificado bloqueado) | 0 % de cobertura; regresiones frecuentes (ya hubo un bug de template esta semana) | Estabilidad para seguir iterando con seguridad | **Media** | Alta | `tests/Feature/*` |

### B. MEJORAS RECOMENDADAS

| # | Mejora | Problema que soluciona | Beneficio | Complejidad | Prioridad | Archivos |
|---|---|---|---|---|---|---|
| 7 | Probar en ejecución reporte de evidencias (PDF/Excel) y certificado | E.2/E.3/F.5 sin demostrar | Cierra la demostración de 2 mejoras de innovación | **Mínima** | Alta | (solo pruebas manuales) |
| 8 | Formulario/parámetros para dni, carrera, institución, semestre al generar certificado | Campos opcionales quedan en `____` | Certificado presentable de verdad | **Baja** | Media | `getCertificate`, `admin/users/actions` |
| 9 | Usar `fecha_inicio/fin` del usuario en el certificado cuando existan | Fechas más precisas | Consistencia con seguimiento | **Mínima** | Media | `getCertificate` |
| 10 | `Log::error` en el catch de `AttendanceDiscordService` y en el bot | Errores del bot silenciosos | Diagnóstico rápido | **Mínima** | Media | `AttendanceDiscordService`, `DiscordBotCommand` |
| 11 | Constantes de horario (8/13) usadas de verdad o config `services.discord.jornada` | Límites duplicados a mano | Cambiar horario en un solo sitio | **Mínima** | Media | `AttendanceDiscordService` |
| 12 | Quitar `UserService`, el bloque comentado de JustificacionController, y unificar carpeta `justificacion`/`justificaciones` | Código muerto y disco inconsistente | Mantenibilidad | **Mínima** | Baja | 3 archivos |
| 13 | No exponer `$e->getMessage()` al usuario (loggear y mensaje genérico) | Información técnica al cliente | Seguridad/UX | **Mínima** | Baja | `UserController` |
| 14 | N+1 en `AuditoriaController@data` (cargar usuario en eager) | Consultas por fila | Rendimiento futuro | **Mínima** | Baja | `AuditoriaController` |

### C. NO IMPLEMENTAR

- **Detección de canales de voz de Discord**: la presencia ya resuelve el objetivo; los voice states añaden intents, gestión de estados parciales y casos raros. Complejidad alta, valor nulo para la demo.
- **Notificaciones por email** (canal mail del `MailMessage` comentado): el canal database + polling cumple; email exige configurar SMTP y revisión de bandeja.
- **Websockets / Laravel Echo / tiempo real**: el polling de 15 s es suficiente y estable.
- **Firma digital, QR o validación oficial de certificados**: fuera del alcance académico/empresarial del proyecto.
- **API REST pública / versión de API**: no hay consumidores externos; las rutas web + DataTables son suficientes.
- **Delete real de usuarios con cascada**: rompería asistencias/evidencias; la desactivación ya cubre el caso.
- **Recuperación de contraseña por email / verificación de correo**: para un sistema interno de demostración no agrega valor demostrable.
- **Edición o borrado del historial de auditoría**: contradice el propósito del módulo; nunca.

---

## 14. CONCLUSIÓN GENERAL

PractiControl está en su mejor momento del proyecto: **los 5 módulos de innovación existen y funcionan**, el ciclo de asistencia↔evidencia↔auditoría↔notificación es real (con datos en BD que lo prueban) y la acumulación de horas por Discord es sólida. El avance global ≈ **76.8 %**, con **84.9 %** funcional, refleja fielmente esa realidad — sin inflar.

La brecha para una demo redonda es pequeña y concreta:

1. **Cerrar la jornada automáticamente** (cutoff 13:00, faltas automáticas, clasificación asistio/tardanza) — la única pieza FALTANTE del alcance y la que separa "sistema con ayuda del admin" de "sistema automatizado".
2. **Alimentar el seguimiento desde la UI** (fecha_inicio, fecha_fin, horas_requeridas) — sin esto, la mejora estrella de innovación no se puede configurar ni mostrar vencida.
3. **Tres correcciones de minutos**: navbar `nombre`, rol `'practicing'`→`'practicante'`, `syncRoles` en update.
4. **Probar una vez** el reporte de evidencias y un certificado real, para poder demostrar las 5 mejoras sin asteriscos.

Nada de esto convierte al proyecto en un sistema empresarial: son correcciones puntuales sobre lo ya construido, coherentes con el objetivo de un sistema **sencillo, estable y demostrable** para CRUD AGENCY S.A.C. No se recomienda ninguna ampliación más allá de las listas §13-A y §13-B.

---

*Fin del informe. Generado sin modificar ningún archivo del proyecto.*
