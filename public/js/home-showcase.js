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

    // The approved portal artwork contains the visible "Client Account Login" button
    // inside the image itself. Make the artwork clickable so tapping that visual button
    // reliably opens Laravel's /login page in both Arabic and English.
    var wireClientLoginButton = function () {
        var portalCard = document.querySelector('.portal-card');
        if (!portalCard) return;

        var buttons = Array.prototype.slice.call(portalCard.querySelectorAll('a.btn,button.btn,a,button'));
        buttons.forEach(function (button) {
            var text = String(button.textContent || '').replace(/\s+/g, ' ').trim();
            var isClientLogin = /دخول\s+حساب\s+العميل|client\s+(account\s+)?login|login\s+to\s+client|enter\s+client/i.test(text);
            if (!isClientLogin) return;

            if (button.tagName.toLowerCase() === 'a') {
                button.setAttribute('href', '/login');
                button.removeAttribute('target');
                button.removeAttribute('onclick');
            } else {
                button.setAttribute('type', 'button');
                button.onclick = function () { window.location.assign('/login'); };
            }
            button.setAttribute('data-client-login', 'true');
        });

        var portalImage = portalCard.querySelector('.portal-device');
        if (portalImage && portalImage.getAttribute('data-client-login-image') !== 'true') {
            portalImage.setAttribute('data-client-login-image', 'true');
            portalImage.setAttribute('role', 'link');
            portalImage.setAttribute('tabindex', '0');
            portalImage.setAttribute('aria-label', (document.documentElement.lang || '').toLowerCase().indexOf('en') === 0 ? 'Client Account Login' : 'دخول حساب العميل');
            portalImage.style.cursor = 'pointer';

            var openLogin = function () { window.location.assign('/login'); };
            portalImage.addEventListener('click', openLogin);
            portalImage.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openLogin();
                }
            });
        }
    };

    wireClientLoginButton();
})();
