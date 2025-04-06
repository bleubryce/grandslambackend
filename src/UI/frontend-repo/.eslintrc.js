module.exports = {
  root: true,
  parser: '@typescript-eslint/parser',
  plugins: [
    '@typescript-eslint',
    'react',
    'react-hooks',
    'jest',
    'testing-library',
  ],
  extends: [
    'eslint:recommended',
    'plugin:@typescript-eslint/recommended',
    'plugin:react/recommended',
    'plugin:react-hooks/recommended',
    'plugin:jest/recommended',
    'plugin:testing-library/react',
  ],
  env: {
    browser: true,
    es2021: true,
    node: true,
    'jest/globals': true,
  },
  settings: {
    react: {
      version: 'detect',
    },
  },
  rules: {
    // General rules
    'no-console': ['warn', { allow: ['warn', 'error'] }],
    'no-unused-vars': 'off',
    '@typescript-eslint/no-unused-vars': ['error'],
    
    // React rules
    'react/react-in-jsx-scope': 'off',
    'react/prop-types': 'off',
    
    // Testing rules
    'jest/consistent-test-it': ['error', { fn: 'it' }],
    'jest/require-top-level-describe': 'error',
    'jest/valid-expect': 'error',
    'jest/prefer-expect-assertions': [
      'error',
      { onlyFunctionsWithAsyncKeyword: true }
    ],
    'jest/no-disabled-tests': 'warn',
    'jest/no-focused-tests': 'error',
    'jest/no-identical-title': 'error',
    'jest/valid-title': [
      'error',
      {
        mustMatch: {
          it: '^should\\s',
          test: '^should\\s',
          describe: '^[A-Z]',
        },
      },
    ],
    'testing-library/await-async-queries': 'error',
    'testing-library/no-await-sync-queries': 'error',
    'testing-library/no-container': 'error',
    'testing-library/no-node-access': 'error',
    'testing-library/prefer-screen-queries': 'error',
    'testing-library/prefer-presence-queries': 'error',
    'testing-library/prefer-find-by': 'error',
    'testing-library/render-result-naming-convention': 'error',
  },
  overrides: [
    {
      files: ['**/__tests__/**/*.[jt]s?(x)', '**/?(*.)+(spec|test).[jt]s?(x)'],
      rules: {
        'testing-library/prefer-explicit-assert': 'error',
        'testing-library/no-wait-for-multiple-assertions': 'error',
        'testing-library/prefer-user-event': 'error',
        '@typescript-eslint/no-explicit-any': 'off',
      },
    },
  ],
}; 