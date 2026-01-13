<script lang="ts">
	import { onMount } from 'svelte';
	import { page } from '$app/state';
	import { fetchMenu, type MenuItem, type MenuType } from '$lib/api/menu';
	import MenuIcon from './MenuIcon.svelte';

	interface Props {
		/** Menu type to fetch (protected, admin, etc.) */
		type?: MenuType;
		/** Additional CSS classes */
		class?: string;
	}

	let { type = 'protected', class: className = '' }: Props = $props();

	let menuItems = $state<MenuItem[]>([]);
	let isLoading = $state(true);
	let error = $state<string | null>(null);
	let expandedItems = $state<Set<string>>(new Set());

	/**
	 * Check if a menu item is active based on current path
	 */
	function isActive(href: string, currentPath: string): boolean {
		if (href === '/') {
			return currentPath === '/';
		}
		return currentPath === href || currentPath.startsWith(href + '/');
	}

	/**
	 * Check if a menu item or any of its children is active
	 */
	function isActiveOrChildActive(item: MenuItem, currentPath: string): boolean {
		if (isActive(item.href, currentPath)) {
			return true;
		}
		if (item.children) {
			return item.children.some((child) => isActiveOrChildActive(child, currentPath));
		}
		return false;
	}

	/**
	 * Toggle expanded state for a menu item
	 */
	function toggleExpanded(itemHref: string) {
		const newExpanded = new Set(expandedItems);
		if (newExpanded.has(itemHref)) {
			newExpanded.delete(itemHref);
		} else {
			newExpanded.add(itemHref);
		}
		expandedItems = newExpanded;
	}

	/**
	 * Check if an item is expanded
	 */
	function isExpanded(itemHref: string): boolean {
		return expandedItems.has(itemHref);
	}

	/**
	 * Load menu items from the backend
	 */
	async function loadMenu() {
		isLoading = true;
		error = null;

		const response = await fetchMenu(type);

		if (response.success && response.items) {
			menuItems = response.items;

			// Auto-expand items that have active children
			const newExpanded = new Set<string>();
			menuItems.forEach((item) => {
				if (item.children && isActiveOrChildActive(item, page.url.pathname)) {
					newExpanded.add(item.href);
				}
			});
			expandedItems = newExpanded;
		} else {
			error = response.error?.message || 'Failed to load menu';
		}

		isLoading = false;
	}

	onMount(() => {
		loadMenu();
	});
</script>

{#if isLoading}
	<div class="px-3 py-4 text-center">
		<div
			class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-400 border-t-transparent"
		></div>
		<p class="mt-2 text-xs text-slate-400">Menü betöltése...</p>
	</div>
{:else if error}
	<div class="px-3 py-4">
		<p class="text-xs text-red-400">{error}</p>
		<button onclick={loadMenu} class="mt-2 text-xs text-slate-400 hover:text-white">
			Újrapróbálás
		</button>
	</div>
{:else}
	<nav class="flex-1 overflow-y-auto px-3 {className}">
		<ul class="space-y-0.5">
			{#each menuItems as item}
				{@const active = isActive(item.href, page.url.pathname)}
				{@const hasChildren = item.children && item.children.length > 0}
				{@const expanded = isExpanded(item.href)}
				<li>
					{#if hasChildren}
						<!-- Parent item with children - clickable to expand/collapse -->
						<button
							onclick={() => toggleExpanded(item.href)}
							class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[13px] transition-colors
								{active
								? 'bg-white/10 font-medium text-white'
								: 'text-gray-400/90 hover:bg-white/5 hover:text-white'}"
						>
							<span
								class="flex h-4 w-4 shrink-0 items-center justify-center {active
									? 'text-blue-400'
									: 'text-slate-500'}"
							>
								<MenuIcon icon={item.icon} />
							</span>
							<span class="flex-1 truncate text-left">{item.label}</span>
							<!-- Expand/collapse indicator -->
							<span
								class="flex h-4 w-4 shrink-0 items-center justify-center text-slate-500 transition-transform {expanded
									? 'rotate-90'
									: ''}"
							>
								<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M9 5l7 7-7 7"
									/>
								</svg>
							</span>
						</button>

						<!-- Nested children menu items -->
						{#if expanded}
							<ul class="mt-0.5 ml-7 space-y-0.5 border-l border-slate-600 pl-3">
								{#each item.children as child}
									{@const childActive = isActive(child.href, page.url.pathname)}
									{@const childHasChildren = child.children && child.children.length > 0}
									{@const childExpanded = isExpanded(child.href)}
									<li>
										{#if childHasChildren}
											<!-- Second level parent with children -->
											<button
												onclick={() => toggleExpanded(child.href)}
												class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-xs transition-colors
													{childActive
													? 'bg-white/10 font-medium text-white'
													: 'text-gray-400/80 hover:bg-white/5 hover:text-white'}"
											>
												<span
													class="flex h-3 w-3 shrink-0 items-center justify-center {childActive
														? 'text-blue-400'
														: 'text-slate-500'}"
												>
													<MenuIcon icon={child.icon} size="sm" />
												</span>
												<span class="flex-1 truncate text-left">{child.label}</span>
												<span
													class="flex h-3 w-3 shrink-0 items-center justify-center text-slate-500 transition-transform {childExpanded
														? 'rotate-90'
														: ''}"
												>
													<svg
														class="h-2 w-2"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
													>
														<path
															stroke-linecap="round"
															stroke-linejoin="round"
															stroke-width="2"
															d="M9 5l7 7-7 7"
														/>
													</svg>
												</span>
											</button>

											<!-- Support for deeper nesting (recursive) -->
											{#if childExpanded && child.children}
												<ul class="mt-0.5 ml-5 space-y-0.5 border-l border-slate-600/50 pl-2">
													{#each child.children as grandchild}
														{@const grandchildActive = isActive(grandchild.href, page.url.pathname)}
														<li>
															<a
																href={grandchild.href}
																class="flex items-center gap-2 rounded-lg px-2 py-1 text-xs transition-colors
																	{grandchildActive
																	? 'bg-white/10 font-medium text-white'
																	: 'text-gray-400/70 hover:bg-white/5 hover:text-white'}"
															>
																<span
																	class="flex h-2 w-2 shrink-0 items-center justify-center {grandchildActive
																		? 'text-blue-400'
																		: 'text-slate-500'}"
																>
																	<MenuIcon icon={grandchild.icon} size="xs" />
																</span>
																<span class="truncate">{grandchild.label}</span>
															</a>
														</li>
													{/each}
												</ul>
											{/if}
										{:else}
											<!-- Second level leaf item -->
											<a
												href={child.href}
												class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs transition-colors
													{childActive
													? 'bg-white/10 font-medium text-white'
													: 'text-gray-400/80 hover:bg-white/5 hover:text-white'}"
											>
												<span
													class="flex h-3 w-3 shrink-0 items-center justify-center {childActive
														? 'text-blue-400'
														: 'text-slate-500'}"
												>
													<MenuIcon icon={child.icon} size="sm" />
												</span>
												<span class="truncate">{child.label}</span>
											</a>
										{/if}
									</li>
								{/each}
							</ul>
						{/if}
					{:else}
						<!-- Leaf item without children - regular link -->
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
								<MenuIcon icon={item.icon} />
							</span>
							<span class="truncate">{item.label}</span>
						</a>
					{/if}
				</li>
			{/each}
		</ul>
	</nav>
{/if}
