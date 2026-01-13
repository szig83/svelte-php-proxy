<script lang="ts">
	import { page } from '$app/state';
	import Logo from './Logo.svelte';
	import { getIsAdmin } from '$lib/auth/store.svelte';
	import DynamicMenu from './DynamicMenu.svelte';

	function isActive(href: string, currentPath: string): boolean {
		if (href === '/admin') {
			return currentPath === '/admin' || currentPath.startsWith('/admin/');
		}
		return false;
	}
</script>

<aside class="flex w-72 shrink-0 flex-col py-6 pr-10">
	<!-- Logo - wider to fit side by side -->
	<div class="mb-8 px-6">
		<Logo variant="sidebar" />
	</div>

	<!-- Dynamic Navigation -->
	<DynamicMenu type="protected" />

	<!-- Admin menu item - only visible for admins -->
	{#if getIsAdmin()}
		{@const adminActive = isActive('/admin', page.url.pathname)}
		<nav class="px-3">
			<ul class="space-y-0.5">
				<li class="mt-3 border-t border-slate-600 pt-3">
					<a
						href="/admin"
						class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] transition-colors
							{adminActive
							? 'bg-white/10 font-medium text-white'
							: 'text-slate-400 hover:bg-white/5 hover:text-white'}"
					>
						<span
							class="flex h-4 w-4 shrink-0 items-center justify-center {adminActive
								? 'text-blue-400'
								: 'text-slate-500'}"
						>
							<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
						</span>
						<span>Admin</span>
					</a>
				</li>
			</ul>
		</nav>
	{/if}

	<!-- Bottom card -->
	<div class="mx-4 mt-4 rounded-2xl bg-slate-600/50 p-4">
		<p class="mb-1 text-sm font-medium text-white">Kapcsolat</p>
		<p class="mb-3 text-xs text-slate-400">Segítségre van szüksége?</p>
		<button
			class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-500/80 text-white transition-colors hover:bg-slate-400/80"
			aria-label="Kapcsolat"
		>
			<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="2"
					d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
				/>
			</svg>
		</button>
	</div>
</aside>
