import { ref } from 'vue';

function read() {
    try {
        const raw = sessionStorage.getItem('user');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export const sessionUser = ref(read());

export function setSessionUser(user) {
    sessionUser.value = user;
    if (user) {
        sessionStorage.setItem('user', JSON.stringify(user));
    } else {
        sessionStorage.removeItem('user');
    }
}
