<x-layouts.admin titulo="Novo papel">
    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel class="mx-auto max-w-lg">
        <form method="POST" action="{{ route('admin.papeis.store') }}" class="space-y-5">
            @csrf
            <x-admin.campo
                rotulo="Nome"
                nome="nome"
                :valor="old('nome')"
                obrigatorio
            />
            <x-admin.botao tipo="submit" class="w-full sm:w-auto">Criar papel</x-admin.botao>
        </form>
    </x-admin.painel>
</x-layouts.admin>
