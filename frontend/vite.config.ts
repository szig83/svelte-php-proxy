import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vitest/config';
import tailwindcss from '@tailwindcss/vite';
import devtoolsJson from 'vite-plugin-devtools-json';

export default defineConfig({
	plugins: [devtoolsJson(),tailwindcss(),sveltekit()],
    server: {
    proxy: {
      // Lokális PHP szerver
      '/api': {
        target: 'https://api:8890',
        changeOrigin: true,
        secure: false, // Self-signed certificate elfogadása
        headers: {
          Host: 'api'
        },
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    }
},
	test: {
		include: ['src/**/*.{test,spec}.{js,ts}'],
		environment: 'jsdom',
		globals: true,
		setupFiles: ['./src/test-setup.ts']
	}
});
