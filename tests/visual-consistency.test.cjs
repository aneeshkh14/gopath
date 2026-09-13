const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const cssom=require('@acemir/cssom');
const {JSDOM}=require('jsdom');
// These are stylesheet/DOM checks, not browser geometry or device screenshots.
function rulesAt(list,width){return Array.from(list).map(r=>{
 if(r.media){const q=r.media.mediaText;if(q.includes('prefers-'))return '';const min=q.match(/min-width:\s*(\d+)px/),max=q.match(/max-width:\s*(\d+)px/);return (min&&width<+min[1])||(max&&width>+max[1])?'':rulesAt(r.cssRules,width);}
 return r.cssText;
}).join('\n');}
const sources=['public/css/gep-public.css','public/css/gep-dashboard.css','public/css/gep-auth.css','public/css/gep-theme.css','public/css/gep-layout.css'].map(p=>fs.readFileSync(p,'utf8'));
const inline=[...fs.readFileSync('templates/dashboard/main.php','utf8').matchAll(/<style>([\s\S]*?)<\/style>/g)].map(m=>m[1]).join('\n');
const sheets=[...sources,inline].map(s=>cssom.parse(s));
for(const width of [320,390,600,768,820,1024,1366,1920])test(`dashboard stylesheet keeps gutters, full titles and usable actions at ${width}px`,t=>{
 const styles=sheets.map(s=>rulesAt(s.cssRules,width)).join('\n');
 const dom=new JSDOM(`<style>${styles}</style><div id="gep-page-wrapper"><main id="gep-main-content" class="gep-dashboard-content"><div class="gep-main-inner gep-full-width-view"><div class="gep-main-inner"><section class="gep-study-start"><div class="gep-study-focus"><div class="gep-study-actions"><a class="gep-study-button">Continue test</a></div></div><div class="gep-study-stats"></div></section><div class="gep-test-card-grid"><a class="gep-hcard"><div class="gep-hcard-body"><h4>Long bilingual title संस्कृत</h4></div></a></div></div></div></main></div>`);
 t.after(()=>dom.window.close());const style=s=>dom.window.getComputedStyle(dom.window.document.querySelector(s));
 assert.notEqual(style('.gep-full-width-view').padding,'0px');
 assert.equal(style('.gep-full-width-view').maxWidth,'1440px');
 assert.equal(style('.gep-main-inner .gep-main-inner').padding,'0px');
 assert.equal(style('.gep-test-card-grid').display,'grid');
 assert.equal(style('.gep-hcard-body h4').display,'block');
 assert.equal(style('.gep-hcard-body h4').overflow,'visible');
 assert.equal(style('.gep-study-button').minHeight,'48px');
 assert.equal(style('.gep-study-focus').flexDirection,width<=900?'column':'row');
 assert.equal(style('.gep-study-stats').gridTemplateColumns,width<=600?'1fr':'repeat(3, minmax(0, 1fr))');
});
for(const status of ['success','failed'])test(`${status} payment page has its own shared layout without visiting another page`,t=>{
 const dom=new JSDOM(`<style>${sources.at(-1)}</style><div class="gep-status-page-sovereign ${status}"><div class="status-card-glass"><div class="status-icon-wrap ${status}"></div></div></div>`);t.after(()=>dom.window.close());
 const style=s=>dom.window.getComputedStyle(dom.window.document.querySelector(s));assert.equal(style('.gep-status-page-sovereign').display,'flex');assert.equal(style('.status-card-glass').maxWidth,'560px');assert.equal(style('.status-icon-wrap').width,'72px');
});
function luminance(hex){const c=hex.replace('#','').match(/../g).map(n=>parseInt(n,16)/255).map(n=>n<=.04045?n/12.92:((n+.055)/1.055)**2.4);return c[0]*.2126+c[1]*.7152+c[2]*.0722;}
for(const [theme,selector] of [['light',':root'],['dark',':root[data-gep-theme="dark"]']])test(`${theme} theme text and primary action tokens meet 4.5:1 contrast`,()=>{
 const rule=Array.from(cssom.parse(sources[3]).cssRules).find(r=>r.selectorText?.split(',').some(s=>s.trim()===selector));assert.ok(rule);
 const token=k=>rule.style.getPropertyValue('--gep-c-'+k).trim();
 for(const [a,b] of [['text','surface'],['text-muted','surface'],['accent-text','accent']]){const x=luminance(token(a)),y=luminance(token(b));assert.ok((Math.max(x,y)+.05)/(Math.min(x,y)+.05)>=4.5,`${a}/${b}`);}
});
