import { initAccordions } from './modules/accordion';
import { initCarousels } from './modules/carousel';
import { initCollapsibles } from './modules/collapsible';
import { initCountdowns } from './modules/countdown';
import { initDismissibles } from './modules/dismissible';
import { initDropdowns } from './modules/dropdown';
import { initImageUploads } from './modules/image-upload';
import { initMobileMenu } from './modules/mobile-menu';
import { initPaymentStatus } from './modules/payment-status';
import { initRegionSelects } from './modules/region-select';
import { initReveal } from './modules/reveal';
import { initSubmitGuards } from './modules/submit-guard';
import { initTabs } from './modules/tabs';

[
    initReveal,
    initDropdowns,
    initMobileMenu,
    initCollapsibles,
    initTabs,
    initAccordions,
    initCarousels,
    initCountdowns,
    initRegionSelects,
    initImageUploads,
    initSubmitGuards,
    initDismissibles,
    initPaymentStatus,
].forEach((init) => init(document));
