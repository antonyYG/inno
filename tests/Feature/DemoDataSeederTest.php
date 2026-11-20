<?php

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Services\CalendarService;
use App\Services\DashboardService;
use Database\Seeders\DemoAreaSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Suma exacta utilizada por DashboardService y UserController::getCertificate().
 */
function segundosAcumuladosDemo(User $usuario): int
{
    return (int) Asistencia::where('usuario_id', $usuario->id)
        ->whereIn('tipo', ['asistio', 'tardanza'])
        ->whereNotNull('tiempo_trabajado')
        ->sum(DB::raw('TIME_TO_SEC(tiempo_trabajado)'));
}

test('las cuatro cuentas demo inician sesion con sus credenciales', function () {
    $this->seed(DemoDataSeeder::class);

    $credenciales = [
        'pepe@gmail.com',
        'practicante@practicontrol.test',
        'carlos.ramirez@practicontrol.test',
        'juan.perez@practicontrol.test',
        'maria.torres@practicontrol.test',
    ];

    foreach ($credenciales as $email) {
        $usuario = User::where('email', $email)->firstOrFail();

        $this->post(route('login.submit'), [
            'email' => $email,
            'password' => '12345678',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($usuario);

        $this->post(route('logout'))->assertRedirect('/login');
    }
});

test('crea las cuentas demo y sus roles sin duplicarlos al repetirse', function () {
    $this->seed(DemoDataSeeder::class);

    $admin = User::where('email', 'pepe@gmail.com')->firstOrFail();
    $practicante = User::where('email', 'practicante@practicontrol.test')->firstOrFail();
    $carlos = User::where('email', 'carlos.ramirez@practicontrol.test')->firstOrFail();
    $juan = User::where('email', 'juan.perez@practicontrol.test')->firstOrFail();
    $maria = User::where('email', 'maria.torres@practicontrol.test')->firstOrFail();

    $emails = [
        'pepe@gmail.com',
        'practicante@practicontrol.test',
        'carlos.ramirez@practicontrol.test',
        'juan.perez@practicontrol.test',
        'maria.torres@practicontrol.test',
    ];

    expect($admin->nombre)->toBe('Administrador Demo')
        ->and(Hash::check('12345678', $admin->password))->toBeTrue()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($practicante->nombre)->toBe('Juan')
        ->and($practicante->apellidos)->toBe('Pérez Demo')
        ->and($practicante->discord_id)->toBe('demo-practicante-0001')
        ->and($carlos->nombre)->toBe('Carlos')
        ->and($carlos->apellidos)->toBe('Ramírez Demo')
        ->and($carlos->area?->nombre)->toBe('Desarrollo web')
        ->and($carlos->discord_id)->toBe('demo-carlos-0001')
        ->and($carlos->fecha_inicio->toDateString())->toBe('2026-08-10')
        ->and($carlos->fecha_fin->toDateString())->toBe('2026-11-17')
        ->and($juan->nombre)->toBe('Juan')
        ->and($juan->apellidos)->toBe('Pérez Demo')
        ->and($juan->email)->toBe('juan.perez@practicontrol.test')
        ->and($juan->area?->nombre)->toBe('Desarrollo web')
        ->and($juan->discord_id)->toBe('demo-juan-0001')
        ->and($juan->fecha_inicio->toDateString())->toBe('2026-09-10')
        ->and($maria->nombre)->toBe('María')
        ->and($maria->apellidos)->toBe('Torres Demo')
        ->and($maria->area?->nombre)->toBe('Diseño gráfico')
        ->and($maria->discord_id)->toBe('demo-maria-0001')
        ->and($maria->fecha_inicio->toDateString())->toBe('2026-10-23')
        ->and($maria->fecha_fin->toDateString())->toBe('2026-11-17');

    foreach ([$practicante, $carlos, $juan, $maria] as $usuario) {
        expect(Hash::check('12345678', $usuario->password))->toBeTrue()
            ->and($usuario->hasRole('practicante'))->toBeTrue()
            ->and($usuario->estado)->toBeTrue()
            ->and($usuario->horas_requeridas)->toBe(360);
    }

    $this->seed(DemoDataSeeder::class);

    expect(User::count())->toBe(5)
        ->and(Area::count())->toBe(5)
        ->and(Area::whereIn('nombre', DemoAreaSeeder::nombres())->count())->toBe(5)
        ->and(User::whereIn('email', $emails)->count())->toBe(5)
        ->and($admin->fresh()->hasRole('admin'))->toBeTrue()
        ->and($carlos->fresh()->hasRole('practicante'))->toBeTrue();
});

test('el catálogo demo tiene exactamente cinco áreas sin duplicados', function () {
    $esperadas = [
        'Base de datos',
        'Desarrollo móvil',
        'Desarrollo web',
        'Diseño gráfico',
        'Diseño web',
    ];

    $this->seed(DemoDataSeeder::class);

    expect(Area::query()->pluck('nombre')->sort()->values()->all())->toBe($esperadas)
        ->and(Area::count())->toBe(5)
        ->and(Area::distinct('nombre')->count('nombre'))->toBe(5);

    $this->seed(DemoAreaSeeder::class);

    expect(Area::query()->pluck('nombre')->sort()->values()->all())->toBe($esperadas)
        ->and(Area::count())->toBe(5);
});

test('reasigna los usuarios de áreas obsoletas antes de eliminarlas', function () {
    $this->seed(DemoAreaSeeder::class);

    $areaObsoleta = Area::create(['nombre' => 'Sistemas']);
    $usuario = User::factory()->create(['estado' => true, 'area_id' => $areaObsoleta->id]);

    $this->seed(DemoAreaSeeder::class);

    expect(Area::find($areaObsoleta->id))->toBeNull()
        ->and($usuario->fresh()->area?->nombre)->toBe('Desarrollo web')
        ->and(Area::count())->toBe(5)
        ->and(User::find($usuario->id))->not->toBeNull();
});

test('genera asistencias, evidencias y justificaciones coherentes sin duplicados', function () {
    $this->seed(DemoDataSeeder::class);

    expect(Asistencia::count())->toBe(151)
        ->and(Asistencia::select('usuario_id', 'fecha')->distinct()->count())->toBe(151)
        ->and(Justificacion::count())->toBe(150);

    $porUsuario = User::whereIn('email', [
        'practicante@practicontrol.test',
        'carlos.ramirez@practicontrol.test',
        'juan.perez@practicontrol.test',
        'maria.torres@practicontrol.test',
    ])->get()->keyBy('email');

    expect(Asistencia::where('usuario_id', $porUsuario['practicante@practicontrol.test']->id)->count())->toBe(12)
        ->and(Asistencia::where('usuario_id', $porUsuario['carlos.ramirez@practicontrol.test']->id)->count())->toBe(72)
        ->and(Asistencia::where('usuario_id', $porUsuario['juan.perez@practicontrol.test']->id)->count())->toBe(49)
        ->and(Asistencia::where('usuario_id', $porUsuario['maria.torres@practicontrol.test']->id)->count())->toBe(18);

    foreach ($porUsuario as $usuario) {
        $fechas = Asistencia::where('usuario_id', $usuario->id)->pluck('fecha');

        expect($fechas->every(fn (string $fecha) => (int) date('N', strtotime($fecha)) <= 5))->toBeTrue()
            ->and($fechas->max())->toBe('2026-11-17');
    }

    foreach (['evidencia', 'justificacion'] as $tipo) {
        foreach (['pendiente', 'aprobada', 'rechazada'] as $estado) {
            expect(Justificacion::where('tipo', $tipo)->where('estado', $estado)->count())->toBeGreaterThan(0);
        }
    }

    $archivos = Justificacion::pluck('archivo_path');

    expect($archivos)->toHaveCount(150)
        ->and($archivos->every(fn (string $ruta) => Storage::disk('public')->exists($ruta)))->toBeTrue();

    $this->seed(DemoDataSeeder::class);

    expect(Asistencia::count())->toBe(151)
        ->and(Justificacion::count())->toBe(150)
        ->and(collect(Storage::disk('public')->allFiles('evidencias'))->filter(
            fn (string $ruta) => str_starts_with(basename($ruta), 'demo_')
        )->count())->toBe(18);
});

test('Carlos acumula exactamente 360 horas con la logica real del sistema', function () {
    $this->seed(DemoDataSeeder::class);

    $carlos = User::where('email', 'carlos.ramirez@practicontrol.test')->firstOrFail();
    $segundos = segundosAcumuladosDemo($carlos);

    expect($segundos)->toBe(1296000)
        ->and(intdiv($segundos, 3600))->toBe(360)
        ->and($segundos)->toBe($carlos->horas_requeridas * 3600)
        ->and(Asistencia::where('usuario_id', $carlos->id)
            ->whereIn('tipo', ['asistio', 'tardanza'])
            ->where('tiempo_trabajado', '05:00:00')->count())->toBe(72);

    $this->actingAs($carlos);

    $dashboard = app(DashboardService::class)->getDashboardPracticante();

    expect($dashboard['seguimiento']['horas_acumuladas'])->toBe('360:00')
        ->and($dashboard['seguimiento']['porcentaje'])->toBeFloat(100.0)
        ->and($dashboard['seguimiento']['estado_seguimiento'])->toBe('completado')
        ->and(app(CalendarService::class)->getAttendances()->count())->toBe(72);
});

test('Juan acumula 245 horas y no puede generar certificado', function () {
    $this->seed(DemoDataSeeder::class);

    $juan = User::where('email', 'juan.perez@practicontrol.test')->firstOrFail();
    $segundos = segundosAcumuladosDemo($juan);

    expect($segundos)->toBe(882000)
        ->and(intdiv($segundos, 3600))->toBe(245)
        ->and($segundos)->toBeLessThan($juan->horas_requeridas * 3600);

    $this->actingAs($juan)
        ->get(route('admin.certificate.mine'))
        ->assertRedirect()
        ->assertSessionHas('swal.icon', 'warning');

    $admin = User::where('email', 'pepe@gmail.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.certificate.generate', $juan))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('swal.icon', 'warning');
});

test('un administrador genera y descarga realmente el certificado de Carlos', function () {
    $this->seed(DemoDataSeeder::class);

    $admin = User::where('email', 'pepe@gmail.com')->firstOrFail();
    $carlos = User::where('email', 'carlos.ramirez@practicontrol.test')->firstOrFail();

    $response = $this->actingAs($admin)
        ->get(route('admin.certificate.generate', $carlos));

    $response->assertOk()->assertDownload();

    $disposicion = strtolower((string) $response->headers->get('content-disposition'));

    expect($disposicion)->toContain('attachment')
        ->and($disposicion)->toContain('.docx');

    $ruta = storage_path('app/temp/certificado_'.$carlos->id.'.docx');

    try {
        expect(file_exists($ruta))->toBeTrue();

        $zip = new ZipArchive;

        expect($zip->open($ruta))->toBeTrue();

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        expect($xml)->toContain('360 horas')
            ->and($xml)->toContain('Desarrollo web');
    } finally {
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
});

test('Maria muestra incidencias, justificaciones y actividades diarias', function () {
    $this->seed(DemoDataSeeder::class);

    $maria = User::where('email', 'maria.torres@practicontrol.test')->firstOrFail();
    $asistencias = Asistencia::where('usuario_id', $maria->id)->get();
    $tipos = $asistencias->countBy(fn (Asistencia $asistencia) => $asistencia->tipo->value);

    expect($tipos['asistio'])->toBe(8)
        ->and($tipos['tardanza'])->toBe(4)
        ->and($tipos['falto'])->toBe(3)
        ->and($tipos['pendiente'])->toBe(2)
        ->and($tipos['justificado'])->toBe(1)
        ->and(segundosAcumuladosDemo($maria))->toBe(208200);

    $justificaciones = Justificacion::whereHas(
        'asistencia',
        fn ($query) => $query->where('usuario_id', $maria->id)
    )->get();

    expect($justificaciones->where('tipo', 'evidencia')->count())->toBe(11)
        ->and($justificaciones->where('tipo', 'justificacion')->count())->toBe(6)
        ->and($justificaciones->where('estado', 'pendiente')->count())->toBeGreaterThan(0)
        ->and($justificaciones->where('estado', 'aprobada')->count())->toBeGreaterThan(0)
        ->and($justificaciones->where('estado', 'rechazada')->count())->toBeGreaterThan(0);
});

test('puebla notificaciones y auditoria sin duplicarlas al repetirse', function () {
    $this->seed(DemoDataSeeder::class);

    expect(DB::table('notifications')->count())->toBe(6)
        ->and(DB::table('activity_log')->count())->toBe(10)
        ->and(DB::table('activity_log')->where('log_name', 'auditoria')->count())->toBe(5)
        ->and(DB::table('activity_log')->where('log_name', 'actividades')->count())->toBe(5);

    $this->seed(DemoDataSeeder::class);

    expect(DB::table('notifications')->count())->toBe(6)
        ->and(DB::table('activity_log')->count())->toBe(10);
});
