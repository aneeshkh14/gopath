const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const cssom=require('@acemir/cssom');const {JSDOM}=require('jsdom');
const theme=fs.readFileSync('public/css/gep-theme.css','utf8');
const exam=fs.readFileSync('public/css/gep-exam.css','utf8');
function colours(mode){const tokens={};for(const r of cssom.parse(theme).cssRules){if(r.selectorText===':root'||(mode==='dark'&&r.selectorText?.includes(':root[data-gep-theme="dark"],'))){for(let i=0;i<r.style.length;i++){const k=r.style[i];if(k.startsWith('--'))tokens[k]=r.style.getPropertyValue(k);}}}return tokens;}
function lum(rgb){const c=rgb.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;});return c[0]*.2126+c[1]*.7152+c[2]*.0722;}
function contrast(a,b){const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);}
for(const mode of ['light','dark'])test(`${mode} exam text, reference controls and status numbers remain readable`,t=>{
 const tokens=colours(mode);assert.ok(tokens['--gep-c-text']);
 // Resolve the known theme tokens for JSDOM; this checks the real selector
 // cascade rather than claiming a full browser CSS-variable/layout engine.
 const css=(exam+'\n'+theme).replace(/var\((--[\w-]+)(?:,\s*[^)]*)?\)/g,(all,key)=>tokens[key]||all);
 const dom=new JSDOM(`<html data-gep-theme="${mode}"><head><style>${css}</style></head><body><main class="gep-exam-fullscreen-container"><div class="gep-q-content"><p><span id="question" style="color: ${mode==='dark'?'#111827':'#ffffff'}">Question</span></p></div><div class="gep-popup-modal-content"><h2>Instructions</h2><div class="gep-popup-modal-body"><strong id="instruction">Read this instruction</strong></div></div><div class="gep-sidebar-bottom-actions"><button class="gep-btn-info">Instructions</button><button class="gep-btn-primary">Submit</button></div>${['answered','not-answered','flagged','not-visited'].map(s=>`<button class="gep-palette-btn ${s}">12</button>`).join('')}</main></body></html>`);t.after(()=>dom.window.close());const style=s=>dom.window.getComputedStyle(dom.window.document.querySelector(s));
 const surface=mode==='dark'?'rgb(20, 26, 39)':'rgb(255, 255, 255)';
 for(const sel of ['#question','#instruction','.gep-popup-modal-content h2'])assert.ok(contrast(style(sel).color,surface)>=4.5,sel);
 for(const sel of ['.gep-btn-info','.gep-btn-primary',...['answered','not-answered','flagged','not-visited'].map(s=>'.gep-palette-btn.'+s)])assert.ok(contrast(style(sel).color,style(sel).backgroundColor)>=4.5,sel);
});
