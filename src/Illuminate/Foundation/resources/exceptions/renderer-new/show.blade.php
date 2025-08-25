<x-laravel-exceptions-renderer-new::layout>
    <!-- Topbar -->
    <x-laravel-exceptions-renderer-new::section-container class="px-6 py-6">
        <x-laravel-exceptions-renderer-new::topbar :title="$exception->title()" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Header Section -->
    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-8">
        <x-laravel-exceptions-renderer-new::header :$exception />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-8">
        <x-laravel-exceptions-renderer-new::request-url :request="$exception->request()" />

        <x-laravel-exceptions-renderer-new::overview :request="$exception->request()" />

        <x-laravel-exceptions-renderer-new::trace :$exception />

        <x-laravel-exceptions-renderer-new::query :queries="$exception->applicationQueries()" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Context -->
    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-12">
        <x-laravel-exceptions-renderer-new::request-header :headers="$exception->requestHeaders()" />

        <x-laravel-exceptions-renderer-new::request-body :body="$exception->requestBody()" />

        <x-laravel-exceptions-renderer-new::routing :routing="$exception->applicationRouteContext()" />

        <x-laravel-exceptions-renderer-new::routing-parameter :routeParameters="$exception->applicationRouteParametersContext()" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Footer with ASCII Art -->
    <x-laravel-exceptions-renderer-new::section-container>
        <div class="flex-1 font-mono text-xs text-transparent whitespace-pre" style="background: radial-gradient(25.8px 13.5px at 106px 70px, rgba(212,212,212,1) 0%, rgba(179,179,179,1) 25%, rgba(146,146,146,1) 50%, rgba(113,113,113,1) 75%, rgba(81,81,81,1) 100%); -webkit-background-clip: text; background-clip: text;">1111111111                                                                                                                                                                                                                    111111111
1011011011                                                                                                                                                                                                                    110110110
1111110111                                                                                                                                                                                                                    111101111
1101011101                                                                                                                                                                                                                    101111011
1111111111                                                                                                                                                                                                                    111011110
1011010111                                                                                                                                                                                                                    101110111
1111111101                                                                                                                                                                                                                    111111101
1010110111                                                                                                                                                                                                                    110101111
1111111111                                                                                                                                                                                                                    111111011
1101101011                                111111111                                                             111111111                                                                         111111111                   101101111
1111111110                            1111101101101111   1111111111      1111111111111111111111111          1111101101101111    111111111    111111111                    11111111111         11111011011011111               111111101
1010101111                         111101011111111101111 1101110110      1101101101011011010110111       1111010111111111011111 110110110    1101101101                  11011011011       11110111111111110110111            101010111
1111111011                       1110111111101010101110111111110111      1111011111111110111111101     1110111111101010111101101111101111     1111101111                 1111111011      111011110101010101111111111          111111110
1101101110                     111011101101111111111111101010111101      1011110110101011110101111   111011101101111111101111111010111011      101111101                1101010111      110111011111111111110110101111        110111011
1111111111                    11011111111110       1010111111101111      1110111111111111011111011  11011111111110       1101011111111110      1110111111              11111111111    11111111110           11111110111       111101111
1010110101                   11111101010              1110110111011      1011101011                11111101010              1110110101111       1111010111             1010110110    1101010101               1011011011      101111101
1111111111                   1011011111                111111111111      1111111110               11011011111                111111111011        1011111011           1111111111    1111111111                 1111111111     111011011
1101010111                  1111011101                  10110101101      1101011011               1111011101                  10101101110        1111011111          11011010101    1010110111                  101101011     101111111
1111111101                 11011110111                   1111111111      1111111111               101111011                    1111111111         1011101101         1111111111    11111111101111111111111111111111111110     111101101
1011011111                 1111011111                    1011010101      1011010110              1111011111                    1101101011          1111111111       1101011011     101101011110110110110101101010101011111    110111111
1111110101                 1011110110                    1111111111      1111111111              1101111011                     111111110           101010110      11111111111     111111110111111101111111111111111110111    111101011
1010111111                 1110111111                    1101011011      1010110101              1111101111                    1101011011           1111111111    11010110101      110110111101010111011010110110110111101    101111110
1111101101                  1111011011                   1111111111      1111111111               101111011                    1111111111            1101101101   1111111111       1111111011                                 111011011
1101111111                  1011111111                  11011010101      1101101101               1110111101                  11011010110            11111111111 1101101011         1010111111                                101111111
1111010111                   11010101111               110111111111      1111111111               111110111111               110111111111             101010110111110111110         11111010111                 1111          111101101
1011111101                   1111111101111           11011101101110      1010101011                1011110110111          111011110110101              1111111110101111011           11011111011              111011111       110111111
111011011111111111111111111   11011011101111111 1111111111111111011      1111111111                 1101111111011111  1111111111011111111              111010111111110111             1111011111111        11110111011111     111110101
101111111011010110110101101     11111111010110111011010110110101111      1101101101                   11010111110110111011010111110101101               11111101011011111               1111011010111111111101111111101       101011111
111101101111111111101111111      1101011111111101111111111101111101      1111111111                    1111101011111111111111101011111111                101111111111101                 1011111111011011011110101011         111111011
110111111010110101111101011         1111111101101010101  1111101011      1010110101                      111111110110101010111  111101011                11101011011011                    110101111111101101111111           110111110
111110101111111111011111111            11111110111111    1011111011      1111111111                          10111111111111     110111111                 1111111110111                        1101101011111101               111101011
        </div>
    </x-laravel-exceptions-renderer-new::section-container>
</x-laravel-exceptions-renderer-new::layout>
