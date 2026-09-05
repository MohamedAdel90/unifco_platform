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

    // Make the Client Account button functional in both Arabic and English.
    // The public homepage is guest-facing and Laravel exposes the login route at /login.
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
                button.onclick = function () { window.location.href = '/login'; };
            }
            button.setAttribute('data-client-login', 'true');
        });
    };

    // Keep the About UNIFCO lightbox reliable in both Arabic and English.
    // The modal is created dynamically, so repair its image after it opens and
    // use the repository-backed locale image instead of a stale/broken CMS URL.
    var locale = (document.documentElement.getAttribute('lang') || 'ar').toLowerCase();
    var aboutImage = locale.indexOf('en') === 0
        ? '/images/home/unifco-about-card-en.webp'
        : '/images/home/unifco-about-card-ar.webp';

    var normalizeText = function (value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    };

    var isAboutTrigger = function (element) {
        if (!element) return false;
        var text = normalizeText(element.textContent);
        return /discover\s+unifco|about\s+unifco|تعر.{0,4}ف\s+على\s+unifco/i.test(text);
    };

    var looksLikeAbout = function (element) {
        if (!element) return false;
        var text = normalizeText(element.textContent);
        if (/about\s+unifco|من\s+نحن|unifco\s+facilities/i.test(text)) return true;

        var images = element.querySelectorAll ? element.querySelectorAll('img') : [];
        for (var i = 0; i < images.length; i += 1) {
            var image = images[i];
            var marker = (image.getAttribute('alt') || '') + ' ' + (image.getAttribute('src') || '');
            if (/about\s+unifco|unifco\s+facilities|من\s+نحن|about-card|unifco-facility-hero/i.test(marker)) {
                return true;
            }
        }

        return false;
    };

    var fixAboutDialog = function () {
        var selector = '[role="dialog"],.modal,.about-modal,[class*="modal"],[class*="lightbox"],[class*="overlay"]';
        var candidates = Array.prototype.slice.call(document.querySelectorAll(selector));

        candidates.forEach(function (dialog) {
            if (!looksLikeAbout(dialog)) return;

            dialog.classList.add('unifco-about-modal-fixed');
            var images = Array.prototype.slice.call(dialog.querySelectorAll('img'));

            images.forEach(function (image) {
                var alt = image.getAttribute('alt') || '';
                var src = image.getAttribute('src') || '';
                var isAboutImage = images.length === 1 || /about\s+unifco|unifco\s+facilities|من\s+نحن|about-card|unifco-facility-hero/i.test(alt + ' ' + src);
                if (!isAboutImage) return;

                image.onerror = function () {
                    if (image.getAttribute('src') !== aboutImage) image.setAttribute('src', aboutImage);
                };
                if (image.getAttribute('src') !== aboutImage) image.setAttribute('src', aboutImage);
            });
        });
    };

    var style = document.createElement('style');
    style.id = 'unifco-about-modal-fix';
    style.textContent = [
        '.unifco-about-modal-fixed{width:min(94vw,1180px)!important;max-width:min(94vw,1180px)!important;height:auto!important;max-height:92vh!important;overflow:auto!important;padding:0!important;background:transparent!important;}',
        '.unifco-about-modal-fixed img{display:block!important;width:100%!important;max-width:100%!important;height:auto!important;max-height:86vh!important;object-fit:contain!important;border-radius:16px!important;}',
        '@media(max-width:640px){.unifco-about-modal-fixed{width:92vw!important;max-width:92vw!important;}.unifco-about-modal-fixed img{max-height:80vh!important;border-radius:12px!important;}}'
    ].join('');
    if (!document.getElementById(style.id)) document.head.appendChild(style);

    document.addEventListener('click', function (event) {
        var trigger = event.target && event.target.closest ? event.target.closest('a,button') : null;
        if (!isAboutTrigger(trigger)) return;

        window.setTimeout(fixAboutDialog, 0);
        window.setTimeout(fixAboutDialog, 80);
        window.setTimeout(fixAboutDialog, 250);
    });

    if (window.MutationObserver && document.body) {
        var observer = new MutationObserver(function () {
            fixAboutDialog();
            wireClientLoginButton();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    wireClientLoginButton();
    fixAboutDialog();
})();
