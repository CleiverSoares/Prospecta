<x-layouts.admin titulo="Novo usuário">
    <x-slot:subtitulo>Vincule unidade, gestor e papel Spatie</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.usuarios.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="p-4 sm:p-6">
        <form method="POST" action="{{ route('admin.usuarios.store') }}">
            @csrf
            @include('admin.usuarios._form', ['rotuloSubmit' => 'Criar usuário'])
        </form>
    </x-admin.painel>
</x-layouts.admin>
