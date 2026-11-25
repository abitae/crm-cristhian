<script setup lang="ts">
import {computed,ref,useId,watch} from 'vue';
import {Building2,ChevronDown,ChevronLeft,ChevronRight,GripVertical,Inbox,Plus,Tag} from '@lucide/vue';
import {usePage} from '@inertiajs/vue3';
import {index} from '@/routes/clients';
import type {Client} from '@/lib/clients';

const props=defineProps<{team:string;statusId:number|null;title:string;color:string;search:string;tag:string;revision:number;dragged:Client|null;busy:boolean}>();
const emit=defineEmits<{edit:[Client];create:[number|null];drag:[Client|null];move:[Client,number|null]}>();
const page=ref(1),pages=ref(1),total=ref(0),cards=ref<Client[]>([]),loading=ref(true),error=ref(false),retry=ref(0),over=ref(false);
const canDrop=computed(()=>!!props.dragged&&!props.busy&&(props.dragged.options.find(o=>o.kind==='status')?.id??null)!==props.statusId);
const contentId=useId();
const user=usePage().props.auth.user;
const preferenceKey=computed(()=>`pulso:kanban:${user?.id}:${props.team}:${props.statusId??'none'}`);
const collapsed=ref(false);
watch(preferenceKey,key=>{
    try{collapsed.value=sessionStorage.getItem(key)==='closed'}catch{collapsed.value=false}
},{immediate:true});
function toggle(){
    collapsed.value=!collapsed.value;
    try{sessionStorage.setItem(preferenceKey.value,collapsed.value?'closed':'open')}catch{/* Keep the in-memory preference when browser storage is unavailable. */}
}
watch(()=>[props.search,props.tag,props.team],()=>page.value=1,{flush:'sync'});
watch(()=>[props.team,props.statusId,props.search,props.tag,props.revision,page.value,retry.value],(_,__,cleanup)=>{
    const abort=new AbortController();loading.value=true;error.value=false;
    const timer=setTimeout(async()=>{
        try{
            const response=await fetch(index.url(props.team,{query:{status:props.statusId??'none',search:props.search,tag:props.tag,page:page.value}}),{signal:abort.signal,headers:{Accept:'application/json'}});
            if(!response.ok)throw new Error();
            const result=await response.json();if(abort.signal.aborted)return;
            cards.value=result.data;total.value=result.total;pages.value=result.last_page;
            if(page.value>pages.value)page.value=pages.value;
        }catch{if(!abort.signal.aborted)error.value=true}
        finally{if(!abort.signal.aborted)loading.value=false}
    },140);
    cleanup(()=>{clearTimeout(timer);abort.abort()});
},{immediate:true});
function start(event:DragEvent,client:Client){if(!event.dataTransfer)return;event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain',String(client.id));emit('drag',client)}
function drop(){over.value=false;if(canDrop.value&&props.dragged)emit('move',props.dragged,props.statusId);emit('drag',null)}
</script>
<template><section class="lane" :class="{target:over&&canDrop,collapsed}" :style="{'--lane':color}" @dragover.prevent="over=true" @dragleave="over=false" @drop.prevent="drop">
<header class="lane-head"><h3><button class="fold" :aria-expanded="!collapsed" :aria-controls="contentId" :aria-label="(collapsed?'Expandir ':'Colapsar ')+title" :title="title" @click="toggle"><ChevronDown class="caret" :size="14"/><i/><strong>{{title}}</strong><span>{{loading?'…':error?'—':total}}</span></button></h3><button class="add" :aria-label="'Añadir cliente en '+title" @click="emit('create',statusId)"><Plus :size="15"/></button></header>
<div v-show="!collapsed" :id="contentId" class="lane-content">
<div v-if="loading" class="empty-lane" role="status">Cargando clientes…</div>
<div v-else-if="error" class="empty-lane">No se pudo cargar la columna.<button class="text-btn" @click="retry++">Reintentar</button></div>
<template v-else>
<button v-for="client in cards" :key="client.id" class="client-card" :draggable="!busy" :disabled="busy" @dragstart="start($event,client)" @dragend="emit('drag',null)" @click="emit('edit',client)">
<span class="card-top"><b class="monogram">{{client.name.split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase()}}</b><strong>{{client.name}}</strong><GripVertical :size="14"/></span>
<span v-if="client.company" class="company"><Building2 :size="13"/>{{client.company}}</span>
<span v-if="client.options.some(o=>o.kind==='tag')" class="card-tags"><span v-for="t in client.options.filter(o=>o.kind==='tag')" :key="t.id"><Tag :size="10"/>{{t.name}}</span></span>
</button>
<div v-if="!cards.length" class="empty-lane"><Inbox :size="20"/>{{search||tag?'Sin coincidencias':'Sin clientes en este estado'}}</div>
<footer v-if="pages>1"><button :disabled="page===1" aria-label="Página anterior" @click="page--"><ChevronLeft :size="15"/></button>{{page}} de {{pages}}<button :disabled="page===pages" aria-label="Página siguiente" @click="page++"><ChevronRight :size="15"/></button></footer></template></div></section></template>
<style scoped>
.lane{min-height:265px;min-width:0;background:#f3f4f6;border:1px solid #e4e6ea;border-radius:12px;padding:12px;transition:background .15s}.lane.target{background:#fff0e8;outline:2px dashed #ed8959;outline-offset:-3px}.crm .lane-head{display:flex;align-items:center;justify-content:flex-start;gap:7px;border:0;background:none;padding:0 1px 12px;height:auto}.lane-head i{background:var(--lane);width:8px;height:8px;border-radius:50%;flex-shrink:0}.lane-head h3{font-size:14px;font-weight:650;overflow-wrap:anywhere}.lane-head span{font-size:10px;background:white;border:1px solid #e1e3e7;border-radius:5px;padding:1px 5px}.lane-head button{margin-left:auto;color:#69707b;padding:4px}.client-card{display:block;width:100%;text-align:left;background:white;border:1px solid #e2e4e8;border-radius:9px;padding:13px;box-shadow:0 2px 4px #29314006;margin-bottom:8px;cursor:grab}.card-top,.company,.card-tags,.card-tags span,footer{display:flex;align-items:center;gap:7px}.card-top strong{font-size:13px;overflow-wrap:anywhere}.card-top>svg{margin-left:auto;color:#aab0ba;flex-shrink:0}.monogram{border-radius:7px;background:#fff0e7;color:#bd653d;font-size:10px;padding:6px}.company{font-size:11px;color:#79808a;margin:9px 0 0}.card-tags{flex-wrap:wrap;margin-top:10px}.card-tags span{font-size:10px;color:#75649d;background:#f2effa;border-radius:5px;padding:3px 6px;overflow-wrap:anywhere;max-width:100%}.empty-lane{display:grid;justify-items:center;align-content:center;gap:8px;min-height:100px;border:1px dashed #d6d9df;border-radius:9px;color:#777e88;font-size:12px;text-align:center}.empty-lane svg{color:#9ca4af}footer{justify-content:center;color:#656b73;font-size:11px}footer button{padding:5px}footer button:disabled{opacity:.3}
</style>
<style scoped>
.lane{flex:1 0 205px;min-height:0;padding:6px;border-radius:9px;border-top:3px solid var(--lane)}
.lane.collapsed{flex:0 0 auto;max-width:180px}
.lane .lane-content{grid-template-columns:minmax(0,1fr)}.lane .client-card,.lane .card-top strong{min-width:0}.lane .card-top strong{flex:1}.lane-head .fold strong{min-width:0}.lane-head .fold span{flex:none}
.crm .lane-head{padding:0;gap:2px}.lane-head h3{flex:1;min-width:0}
.lane-head .fold{width:100%;display:flex;align-items:center;gap:6px;color:#363a42;text-align:left;padding:4px 2px;margin:0}
.fold strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;font-weight:650}.lane-head i{display:none}.caret{color:#818792;flex:none;transition:transform .15s}.collapsed .caret{transform:rotate(-90deg)}
.lane-head .add{padding:5px;border-radius:5px;flex:none}.lane-head button:focus-visible{outline:2px solid #cc6b36;outline-offset:1px}
.lane-content{display:grid;gap:5px;margin-top:4px}.empty-lane{min-height:38px;display:flex;align-items:center;justify-content:center;gap:5px;padding:5px;font-size:11px}.empty-lane svg{width:14px}
.client-card{margin:0;padding:8px;border-radius:7px}.card-top{gap:5px}.card-top strong{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;font-size:12px;line-height:1.25}.monogram{padding:4px;border-radius:5px}.company{margin:4px 0 0;display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.company svg{display:inline;vertical-align:-2px;margin-right:4px}.card-tags{margin-top:5px;gap:4px}.card-tags span{padding:1px 5px}
@media(max-width:660px){.lane,.lane.collapsed{flex:none;width:100%;max-width:none}.lane-head .fold,.lane-head .add{min-height:32px}}
</style>
