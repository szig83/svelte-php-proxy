<script lang="ts">
	import { logout, getAuthState } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}
</script>

<header class="flex h-16 items-center justify-between rounded-2xl bg-white px-6 shadow-sm">
	<!-- Left side - Title -->
	<div class="flex items-center gap-3">
		<h1 class="text-xl font-semibold text-slate-800">Admin vezérlőpult</h1>
		<span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700"
			>Admin</span
		>
	</div>

	<!-- Center - Search -->
	<div class="flex flex-1 justify-center px-8">
		<div class="relative w-full max-w-md">
			<span class="absolute inset-y-0 left-0 flex items-center pl-3">
				<svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
					/>
				</svg>
			</span>
			<input
				type="search"
				placeholder="Keresés..."
				class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-sm text-slate-700 placeholder-slate-400 focus:border-purple-500 focus:bg-white focus:ring-1 focus:ring-purple-500 focus:outline-none"
			/>
		</div>
	</div>

	<!-- Right side -->
	<div class="flex items-center gap-3">
		<!-- Notifications -->
		<button
			class="relative flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
			aria-label="Értesítések"
		>
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
				/>
			</svg>
			<span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-purple-500"></span>
		</button>

		<!-- User info -->
		<div class="flex items-center gap-3">
			<div class="text-right">
				<p class="text-sm font-medium text-slate-700">{authState.user?.name || 'Admin'}</p>
				<p class="text-xs text-slate-500">{authState.user?.email || ''}</p>
			</div>
			<div
				class="flex h-9 w-9 items-center justify-center rounded-full bg-purple-100 text-sm font-medium text-purple-700"
			>
				{authState.user?.name?.charAt(0).toUpperCase() || 'A'}
			</div>
		</div>

		<!-- Logout -->
		<button
			onclick={handleLogout}
			class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700"
			title="Kijelentkezés"
		>
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
				/>
			</svg>
			<span class="hidden sm:inline">Kijelentkezés</span>
		</button>
	</div>
</header>
