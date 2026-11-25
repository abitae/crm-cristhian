<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2, X } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';

const open = ref(false);
const passwordInput = useTemplateRef('passwordInput');

function close() {
    open.value = false;
}
</script>

<template>
    <section class="panel danger-panel">
        <div class="card-head">
            <div>
                <div class="eyebrow"><i /> ZONA DE RIESGO</div>
                <h2>Eliminar cuenta</h2>
                <p>Esta acción borra tu cuenta y todos sus recursos de forma permanente.</p>
            </div>
            <span class="sub-icon"><Trash2 :size="16" /></span>
        </div>

        <div class="danger-copy">
            <strong>Advertencia</strong>
            <p>Procede con cuidado: no se puede deshacer.</p>
        </div>

        <button
            type="button"
            class="danger-btn"
            data-test="delete-user-button"
            @click="open = true"
        >
            Eliminar cuenta
        </button>
    </section>

    <div
        v-if="open"
        class="overlay"
        @click.self="close"
        @keydown.esc="close"
    >
        <section
            class="dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-account-title"
        >
            <div class="card-head">
                <h2 id="delete-account-title">¿Eliminar tu cuenta?</h2>
                <button type="button" aria-label="Cerrar" @click="close">
                    <X :size="19" />
                </button>
            </div>

            <Form
                v-bind="ProfileController.destroy.form()"
                reset-on-success
                :options="{ preserveScroll: true }"
                class="settings-form"
                v-slot="{ errors, processing, reset, clearErrors }"
                @error="() => passwordInput?.focus()"
            >
                <p>
                    Una vez eliminada, todos tus datos se borrarán de forma
                    permanente. Confirma con tu contraseña para continuar.
                </p>

                <label for="password">
                    Contraseña
                    <PasswordInput
                        id="password"
                        name="password"
                        ref="passwordInput"
                        placeholder="Tu contraseña"
                    />
                    <InputError :message="errors.password" />
                </label>

                <div class="dialog-actions">
                    <button
                        type="button"
                        class="text-btn"
                        @click="
                            () => {
                                clearErrors();
                                reset();
                                close();
                            }
                        "
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="danger-btn"
                        :disabled="processing"
                        data-test="confirm-delete-user-button"
                    >
                        Confirmar eliminación
                    </button>
                </div>
            </Form>
        </section>
    </div>
</template>

<style scoped>
.dialog-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 8px;
}

.dialog-actions .danger-btn {
    margin-top: 0;
}
</style>
