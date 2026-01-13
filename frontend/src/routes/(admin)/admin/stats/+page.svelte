<script lang="ts">
	const stats = {
		totalUsers: 1234,
		activeUsers: 567,
		totalRequests: 45678,
		avgResponseTime: '45ms'
	};
</script>

<svelte:head>
	<title>Admin - Statisztikák</title>
</svelte:head>

<div class="space-y-6">
	<section>
		<h1 class="mb-1 text-2xl font-bold text-slate-800">Rendszer Statisztikák</h1>
		<p class="text-slate-500">Áttekintés a rendszer teljesítményéről és használatáról</p>
	</section>

	<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
		<div class="rounded-xl bg-white p-6 shadow-sm">
			<div class="mb-2 text-2xl">👥</div>
			<p class="text-2xl font-bold text-slate-800">{stats.totalUsers.toLocaleString()}</p>
			<p class="text-sm text-slate-500">Összes felhasználó</p>
		</div>

		<div class="rounded-xl bg-white p-6 shadow-sm">
			<div class="mb-2 text-2xl">✅</div>
			<p class="text-2xl font-bold text-slate-800">{stats.activeUsers.toLocaleString()}</p>
			<p class="text-sm text-slate-500">Aktív felhasználó</p>
		</div>

		<div class="rounded-xl bg-white p-6 shadow-sm">
			<div class="mb-2 text-2xl">📊</div>
			<p class="text-2xl font-bold text-slate-800">{stats.totalRequests.toLocaleString()}</p>
			<p class="text-sm text-slate-500">Összes kérés</p>
		</div>

		<div class="rounded-xl bg-white p-6 shadow-sm">
			<div class="mb-2 text-2xl">⚡</div>
			<p class="text-2xl font-bold text-slate-800">{stats.avgResponseTime}</p>
			<p class="text-sm text-slate-500">Átlagos válaszidő</p>
		</div>
	</div>

	<section class="rounded-xl bg-white p-6 shadow-sm">
		<h3 class="mb-4 font-semibold text-slate-800">Aktivitás</h3>
		<div class="flex items-center justify-center rounded-lg bg-slate-50 py-12">
			<div class="text-center">
				<p class="mb-2 text-4xl">📈</p>
				<p class="text-slate-500">Grafikon helye</p>
				<p class="text-sm text-slate-400">
					A valós implementációban itt jelenne meg az aktivitási grafikon.
				</p>
			</div>
		</div>
	</section>

	<section class="rounded-xl bg-amber-50 p-4">
		<p class="text-sm text-amber-800">
			<span class="font-medium">Megjegyzés:</span> Ez egy példa oldal. A valós implementációban az adatok
			a külső API-ból érkeznének és valós grafikonok jelennének meg.
		</p>
	</section>
</div>
