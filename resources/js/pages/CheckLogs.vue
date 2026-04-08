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

const logs = ref([]);
const meta = ref(null);
const loading = ref(true);

async function load(page = 1) {
    loading.value = true;
    const { data } = await api.get(`/domain-checks/${route.params.id}/logs`, {
        params: { page, per_page: 40 },
    });
    logs.value = data.data;
    meta.value = data.meta;
    loading.value = false;
}

onMounted(() => load());
</script>

<template>
    <div>
        <button
            type="button"
            class="mb-4 text-sm text-zinc-400 hover:text-white"
            @click="router.back()"
        >
            ← {{ t('common.back') }}
        </button>
        <h1 class="mb-6 text-2xl font-semibold">
            {{ t('logs.title') }}
        </h1>
        <div v-if="loading" class="text-zinc-500">
            …
        </div>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-800 text-zinc-500">
                        <th class="py-2 pr-4">
                            {{ t('logs.ok') }}
                        </th>
                        <th class="py-2 pr-4">
                            {{ t('logs.http') }}
                        </th>
                        <th class="py-2 pr-4">
                            {{ t('logs.ms') }}
                        </th>
                        <th class="py-2 pr-4">
                            {{ t('logs.error') }}
                        </th>
                        <th class="py-2">
                            {{ t('logs.meta') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in logs" :key="row.id" class="border-b border-zinc-900">
                        <td class="py-2 pr-4" :class="row.ok ? 'text-emerald-400' : 'text-red-400'">
                            {{ row.ok ? '✓' : '✗' }}
                        </td>
                        <td class="py-2 pr-4 font-mono">
                            {{ row.http_status ?? '—' }}
                        </td>
                        <td class="py-2 pr-4">
                            {{ row.response_time_ms ?? '—' }}
                        </td>
                        <td class="max-w-xs truncate py-2 pr-4 text-zinc-400" :title="row.error_message">
                            {{ row.error_message ?? '—' }}
                        </td>
                        <td class="max-w-xs truncate py-2 font-mono text-xs text-zinc-500" :title="JSON.stringify(row.meta)">
                            {{ row.meta ? JSON.stringify(row.meta) : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="meta && meta.last_page > 1" class="mt-4 flex flex-wrap gap-2">
            <button
                v-for="p in meta.last_page"
                :key="p"
                type="button"
                class="rounded px-2 py-1 text-sm"
                :class="p === meta.current_page ? 'bg-emerald-700' : 'bg-zinc-800'"
                @click="load(p)"
            >
                {{ p }}
            </button>
        </div>
    </div>
</template>
