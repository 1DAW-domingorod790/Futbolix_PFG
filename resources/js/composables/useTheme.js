import { ref, onMounted } from 'vue';

const isDark = ref(true);

export function useTheme() {
    const toggle = () => {
        isDark.value = !isDark.value;
        if (isDark.value) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    };

    onMounted(() => {
        const saved = localStorage.getItem('theme');
        isDark.value = saved !== 'light';
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });

    return { isDark, toggle };
}
