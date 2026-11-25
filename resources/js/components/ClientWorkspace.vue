<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import {
    ArrowRight,
    Check,
    ChevronDown,
    CircleDot,
    Columns3,
    ContactRound,
    History,
    List,
    MessageSquarePlus,
    Pencil,
    Plus,
    PlusCircle,
    Search,
    Settings2,
    Tag,
    X,
} from '@lucide/vue';
import { index, store, update, status as changeStatus } from '@/routes/clients';
import { index as historyIndex, store as historyStore } from '@/routes/clients/histories';
import { store as storeOption } from '@/routes/client-options';
import {
    demoClients,
    demoOptions,
    loadDemo,
    type Client,
    type ClientHistory,
    type ClientOption,
} from '@/lib/clients';
import type { Team, User } from '@/types';
import TablePager from './TablePager.vue';
import ClientKanbanColumn from './ClientKanbanColumn.vue';

const props = defineProps<{ demo: boolean; section: string }>();
const emit = defineEmits<{ navigate: [section: string]; opportunities: [client: Client] }>();
const shared = usePage<{
    auth: { user: User | null };
    currentTeam: Team | null;
    clientOptions?: ClientOption[];
}>();

const samples = ref<Client[]>([]);
const sampleOptions = ref<ClientOption[]>([]);
const sampleHistories = ref<Record<number, ClientHistory[]>>({});
const options = computed(() =>
    props.demo ? sampleOptions.value : (shared.props.clientOptions ?? []),
);
const tags = computed(() => options.value.filter((o) => o.kind === 'tag'));
const statuses = computed(() => options.value.filter((o) => o.kind === 'status'));
const search = ref('');
const tag = ref('');
const status = ref('');
const page = ref(1);
const serverRows = ref<Client[]>([]);
const serverTotal = ref(0);
const refresh = ref(0);
const loading = ref(false);
const failure = ref('');
const size = 8;
const board = computed(() => props.section === 'Kanban');
const dragged = ref<Client | null>(null);
const moveForm = useForm({ status: null as number | null });
const newColumn = ref(false);
const columnForm = useForm({ name: '', kind: 'status' });
const lanes = computed(() =>
    [{ id: null as number | null, name: 'Sin estado' }, ...statuses.value].filter(
        (o) => !status.value || String(o.id) === status.value,
    ),
);
const palette = ['#99a1b0', '#f7a36d', '#9a87d6', '#6aafa2', '#709ed8'];

function move(client: Client, id: number | null) {
    if (!shared.props.currentTeam || moveForm.processing) return;
    moveForm.status = id;
    feedback.value = '';
    moveForm.patch(changeStatus.url([shared.props.currentTeam.slug, client.id]), {
        preserveScroll: true,
        onSuccess: () => {
            refresh.value++;
            feedback.value = 'Estado actualizado.';
            if (editing.value?.id === client.id) loadHistories(client);
        },
        onError: () => {
            feedback.value = 'No se pudo guardar el cambio de estado.';
        },
    });
}

function createColumn() {
    if (!shared.props.currentTeam) return;
    columnForm.post(storeOption.url(shared.props.currentTeam.slug), {
        preserveScroll: true,
        onSuccess: () => {
            columnForm.reset();
            newColumn.value = false;
            status.value = '';
            feedback.value = 'Estado agregado al tablero.';
        },
    });
}

function createInLane(id: number | null) {
    showForm();
    form.status = id;
}

const filtered = computed(() =>
    samples.value.filter(
        (c) =>
            [c.name, c.company, c.email, c.phone, c.city]
                .join(' ')
                .toLocaleLowerCase()
                .includes(search.value.trim().toLocaleLowerCase()) &&
            [tag.value, status.value].every(
                (id) => !id || c.options.some((o) => o.id === +id),
            ),
    ),
);
const total = computed(() => (props.demo ? filtered.value.length : serverTotal.value));
const rows = computed(() =>
    props.demo
        ? filtered.value.slice((page.value - 1) * size, page.value * size)
        : serverRows.value,
);
const hasFilters = computed(() => !!(tag.value || status.value || search.value));
const editing = ref<Client | null>(null);
const open = ref(false);
const feedback = ref('');
const panel = ref<'datos' | 'historial'>('datos');
const histories = ref<ClientHistory[]>([]);
const historiesLoading = ref(false);
const historiesError = ref('');
const note = ref('');
const noteError = ref('');
const noteSaving = ref(false);
const form = useForm({
    name: '',
    company: '',
    email: '',
    phone: '',
    city: '',
    status: null as number | null,
    tags: [] as number[],
});
const optionForm = useForm({ name: '', kind: 'tag' as ClientOption['kind'] });
const clear = () => {
    search.value = '';
    tag.value = '';
    status.value = '';
    page.value = 1;
};

