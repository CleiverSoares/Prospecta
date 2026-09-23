<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AgendaDiaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaDiaController extends Controller
{
    public function __construct(
        private readonly AgendaDiaService $agendaDiaService,
    ) {}

    public function __invoke(Request $request): View
    {
        $dados = $this->agendaDiaService->montar($request->user());

        return view('admin.agenda.index', $dados);
    }
}
