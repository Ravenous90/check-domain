<script setup>
import { ref, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api, { setApiLocale } from '../api';

const { t, locale } = useI18n();

setApiLocale(locale.value);
watch(locale, (l) => setApiLocale(l));

const users = ref([]);
const meta = ref(null);
const loading = ref(true);
const error = ref('');

async function load(page = 1) {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await api.get('/admin/users', { params: { page } });
        users.value = data.data;
        meta.value = data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || e.message;
    } finally {
        loading.value = false;
    }
}

async function toggleSuper(u) {
    try {
        await api.patch(`/admin/users/${u.id}`, { is_superuser: ! u.is_superuser });
        await load(meta.value?.current_page || 1);
    } catch (e) {
        error.value = e.response?.data?.message || e.message;
    }
}

onMounted(() => load());
</script>

<template>
    <div>
        <h1 class="mb-6 text-2xl font-semibold">
            {{ t('admin.title') }}
        </h1>
        <p v-if="error" class="mb-4 text-red-400">
            {{ error }}
        </p>
        <div v-if="loading" class="text-zinc-500">
            …
        </div>
        <ul v-else class="space-y-2">
            <li
                v-for="u in users"
                :key="u.id"
                class="flex flex-wrap items-center justify-between gap-2 rounded border border-zinc-800 bg-zinc-900/50 px-4 py-3"
            >
                <div>
                    <div class="font-medium">
                        {{ u.name }}
                    </div>
                    <div class="text-sm text-zinc-500">
                        {{ u.email }} · #{{ u.id }}
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span v-if="u.is_superuser" class="text-amber-400">{{ t('admin.superuser') }}</span>
                    <button
                        type="button"
                        class="rounded border border-zinc-600 px-3 py-1 hover:bg-zinc-800"
                        @click="toggleSuper(u)"
                    >
                        {{ t('admin.save') }} ({{ u.is_superuser ? '−' : '+' }})
                    </button>
                </div>
            </li>
        </ul>
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
