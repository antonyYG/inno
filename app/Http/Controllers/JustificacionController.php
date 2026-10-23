<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JustificacionController extends Controller
{
    public function index()
    {
        return view('admin.justificaciones.index');
    }

    public function data()
    {
        $query = Asistencia::with('usuario')->whereIn('tipo',['falto','tardanza']);
        return DataTables::eloquent($query)
        ->addColumn('acciones',fn($asistencia)=> view('admin.justificaciones.actions',compact('asistencia'))->render())
        ->rawColumns(['acciones'])
        ->toJson();
    }

}
