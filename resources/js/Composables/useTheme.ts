import { onMounted, ref } from 'vue';

type Theme = 'light' | 'dark';

const storageKey = 'voz-theme';
const theme = ref<Theme>('light');

const applyTheme = (value: Theme) => {
    theme.value = value;
    document.documentElement.dataset.theme = value;
    localStorage.setItem(storageKey, value);
};

export function useTheme() {
    onMounted(() => {
        const storedTheme = localStorage.getItem(storageKey) as Theme | null;
        const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)')
            .matches
            ? 'dark'
            : 'light';

        applyTheme(storedTheme ?? preferredTheme);
    });

    const toggleTheme = () => {
        applyTheme(theme.value === 'dark' ? 'light' : 'dark');
    };

    return {
        theme,
        toggleTheme,
    };
}