watch([search, tag, status], () => (page.value = 1), { flush: 'sync' });
watch(total, (n) => {
    if (props.demo) page.value = Math.min(page.value, Math.max(1, Math.ceil(n / size)));
});
watch(
    [search, tag, status, page, refresh, () => props.section],
    (_, __, cleanup) => {
        if (props.demo || props.section !== 'Clientes' || !shared.props.currentTeam) return;
        loading.value = true;
        failure.value = '';
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const response = await fetch(
                    index.url(shared.props.currentTeam!.slug, {
                        query: {
                            search: search.value,
                            tag: tag.value,
                            status: status.value,
                            page: page.value,
                        },
                    }),
                    { signal: controller.signal, headers: { Accept: 'application/json' } },
                );
                if (!response.ok) throw new Error();
                const result = await response.json();
                serverRows.value = result.data;
                serverTotal.value = result.total;
                if (page.value > result.last_page) page.value = result.last_page;
            } catch {
                if (!controller.signal.aborted) failure.value = 'No se pudo cargar el directorio.';
            } finally {
                if (!controller.signal.aborted) loading.value = false;
            }
        }, 180);
        cleanup(() => {
            clearTimeout(timer);
            controller.abort();
        });
    },
    { immediate: true },
);

onMounted(() => {
    if (props.demo) {
        samples.value = loadDemo('pulso-clients', demoClients());
        sampleOptions.value = loadDemo('pulso-client-options', demoOptions);
        sampleHistories.value = loadDemo('pulso-client-histories', {});
    }
});
watch(
    () => props.section,
    () => {
        open.value = false;
        feedback.value = '';
    },
);

function csrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function persistDemoHistories() {
    localStorage.setItem('pulso-client-histories', JSON.stringify(sampleHistories.value));
}

function pushDemoHistory(clientId: number, entry: Omit<ClientHistory, 'id' | 'created_at' | 'user'> & { body: string }) {
    const item: ClientHistory = {
        id: Date.now() + Math.floor(Math.random() * 1000),
        type: entry.type,
        body: entry.body,
        meta: entry.meta ?? null,
        created_at: new Date().toISOString(),
        user: {
            id: shared.props.auth.user?.id ?? null,
            name: shared.props.auth.user?.name ?? 'Demo',
        },
    };
    sampleHistories.value[clientId] = [item, ...(sampleHistories.value[clientId] ?? [])];
    persistDemoHistories();
    return item;
}

async function loadHistories(client: Client) {
    historiesError.value = '';
    if (props.demo) {
        histories.value = sampleHistories.value[client.id] ?? [];
        historiesLoading.value = false;
        return;
    }
    if (!shared.props.currentTeam) return;
    historiesLoading.value = true;
    try {
        const response = await fetch(
            historyIndex.url([shared.props.currentTeam.slug, client.id]),
            { headers: { Accept: 'application/json' } },
        );
        if (!response.ok) throw new Error();
        const result = await response.json();
        histories.value = result.data ?? [];
    } catch {
        historiesError.value = 'No se pudo cargar el historial.';
        histories.value = [];
    } finally {
        historiesLoading.value = false;
    }
}

function showForm(client?: Client) {
    editing.value = client ?? null;
    form.reset();
    form.clearErrors();
    feedback.value = '';
    panel.value = 'datos';
    note.value = '';
    noteError.value = '';
    histories.value = [];
    historiesError.value = '';
    if (client) {
        Object.assign(form, {
            name: client.name,
            company: client.company ?? '',
            email: client.email ?? '',
            phone: client.phone ?? '',
            city: client.city ?? '',
            status: client.options.find((o) => o.kind === 'status')?.id ?? null,
            tags: client.options.filter((o) => o.kind === 'tag').map((o) => o.id),
        });
        loadHistories(client);
    }
    open.value = true;
}

