<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { setApiLocale } from '../api';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

setApiLocale(locale.value);
watch(locale, (l) => setApiLocale(l));

const domain = ref(null);
const loading = ref(true);
const form = ref({
    path: '/',
    method: 'GET',
    interval_seconds: 300,
    timeout_seconds: 30,
    is_active: true,
});
const editId = ref(null);
const editForm = ref({});

async function load() {
    loading.value = true;
    const { data } = await api.get(`/domains/${route.params.id}`);
    domain.value = data.data;
    loading.value = false;
}

async function addCheck() {
    await api.post(`/domains/${route.params.id}/checks`, form.value);
    form.value = {
        path: '/',
        method: 'GET',
        interval_seconds: 300,
        timeout_seconds: 30,
        is_active: true,
    };
    await load();
}

function startEdit(c) {
    editId.value = c.id;
    editForm.value = {
        path: c.path,
        method: c.method,
        interval_seconds: c.interval_seconds,
        timeout_seconds: c.timeout_seconds,
        is_active: c.is_active,
    };
}

async function saveEdit() {
    await api.patch(`/domain-checks/${editId.value}`, editForm.value);
    editId.value = null;
    await load();
}

async function removeCheck(id) {
    if (! confirm('OK?')) {
        return;
    }
    await api.delete(`/domain-checks/${id}`);
    await load();
}

function checkBadgeType(c) {
    if (! c.is_active) {
        return 'inactive';
    }
    if (c.last_ok === null) {
        return 'unknown';
    }
    return c.last_ok ? 'ok' : 'fail';
}

function badgeClass(type) {
    switch (type) {
        case 'fail':
            return 'bg-red-950/90 text-red-200 ring-2 ring-red-700/80';
        case 'ok':
            return 'bg-emerald-950/90 text-emerald-200 ring-2 ring-emerald-600/80';
        case 'unknown':
            return 'bg-amber-950/90 text-amber-100 ring-2 ring-amber-700/80';
        default:
            return 'bg-zinc-800 text-zinc-400 ring-2 ring-zinc-600';
    }
}

function cardBorderClass(type) {
    switch (type) {
        case 'fail':
            return 'border-red-900/60';
        case 'ok':
            return 'border-emerald-900/50';
        case 'unknown':
            return 'border-amber-900/50';
        default:
            return 'border-zinc-800';
    }
}

function formatDt(iso) {
    if (! iso) {
        return '—';
    }
    try {
        const loc = locale.value === 'uk' ? 'uk-UA' : 'en-GB';
        return new Date(iso).toLocaleString(loc, { dateStyle: 'short', timeStyle: 'short' });
    } catch {
        return iso;
    }
}

onMounted(load);
</script>

<template>
    <div v-if="loading" class="text-zinc-500">
        …
    </div>
    <div v-else-if="domain">
        <button
            type="button"
            class="mb-4 text-sm text-zinc-400 hover:text-white"
            @click="router.push({ name: 'domains' })"
        >
            ← {{ t('common.back') }}
        </button>
        <h1 class="mb-2 font-mono text-2xl text-emerald-300">
            {{ domain.hostname }}
        </h1>
        <p class="mb-8 text-sm text-zinc-500">
            user #{{ domain.user_id }}
        </p>

        <h2 class="mb-4 text-lg font-medium">
            {{ t('checks.title') }}
        </h2>

        <form class="mb-8 grid gap-3 rounded border border-zinc-800 bg-zinc-900/40 p-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="addCheck">
            <div>
                <label class="mb-1 block text-xs text-zinc-500">{{ t('checks.path') }}</label>
                <input v-model="form.path" required class="w-full rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">{{ t('checks.method') }}</label>
                <select v-model="form.method" class="w-full rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
                    <option value="GET">
                        GET
                    </option>
                    <option value="HEAD">
                        HEAD
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">{{ t('checks.interval') }}</label>
                <input v-model.number="form.interval_seconds" type="number" min="60" max="86400" required class="w-full rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">{{ t('checks.timeout') }}</label>
                <input v-model.number="form.timeout_seconds" type="number" min="1" max="120" required class="w-full rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox">
                    {{ t('checks.active') }}
                </label>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-sm text-white">
                    {{ t('checks.add') }}
                </button>
            </div>
        </form>

        <ul class="space-y-3">
            <li
                v-for="c in domain.checks"
                :key="c.id"
                class="rounded border-2 bg-zinc-900/50 p-4"
                :class="cardBorderClass(checkBadgeType(c))"
            >
                <template v-if="editId === c.id">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input v-model="editForm.path" class="rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
                        <select v-model="editForm.method" class="rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
                            <option value="GET">
                                GET
                            </option>
                            <option value="HEAD">
                                HEAD
                            </option>
                        </select>
                        <input v-model.number="editForm.interval_seconds" type="number" min="60" max="86400" class="rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
                        <input v-model.number="editForm.timeout_seconds" type="number" min="1" max="120" class="rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-sm">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="editForm.is_active" type="checkbox">
                            {{ t('checks.active') }}
                        </label>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="text-sm text-emerald-400" @click="saveEdit">
                            {{ t('checks.save') }}
                        </button>
                        <button type="button" class="text-sm text-zinc-500" @click="editId = null">
                            {{ t('common.cancel') }}
                        </button>
                    </div>
                </template>
                <template v-else>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide"
                            :class="badgeClass(checkBadgeType(c))"
                        >
                            {{ t(`checks.badge_${checkBadgeType(c)}`) }}
                        </span>
                    </div>
                    <div class="font-mono text-sm text-zinc-200">
                        {{ c.method }} {{ c.url || `https://${domain.hostname}${c.path}` }}
                    </div>
                    <div class="mt-2 grid gap-1 text-xs text-zinc-500 sm:grid-cols-2">
                        <div>
                            {{ t('checks.interval') }}: <span class="text-zinc-300">{{ c.interval_seconds }}</span>
                            · {{ t('checks.timeout') }}: <span class="text-zinc-300">{{ c.timeout_seconds }}</span>
                        </div>
                        <div>
                            {{ t('checks.last_check_at') }}:
                            <span class="text-zinc-300">{{ formatDt(c.last_checked_at) }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            {{ t('checks.next_run') }}:
                            <span class="text-zinc-300">{{ formatDt(c.next_run_at) }}</span>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2 text-sm">
                        <button type="button" class="text-emerald-400 hover:underline" @click="router.push({ name: 'logs', params: { id: c.id } })">
                            {{ t('checks.logs') }}
                        </button>
                        <button type="button" class="text-zinc-400 hover:underline" @click="startEdit(c)">
                            {{ t('checks.edit') }}
                        </button>
                        <button type="button" class="text-red-400 hover:underline" @click="removeCheck(c.id)">
                            {{ t('domains.delete') }}
                        </button>
                    </div>
                </template>
            </li>
        </ul>
    </div>
</template>
