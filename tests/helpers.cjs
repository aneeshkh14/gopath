const fs = require('node:fs');
const {JSDOM, VirtualConsole} = require('jsdom');
const jquery = require('jquery');
const tick = () => new Promise(resolve => setTimeout(resolve, 15));
async function setup(html, script, globals = {}, before) {
    const errors = [];
    const vc = new VirtualConsole();
    vc.on('jsdomError', e => { if (!String(e.message).includes('navigation')) errors.push(e); });
    const dom = new JSDOM(html, {url: 'https://portal.example/dashboard/', runScripts: 'outside-only', virtualConsole: vc, pretendToBeVisual: true});
    const w = dom.window, $ = jquery(w), requests = [];
    w.$ = w.jQuery = $; $.fx.off = true;
    Object.assign(w, {gep_ajax: {ajax_url: '/ajax', nonce: 'test-nonce'}, GEP_Auth: {ajaxurl: '/ajax'}, alert: text => { w.lastAlert = text; }}, globals);
    $.ajax = function(options) {
        const d = $.Deferred();
        requests.push({options, resolve(data) { options.success?.(data); d.resolve(data); options.complete?.(); }, reject() {options.error?.({},'error'); d.reject({}); options.complete?.();}});
        return d.promise();
    };
    $.post = (url,data,success) => $.ajax({url,type:'POST',data,success});
    if (before) before(w, $);
    w.eval(script.includes('\n') ? script : fs.readFileSync(script,'utf8'));
    await tick();
    return {dom,w,$,requests,errors,close: () => dom.window.close()};
}
function inline(file, match) {
    return [...fs.readFileSync(file,'utf8').matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)].map(m => m[1]).find(s => s.includes(match));
}
module.exports = {setup,tick,inline};
