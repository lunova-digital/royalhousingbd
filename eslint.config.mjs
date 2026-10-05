import { defineConfig, globalIgnores } from 'eslint/config'
import nextVitals from 'eslint-config-next/core-web-vitals'
import nextTs from 'eslint-config-next/typescript'

export default defineConfig([
  ...nextVitals,
  ...nextTs,
  {
    rules: {
      // Uploaded images are served from /storage at arbitrary sizes; next/image adds nothing there.
      '@next/next/no-img-element': 'off',
      // The admin pages load their data with fetch-in-effect and mirror it into state; that is the pattern, not a bug.
      'react-hooks/set-state-in-effect': 'off',
    },
  },
  globalIgnores(['.next/**', 'node_modules/**', 'db/migrations/**', 'storage/**']),
])
