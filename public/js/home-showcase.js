(function () {
    document.querySelectorAll('[data-showcase-carousel]').forEach(function (carousel) {
        var rail = carousel.querySelector('[data-showcase-rail]');
        var previous = carousel.querySelector('[data-showcase-prev]');
        var next = carousel.querySelector('[data-showcase-next]');

        if (!rail || !previous || !next) return;

        var direction = getComputedStyle(rail).direction;
        var amount = function () { return Math.max(rail.clientWidth * .82, 240); };
        var update = function () {
            var remaining = rail.scrollWidth - rail.clientWidth;
            var position = Math.abs(rail.scrollLeft);
            previous.disabled = position < 4;
            next.disabled = position > remaining - 4;
        };
        var scroll = function (forward) {
            var sign = direction === 'rtl' ? -1 : 1;
            rail.scrollBy({ left: amount() * sign * (forward ? 1 : -1), behavior: 'smooth' });
        };

        previous.addEventListener('click', function () { scroll(false); });
        next.addEventListener('click', function () { scroll(true); });
        rail.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });
        update();
    });
})();
