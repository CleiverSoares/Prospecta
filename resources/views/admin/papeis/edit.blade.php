<x-layouts.admin :titulo="'Papel — '.$papel->name">
    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('admin.papeis.update', $papel) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-800">Permissões</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($permissoes as $permissao)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm transition hover:border-brand/40 hover:bg-slate-50">
                            <input
                                type="checkbox"
                                name="permissoes[]"
                                value="{{ $permissao->name }}"
                                class="size-4 rounded border-slate-300 text-brand focus:ring-brand/40"
                                @checked($papel->permissions->contains('name', $permissao->name))
                            >
                            <span class="text-slate-800">{{ $permissao->name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-admin.botao tipo="submit" class="w-full sm:w-auto">Salvar permissões</x-admin.botao>
        </form>
    </x-admin.painel>
</x-layouts.admin>
