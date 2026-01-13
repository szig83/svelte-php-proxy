<script lang="ts">
	import { page } from '$app/state';
	import { getAuthState } from '$lib/auth';

	interface MenuItem {
		label: string;
		href: string;
		icon: string;
	}

	const menuItems: MenuItem[] = [
		{ label: 'Dashboard', href: '/admin', icon: 'dashboard' },
		{ label: 'Felhasználók', href: '/admin/users', icon: 'users' },
		{ label: 'Statisztikák', href: '/admin/stats', icon: 'chart' },
		{ label: 'Hibák', href: '/admin/errors', icon: 'bug' }
	];

	function isActive(href: string, currentPath: string): boolean {
		if (href === '/admin') {
			return currentPath === '/admin';
		}
		return currentPath === href || currentPath.startsWith(href + '/');
	}

	// Ellenőrizzük, hogy a felhasználónak van-e "user" joga is az admin mellett
	const authState = getAuthState();
	const hasUserPermission = $derived(authState.user?.permissions.includes('user') ?? false);
</script>

<aside class="flex w-72 shrink-0 flex-col py-6 pr-10">
	<!-- Logo -->
	<div class="mb-8 px-6">
		<div class="flex items-center gap-3">
			<div
				class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-cyan-400 to-purple-500 shadow-lg"
			>
				<svg viewBox="0 0 24 24" class="h-6 w-6 text-white" fill="none" stroke="currentColor">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="1.5"
						d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
					/>
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="1.5"
						d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
					/>
				</svg>
			</div>
			<div>
				<h1 class="text-lg font-bold text-white">Admin</h1>
				<p class="text-xs text-slate-400">Vezérlőpult</p>
			</div>
		</div>
	</div>

	<!-- Navigation -->
	<nav class="flex-1 overflow-y-auto px-3">
		<ul class="space-y-0.5">
			{#each menuItems as item}
				{@const active = isActive(item.href, page.url.pathname)}
				<li>
					<a
						href={item.href}
						class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] transition-colors
							{active
							? 'bg-white/10 font-medium text-white'
							: 'text-slate-400 hover:bg-white/5 hover:text-white'}"
					>
						<span
							class="flex h-4 w-4 shrink-0 items-center justify-center {active
								? 'text-cyan-400'
								: 'text-slate-500'}"
						>
							{#if item.icon === 'dashboard'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
									/>
								</svg>
							{:else if item.icon === 'users'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
									/>
								</svg>
							{:else if item.icon === 'chart'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
									/>
								</svg>
							{:else if item.icon === 'bug'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
									/>
								</svg>
							{:else}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M13 10V3L4 14h7v7l9-11h-7z"
									/>
								</svg>
							{/if}
						</span>
						<span class="truncate">{item.label}</span>
					</a>
				</li>
			{/each}
		</ul>

		<!-- Back to main site - csak ha van "user" jog is -->
		{#if hasUserPermission}
			<div class="mt-6 border-t border-slate-600 pt-6">
				<a
					href="/"
					class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] text-slate-400 transition-colors hover:bg-white/5 hover:text-white"
				>
					<span class="flex h-4 w-4 shrink-0 items-center justify-center text-slate-500">
						<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="1.5"
								d="M11 17l-5-5m0 0l5-5m-5 5h12"
							/>
						</svg>
					</span>
					<span>Vissza a főoldalra</span>
				</a>
			</div>
		{/if}
	</nav>
</aside>
