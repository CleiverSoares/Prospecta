<x-layouts.admin :titulo="'Editar — '.$unidade->nome">
    <x-slot:subtitulo>Ajuste dados, CEPs e polígono livre no mapa</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.unidades.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="p-4 sm:p-6">
        <form method="POST" action="{{ route('admin.unidades.update', $unidade) }}">
            @csrf
            @method('PUT')
            @include('admin.unidades._form', [
                'unidade' => $unidade,
                'rotuloSubmit' => 'Salvar alterações',
                'prospectosMapa' => $prospectosMapa ?? [],
            ])
        </form>
    </x-admin.painel>
</x-layouts.admin>
