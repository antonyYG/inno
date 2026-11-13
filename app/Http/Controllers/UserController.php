<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditUser;
use App\Http\Requests\UserStoreRequest;
use App\Models\Area;
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
            new Middleware('can:read.users', only: ['index']),
            new Middleware('can:create.users', only: ['create']),
            new Middleware('can:edit.users', only: ['edit']),
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
            ->select(['id', 'nombre', 'email', 'estado', 'apellidos']);

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
            $user->update($data);
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
        $user->load(['asistencias', 'area']);

        // Tiempo total
        $segundos = $user->asistencias->sum(
            fn ($a) => strtotime($a->tiempo_trabajado ?? '00:00:00') - strtotime('00:00:00')
        );
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $tiempo = $horas.' horas'.($minutos > 0 ? " y {$minutos} minutos" : '');

        // Primer y último día de asistencia
        $fechas = $user->asistencias->map(fn ($a) => Carbon::parse($a->fecha ?? $a->created_at));
        $formato = 'd \d\e F \d\e Y';

        $template = new TemplateProcessor(storage_path('app/plantillas/certificado.docx'));

        $template->setValue('nombre', mb_strtoupper($user->nombre.' '.$user->apellidos));
        $template->setValue('area', $user->area->nombre ?? '________');
        $template->setValue('fecha_inicio', $fechas->min()?->locale('es')->translatedFormat($formato) ?? '________');
        $template->setValue('fecha_fin', $fechas->max()?->locale('es')->translatedFormat($formato) ?? '________');
        $template->setValue('tiempo', $tiempo);
        $template->setValue('fecha_emision', now()->locale('es')->translatedFormat($formato));

        // Datos que no guardas en BD: se llenan desde el formulario o quedan en blanco
        $template->setValue('dni', $request->input('dni', '________'));
        $template->setValue('carrera', $request->input('carrera', '________________'));
        $template->setValue('institucion', $request->input('institucion', '________'));
        $template->setValue('semestre', $request->input('semestre', '__'));

        File::ensureDirectoryExists(storage_path('app/temp'));
        $path = storage_path('app/temp/certificado_'.$user->id.'.docx');
        $template->saveAs($path);

        return response()->download($path, 'certificado_'.$user->apellidos.'.docx')
            ->deleteFileAfterSend(true);

    }
}
