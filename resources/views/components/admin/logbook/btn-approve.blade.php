@props(['id'])

<form action="{{ route('admin.log_book.approve', $id) }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="inline-flex items-center justify-center h-8 px-3 text-sm font-semibold rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 focus:outline-none focus:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-500 dark:hover:bg-green-800/30 cursor-pointer" title="Tandai Sudah Direview">
        Approve
    </button>
</form>
