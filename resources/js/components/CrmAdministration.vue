<script setup lang="ts">
import {Link,useForm} from '@inertiajs/vue3';
import {ArrowRight,Check,ShieldCheck,UserPlus,Users} from '@lucide/vue';
import {ref} from 'vue';
import {store} from '@/routes/crm-users';
import {index} from '@/routes/teams';

const form=useForm({name:'',email:'',password:'',password_confirmation:''});
const created=ref('');
function submit(){
    created.value='';
    form.post(store.url(),{preserveScroll:true,onSuccess:()=>{created.value=form.email;form.reset()}});
}
</script>
<template><section class="admin-space">
<div class="eyebrow"><i/> SUPERADMINISTRADOR</div>
<h1>Administración</h1><p class="intro">Gestiona el acceso de usuarios y equipos al CRM.</p>
<div class="admin-grid"><section v-if="$page.props.canCreateUsers" class="panel">
<div class="card-head"><div><h2>Crear usuario</h2><p>La cuenta comenzará en su propio espacio de trabajo.</p></div><span class="tile c0"><UserPlus :size="17"/></span></div>
<form @submit.prevent="submit">
<label>Nombre<input v-model="form.name" required maxlength="255" autocomplete="off"><small>{{form.errors.name}}</small></label>
<label>Correo electrónico<input v-model="form.email" type="email" required maxlength="255" autocomplete="off"><small>{{form.errors.email}}</small></label>
<label>Contraseña<input v-model="form.password" type="password" required autocomplete="new-password"><small>{{form.errors.password}}</small></label>
<label>Confirmar contraseña<input v-model="form.password_confirmation" type="password" required autocomplete="new-password"></label>
<button class="primary" :disabled="form.processing"><UserPlus :size="15"/> Crear usuario</button>
<p v-if="created" class="success" role="status"><Check :size="15"/> {{created}} ya puede iniciar sesión.</p></form></section>
<section v-if="$page.props.canCreateTeams" class="panel"><div class="card-head"><div><h2>Equipos</h2><p>Crea equipos y gestiona sus miembros.</p></div><span class="tile c1"><Users :size="17"/></span></div><Link class="text-btn" :href="index()">Gestionar equipos <ArrowRight :size="14"/></Link><div class="note"><ShieldCheck :size="16"/> Solo el superadministrador puede crear equipos.</div></section></div></section></template>
<style scoped>
.admin-space h1{font-size:32px;font-weight:650;letter-spacing:-1.2px}.intro{font-size:13px;color:#747984;margin:4px 0 22px}.admin-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:16px;align-items:start}.admin-space form{display:grid;gap:12px;margin-top:18px}.admin-space label{display:grid;gap:5px;font-size:12px;font-weight:550}.admin-space input{border:1px solid #dddfe3;border-radius:6px;padding:9px 11px}.admin-space small{color:#ae4439}.admin-space small:empty{display:none}.admin-space .primary{justify-self:start}.admin-space .text-btn{margin-top:20px}.note,.success{display:flex;align-items:center;gap:7px;font-size:12px;color:#737984}.note{border-top:1px solid #eee;margin-top:20px;padding-top:14px}.success{color:#367c55}@media(max-width:700px){.admin-grid{grid-template-columns:1fr}}
</style>
