import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';

export default [
    {
        linterOptions: {
            reportUnusedDisableDirectives: 'off',
        },
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-wrapper-object-types': 'off',
            'no-undef': 'off',
            'vue/attributes-order': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/html-indent': 'off',
            'vue/html-self-closing': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/singleline-html-element-content-newline': 'off',
        },
    },
];
