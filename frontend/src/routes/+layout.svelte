<script lang="ts">
	// +layout.svelte
	// Gyökér layout - Auth állapot és Error Logger inicializálás
	// Követelmények: 1.1, 1.2, 1.4, 8.1, 8.2, 8.4

	import '../app.css';
	import { onMount } from 'svelte';
	import favicon from '$lib/assets/favicon.svg';
	import { checkAuth, getAuthState } from '$lib/auth';
	import { initErrorLogger, getErrorLogger } from '$lib/errors/logger';
	import { setupGlobalErrorHandlers } from '$lib/errors/handlers';
	import { dev } from '$app/environment';

	let { children } = $props();

	// Track if initial auth check is complete
	let authInitialized = $state(false);

	onMount(async () => {
		// Initialize Error Logger on app load
		// Követelmények: 8.1, 8.2, 8.4
		initErrorLogger({
			enabled: true,
			endpoint: '/api/errors',
			isDevelopment: dev,
			rateLimit: {
				maxErrors: 10,
				windowMs: 60000
			}
		});

		// Register global error handlers
		// Követelmények: 1.1, 1.2, 1.4
		setupGlobalErrorHandlers(getErrorLogger());

		// Initialize auth state on app load
		// Követelmények: 8.4 - AMIKOR a Frontend betöltődik, ellenőrizni kell az auth állapotot
		const authState = getAuthState();
		if (authState.isLoading) {
			await checkAuth();
		}
		authInitialized = true;
	});
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
</svelte:head>

{#if authInitialized}
	{@render children()}
{:else}
	<!-- Initial loading state while checking auth -->
	<div class="app-loading">
		<p>Betöltés...</p>
	</div>
{/if}

<style>
	.app-loading {
		display: flex;
		justify-content: center;
		align-items: center;
		min-height: 100vh;
		color: #666;
		font-family:
			system-ui,
			-apple-system,
			sans-serif;
	}
</style>
