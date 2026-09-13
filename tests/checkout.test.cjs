const {test} = require('node:test');
const assert = require('node:assert/strict');
const {setup,tick} = require('./helpers.cjs');
const markup = `<label class="gep-tier-label-wrap"><input type="radio" name="selected_attempts_tier" value="1" data-price="1200" checked></label><label class="gep-tier-label-wrap"><input type="radio" name="selected_attempts_tier" value="3" data-price="2000"></label><input id="gep-coupon-code"><button id="gep-apply-coupon">APPLY</button><p id="gep-coupon-status" role="status"></p><span id="gep-final-amount">1,200.00</span><button id="gep-pay-button">Pay</button>`;
async function page() {
  const gateways=[];
  const p=await setup(markup,'public/js/gep-checkout.js',{GEP_Checkout:{ajaxurl:'/ajax',nonce:'test',item_id:2,item_type:'test',key_id:'mock'},Razorpay:function(options){ this.options=options; this.handlers={}; this.on=(e,fn)=>this.handlers[e]=fn; this.open=()=>{}; gateways.push(this); }});
  p.gateways=gateways; return p;
}
test('coupon replacement restores full price after invalid response',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-coupon-code').val('SAVE');p.$('#gep-apply-coupon').trigger('click');
 assert.equal(p.$('#gep-pay-button').prop('disabled'),true);
 p.requests[0].resolve({success:true,data:{new_total:1000,discount:200}});
 assert.equal(p.$('#gep-final-amount').text(),'1000.00');
 p.$('#gep-coupon-code').val('INVALID').trigger('input');p.$('#gep-apply-coupon').trigger('click');
 p.requests[1].resolve({success:false,data:{message:'Invalid coupon'}});
 assert.equal(p.$('#gep-final-amount').text(),'1200.00');assert.equal(p.$('#gep-pay-button').prop('disabled'),false);assert.deepEqual(p.errors,[]);
});
test('stale coupon response cannot overwrite a newly selected tier',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-coupon-code').val('SAVE');p.$('#gep-apply-coupon').trigger('click');
 p.$('input[value="3"]').prop('checked',true).trigger('change');p.requests[0].resolve({success:true,data:{new_total:1000,discount:200}});
 assert.equal(p.$('#gep-final-amount').text(),'2000.00');assert.equal(p.$('#gep-pay-button').prop('disabled'),false);
});
test('payment window stays locked against duplicate orders until dismissal',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-pay-button').trigger('click').trigger('click');assert.equal(p.requests.length,1);
 p.requests[0].resolve({success:true,data:{id:'order_test',amount:120000}});
 assert.equal(p.$('#gep-pay-button').prop('disabled'),true);assert.equal(p.$('#gep-coupon-code').prop('disabled'),true);
 p.$('#gep-pay-button').trigger('click');assert.equal(p.requests.length,1);
 p.gateways[0].options.modal.ondismiss();assert.equal(p.$('#gep-pay-button').prop('disabled'),false);
});
test('failed verification retries the same payment, never a new order',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-pay-button').trigger('click');p.requests[0].resolve({success:true,data:{id:'order_test',amount:120000}});
 p.gateways[0].options.handler({razorpay_payment_id:'pay_test',razorpay_order_id:'order_test',razorpay_signature:'test_signature'});
 p.requests[1].reject();assert.match(p.$('#gep-payment-error').text(),/Do not pay again/);
 assert.equal(p.$('#gep-pay-button').text(),'Retry payment verification');
 p.$('#gep-pay-button').trigger('click');assert.equal(p.requests[2].options.data.action,'gep_verify_payment');assert.equal(p.requests[2].options.data.razorpay_payment_id,'pay_test');
});
test('coupon and order network errors release controls with persistent feedback',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-coupon-code').val('SAVE');p.$('#gep-apply-coupon').trigger('click');p.requests[0].reject();
 assert.equal(p.$('#gep-apply-coupon').prop('disabled'),false);assert.match(p.$('#gep-coupon-status').text(),/try again/);
 p.$('#gep-pay-button').trigger('click');p.requests[1].reject();assert.equal(p.$('#gep-pay-button').prop('disabled'),false);assert.equal(p.$('#gep-payment-error').attr('role'),'alert');
});
test('free total and missing gateway have actionable states',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-coupon-code').val('FREE');p.$('#gep-apply-coupon').trigger('click');p.requests[0].resolve({success:true,data:{new_total:0,discount:1200}});
 assert.equal(p.$('#gep-pay-button').text(),'Complete Enrollment');
 p.w.Razorpay=undefined;p.$('#gep-pay-button').trigger('click');p.requests[1].resolve({success:true,data:{id:'order',amount:1}});assert.match(p.$('#gep-payment-error').text(),/could not load/);assert.equal(p.$('#gep-pay-button').prop('disabled'),false);
});

test('duplicate gateway completion callbacks trigger only one verification request',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-pay-button').trigger('click');p.requests[0].resolve({success:true,data:{id:'order_test',amount:120000}});const payment={razorpay_payment_id:'pay_test',razorpay_order_id:'order_test',razorpay_signature:'signature'};p.gateways[0].options.handler(payment);p.gateways[0].options.handler(payment);assert.equal(p.requests.length,2);p.requests[1].reject();p.$('#gep-pay-button').trigger('click');assert.equal(p.requests.length,3);assert.equal(p.requests[2].options.data.action,'gep_verify_payment');
});
test('missing order fields never open a broken gateway and allow retry',async t=>{
 const p=await page();t.after(p.close);for(const data of [{},{id:'order'},{id:'order',amount:-1}]){p.$('#gep-pay-button').trigger('click');p.requests.at(-1).resolve({success:true,data});assert.equal(p.gateways.length,0);assert.equal(p.$('#gep-pay-button').prop('disabled'),false);}assert.deepEqual(p.errors,[]);
});
test('incomplete free enrollment confirmation keeps an actionable recovery message',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-pay-button').trigger('click');p.requests[0].resolve({success:true,data:{status:'free'}});assert.match(p.$('#gep-payment-error').text(),/My Purchases/);assert.equal(p.$('#gep-pay-button').prop('disabled'),false);assert.equal(p.w.location.pathname,'/dashboard/');
});
