import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import laramod from 'laramod-vite-plugin'
import path from 'node:path'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [
    laramod({
      input: ['resources/css/app.css', 'resources/css/site.css'],
      refresh: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, 'resources/js'),
    },
  },
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
})
