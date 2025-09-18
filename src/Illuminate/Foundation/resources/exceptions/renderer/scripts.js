import Alpine from 'alpinejs';
import tippy from 'tippy.js';

window.Alpine = Alpine;

Alpine.start();

tippy('[data-tippy-content]', {
    arrow: false,
    allowHTML: true,
    animation: 'shift-away',
    delay: [300, 0],
    duration: 200,
    theme: 'laravel',
});

window.copyToClipboard = async function (text) {
    if (navigator.clipboard) {
        await navigator.clipboard.writeText(text);
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);
        textarea.select();

        const result = document.execCommand('copy');
        document.body.removeChild(textarea);

        if (!result) {
            throw new Error('Failed to copy text to clipboard');
        }
    }
};
