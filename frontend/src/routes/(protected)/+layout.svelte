<script lang="ts">
	// (protected)/+layout.svelte
	// Protected layout - requires authentication
	// Követelmények: 5.1, 5.2

	import { onMount } from 'svelte';
	import { guardRoute, createGuardState } from '$lib/auth/guard.svelte';
	import { getAuthState } from '$lib/auth/store.svelte';
	import { checkAuth } from '$lib/auth/operations';
	import Sidebar from '$lib/components/Sidebar.svelte';
	import Header from '$lib/components/Header.svelte';

	let { children } = $props();

	// Create reactive guard state
	const guard = createGuardState();

	onMount(async () => {
		// Check auth state first
		const authState = getAuthState();
		if (authState.isLoading) {
			await checkAuth();
		}

		// Run the guard
		const allowed = await guardRoute({
			redirectTo: '/login',
			preserveRedirect: true
		});

		guard.setAllowed(allowed);
		guard.setChecking(false);
	});
</script>

{#if guard.isChecking}
	<!-- Loading state while checking authentication -->
	<div class="flex min-h-screen items-center justify-center bg-gray-100">
		<div class="flex flex-col items-center gap-3">
			<div
				class="h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"
			></div>
			<p class="text-sm text-gray-500">Betöltés...</p>
		</div>
	</div>
{:else if guard.isAllowed}
	<!-- Protected layout with sidebar - layered design -->
	<div class="flex h-screen bg-slate-700">
		<!-- Sidebar - appears as background layer -->
		<Sidebar />

		<!-- Main content area - appears as foreground layer, edge to edge -->
		<div class="-ml-6 flex flex-1 flex-col overflow-hidden rounded-l-3xl bg-slate-100 shadow-xl">
			<!-- Header -->
			<div class="shrink-0 px-6 pt-4">
				<Header />
			</div>

			<!-- Page content - scrollable -->
			<main class="flex-1 overflow-y-auto p-6">
				<div class="mx-auto max-w-7xl">
					{@render children()}
				</div>
			</main>
		</div>
	</div>
{:else}
	<!-- Redirecting state -->
	<div class="flex min-h-screen items-center justify-center bg-gray-100">
		<p class="text-sm text-gray-500">Átirányítás...</p>
	</div>
{/if}
