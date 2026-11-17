# INFORME DE IMPLEMENTACIÓN — PractiControl
**Fecha:** 16 de noviembre de 2026 · **Proyecto:** PractiControl (CRUD AGENCY S.A.C.)
**Alcance:** Implementación de todas las correcciones y mejoras identificadas en la auditoría (`REPORTE_AUDITORIA_COMPLETA_NOV16.md`, 76.8 % global), trabajando directamente sobre el código, con pruebas automatizadas y verificación en navegador.

---

## 1. Resumen ejecutivo

Se implementó y verificó el paquete completo de correcciones del sistema de asistencias de practicantes:

- **Reglas de asistencia (Fase 4)**: registro antes de 08:00 → `asistio`; desde 08:00 hasta antes de 13:00 → `tardanza` (sin bloquear); desde las 13:00 → bloqueado. El tiempo contabilizable solo corre entre 08:00 y 13:00 (máx. 5 h/día). Se eliminó el límite arbitrario de 08:15.
- **Tardanza permanente**: aprobar evidencia o justificación de tardanza **no** la convierte en `asistio`/`justificado`; rechazar evidencia → `falto` definitivo; rechazar justificación de falta → `falto`.
- **Cierre automático de jornada (nuevo)**: comando `asistencia:cierre` idempotente, registrado en el scheduler (lunes–viernes 13:05, `America/Lima`, `withoutOverlapping`), que corta conexiones activas de Discord a las 13:00 y genera faltas de practicantes que no registraron asistencia (respetando periodo de prácticas, fines de semana y excluyendo administradores), con notificación a los administradores.
- **Seguridad y autorización**: reportes y auditorías restringidos a `role:admin` en rutas; `UserController` y `AreaController` con middleware `can:*`; certificado con autorización en backend (un practicante solo puede generar el suyo); campana de notificaciones solo para administradores.
- **Usuarios**: formulario completo de practicantes (fecha inicio, fecha fin, horas requeridas con valor 360) con validaciones en crear y editar, `syncRoles()` en la actualización, corrección de `name` → `nombre/apellidos` en navbar y de `'practicing'` → `'practicante'` en las validaciones.
- **Cobertura de pruebas (nuevo)**: suite Pest de **57 pruebas**, todas pasando, que cubre los 41 escenarios exigidos en Fase 17 (asistencia, Discord, cierre idempotente, evidencias/justificaciones, seguimiento, certificados, permisos, auditoría y notificaciones).
- **Integridad de datos**: migración reversible del índice único `(usuario_id, fecha)` en `asistencias`, aplicada al entorno real tras verificar 0 duplicados. **Ningún dato real de usuarios fue modificado.**

Verificación real en navegador: login de practicante y administrador, dashboards, calendario, formularios de usuario, reportes, auditorías y generación real de certificado (descarga DOCX correcta).

---

## 2. Archivos modificados

