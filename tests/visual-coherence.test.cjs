const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const cssom=require('@acemir/cssom');
const Specificity=require('@bramus/specificity').default;
const {JSDOM}=require('jsdom');
const {setup,inline}=require('./helpers.cjs');
const read=p=>fs.readFileSync(p,'utf8');
const baseCss=['public/css/gep-dashboard.css','public/css/gep-theme.css','public/css/gep-layout.css','public/css/gep-profile.css','public/css/gep-learning.css'].map(read).join('\n');
const purchaseCss=[...read('templates/dashboard/my-purchases.php').matchAll(/<style>([\s\S]*?)<\/style>/g)].map(m=>m[1]).join('\n');
function atWidth(rules,width){return Array.from(rules).flatMap(r=>{if(!r.media)return [r];const q=r.media.mediaText;if(q.includes('prefers-'))return [];const min=q.match(/min-width:\s*(\d+)px/),max=q.match(/max-width:\s*(\d+)px/);return (min&&width<+min[1])||(max&&width>+max[1])?[]:atWidth(r.cssRules,width);});}
function palette(mode){const tokens={};for(const r of cssom.parse(read('public/css/gep-theme.css')).cssRules){if(r.selectorText===':root'||(mode==='dark'&&r.selectorText?.startsWith(':root[data-gep-theme="dark"]'))){for(let i=0;i<r.style.length;i++){const k=r.style[i];if(k.startsWith('--'))tokens[k]=r.style.getPropertyValue(k);}}}return tokens;}
function winner(el,property,rules){let best=null;for(const r of rules){if(!r.selectorText||!r.style[property])continue;for(const weight of Specificity.calculate(r.selectorText)){if(weight.selectorString().includes('::')||!el.matches(weight.selectorString()))continue;const v={weight,important:r.style.getPropertyPriority(property)==='important'?1:0,value:r.style[property]};if(!best||v.important>best.important||(v.important===best.important&&Specificity.compare(v.weight,best.weight)>=0))best=v;}}return best?.value;}
function rgb(s){return s.startsWith('#')?s.slice(1).match(/../g).map(v=>parseInt(v,16)):s.match(/[\d.]+/g).slice(0,3).map(Number);}
function luminance(c){c=rgb(c).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;});return c[0]*.2126+c[1]*.7152+c[2]*.0722;}
function contrast(f,b){const a=luminance(f),c=luminance(b);return (Math.max(a,c)+.05)/(Math.min(a,c)+.05);}
const markup=`<div id="gep-page-wrapper"><main class="gep-main-inner"><div class="gep-profile-wrapper"><div class="gep-account-card gep-account-main"><section class="gep-form-section gep-account-panel active"><div class="gep-account-fields"><div class="gep-form-group"><label>Phone number</label><input class="gep-input"></div></div><p class="gep-info-text">Language help</p></section></div><div class="gep-account-sidebar"><div class="gep-account-progress"></div><nav class="gep-profile-nav"><a>Learning goals</a><a class="active">Personal details</a></nav></div><div class="gep-account-layout"></div></div><div class="gep-learning-catalog"><header class="gep-learning-header"><p class="hero-desc-premium">Course introduction</p><div class="gep-nav-filters"><button class="filter-btn active">All programs</button></div></header><div class="gep-premium-grid"></div><a class="btn-action-elite btn-primary">Launch</a></div><div class="purchase-attempts-block"><span class="attempts-label">Attempts</span><span class="attempts-value">1 of 2</span></div><div class="gep-predictor-metrics"></div></main></div>`;
for(const mode of ['light','dark'])test(`${mode} account, course and purchase labels retain contrast under legacy CSS`,t=>{
 const tokens=palette(mode);const css=(baseCss+'\n'+purchaseCss).replace(/var\((--[\w-]+)(?:,\s*[^)]*)?\)/g,(all,k)=>tokens[k]||all);
 const dom=new JSDOM(`<html data-gep-theme="${mode}"><body>${markup}</body></html>`);t.after(()=>dom.window.close());const rules=atWidth(cssom.parse(css).cssRules,1366);const el=s=>dom.window.document.querySelector(s);
 for(const selector of ['.gep-form-group label','.gep-info-text','.gep-profile-nav a','.gep-profile-nav a.active','.hero-desc-premium']){
  const color=winner(el(selector),'color',rules);assert.ok(contrast(color,tokens['--gep-c-surface'])>=4.5,`${selector}: ${color}`);
 }
 for(const selector of ['.filter-btn.active','.btn-action-elite']){const color=winner(el(selector),'color',rules),bg=winner(el(selector),'background',rules);assert.ok(contrast(color,bg)>=4.5,`${selector}: ${color} on ${bg}`);}
 for(const selector of ['.attempts-label','.attempts-value'])assert.ok(contrast(winner(el(selector),'color',rules),tokens['--gep-c-surface-2'])>=4.5,selector);
 assert.equal(winner(el('.gep-account-panel'),'background',rules),'transparent','The form must not acquire a second white card from the theme.');
 assert.deepEqual(rgb(winner(el('.gep-account-main'),'background',rules)),rgb(tokens['--gep-c-surface']));
});
for(const width of [320,390,600,768,820,1024,1366,1920])test(`account and learning layouts have usable rules at ${width}px`,t=>{
 const dom=new JSDOM(markup);t.after(()=>dom.window.close());const rules=atWidth(cssom.parse(baseCss).cssRules,width);const value=(s,p)=>winner(dom.window.document.querySelector(s),p,rules);
 assert.equal(value('.gep-account-layout','grid-template-columns'),width<=900?'1fr':width<=1100?'210px minmax(0,1fr)':'236px minmax(0,1fr)');
 assert.equal(value('.gep-account-fields','grid-template-columns'),width<=600?'1fr':'repeat(2,minmax(0,1fr))');
 assert.equal(value('.gep-input','min-height'),'44px');
 assert.equal(value('.gep-nav-filters','flex-wrap'),'wrap');
 assert.notEqual(value('.gep-account-progress','display'),'none','Completion should remain available on phones.');
 assert.equal(value('.gep-predictor-metrics','grid-template-columns'),width<=600?'1fr':'repeat(2,minmax(0,1fr))');
 if(width<=600)assert.equal(value('.gep-premium-grid','grid-template-columns'),'1fr');
});
test('profile tabs support keyboard navigation while preserving unsaved form fields',async t=>{
 const keys=['personal','academic','security','telemetry'];
 const html='<nav class="gep-profile-nav">'+keys.map((k,i)=>`<a href="#${k}" data-tab="${k}" role="tab" aria-selected="${!i}" tabindex="${i?-1:0}">${k}</a>`).join('')+'</nav><form id="gep-profile-update-form">'+keys.map((k,i)=>`<section id="tab-${k}" class="gep-form-section ${i?'':'active'}"><input name="${k}" value="Unsaved"></section>`).join('')+'<div class="gep-account-actions"><button type="submit">Save</button></div></form>';
 const p=await setup(html,inline('templates/dashboard/profile.php','Password Toggle'));t.after(p.close);
 p.$('[data-tab="personal"]').trigger(p.$.Event('keydown',{key:'End'}));assert.equal(p.w.document.activeElement.dataset.tab,'telemetry');assert.equal(p.$('[aria-selected="true"]').length,1);assert.equal(p.$('#tab-telemetry').hasClass('active'),true);assert.equal(p.$('.gep-account-actions').css('display'),'none');
 p.$('[data-tab="telemetry"]').trigger(p.$.Event('keydown',{key:'ArrowRight'}));assert.equal(p.w.document.activeElement.dataset.tab,'personal');assert.notEqual(p.$('.gep-account-actions').css('display'),'none');assert.equal(p.$('[name="personal"]').val(),'Unsaved');assert.deepEqual(p.errors,[]);
});
