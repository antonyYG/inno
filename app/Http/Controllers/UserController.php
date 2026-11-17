<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditUser;
use App\Http\Requests\UserStoreRequest;
use App\Models\Area;
use App\Models\Asistencia;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(
        protected UserService $userService
    ) {}

    public static function middleware()
    {
        return [
            new Middleware('can:read.users', only: ['index', 'data']),
            new Middleware('can:create.users', only: ['create', 'store']),
            new Middleware('can:edit.users', only: ['edit', 'update']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.users.index');
    }

    public function data()
    {
        $query = User::with('roles')
            ->select([
                'id',
                'nombre',
                'email',
                'estado',
                'apellidos',
                'horas_requeridas',
            ])
            ->addSelect([
                'segundos_acumulados' => Asistencia::query()
                    ->selectRaw('COALESCE(SUM(TIME_TO_SEC(tiempo_trabajado)), 0)')
                    ->whereColumn('asistencias.usuario_id', 'usuarios.id')
                    ->whereIn('tipo', ['asistio', 'tardanza'])
                    ->whereNotNull('tiempo_trabajado'),
            ]);

        return DataTables::eloquent($query)
            ->filter(function ($query) {
                if (request()->filled('rol')) {
                    $query->whereHas('roles', function (Builder $q) {
                        $q->where('name', request('rol'));
                    });
                }
            }, true)
            ->addColumn(
                'acciones',
                fn ($user) => view('admin.users.actions', compact('user'))->render()
            )
            ->rawColumns(['acciones'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $areas = Area::select('id', 'nombre')->get();

        return view('admin.users.create', compact('areas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        try {
            DB::beginTransaction();
            $user = User::create($data);
            $user->assignRole($data['rol']);
            DB::commit();
            session()->flash('swal', [
                'message' => 'usuario Creado',
                'title' => 'Exito',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.users.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('swal', [
                'message' => 'No se pudo crear al usuario'.$e->getMessage(),
                'title' => 'Fallido',
                'icon' => 'error',
            ]);

            return redirect()->route('admin.users.index');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $areas = Area::select(['id', 'nombre'])->get();

        return view('admin.users.edit', compact('user', 'areas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EditUser $request, User $user)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();
            if (empty($data['password'])) {
                unset($data['password']);
            }

            // El rol no es un atributo del modelo: se sincroniza con Spatie.
            $rol = $data['rol'];
            unset($data['rol']);

            $user->update($data);

            // Sincronizar el rol permite cambiarlo desde la edición.
            $user->syncRoles($rol);

            DB::commit();
            session()->flash('swal', [
                'message' => 'Usuario Actualizado',
                'title' => 'Exito',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.users.index');
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('swal', [
                'message' => 'No se pudo actualizar al usuario'.$e->getMessage(),
                'title' => 'Fallido',
                'icon' => 'error',
            ]);

            return redirect()->route('admin.users.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function Status(User $user)
    {
        $user->estado = ! $user->estado;
        $user->save();

        return response()->json([
            'estado' => (bool) $user->fresh()->estado,
            'swal' => [
                'title' => 'Éxito',
                'message' => 'Estado cambiado con éxito',
                'icon' => 'success',
            ],
        ]);
    }

    public function getCertificate(Request $request, User $user)
    {
        // Un practicante solo puede generar su propio certificado.
        if (! auth()->user()->hasRole('admin') && $user->id !== auth()->id()) {
            abort(403);
        }

        // Obtener únicamente asistencias válidas
        $asistencias = Asistencia::query()
            ->where('usuario_id', $user->id)
            ->whereIn('tipo', ['asistio', 'tardanza'])
            ->whereNotNull('tiempo_trabajado')
            ->get(['fecha', 'tiempo_trabajado']);

        // Sumar los segundos directamente desde MySQL
        $segundos = (int) Asistencia::query()
            ->where('usuario_id', $user->id)
            ->whereIn('tipo', ['asistio', 'tardanza'])
            ->whereNotNull('tiempo_trabajado')
            ->sum(DB::raw('TIME_TO_SEC(tiempo_trabajado)'));

        // Convertir segundos a horas y minutos
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);

        $tiempo = $horas.' horas'.
            ($minutos > 0 ? " y {$minutos} minutos" : '');

        // Validar que haya completado las horas requeridas
        $horasRequeridas = (int) ($user->horas_requeridas ?: 360);

        if ($segundos < ($horasRequeridas * 3600)) {
            return redirect()
                ->route(auth()->user()->hasRole('admin') ? 'admin.users.index' : 'admin.dashboard')
                ->with('swal', [
                    'title' => 'Certificado no disponible',
                    'message' => 'El practicante todavía no ha completado las horas requeridas.',
                    'icon' => 'warning',
                ]);
        }

        $user->load('area');

        // Fechas de respaldo: primera y última asistencia válida.
        $fechas = $asistencias->map(
            fn ($a) => Carbon::parse($a->fecha)
        );

        // Periodo de prácticas: fechas configuradas del usuario; si no existen,
        // primera y última fecha de asistencia válida.
        $fechaInicio = $user->fecha_inicio ?? $fechas->min();
        $fechaFin = $user->fecha_fin ?? $fechas->max();

        $formato = 'd \d\e F \d\e Y';

        $template = new TemplateProcessor(
            storage_path('app/plantillas/certificado.docx')
        );

        $template->setValue(
            'nombre',
            mb_strtoupper($user->nombre.' '.$user->apellidos)
        );

        $template->setValue('area', $user->area->nombre ?? '________');

        $template->setValue(
            'fecha_inicio',
            $fechaInicio?->locale('es')->translatedFormat($formato) ?? '________'
        );

        $template->setValue(
            'fecha_fin',
            $fechaFin?->locale('es')->translatedFormat($formato) ?? '________'
        );

        $template->setValue('tiempo', $tiempo);

        $template->setValue(
            'fecha_emision',
            now()->locale('es')->translatedFormat($formato)
        );

        $template->setValue('dni', $request->input('dni', '________'));
        $template->setValue('carrera', $request->input('carrera', '________________'));
        $template->setValue('institucion', $request->input('institucion', '________'));
        $template->setValue('semestre', $request->input('semestre', '__'));

        File::ensureDirectoryExists(storage_path('app/temp'));

        $path = storage_path('app/temp/certificado_'.$user->id.'.docx');

        $template->saveAs($path);

        return response()
            ->download($path, 'certificado_'.$user->apellidos.'.docx')
            ->deleteFileAfterSend(true);
    }

    /**
     * El practicante genera (o consulta) su propio certificado.
     * La autorización se hace dentro de getCertificate.
     */
    public function miCertificado()
    {
        return $this->getCertificate(request(), auth()->user());
    }
}
