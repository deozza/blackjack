import { purgeCss } from 'vite-plugin-tailwind-purgecss';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vitest/config';

export default defineConfig({
	plugins: [sveltekit(), purgeCss()],
	test: {
		environment: 'jsdom',
		setupFiles: ['./src/lib/test-setup.ts']
	},
	resolve: process.env.VITEST
		? {
				conditions: ['browser']
			}
		: undefined
});