/**
 * Dependent province → city selects.
 *
 * Markup: [data-region-select] contains a <script type="application/json" data-region-data> map of
 * province → cities, a [data-region-province] select and a [data-region-city] select whose
 * data-placeholder is used as the empty option. The server renders the initial city options.
 */
export function initRegionSelects(root) {
    root.querySelectorAll('[data-region-select]').forEach((container) => {
        const source = container.querySelector('[data-region-data]');
        const province = container.querySelector('[data-region-province]');
        const city = container.querySelector('[data-region-city]');

        if (!source || !province || !city) {
            return;
        }

        const regions = JSON.parse(source.textContent);

        const renderCities = () => {
            const cities = regions[province.value] ?? [];
            const placeholder = new Option(city.dataset.placeholder, '', true, true);

            placeholder.disabled = true;
            city.replaceChildren(placeholder, ...cities.map((name) => new Option(name, name)));
            city.disabled = cities.length === 0;
        };

        city.disabled = province.value === '';
        province.addEventListener('change', renderCities);
    });
}
