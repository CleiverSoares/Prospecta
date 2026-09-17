@php
    /** @var \App\Models\Unidade|null $unidade */
    $unidade = $unidade ?? null;
@endphp

<div>
    <label for="nome" class="block text-sm font-medium">Nome</label>
    <input id="nome" name="nome" value="{{ old('nome', $unidade?->nome) }}" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm">
    @error('nome') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="tipo" class="block text-sm font-medium">Tipo</label>
    <select id="tipo" name="tipo" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm">
        @foreach ($tipos as $tipo)
            <option value="{{ $tipo->value }}" @selected(old('tipo', $unidade?->tipo?->value) === $tipo->value)>{{ $tipo->value }}</option>
        @endforeach
    </select>
    @error('tipo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="cep_inicio" class="block text-sm font-medium">CEP início</label>
        <input id="cep_inicio" name="cep_inicio" value="{{ old('cep_inicio', $unidade?->cep_inicio) }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm">
        @error('cep_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="cep_fim" class="block text-sm font-medium">CEP fim</label>
        <input id="cep_fim" name="cep_fim" value="{{ old('cep_fim', $unidade?->cep_fim) }}" class="mt-1 w-full rounded-md border-slate-300 shadow-sm">
        @error('cep_fim') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
