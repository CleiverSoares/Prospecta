<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class InicioController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('app.setup');
    }
}
