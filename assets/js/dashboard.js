/**
 * Executive Dashboard — stagger panels (GSAP)
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof gsap !== 'undefined' && !prefersReduced) {
        gsap.from('#exec-dashboard .dashboard-panel', {
            opacity: 0,
            y: 14,
            duration: 0.42,
            stagger: 0.06,
            ease: 'power3.out',
            delay: 0.06,
        });
    }
})();
