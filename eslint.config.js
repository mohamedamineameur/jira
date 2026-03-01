import js from '@eslint/js';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**', 'public/**', 'vendor/**', '.npm-cache/**'],
    },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js', 'tests/frontend/**/*.js'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        rules: {
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        },
    },
];
