const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const cssom=require('@acemir/cssom');
const Specificity=require('@bramus/specificity').default;
const {JSDOM}=require('jsdom');
const read=p=>fs.readFileSync(p,'utf8');
const template=read('templates/payment/checkout.php');
const portalCss=read('templates/portal-layout.php').match(/<style>\s*\n([\s\S]*?)<\/style>/)[1].replace(/<\?php[\s\S]*?\?>/g,'auto');
const css=['public/css/gep-public.css','public/css/gep-dashboard.css','public/css/gep-auth.css','public/css/gep-theme.css','public/css/gep-layout.css'].map(read).join('\n')+'\n'+portalCss+'\n'+template.match(/<style>([\s\S]*?)<\/style>/)[1];
// Use the template's actual elements, including one iteration of the tier branch.
const markup=template.split('<style>')[0].replace(/<\?php[\s\S]*?\?>/g,'');
function palette(mode){const tokens={};for(const r of cssom.parse(css).cssRules){if(r.selectorText===':root'||(mode==='dark'&&r.selectorText?.startsWith(':root[data-gep-theme="dark"]'))){for(let i=0;i<r.style.length;i++){const key=r.style[i];if(key.startsWith('--'))tokens[key]=r.style.getPropertyValue(key);}}}return tokens;}
function atWidth(rules,width){return [...rules].flatMap(r=>{if(!r.media)return [r];const q=r.media.mediaText;if(q.includes('prefers-'))return [];const min=q.match(/min-width:\s*(\d+)px/),max=q.match(/max-width:\s*(\d+)px/);return (min&&width<+min[1])||(max&&width>+max[1])?[]:atWidth(r.cssRules,width);});}
function fixture(t,mode,width=1366){
 const tokens=palette(mode);let resolved=css;
 for(let i=0;i<4;i++)resolved=resolved.replace(/var\((--[\w-]+)(?:,\s*([^)]*))?\)/g,(all,key,fallback)=>tokens[key]||fallback||all);
 const rules=atWidth(cssom.parse(resolved).cssRules,width);
 const dom=new JSDOM(`<html data-gep-theme="${mode}"><body><div id="gep-page-wrapper"><div class="gep-dashboard-container gep-sovereign-active"><main id="gep-main-content" class="gep-dashboard-content"><div class="gep-main-inner">${markup}</div></main></div></div></body></html>`);t.after(()=>dom.window.close());
 const el=s=>dom.window.document.querySelector(s);
 // Rank importance, selector specificity and source order explicitly: JSDOM's
 // cascade does not reproduce the legacy !important overrides on this portal.
 function value(element,property,pseudo=''){
  const properties=property==='background-color'?['background','background-color']:[property];let best;
  for(const r of rules){if(!r.selectorText)continue;for(const weight of Specificity.calculate(r.selectorText)){
   const selector=weight.selectorString();
   if(pseudo?!selector.endsWith(pseudo):selector.includes('::'))continue;
   if(!element.matches(pseudo?selector.slice(0,-pseudo.length):selector))continue;
   for(let i=0;i<r.style.length;i++){const p=r.style[i];if(!properties.includes(p))continue;const candidate={weight,important:r.style.getPropertyPriority(p)==='important'?1:0,value:r.style.getPropertyValue(p)};
    if(!best||candidate.important>best.important||(candidate.important===best.important&&Specificity.compare(weight,best.weight)>=0))best=candidate;
   }
  }}
  if(!pseudo)for(const p of properties){const inline=element.style.getPropertyValue(p);if(inline&&(!best||!best.important||element.style.getPropertyPriority(p)==='important'))return inline;}
  return best?.value;
 }
 function color(element){const own=value(element,'color');return own&&own!=='inherit'?own:element.parentElement?color(element.parentElement):tokens['--gep-c-text'];}
 function background(element){if(!element)return rgba(tokens['--gep-c-bg']);const own=rgba(value(element,'background-color')||'transparent');if(own[3]===1)return own;const parent=background(element.parentElement);return own.slice(0,3).map((c,i)=>c*own[3]+parent[i]*(1-own[3])).concat(1);}
 return {el,value,color,background,tokens};
}
function rgba(s){if(s==='transparent'||s==='none')return [0,0,0,0];if(s.startsWith('#')){let hex=s.slice(1);if(hex.length===3)hex=[...hex].map(c=>c+c).join('');return [...hex.slice(0,6).match(/../g).map(v=>parseInt(v,16)),hex.length===8?parseInt(hex.slice(6),16)/255:1];}assert.match(s,/^rgba?\(/,`Unexpected color ${s}`);const a=s.match(/[\d.]+/g).map(Number);return a.length===3?a.concat(1):a;}
function contrast(f,b){const lum=c=>c.slice(0,3).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((v,n,i)=>v+n*[.2126,.7152,.0722][i],0);const a=lum(rgba(f)),c=lum(b);return (Math.max(a,c)+.05)/(Math.min(a,c)+.05);}
for(const mode of ['light','dark'])test(`${mode} checkout copy and controls contrast with their actual surfaces`,t=>{
 const p=fixture(t,mode);
 for(const selector of ['.header-badge','.gep-checkout-header h1','.gep-checkout-header h1 span','.gep-checkout-header p','.card-header h2','.item-count','.item-type','.item-title','.summary-row .label','.summary-row .value','.final-price-wrap .currency','#gep-final-amount','.gep-coupon-label','#gep-coupon-code','#gep-coupon-status','#gep-apply-coupon','#gep-pay-button','.secure-badge','.payment-methods','.info-text h2','.info-text p','.gep-checkout-pricing-tiers legend','.gep-tier-choice','.gep-tier-price']){
  const node=p.el(selector);assert.ok(node,`Missing ${selector}`);const ratio=contrast(p.color(node),p.background(node));assert.ok(ratio>=4.5,`${mode} ${selector}: ${ratio.toFixed(2)}:1`);
 }
 p.el('.gep-tier-label-wrap').classList.add('is-selected');
 assert.ok(contrast(p.value(p.el('#gep-coupon-code'),'color','::placeholder'),p.background(p.el('#gep-coupon-code')))>=4.5,'Coupon placeholder remains legible');
 for(const selector of ['.gep-tier-choice','.gep-tier-price'])assert.ok(contrast(p.color(p.el(selector)),p.background(p.el(selector)))>=4.5,`${selector} selected`);
 p.el('#gep-pay-button').disabled=true;
 assert.ok(contrast(p.color(p.el('#gep-pay-button')),p.background(p.el('#gep-pay-button')))>=4.5,'Busy payment action retains readable text');
 for(const selector of ['.gep-checkout-header','.gep-checkout-grid','.checkout-info-column']){
  assert.deepEqual(p.background(p.el(selector)),p.background(p.el('.gep-sovereign-checkout-wrap')),`${selector} must not acquire its own card surface`);
 }
});
for(const width of [320,390,600,768,820,1024,1366,1920])test(`checkout fits its columns and preserves order at ${width}px`,t=>{
 const p=fixture(t,'dark',width),get=(s,k)=>p.value(p.el(s),k);
 for(const selector of ['.gep-checkout-grid > div','.checkout-info-column','.item-info','.info-text','#gep-coupon-code'])assert.equal(parseFloat(get(selector,'min-width')),0,`${selector} can shrink for long text`);
 assert.notEqual(get('.checkout-info-column','order'),'-1','Payment summary stays before secondary information');
 assert.ok(get('.info-item-glass','min-width')===undefined||parseFloat(get('.info-item-glass','min-width'))===0,'No fixed minimum forces cards offscreen');
 if(width<=992)assert.match(get('.gep-checkout-grid','grid-template-columns'),/^(1fr|minmax\(0(?:px)?,\s*1fr\))$/);
 else assert.match(get('.gep-checkout-grid','grid-template-columns'),/^minmax\(0(?:px)?,\s*[\d.]+fr\) minmax\(0(?:px)?,\s*[\d.]+fr\)$/);
 for(const selector of ['#gep-coupon-code','#gep-apply-coupon','#gep-pay-button'])assert.ok(parseInt(get(selector,'min-height'),10)>=44,`${selector} touch target`);
 assert.equal(get('.secure-footer','flex-wrap'),'wrap');
 if(width<=480)assert.equal(get('.gep-coupon-section-modern .input-wrap','flex-direction'),'column');
});
