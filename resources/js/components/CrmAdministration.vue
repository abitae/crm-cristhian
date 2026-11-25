<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    KeyRound,
    Pencil,
    Plus,
    ShieldCheck,
    UserPlus,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { password as resetPassword, store } from '@/routes/crm-users';
import { edit as editTeam, index as teamsIndex, store as storeTeam } from '@/routes/teams';

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    teams_count: number;
    created_at: string | null;
};

export type AdminTeam = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    members_count: number;
    owner_name: string | null;
    created_at: string | null;
};

const props = withDefaults(
    defineProps<{
        users?: AdminUser[];
        teams?: AdminTeam[];
    }>(),
    {
        users: () => [],
        teams: () => [],
    },
);

const page = usePage();
const userForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const teamForm = useForm({ name: '' });
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});
const showUserForm = ref(false);
const showTeamForm = ref(false);
const resetting = ref<AdminUser | null>(null);

const canCreateUsers = computed(() => page.props.canCreateUsers);
const canCreateTeams = computed(() => page.props.canCreateTeams);

function roleLabel(role: string) {
    if (role === 'super-admin') return 'Superadmin';
    if (role === 'crm-user') return 'Usuario CRM';
    return role;
}

function formatDate(value: string | null) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString('es', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return value;
    }
}

function submitUser() {
    userForm.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            userForm.reset();
            showUserForm.value = false;
        },
    });
}

function submitTeam() {
    teamForm.post(storeTeam.url(), {
        preserveScroll: true,
        onSuccess: () => {
            teamForm.reset();
            showTeamForm.value = false;
        },
    });
}

function openPasswordReset(user: AdminUser) {
    resetting.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
}

function closePasswordReset() {
    resetting.value = null;
    passwordForm.reset();
    passwordForm.clearErrors();
}

function submitPasswordReset() {
    if (!resetting.value) return;
    passwordForm.put(resetPassword.url(resetting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePasswordReset();
        },
    });
}
</script>

