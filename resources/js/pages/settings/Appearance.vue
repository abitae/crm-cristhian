<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Palette } from '@lucide/vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ajustes de apariencia',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Ajustes de apariencia" />

    <h1 class="sr-only">Ajustes de apariencia</h1>

    <section class="settings-hero">
        <svg
            class="waves"
            viewBox="0 0 800 190"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <path
                v-for="n in 42"
                :key="n"
                :d="`M${-110 + n * 6} 200 C${150 + n * 6} ${240 - n * 3},${170 + n * 7} ${-40 + n * 2},${365 + n * 6} ${65 + n * 2} S${640 + n * 7} ${235 - n * 4},${740 + n * 6} -25`"
                fill="none"
                stroke="white"
                opacity=".28"
                stroke-width="1"
            />
        </svg>
        <div>
            <div class="hero-label"><span /> PREFERENCIAS</div>
            <h1>Apariencia</h1>
            <p>Elige cómo se ve la interfaz en tu cuenta.</p>
        </div>
        <span class="hero-icon"><Palette :size="22" /></span>
    </section>

    <div class="settings-stack">
        <section class="panel">
            <div class="card-head">
                <div>
                    <div class="eyebrow"><i /> TEMA</div>
                    <h2>Modo de visualización</h2>
                    <p>Puedes usar el tema del sistema o fijar claro / oscuro.</p>
                </div>
            </div>
            <div class="appearance-wrap">
                <AppearanceTabs />
            </div>
        </section>
    </div>
</template>

<style scoped>
.hero-icon {
    position: relative;
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #ffffff22;
    border: 1px solid #ffffff33;
}

.appearance-wrap {
    margin-top: 18px;
}
</style>
