import{E as e,F as t,J as n,K as r,M as i,N as a,P as o,Q as s,U as c,V as l,W as u,_t as d,bt as f,c as p,h as m,j as h,k as g,l as _,m as v,o as y,r as b,s as x,u as S,w as C,x as w}from"./runtime-core.esm-bundler-Dke-uHpV.js";import{c as T,s as ee,t as E}from"./runtime-dom.esm-bundler-BA_bigkx.js";import{W as D,Z as O,c as k,n as A,o as j,t as M,ut as N,v as P}from"./portal-Ci_wnqEi.js";import{t as F}from"./api-DkfhEQ_c.js";import{t as I}from"./button-BMZz_agf.js";import{t as L}from"./focustrap-BuZxTfnr.js";import{b as te,d as R,p as z,u as ne,y as re}from"./main-BgW0U3rO.js";import{t as ie}from"./Breadcrumb-Ckpq_faB.js";import{t as ae}from"./ProductCard-qJHCcYq2.js";var B=n(localStorage.getItem(`shop_locale`)||`th`),V={หมวดหมู่:{en:`Category`},ทั้งหมด:{en:`All`},กลุ่มผู้ขาย:{en:`Sellers`},ทุกกลุ่ม:{en:`All Groups`},ช่วงราคา:{en:`Price Range`},ต่ำสุด:{en:`Min`},สูงสุด:{en:`Max`},กรองราคา:{en:`Filter`},ใหม่ล่าสุด:{en:`Newest`},"ราคา: ต่ำ→สูง":{en:`Price: Low→High`},"ราคา: สูง→ต่ำ":{en:`Price: High→Low`},ยอดนิยม:{en:`Popular`},คะแนนรีวิว:{en:`Rating`},เรียงโดย:{en:`Sort`},พบ:{en:`Found`},รายการ:{en:`items`},สำหรับ:{en:`for`},ไม่พบสินค้าที่ตรงกับเงื่อนไข:{en:`No products found`},ตัวกรอง:{en:`Filters`}};function H(){function e(e){return B.value===`en`&&V[e]?V[e].en:e}function t(){B.value=B.value===`th`?`en`:`th`,localStorage.setItem(`shop_locale`,B.value)}return{locale:B,t:e,toggleLocale:t}}var U={class:`space-y-4`},W={class:`flex justify-end`},G={class:`box-card p-4`},K={class:`text-sm font-semibold text-violet-700 mb-2 flex items-center gap-2`},q={class:`space-y-1 text-sm`},J=[`onClick`],oe={class:`text-xs text-slate-400`},se={class:`box-card p-4`},ce={class:`text-sm font-semibold text-fuchsia-700 mb-2 flex items-center gap-2`},le={class:`space-y-1 text-sm`},ue=[`onClick`],de={class:`box-card p-4`},fe={class:`text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2`},pe={class:`flex items-center gap-2`},me=[`placeholder`],he=[`placeholder`],Y={__name:`CatalogFilters`,props:{categories:{type:Array,default:()=>[]},groups:{type:Array,default:()=>[]},filters:{type:Object,required:!0},onSet:{type:Function,required:!0},onApply:{type:Function,required:!0}},setup(e){let{locale:t,t:n,toggleLocale:r}=H();return(i,a)=>(g(),S(`div`,U,[x(`div`,W,[x(`button`,{onClick:a[0]||=(...e)=>s(r)&&s(r)(...e),class:d([`flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-semibold transition`,s(t)===`en`?`border-orange-300 bg-orange-50 text-orange-600`:`border-violet-200 bg-violet-50 text-violet-700`])},[a[6]||=x(`i`,{class:`fi fi-rr-globe text-[11px]`},null,-1),v(` `+f(s(t)===`th`?`TH · เปลี่ยนเป็น EN`:`EN · Switch to TH`),1)],2)]),x(`div`,G,[x(`p`,K,[a[7]||=x(`i`,{class:`fi fi-rr-apps`},null,-1),v(` `+f(s(n)(`หมวดหมู่`)),1)]),x(`ul`,q,[x(`li`,null,[x(`button`,{class:d([`w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50`,e.filters.category?`text-slate-600`:`bg-violet-100 text-violet-700 font-medium`]),onClick:a[1]||=t=>e.onSet(`category`,null)},f(s(n)(`ทั้งหมด`)),3)]),(g(!0),S(b,null,h(e.categories,t=>(g(),S(`li`,{key:t.id},[x(`button`,{class:d([`w-full flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-violet-50`,e.filters.category===t.slug?`bg-violet-100 text-violet-700 font-medium`:`text-slate-600`]),onClick:n=>e.onSet(`category`,t.slug)},[x(`span`,null,f(t.name),1),x(`span`,oe,f(t.products_count),1)],10,J)]))),128))])]),x(`div`,se,[x(`p`,ce,[a[8]||=x(`i`,{class:`fi fi-rr-shop`},null,-1),v(` `+f(s(n)(`กลุ่มผู้ขาย`)),1)]),x(`ul`,le,[x(`li`,null,[x(`button`,{class:d([`w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50`,e.filters.group?`text-slate-600`:`bg-violet-100 text-violet-700 font-medium`]),onClick:a[2]||=t=>e.onSet(`group`,null)},f(s(n)(`ทุกกลุ่ม`)),3)]),(g(!0),S(b,null,h(e.groups,t=>(g(),S(`li`,{key:t.id},[x(`button`,{class:d([`w-full text-left px-2 py-1.5 rounded-lg hover:bg-violet-50 truncate`,e.filters.group===t.slug?`bg-violet-100 text-violet-700 font-medium`:`text-slate-600`]),onClick:n=>e.onSet(`group`,t.slug)},f(t.name),11,ue)]))),128))])]),x(`div`,de,[x(`p`,fe,[a[9]||=x(`i`,{class:`fi fi-rr-coins`},null,-1),v(` `+f(s(n)(`ช่วงราคา`)),1)]),x(`div`,pe,[u(x(`input`,{"onUpdate:modelValue":a[3]||=t=>e.filters.min_price=t,type:`number`,min:`0`,placeholder:s(n)(`ต่ำสุด`),class:`w-full h-9 px-2 rounded-lg border border-slate-200 text-sm`},null,8,me),[[T,e.filters.min_price,void 0,{number:!0}]]),a[10]||=x(`span`,{class:`text-slate-400`},`-`,-1),u(x(`input`,{"onUpdate:modelValue":a[4]||=t=>e.filters.max_price=t,type:`number`,min:`0`,placeholder:s(n)(`สูงสุด`),class:`w-full h-9 px-2 rounded-lg border border-slate-200 text-sm`},null,8,he),[[T,e.filters.max_price,void 0,{number:!0}]])]),x(`button`,{class:`btn-orange mt-2 w-full h-9 rounded-lg text-sm font-medium`,onClick:a[5]||=(...t)=>e.onApply&&e.onApply(...t)},f(s(n)(`กรองราคา`)),1)])]))}},ge={key:0,class:`overflow-hidden px-4 sm:px-6 py-3 flex items-center gap-3`,style:{background:`linear-gradient(135deg,#dc2626 0%,#ea580c 60%,#f97316 100%)`}},_e={class:`flex-1 min-w-0`},ve={class:`text-white/70 text-xs mt-0.5 truncate`},ye={class:`flex items-center gap-0.5 shrink-0`},be={class:`bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none`},xe={class:`bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none`},Se={class:`bg-black/40 text-white text-sm font-mono font-bold px-2 py-1.5 rounded-lg leading-none`},Ce={__name:`FlashSaleBanner`,setup(t){let r=n(null),i=n(0),a=n(0),o=n(0),s=null;function c(e){return String(e).padStart(2,`0`)}function l(e){let t=Math.max(0,e-Date.now());if(t===0){clearInterval(s),r.value=null;return}let n=Math.floor(t/1e3);i.value=Math.floor(n/3600),a.value=Math.floor(n%3600/60),o.value=n%60}return e(async()=>{try{let{data:e}=await F.get(`/shop/flash-sale-events`),t=Date.now(),n=(e||[]).find(e=>{let n=new Date(e.starts_at).getTime(),r=new Date(e.ends_at).getTime();return n<=t&&r>=t});if(!n)return;r.value=n;let i=new Date(n.ends_at).getTime();l(i),s=setInterval(()=>l(i),1e3)}catch{}}),C(()=>clearInterval(s)),(e,t)=>r.value?(g(),S(`div`,ge,[t[3]||=x(`i`,{class:`fi fi-rr-bolt text-yellow-300 text-xl shrink-0`,style:{"line-height":`1`}},null,-1),x(`div`,_e,[t[0]||=x(`p`,{class:`font-extrabold text-white text-base tracking-wider leading-none`},`FLASH SALE`,-1),x(`p`,ve,f(r.value.title),1)]),x(`div`,ye,[x(`span`,be,f(c(i.value)),1),t[1]||=x(`span`,{class:`text-white/70 text-base font-bold mx-0.5`},`:`,-1),x(`span`,xe,f(c(a.value)),1),t[2]||=x(`span`,{class:`text-white/70 text-base font-bold mx-0.5`},`:`,-1),x(`span`,Se,f(c(o.value)),1)])])):_(``,!0)}},we=k.extend({name:`drawer`,style:`
    .p-drawer {
        display: flex;
        flex-direction: column;
        transform: translate3d(0px, 0px, 0px);
        position: relative;
        transition: transform 0.3s;
        background: dt('drawer.background');
        color: dt('drawer.color');
        border-style: solid;
        border-color: dt('drawer.border.color');
        box-shadow: dt('drawer.shadow');
    }

    .p-drawer-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: dt('drawer.content.padding');
    }

    .p-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: dt('drawer.header.padding');
    }

    .p-drawer-footer {
        padding: dt('drawer.footer.padding');
    }

    .p-drawer-title {
        font-weight: dt('drawer.title.font.weight');
        font-size: dt('drawer.title.font.size');
    }

    .p-drawer-full .p-drawer {
        transition: none;
        transform: none;
        width: 100vw !important;
        height: 100vh !important;
        max-height: 100%;
        top: 0px !important;
        left: 0px !important;
        border-width: 1px;
    }

    .p-drawer-left .p-drawer-enter-active {
        animation: p-animate-drawer-enter-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-left .p-drawer-leave-active {
        animation: p-animate-drawer-leave-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-right .p-drawer-enter-active {
        animation: p-animate-drawer-enter-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-right .p-drawer-leave-active {
        animation: p-animate-drawer-leave-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-top .p-drawer-enter-active {
        animation: p-animate-drawer-enter-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-top .p-drawer-leave-active {
        animation: p-animate-drawer-leave-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-bottom .p-drawer-enter-active {
        animation: p-animate-drawer-enter-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-bottom .p-drawer-leave-active {
        animation: p-animate-drawer-leave-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-full .p-drawer-enter-active {
        animation: p-animate-drawer-enter-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-full .p-drawer-leave-active {
        animation: p-animate-drawer-leave-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    
    .p-drawer-left .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-end-width: 1px;
    }

    .p-drawer-right .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-start-width: 1px;
    }

    .p-drawer-top .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-end-width: 1px;
    }

    .p-drawer-bottom .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-start-width: 1px;
    }

    .p-drawer-left .p-drawer-content,
    .p-drawer-right .p-drawer-content,
    .p-drawer-top .p-drawer-content,
    .p-drawer-bottom .p-drawer-content {
        width: 100%;
        height: 100%;
    }

    .p-drawer-open {
        display: flex;
    }

    .p-drawer-mask:dir(rtl) {
        flex-direction: row-reverse;
    }

    @keyframes p-animate-drawer-enter-left {
        from {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-left {
        to {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-right {
        from {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-right {
        to {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-top {
        from {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-top {
        to {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-bottom {
        from {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-bottom {
        to {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-full {
        from {
            opacity: 0;
            transform: scale(0.93);
        }
    }

    @keyframes p-animate-drawer-leave-full {
        to {
            opacity: 0;
            transform: scale(0.93);
        }
    }
`,classes:{mask:function(e){var t=e.instance,n=e.props,r=[`left`,`right`,`top`,`bottom`].find(function(e){return e===n.position});return[`p-drawer-mask`,{"p-overlay-mask p-overlay-mask-enter-active":n.modal,"p-drawer-open":t.containerVisible,"p-drawer-full":t.fullScreen},r?`p-drawer-${r}`:``]},root:function(e){var t=e.instance;return[`p-drawer p-component`,{"p-drawer-full":t.fullScreen}]},header:`p-drawer-header`,title:`p-drawer-title`,pcCloseButton:`p-drawer-close-button`,content:`p-drawer-content`,footer:`p-drawer-footer`},inlineStyles:{mask:function(e){var t=e.position,n=e.modal;return{position:`fixed`,height:`100%`,width:`100%`,left:0,top:0,display:`flex`,justifyContent:t===`left`?`flex-start`:t===`right`?`flex-end`:`center`,alignItems:t===`top`?`flex-start`:t===`bottom`?`flex-end`:`center`,pointerEvents:n?`auto`:`none`}},root:{pointerEvents:`auto`}}}),Te={name:`BaseDrawer`,extends:j,props:{visible:{type:Boolean,default:!1},position:{type:String,default:`left`},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:`secondary`,text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:we,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function X(e){"@babel/helpers - typeof";return X=typeof Symbol==`function`&&typeof Symbol.iterator==`symbol`?function(e){return typeof e}:function(e){return e&&typeof Symbol==`function`&&e.constructor===Symbol&&e!==Symbol.prototype?`symbol`:typeof e},X(e)}function Z(e,t,n){return(t=Ee(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function Ee(e){var t=De(e,`string`);return X(t)==`symbol`?t:t+``}function De(e,t){if(X(e)!=`object`||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var r=n.call(e,t);if(X(r)!=`object`)return r;throw TypeError(`@@toPrimitive must return a primitive value.`)}return(t===`string`?String:Number)(e)}var Q={name:`Drawer`,extends:Te,inheritAttrs:!1,emits:[`update:visible`,`show`,`after-show`,`hide`,`after-hide`,`before-hide`],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(e){e&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&P.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit(`update:visible`,!1)},onEnter:function(){this.$emit(`show`),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&P.set(`modal`,this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit(`after-show`)},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&D(this.mask,`p-overlay-mask-leave-active`),this.$emit(`before-hide`)},onLeave:function(){this.$emit(`hide`)},onAfterLeave:function(){this.autoZIndex&&P.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit(`after-hide`)},onMaskClick:function(e){this.dismissable&&this.modal&&this.mask===e.target&&this.hide()},focus:function(){var e=function(e){return e&&e.querySelector(`[autofocus]`)},t=this.$slots.header&&e(this.headerContainer);t||(t=this.$slots.default&&e(this.container),t||(t=this.$slots.footer&&e(this.footerContainer),t||=this.closeButton)),t&&O(t)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&ne()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&R()},onKeydown:function(e){e.code===`Escape`&&this.closeOnEscape&&this.hide()},containerRef:function(e){this.container=e},maskRef:function(e){this.mask=e},contentRef:function(e){this.content=e},headerContainerRef:function(e){this.headerContainer=e},footerContainerRef:function(e){this.footerContainer=e},closeButtonRef:function(e){this.closeButton=e?e.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener(`keydown`,this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&=(document.removeEventListener(`keydown`,this.documentKeydownListener),null)},bindOutsideClickListener:function(){var e=this;this.outsideClickListener||(this.outsideClickListener=function(t){e.isOutsideClicked(t)&&e.hide()},document.addEventListener(`click`,this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&=(document.removeEventListener(`click`,this.outsideClickListener,!0),null)},isOutsideClicked:function(e){return this.container&&!this.container.contains(e.target)}},computed:{fullScreen:function(){return this.position===`full`},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return N(Z(Z(Z({"full-screen":this.position===`full`},this.position,this.position),`open`,this.containerVisible),`modal`,this.modal))}},directives:{focustrap:L},components:{Button:I,Portal:M,TimesIcon:A}},Oe=[`data-p`],ke=[`role`,`aria-modal`,`data-p`];function Ae(e,n,r,s,l,h){var v=a(`Button`),y=a(`Portal`),C=o(`focustrap`);return g(),p(y,null,{default:c(function(){return[l.containerVisible?(g(),S(`div`,w({key:0,ref:h.maskRef,onMousedown:n[0]||=function(){return h.onMaskClick&&h.onMaskClick.apply(h,arguments)},class:e.cx(`mask`),style:e.sx(`mask`,!0,{position:e.position,modal:e.modal}),"data-p":h.dataP},e.ptm(`mask`)),[m(E,w({name:`p-drawer`,onEnter:h.onEnter,onAfterEnter:h.onAfterEnter,onBeforeLeave:h.onBeforeLeave,onLeave:h.onLeave,onAfterLeave:h.onAfterLeave,appear:``},e.ptm(`transition`)),{default:c(function(){return[e.visible?u((g(),S(`div`,w({key:0,ref:h.containerRef,class:e.cx(`root`),style:e.sx(`root`),role:e.modal?`dialog`:`complementary`,"aria-modal":e.modal?!0:void 0,"data-p":h.dataP},e.ptmi(`root`)),[e.$slots.container?i(e.$slots,`container`,{key:0,closeCallback:h.hide}):(g(),S(b,{key:1},[x(`div`,w({ref:h.headerContainerRef,class:e.cx(`header`)},e.ptm(`header`)),[i(e.$slots,`header`,{class:d(e.cx(`title`))},function(){return[e.header?(g(),S(`div`,w({key:0,class:e.cx(`title`)},e.ptm(`title`)),f(e.header),17)):_(``,!0)]}),e.showCloseIcon?i(e.$slots,`closebutton`,{key:0,closeCallback:h.hide},function(){return[m(v,w({ref:h.closeButtonRef,type:`button`,class:e.cx(`pcCloseButton`),"aria-label":h.closeAriaLabel,unstyled:e.unstyled,onClick:h.hide},e.closeButtonProps,{pt:e.ptm(`pcCloseButton`),"data-pc-group-section":`iconcontainer`}),{icon:c(function(n){return[i(e.$slots,`closeicon`,{},function(){return[(g(),p(t(e.closeIcon?`span`:`TimesIcon`),w({class:[e.closeIcon,n.class]},e.ptm(`pcCloseButton`).icon),null,16,[`class`]))]})]}),_:3},16,[`class`,`aria-label`,`unstyled`,`onClick`,`pt`])]}):_(``,!0)],16),x(`div`,w({ref:h.contentRef,class:e.cx(`content`)},e.ptm(`content`)),[i(e.$slots,`default`)],16),e.$slots.footer?(g(),S(`div`,w({key:0,ref:h.footerContainerRef,class:e.cx(`footer`)},e.ptm(`footer`)),[i(e.$slots,`footer`)],16)):_(``,!0)],64))],16,ke)),[[C]]):_(``,!0)]}),_:3},16,[`onEnter`,`onAfterEnter`,`onBeforeLeave`,`onLeave`,`onAfterLeave`])],16,Oe)):_(``,!0)]}),_:3})}Q.render=Ae;var je={class:`max-w-7xl mx-auto px-4 sm:px-6 py-6 max-lg:pb-20 space-y-5`},Me={class:`flex items-center justify-between gap-3`},Ne={key:0,class:`min-w-[18px] h-[18px] px-1 rounded-full bg-orange-500 text-white text-[11px] flex items-center justify-center`},Pe={class:`flex flex-col lg:flex-row gap-6`},Fe={class:`hidden lg:block lg:w-64 shrink-0`},Ie={class:`flex-1 min-w-0 space-y-4`},Le={class:`box-card p-3 flex items-center justify-between flex-wrap gap-3`},Re={class:`text-sm text-slate-500`},ze={class:`font-semibold text-slate-700`},Be={key:0,class:`text-violet-600`},Ve={class:`flex items-center gap-2`},$={class:`text-sm text-slate-500`},He={value:``},Ue={value:`newest`},We={value:`price_asc`},Ge={value:`price_desc`},Ke={value:`popular`},qe={value:`rating`},Je={key:0,class:`grid grid-cols-2 sm:grid-cols-3 gap-4`},Ye={key:1,class:`grid grid-cols-2 sm:grid-cols-3 gap-4`},Xe={key:2,class:`box-card p-12 text-center text-slate-400`},Ze={class:`mt-3`},Qe={__name:`ProductCatalog`,setup(t){let{t:i}=H(),a=re(),d=te(),C=n(!0),w=n([]),T=n({}),E=n([]),D=n([]),O=n(!1),k=r({q:a.query.q||``,category:a.query.category||null,group:a.query.group||null,min_price:a.query.min_price||null,max_price:a.query.max_price||null,sort:a.query.sort||``,page:Number(a.query.page)||1,on_sale:a.query.on_sale?1:0});async function A(){C.value=!0;try{let e={per_page:20,page:k.page};for(let t of[`q`,`category`,`group`,`min_price`,`max_price`,`sort`])k[t]&&(e[t]=k[t]);k.on_sale&&(e.on_sale=1);let{data:t}=await F.get(`/shop/products`,{params:e});w.value=t.data||[],T.value=t,d.replace({query:{...e,per_page:void 0,page:k.page>1?k.page:void 0}})}finally{C.value=!1}}async function j(){let[e,t]=await Promise.all([F.get(`/shop/categories`),F.get(`/shop/groups`)]);E.value=(e.data||[]).filter(e=>e.products_count>0),D.value=t.data||[]}function M(e,t){k[e]=t,k.page=1,A()}function N(){k.page=1,A()}function P(e,t){M(e,t),O.value=!1}function I(){N(),O.value=!1}let L=y(()=>[`category`,`group`,`min_price`,`max_price`].filter(e=>k[e]).length);function R(e){k.page=e,A(),window.scrollTo({top:0,behavior:`smooth`})}return l(()=>a.query.q,e=>{e!==k.q&&(k.q=e||``,k.page=1,A())}),l(()=>a.query.on_sale,e=>{let t=e?1:0;t!==k.on_sale&&(k.on_sale=t,k.page=1,A())}),e(()=>{j(),A()}),(e,t)=>{let n=o(`reveal`);return g(),S(`div`,je,[k.on_sale?(g(),p(Ce,{key:0,class:`-mx-4 sm:-mx-6 -mt-6`})):_(``,!0),x(`div`,Me,[m(ie,{items:k.on_sale?[{label:`FLASH SALE`,icon:`fi fi-rr-bolt`}]:[{label:`สินค้าทั้งหมด`}]},null,8,[`items`]),x(`button`,{class:`lg:hidden inline-flex items-center gap-2 px-4 py-2 rounded-full box-card text-sm font-medium text-slate-700`,onClick:t[0]||=e=>O.value=!0},[t[3]||=x(`i`,{class:`fi fi-rr-settings-sliders text-violet-600`},null,-1),t[4]||=v(` ตัวกรอง `,-1),L.value?(g(),S(`span`,Ne,f(L.value),1)):_(``,!0)])]),x(`div`,Pe,[x(`aside`,Fe,[m(Y,{categories:E.value,groups:D.value,filters:k,"on-set":M,"on-apply":N},null,8,[`categories`,`groups`,`filters`])]),m(s(Q),{visible:O.value,"onUpdate:visible":t[1]||=e=>O.value=e,position:`right`,header:`ตัวกรอง`,style:{width:`85vw`,maxWidth:`22rem`},class:`lg:hidden`},{default:c(()=>[m(Y,{categories:E.value,groups:D.value,filters:k,"on-set":P,"on-apply":I},null,8,[`categories`,`groups`,`filters`])]),_:1},8,[`visible`]),x(`div`,Ie,[x(`div`,Le,[x(`p`,Re,[v(f(s(i)(`พบ`))+` `,1),x(`span`,ze,f(T.value.total||0),1),v(` `+f(s(i)(`รายการ`))+` `,1),k.q?(g(),S(`span`,Be,f(s(i)(`สำหรับ`))+` "`+f(k.q)+`"`,1)):_(``,!0)]),x(`div`,Ve,[x(`span`,$,f(s(i)(`เรียงโดย`)),1),u(x(`select`,{"onUpdate:modelValue":t[2]||=e=>k.sort=e,class:`h-9 px-2 rounded-lg border border-slate-200 text-sm`,onChange:N},[x(`option`,He,f(s(i)(`แนะนำ (สุ่ม)`)),1),x(`option`,Ue,f(s(i)(`ใหม่ล่าสุด`)),1),x(`option`,We,f(s(i)(`ราคา: ต่ำ→สูง`)),1),x(`option`,Ge,f(s(i)(`ราคา: สูง→ต่ำ`)),1),x(`option`,Ke,f(s(i)(`ยอดนิยม`)),1),x(`option`,qe,f(s(i)(`คะแนนรีวิว`)),1)],544),[[ee,k.sort]])])]),C.value?(g(),S(`div`,Je,[(g(),S(b,null,h(9,e=>x(`div`,{key:e,class:`box-card aspect-[3/4] skeleton`})),64))])):w.value.length?(g(),S(`div`,Ye,[(g(!0),S(b,null,h(w.value,(e,t)=>u((g(),p(ae,{key:e.id,product:e,"flash-sale":!!k.on_sale},null,8,[`product`,`flash-sale`])),[[n,t%6]])),128))])):(g(),S(`div`,Xe,[t[5]||=x(`i`,{class:`fi fi-rr-search text-4xl`},null,-1),x(`p`,Ze,f(s(i)(`ไม่พบสินค้าที่ตรงกับเงื่อนไข`)),1)])),m(z,{meta:T.value,onChange:R},null,8,[`meta`])])])])}}};export{Qe as default};