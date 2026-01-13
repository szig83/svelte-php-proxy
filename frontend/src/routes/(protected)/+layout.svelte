<script lang="ts">
	// (protected)/+layout.svelte
	// Protected layout - requires authentication AND "user" permission
	// Követelmények: 5.1, 5.2

	import { onMount } from 'svelte';
	import { guardRoute, createGuardState } from '$lib/auth/guard.svelte';
	import { getAuthState } from '$lib/auth/store.svelte';
	import { checkAuth } from '$lib/auth/operations';
	import ProtectedLayout from '$lib/components/ProtectedLayout.svelte';

	let { children } = $props();

	// Create reactive guard state
	const guard = createGuardState();

	onMount(async () => {
		// Check auth state first
		const authState = getAuthState();
		if (authState.isLoading) {
			await checkAuth();
		}

		// Run the guard with "user" permission requirement
		const allowed = await guardRoute({
			requiredPermissions: ['user'],
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
	<ProtectedLayout>
		{@render children()}
	</ProtectedLayout>
{:else}
	<!-- Redirecting state -->
	<div class="flex min-h-screen items-center justify-center bg-gray-100">
		<p class="text-sm text-gray-500">Átirányítás...</p>
	</div>
{/if}
