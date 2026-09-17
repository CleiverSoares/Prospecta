<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        return view('app.inicio');
    }
}
