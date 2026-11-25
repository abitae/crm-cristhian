<script setup lang="ts">
import { computed } from 'vue';
const props=defineProps<{groups:{n:number;color:string}[];total:number}>();
const arcs=computed(()=>{let start=0;return props.groups.map(g=>{const length=props.total?100*g.n/props.total:0;const arc={start,length,color:g.color};start+=length;return arc})});
</script>
<template><svg class="pipeline-ring" viewBox="0 0 150 150" aria-label="Distribución por etapa"><circle cx="75" cy="75" r="63" fill="none" stroke="#f1f1f2" stroke-width="20"/><g transform="rotate(-90 75 75)"><circle v-for="a in arcs.filter(a=>a.length)" :key="a.color" cx="75" cy="75" r="63" fill="none" :stroke="a.color" stroke-width="20" pathLength="100" :stroke-dasharray="`${Math.max(0,a.length-.7)} 100`" :stroke-dashoffset="-a.start"/></g><text x="75" y="78" text-anchor="middle" fill="#252832" font-size="29" font-weight="650">{{total}}</text><text x="75" y="95" text-anchor="middle" fill="#82868c" font-size="11">En total</text></svg></template>
