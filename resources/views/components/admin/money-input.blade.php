@props(['name', 'label', 'cents' => 0, 'required' => false])

<div x-data="{ dollars: '{{ number_format(((int) old($name, $cents)) / 100, 2, '.', '') }}', get cents() { return Math.round((Number(this.dollars) || 0) * 100) } }">
    <label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}-dollars">{{ $label }} ($)</label>
    <div class="mt-1.5 flex min-h-10 items-center rounded-xl border border-cine-border/10 bg-cine-bg px-3">
        <span class="text-sm text-cine-tertiary">$</span>
        <input id="{{ $name }}-dollars" x-model="dollars" type="number" min="0" step="0.01" @required($required) class="w-full border-0 bg-transparent text-sm text-cine-primary focus:ring-0">
        <input type="hidden" name="{{ $name }}" :value="cents">
    </div>
    @error($name)<span class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</div>
