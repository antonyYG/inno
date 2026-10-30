<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TickController extends Controller
{
    public function index()
    {
        return view('practicing.tick.index');
    }
}
