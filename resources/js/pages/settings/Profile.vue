<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, UserRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Team } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ajustes de perfil',
                href: edit(),
            },
        ],
    },
});

const page = usePage<{
    auth: {
        user: {
            name: string;
            email: string;
            email_verified_at: string | null;
        };
    };
    currentTeam: Team | null;
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const user = computed(() => page.props.auth.user);
const name = ref(user.value.name);
const email = ref(user.value.email);

watch(
    user,
    (value) => {
        name.value = value.name;
        email.value = value.email;
    },
    { deep: true },
);

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : '/',
);
</script>

<template>
    <Head title="Ajustes de perfil" />

    <h1 class="sr-only">Ajustes de perfil</h1>

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
            <div class="hero-label"><span /> CUENTA PERSONAL</div>
            <h1>Perfil</h1>
            <p>Actualiza tu nombre y correo para mantener tu cuenta al día.</p>
        </div>
        <Link :href="dashboardUrl" class="hero-cta">
            <ArrowLeft :size="16" />
            Volver al panel
        </Link>
    </section>

    <div class="settings-stack">
        <section class="panel">
            <div class="card-head">
                <div>
                    <div class="eyebrow"><i /> INFORMACIÓN</div>
                    <h2>Datos del perfil</h2>
                    <p>
                        Estos datos aparecen en tu espacio de trabajo y en las
                        invitaciones.
                    </p>
                </div>
                <span class="sub-icon"><UserRound :size="16" /></span>
            </div>

            <Form
                v-bind="ProfileController.update.form()"
                class="settings-form"
                v-slot="{ errors, processing }"
            >
                <label for="name">
                    Nombre
                    <input
                        id="name"
                        v-model="name"
                        name="name"
                        required
                        autocomplete="name"
                        placeholder="Nombre completo"
                        maxlength="255"
                    />
                    <span v-if="errors.name" class="field-error">{{
                        errors.name
                    }}</span>
                </label>

                <label for="email">
                    Correo electrónico
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                        placeholder="correo@ejemplo.com"
                        maxlength="255"
                    />
                    <span v-if="errors.email" class="field-error">{{
                        errors.email
                    }}</span>
                </label>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                    class="verify-note"
                >
                    Tu correo aún no está verificado.
                    <Link :href="send()" as="button">
                        Reenviar correo de verificación
                    </Link>
                    <p
                        v-if="page.props.status === 'verification-link-sent'"
                        class="success-note"
                    >
                        Se envió un nuevo enlace de verificación a tu correo.
                    </p>
                </div>

                <button
                    class="primary"
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    Guardar cambios
                </button>
            </Form>
        </section>

        <DeleteUser />
    </div>
</template>
