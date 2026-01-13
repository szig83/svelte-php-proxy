<script lang="ts">
	import { getAuthState } from '$lib/auth';

	const authState = getAuthState();

	const navigationCards = [
		{
			label: 'Felhasználók',
			href: '/admin/users',
			description: 'Felhasználók kezelése és jogosultságok beállítása',
			icon: 'users',
			color: 'bg-blue-500',
			bgColor: 'bg-blue-50'
		},
		{
			label: 'Statisztikák',
			href: '/admin/stats',
			description: 'Rendszer statisztikák és jelentések megtekintése',
			icon: 'chart',
			color: 'bg-purple-500',
			bgColor: 'bg-purple-50'
		},
		{
			label: 'Hibák',
			href: '/admin/errors',
			description: 'Frontend hibák megtekintése és elemzése',
			icon: 'bug',
			color: 'bg-amber-500',
			bgColor: 'bg-amber-50'
		}
	];
</script>

<svelte:head>
	<title>Admin - Vezérlőpult</title>
</svelte:head>

<div class="space-y-6">
	<!-- Welcome Section -->
	<section
		class="rounded-xl bg-linear-to-br from-purple-600 to-indigo-700 p-8 text-white shadow-lg"
	>
		<h1 class="mb-2 text-2xl font-bold md:text-3xl">
			Üdvözöljük, <span data-testid="user-name">{authState.user?.name || 'Felhasználó'}</span>!
		</h1>
		<p class="text-purple-100">Az admin vezérlőpultról kezelheti a rendszer összes funkcióját.</p>
	</section>

	<!-- Navigation Cards -->
	<section class="space-y-4">
		<h2 class="text-lg font-semibold text-slate-800">Gyors navigáció</h2>

		<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
			{#each navigationCards as card}
				<a
					href={card.href}
					class="group rounded-xl bg-white p-6 shadow-sm transition-all hover:shadow-md"
				>
					<!-- Icon -->
					<div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl {card.bgColor}">
						{#if card.icon === 'users'}
							<svg
								class="h-6 w-6 text-blue-600"
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
								class="h-6 w-6 text-purple-600"
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
								class="h-6 w-6 text-amber-600"
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
					<h3 class="mb-2 text-lg font-semibold text-slate-800">{card.label}</h3>

					<!-- Description -->
					<p class="mb-4 text-sm text-slate-500">{card.description}</p>

					<!-- Arrow indicator -->
					<div
						class="flex items-center gap-1 text-sm text-slate-400 transition-all group-hover:translate-x-1 group-hover:text-purple-600"
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
				</a>
			{/each}
		</div>
	</section>

	<!-- Permissions Section -->
	<section class="rounded-xl bg-white p-6 shadow-sm" data-testid="permissions-section">
		<h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-800">
			<svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
				{#each authState.user.permissions as permission}
					<span
						class="rounded-full bg-purple-100 px-3 py-1.5 text-xs font-medium text-purple-700"
						data-testid="permission-badge"
					>
						{permission}
					</span>
				{/each}
			{:else}
				<span class="text-sm text-slate-500">Nincsenek jogosultságok</span>
			{/if}
		</div>
	</section>
</div>
