const {test} = require('node:test'); const assert = require('node:assert/strict');
const {setup,inline,tick} = require('./helpers.cjs');
const shell=`<div id="gep-page-wrapper"><aside class="gep-dashboard-sidebar"><button id="gep-menu-close">Close menu</button><nav class="gep-dashboard-nav"><a href="/dashboard/" class="active">Dashboard</a></nav></aside><main class="gep-dashboard-content"><button id="gep-menu-toggle">Menu</button><form id="gep-header-search-form"><input id="gep-header-search-input"></form><div class="gep-header-notification-wrapper"><button id="gep-notif-trigger">Notifications</button><div id="gep-notif-panel"><a id="gep-mark-all-read">Mark all read</a><div role="button" tabindex="0" class="notif-item unread" data-id="2">An update</div></div><span class="gep-notif-count">1</span></div><div class="gep-main-inner"><a class="gep-hcard"><h4>Sanskrit Grammar</h4></a><a class="gep-test-card"><h4>Paper 1 Reasoning</h4></a><div class="gep-dashboard-carousel"><div class="gep-carousel-slide"><a>First</a></div><div class="gep-carousel-slide"><a>Second</a></div><button class="gep-carousel-dot" data-index="0">Slide 1</button><button class="gep-carousel-dot" data-index="1">Slide 2</button></div></div></main></div>`;
async function page(width=390) {return setup(shell,'public/js/gep-dashboard.js',{},w=>{w.innerWidth=width;});}
test('mobile drawer opens once, isolates content, closes with Escape and restores focus',async t=>{
 const p=await page();t.after(p.close);assert.equal(p.$('.gep-dashboard-sidebar').prop('inert'),true);
 p.$('#gep-menu-toggle').trigger('click');assert.equal(p.$('.gep-dashboard-sidebar').hasClass('active'),true);assert.equal(p.$('#gep-menu-toggle').attr('aria-expanded'),'true');assert.equal(p.$('.gep-dashboard-content').prop('inert'),true);
 assert.equal(p.w.document.activeElement.id,'gep-menu-close');
 p.w.document.dispatchEvent(new p.w.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 assert.equal(p.$('.gep-dashboard-sidebar').hasClass('active'),false);assert.equal(p.w.document.activeElement.id,'gep-menu-toggle');assert.deepEqual(p.errors,[]);
});
test('resizing an open drawer releases desktop content and scrolling',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-menu-toggle').trigger('click');p.w.innerWidth=1280;p.w.dispatchEvent(new p.w.Event('resize'));
 assert.equal(p.$('.gep-dashboard-sidebar').prop('inert'),false);assert.equal(p.$('.gep-dashboard-content').prop('inert'),false);assert.equal(p.$('html').hasClass('gep-menu-open'),false);
});
test('search includes horizontal cards, reports no matches, and restores cards on clearing',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-header-search-input').val('Sanskrit').trigger('input');
 assert.notEqual(p.$('.gep-hcard').css('display'),'none');assert.equal(p.$('.gep-test-card').css('display'),'none');assert.match(p.$('#gep-search-feedback').text(),/1 matching/);
 p.$('#gep-header-search-input').val('missing').trigger('input');assert.match(p.$('#gep-search-feedback').text(),/No matches/);
 p.$('#gep-header-search-input').val('').trigger('input');assert.notEqual(p.$('.gep-test-card').css('display'),'none');
});
test('notification panel stays open for internal actions and closes on Escape',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-notif-trigger').trigger('click');p.$('#gep-mark-all-read').trigger('click');assert.equal(p.$('#gep-notif-panel').hasClass('active'),true);
 p.requests[0].resolve({success:true,data:{success:true}});assert.equal(p.$('.notif-item').hasClass('unread'),false);
 p.w.document.dispatchEvent(new p.w.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));assert.equal(p.$('#gep-notif-trigger').attr('aria-expanded'),'false');
});
test('carousel exposes only the selected slide to keyboard and screen readers',async t=>{
 const p=await page();t.after(p.close);assert.equal(p.$('.gep-carousel-slide').eq(1).prop('inert'),true);p.$('.gep-carousel-dot').eq(1).trigger('click');
 assert.equal(p.$('.gep-carousel-slide').eq(0).attr('aria-hidden'),'true');assert.equal(p.$('.gep-carousel-dot').eq(1).attr('aria-pressed'),'true');
});
const hub=`<select id="gep-cat-select"><option value="0">All</option><option value="1">Sanskrit</option><option value="2">Paper 1</option></select><select id="gep-type-select"><option value="all">All</option><option value="single">Single</option><option value="random">Custom</option></select><input id="gep-test-search"><input id="gep-header-search-input"><button id="gep-clear-filters">Clear</button><p id="gep-filter-count"></p><div id="gep-filter-empty" hidden></div><div class="gep-category-section"><div class="gep-asset-card" data-cat="1" data-type="random"><h4>संस्कृत Practice</h4></div></div><div class="gep-category-section"><div class="gep-asset-card" data-cat="2" data-type="single"><h4>Reasoning</h4></div></div>`;
test('combined catalogue filters, Unicode search, no-results and clear filters',async t=>{
 const p=await setup(hub,inline('templates/dashboard/browse-tests.php','function filterAssets'));t.after(p.close);
 p.$('#gep-test-search').val('संस्कृत').trigger('input');assert.equal(p.$('#gep-filter-count').text(),'1 test shown');
 p.$('#gep-type-select').val('single').trigger('change');assert.equal(p.$('#gep-filter-empty').prop('hidden'),false);
 p.$('#gep-clear-filters').trigger('click');assert.equal(p.$('#gep-filter-count').text(),'2 tests shown');assert.equal(p.$('#gep-filter-empty').prop('hidden'),true);assert.deepEqual(p.errors,[]);
});
