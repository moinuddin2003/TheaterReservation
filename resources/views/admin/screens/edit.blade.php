<x-admin.layout title="Edit screen" breadcrumb="CineBook / Admin / Cinema / Edit screen">
    @include('admin.screens._form', ['screen' => $screen, 'formTitle' => 'Edit screen', 'formSubtitle' => 'Update the auditorium details. Existing seats are not changed.'])
</x-admin.layout>
