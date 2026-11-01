import { initAccordions } from './modules/accordion';
import { initCarousels } from './modules/carousel';
import { initCollapsibles } from './modules/collapsible';
import { initCountdowns } from './modules/countdown';
import { initDismissibles } from './modules/dismissible';
import { initDropdowns } from './modules/dropdown';
import { initMobileMenu } from './modules/mobile-menu';
import { initRegionSelects } from './modules/region-select';
import { initSubmitGuards } from './modules/submit-guard';
import { initTabs } from './modules/tabs';

[
    initDropdowns,
    initMobileMenu,
    initCollapsibles,
    initTabs,
    initAccordions,
    initCarousels,
    initCountdowns,
    initRegionSelects,
    initSubmitGuards,
    initDismissibles,
].forEach((init) => init(document));
