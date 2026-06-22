<x-filament-panels::page>
<div dir="rtl" x-data="orderBuilder" x-init="$watch('customerId', () => reprice())"
     style="font-family: inherit; display:flex; flex-direction:column; gap:1rem; max-width:1000px;">

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <div style="display:inline-flex; background:rgba(0,0,0,0.05); border-radius:8px; padding:3px;">
            <button type="button" @click="docType='order'" :class="docType==='order' ? 'ob-tab ob-tab-on' : 'ob-tab'">طلب</button>
            <button type="button" @click="canBeProforma() && (docType='proforma')" :disabled="!canBeProforma()" :class="docType==='proforma' ? 'ob-tab ob-tab-on' : 'ob-tab'" :style="!canBeProforma() ? 'opacity:0.4; cursor:not-allowed;' : ''">عرض سعر مبدئي</button>
        </div>
        <span style="font-size:13px; color:#888;" x-show="docType==='proforma'">يمكن تحويله إلى طلب لاحقاً</span>
    </div>

    <div class="ob-card ob-row-resp" style="display:flex; flex-wrap:wrap; gap:1rem;">
        <div style="flex:1; min-width:160px;">
            <label class="ob-label">حالة الطلب</label>
            <select x-model="status" class="ob-input">
                <option value="pending">قيد الانتظار</option>
                <option value="processing">قيد المعالجة</option>
                <option value="on_hold">معلّق</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
            </select>
        </div>
        <div style="flex:1; min-width:160px;">
            <label class="ob-label">حالة الدفع</label>
            <select x-model="paymentStatus" class="ob-input">
                <option value="pending">غير مدفوع</option>
                <option value="paid">مدفوع</option>
            </select>
        </div>
    </div>

    <div class="ob-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <span style="font-weight:600;">العميل</span>
            <button type="button" @click="newCustomer=!newCustomer" class="ob-btn ob-btn-sm">
                <span x-text="newCustomer ? 'اختيار عميل موجود' : '+ عميل جديد'"></span>
            </button>
        </div>
        <div x-show="!newCustomer">
            <select x-model="customerId" class="ob-input" x-init="$nextTick(() => { if(customerId) $el.value = customerId })">
                <option value="">اختر العميل</option>
                <template x-for="c in customers" :key="c.id"><option :value="String(c.id)" x-text="c.name"></option></template>
            </select>
        </div>
        <div x-show="newCustomer" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px;">
            <div><label class="ob-label">الاسم *</label><input type="text" x-model="nc.name" class="ob-input" placeholder="اسم العميل"></div>
            <div><label class="ob-label">الجوال</label><input type="text" x-model="nc.phone" class="ob-input" placeholder="05xxxxxxxx"></div>
            <div><label class="ob-label">البريد الإلكتروني</label><input type="email" x-model="nc.email" class="ob-input" placeholder="(اختياري)"></div>
            <div style="grid-column:1/-1;"><label class="ob-label">العنوان</label><input type="text" x-model="nc.address" class="ob-input" placeholder="(اختياري)"></div>
        </div>
    </div>

    <div class="ob-card">
        <label class="ob-label">إضافة منتج من الكتالوج</label>
        <div style="position:relative;">
            <input type="text" x-model="search" @focus="showResults=true" @input="showResults=true"
                   class="ob-input" placeholder="ابحث بالاسم أو رمز المنتج (SKU)...">
            <div x-show="showResults && search.trim().length > 0"
                 @click.outside="showResults=false"
                 style="position:absolute; top:44px; right:0; left:0; z-index:50; background:#fff; border:1px solid #d4d4d4; border-radius:8px; max-height:320px; overflow-y:auto; box-shadow:0 6px 20px rgba(0,0,0,.12);">
                <template x-for="p in filteredProducts()" :key="p.id">
                    <div @click="addProduct(p.id)"
                         style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:10px;"
                         onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
                        <template x-if="p.thumb">
                            <img :src="p.thumb" style="width:34px; height:34px; object-fit:cover; border-radius:5px; border:1px solid #eee;">
                        </template>
                        <template x-if="!p.thumb">
                            <div style="width:34px; height:34px; border-radius:5px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#bbb;">📦</div>
                        </template>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="p.title"></div>
                            <div style="font-size:11px; color:#999;" x-text="p.sku"></div>
                        </div>
                        <div style="font-size:13px; color:#0F7A3D; font-weight:600; white-space:nowrap;" x-text="money(price(p.id))"></div>
                    </div>
                </template>
                <div x-show="filteredProducts().length === 0" style="padding:12px; text-align:center; color:#aaa; font-size:13px;">
                    لا توجد نتائج
                </div>
            </div>
        </div>
        <div style="margin-top:8px;">
            <button type="button" @click="addCustom()" class="ob-btn">+ بند مخصص</button>
        </div>
    </div>

    <!-- Items: table on desktop, cards on mobile -->
    <div class="ob-items">
        <table class="ob-items-table">
            <thead>
                <tr>
                    <th style="text-align:right; padding:10px 12px;">المنتج</th>
                    <th style="text-align:right; padding:10px 8px;">السعر</th>
                    <th style="text-align:center; padding:10px 8px;">الكمية</th>
                    <th style="text-align:left; padding:10px 12px;">الإجمالي</th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(it,idx) in items" :key="it.uid">
                    <tr class="ob-item-row">
                        <td class="ob-cell-product" style="padding:8px 12px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <template x-if="it.thumb">
                                    <img :src="it.thumb" style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #eee; flex-shrink:0;">
                                </template>
                                <template x-if="!it.thumb">
                                    <div style="width:44px; height:44px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#bbb; font-size:18px; flex-shrink:0;">📦</div>
                                </template>
                                <div style="flex:1; min-width:0;">
                                    <template x-if="it.custom"><input type="text" x-model="it.title" class="ob-input" style="height:32px;" placeholder="وصف البند"></template>
                                    <span x-show="!it.custom" x-text="it.title" style="font-weight:600;"></span>
                                    <input type="text" x-model="it.note" class="ob-input" style="height:28px; font-size:12px; margin-top:4px;" placeholder="ملاحظة على البند (اختياري)">
                                </div>
                            </div>
                        </td>
                        <td class="ob-cell-price" data-label="السعر" style="padding:8px;">
                            <input type="number" min="0" step="0.01" x-model.number="it.priceSar" class="ob-input" style="width:110px; height:32px;">
                        </td>
                        <td class="ob-cell-qty" data-label="الكمية" style="padding:8px; text-align:center;">
                            <input type="number" min="1" step="1" x-model.number="it.qty" class="ob-input" style="width:75px; height:36px; text-align:center;">
                        </td>
                        <td class="ob-cell-total" data-label="الإجمالي" style="padding:8px 12px; text-align:left; font-weight:600;" x-text="money(lineTotal(it))"></td>
                        <td class="ob-cell-del" style="text-align:center;"><button type="button" @click="items.splice(idx,1)" class="ob-del" aria-label="حذف">✕</button></td>
                    </tr>
                </template>
                <tr x-show="items.length===0"><td colspan="5" style="padding:1.5rem; text-align:center; color:#aaa;">لا توجد بنود بعد — أضف منتجاً للبدء</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ob-totals-wrap" style="display:flex; flex-wrap:wrap; gap:1rem; justify-content:space-between; align-items:flex-start;">
        <div style="flex:1; min-width:240px;">
            <label class="ob-label">ملاحظات على الطلب</label>
            <textarea x-model="notes" class="ob-input" style="min-height:80px; padding-top:8px;" placeholder="ملاحظات على الطلب"></textarea>
        </div>

        <div style="flex:1; min-width:260px; background:#f7f7f7; border-radius:10px; padding:1rem;">
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                <label class="ob-label" style="margin:0; white-space:nowrap; min-width:90px;">الخصم</label>
                <input type="number" min="0" step="0.01" x-model.number="discountValue" class="ob-input" style="height:36px;" placeholder="0">
                <div style="display:inline-flex; background:#fff; border:1px solid #d4d4d4; border-radius:6px; overflow:hidden;">
                    <button type="button" @click="discountType='fixed'" :style="discountType==='fixed' ? onSeg : offSeg">ر.س</button>
                    <button type="button" @click="discountType='percent'" :style="discountType==='percent' ? onSeg : offSeg">%</button>
                </div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                <label class="ob-label" style="margin:0; white-space:nowrap; min-width:90px;">الشحن (ر.س)</label>
                <input type="number" min="0" step="0.01" x-model.number="shipping" class="ob-input" style="height:36px;" placeholder="0">
            </div>
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                <label class="ob-label" style="margin:0; white-space:nowrap; min-width:90px;">رسوم الخدمة (ر.س)</label>
                <input type="number" min="0" step="0.01" x-model.number="serviceFees" class="ob-input" style="height:36px;" placeholder="0">
            </div>
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <label class="ob-label" style="margin:0; white-space:nowrap; min-width:90px;">الضريبة %</label>
                <input type="number" min="0" max="100" step="0.01" x-model.number="vat" class="ob-input" style="height:36px;" placeholder="مثال: 15">
            </div>

            <div style="border-top:1px solid #ddd; padding-top:8px;">
                <div style="display:flex; justify-content:space-between; padding:3px 0;"><span style="color:#777;">المجموع الفرعي</span><span x-text="money(subtotal())"></span></div>
                <div style="display:flex; justify-content:space-between; padding:3px 0;" x-show="discountAmount()>0"><span style="color:#777;">الخصم</span><span style="color:#dc2626;" x-text="'- ' + money(discountAmount())"></span></div>
                <div style="display:flex; justify-content:space-between; padding:3px 0;" x-show="shippingH()>0"><span style="color:#777;">الشحن</span><span x-text="money(shippingH())"></span></div>
                <div style="display:flex; justify-content:space-between; padding:3px 0;" x-show="feesH()>0"><span style="color:#777;">رسوم الخدمة</span><span x-text="money(feesH())"></span></div>
                <div style="display:flex; justify-content:space-between; padding:3px 0;"><span style="color:#777;">الضريبة</span><span x-text="money(vatAmount())"></span></div>
                <div style="display:flex; justify-content:space-between; font-size:17px; font-weight:600; border-top:1px solid #ddd; padding-top:8px; margin-top:4px;"><span>الإجمالي</span><span x-text="money(total())"></span></div>
            </div>
        </div>
    </div>

    <div>
        <button type="button" @click="save()" class="ob-btn ob-btn-primary ob-save" :disabled="saving">
            <span x-text="saving ? 'جاري الحفظ...' : (editId ? 'تحديث الطلب' : (docType==='proforma' ? 'حفظ عرض السعر' : 'حفظ الطلب'))"></span>
        </button>
    </div>
