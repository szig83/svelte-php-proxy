<script lang="ts">
	import { page } from '$app/state';
	import { getAuthState } from '$lib/auth';
	import DynamicMenu from '../DynamicMenu.svelte';

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

	<!-- Dynamic Navigation -->
	<DynamicMenu type="admin" />

	<!-- Back to main site - csak ha van "user" jog is -->
	{#if hasUserPermission}
		<nav class="px-3">
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
		</nav>
	{/if}
</aside>
