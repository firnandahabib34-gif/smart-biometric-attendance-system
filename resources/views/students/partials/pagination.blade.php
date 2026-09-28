<div class="pagination">
    <span>Menampilkan {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }}</span>
    {{ $students->links() }}
</div>