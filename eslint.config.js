import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        // Globally ignore build output and vendored code.
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/build/**',
            'public/vendor/**',
            'bootstrap/ssr/**',
            'storage/**',
            'resources/js/ziggy.js',
        ],
    },

    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],

    {
        files: ['**/*.{js,mjs,cjs,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                // Ziggy global helper injected via Blade.
                route: 'readonly',
                Ziggy: 'readonly',
            },
        },
        rules: {
            // The codebase predates strict component naming and uses many
            // single-word page components (Inertia pages). Relax the rules
            // that would otherwise flood the report without improving quality.
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
            'vue/require-default-prop': 'off',
            'vue/attributes-order': 'warn',
            'vue/order-in-components': 'warn',

            // Allow intentional empty catch blocks used for defensive guards.
            'no-empty': ['error', { allowEmptyCatch: true }],
            // Unused vars are warnings; allow underscore-prefixed args to be ignored.
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
        },
    },
];
