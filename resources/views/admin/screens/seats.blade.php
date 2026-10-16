<x-admin.layout :title="$screen->name.' seats'" breadcrumb="Cinema">
    <x-admin.page-header :title="$screen->name.' seat map'" subtitle="{{ $screen->cinema->name }} · {{ $screen->seats->count() }} seats · Toggle seats to disable or restore availability.">
        <x-slot:actions><x-admin.button variant="secondary" href="{{ route('admin.cinema.index') }}">Back to screens</x-admin.button><x-admin.button href="{{ route('admin.screens.edit', $screen) }}">Edit layout</x-admin.button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    <x-admin.seat-map :seats="$screen->seats" editable />
</x-admin.layout>
