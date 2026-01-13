<script lang="ts">
	const users = [
		{
			id: '1',
			name: 'Admin User',
			email: 'admin@example.com',
			permissions: ['admin', 'read', 'write']
		},
		{ id: '2', name: 'Regular User', email: 'user@example.com', permissions: ['read'] },
		{ id: '3', name: 'Editor', email: 'editor@example.com', permissions: ['read', 'write'] }
	];
</script>

<svelte:head>
	<title>Admin - Felhasználók</title>
</svelte:head>

<div class="space-y-6">
	<section>
		<h1 class="mb-1 text-2xl font-bold text-slate-800">Felhasználók</h1>
		<p class="text-slate-500">Felhasználók listája és kezelése</p>
	</section>

	<section class="overflow-hidden rounded-xl bg-white shadow-sm">
		<div class="overflow-x-auto">
			<table class="w-full">
				<thead>
					<tr class="border-b border-slate-200 bg-slate-50">
						<th class="px-6 py-3 text-left text-sm font-medium text-slate-600">ID</th>
						<th class="px-6 py-3 text-left text-sm font-medium text-slate-600">Név</th>
						<th class="px-6 py-3 text-left text-sm font-medium text-slate-600">E-mail</th>
						<th class="px-6 py-3 text-left text-sm font-medium text-slate-600">Jogosultságok</th>
						<th class="px-6 py-3 text-left text-sm font-medium text-slate-600">Műveletek</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-200">
					{#each users as user}
						<tr class="hover:bg-slate-50">
							<td class="px-6 py-4 text-sm text-slate-600">{user.id}</td>
							<td class="px-6 py-4 text-sm font-medium text-slate-800">{user.name}</td>
							<td class="px-6 py-4 text-sm text-slate-600">{user.email}</td>
							<td class="px-6 py-4">
								<div class="flex flex-wrap gap-1">
									{#each user.permissions as permission}
										<span
											class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700"
										>
											{permission}
										</span>
									{/each}
								</div>
							</td>
							<td class="px-6 py-4">
								<button
									class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-purple-700"
								>
									Szerkesztés
								</button>
							</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	</section>

	<section class="rounded-xl bg-amber-50 p-4">
		<p class="text-sm text-amber-800">
			<span class="font-medium">Megjegyzés:</span> Ez egy példa oldal. A valós implementációban az adatok
			a külső API-ból érkeznének.
		</p>
	</section>
</div>
