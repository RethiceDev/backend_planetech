@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="flex flex-col items-center gap-3">

    <p class="text-xs text-slate-500 text-center">
        Affichage de
        <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
        à
        <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
        sur
        <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
        produits
    </p>

    <ul class="flex flex-wrap items-center justify-center gap-1">

        {{-- Précédent --}}
        @if ($paginator->onFirstPage())
            <li>
                <span class="px-3 py-1.5 rounded-lg border border-gray-100 text-xs text-gray-300 cursor-not-allowed">‹</span>
            </li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-slate-600 hover:bg-gray-50">‹</a>
            </li>
        @endif

        {{-- Numéros --}}
        @foreach ($elements as $element)

            @if (is_string($element))
                <li><span class="px-2 text-xs text-slate-400">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li>
                            <span class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold">{{ $page }}</span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $url }}"
                               class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-slate-600 hover:bg-gray-50">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif

        @endforeach

        {{-- Suivant --}}
        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-slate-600 hover:bg-gray-50">›</a>
            </li>
        @else
            <li>
                <span class="px-3 py-1.5 rounded-lg border border-gray-100 text-xs text-gray-300 cursor-not-allowed">›</span>
            </li>
        @endif

    </ul>
</nav>
@endif
