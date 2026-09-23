<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Disco de mídia: local (dev) ou Supabase Storage (Render/portfolio).
 */
class ArquivoMidiaService
{
    public function driver(): string
    {
        $driver = (string) config('prospecta.storage.driver', 'local');

        if ($driver === 'supabase' && filled(config('prospecta.storage.supabase.url'))
            && filled(config('prospecta.storage.supabase.service_role_key'))) {
            return 'supabase';
        }

        return 'local';
    }

    public function salvar(UploadedFile $arquivo, string $pasta): string
    {
        $pasta = trim($pasta, '/');
        $nome = Str::random(40).'.'.$this->extensao($arquivo);
        $caminho = $pasta.'/'.$nome;

        if ($this->driver() === 'supabase') {
            $this->enviarSupabase($caminho, $arquivo->get(), (string) $arquivo->getMimeType());

            return $caminho;
        }

        $disk = str_starts_with($pasta, 'usuarios/') ? 'public' : 'local';

        return $arquivo->storeAs($pasta, $nome, $disk);
    }

    public function apagar(?string $caminho): void
    {
        if (! filled($caminho)) {
            return;
        }

        if ($this->driver() === 'supabase') {
            $bucket = $this->bucket();
            Http::withToken($this->serviceRole())
                ->withHeaders(['apikey' => $this->serviceRole()])
                ->delete($this->baseUrl()."/storage/v1/object/{$bucket}/{$caminho}");

            return;
        }

        $disk = str_starts_with($caminho, 'usuarios/') ? 'public' : 'local';
        Storage::disk($disk)->delete($caminho);
    }

    public function existe(string $caminho): bool
    {
        if ($this->driver() === 'supabase') {
            $resposta = Http::withToken($this->serviceRole())
                ->withHeaders(['apikey' => $this->serviceRole()])
                ->head($this->baseUrl().'/storage/v1/object/'.$this->bucket().'/'.$caminho);

            return $resposta->successful();
        }

        $disk = str_starts_with($caminho, 'usuarios/') ? 'public' : 'local';

        return Storage::disk($disk)->exists($caminho);
    }

    public function urlPublica(string $caminho): string
    {
        if ($this->driver() === 'supabase') {
            return $this->baseUrl().'/storage/v1/object/public/'.$this->bucket().'/'.ltrim($caminho, '/');
        }

        if (str_starts_with($caminho, 'usuarios/')) {
            return Storage::disk('public')->url($caminho);
        }

        return Storage::disk('local')->url($caminho);
    }

    public function resposta(string $caminho, string $contentType): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        if ($this->driver() === 'supabase') {
            return redirect()->away($this->urlPublica($caminho));
        }

        $disk = str_starts_with($caminho, 'usuarios/') ? 'public' : 'local';

        return Storage::disk($disk)->response($caminho, null, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function enviarSupabase(string $caminho, string $binario, string $mime): void
    {
        $bucket = $this->bucket();
        $resposta = Http::withToken($this->serviceRole())
            ->withHeaders([
                'apikey' => $this->serviceRole(),
                'Content-Type' => $mime !== '' ? $mime : 'application/octet-stream',
                'x-upsert' => 'true',
            ])
            ->withBody($binario, $mime !== '' ? $mime : 'application/octet-stream')
            ->post($this->baseUrl()."/storage/v1/object/{$bucket}/{$caminho}");

        if (! $resposta->successful()) {
            throw new RuntimeException(
                'Falha ao enviar mídia ao Supabase Storage: '.$resposta->status().' '.$resposta->body()
            );
        }
    }

    private function extensao(UploadedFile $arquivo): string
    {
        $ext = strtolower((string) $arquivo->getClientOriginalExtension());

        return $ext !== '' ? $ext : 'bin';
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('prospecta.storage.supabase.url'), '/');
    }

    private function serviceRole(): string
    {
        return (string) config('prospecta.storage.supabase.service_role_key');
    }

    private function bucket(): string
    {
        return (string) config('prospecta.storage.supabase.bucket', 'prospecta');
    }
}
