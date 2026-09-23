<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visita;
use App\Services\ArquivoMidiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitaController extends Controller
{
    public function __construct(
        private readonly ArquivoMidiaService $arquivoMidiaService,
    ) {}

    public function index(Request $request): View
    {
        $query = Visita::query()
            ->with(['prospecto:id,razao_social,cnpj,endereco,is_cliente', 'usuario:id,name,unidade_id'])
            ->latest();

        if ($request->user()?->hasRole('gestor') && ! $request->user()?->hasRole('adm')) {
            $gestorId = $request->user()->id;
            $unidadeId = $request->user()->unidade_id;
            $ids = User::query()
                ->where(function ($q) use ($gestorId, $unidadeId) {
                    $q->where('gestor_id', $gestorId)
                        ->orWhere('id', $gestorId);
                    if ($unidadeId) {
                        $q->orWhere('unidade_id', $unidadeId);
                    }
                })
                ->pluck('id');
            $query->whereIn('user_id', $ids);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('vendedor_id')) {
            $query->where('user_id', (int) $request->input('vendedor_id'));
        }

        if ($request->filled('q')) {
            $termo = '%'.$request->string('q').'%';
            $query->whereHas('prospecto', function ($q) use ($termo) {
                $q->where('razao_social', 'like', $termo)
                    ->orWhere('cnpj', 'like', $termo);
            });
        }

        return view('admin.visitas.index', [
            'visitas' => $query->paginate(20)->withQueryString(),
            'vendedores' => User::query()->role('vendedor')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Visita $visita): View
    {
        $this->autorizarVisita($visita);
        $visita->load(['prospecto', 'usuario.unidade']);

        return view('admin.visitas.show', [
            'visita' => $visita,
        ]);
    }

    public function foto(Visita $visita): StreamedResponse|RedirectResponse
    {
        $this->autorizarVisita($visita);

        abort_unless($visita->caminho_foto && $this->arquivoMidiaService->existe($visita->caminho_foto), 404);

        return $this->arquivoMidiaService->resposta($visita->caminho_foto, 'image/jpeg');
    }

    public function audio(Visita $visita): StreamedResponse|RedirectResponse
    {
        $this->autorizarVisita($visita);

        abort_unless($visita->caminho_audio && $this->arquivoMidiaService->existe($visita->caminho_audio), 404);

        return $this->arquivoMidiaService->resposta($visita->caminho_audio, 'audio/webm');
    }

    private function autorizarVisita(Visita $visita): void
    {
        $user = request()->user();
        abort_unless($user?->can('visitas.ver'), 403);

        if ($user->hasRole('adm')) {
            return;
        }

        if ($user->hasRole('gestor')) {
            $ok = User::query()
                ->where('id', $visita->user_id)
                ->where(function ($q) use ($user) {
                    $q->where('gestor_id', $user->id)
                        ->orWhere('id', $user->id)
                        ->orWhere('unidade_id', $user->unidade_id);
                })
                ->exists();
            abort_unless($ok, 403);

            return;
        }

        abort_unless((int) $visita->user_id === (int) $user->id, 403);
    }
}
