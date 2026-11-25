<script setup lang="ts">
import { computed,ref } from 'vue';
import { ChevronDown } from '@lucide/vue';
import { money,type Opportunity } from '@/lib/commercial';
const props=defineProps<{records:Opportunity[]}>();
const months=ref(6);
const data=computed(()=>Array.from({length:months.value},(_,i)=>{
 const d=new Date(); d.setDate(1); d.setMonth(d.getMonth()-months.value+1+i);
 const monthRows=props.records.filter(r=>new Date(r.created_at).getMonth()===d.getMonth()&&new Date(r.created_at).getFullYear()===d.getFullYear());
 return {label:d.toLocaleString('es',{month:'short'}).replace('.',''),sum:monthRows.filter(r=>r.stage==='Ganada').reduce((a,r)=>a+Number(r.amount),0)};
}));
const max=computed(()=>Math.max(60000,...data.value.map(r=>Math.ceil(r.sum/20000)*20000)));
const points=computed(()=>data.value.map((r,i)=>[i*500/(data.value.length-1),139-r.sum/max.value*134]));
const curve=computed(()=>points.value.reduce((s,p,i,ar)=>{if(!i)return `M0 ${p[1]}`;const prev=ar[i-1],m=(p[0]+prev[0])/2;return `${s} C${m} ${prev[1]},${m} ${p[1]},${p[0]} ${p[1]}`},''));
</script>
<template><section class="panel trend"><div class="card-head"><div><h2>Evolución de ventas</h2><p>Ingresos generados en el tiempo</p></div><label class="select-btn"><select v-model.number="months"><option :value="6">6 meses</option><option :value="3">3 meses</option></select><ChevronDown :size="13"/></label></div>
<div class="legend"><i></i> Ingresos</div><div class="plot"><div class="axis"><span v-for="k in [3,2,1,0]" :key="k">{{ k?'$'+Math.round(max*k/3000)+'k':'$0' }}</span></div><div class="graph"><svg viewBox="0 0 500 145" preserveAspectRatio="none" role="img" aria-label="Ingresos por mes"><defs><linearGradient id="sales-fill" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#ff9969" stop-opacity=".35"/><stop offset="1" stop-color="#ffad84" stop-opacity=".035"/></linearGradient></defs><path v-for="y in [5,50,94,139]" :key="y" :d="`M0 ${y} H500`" stroke="#e8e9eb" stroke-dasharray="4 4"/><path :d="curve+' V139 H0 Z'" fill="url(#sales-fill)"/><path :d="curve" fill="none" stroke="#f87540" stroke-width="3" vector-effect="non-scaling-stroke"/><circle v-for="(p,i) in points" :key="i" :cx="p[0]" :cy="p[1]" r="3.6" fill="white" stroke="#f87540" stroke-width="2"><title>{{ data[i].label }}: {{money(data[i].sum)}}</title></circle></svg><div class="months"><span v-for="d in data" :key="d.label">{{d.label}}</span></div></div></div></section></template>
