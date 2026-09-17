<x-layouts.admin :titulo="'Editar — '.$usuario->name">
    <x-slot:subtitulo>Ajuste dados, território e papel</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.usuarios.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="p-4 sm:p-6">
        <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
            @csrf
            @method('PUT')
            @include('admin.usuarios._form', [
                'usuario' => $usuario,
                'rotuloSubmit' => 'Salvar alterações',
            ])
        </form>
    </x-admin.painel>
</x-layouts.admin>
