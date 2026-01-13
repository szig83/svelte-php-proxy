<script lang="ts">
	interface Props {
		label: string;
		href: string;
		icon: 'dashboard' | 'users' | 'chart' | 'bug';
		isActive: boolean;
		animationDelay?: number;
	}

	let { label, href, icon, isActive, animationDelay = 0 }: Props = $props();
</script>

<a
	{href}
	class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-300
		{isActive
		? 'bg-admin-accent-cyan/10 text-admin-accent-cyan border-admin-accent-cyan border-l-2 shadow-[0_0_15px_rgba(0,212,255,0.15)]'
		: 'text-admin-text-secondary hover:text-admin-text-primary hover:scale-[1.02] hover:bg-white/5 hover:shadow-[0_0_20px_rgba(0,212,255,0.1)]'}"
	style="animation-delay: {animationDelay}ms"
>
	<span
		class="flex h-5 w-5 items-center justify-center transition-all duration-200 group-hover:scale-110 {isActive
			? 'scale-110'
			: ''}"
	>
		{#if icon === 'dashboard'}
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"
				/>
			</svg>
		{:else if icon === 'users'}
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
				/>
			</svg>
		{:else if icon === 'chart'}
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
				/>
			</svg>
		{:else if icon === 'bug'}
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
				/>
			</svg>
		{:else}
			<!-- Fallback icon -->
			<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M13 10V3L4 14h7v7l9-11h-7z"
				/>
			</svg>
		{/if}
	</span>
	<span class="truncate">{label}</span>
</a>
