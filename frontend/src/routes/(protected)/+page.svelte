<script lang="ts">
	// (protected)/+page.svelte
	// Főoldal (védett) - Egyenleglekérdezés
	// Követelmények: 5.3

	import { getAuthState } from '$lib/auth';
	import { PermissionGate } from '$lib/components';

	const authState = getAuthState();
</script>

<svelte:head>
	<title>Egyenleglekérdezés - Új Pillér Egészségpénztár</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page header -->
	<div>
		<h1 class="text-2xl font-semibold text-gray-800">Egyenleglekérdezés</h1>
		<p class="mt-1 text-sm text-gray-500">Üdvözöljük, {authState.user?.name}!</p>
	</div>

	<!-- Balance cards -->
	<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
		<!-- Main balance card -->
		<div class="rounded-xl bg-linear-to-br from-blue-600 to-blue-800 p-6 text-white shadow-lg">
			<div class="flex items-center justify-between">
				<div>
					<p class="text-sm font-medium text-blue-100">Aktuális egyenleg</p>
					<p class="mt-2 text-3xl font-bold">1 234 567 Ft</p>
				</div>
				<div class="rounded-full bg-white/20 p-3">
					<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
						/>
					</svg>
				</div>
			</div>
			<div class="mt-4 flex items-center gap-2 text-sm text-blue-100">
				<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
					/>
				</svg>
				<span>+12.5% az előző hónaphoz képest</span>
			</div>
		</div>

		<!-- Available balance -->
		<div class="rounded-xl bg-gray-50 p-6">
			<div class="flex items-center justify-between">
				<div>
					<p class="text-sm font-medium text-gray-500">Felhasználható egyenleg</p>
					<p class="mt-2 text-2xl font-bold text-gray-800">987 654 Ft</p>
				</div>
				<div class="rounded-full bg-green-100 p-3 text-green-600">
					<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				</div>
			</div>
		</div>

		<!-- Pending -->
		<div class="rounded-xl bg-gray-50 p-6">
			<div class="flex items-center justify-between">
				<div>
					<p class="text-sm font-medium text-gray-500">Függőben lévő</p>
					<p class="mt-2 text-2xl font-bold text-gray-800">246 913 Ft</p>
				</div>
				<div class="rounded-full bg-amber-100 p-3 text-amber-600">
					<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
						/>
					</svg>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent transactions -->
	<div class="rounded-xl bg-gray-50 p-6">
		<div class="mb-4 flex items-center justify-between">
			<h2 class="text-lg font-semibold text-gray-800">Legutóbbi tranzakciók</h2>
			<button class="text-sm font-medium text-blue-600 hover:text-blue-700"
				>Összes megtekintése</button
			>
		</div>
		<div class="divide-y divide-gray-200">
			{#each [{ name: 'Gyógyszertár', date: '2026. jan. 10.', amount: '-4 500 Ft', type: 'expense' }, { name: 'Befizetés', date: '2026. jan. 8.', amount: '+50 000 Ft', type: 'income' }, { name: 'Szemészet', date: '2026. jan. 5.', amount: '-12 000 Ft', type: 'expense' }, { name: 'Fogorvos', date: '2026. jan. 3.', amount: '-25 000 Ft', type: 'expense' }, { name: 'Munkáltatói befizetés', date: '2026. jan. 1.', amount: '+100 000 Ft', type: 'income' }] as transaction}
				<div class="flex items-center justify-between py-3">
					<div class="flex items-center gap-3">
						<div
							class="flex h-10 w-10 items-center justify-center rounded-full {transaction.type ===
							'income'
								? 'bg-green-100 text-green-600'
								: 'bg-gray-200 text-gray-600'}"
						>
							{#if transaction.type === 'income'}
								<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M12 4v16m8-8H4"
									/>
								</svg>
							{:else}
								<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M20 12H4"
									/>
								</svg>
							{/if}
						</div>
						<div>
							<p class="font-medium text-gray-800">{transaction.name}</p>
							<p class="text-sm text-gray-500">{transaction.date}</p>
						</div>
					</div>
					<span
						class="font-medium {transaction.type === 'income' ? 'text-green-600' : 'text-gray-800'}"
					>
						{transaction.amount}
					</span>
				</div>
			{/each}
		</div>
	</div>

	<!-- User info section -->
	<div class="rounded-xl bg-gray-50 p-6">
		<h2 class="mb-4 text-lg font-semibold text-gray-800">Felhasználói adatok</h2>
		<div class="grid gap-4 sm:grid-cols-2">
			<div class="rounded-lg bg-white p-4">
				<p class="text-sm text-gray-500">Azonosító</p>
				<p class="mt-1 font-medium text-gray-800">{authState.user?.id}</p>
			</div>
			<div class="rounded-lg bg-white p-4">
				<p class="text-sm text-gray-500">E-mail</p>
				<p class="mt-1 font-medium text-gray-800">{authState.user?.email}</p>
			</div>
			<div class="rounded-lg bg-white p-4">
				<p class="text-sm text-gray-500">Név</p>
				<p class="mt-1 font-medium text-gray-800">{authState.user?.name}</p>
			</div>
			<div class="rounded-lg bg-white p-4">
				<p class="text-sm text-gray-500">Jogosultságok</p>
				<p class="mt-1 font-medium text-gray-800">
					{#if authState.user?.permissions.length}
						{authState.user.permissions.join(', ')}
					{:else}
						Nincs
					{/if}
				</p>
			</div>
		</div>
	</div>

	<!-- Admin notice -->
	<PermissionGate permission="admin">
		<div class="rounded-xl bg-linear-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-lg">
			<div class="flex items-center gap-4">
				<div class="rounded-full bg-white/20 p-3">
					<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
						/>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
						/>
					</svg>
				</div>
				<div>
					<h3 class="text-lg font-semibold">Admin hozzáférés</h3>
					<p class="mt-1 text-blue-100">Admin jogosultsággal rendelkezik.</p>
				</div>
				<a
					href="/admin"
					class="ml-auto rounded-lg bg-white/20 px-4 py-2 text-sm font-medium hover:bg-white/30"
				>
					Admin felület →
				</a>
			</div>
		</div>
	</PermissionGate>
</div>
