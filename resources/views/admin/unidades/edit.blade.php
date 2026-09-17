<x-layouts.admin :titulo="'Editar — '.$unidade->nome">
    <x-slot:acoes>
        <x-admin.botao :href="route('admin.unidades.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('admin.unidades.update', $unidade) }}" class="space-y-5">
            @csrf
            @method('PUT')
            @include('admin.unidades._form')
            <x-admin.botao tipo="submit" class="w-full sm:w-auto">Salvar alterações</x-admin.botao>
        </form>
    </x-admin.painel>
</x-layouts.admin>
