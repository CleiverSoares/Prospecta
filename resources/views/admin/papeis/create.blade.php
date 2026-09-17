<x-layouts.admin titulo="Novo papel">
    <x-slot:subtitulo>Defina o nome do papel; permissões vêm na edição</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="max-w-3xl p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.papeis.store') }}" class="space-y-5">
            @csrf
            <x-admin.campo
                rotulo="Nome"
                nome="nome"
                :valor="old('nome')"
                obrigatorio
            />
            <div class="flex flex-wrap gap-2">
                <x-admin.botao tipo="submit">Criar papel</x-admin.botao>
                <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Cancelar</x-admin.botao>
            </div>
        </form>
    </x-admin.painel>
</x-layouts.admin>
