<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __invoke(): View
    {
        return view('app.setup');
    }
}
