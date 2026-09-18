<x-layouts.admin titulo="Nova unidade">
    <x-slot:subtitulo>Pins no mapa + polígono livre para delimitar o território</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.unidades.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="p-4 sm:p-6">
        <form method="POST" action="{{ route('admin.unidades.store') }}">
            @csrf
            @include('admin.unidades._form', [
                'rotuloSubmit' => 'Criar unidade',
                'poligonoPrefill' => $poligonoPrefill ?? null,
                'cepInicioPrefill' => $cepInicioPrefill ?? null,
                'cepFimPrefill' => $cepFimPrefill ?? null,
                'prospectosMapa' => $prospectosMapa ?? [],
            ])
        </form>
    </x-admin.painel>
</x-layouts.admin>
