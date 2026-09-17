<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RotaPageController extends Controller
{
    public function __invoke(): View
    {
        return view('app.rota', [
            'googleMapsKey' => config('prospecta.google.maps_api_key'),
        ]);
    }
}
