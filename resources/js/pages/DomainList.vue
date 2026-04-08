<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { setApiLocale } from '../api';
import { sessionUser } from '../sessionUser';

const { t, locale } = useI18n();
const router = useRouter();

setApiLocale(locale.value);
watch(locale, (l) => setApiLocale(l));

const domains = ref([]);
const meta = ref(null);
const loading = ref(true);
const hostname = ref('');
const ownerId = ref('');
const error = ref('');

async function load(page = 1) {
    loading.value = true;
    error.value = '';
    try {
        const params = { page };
        if (sessionUser.value?.is_superuser && ownerId.value) {
            params.user_id = ownerId.value;
        }
        const { data } = await api.get('/domains', { params });
        domains.value = data.data;
        meta.value = data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || e.message;
    } finally {
        loading.value = false;
    }
}

async function addDomain() {
    error.value = '';
    try {
        const body = { hostname: hostname.value };
        if (sessionUser.value?.is_superuser && ownerId.value) {
            body.user_id = Number(ownerId.value);
        }
        await api.post('/domains', body);
        hostname.value = '';
        await load(meta.value?.current_page || 1);
    } catch (e) {
        const errs = e.response?.data?.errors;
        error.value = errs ? Object.values(errs).flat().join(' ') : e.message;
    }
}

async function remove(id) {
    if (! confirm('OK?')) {
        return;
    }
    await api.delete(`/domains/${id}`);
    await load(meta.value?.current_page || 1);
}

/** Зведений статус по всіх активних перевірках домену */
function domainStatusType(d) {
    const checks = d.checks || [];
    if (checks.length === 0) {
        return 'nochecks';
    }
    const active = checks.filter((c) => c.is_active);
    if (active.length === 0) {
        return 'inactive';
    }
    if (active.some((c) => c.last_ok === false)) {
        return 'fail';
    }
    if (active.some((c) => c.last_ok === null)) {
        return 'unknown';
    }
    return 'ok';
}

function domainStatusRowClass(type) {
    const base = 'border-l-4 ';
    switch (type) {
        case 'fail':
            return base + 'border-l-red-500';
        case 'ok':
            return base + 'border-l-emerald-500';
        case 'unknown':
            return base + 'border-l-amber-500';
        default:
            return base + 'border-l-zinc-600';
    }
}

function domainStatusBadgeClass(type) {
    switch (type) {
        case 'fail':
            return 'bg-red-950/80 text-red-300 ring-1 ring-red-800';
        case 'ok':
            return 'bg-emerald-950/80 text-emerald-300 ring-1 ring-emerald-800';
        case 'unknown':
            return 'bg-amber-950/80 text-amber-200 ring-1 ring-amber-800';
        case 'inactive':
            return 'bg-zinc-800 text-zinc-400 ring-1 ring-zinc-700';
        default:
            return 'bg-zinc-800 text-zinc-500 ring-1 ring-zinc-700';
    }
}

onMounted(() => load());
</script>

<template>
    <div>
        <h1 class="mb-6 text-2xl font-semibold">
            {{ t('domains.title') }}
        </h1>

        <form class="mb-8 flex flex-wrap items-end gap-3" @submit.prevent="addDomain">
            <div class="grow">
                <label class="mb-1 block text-sm text-zinc-400">{{ t('domains.hostname') }}</label>
                <input
                    v-model="hostname"
                    required
                    placeholder="example.com"
                    class="w-full min-w-[200px] rounded border border-zinc-700 bg-zinc-900 px-3 py-2"
                >
            </div>
            <div v-if="sessionUser?.is_superuser" class="w-40">
                <label class="mb-1 block text-sm text-zinc-400">{{ t('domains.owner') }}</label>
                <input
                    v-model="ownerId"
                    type="number"
                    min="1"
                    placeholder="optional"
                    class="w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2"
                >
            </div>
            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-500">
                {{ t('domains.add') }}
            </button>
        </form>

        <p v-if="error" class="mb-4 text-red-400">
            {{ error }}
        </p>
        <p v-if="!loading && !domains.length" class="text-zinc-500">
            {{ t('domains.empty') }}
        </p>
        <ul v-else class="space-y-2">
            <li
                v-for="d in domains"
                :key="d.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded border border-zinc-800 bg-zinc-900/50 py-3 pl-3 pr-4"
                :class="domainStatusRowClass(domainStatusType(d))"
            >
                <div class="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                    <span class="font-mono text-emerald-300">{{ d.hostname }}</span>
                    <span
                        class="inline-flex w-fit shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide"
                        :class="domainStatusBadgeClass(domainStatusType(d))"
                    >
                        {{ t(`domains.status_${domainStatusType(d)}`) }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="text-sm text-zinc-400 hover:text-white"
                        @click="router.push({ name: 'domain', params: { id: d.id } })"
                    >
                        {{ t('domains.open') }}
                    </button>
                    <button type="button" class="text-sm text-red-400 hover:text-red-300" @click="remove(d.id)">
                        {{ t('domains.delete') }}
                    </button>
                </div>
            </li>
        </ul>

        <div v-if="meta && meta.last_page > 1" class="mt-6 flex gap-2">
            <button
                v-for="p in meta.last_page"
                :key="p"
                type="button"
                class="rounded px-3 py-1"
                :class="p === meta.current_page ? 'bg-emerald-700' : 'bg-zinc-800'"
                @click="load(p)"
            >
                {{ p }}
            </button>
        </div>
    </div>
</template>
