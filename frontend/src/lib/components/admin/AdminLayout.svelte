<script lang="ts">
	import type { Snippet } from 'svelte';
	import { page } from '$app/stores';
	import AdminSidebar from './AdminSidebar.svelte';

	interface Props {
		children: Snippet;
	}

	let { children }: Props = $props();
	let isMobileMenuOpen = $state(false);
	let sidebarElement: HTMLElement | null = $state(null);
	let contentKey = $derived($page.url.pathname);

	function openMobileMenu() {
		isMobileMenuOpen = true;
	}

	function closeMobileMenu() {
		isMobileMenuOpen = false;
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape' && isMobileMenuOpen) {
			closeMobileMenu();
		}
	}

	function handleClickOutside(event: MouseEvent) {
		// Only handle on mobile when menu is open
		if (!isMobileMenuOpen) return;

		// Check if we're on mobile (viewport < 768px)
		if (window.innerWidth >= 768) return;

		const target = event.target as Node;

		// Check if click is outside the sidebar
		if (sidebarElement && !sidebarElement.contains(target)) {
			closeMobileMenu();
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} onclick={handleClickOutside} />

<div class="bg-admin-bg-primary text-admin-text-primary flex min-h-screen p-4 md:p-6">
	<!-- Sidebar wrapper for click-outside detection -->
	<div bind:this={sidebarElement}>
		<AdminSidebar isOpen={isMobileMenuOpen} onClose={closeMobileMenu} />
	</div>

	<!-- Main content area -->
	<div class="flex min-h-[calc(100vh-3rem)] flex-1 flex-col md:ml-6">
		<!-- Mobile header with hamburger -->
		<header class="glass-panel mb-4 flex h-14 items-center rounded-2xl px-4 md:hidden">
			<button
				onclick={openMobileMenu}
				class="text-admin-text-secondary hover:text-admin-text-primary hover:bg-admin-bg-card flex h-10 w-10 items-center justify-center rounded-lg transition-colors duration-200"
				aria-label="Open menu"
			>
				<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M4 6h16M4 12h16M4 18h16"
					/>
				</svg>
			</button>
			<span class="text-admin-text-primary ml-4 text-lg font-semibold">Admin</span>
		</header>

		<!-- Scrollable content area with page transition -->
		<main class="glass-panel flex flex-1 flex-col overflow-y-auto rounded-2xl p-4 md:p-6 lg:p-8">
			{#key contentKey}
				<div class="animate-page-fade-in flex-1">
					{@render children()}
				</div>
			{/key}
		</main>
	</div>
</div>
