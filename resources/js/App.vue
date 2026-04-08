<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api, { getToken, setToken, initApiAuth, setApiLocale } from './api';
import { sessionUser, setSessionUser } from './sessionUser';

const router = useRouter();
const { locale, t } = useI18n();

initApiAuth();
setApiLocale(locale.value);

onMounted(async () => {
    if (getToken() && ! sessionUser.value) {
        try {
            const { data } = await api.get('/user');
            setSessionUser(data.data);
        } catch {
            setToken(null);
        }
    }
});

function switchLocale(lang) {
    locale.value = lang;
    setApiLocale(lang);
    localStorage.setItem('locale', lang);
}

function logout() {
    setToken(null);
    setSessionUser(null);
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <nav class="flex flex-wrap items-center gap-4 text-sm">
                    <router-link
                        v-if="sessionUser"
                        class="text-zinc-300 hover:text-white"
                        :to="{ name: 'domains' }"
                    >
                        {{ t('nav.domains') }}
                    </router-link>
                    <router-link
                        v-if="sessionUser?.is_superuser"
                        class="text-zinc-300 hover:text-white"
                        :to="{ name: 'admin-users' }"
                    >
                        {{ t('nav.admin') }}
                    </router-link>
                </nav>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <label class="flex items-center gap-2 text-zinc-400">
                        <span>{{ t('nav.locale') }}</span>
                        <select
                            class="rounded border border-zinc-700 bg-zinc-900 px-2 py-1 text-zinc-100"
                            :value="locale"
                            @change="switchLocale($event.target.value)"
                        >
                            <option value="uk">УК</option>
                            <option value="en">EN</option>
                        </select>
                    </label>
                    <template v-if="!sessionUser">
                        <router-link class="text-emerald-400 hover:text-emerald-300" :to="{ name: 'login' }">
                            {{ t('nav.login') }}
                        </router-link>
                        <router-link class="text-zinc-300 hover:text-white" :to="{ name: 'register' }">
                            {{ t('nav.register') }}
                        </router-link>
                    </template>
                    <button
                        v-else
                        type="button"
                        class="text-zinc-400 hover:text-white"
                        @click="logout"
                    >
                        {{ t('nav.logout') }}
                    </button>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-8">
            <router-view />
        </main>
    </div>
</template>
