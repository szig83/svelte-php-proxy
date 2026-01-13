<script lang="ts">
	import { page } from '$app/state';
	import Logo from './Logo.svelte';
	import { getIsAdmin } from '$lib/auth/store.svelte';

	interface MenuItem {
		label: string;
		href: string;
		icon: string;
	}

	const menuItems: MenuItem[] = [
		{ label: 'Egyenleglekérdezés', href: '/', icon: 'wallet' },
		{ label: 'SZJA-kalkulátor', href: '/szja-kalkulator', icon: 'calculator' },
		{ label: '24 hónapos lekötés', href: '/lekotes', icon: 'lock' },
		{ label: 'Folyószámla-kimutatás', href: '/folyoszamla', icon: 'document' },
		{ label: 'ÉnPénztáram hűségprogram', href: '/husegprogram', icon: 'gift' },
		{ label: 'Feliratkozom e-ügyintézésre', href: '/e-ugyintezes', icon: 'mail' },
		{ label: 'Kártyáim', href: '/kartyaim', icon: 'card' },
		{ label: 'Kedvezményezettjeim', href: '/kedvezmenyezettek', icon: 'users' },
		{ label: 'Számlafeltöltés', href: '/szamlafeltoltes', icon: 'upload' },
		{ label: 'Dokumentumfeltöltés', href: '/dokumentumfeltoltes', icon: 'file-upload' },
		{ label: 'Állandó bankkártyás megbízás', href: '/bankkartyás-megbizas', icon: 'repeat' },
		{ label: 'Mesterkártya', href: '/mesterkartya', icon: 'star' },
		{ label: 'E-irat-feliratkozás', href: '/e-irat', icon: 'file-text' },
		{ label: 'Patikakártyával fizetett szolgáltatás', href: '/patikakartya', icon: 'heart' },
		{ label: 'Elutasított számla', href: '/elutasitott-szamla', icon: 'x-circle' }
	];

	function isActive(href: string, currentPath: string): boolean {
		if (href === '/') {
			return currentPath === '/';
		}
		return currentPath === href || currentPath.startsWith(href + '/');
	}
</script>

<aside class="flex w-72 shrink-0 flex-col py-6 pr-10">
	<!-- Logo - wider to fit side by side -->
	<div class="mb-8 px-6">
		<Logo variant="sidebar" />
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
							: 'text-gray-400/90 hover:bg-white/5 hover:text-white'}"
					>
						<span
							class="flex h-4 w-4 shrink-0 items-center justify-center {active
								? 'text-blue-400'
								: 'text-slate-500'}"
						>
							{#if item.icon === 'wallet'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
									/>
								</svg>
							{:else if item.icon === 'calculator'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
									/>
								</svg>
							{:else if item.icon === 'lock'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
									/>
								</svg>
							{:else if item.icon === 'document'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									/>
								</svg>
							{:else if item.icon === 'gift'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"
									/>
								</svg>
							{:else if item.icon === 'mail'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
									/>
								</svg>
							{:else if item.icon === 'card'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
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
							{:else if item.icon === 'upload'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
									/>
								</svg>
							{:else if item.icon === 'file-upload'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									/>
								</svg>
							{:else if item.icon === 'repeat'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
									/>
								</svg>
							{:else if item.icon === 'star'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
									/>
								</svg>
							{:else if item.icon === 'file-text'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
									/>
								</svg>
							{:else if item.icon === 'heart'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
									/>
								</svg>
							{:else if item.icon === 'x-circle'}
								<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
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

			<!-- Admin menu item - only visible for admins -->
			{#if getIsAdmin()}
				{@const adminActive = isActive('/admin', page.url.pathname)}
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
			{/if}
		</ul>
	</nav>

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
