import { initDismissibles } from './modules/dismissible';
import { initDropdowns } from './modules/dropdown';
import { initSubmitGuards } from './modules/submit-guard';
import { initAutoSubmitForms } from './modules/admin/auto-submit';
import { initChartTooltips } from './modules/admin/chart-tooltip';
import { initLineCharts } from './modules/admin/line-chart';
import { initAdminModal } from './modules/admin/modal';
import { initPasswordToggles } from './modules/admin/password-toggle';

[
    initDropdowns,
    initDismissibles,
    initSubmitGuards,
    initPasswordToggles,
    initAutoSubmitForms,
    initAdminModal,
    initChartTooltips,
    initLineCharts,
].forEach((init) => init(document));

// The scanner (and its QR decoder) is only downloaded on the QR Scanner page.
if (document.querySelector('[data-qr-scanner]')) {
    import('./modules/admin/qr-scanner').then(({ initQrScanner }) => initQrScanner(document));
}
