<?php

namespace App\Http\Controllers\WakilDekan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WakilDekanController extends Controller
{
    public function dashboard()
    {
        // Mengarah ke file resources/views/wakil-dekan/dashboard.blade.php
        return view('wakil-dekan.dashboard');
    }
}