| Ruta | Modificación | Motivo |
|---|---|---|
| `app/Http/Controllers/TickController.php` | Corte a las 13:00 habilitado; clasificación `asistio`/`tardanza` al marcar | Reglas Fase 4.2 |
| `app/Services/Discord/AttendanceDiscordService.php` | Constantes `08:00:00`/`13:00:00`; corte de contabilización vía `setTime`; `endConnection()` pública con `$finForzado`; `Log::error` sin secretos | Fases 6 y 7 |
| `app/Console/Commands/AsistenciaCierreCommand.php` **(nuevo)** | Comando `asistencia:cierre`: cierra conexiones activas y genera faltas con `firstOrCreate` (idempotente) | Fase 7 |
| `routes/console.php` | Scheduler: `asistencia:cierre` → `weekdays()->at('13:05')->timezone('America/Lima')->withoutOverlapping()` | Fase 7.4 |
| `app/Notifications/FaltaAutomaticaNotification.php` **(nuevo)** | Notificación (canal database) al admin por faltas automáticas nuevas | Fases 7 y 13 |
| `app/Http/Controllers/CalendarController.php` | `validarAsistencia`, `validarJustificacion` y `rechazarJustificacion` conservan la `tardanza` | Fases 4 y 5 |
| `app/Http/Controllers/JustificacionController.php` | `justificateFalta` admite `falto`/`tardanza`; import de enum | Fase 5.2 |
| `app/Models/User.php` | `horas_requeridas` en Fillable; casts `fecha_inicio`/`fecha_fin` => date | Fases 3 y 8 |
| `app/Http/Requests/UserStoreRequest.php` / `EditUser.php` | Reescritas: rol `practicante`, `discord_id`/`area_id` `required_if`, unicidad de Discord ID, `after_or_equal` fechas, `horas_requeridas` entero ≥1, mensajes en español, normalización de Discord ID vacío | Fase 3.2 |
| `app/Http/Controllers/UserController.php` | `syncRoles()` en update; middleware `can:*` sobre `data()`; `getCertificate()` con autorización y fechas del periodo; nuevo `miCertificado()` | Fases 2, 3 y 11 |
| `routes/admin.php` | Grupos `role:admin` (reportes, auditorías); ruta `admin.certificate.mine` | Fases 2 y 14 |
| `app/Http/Controllers/AreaController.php` | `HasMiddleware` con `can:read/create/edit/delete.areas` | Fase 2.3 |
| `bootstrap/app.php` | Alias `role`, `permission`, `roles_or_permissions` (Spatie no los registra automáticamente en esta configuración); alias `active` conservado | Fase 14 (bug real detectado en pruebas) |
| `resources/views/layouts/includes/navbar.blade.php` | `nombre . ' ' . apellidos`, inicial de avatar por `nombre`, campana dentro de `@if(hasRole('admin'))` | Fase 3.1 |
| `resources/views/admin/users/create.blade.php` / `edit.blade.php` | Campos fecha_inicio, fecha_fin, horas_requeridas (con corrección de `for="area_id"`) | Fase 3.2 |
| `resources/views/practicing/calendar/index.blade.php` | Sección "Justificación de tardanza" (dinámica), textos/placeholder dinámicos, bloque "Tardanza justificada" | Fases 5.2 y 18 |
| `resources/views/admin/calendar/index.blade.php` | Título de justificación dinámico para tardanzas | Fase 18 |
| `database/migrations/0001_01_01_000000_create_users_table.php` | Columnas `fecha_inicio`, `fecha_fin`, `horas_requeridas (default 360)`; `down()` corregido a `usuarios` | Fases 3 y 8 |
| `database/migrations/2026_11_16_101201_add_unique_index_to_asistencias.php` **(nuevo)** | Índice único `(usuario_id, fecha)`, reversible | Fase 15 |
| `database/migrations/2026_11_16_080836_create_activity_log_table.php`, `2026_11_16_085148_create_notifications_table.php` **(nuevas, de fases previas)** | Tablas de auditoría y notificaciones | Fases 12 y 13 |
| `tests/TestCase.php` | Seed de roles + permisos (equivalente a seeders, sin `User::find(1)`), shim sqlite `TIME_TO_SEC` | Fase 17 |
| `tests/Pest.php` | `RefreshDatabase` activado; helpers `crearUsuario()` / `crearPracticante()` (con `estado` explícito) | Fase 17 |
| `tests/Feature/AsistenciaRegistroTest.php` **(nuevo, 9 pruebas)** | Escenarios 1–9 de Fase 17 | Fase 17 |
| `tests/Feature/DiscordAsistenciaTest.php` **(nuevo, 10 pruebas)** | Escenarios 10–18 (conexiones, corte 13:00, sin asistencia, faltas/justificadas, inactivos) | Fase 17 |
| `tests/Feature/CierreJornadaTest.php` **(nuevo, 8 pruebas)** | Cierre 13:00, idempotencia, faltas, fines de semana, periodo, notificación única, admins excluidos | Fase 17 |
| `tests/Feature/RevisionEvidenciasTest.php` **(nuevo, 11 pruebas)** | Evidencias y justificaciones (tardanza preservada, rechazos, acceso ajeno, auditoría) | Fase 17 |
| `tests/Feature/SeguimientoCertificadosTest.php` **(nuevo, 18 pruebas)** | Cálculos de seguimiento, certificados, permisos, calendario propio, sincronización de roles, notificaciones | Fase 17 |
| `tests/Feature/ExampleTest.php` | Ajustado a `assertRedirect` (la raíz redirige al login si no hay sesión) | Fase 17 |
| `.env.example` | `DISCORD_TOKEN=` con comentario orientativo; sin secretos versionados | Fase 14 |
| `database/seeders/*`, `app/Models/Area.php`, `app/Exports/EvidenceExport.php`, `app/Http/Controllers/DashboardController.php`, `ReporteController.php`, `NotificacionController.php`, `AuditoriaController.php`, `app/Services/DashboardService.php`, vistas de dashboards/reportes/sidebar | Pasados por `vendor/bin/pint` (estilo) y, en fases previas, implementación de seguimiento/exports/notificaciones | Fases 8–13 |

