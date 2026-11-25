<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeft,
    ChevronDown,
    House,
    LayoutDashboard,
    Menu,
    Palette,
    Shield,
    UserRound,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as teams } from '@/routes/teams';
import type { NavItem, Team, User } from '@/types';

const page = usePage<{ auth: { user: User | null }; currentTeam: Team | null }>();
const mobile = ref(false);
const { isCurrentOrParentUrl } = useCurrentUrl();

const name = computed(() => page.props.auth.user?.name ?? 'Usuario');
const initials = (value: string) =>
    value
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : '/',
);

const sidebarNavItems: (NavItem & { icon: unknown })[] = [
    {
        title: 'Perfil',
        href: editProfile(),
        icon: UserRound,
    },
    {
        title: 'Seguridad',
        href: editSecurity(),
        icon: Shield,
    },
    {
        title: 'Equipos',
        href: teams(),
        icon: Users,
    },
    {
        title: 'Apariencia',
        href: editAppearance(),
        icon: Palette,
    },
];

const activeTitle = computed(
    () =>
        sidebarNavItems.find((item) => isCurrentOrParentUrl(item.href))
            ?.title ?? 'Ajustes',
);
</script>

<template>
    <div class="crm settings-shell">
        <div v-if="mobile" class="shade" @click="mobile = false" />

        <aside :class="{ open: mobile }">
            <Link :href="dashboardUrl" class="brand" @click="mobile = false">
                <span class="logomark">
                    <Activity :size="26" :stroke-width="3" />
                </span>
                Pulso
                <span class="crm-tag">CRM</span>
            </Link>

            <div class="nav-wrap">
                <small>ESPACIO DE TRABAJO</small>
                <Link
                    :href="dashboardUrl"
                    class="nav-link"
                    @click="mobile = false"
                >
                    <LayoutDashboard />
                    Panel comercial
                </Link>

                <div class="nav-line" />

                <small>AJUSTES DE CUENTA</small>
                <Link
                    v-for="item in sidebarNavItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    class="nav-link"
                    :class="{ active: isCurrentOrParentUrl(item.href) }"
                    @click="mobile = false"
                >
                    <component :is="item.icon" />
                    {{ item.title }}
                    <span
                        v-if="isCurrentOrParentUrl(item.href)"
                        class="active-dot"
                    />
                </Link>
            </div>

            <Link
                :href="editProfile()"
                class="user-box"
                @click="mobile = false"
            >
                <b class="avatar">{{ initials(name) }}</b>
                <span>
                    <strong>{{ name }}</strong>
                    <em>Mi cuenta</em>
                </span>
                <ChevronDown :size="15" />
            </Link>
        </aside>

        <div class="main">
            <header>
                <div class="breadcrumbs">
                    <button
                        class="mobile-toggle"
                        aria-label="Abrir menú"
                        @click="mobile = true"
                    >
                        <Menu />
                    </button>
                    <House :size="16" />
                    <span>/</span>
                    Mi espacio
                    <span>/</span>
                    <b>{{ activeTitle }}</b>
                </div>

                <div class="tools">
                    <Link :href="dashboardUrl" class="text-btn back-link">
                        <ArrowLeft :size="14" />
                        Volver al panel
                    </Link>
                    <Link
                        :href="editProfile()"
                        class="avatar small"
                        aria-label="Mi cuenta"
                    >
                        {{ initials(name) }}
                    </Link>
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>

<style src="../../../css/commercial.css"></style>
<style scoped>
.brand {
    color: inherit;
    text-decoration: none;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px;
    border-radius: 7px;
    font-weight: 530;
    color: #61656f;
    text-align: left;
    font-size: 13px;
    text-decoration: none;
}

.nav-link :deep(svg) {
    width: 17px;
    height: 17px;
    stroke-width: 1.7;
    flex-shrink: 0;
}

.nav-link.active {
    color: #d85c2b;
    background: #fff0e8;
    font-weight: 650;
}

.user-box {
    text-decoration: none;
    color: inherit;
}

.back-link {
    text-decoration: none;
}

.settings-shell :deep(.settings-hero) {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background: linear-gradient(110deg, #ed642e 0%, #fc7a3b 56%, #ff9563 100%);
    border-radius: 13px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 26px 29px;
    color: white;
    min-height: 145px;
    margin-bottom: 22px;
}

.settings-shell :deep(.settings-hero .waves) {
    position: absolute;
    z-index: 0;
    inset: 0 0 0 auto;
    width: 68%;
    height: 100%;
    pointer-events: none;
}

.settings-shell :deep(.settings-hero > div),
.settings-shell :deep(.settings-hero > a),
.settings-shell :deep(.settings-hero > button),
.settings-shell :deep(.settings-hero > span) {
    position: relative;
}

.settings-shell :deep(.settings-hero .hero-cta) {
    background: #fff;
    border-radius: 7px;
    color: #753f28;
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 1px 3px #8e45251a;
    text-decoration: none;
}

.settings-shell :deep(.avatar.small) {
    text-decoration: none;
}

.settings-shell :deep(.settings-stack) {
    display: grid;
    gap: 16px;
    max-width: 760px;
}

.settings-shell :deep(.settings-form) {
    display: grid;
    gap: 14px;
    margin-top: 18px;
}

.settings-shell :deep(.settings-form > .primary),
.settings-shell :deep(.settings-form > .danger-btn) {
    justify-self: start;
}

.settings-shell :deep(.settings-form label) {
    display: grid;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
}

.settings-shell :deep(.settings-form input),
.settings-shell :deep(.settings-form select) {
    width: 100%;
    padding: 9px 11px;
    border: 1px solid #d7d9dd;
    border-radius: 6px;
    background: white;
    color: #262a32;
}

.settings-shell :deep(.settings-form input:focus),
.settings-shell :deep(.settings-form select:focus) {
    outline: 2px solid #ffd0b8;
    border-color: #f77c43;
}

.settings-shell :deep(.field-error) {
    color: #b63e38;
    font-size: 12px;
    font-weight: 500;
}

.settings-shell :deep(.verify-note) {
    font-size: 13px;
    color: #797e88;
    line-height: 1.6;
}

.settings-shell :deep(.verify-note a),
.settings-shell :deep(.verify-note button) {
    color: #ce602c;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.settings-shell :deep(.success-note) {
    margin-top: 8px;
    color: #34845e;
    font-size: 13px;
    font-weight: 600;
}

.settings-shell :deep(.danger-panel) {
    border-color: #f0cfc4;
    background: #fff8f5;
}

.settings-shell :deep(.danger-copy) {
    display: grid;
    gap: 4px;
    margin: 14px 0 16px;
}

.settings-shell :deep(.danger-copy strong) {
    color: #b63e38;
    font-size: 13px;
}

.settings-shell :deep(.danger-copy p) {
    color: #8a675c;
    font-size: 12px;
    margin: 0;
}

.settings-shell :deep(.danger-btn) {
    background: #c24b3a;
    color: white;
    border-radius: 7px;
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 600;
}

@media (max-width: 660px) {
    .settings-shell :deep(.settings-hero) {
        display: block;
        padding: 22px;
        min-height: 172px;
    }
}
</style>
