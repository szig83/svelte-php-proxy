<script lang="ts">
	// (admin)/admin/+page.svelte
	// Admin főoldal - Futurisztikus redesign
	// Requirements: 6.1, 6.2, 6.3, 6.4, 2.4, 2.5

	import { getAuthState } from '$lib/auth';

	const authState = getAuthState();

	const navigationCards = [
		{
			label: 'Felhasználók',
			href: '/admin/users',
			description: 'Felhasználók kezelése és jogosultságok beállítása',
			icon: 'users' as const,
			gradient: 'from-cyan-500/20 to-blue-500/20',
			glowColor: 'rgba(0, 212, 255, 0.3)'
		},
		{
			label: 'Statisztikák',
			href: '/admin/stats',
			description: 'Rendszer statisztikák és jelentések megtekintése',
			icon: 'chart' as const,
			gradient: 'from-purple-500/20 to-pink-500/20',
			glowColor: 'rgba(139, 92, 246, 0.3)'
		},
		{
			label: 'Hibák',
			href: '/admin/errors',
			description: 'Frontend hibák megtekintése és elemzése',
			icon: 'bug' as const,
			gradient: 'from-orange-500/20 to-red-500/20',
			glowColor: 'rgba(249, 115, 22, 0.3)'
		}
	];
</script>

<svelte:head>
	<title>Admin - Vezérlőpult</title>
</svelte:head>

<div class="space-y-8">
	<!-- Welcome Section -->
	<section
		class="glass-panel glossy-card animate-fade-in-up relative overflow-hidden rounded-2xl p-8"
	>
		<!-- Gradient overlay -->
		<div
			class="from-admin-accent-cyan/10 to-admin-accent-purple/10 pointer-events-none absolute inset-0 bg-linear-to-br"
		></div>

		<!-- Glow effect -->
		<div
			class="pointer-events-none absolute -top-20 -right-20 h-40 w-40 rounded-full bg-[radial-gradient(circle,rgba(0,212,255,0.15)_0%,transparent_70%)]"
		></div>

		<div class="relative z-10">
			<h1 class="text-admin-text-primary mb-2 text-2xl font-bold md:text-3xl">
				Üdvözöljük, <span
					class="from-admin-accent-cyan to-admin-accent-purple bg-linear-to-r bg-clip-text text-transparent"
					data-testid="user-name">{authState.user?.name || 'Felhasználó'}</span
				>!
			</h1>
			<p class="text-admin-text-secondary text-base">
				Az admin vezérlőpultról kezelheti a rendszer összes funkcióját.
			</p>
		</div>
	</section>

	<!-- Navigation Cards -->
	<section class="space-y-4">
		<h2
			class="text-admin-text-primary animate-fade-in-up text-lg font-semibold"
			style="animation-delay: 100ms"
		>
			Gyors navigáció
		</h2>

		<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
			{#each navigationCards as card, index}
				<a
					href={card.href}
					class="glass-panel glossy-card group animate-fade-in-up relative overflow-hidden rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02]"
					style="animation-delay: {150 + index * 50}ms; --glow-color: {card.glowColor}"
				>
					<!-- Gradient background -->
					<div
						class="pointer-events-none absolute inset-0 bg-linear-to-br opacity-0 transition-opacity duration-300 group-hover:opacity-100 {card.gradient}"
					></div>

					<!-- Glow effect on hover -->
					<div
						class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 shadow-[0_0_30px_var(--glow-color)] transition-opacity duration-300 group-hover:opacity-100"
					></div>

					<div class="relative z-10">
						<!-- Icon -->
						<div
							class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-white/5 transition-all duration-300 group-hover:scale-110 group-hover:bg-white/10"
						>
							{#if card.icon === 'users'}
								<svg
									class="text-admin-accent-cyan h-6 w-6"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
									/>
								</svg>
							{:else if card.icon === 'chart'}
								<svg
									class="text-admin-accent-purple h-6 w-6"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
									/>
								</svg>
							{:else if card.icon === 'bug'}
								<svg
									class="h-6 w-6 text-orange-400"
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="1.5"
										d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
									/>
								</svg>
							{/if}
						</div>

						<!-- Title -->
						<h3
							class="text-admin-text-primary mb-2 text-lg font-semibold transition-colors duration-300"
						>
							{card.label}
						</h3>

						<!-- Description -->
						<p class="text-admin-text-secondary text-sm">{card.description}</p>

						<!-- Arrow indicator -->
						<div
							class="text-admin-text-muted mt-4 flex items-center gap-1 text-sm transition-all duration-300 group-hover:translate-x-1 group-hover:text-white/80"
						>
							<span>Megnyitás</span>
							<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M9 5l7 7-7 7"
								/>
							</svg>
						</div>
					</div>
				</a>
			{/each}
		</div>
	</section>

	<!-- Permissions Section -->
	<section
		class="glass-panel animate-fade-in-up rounded-2xl p-6"
		style="animation-delay: 350ms"
		data-testid="permissions-section"
	>
		<h3 class="text-admin-text-primary mb-4 flex items-center gap-2 text-base font-semibold">
			<svg
				class="text-admin-accent-cyan h-5 w-5"
				fill="none"
				stroke="currentColor"
				viewBox="0 0 24 24"
			>
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
				/>
			</svg>
			Az Ön jogosultságai
		</h3>

		<div class="flex flex-wrap gap-2" data-testid="permissions-list">
			{#if authState.user?.permissions && authState.user.permissions.length > 0}
				{#each authState.user.permissions as permission, index}
					<span
						class="from-admin-accent-cyan/10 to-admin-accent-purple/10 text-admin-text-primary animate-fade-in-up rounded-full border border-white/10 bg-linear-to-r px-3 py-1.5 text-xs font-medium"
						style="animation-delay: {400 + index * 30}ms"
						data-testid="permission-badge"
					>
						{permission}
					</span>
				{/each}
			{:else}
				<span class="text-admin-text-muted text-sm">Nincsenek jogosultságok</span>
			{/if}
		</div>
	</section>
</div>
