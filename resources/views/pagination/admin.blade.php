@if ($paginator->hasPages())
<nav class="admin-pagination" aria-label="Navigasi halaman daftar">
    @if ($paginator->onFirstPage())
        <span class="btn disabled" aria-disabled="true">Sebelumnya</span>
    @else
        <a class="btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
    @endif
    <span class="small text-secondary" aria-label="Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())
        <a class="btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
    @else
        <span class="btn disabled" aria-disabled="true">Berikutnya</span>
    @endif
</nav>
@endif
