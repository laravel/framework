<x-mail::message>
    # Hello World

    <x-mail::button :url="''">
        Click me
    </x-mail::button>

    <img
        src="{{ $message->embedData(
        data: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
        name: 'logo.svg',
        contentType: 'image/svg+xml',
    ) }}"
        class="logo"
        alt="Logo"
    />

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
