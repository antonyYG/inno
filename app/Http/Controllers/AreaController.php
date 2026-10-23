<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.area.index');
    }

    public function data()
    {
        $query = Area::select(['id','nombre','created_at']);

        return DataTables::eloquent($query)
            ->addColumn('acciones',fn($area)=> view('admin.area.actions',compact('area'))->render())
            ->rawColumns(['acciones'])
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|unique:areas,nombre',
        ]);

        Area::create($data);
        session()->flash('swal',[
            'title' => '!Ben Hecho¡',
            'message' => 'area creado correctamente',
        ]);

        return redirect()->route('admin.areas.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Area $area)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Area $area)
    {
        $data=$request->validate([
            'nombre' => 'required|unique:areas,nombre,'.$area->id
        ]);

        $area->update($data);

        return redirect()->route('admin.areas.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        if ($area->users()->exists()) {
            session()->flash('swal',[
                'title' => 'No se peude elimianr!',
                'message' => 'Usuarios asociados',
                'icon' => 'error'
            ]);
            return redirect()->back();
        }

        $area->delete();

        session()->flash('swal',[
            'title' => 'Bien hecho!',
            'message' => 'Area eliminada correctamente',
            'icon' => 'success'
        ]);

        return redirect()->back();

    }
}
