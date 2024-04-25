@props(['cols' => 6, 'rows' => 1])
<section
    {{ $attributes->merge(['class' => "@container flex flex-col p-6 sm:p-12 bg-white dark:bg-gray-900/80 text-gray-900 dark:text-gray-100 rounded-lg default:col-span-full default:lg:col-span-{$cols} default:row-span-{$rows}  dark:ring-1 dark:ring-gray-800 shadow-xl"]) }}"
>
    {{ $slot }}
</section>