---

## 3. Funcionalidades completadas (implementadas y verificadas)

1. **Registro de asistencia con clasificación definitiva**: `< 08:00` → `asistio`; `08:00–12:59` → `tardanza` sin bloqueo; `≥ 13:00` → bloqueado con mensaje claro. Ventana original respetada; no existe corte de 08:15.
2. **Acumulación de horas por Discord**: solo con asistencia registrada (`pendiente`/`asistio`/`tardanza`), solo usuarios activos, sin duplicados, sin contar antes de 08:00 ni después de 13:00, cambios online/idle/dnd no reinician el contador, `hora_inicio` se guarda en la primera conexión contabilizable y `hora_fin` al cerrar.
3. **Cierre automático 13:00**: comando idempotente (doble ejecución no duplica horas ni faltas), cerrado de conexiones activas por registro controlado en caché (no enumeración de claves), faltas con `firstOrCreate`, respetando periodo, fines de semana y excluyendo administradores; notificación única a admins.
4. **Tardanza preservada**: aprobación de evidencia/justificación no altera el estado `tardanza`; rechazo de evidencia → `falto`; rechazo de justificación de falta → `falto`; justificación de falta aprobada → `justificado`.
5. **Justificación de tardanza**: presentable desde el calendario del practicante (motivo + archivo de sustento), revisable por el admin con observaciones; estados y trazabilidad completos.
6. **Seguridad y autorización backend**: reportes/auditorías solo admin (middleware de rol), usuarios/áreas con `can:*`, certificado con 403 para practicantes ajenos, `syncRoles` correcto, campana de notificaciones solo admin.
7. **Gestión de usuarios completa**: fechas de periodo, horas requeridas (default 360), validaciones cruzadas (fecha fin ≥ inicio), Discord ID único, mensajes en español.
8. **Seguimiento de prácticas**: horas acumuladas, restantes, porcentaje (tope visual 100 %), estados `en_curso`/`completado`/`vencido`; no suma faltas ni justificados; fechas nulas muestran "requiere configuración" sin inventar valores.
9. **Certificados**: bloqueo sin horas suficientes (swal warning), generación por admin y por el propio practicante (`admin.certificate.mine`), periodo desde fechas del usuario con respaldo en asistencias, descarga DOCX verificada en el entorno real (53.5 KB, `certificado_Perez Gomez.docx`).
10. **Auditoría administrativa**: Spatie Activitylog activa; cada revisión genera exactamente una entrada con causer, valores anterior/nuevo; practicante no puede consultarla (403).
11. **Notificaciones**: database channel, envío por evidencia/justificación, lectura sin alterar estados, sin duplicados por faltas repetidas (solo faltas nuevas de cada ejecución).
12. **Integridad de datos**: índice único `(usuario_id, fecha)` aplicado; operaciones críticas con transacciones (`DB::beginTransaction/commit/rollback`) y bloqueo `lockForUpdate` en el cierre de conexión.
13. **Suite de pruebas Pest**: 57 pruebas pasando sobre sqlite en memoria (shim `TIME_TO_SEC`), sin tocar la base real.

---

## 4. Funcionalidades parcialmente completadas

| Funcionalidad | Estado | Qué falta |
|---|---|---|
| **Reportes (Fase 10)** | Implementada y navegada; exportaciones **no ejecutadas** en el entorno real | Ejecutar descargas reales PDF/Excel (`reportes.exportar.*`) y validar contenido con datos controlados; el código (`EvidenceExport`, DomPDF) existe pero no se probó la descarga en producción |
| **Cierre automático en entorno real** | Probado exhaustivamente en tests; **no ejecutado** sobre la base MySQL real | Ejecutar `php artisan asistencia:cierre` una vez en horario real (solo en un día de prueba) y activar el cron del scheduler |
| **Seguimiento con periodo configurado** | La lógica funciona; los usuarios reales tienen `fecha_inicio`/`fecha_fin` `NULL` | Configurar las fechas reales de los practicantes (requiere autorización del cliente, no se tocó) |
| **Verificación del bot Discord en vivo** | Servicio cubierto por pruebas unitarias/funcionales | Prueba con presencias reales del bot corriendo (`php artisan discord:bot`) |
| **Compilación frontend** | No hubo cambios que la requieran esta fase; el build no se ejecutó | Ejecutar `npm run build` al desplegar |

