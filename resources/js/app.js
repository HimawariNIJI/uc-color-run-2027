/* UC Color Run 2027 - interaksi halaman */
// Library kamera cukup berat: hanya dimuat di halaman scanner.
if (document.getElementById('scanner')) {
    import('./racepack-scanner.js');
}
(function () {
    'use strict';

    const pad = (n) => String(n).padStart(2, '0');
    const rupiah = (n) => 'Rp. ' + Number(n).toLocaleString('id-ID');
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    /* ---------- Countdown (Race Schedule) ---------- */
    const cd = document.getElementById('countdown');
    if (cd) {
        const target = new Date(cd.dataset.target).getTime();
        const el = (u) => cd.querySelector('[data-unit="' + u + '"]');

        const tick = () => {
            let diff = Math.max(0, target - Date.now());
            const days = Math.floor(diff / 86400000); diff %= 86400000;
            const hours = Math.floor(diff / 3600000); diff %= 3600000;
            const minutes = Math.floor(diff / 60000); diff %= 60000;
            const seconds = Math.floor(diff / 1000);

            el('days').textContent = pad(days);
            el('hours').textContent = pad(hours);
            el('minutes').textContent = pad(minutes);
            el('seconds').textContent = pad(seconds);
        };
        tick();
        setInterval(tick, 1000);
    }

    /* ---------- Pilih kategori (Race Category) ---------- */
    const section = document.querySelector('.category');
    if (section) {
        const data = JSON.parse(section.dataset.categories || '{}');
        const cards = section.querySelectorAll('.cat-card');
        const feeList = document.getElementById('fee-list');
        const raceInfo = document.getElementById('race-info');
        const raceDay = raceInfo.querySelector('p').innerHTML; // blok "Race Day" tetap

        const select = (key) => {
            const cat = data[key];
            if (!cat) return;

            cards.forEach((c) => {
                const on = c.dataset.key === key;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            feeList.innerHTML = cat.fee
                .map((f) => '<p>' + esc(f.label) + '<br>' + rupiah(f.price) + '</p>')
                .join('');

            raceInfo.innerHTML =
                '<p>' + raceDay + '</p>' +
                '<p>' + cat.info.map(esc).join('<br>') + '</p>';
        };

        cards.forEach((c) => c.addEventListener('click', () => select(c.dataset.key)));
    }
})();