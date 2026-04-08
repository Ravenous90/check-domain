import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from './api';
import { sessionUser } from './sessionUser';
import Login from './pages/Login.vue';
import Register from './pages/Register.vue';
import DomainList from './pages/DomainList.vue';
import DomainDetail from './pages/DomainDetail.vue';
import CheckLogs from './pages/CheckLogs.vue';
import AdminUsers from './pages/AdminUsers.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/register', name: 'register', component: Register, meta: { guest: true } },
        { path: '/', name: 'domains', component: DomainList, meta: { auth: true } },
        { path: '/domains/:id', name: 'domain', component: DomainDetail, meta: { auth: true } },
        { path: '/checks/:id/logs', name: 'logs', component: CheckLogs, meta: { auth: true } },
        { path: '/admin/users', name: 'admin-users', component: AdminUsers, meta: { auth: true, superuser: true } },
    ],
});

router.beforeEach((to) => {
    const token = getToken();
    if (to.meta.auth && ! token) {
        if (to.fullPath === '/' || to.fullPath === '') {
            return { name: 'login' };
        }

        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (to.meta.guest && token) {
        return { name: 'domains' };
    }
    if (to.meta.superuser && ! sessionUser.value?.is_superuser) {
        return { name: 'domains' };
    }
    return true;
});

export default router;