function formatHistoryDate(value: string) {
    try {
        return new Date(value).toLocaleString('es', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return value;
    }
}

function historyLabel(type: string) {
    if (type === 'note') return 'Nota';
    if (type === 'status_change') return 'Estado';
    if (type === 'created') return 'Alta';
    return 'Evento';
}

async function saveNote() {
    if (!editing.value) return;
    const body = note.value.trim();
    noteError.value = '';
    if (!body) {
        noteError.value = 'Escribe una nota para guardarla en el historial.';
        return;
    }
    if (props.demo) {
        const item = pushDemoHistory(editing.value.id, { type: 'note', body });
        histories.value = [item, ...histories.value];
        note.value = '';
        feedback.value = 'Nota guardada en el historial.';
        return;
    }
    if (!shared.props.currentTeam || noteSaving.value) return;
    noteSaving.value = true;
    try {
        const response = await fetch(
            historyStore.url([shared.props.currentTeam.slug, editing.value.id]),
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ body }),
            },
        );
        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            throw new Error(payload?.message ?? 'error');
        }
        const result = await response.json();
        histories.value = [result.data, ...histories.value];
        note.value = '';
        feedback.value = 'Nota guardada en el historial.';
    } catch {
        noteError.value = 'No se pudo guardar la nota.';
    } finally {
        noteSaving.value = false;
    }
}

function save() {
    if (!form.name.trim()) {
        form.setError('name', 'Introduce el nombre del cliente.');
        return;
    }
    const previousStatus = editing.value?.options.find((o) => o.kind === 'status')?.id ?? null;
    const done = () => {
        open.value = false;
        feedback.value = editing.value ? 'Cliente actualizado.' : 'Cliente creado.';
        if (!editing.value) clear();
        refresh.value++;
    };
    if (props.demo) {
        const client: Client = {
            id: editing.value?.id ?? Date.now(),
            name: form.name.trim(),
            company: form.company.trim(),
            email: form.email.trim(),
            phone: form.phone.trim(),
            city: form.city.trim(),
            options: options.value.filter(
                (o) => o.id === form.status || form.tags.includes(o.id),
            ),
        };
        if (editing.value) {
            samples.value = samples.value.map((c) => (c.id === client.id ? client : c));
            if (previousStatus !== form.status) {
                const fromLabel =
                    options.value.find((o) => o.id === previousStatus)?.name ?? 'Sin estado';
                const toLabel =
                    options.value.find((o) => o.id === form.status)?.name ?? 'Sin estado';
                pushDemoHistory(client.id, {
                    type: 'status_change',
                    body: `Estado actualizado: ${fromLabel} → ${toLabel}`,
                    meta: { from: fromLabel, to: toLabel },
                });
            }
        } else {
            samples.value.unshift(client);
            pushDemoHistory(client.id, {
                type: 'created',
                body: 'Cliente creado en el directorio.',
            });
            if (form.status) {
                const toLabel = options.value.find((o) => o.id === form.status)?.name ?? 'Estado';
                pushDemoHistory(client.id, {
                    type: 'status_change',
                    body: `Estado actualizado: Sin estado → ${toLabel}`,
                    meta: { from: 'Sin estado', to: toLabel },
                });
            }
        }
        localStorage.setItem('pulso-clients', JSON.stringify(samples.value));
        done();
    } else if (shared.props.currentTeam) {
        const team = shared.props.currentTeam.slug;
        if (editing.value) {
            form.put(update.url([team, editing.value.id]), {
                preserveScroll: true,
                onSuccess: done,
            });
        } else {
            form.post(store.url(team), { preserveScroll: true, onSuccess: done });
        }
    }
}

function addOption() {
    const name = optionForm.name.trim();
    optionForm.clearErrors();
    feedback.value = '';
    if (
        !name ||
        options.value.some(
            (o) =>
                o.kind === optionForm.kind &&
                o.name.toLocaleLowerCase() === name.toLocaleLowerCase(),
        )
    ) {
        optionForm.setError(
            'name',
            name ? 'Ya existe una opción con ese nombre.' : 'Introduce un nombre.',
        );
        return;
    }
    const done = () => {
        feedback.value = optionForm.kind === 'tag' ? 'Etiqueta creada.' : 'Estado creado.';
        optionForm.reset('name');
    };
    if (props.demo) {
        sampleOptions.value.push({ id: Date.now(), name, kind: optionForm.kind });
        localStorage.setItem('pulso-client-options', JSON.stringify(sampleOptions.value));
        done();
    } else if (shared.props.currentTeam) {
        optionForm.post(storeOption.url(shared.props.currentTeam.slug), {
            preserveScroll: true,
            onSuccess: done,
        });
    }
}
</script>

