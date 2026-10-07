<?php

namespace App\Http\Controllers;

use App\Models\Practicing;
use Illuminate\Http\Request;

class PracticingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $practicings = Practicing::with(['user','area'])->GetOrPaginate();
        return view('admin.practicing.index',compact('practicings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Practicing $practicing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Practicing $practicing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Practicing $practicing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Practicing $practicing)
    {
        //
    }
}
