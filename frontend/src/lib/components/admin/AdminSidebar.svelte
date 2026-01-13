<script lang="ts">
	import { page } from '$app/stores';
	import AdminNavItem from './AdminNavItem.svelte';
	import AdminUserInfo from './AdminUserInfo.svelte';

	interface Props {
		isOpen?: boolean;
		onClose?: () => void;
	}

	let { isOpen = true, onClose }: Props = $props();

	const menuItems = [
		{ label: 'Dashboard', href: '/admin', icon: 'dashboard' as const },
		{ label: 'Felhasználók', href: '/admin/users', icon: 'users' as const },
		{ label: 'Statisztikák', href: '/admin/stats', icon: 'chart' as const },
		{ label: 'Hibák', href: '/admin/errors', icon: 'bug' as const }
	];

	function isActive(href: string, currentPath: string): boolean {
		if (href === '/admin') {
			return currentPath === '/admin';
		}
		return currentPath === href || currentPath.startsWith(href + '/');
	}
</script>

<!-- Mobile overlay backdrop -->
{#if isOpen}
	<div
		class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden"
		onclick={onClose}
		onkeydown={(e) => e.key === 'Escape' && onClose?.()}
		role="button"
		tabindex="-1"
		aria-label="Close menu"
	></div>
{/if}

<!-- Sidebar -->
<aside
	class="glass-panel fixed top-0 left-0 z-50 flex h-full w-[280px] flex-col rounded-none transition-transform duration-300 ease-out
		{isOpen ? 'translate-x-0' : '-translate-x-full'}
		md:static md:z-auto md:h-[calc(100vh-3rem)] md:translate-x-0 md:rounded-2xl"
>
	<!-- Logo/Brand section -->
	<div class="flex items-center gap-3 p-6 pb-4">
		<div
			class="from-admin-accent-cyan to-admin-accent-purple flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-br shadow-[0_0_20px_rgba(0,212,255,0.3)]"
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
			<h1 class="text-admin-text-primary text-lg font-bold">Admin</h1>
			<p class="text-admin-text-muted text-xs">Vezérlőpult</p>
		</div>
	</div>

	<!-- Navigation -->
	<nav class="flex-1 overflow-y-auto px-4 py-2">
		<ul class="space-y-1">
			{#each menuItems as item, index}
				{@const active = isActive(item.href, $page.url.pathname)}
				<li class="animate-fade-in-up" style="animation-delay: {index * 50}ms">
					<AdminNavItem
						label={item.label}
						href={item.href}
						icon={item.icon}
						isActive={active}
						animationDelay={index * 50}
					/>
				</li>
			{/each}
		</ul>
	</nav>

	<!-- User info section -->
	<AdminUserInfo />
</aside>