<template>
    <div class="client-space">
        <section class="directory-head">
            <div>
                <div class="eyebrow">
                    <i />{{
                        section === 'Configuración'
                            ? 'PREFERENCIAS PERSONALES'
                            : 'GESTIÓN COMERCIAL'
                    }}
                </div>
                <h1>
                    {{
                        board
                            ? 'Tablero de clientes'
                            : section === 'Clientes'
                              ? 'Directorio de clientes'
                              : 'Configuración'
                    }}
                </h1>
                <p>
                    {{
                        board
                            ? 'Sigue a cada cliente por estado. Arrastra una tarjeta o ábrela para actualizar su seguimiento.'
                            : section === 'Clientes'
                              ? 'Consulta y organiza los contactos de tu equipo.'
                              : 'Personaliza las etiquetas y los estados que utilizas en tu directorio.'
                    }}
                </p>
            </div>
            <button
                v-if="section !== 'Configuración'"
                class="primary"
                @click="showForm()"
            >
                <Plus :size="16" /> Nuevo cliente
            </button>
        </section>

        <p v-if="feedback" class="client-feedback" role="status">
            <Check :size="15" />{{ feedback }}
        </p>

        <section v-if="section === 'Clientes' || board" class="panel table-panel">
            <div class="card-head">
                <div class="view-tabs" aria-label="Vista de clientes">
                    <button
                        :aria-pressed="!board"
                        @click="emit('navigate', 'Clientes')"
                    >
                        <List :size="15" /> Lista
                    </button>
                    <button
                        :aria-pressed="board"
                        @click="emit('navigate', 'Kanban')"
                    >
                        <Columns3 :size="15" /> Kanban
                    </button>
                </div>
                <button class="text-btn" @click="emit('navigate', 'Configuración')">
                    <Settings2 :size="14" /> Personalizar
                </button>
            </div>

            <div class="directory-filters">
                <label class="client-search">
                    <Search :size="15" />
                    <input
                        v-model="search"
                        placeholder="Buscar cliente, teléfono o ciudad"
                        aria-label="Buscar clientes"
                    />
                </label>
                <label class="filter-select">
                    <CircleDot :size="14" />
                    <select v-model="status" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option
                            v-for="o in statuses"
                            :key="o.id"
                            :value="String(o.id)"
                        >
                            {{ o.name }}
                        </option>
                    </select>
                    <ChevronDown :size="13" />
                </label>
                <label class="filter-select">
                    <Tag :size="14" />
                    <select v-model="tag" aria-label="Filtrar por etiqueta">
                        <option value="">Todas las etiquetas</option>
                        <option v-for="o in tags" :key="o.id" :value="String(o.id)">
                            {{ o.name }}
                        </option>
                    </select>
                    <ChevronDown :size="13" />
                </label>
                <button v-if="hasFilters" class="text-btn" @click="clear">Limpiar</button>
            </div>

            <div v-if="board && shared.props.currentTeam" class="board-body">
                <div class="board-heading">
                    <div>
                        <Columns3 :size="16" />
                        <h2>Seguimiento por estado</h2>
                    </div>
                    <button class="text-btn" @click="newColumn = !newColumn">
                        <PlusCircle :size="14" /> Agregar estado
                    </button>
                </div>
                <form
                    v-if="newColumn"
                    class="add-state"
                    @submit.prevent="createColumn"
                >
                    <input
                        v-model="columnForm.name"
                        v-focus
                        required
                        maxlength="60"
                        placeholder="Nombre del estado"
                        aria-label="Nombre del estado"
                    />
                    <button class="primary" :disabled="columnForm.processing">
                        Guardar
                    </button>
                    <button type="button" aria-label="Cancelar" @click="newColumn = false">
                        <X :size="15" />
                    </button>
                    <span>{{ columnForm.errors.name }}</span>
                </form>
                <div class="board-lanes">
                    <ClientKanbanColumn
                        v-for="(lane, i) in lanes"
                        :key="lane.id ?? 'none'"
                        :team="shared.props.currentTeam.slug"
                        :status-id="lane.id"
                        :title="lane.name"
                        :color="palette[i % palette.length]"
                        :search="search"
                        :tag="tag"
                        :revision="refresh"
                        :dragged="dragged"
                        :busy="moveForm.processing"
                        @drag="dragged = $event"
                        @move="move"
                        @edit="showForm"
                        @create="createInLane"
                    />
                    <button
                        v-if="!statuses.length && !newColumn"
                        class="setup-state"
                        @click="newColumn = true"
                    >
                        <PlusCircle :size="23" />
                        <strong>Crea tu primer estado</strong>
                        <span
                            >Agrega una columna, por ejemplo «En seguimiento», para
                            organizar tus clientes.</span
                        >
                    </button>
                </div>
            </div>
            <template v-else>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Ciudad</th>
                                <th>Estado</th>
                                <th>Etiquetas</th>
                                <th><span class="sr-only">Editar</span></th>
                            </tr>
                        </thead>
                        <tbody v-if="!loading && !failure">
                            <tr
                                v-for="(c, i) in rows"
                                :key="c.id"
                                @click="showForm(c)"
                            >
                                <td>
                                    <div class="identity">
                                        <b class="initial-badge" :class="'c' + (i % 4)">{{
                                            c.name
                                                .split(/\s+/)
                                                .slice(0, 2)
                                                .map((v) => v[0])
                                                .join('')
                                                .toUpperCase()
                                        }}</b>
                                        <div>
                                            {{ c.name
                                            }}<small>{{ c.email || 'Sin correo' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ c.phone || '—' }}</td>
                                <td>{{ c.city || '—' }}</td>
                                <td>
                                    <span
                                        v-if="c.options.some((o) => o.kind === 'status')"
                                        class="status-chip"
                                        >● &nbsp;{{
                                            c.options.find((o) => o.kind === 'status')?.name
                                        }}</span
                                    >
                                    <span v-else>—</span>
                                </td>
                                <td>
                                    <span
                                        v-for="o in c.options.filter((o) => o.kind === 'tag')"
                                        :key="o.id"
                                        class="tag-chip"
                                        >{{ o.name }}</span
                                    >
                                    <span v-if="!c.options.some((o) => o.kind === 'tag')"
                                        >—</span
                                    >
                                </td>
                                <td>
                                    <button
                                        class="deal-link"
                                        @click.stop="emit('opportunities', c)"
                                    >
                                        Oportunidades
                                    </button>
                                    <button
                                        :aria-label="'Editar ' + c.name"
                                        @click.stop="showForm(c)"
                                    >
                                        <Pencil :size="14" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="loading || failure || !rows.length" class="directory-empty">
                    <ContactRound :size="24" />
                    <p>
                        {{
                            loading
                                ? 'Cargando clientes…'
                                : failure ||
                                  (hasFilters
                                      ? 'No hay clientes que coincidan con los filtros.'
                                      : 'Tu directorio aún no tiene clientes.')
                        }}
                    </p>
                    <button v-if="failure" class="text-btn" @click="refresh++">
                        Reintentar
                    </button>
                </div>
                <TablePager
                    :page="page"
                    :size="size"
                    :total="total"
                    @change="page = $event"
                />
            </template>
        </section>

        <template v-else>
            <section class="panel option-create">
                <div class="card-head">
                    <div>
                        <h2>Agregar una opción</h2>
                        <p>
                            Estas opciones son personales y estarán disponibles al editar o
                            filtrar clientes.
                        </p>
                    </div>
                </div>
                <form @submit.prevent="addOption">
                    <label
                        >Tipo
                        <select
                            v-model="optionForm.kind"
                            @change="optionForm.clearErrors()"
                        >
                            <option value="tag">Etiqueta</option>
                            <option value="status">Estado</option>
                        </select></label
                    >
                    <label
                        >Nombre
                        <input
                            v-model="optionForm.name"
                            maxlength="60"
                            required
                            :placeholder="
                                optionForm.kind === 'tag'
                                    ? 'Ej. Prioritario'
                                    : 'Ej. En seguimiento'
                            "
                        /></label
                    >
                    <button class="primary" :disabled="optionForm.processing">
                        <Plus :size="15" /> Agregar
                    </button>
                </form>
                <p v-if="optionForm.errors.name" class="error">
                    {{ optionForm.errors.name }}
                </p>
            </section>
            <div class="option-grid">
                <section v-for="kind in ['tag', 'status'] as const" :key="kind" class="panel">
                    <div class="inline-title">
                        <span class="tile" :class="kind === 'tag' ? 'c1' : 'c3'">
                            <component :is="kind === 'tag' ? Tag : CircleDot" :size="16" />
                        </span>
                        <h2>{{ kind === 'tag' ? 'Mis etiquetas' : 'Mis estados' }}</h2>
                        <span class="number">{{
                            options.filter((o) => o.kind === kind).length
                        }}</span>
                    </div>
                    <div class="option-chips">
                        <span
                            v-for="o in options.filter((o) => o.kind === kind)"
                            :key="o.id"
                            :class="kind === 'tag' ? 'tag-chip' : 'status-chip'"
                            >{{ o.name }}</span
                        >
                        <p v-if="!options.some((o) => o.kind === kind)">
                            Aún no has agregado opciones.
                        </p>
                    </div>
                </section>
            </div>
            <button class="text-btn" @click="emit('navigate', 'Clientes')">
                Ir al directorio de clientes <ArrowRight :size="14" />
            </button>
        </template>

        <div
            v-if="open"
            class="overlay"
            @click.self="open = false"
            @keydown.esc="open = false"
        >
            <section
                class="dialog client-dialog"
                :class="{ wide: !!editing }"
                role="dialog"
                aria-modal="true"
                aria-labelledby="client-title"
            >
                <div class="card-head">
                    <h2 id="client-title">
                        {{ editing ? 'Ficha del cliente' : 'Nuevo cliente' }}
                    </h2>
                    <button aria-label="Cerrar" @click="open = false">
                        <X :size="18" />
                    </button>
                </div>

                <div v-if="editing" class="dialog-tabs" aria-label="Secciones de la ficha">
                    <button
                        type="button"
                        :aria-pressed="panel === 'datos'"
                        @click="panel = 'datos'"
                    >
                        Datos
                    </button>
                    <button
                        type="button"
                        :aria-pressed="panel === 'historial'"
                        @click="panel = 'historial'"
                    >
                        <History :size="14" /> Historial
                    </button>
                </div>

                <form v-show="panel === 'datos'" @submit.prevent="save">
                    <label
                        >Nombre del cliente
                        <input
                            v-model="form.name"
                            v-focus
                            required
                            maxlength="255"
                        /></label>
                    <label
                        >Empresa<input v-model="form.company" maxlength="255"
                    /></label>
                    <label
                        >Teléfono
                        <input
                            v-model="form.phone"
                            type="tel"
                            maxlength="40"
                            autocomplete="tel"
                    /></label>
                    <label
                        >Ciudad
                        <input
                            v-model="form.city"
                            maxlength="120"
                            autocomplete="address-level2"
                    /></label>
                    <label
                        >Correo electrónico
                        <input v-model="form.email" type="email" maxlength="255"
                    /></label>
                    <label
                        >Estado
                        <select v-model="form.status">
                            <option :value="null">Sin estado</option>
                            <option
                                v-for="o in statuses"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select></label
                    >
                    <fieldset>
                        <legend>Etiquetas</legend>
                        <div class="tag-choices">
                            <label v-for="o in tags" :key="o.id">
                                <input
                                    v-model="form.tags"
                                    type="checkbox"
                                    :value="o.id"
                                />{{ o.name }}
                            </label>
                            <p v-if="!tags.length">Agrega etiquetas en Configuración.</p>
                        </div>
                    </fieldset>
                    <p v-for="e in form.errors" :key="e" class="error">{{ e }}</p>
                    <button class="primary" :disabled="form.processing">
                        Guardar cliente
                    </button>
                    <button
                        v-if="editing"
                        type="button"
                        class="deal-link"
                        @click="
                            open = false;
                            emit('opportunities', editing);
                        "
                    >
                        Ver oportunidades
                    </button>
                </form>

                <div v-if="editing && panel === 'historial'" class="history-panel">
                    <form class="note-form" @submit.prevent="saveNote">
                        <label
                            >Nueva nota
                            <textarea
                                v-model="note"
                                rows="3"
                                maxlength="2000"
                                placeholder="Registra una llamada, reunión o seguimiento…"
                            />
                        </label>
                        <p v-if="noteError" class="error">{{ noteError }}</p>
                        <button class="primary" :disabled="noteSaving">
                            <MessageSquarePlus :size="15" />
                            {{ noteSaving ? 'Guardando…' : 'Guardar en historial' }}
                        </button>
                    </form>

                    <div v-if="historiesLoading" class="history-empty">
                        Cargando historial…
                    </div>
                    <div v-else-if="historiesError" class="history-empty">
                        {{ historiesError }}
                        <button class="text-btn" @click="editing && loadHistories(editing)">
                            Reintentar
                        </button>
                    </div>
                    <div v-else-if="!histories.length" class="history-empty">
                        Todavía no hay eventos. Guarda una nota para empezar el seguimiento.
                    </div>
                    <ol v-else class="history-list">
                        <li v-for="entry in histories" :key="entry.id">
                            <span class="history-badge" :data-type="entry.type">{{
                                historyLabel(entry.type)
                            }}</span>
                            <div>
                                <p>{{ entry.body }}</p>
                                <small
                                    >{{ entry.user.name }} ·
                                    {{ formatHistoryDate(entry.created_at) }}</small
                                >
                            </div>
                        </li>
                    </ol>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.board-body .board-lanes {
    display: flex;
    gap: 8px;
}
.board-lanes .setup-state {
    flex: 1 0 205px;
    min-height: 110px;
    padding: 12px;
}
@media (max-width: 660px) {
    .board-body .board-lanes {
        flex-direction: column;
    }
    .board-lanes .setup-state {
        width: 100%;
        flex: none;
    }
}
.directory-head,
.inline-title,
.client-feedback,
.client-search,
.filter-select,
.identity,
.tag-choices {
    display: flex;
    align-items: center;
    gap: 9px;
}
.directory-head {
    justify-content: space-between;
    margin: 5px 0 23px;
}
.directory-head h1 {
    font-weight: 650;
    font-size: 32px;
    letter-spacing: -1.3px;
}
.directory-head p,
.option-chips p {
    font-size: 13px;
    color: #737984;
}
.directory-filters {
    display: flex;
    gap: 9px;
    flex-wrap: wrap;
    padding: 0 20px 18px;
}
.client-search,
.filter-select {
    border: 1px solid #dfe1e5;
    border-radius: 7px;
    color: #747a83;
    font-size: 12px;
    padding: 8px 10px;
    background: #fff;
}
.client-search {
    flex: 1;
    min-width: 215px;
}
.client-search input {
    width: 100%;
    outline: none;
}
.client-search:focus-within {
    border-color: #f77c43;
}
.filter-select select {
    appearance: none;
}
.inline-title h2 {
    font-size: 18px;
    letter-spacing: -0.6px;
    font-weight: 650;
}
.inline-title > svg {
    color: #cd673e;
}
.number {
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    padding: 2px 6px;
    background: #f3f3f6;
    color: #69707a;
}
.identity small {
    display: block;
    color: #7c818a;
    font-weight: 400;
    font-size: 11px;
}
.initial-badge {
    width: 31px;
    height: 31px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    font-size: 11px;
}
.tag-chip,
.status-chip {
    display: inline-block;
    border-radius: 6px;
    padding: 3px 7px;
    font-size: 11px;
    font-weight: 550;
    white-space: normal;
    overflow-wrap: anywhere;
}
.tag-chip {
    background: #f2effb;
    color: #75649d;
    margin-right: 4px;
}
.status-chip {
    background: #eaf6ef;
    color: #458365;
}
.directory-empty {
    display: grid;
    justify-items: center;
    gap: 8px;
    color: #838791;
    padding: 32px;
}
.option-create form {
    display: flex;
    gap: 14px;
    align-items: end;
    margin-top: 18px;
}
.option-create label {
    display: grid;
    gap: 5px;
    font-size: 12px;
    font-weight: 550;
}
.option-create label:nth-child(2) {
    flex: 1;
}
.option-create input,
.option-create select {
    border: 1px solid #dfe1e5;
    border-radius: 6px;
    padding: 9px 12px;
    background: white;
}
.option-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 16px 0;
}
.option-chips,
.tag-choices {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 16px;
}
.tag-choices {
    margin: 6px 0 14px;
}
.tag-choices label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    background: #f5f4f7;
    padding: 5px 8px;
    border-radius: 6px;
}
.tag-choices input {
    width: auto;
    accent-color: #e96c37;
}
.dialog legend {
    font-size: 12px;
    font-weight: 600;
}
.client-feedback {
    margin-bottom: 12px;
    color: #37734e;
    font-size: 13px;
}
.error {
    color: #b63e38 !important;
}
.option-chips {
    max-height: 220px;
    overflow: auto;
}
@media (max-width: 660px) {
    .directory-head {
        align-items: start;
        flex-direction: column;
        gap: 12px;
    }
    .directory-head h1 {
        font-size: 26px;
    }
    .directory-filters {
        padding: 0 14px 14px;
    }
    .client-search {
        width: 100%;
    }
    .filter-select {
        padding: 7px;
        gap: 5px;
        font-size: 11px;
    }
    .option-grid {
        grid-template-columns: 1fr;
    }
    .option-create form {
        display: grid;
        gap: 10px;
    }
    .option-create .primary {
        justify-self: start;
    }
    .client-space .card-head .text-btn {
        font-size: 11px;
    }
}
</style>
<style scoped>
.deal-link {
    color: #ae552d;
    font-size: 12px;
    margin-inline: 10px;
}
.view-tabs {
    display: flex;
    padding: 3px;
    border-radius: 8px;
    background: #f3f3f5;
    gap: 2px;
}
.view-tabs button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    color: #707581;
}
.view-tabs button[aria-pressed='true'] {
    background: white;
    color: #bc592e;
    font-weight: 650;
    box-shadow: 0 1px 4px #20232d15;
}
.board-body {
    border-top: 1px solid #ebecef;
    padding: 19px 20px 22px;
}
.board-heading,
.board-heading > div,
.add-state {
    display: flex;
    align-items: center;
    gap: 8px;
}
.board-heading {
    justify-content: space-between;
    margin-bottom: 16px;
}
.board-heading > div {
    color: #85593e;
}
.board-heading h2 {
    font-size: 15px;
    font-weight: 650;
    color: #323640;
}
.board-lanes {
    display: grid;
    grid-auto-columns: minmax(235px, 1fr);
    grid-auto-flow: column;
    overflow-x: auto;
    gap: 12px;
    align-items: start;
    padding: 2px 2px 10px;
}
.setup-state {
    border: 1px dashed #e3b59b;
    border-radius: 12px;
    background: #fffaf6;
    display: grid;
    justify-items: center;
    align-content: center;
    gap: 8px;
    padding: 20px;
    min-height: 265px;
    color: #bc6438;
    text-align: center;
}
.setup-state span {
    font-size: 12px;
    max-width: 260px;
    color: #806f64;
}
.add-state {
    margin: 0 0 15px;
    flex-wrap: wrap;
}
.add-state input {
    padding: 8px 10px;
    border: 1px solid #d9dce1;
    border-radius: 6px;
}
.add-state span {
    font-size: 12px;
    color: #ad4238;
}
.directory-head > .primary {
    flex-shrink: 0;
}
.client-dialog.wide {
    width: min(560px, 100%);
}
.dialog-tabs {
    display: flex;
    gap: 4px;
    padding: 3px;
    margin-bottom: 16px;
    border-radius: 8px;
    background: #f3f3f5;
}
.dialog-tabs button {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 12px;
    color: #707581;
}
.dialog-tabs button[aria-pressed='true'] {
    background: white;
    color: #bc592e;
    font-weight: 650;
    box-shadow: 0 1px 4px #20232d15;
}
.history-panel {
    display: grid;
    gap: 16px;
}
.note-form {
    display: grid;
    gap: 10px;
}
.note-form label {
    display: grid;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
}
.note-form textarea {
    width: 100%;
    resize: vertical;
    min-height: 84px;
    padding: 9px 11px;
    border: 1px solid #d7d9dd;
    border-radius: 6px;
    background: white;
    color: #262a32;
    font: inherit;
}
.note-form .primary {
    justify-self: start;
}
.history-empty {
    display: grid;
    justify-items: center;
    gap: 8px;
    padding: 22px 12px;
    border: 1px dashed #d9dce2;
    border-radius: 10px;
    color: #7a808b;
    font-size: 12px;
    text-align: center;
}
.history-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 12px;
    max-height: 320px;
    overflow: auto;
}
.history-list li {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 10px;
    align-items: start;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}
.history-list p {
    margin: 0;
    font-size: 13px;
    color: #323640;
    line-height: 1.45;
}
.history-list small {
    display: block;
    margin-top: 4px;
    color: #818791;
    font-size: 11px;
}
.history-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 4px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 650;
    background: #f3f3f6;
    color: #6a707a;
}
.history-badge[data-type='note'] {
    background: #fff0e8;
    color: #c25d2d;
}
.history-badge[data-type='status_change'] {
    background: #eaf6ef;
    color: #458365;
}
.history-badge[data-type='created'] {
    background: #e6f0fd;
    color: #4f74b5;
}
@media (max-width: 660px) {
    .board-body {
        padding: 14px;
    }
    .board-heading {
        align-items: start;
        flex-wrap: wrap;
    }
    .board-lanes {
        grid-auto-columns: min(255px, 92%);
    }
    .view-tabs button {
        padding: 6px 9px;
    }
}
</style>
