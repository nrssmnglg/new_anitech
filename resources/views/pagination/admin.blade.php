@php
    $paginationSummaryLabel = $summaryLabel ?? 'farmer records';
    $paginationSummaryContext = $summaryContext ?? 'in the registry';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">
        <div class="app-pagination">
            <div class="app-pagination__summary">
                <span class="app-pagination__eyebrow">Showing {{ number_format($paginator->firstItem() ?? 0) }}-{{ number_format($paginator->lastItem() ?? 0) }}</span>
                <p class="app-pagination__copy"><strong>{{ number_format($paginator->total()) }}</strong> {{ $paginationSummaryLabel }} {{ $paginationSummaryContext }}.</p>
            </div>

            <div class="app-pagination__controls">
                @if ($paginator->onFirstPage())
                    <span class="app-pagination__nav app-pagination__nav--disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="app-pagination__nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                @endif

                <div class="app-pagination__pages">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="app-pagination__ellipsis">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="app-pagination__page app-pagination__page--active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="app-pagination__page" href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a class="app-pagination__nav" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="app-pagination__nav app-pagination__nav--disabled" aria-disabled="true">Next</span>
                @endif
            </div>
        </div>
    </nav>
@endif