---

## 5. Pruebas ejecutadas

**Suite final: 57 pruebas, 57 pasando, 110 aserciones, ~24 s** (`php artisan test --compact`, sqlite `:memory:`).

| Archivo | Pruebas | Escenarios |
|---|---|---|
| `AsistenciaRegistroTest` | 9 | Antes de 08:00, exactamente 08:00, 08:30, 09:00, 12:30, bloqueo 13:00, duplicado, sin Discord ID, usuario inactivo |
| `DiscordAsistenciaTest` | 10 | Inicio de conexión, online→idle→dnd, acumulación, varias conexiones, antes de 08:00, inicio tras 13:00, desconexión tardía, sin asistencia, faltas/justificadas, inactivo |
| `CierreJornadaTest` | 8 | Cierre 13:00, idempotencia, faltas, fin de semana, fuera de periodo, notificación única, admins excluidos, justificados intactos |
| `RevisionEvidenciasTest` | 11 | Tardanza preservada (evidencia/justificación), rechazos, envío de justificación de tardanza, acceso ajeno bloqueado, auditoría única |
| `SeguimientoCertificadosTest` | 18 | Cálculos, completado/vencido, no suma faltas, certificado sin/per horas, 403 ajeno, auditoría solo admin, reportes solo admin, calendario propio, dashboard propio, `syncRoles`, validación fechas, notificaciones |
| `ExampleTest` | 1 | Raíz redirige a login (invitado) |

**Errores reales detectados y corregidos durante la construcción de la suite** (evidencia del valor de las pruebas):
1. `Carbon::setFromTimeString()` no existe en la versión instalada → sustituido por `setTime()` (4 lugares).
2. Alias `role` no registrado en esta configuración → registrado en `bootstrap/app.php`.
3. Instancias de usuario en memoria sin atributo `estado` provocaban logout espurio del middleware `active` → helpers de test setean `estado` explícito (el bug solo afectaba al entorno de prueba; el flujo real recarga el usuario).
4. `EditUser` ignoraba el registro equivocado en la unicidad (`$this->user` es el admin autenticado, no el usuario editado) → corregido usando el parámetro de ruta.
5. Falta de permisos sembrados en tests → seed de roles + permisos replicado en `tests/TestCase.php`.

