(function () {
    'use strict';
    var root = document.getElementById('gn-marketing');
    if (!root) { return; }
    var offer = JSON.parse(root.dataset.offer);
    var status = document.getElementById('gn-marketing-status');
    var format = 'square';
    var logo = null;
    var ready = false;
    var money = function (value) {
        return '\u20ac' + Number(value).toLocaleString('en-GB', { maximumFractionDigits: 2 });
    };
    var copy = {
        el: {
            heading: 'Ένα δώρο για εσάς', discount: 'ΕΚΠΤΩΣΗ ΣΤΟ ΚΑΤΑΣΤΗΜΑ',
            minimum: 'σε αγορές ' + money(offer.minimum) + ' και άνω',
            expiry: offer.days ? 'Ισχύς: ' + offer.days + ' ημέρες από την έκδοση' : 'Χωρίς ημερομηνία λήξης',
            once: 'Ένα κουπόνι ανά πελάτη, για μία μόνο χρήση.',
            eligibility: 'Για επιλέξιμους πελάτες και συνδρομητές.',
            instruction: 'Με τον προσωπικό σας κωδικό στο ταμείο.',
            disclaimer: 'Η εικόνα δεν αποτελεί κουπόνι εξαργύρωσης.'
        },
        en: {
            heading: 'A little extra, for you', discount: 'OFF YOUR IN-STORE PURCHASE',
            minimum: 'on purchases of ' + money(offer.minimum) + ' or more',
            expiry: offer.days ? 'Valid for ' + offer.days + ' days from issue' : 'No expiry date',
            once: 'One coupon per customer, ever. Single use.',
            eligibility: 'For eligible customers and subscribers.',
            instruction: 'Show your personal coupon code at the till.',
            disclaimer: 'This image is not a redeemable coupon.'
        }
    };

    function text(ctx, value, x, y, size, width, weight) {
        do {
            ctx.font = (weight || '400') + ' ' + size + 'px Arial, sans-serif';
            if (ctx.measureText(value).width <= width || size <= 12) { break; }
            size -= 1;
        } while (size > 12);
        ctx.fillText(value, x, y);
    }

    function draw(canvas) {
        var c = copy[canvas.dataset.language];
        var story = format === 'story';
        canvas.height = story ? 1920 : 1080;
        var ctx = canvas.getContext('2d');
        var top = story ? 245 : 70;
        var titleY = story ? 540 : 295;
        var amountY = story ? 805 : 495;
        var footerY = story ? 1240 : 740;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, 1080, canvas.height);
        ctx.fillStyle = offer.color;
        ctx.fillRect(0, 0, 1080, 20);
        ctx.textAlign = 'center';
        if (logo) {
            var scale = Math.min(260 / logo.naturalWidth, 140 / logo.naturalHeight);
            var width = logo.naturalWidth * scale;
            ctx.drawImage(logo, (1080 - width) / 2, top, width, logo.naturalHeight * scale);
        }
        ctx.fillStyle = '#242424';
        text(ctx, offer.brand, 540, top + 185, 34, 920, '700');
        text(ctx, c.heading, 540, titleY + 45, 44, 920, '700');
        ctx.fillStyle = offer.color;
        text(ctx, money(offer.amount), 540, amountY + 65, story ? 250 : 220, 900, '700');
        ctx.fillStyle = '#242424';
        text(ctx, c.discount, 540, amountY + 125, 29, 940, '700');
        text(ctx, c.minimum, 540, amountY + 193, 44, 940, '700');
        ctx.fillStyle = offer.color;
        ctx.fillRect(0, footerY, 1080, 95);
        // Keep offer terms legible even when an administrator chooses a pale accent.
        var hex = offer.color.substring(1);
        if (hex.length === 3) { hex = hex.split('').map(function (v) { return v + v; }).join(''); }
        var rgb = hex.match(/.{2}/g).map(function (v) { return parseInt(v, 16); });
        ctx.fillStyle = (rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000 > 150 ? '#242424' : '#ffffff';
        text(ctx, c.expiry, 540, footerY + 61, 38, 940, '700');
        ctx.fillStyle = '#242424';
        text(ctx, c.once, 540, footerY + 153, 29, 940, '700');
        text(ctx, c.eligibility, 540, footerY + 199, 28, 940);
        text(ctx, c.instruction, 540, footerY + 243, 28, 940);
        ctx.fillStyle = '#555555';
        text(ctx, c.disclaimer, 540, footerY + 285, 23, 940);
        text(ctx, offer.website, 540, story ? 1650 : 1060, 24, 940, '700');
        canvas.setAttribute('aria-label', offer.brand + ': ' + money(offer.amount) + ', ' + c.minimum + '. ' + c.expiry + '. ' + c.once);
    }

    function caption(language) {
        var c = copy[language];
        var details = language === 'el'
            ? 'Αποκλειστικά στο φυσικό κατάστημα. Το ελάχιστο ποσό αγοράς υπολογίζεται πριν από την έκπτωση. Για επιλέξιμους εγγεγραμμένους πελάτες και συνδρομητές της λίστας μας που δεν έχουν λάβει ήδη κουπόνι. Δεν ισχύει στο ηλεκτρονικό κατάστημα. Δεν ανταλλάσσεται με μετρητά.'
            : 'In-store only. The minimum purchase is calculated before the discount. For eligible registered customers and mailing-list subscribers who have not already received a coupon. Not valid at online checkout. Cannot be exchanged for cash.';
        return c.heading + '\n\n' + money(offer.amount) + ' ' + (language === 'el' ? 'έκπτωση ' : 'off ') + c.minimum + ' | ' + offer.brand + '\n' + c.expiry + '. ' + c.once + '\n\n' + c.instruction + '\n' + details + '\n\n' + c.disclaimer + '\n' + offer.website;
    }

    function render() {
        root.querySelectorAll('canvas').forEach(draw);
        root.querySelectorAll('.gn-download-asset').forEach(function (button) { button.disabled = !ready; });
    }
    root.querySelectorAll('input[name="gn-marketing-format"]').forEach(function (radio) {
        radio.addEventListener('change', function () { format = radio.value; render(); });
    });
    ['el', 'en'].forEach(function (language) {
        document.getElementById('gn-caption-' + language).value = caption(language);
    });
    root.querySelectorAll('.gn-download-asset').forEach(function (button) {
        button.addEventListener('click', function () {
            var canvas = root.querySelector('canvas[data-language="' + button.dataset.language + '"]');
            try {
                canvas.toBlob(function (blob) {
                    if (!blob) { status.textContent = 'PNG export failed. Please reload and try again.'; return; }
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'in-store-coupon-' + button.dataset.language + '-' + format + '.png';
                    link.click();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                    status.textContent = 'PNG downloaded (' + canvas.width + ' × ' + canvas.height + ').';
                }, 'image/png');
            } catch (error) { status.textContent = 'PNG export blocked. Choose a logo hosted in this site\'s Media Library.'; }
        });
    });
    root.querySelectorAll('.gn-copy-caption').forEach(function (button) {
        button.addEventListener('click', async function () {
            var field = document.getElementById('gn-caption-' + button.dataset.language);
            try { await navigator.clipboard.writeText(field.value); status.textContent = 'Caption copied.'; }
            catch (error) { field.focus(); field.select(); status.textContent = 'Clipboard unavailable. Caption selected.'; }
        });
    });
    if (offer.logo) {
        var image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = function () { logo = image; ready = true; render(); status.textContent = ''; };
        image.onerror = function () { status.textContent = 'Logo could not load. Choose a logo hosted in this site\'s Media Library.'; };
        image.src = offer.logo;
    } else { ready = true; render(); status.textContent = ''; }
})();