<template>
    <section class="admin-space">
        <div class="eyebrow"><i /> SUPERADMINISTRADOR</div>
        <h1>Administración</h1>
        <p class="intro">
            Consulta primero los usuarios y equipos del CRM, y crea nuevos accesos
            cuando lo necesites.
        </p>

        <div class="lists-grid">
            <section class="panel table-panel">
                <div class="card-head">
                    <div>
                        <div class="eyebrow"><i /> ACCESOS</div>
                        <h2>Usuarios</h2>
                        <p>{{ users.length }} cuentas registradas en el sistema.</p>
                    </div>
                    <button
                        v-if="canCreateUsers"
                        type="button"
                        class="primary"
                        @click="showUserForm = !showUserForm"
                    >
                        <UserPlus :size="15" />
                        {{ showUserForm ? 'Ocultar formulario' : 'Nuevo usuario' }}
                    </button>
                </div>

                <form
                    v-if="canCreateUsers && showUserForm"
                    class="inline-form"
                    @submit.prevent="submitUser"
                >
                    <label
                        >Nombre
                        <input
                            v-model="userForm.name"
                            required
                            maxlength="255"
                            autocomplete="off"
                        />
                        <small>{{ userForm.errors.name }}</small>
                    </label>
                    <label
                        >Correo
                        <input
                            v-model="userForm.email"
                            type="email"
                            required
                            maxlength="255"
                            autocomplete="off"
                        />
                        <small>{{ userForm.errors.email }}</small>
                    </label>
                    <label
                        >Contraseña
                        <input
                            v-model="userForm.password"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                        <small>{{ userForm.errors.password }}</small>
                    </label>
                    <label
                        >Confirmar
                        <input
                            v-model="userForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                        />
                    </label>
                    <button class="primary" :disabled="userForm.processing">
                        <UserPlus :size="15" /> Crear usuario
                    </button>
                </form>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Equipos</th>
                                <th>Alta</th>
                                <th v-if="canCreateUsers"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, i) in users" :key="user.id">
                                <td>
                                    <div class="identity">
                                        <b class="avatar-chip" :class="'c' + (i % 4)">{{
                                            user.name
                                                .split(/\s+/)
                                                .slice(0, 2)
                                                .map((part) => part[0])
                                                .join('')
                                                .toUpperCase()
                                        }}</b>
                                        <div>
                                            {{ user.name }}
                                            <small>{{ user.email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        v-for="role in user.roles"
                                        :key="role"
                                        class="role-chip"
                                        :data-role="role"
                                        >{{ roleLabel(role) }}</span
                                    >
                                    <span v-if="!user.roles.length">—</span>
                                </td>
                                <td>{{ user.teams_count }}</td>
                                <td>{{ formatDate(user.created_at) }}</td>
                                <td v-if="canCreateUsers">
                                    <button
                                        type="button"
                                        class="text-btn"
                                        data-test="reset-password-button"
                                        :aria-label="'Restablecer contraseña de ' + user.name"
                                        @click="openPasswordReset(user)"
                                    >
                                        <KeyRound :size="14" /> Restablecer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!users.length" class="empty">
                        Todavía no hay usuarios para mostrar.
                    </p>
                </div>
            </section>

            <section class="panel table-panel">
                <div class="card-head">
                    <div>
                        <div class="eyebrow"><i /> ORGANIZACIÓN</div>
                        <h2>Equipos</h2>
                        <p>{{ teams.length }} espacios de trabajo activos.</p>
                    </div>
                    <div class="head-actions">
                        <Link class="text-btn" :href="teamsIndex()">
                            Gestionar <ArrowRight :size="14" />
                        </Link>
                        <button
                            v-if="canCreateTeams"
                            type="button"
                            class="primary"
                            @click="showTeamForm = !showTeamForm"
                        >
                            <Plus :size="15" />
                            {{ showTeamForm ? 'Ocultar formulario' : 'Nuevo equipo' }}
                        </button>
                    </div>
                </div>

                <form
                    v-if="canCreateTeams && showTeamForm"
                    class="inline-form team-form"
                    @submit.prevent="submitTeam"
                >
                    <label
                        >Nombre del equipo
                        <input
                            v-model="teamForm.name"
                            required
                            maxlength="255"
                            placeholder="Ej. Comercial Open9"
                        />
                        <small>{{ teamForm.errors.name }}</small>
                    </label>
                    <button class="primary" :disabled="teamForm.processing">
                        <Building2 :size="15" /> Crear equipo
                    </button>
                    <div class="note">
                        <ShieldCheck :size="16" /> Solo el superadministrador puede crear
                        equipos.
                    </div>
                </form>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipo</th>
                                <th>Propietario</th>
                                <th>Miembros</th>
                                <th>Alta</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="team in teams" :key="team.id">
                                <td>
                                    <div class="identity">
                                        <span class="tile c1"><Users :size="15" /></span>
                                        <div>
                                            {{ team.name }}
                                            <small>{{
                                                team.is_personal
                                                    ? 'Espacio personal'
                                                    : 'Equipo compartido'
                                            }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ team.owner_name || '—' }}</td>
                                <td>{{ team.members_count }}</td>
                                <td>{{ formatDate(team.created_at) }}</td>
                                <td>
                                    <Link
                                        class="text-btn"
                                        :href="editTeam(team.slug)"
                                        :aria-label="'Editar ' + team.name"
                                    >
                                        <Pencil :size="14" /> Editar
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!teams.length" class="empty">
                        Todavía no hay equipos para mostrar.
                    </p>
                </div>
            </section>
        </div>

        <div
            v-if="resetting"
            class="overlay"
            @click.self="closePasswordReset"
            @keydown.esc="closePasswordReset"
        >
            <section
                class="dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reset-password-title"
            >
                <div class="card-head">
                    <h2 id="reset-password-title">Restablecer contraseña</h2>
                    <button type="button" aria-label="Cerrar" @click="closePasswordReset">
                        <X :size="18" />
                    </button>
                </div>
                <p class="dialog-copy">
                    Define una nueva contraseña para
                    <strong>{{ resetting.name }}</strong>
                    ({{ resetting.email }}).
                </p>
                <form class="password-form" @submit.prevent="submitPasswordReset">
                    <label
                        >Nueva contraseña
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            data-test="reset-password-input"
                        />
                        <small>{{ passwordForm.errors.password }}</small>
                    </label>
                    <label
                        >Confirmar contraseña
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            data-test="reset-password-confirmation"
                        />
                    </label>
                    <div class="dialog-actions">
                        <button type="button" class="text-btn" @click="closePasswordReset">
                            Cancelar
                        </button>
                        <button
                            class="primary"
                            :disabled="passwordForm.processing"
                            data-test="confirm-reset-password-button"
                        >
                            <KeyRound :size="15" />
                            Guardar contraseña
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</template>

<style scoped>
.admin-space h1 {
    font-size: 32px;
    font-weight: 650;
    letter-spacing: -1.2px;
}
.intro {
    font-size: 13px;
    color: #747984;
    margin: 4px 0 22px;
}
.lists-grid {
    display: grid;
    gap: 16px;
}
.admin-space .card-head {
    align-items: center;
    padding: 17px 20px;
}
.admin-space .card-head h2 {
    margin: 0;
}
.head-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.identity {
    display: flex;
    align-items: center;
    gap: 10px;
}
.identity small {
    display: block;
    color: #7c818a;
    font-weight: 400;
    font-size: 11px;
}
.avatar-chip {
    width: 31px;
    height: 31px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    font-size: 11px;
    flex-shrink: 0;
}
.role-chip {
    display: inline-flex;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 650;
    background: #f2effb;
    color: #75649d;
    margin-right: 4px;
}
.role-chip[data-role='super-admin'] {
    background: #fff0e8;
    color: #c25d2d;
}
.role-chip[data-role='crm-user'] {
    background: #eaf6ef;
    color: #458365;
}
.inline-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 0 20px 18px;
    border-bottom: 1px solid #eff0f1;
}
.inline-form.team-form {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
}
.inline-form label {
    display: grid;
    gap: 5px;
    font-size: 12px;
    font-weight: 550;
}
.inline-form input {
    border: 1px solid #dddfe3;
    border-radius: 6px;
    padding: 9px 11px;
    background: white;
}
.inline-form small {
    color: #ae4439;
}
.inline-form small:empty {
    display: none;
}
.inline-form .primary {
    justify-self: start;
    align-self: end;
}
.note {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #737984;
    grid-column: 1 / -1;
}
.empty {
    padding: 20px;
    color: #999;
}
.admin-space .text-btn {
    text-decoration: none;
}
.dialog-copy {
    font-size: 13px;
    color: #747984;
    margin: 0 0 16px;
    line-height: 1.5;
}
.password-form {
    display: grid;
    gap: 12px;
}
.password-form label {
    display: grid;
    gap: 5px;
    font-size: 12px;
    font-weight: 550;
}
.password-form input {
    border: 1px solid #dddfe3;
    border-radius: 6px;
    padding: 9px 11px;
    background: white;
}
.password-form small {
    color: #ae4439;
}
.password-form small:empty {
    display: none;
}
.dialog-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 4px;
}
@media (max-width: 900px) {
    .inline-form,
    .inline-form.team-form {
        grid-template-columns: 1fr;
    }
    .admin-space .card-head {
        align-items: start;
        flex-direction: column;
    }
}
</style>
