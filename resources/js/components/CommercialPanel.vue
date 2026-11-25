<script setup lang="ts">
import { Head,Link,useForm,usePage } from '@inertiajs/vue3';
import { computed,onMounted,onUnmounted,ref,watch } from 'vue';
import ClientWorkspace from './ClientWorkspace.vue';
import CrmAdministration from './CrmAdministration.vue';
import type { Client } from '@/lib/clients';
import {ShieldCheck} from '@lucide/vue';
import TablePager from './TablePager.vue';
import { Activity,ArrowDownRight,ArrowRight,ArrowUpRight,Bell,BriefcaseBusiness,Building2,CalendarDays,ChartNoAxesCombined,Check,ChevronDown,ChevronRight,CircleDollarSign,CircleHelp,Command,Ellipsis,Filter,Handshake,House,Layers3,LayoutDashboard,Menu,Plus,Search,Settings2,Target,TrendingUp,Users,X,Zap } from '@lucide/vue';
import PipelineRing from './PipelineRing.vue';
import SalesTrend from './SalesTrend.vue';
import { colors,money,sampleRecords,stages,type Opportunity } from '@/lib/commercial';
import { dashboard,login } from '@/routes';
import { store,update } from '@/routes/opportunities';
import { edit } from '@/routes/profile';
import type {Team,User} from '@/types';
const props=defineProps<{records?:Opportunity[]}>();
const page=usePage<{auth:{user:User|null}; currentTeam:Team|null}>();
const demo=computed(()=>props.records===undefined);
const examples=ref(sampleRecords());
const all=computed(()=>props.records??examples.value);
const section=ref('Resumen general'),modal=ref(''),query=ref(''),stage=ref('Todas'),mobile=ref(false),period=ref(0),notice=ref(''),detail=ref<Opportunity>();
const form=useForm({name:'',company:'',amount:'',stage:stages[0]});
const activeClient=ref<Client>();
const clientPage=ref(1);
const clientDeals=computed(()=>all.value.filter(r=>r.client_id===activeClient.value?.id));
function showClientDeals(c:Client){activeClient.value=c;clientPage.value=1;form.reset();form.clearErrors();modal.value='Oportunidades del cliente'}
const accountName=(r:Opportunity)=>r.client?.name??r.company;
const canAdminister=computed(()=>page.props.canCreateUsers||page.props.canCreateTeams);
const nav=computed(()=>[{name:'Resumen general',icon:LayoutDashboard},{name:'Clientes',icon:Users},{name:'Oportunidades',icon:BriefcaseBusiness},{name:'Actividad',icon:Activity},...(canAdminister.value?[{name:'Administración',icon:ShieldCheck}]:[])]);
const name=computed(()=>page.props.auth.user?.name??'Alex Morgan');
const initials=(s:string)=>s.split(' ').slice(0,2).map(i=>i[0]).join('').toUpperCase();
const monthRows=(offset:number)=>{const date=new Date();date.setDate(1);date.setMonth(date.getMonth()-offset);return all.value.filter(r=>new Date(r.created_at).toLocaleString('en',{year:'numeric',month:'numeric'})===date.toLocaleString('en',{year:'numeric',month:'numeric'}))};
const rows=computed(()=>monthRows(period.value));
const totals=(r:Opportunity[])=>{const won=r.filter(x=>x.stage==='Ganada');return [won.reduce((s,x)=>s+Number(x.amount),0),r.filter(x=>x.stage!=='Ganada').reduce((s,x)=>s+Number(x.amount),0),r.length,r.length?won.length/r.length*100:0]};
const metrics=computed(()=>{const prev=totals(monthRows(period.value+1));return totals(rows.value).map((x,i)=>({label:['Ingresos totales','Valor del pipeline','Oportunidades','Tasa de conversión'][i],value:i<2?money(x):i===2?x:x.toLocaleString('es',{maximumFractionDigits:1})+'%',icon:[CircleDollarSign,Layers3,BriefcaseBusiness,Target][i],change:prev[i]?Math.round((x-prev[i])/prev[i]*1000)/10:0}))});
const groups=computed(()=>stages.map((s,i)=>({label:s,n:rows.value.filter(r=>r.stage===s).length,color:colors[i]})));

