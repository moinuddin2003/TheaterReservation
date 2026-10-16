@props(['name' => 'poster', 'label' => 'Poster image', 'value' => null])

<div x-data="{ preview: '{{ $value ? e(\Illuminate\Support\Facades\Storage::disk('public')->url($value)) : '' }}' }">
    <label for="{{ $name }}" class="block text-xs font-semibold text-cine-secondary">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/*" class="mt-1.5 block w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-secondary file:mr-3 file:rounded-lg file:border-0 file:bg-cine-elevated file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-cine-primary" x-on:change="const file = $event.target.files[0]; if (file) preview = URL.createObjectURL(file)">
    <template x-if="preview"><img :src="preview" alt="Poster preview" class="mt-3 h-36 rounded-xl object-cover"></template>
    @error($name)<span class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</div>