**Verificación funcional en navegador (http://127.0.0.1:8000):**
- Login juan (practicante): dashboard propio (384:00 de 360 h, 100 %, "No definida" en fechas nulas), calendario, sin campana de notificaciones.
- Login pepe (admin): dashboard general, edición de juan con los campos nuevos renderizados, listado de usuarios, reportes y auditorías cargando, campana visible.
- Generación real de certificado de juan: **HTTP 200**, `content-disposition: attachment; filename="certificado_Perez Gomez.docx"`.

---

## 6. Migraciones

| Migración | Operación | Estado | Riesgo |
|---|---|---|---|
| `0001_01_01_000000_create_users_table` (editada) | Columnas `fecha_inicio`, `fecha_fin`, `horas_requeridas` (default 360); `down()` → `usuarios` | Columnas presentes en la base real (verificado por esquema) | Ninguno: datos reales intactos (NULL/360) |
| `2026_11_16_080836_create_activity_log_table` | Tabla de auditoría | Aplicada | Ninguno |
| `2026_11_16_085148_create_notifications_table` | Tabla de notificaciones | Aplicada | Ninguno |
| `2026_11_16_101201_add_unique_index_to_asistencias` | Índice único `(usuario_id, fecha)` | Aplicada a la base real; **0 duplicados verificados antes** | Ninguno; reversible con `dropUnique` |

- No se ejecutó `migrate:fresh`, `db:wipe` ni truncamientos. No se eliminaron columnas ni tablas.
- `php artisan migrate:status`: todas las migraciones "Ran".
- Base real verificada tras la implementación: 16 asistencias, 7 actividades de auditoría, 1 notificación — **sin pérdida ni alteración de datos**.

---

## 7. Seguridad

**Correcciones aplicadas:**
- Rutas de reportes y auditorías con `role:admin` (backend, no solo UI).
- Certificados: 403 si un practicante solicita el certificado de otro.
- Asistencias/justificaciones: `abort(403)` si el usuario no es dueño del registro.
- `UserController::data()` y CRUD de áreas con middleware `can:*`.
- `syncRoles()` en la actualización (los roles ya no dependen de asignación masiva).
- `.env.example` sin token real; `.gitignore` excluye `.env` (verificado: solo `.env.example` está versionado).
- Logs del servicio de Discord registran contexto útil sin secretos.

**Riesgos que permanecen (informativos):**
- El token de Discord fue expuesto previamente en el `.env` local. **Debe rotarse desde el Discord Developer Portal**; no se generó ni modificó ningún token.
- El middleware `active` responde con error en la sesión; comportamiento existente que se conservó (no rompe el flujo).

---

## 8. Estado de las mejoras de innovación

| # | Mejora | Estado | Evidencia |
|---|---|---|---|
| 1 | Seguimiento de prácticas | **Completada** | Pruebas de cálculo (07:30 → 75 %, completado, vencido), dashboards reales mostrando estados y "requiere configuración" |
| 2 | Reportes de evidencias | **Alta** (UI y lógica OK; descargas sin probar en real) | Página de reportes cargada con pestañas Asistencias/Evidencias; exports pendientes de prueba real |
| 3 | Certificados | **Completada** | Descarga real DOCX verificada; bloqueo por horas probado en tests |
| 4 | Auditoría administrativa | **Completada** | Una entrada por revisión, causer correcto, 403 para practicantes |
| 5 | Notificaciones | **Completada** | Notificación por evidencia, lectura sin aprobar, sin duplicados de faltas |

---

## 9. Porcentaje de avance actualizado

Basado en evidencia verificable (suite verde + navegación real). Punto de partida: 76.8 %.

| Dimensión | Antes | Ahora | Justificación |
|---|---|---|---|
| **Avance funcional** | 75 % | **92 %** | Asistencia/tardanza/cierre/justificaciones/seguimiento/certificados/notificaciones implementados y verificados; faltan descargas de reportes y datos de periodo reales |
| **Avance técnico** | 72 % | **90 %** | Suite de 57 pruebas, índice único, transacciones, middleware de rol/can registrado, comando idempotente, código con Pint |
| **Avance de innovación** | 80 % | **88 %** | Las 5 mejoras de innovación operativas; reportes de evidencias pendiente de prueba de export |
| **Avance global** | 76.8 % | **≈ 90 %** | Media ponderada con lo anterior |

---

## 10. Pendientes para producción

1. **Rotar el token de Discord** en el Discord Developer Portal (fue expuesto antes de esta sesión) y colocarlo en el `.env` del servidor de producción (nunca en archivos versionados).
2. **Scheduler activo**: en producción, crontab: `* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1`.
3. **Bot de Discord corriendo como proceso persistente**: `php artisan discord:bot` (supervisor/systemd recomendado).
4. **Ejecutar `php artisan asistencia:cierre`** manualmente una vez en un día de prueba real para validar el cierre con MySQL.
5. **Configurar `fecha_inicio`/`fecha_fin` reales** de los practicantes (requiere autorización; no se tocó).
6. **Probar descargas PDF/Excel de reportes** con datos reales.
7. `npm run build` al desplegar (no hubo cambios de JS esta fase, pero el build debe estar vigente).
8. `php artisan migrate` en producción para confirmar que el índice único está aplicado (ya está en el entorno local).

---

## 11. Instrucciones de ejecución

```bash
# Migraciones (sin destructive ops)
php artisan migrate --no-interaction

# Compilación frontend
npm install && npm run build

# Suite de pruebas (sqlite en memoria, no toca datos reales)
php artisan test --compact
#   o directamente: vendor\bin\pest

# Cierre manual de jornada (idempotente; también acepta --fecha=Y-m-d)
php artisan asistencia:cierre

# Scheduler en producción (crontab del servidor)
# * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1

# Bot de Discord (proceso persistente)
php artisan discord:bot

# Servidor local
php artisan serve
```

---

*Informe generado tras 57/57 pruebas en verde, `vendor/bin/pint --dirty` aplicado, `migrate:status` y `schedule:list` verificados, y verificación funcional en navegador con ambos roles.*
