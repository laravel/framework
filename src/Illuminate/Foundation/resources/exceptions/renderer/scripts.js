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
