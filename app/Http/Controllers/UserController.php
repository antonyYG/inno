<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\Area;
use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function __construct(
        protected UserService $userService
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::role('admin')->filters()->GetOrPaginate();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $areas = Area::select('id','name')->get();
        return view('admin.users.create',compact('areas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        try {
            DB::beginTransaction();
            $this->userService->store($data);
            DB::commit();
            session()->flash('swal',[
                'message' => 'usuario Creado',
                'title' => 'Exito',
                'icon' => 'success'
            ]);
            return redirect()->route('admin.users.index');

        } catch (Exception $e) {
            DB::rollback();
             session()->flash('swal',[
                'message' => 'No se pudo crear al usuario',
                'title' => 'Fallido',
                'icon' => 'error'
            ]);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
