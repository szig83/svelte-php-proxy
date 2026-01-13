<script lang="ts">
	// (admin)/+layout.svelte
	// Admin layout - requires authentication AND admin permission
	// Követelmények: 1.1, 5.1, 5.4, 6.4

	import { onMount } from 'svelte';
	// Direct imports to avoid circular dependency
	import { guardRoute, createGuardState } from '$lib/auth/guard.svelte';
	import { getAuthState } from '$lib/auth/store.svelte';
	import { checkAuth } from '$lib/auth/operations';
	import { AdminLayout } from '$lib/components/admin';

	let { children } = $props();

	// Create reactive guard state
	const guard = createGuardState();

	onMount(async () => {
		// Check auth state first
		const authState = getAuthState();
		if (authState.isLoading) {
			await checkAuth();
		}

		// Run the guard with admin requirement
		const allowed = await guardRoute({
			requireAdmin: true,
			redirectTo: '/login',
			preserveRedirect: true
		});

		guard.setAllowed(allowed);
		guard.setChecking(false);
	});
</script>

{#if guard.isChecking}
	<!-- Loading state while checking authentication -->
	<div class="auth-loading">
		<p>Betöltés...</p>
	</div>
{:else if guard.isAllowed}
	<!-- Render admin content with futuristic layout -->
	<AdminLayout>
		{@render children()}
	</AdminLayout>
{:else}
	<!-- Redirecting state (should not be visible long) -->
	<div class="auth-redirecting">
		<p>Átirányítás...</p>
	</div>
{/if}

<style>
	.auth-loading,
	.auth-redirecting {
		display: flex;
		justify-content: center;
		align-items: center;
		background-color: var(--admin-bg-primary, #0a0a0f);
		min-height: 200px;
		color: var(--admin-text-secondary, #8888a0);
	}
</style>
