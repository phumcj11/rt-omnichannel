/**
 * Global shell — subtle premium motion + mobile sidebar
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof gsap !== 'undefined' && !prefersReduced) {
        gsap.from('.sidebar-shell', {
            opacity: 0,
            x: -8,
            duration: 0.45,
            ease: 'power3.out',
        });

        gsap.from('header', {
            opacity: 0,
            y: -6,
            duration: 0.4,
            ease: 'power2.out',
        });

        gsap.from('.dashboard-card', {
            opacity: 0,
            y: 18,
            duration: 0.5,
            stagger: 0.07,
            ease: 'power3.out',
            delay: 0.08,
        });

        gsap.from('#exec-dashboard h2', {
            opacity: 0,
            y: 10,
            duration: 0.45,
            ease: 'power2.out',
        });

        gsap.from('#exec-dashboard .hero-actions a, #exec-dashboard .hero-actions span', {
            opacity: 0,
            y: 8,
            duration: 0.4,
            stagger: 0.05,
            ease: 'power2.out',
            delay: 0.12,
        });
    }

    var btn = document.getElementById('btn-sidebar');
    var sidebar = document.getElementById('app-sidebar');
    if (btn && sidebar) {
        btn.addEventListener('click', function () {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('fixed');
            sidebar.classList.toggle('inset-y-0');
            sidebar.classList.toggle('left-0');
            sidebar.classList.toggle('z-40');
            sidebar.classList.toggle('shadow-soft-lg');
            sidebar.classList.toggle('flex');
        });
    }
})();
