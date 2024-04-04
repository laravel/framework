<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">

    <div>
        <span>Request</span>
    </div>

    <div class="mt-2">
        <span class="font-semibold">{{ $exception->request()->method() }}</span>
        <span class="text-gray-500">{{ $exception->request()->url() . $exception->request()->path() }}</span>
    </div>

    <div class="mt-2">
        <span class="font-semibold">Headers</span>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <table class="table-auto">
            <tbody>
                @foreach ($exception->request()->headers->all() as $key => $value)
                    <tr>
                        <td class="border px-4 py-2">{{ $key }}</td>
                        <td class="border px-4 py-2">{{ implode(', ', $value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
</x-laravel-exceptions-renderer::card>
