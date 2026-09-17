<x-layouts.admin :titulo="'Papel — '.$papel->name">
    <x-slot:subtitulo>Permissões granulares deste papel</x-slot:subtitulo>

    <x-slot:acoes>
        <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Voltar</x-admin.botao>
    </x-slot:acoes>

    <x-admin.painel :padding="false" class="p-5 sm:p-6">
        <form method="POST" action="{{ route('admin.papeis.update', $papel) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <fieldset>
                <legend class="mb-3 text-xs font-semibold uppercase tracking-[0.12em] text-ink-faint">Permissões</legend>
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($permissoes as $permissao)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-surface-line px-3 py-2.5 text-sm transition hover:border-brand/40 hover:bg-brand-soft/50">
                            <input
                                type="checkbox"
                                name="permissoes[]"
                                value="{{ $permissao->name }}"
                                class="size-4 rounded border-surface-line text-brand focus:ring-brand/40"
                                @checked($papel->permissions->contains('name', $permissao->name))
                            >
                            <span class="text-ink">{{ $permissao->name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex flex-wrap gap-2 border-t border-surface-line pt-5">
                <x-admin.botao tipo="submit">Salvar permissões</x-admin.botao>
                <x-admin.botao :href="route('admin.papeis.index')" variante="secundario">Cancelar</x-admin.botao>
            </div>
        </form>
    </x-admin.painel>
</x-layouts.admin>
