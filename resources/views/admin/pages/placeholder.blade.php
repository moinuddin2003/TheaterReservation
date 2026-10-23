<x-admin.layout :title="$pageTitle" :breadcrumb="$breadcrumb">
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" />

    <x-admin.card>
        <x-admin.empty-state
            :icon="$icon"
            title="Coming soon"
            :text="$emptyText ?? 'This section is ready for its management tools. Functionality will be added in a later step.'"
        />
    </x-admin.card>
</x-admin.layout>
