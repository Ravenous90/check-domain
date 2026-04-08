<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { setToken, setApiLocale } from '../api';
import { setSessionUser } from '../sessionUser';

const { t, locale } = useI18n();
const router = useRouter();

setApiLocale(locale.value);

const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        const { data } = await api.post('/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: password_confirmation.value,
        });
        setToken(data.token);
        setSessionUser(data.user);
        router.push({ name: 'domains' });
    } catch (e) {
        const errs = e.response?.data?.errors;
        error.value = errs
            ? Object.values(errs).flat().join(' ')
            : e.response?.data?.message || e.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-semibold text-white">
            {{ t('auth.register_title') }}
        </h1>
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm text-zinc-400">{{ t('auth.name') }}</label>
                <input
                    v-model="name"
                    required
                    class="w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">{{ t('auth.email') }}</label>
                <input
                    v-model="email"
                    type="email"
                    required
                    class="w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">{{ t('auth.password') }}</label>
                <input
                    v-model="password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">{{ t('auth.password_confirm') }}</label>
                <input
                    v-model="password_confirmation"
                    type="password"
                    required
                    class="w-full rounded border border-zinc-700 bg-zinc-900 px-3 py-2 text-white"
                >
            </div>
            <p v-if="error" class="text-sm text-red-400">
                {{ error }}
            </p>
            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded bg-emerald-600 py-2 font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
            >
                {{ t('auth.submit_register') }}
            </button>
        </form>
    </div>
</template>
