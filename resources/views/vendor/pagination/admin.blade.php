@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-end">
        <div class="inline-flex items-center overflow-hidden rounded-lg border border-cine-border/10 bg-cine-bg">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex h-9 w-9 items-center justify-center text-cine-tertiary/50">
                    <x-admin.icon name="chevron-left" class="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex h-9 w-9 items-center justify-center border-r border-cine-border/10 text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">
                    <x-admin.icon name="chevron-left" class="h-4 w-4" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-9 min-w-9 items-center justify-center border-r border-cine-border/10 px-2 text-xs text-cine-tertiary">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center border-r border-cine-border/10 bg-cine-accent px-2 text-xs font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="inline-flex h-9 min-w-9 items-center justify-center border-r border-cine-border/10 px-2 text-xs text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex h-9 w-9 items-center justify-center text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">
                    <x-admin.icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex h-9 w-9 items-center justify-center text-cine-tertiary/50">
                    <x-admin.icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
