<script lang="ts">
	// +layout.svelte
	// Gyökér layout - Auth állapot inicializálás
	// Követelmények: 1.1, 8.4

	import '../app.css';
	import { onMount } from 'svelte';
	import favicon from '$lib/assets/favicon.svg';
	import { checkAuth, getAuthState } from '$lib/auth';

	let { children } = $props();

	// Track if initial auth check is complete
	let authInitialized = $state(false);

	onMount(async () => {
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
