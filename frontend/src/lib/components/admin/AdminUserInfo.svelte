<script lang="ts">
	import { getAuthState, logout } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	let isLoggingOut = $state(false);

	async function handleLogout() {
		isLoggingOut = true;
		try {
			await logout();
			await goto('/login');
		} catch (error) {
			console.error('Logout failed:', error);
		} finally {
			isLoggingOut = false;
		}
	}

	// Get user initials for avatar
	function getInitials(name: string | undefined): string {
		if (!name) return 'U';
		const parts = name.trim().split(/\s+/);
		if (parts.length === 1) {
			return parts[0].charAt(0).toUpperCase();
		}
		return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
	}
</script>

<div class="border-admin-border border-t p-4">
	<div class="flex items-center gap-3">
		<!-- Avatar -->
		<div
			class="from-admin-accent-cyan to-admin-accent-purple flex h-10 w-10 items-center justify-center rounded-full bg-linear-to-br text-sm font-semibold text-white shadow-[0_0_15px_rgba(0,212,255,0.3)]"
		>
			{getInitials(authState.user?.name)}
		</div>

		<!-- User info -->
		<div class="min-w-0 flex-1">
			<p class="text-admin-text-primary truncate text-sm font-medium">
				{authState.user?.name || 'Felhasználó'}
			</p>
			<p class="text-admin-text-muted truncate text-xs">
				{authState.user?.email || ''}
			</p>
		</div>

		<!-- Logout button -->
		<button
			onclick={handleLogout}
			disabled={isLoggingOut}
			class="text-admin-text-secondary hover:text-admin-accent-cyan flex h-8 w-8 items-center justify-center rounded-lg transition-all duration-200 hover:bg-white/5 hover:shadow-[0_0_10px_rgba(0,212,255,0.2)] disabled:cursor-not-allowed disabled:opacity-50"
			title="Kijelentkezés"
		>
			{#if isLoggingOut}
				<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
					></circle>
					<path
						class="opacity-75"
						fill="currentColor"
						d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
					></path>
				</svg>
			{:else}
				<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="1.5"
						d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
					/>
				</svg>
			{/if}
		</button>
	</div>
</div>
