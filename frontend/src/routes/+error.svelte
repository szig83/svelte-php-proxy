<script lang="ts">
	// Root error page - intelligently chooses layout based on URL
	import { page } from '$app/stores';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { getAuthState } from '$lib/auth/store.svelte';
	import { ProtectedLayout, ErrorPage } from '$lib/components';
	import { AdminLayout } from '$lib/components/admin';

	// Determine which layout to use based on URL
	const isAdminRoute = $derived($page.url.pathname.startsWith('/admin'));

	// Check if user is authenticated
	let isAuthenticated = $state(false);

	onMount(() => {
		const authState = getAuthState();
		isAuthenticated = authState.isAuthenticated;

		// If not authenticated, redirect to login
		if (!isAuthenticated) {
			goto('/login');
		}
	});
</script>

{#if isAdminRoute && isAuthenticated}
	<!-- Admin route with admin layout -->
	<AdminLayout>
		<ErrorPage
			status={$page.status}
			message={$page.error?.message}
			homeUrl="/admin"
			homeLabel="Vissza az admin főoldalra"
		/>
	</AdminLayout>
{:else if isAuthenticated}
	<!-- Protected route with protected layout -->
	<ProtectedLayout>
		<ErrorPage status={$page.status} message={$page.error?.message} />
	</ProtectedLayout>
{:else}
	<!-- Not authenticated - redirecting to login -->
	<div class="flex min-h-screen items-center justify-center bg-gray-100">
		<div class="flex flex-col items-center gap-3">
			<div
				class="h-8 w-8 animate-spin rounded-full border-4 border-blue-200 border-t-blue-600"
			></div>
			<p class="text-sm text-gray-500">Átirányítás a bejelentkezéshez...</p>
		</div>
	</div>
{/if}