const results=computed(()=>all.value.filter(r=>(r.name+' '+accountName(r)).toLocaleLowerCase().includes(query.value.toLocaleLowerCase())&&(stage.value==='Todas'||r.stage===stage.value)));
const tablePage=ref(1),tableSize=computed(()=>section.value==='Resumen general'?4:10);
const tableRecords=computed(()=>section.value==='Resumen general'?all.value:results.value);
const visibleRecords=computed(()=>tableRecords.value.slice((tablePage.value-1)*tableSize.value,tablePage.value*tableSize.value));
watch([section,query,stage],()=>tablePage.value=1);
const sections:Record<string,string>={'Resumen general':'','Clientes':'clientes','Kanban':'kanban','Configuración':'configuracion','Oportunidades':'oportunidades','Actividad':'actividad','Administración':'administracion'};
function selectSection(s:string){section.value=s==='Administración'&&!canAdminister.value?'Resumen general':s;mobile.value=false;modal.value='';query.value='';stage.value='Todas'}
function restoreSection(){selectSection(Object.keys(sections).find(s=>sections[s]===window.location.hash.slice(1))??'Resumen general')}
function go(s:string){selectSection(s);const hash=sections[s];if(window.location.hash.slice(1)!==hash){window.history.pushState(window.history.state,'',window.location.pathname+window.location.search+(hash?'#'+hash:''))}}
onMounted(()=>{restoreSection();window.addEventListener('popstate',restoreSection)});
onUnmounted(()=>window.removeEventListener('popstate',restoreSection));
function inspect(r:Opportunity){detail.value=r;modal.value='Detalle'}
function saved(){modal.value='Oportunidades del cliente';clientPage.value=1;form.reset();notice.value='Oportunidad creada';setTimeout(()=>notice.value='',3000)}
function submit(){if(!activeClient.value)return;if(demo.value){examples.value.unshift({...form.data(),client_id:activeClient.value.id,client:{id:activeClient.value.id,name:activeClient.value.name},id:Date.now(),created_at:new Date().toISOString()});localStorage.setItem('pulso-extra',JSON.stringify(examples.value.filter(x=>x.id>1000)));saved()}else if(page.props.currentTeam){form.post(store.url([page.props.currentTeam.slug,activeClient.value.id]),{preserveScroll:true,onSuccess:saved})}}
function changeStage(stage:string){
 const r=detail.value;if(!r)return;
 if(demo.value){r.stage=stage;const edits=JSON.parse(localStorage.getItem('pulso-stages')??'{}');edits[r.id]=stage;localStorage.setItem('pulso-stages',JSON.stringify(edits))}
 else if(page.props.currentTeam){form.stage=stage;form.patch(update.url([page.props.currentTeam.slug,r.id]),{preserveScroll:true,onSuccess:()=>{detail.value=all.value.find(x=>x.id===r.id);form.reset()}})}
}
function key(e:KeyboardEvent){if(e.key==='Escape'){modal.value='';mobile.value=false}if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();query.value='';modal.value='Buscar'}}
onMounted(()=>{try{examples.value=[...JSON.parse(localStorage.getItem('pulso-extra')??'[]'),...examples.value];const edits=JSON.parse(localStorage.getItem('pulso-stages')??'{}');examples.value.forEach(r=>{if(stages.includes(edits[r.id]))r.stage=edits[r.id]})}catch{}window.addEventListener('keydown',key)});
onUnmounted(()=>window.removeEventListener('keydown',key));
</script>
<template><div class="crm"><Head title="Pulso CRM · Panel comercial"/><div v-if="mobile" class="shade" @click="mobile=false"></div>
<aside :class="{open:mobile}"><div class="brand"><span class="logomark"><Activity :size="26" :stroke-width="3"/></span>Pulso<span class="crm-tag">CRM</span></div>
<div class="nav-wrap"><small>ESPACIO DE TRABAJO</small><button v-for="n in nav" :key="n.name" :class="{active:section===n.name||(section==='Kanban'&&n.name==='Clientes')}" @click="go(n.name)"><component :is="n.icon"/>{{n.name}}<span v-if="n.name===section" class="active-dot"></span></button><div class="nav-line"></div><small>PREFERENCIAS</small><button  :class="{active:section==='Configuración'}" @click="go('Configuración')"><Settings2/>Configuración</button><button @click="modal='Centro de ayuda'"><CircleHelp/>Centro de ayuda</button></div>
<button class="user-box" @click="modal='Mi cuenta'"><b class="avatar">{{initials(name)}}</b><span><strong>{{name}}</strong><em>{{demo?'Espacio demo':'Mi cuenta'}}</em></span><ChevronDown :size="15"/></button></aside>
<div class="main"><header><div class="breadcrumbs"><button class="mobile-toggle" aria-label="Abrir menú" @click="mobile=true"><Menu/></button><House :size="16"/><span>/</span> Mi espacio <span>/</span><b>{{section}}</b></div><div class="tools"><button class="search-btn" @click="query='';modal='Buscar'"><Search :size="15"/> Buscar... <kbd><Command :size="10"/> K</kbd></button><button class="bell" aria-label="Notificaciones" @click="modal='Notificaciones'"><Bell :size="18"/><i/></button><button class="avatar small" aria-label="Mi cuenta" @click="modal='Mi cuenta'">{{initials(name)}}</button></div></header>
<main><CrmAdministration v-if="canAdminister && section==='Administración'"/><ClientWorkspace v-if="section==='Clientes'||section==='Kanban'||section==='Configuración'" :section="section" :demo="demo" @navigate="go" @opportunities="showClientDeals"/><template v-if="section==='Resumen general'">
<section class="hero"><svg class="waves" viewBox="0 0 800 190" preserveAspectRatio="none" aria-hidden="true"><path v-for="n in 42" :key="n" :d="`M${-110+n*6} 200 C${150+n*6} ${240-n*3},${170+n*7} ${-40+n*2},${365+n*6} ${65+n*2} S${640+n*7} ${235-n*4},${740+n*6} -25`" fill="none" stroke="white" opacity=".28" stroke-width="1"/></svg><div><div class="hero-label"><span></span> CENTRO DE CONTROL</div><h1>Panel comercial</h1><p>Una vista general del rendimiento de tu negocio.</p></div><button @click="go('Clientes')"><Users :size="16"/> Ver clientes</button></section>
<div class="section-title"><div><div class="eyebrow"><i/> ANALÍTICA</div><h2>Resumen de rendimiento</h2></div><label class="select-btn"><CalendarDays :size="15"/><select v-model.number="period"><option :value="0">Este mes</option><option :value="1">Mes anterior</option></select><ChevronDown :size="13"/></label></div>
<div class="metrics"><article v-for="(m,i) in metrics" :key="m.label"><div class="metric-top"><span>{{m.label}}</span><b :class="'tile c'+i"><component :is="m.icon" :size="17"/></b></div><strong>{{m.value}}</strong><div class="comparison"><span :class="{down:m.change<0}"><component :is="m.change<0?ArrowDownRight:ArrowUpRight" :size="12"/>{{m.change>0?'+':''}}{{m.change}}%</span> vs. mes anterior</div></article></div>
<div class="charts"><SalesTrend :records="all"/><section class="panel"><div class="card-head"><div><h2>Pipeline comercial</h2><p>Oportunidades por etapa</p></div><span class="sub-icon"><Filter :size="16"/></span></div><div class="donut-wrap"><PipelineRing :groups="groups" :total="rows.length"/><div class="keys"><div v-for="g in groups" :key="g.label"><i :style="{background:g.color}"/>{{g.label}}<b>{{g.n}}</b></div></div></div></section></div>
</template>
<section v-if="['Resumen general','Oportunidades'].includes(section)" class="panel table-panel"><div class="card-head"><div><div v-if="section==='Resumen general'" class="eyebrow"><i/> SEGUIMIENTO</div><h2>{{section==='Resumen general'?'Oportunidades recientes':'Oportunidades'}}</h2></div><button v-if="section==='Resumen general'" class="text-btn" @click="go('Oportunidades')">Ver todas <ArrowRight :size="14"/></button><button v-else class="primary" @click="go('Clientes')"><Users :size="15"/> Ver clientes</button></div><div v-if="section==='Oportunidades'" class="filters"><input v-model="query" placeholder="Buscar oportunidad o cliente"><select v-model="stage"><option>Todas</option><option v-for="s in stages" :key="s">{{s}}</option></select></div><div class="table-scroll"><table><thead><tr><th>Oportunidad</th><th>Cliente</th><th>Etapa</th><th>Valor</th><th></th></tr></thead><tbody><tr v-for="(r,i) in visibleRecords" :key="r.id" @click="inspect(r)"><td><span class="cell-icon" :class="'c'+(i%4)"><component :is="[Layers3,Handshake,Zap,ChartNoAxesCombined][i%4]" :size="15"/></span>{{r.name}}</td><td>{{accountName(r)}}</td><td><span class="pill" :style="{color:colors[stages.indexOf(r.stage)],background:colors[stages.indexOf(r.stage)]+'18'}">● &nbsp;{{r.stage}}</span></td><td>{{money(+r.amount)}}</td><td><button aria-label="Ver detalle" @click.stop="inspect(r)"><Ellipsis :size="16"/></button></td></tr></tbody></table><p v-if="!tableRecords.length" class="empty">No hay oportunidades para mostrar.</p></div><TablePager :page="tablePage" :size="tableSize" :total="tableRecords.length" @change="tablePage=$event"/></section>
<section v-if="section==='Actividad'" class="panel"><div class="card-head"><div><div class="eyebrow"><i/> ESPACIO DE TRABAJO</div><h2>{{section}}</h2></div></div><template v-if="all.length"><button v-for="r in all.slice(0,30)" :key="r.id" class="list-item" @click="inspect(r)"><Activity :size="17"/><span>{{r.name}} · {{accountName(r)}}</span><small>{{new Date(r.created_at).toLocaleDateString('es')}}</small></button></template><p v-if="!all.length">Todavía no hay registros.</p></section>
</main></div>
<div v-if="modal" class="overlay" @click.self="modal=''"><section role="dialog" aria-modal="true" :aria-label="modal" class="dialog"><div class="card-head"><h2>{{modal}}</h2><button aria-label="Cerrar" @click="modal=''"><X :size="19"/></button></div>
<template v-if="modal==='Oportunidades del cliente'&&activeClient"><h3>{{activeClient.name}}</h3><button class="primary" @click="modal='Nueva oportunidad'"><Plus :size="15"/> Nueva oportunidad</button><button v-for="r in clientDeals.slice((clientPage-1)*5,clientPage*5)" :key="r.id" class="list-item" @click="inspect(r)"><span>{{r.name}} · {{r.stage}}</span><b>{{money(+r.amount)}}</b></button><p v-if="!clientDeals.length">Este cliente aún no tiene oportunidades.</p><TablePager v-if="clientDeals.length>5" :page="clientPage" :size="5" :total="clientDeals.length" @change="clientPage=$event"/></template>
<form v-else-if="modal==='Nueva oportunidad'" @submit.prevent="submit"><label>Nombre de la oportunidad<input v-model="form.name" required maxlength="255" autofocus/></label><p>Cliente: <strong>{{activeClient?.name}}</strong></p><label>Valor (USD)<input v-model="form.amount" type="number" min="0" max="999999999999.99" step=".01" required/></label><label>Etapa<select v-model="form.stage"><option v-for="s in stages" :key="s">{{s}}</option></select></label><p v-for="error in form.errors" :key="error">{{error}}</p><button class="primary" :disabled="form.processing">Guardar oportunidad</button></form>
<template v-else-if="modal==='Buscar'"><input v-model="query" v-focus placeholder="Buscar oportunidades o clientes"><button v-for="r in results.slice(0,8)" :key="r.id" class="list-item" @click="inspect(r)">{{r.name}} · {{accountName(r)}}<ChevronRight/></button><p v-if="!results.length">Sin resultados.</p></template>
<template v-else-if="modal==='Detalle'&&detail"><h3>{{detail.name}}</h3><p>{{accountName(detail)}}</p><div class="list-item">Etapa <select :value="detail.stage" @change="changeStage(($event.target as HTMLSelectElement).value)"><option v-for="s in stages" :key="s">{{s}}</option></select></div><div class="list-item">Valor <b>{{money(+detail.amount)}}</b></div></template>
<p v-else-if="modal==='Notificaciones'">No tienes notificaciones pendientes.</p><p v-else-if="modal==='Centro de ayuda'">Abre Clientes, elige «Oportunidades» junto al cliente y pulsa «Nueva oportunidad». Registra su importe y etapa comercial. Consulta tus registros desde Oportunidades o búscalos con Ctrl + K.</p>
<template v-else><p>{{demo?'Estás explorando el espacio de demostración. Accede a tu cuenta para trabajar con los datos de tu equipo.':name}}</p><Link class="primary" :href="demo?(page.props.currentTeam?dashboard(page.props.currentTeam.slug):login()):edit()">{{demo?'Ir a mi espacio':'Ajustes de perfil'}} <ArrowRight :size="15"/></Link></template></section></div><div v-if="notice" class="toast"><Check :size="16"/>{{notice}}</div></div></template>
<style src="../../css/commercial.css"></style>