</div>

<style>
    .ob-card{background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:1rem 1.25rem;}
    .ob-input{width:100%; height:40px; border:1px solid #d4d4d4; border-radius:8px; padding:0 10px; font-size:14px; background:#fff; color:#1a1a1a;}
    .ob-input:focus{outline:none; border-color:#0F7A3D; box-shadow:0 0 0 2px rgba(15,122,61,.2);}
    .ob-label{display:block; font-size:12px; color:#777; margin-bottom:4px;}
    .ob-btn{height:40px; padding:0 16px; border:1px solid #d4d4d4; border-radius:8px; background:#fff; font-size:14px; cursor:pointer; color:#1a1a1a; white-space:nowrap;}
    .ob-btn:hover{background:#f5f5f5;}
    .ob-btn-sm{height:auto; padding:5px 12px; font-size:13px;}
    .ob-btn-primary{background:#0F7A3D; border-color:#0F7A3D; color:#fff; font-weight:600; padding:0 28px;}
    .ob-btn-primary:hover{background:#0c6431;}
    .ob-tab{border:none; background:transparent; border-radius:6px; padding:6px 16px; font-size:14px; cursor:pointer; color:#777;}
    .ob-tab-on{background:#fff; color:#1a1a1a; font-weight:600; box-shadow:0 1px 2px rgba(0,0,0,.1);}
    .ob-del{width:32px; height:32px; border:none; background:transparent; color:#dc2626; cursor:pointer; font-size:16px;}

    .ob-items{border:1px solid #e5e5e5; border-radius:12px; overflow:hidden;}
    .ob-items-table{width:100%; border-collapse:collapse; font-size:14px;}
    .ob-items-table thead{background:#f7f7f7;}
    .ob-item-row{border-top:1px solid #eee;}

    .onSeg{background:#0F7A3D;}

    .dark .ob-card{background:#1f2937; border-color:#374151;}
    .dark .ob-input{background:#111827; border-color:#374151; color:#eee;}
    .dark .ob-btn{background:#1f2937; border-color:#374151; color:#eee;}

    /* ---- MOBILE: turn table rows into cards ---- */
    @media (max-width: 640px) {
        .ob-items{border:none; background:transparent;}
        .ob-items-table thead{display:none;}
        .ob-items-table, .ob-items-table tbody, .ob-item-row, .ob-items-table td{display:block; width:100%;}
        .ob-item-row{
            border:1px solid #e5e5e5; border-radius:12px; background:#fff;
            margin-bottom:12px; padding:12px; position:relative;
        }
        .dark .ob-item-row{background:#1f2937; border-color:#374151;}
        .ob-cell-product{padding:0 0 10px 0 !important;}
        .ob-cell-price, .ob-cell-qty, .ob-cell-total{
            display:flex !important; justify-content:space-between; align-items:center;
            padding:6px 0 !important; text-align:right !important; border-top:1px solid #f0f0f0;
        }
        .ob-cell-price::before, .ob-cell-qty::before, .ob-cell-total::before{
            content: attr(data-label); color:#888; font-size:13px; font-weight:normal;
        }
        .ob-cell-del{
            position:absolute; top:8px; left:8px; padding:0 !important; width:auto !important;
            border-top:none !important;
        }
        .ob-save{width:100%;}
    }
</style>

@script
<script>
    Alpine.data('orderBuilder', () => ({
        customers: @json($customers),
        products: @json($products),
        priceLists: @json($priceLists),
        edit: @json($editData),
        editId: @json($editOrderId),
        origType: @json($editData['docType'] ?? null),
        docType:'order', status:'pending', paymentStatus:'pending', newCustomer:false, customerId:'', notes:'', vat:null,
        search:'', showResults:false,
        discountType:'fixed', discountValue:0, shipping:0, serviceFees:0,
        nc:{name:'',phone:'',email:'',address:''},
        items:[], uidc:1, saving:false,
        onSeg:'border:none; background:#0F7A3D; color:#fff; padding:8px 12px; cursor:pointer; font-size:13px;',
        offSeg:'border:none; background:transparent; color:#777; padding:8px 12px; cursor:pointer; font-size:13px;',

        init(){
            if(this.edit && Object.keys(this.edit).length){
                this.docType = this.edit.docType;
                this.status = this.edit.status;
                this.paymentStatus = this.edit.paymentStatus;
                this.customerId = this.edit.customerId != null ? String(this.edit.customerId) : '';
                this.vat = this.edit.vat;
                this.discountType = this.edit.discountType;
                this.discountValue = this.edit.discountValue;
                this.shipping = this.edit.shipping;
                this.serviceFees = this.edit.serviceFees;
                this.notes = this.edit.notes || '';
                this.items = this.edit.items || [];
                // Ensure every loaded item has an editable priceSar
                this.items.forEach(it => { if (it.priceSar === undefined || it.priceSar === 0) { if (it.priceHalalas !== undefined && it.priceHalalas > 0) it.priceSar = it.priceHalalas/100; } });
                this.uidc = this.items.length + 1000;
            }
        },

        canBeProforma(){ return this.origType !== 'order'; },

        filteredProducts(){
            const q = this.search.trim().toLowerCase();
            if(q === '') return [];
            const out = [];
            for(const p of this.products){
                const title = (p.title || '').toLowerCase();
                const sku = (p.sku || '').toLowerCase();
                if(title.includes(q) || sku.includes(q)){
                    out.push(p);
                    if(out.length >= 50) break;
                }
            }
            return out;
        },

        price(id){
            const pl=this.priceLists[this.customerId];
            if(pl && pl[id]!=null) return pl[id];
            const p=this.products.find(c=>c.id===id); return p?p.def:0;
        },
        // When customer changes, reset catalog item prices to that customer's price (in SAR)
        reprice(){ this.items.forEach(it=>{ if(!it.custom) it.priceSar=this.price(it.cid)/100; }); },

        addProduct(id){
            const ex=this.items.find(i=>!i.custom && i.cid===id);
            if(ex){ ex.qty++; }
            else {
                const p=this.products.find(c=>c.id===id);
                this.items.push({uid:this.uidc++, cid:id, sku:p.sku, custom:false, title:p.title, thumb:p.thumb, note:'', priceSar:this.price(id)/100, qty:1});
            }
            this.search='';
            this.showResults=false;
        },
        addCustom(){ this.items.push({uid:this.uidc++, custom:true, sku:'CUSTOM', title:'', thumb:null, note:'', priceSar:0, qty:1}); },
        // All items now use priceSar (in SAR), converted to halalas here
        lineTotal(it){ const unit = Math.round((it.priceSar||0)*100); return unit*(it.qty||0); },
        subtotal(){ return this.items.reduce((s,it)=>s+this.lineTotal(it),0); },
        discountAmount(){
            const v=parseFloat(this.discountValue)||0;
            let amt = this.discountType==='percent' ? Math.round(this.subtotal()*v/100) : Math.round(v*100);
            return Math.min(amt, this.subtotal());
        },
        shippingH(){ return Math.round((parseFloat(this.shipping)||0)*100); },
        feesH(){ return Math.round((parseFloat(this.serviceFees)||0)*100); },
        vatAmount(){ const r=parseFloat(this.vat)||0; return Math.round(this.subtotal()*r/100); },
        total(){ return this.subtotal() - this.discountAmount() + this.shippingH() + this.feesH() + this.vatAmount(); },
        money(h){ return (h/100).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})+' ر.س'; },

        save(){
            if(!this.customerId && !this.nc.name){ alert('يجب اختيار العميل أو إدخال عميل جديد'); return; }
            if(this.items.length===0){ alert('أضف بنداً واحداً على الأقل'); return; }
            this.saving=true;
            $wire.save({
                docType:this.docType, status:this.status, paymentStatus:this.paymentStatus,
                customerId:this.customerId, nc:this.nc,
                vat:this.vat, notes:this.notes, items:this.items,
                discountType:this.discountType, discountValue:this.discountValue,
                shipping:this.shipping, serviceFees:this.serviceFees,
            }).then(()=>{
                if(!this.editId){
                    this.items=[]; this.notes=''; this.vat=null; this.customerId='';
                    this.discountValue=0; this.shipping=0; this.serviceFees=0; this.newCustomer=false;
                    this.nc={name:'',phone:'',email:'',address:''};
                }
                this.saving=false;
            }).catch(()=>{ this.saving=false; });
        },
    }));
</script>
@endscript
</x-filament-panels::page>