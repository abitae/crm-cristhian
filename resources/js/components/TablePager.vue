<script setup lang="ts">
import {computed} from 'vue';
import { ChevronLeft,ChevronRight } from '@lucide/vue';
const props=defineProps<{page:number;size:number;total:number}>();
const emit=defineEmits<{change:[page:number]}>();
const pages=computed(()=>Math.max(1,Math.ceil(props.total/props.size)));
</script>
<template><nav class="table-pager" aria-label="Paginación de la tabla"><span aria-live="polite">{{total?1+(page-1)*size:0}}–{{Math.min(page*size,total)}} de {{total}} registros</span><div><button aria-label="Página anterior" :disabled="page<=1" @click="emit('change',page-1)"><ChevronLeft :size="15"/></button><span>Página <b>{{page}}</b> de {{pages}}</span><button aria-label="Página siguiente" :disabled="page>=pages" @click="emit('change',page+1)"><ChevronRight :size="15"/></button></div></nav></template>
<style scoped>
.table-pager,.table-pager div{display:flex;align-items:center;justify-content:space-between;gap:10px}.table-pager{padding:13px 20px;border-top:1px solid #ededf0;color:#737882;font-size:11px}.table-pager button{background:white;border:1px solid #dcdee2;border-radius:6px;padding:5px;color:#4c535c}.table-pager button:disabled{opacity:.35;cursor:default}.table-pager b{color:#30343a}@media(max-width:500px){.table-pager{padding:12px;gap:5px}.table-pager div{gap:6px}}
</style>
