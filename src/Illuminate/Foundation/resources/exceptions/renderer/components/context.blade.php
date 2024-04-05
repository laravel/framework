<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">

    <div>
        <span>Request</span>
    </div>

    <div class="mt-2">
        <span class="font-semibold">{{ $exception->request()->method() }}</span>
        <span class="text-gray-500">{{ $exception->request()->url() }}</span>
    </div>

    <div class="mt-2">
        <span class="font-semibold">Headers</span>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <table class="table-auto">
            <tbody>
                @forelse ($exception->request()->headers->all() as $key => $value)
                    <tr>
                        <td class="border px-4 py-2">{{ $key }}</td>
                        <td class="border px-4 py-2 overflow-x-auto">{{ implode(', ', $value) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border px-4 py-2" colspan="2">No headers data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        <span class="font-semibold">Body</span>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <table class="table-auto">
            <thead>
                <tr>
                    <th class="px-4 py-2">Key</th>
                    <th class="px-4 py-2">Value</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($exception->request()->all() as $key => $value)
                    <tr>
                        <td class="border px-4 py-2">{{ $key }}</td>
                        <td class="border px-4 py-2">{{ $value }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border px-4 py-2" colspan="2">No body data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        <span class="font-semibold">Queries</span>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <table class="table-auto">
            <tbody>
                @if (count($exception->listener()->queries()) === 1)
                    <tr>
                        <td class="border px-4 py-2" colspan="3">Only the first 100 queries are displayed</td>
                    </tr>
                @endif

                @forelse ($exception->listener()->queries() as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
                    <tr>
                        <td class="border px-4 py-2">{{ $connectionName }}</td>
                        <td class="border px-4 py-2">{{ $sql }}</td>
                        <td class="border px-4 py-2">{{ $time }}ms</td>
                    </tr>
                @empty
                    <tr>
                        <td class="border px-4 py-2" colspan="2">No query data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
</x-laravel-exceptions-renderer::card>
