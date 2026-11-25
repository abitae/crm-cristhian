<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, Shield } from '@lucide/vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { edit } from '@/routes/security';

// oxfmt-ignore
type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ajustes de seguridad',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Ajustes de seguridad" />

    <h1 class="sr-only">Ajustes de seguridad</h1>

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
            <div class="hero-label"><span /> PROTECCIÓN</div>
            <h1>Seguridad</h1>
            <p>Gestiona tu contraseña y los métodos de acceso a tu cuenta.</p>
        </div>
        <span class="hero-icon"><Shield :size="22" /></span>
    </section>

    <div class="settings-stack">
        <section class="panel">
            <div class="card-head">
                <div>
                    <div class="eyebrow"><i /> CONTRASEÑA</div>
                    <h2>Actualizar contraseña</h2>
                    <p>Usa una contraseña larga y aleatoria para mantener tu cuenta segura.</p>
                </div>
                <span class="sub-icon"><KeyRound :size="16" /></span>
            </div>

            <Form
                v-bind="SecurityController.update.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="settings-form"
                v-slot="{ errors, processing }"
            >
                <label for="current_password">
                    Contraseña actual
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        placeholder="Contraseña actual"
                    />
                    <span v-if="errors.current_password" class="field-error">
                        {{ errors.current_password }}
                    </span>
                </label>

                <label for="password">
                    Nueva contraseña
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="Nueva contraseña"
                        :passwordrules="props.passwordRules"
                    />
                    <span v-if="errors.password" class="field-error">
                        {{ errors.password }}
                    </span>
                </label>

                <label for="password_confirmation">
                    Confirmar contraseña
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder="Confirmar contraseña"
                        :passwordrules="props.passwordRules"
                    />
                    <span v-if="errors.password_confirmation" class="field-error">
                        {{ errors.password_confirmation }}
                    </span>
                </label>

                <button
                    class="primary"
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Guardar contraseña
                </button>
            </Form>
        </section>

        <ManageTwoFactor
            :canManageTwoFactor="canManageTwoFactor"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />

        <ManagePasskeys
            :canManagePasskeys="canManagePasskeys"
            :passkeys="passkeys"
        />
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
</style>
