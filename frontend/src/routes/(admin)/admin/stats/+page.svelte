<script lang="ts">
	// (admin)/admin/stats/+page.svelte
	// Admin - Statisztikák oldal
	// Követelmények: 5.3, 6.4

	// Placeholder stats for demonstration
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

<div class="flex h-full flex-col space-y-6">
	<section class="page-header">
		<h1 class="text-admin-text-primary mb-1 text-2xl font-bold">Rendszer Statisztikák</h1>
		<p class="text-admin-text-secondary">Áttekintés a rendszer teljesítményéről és használatáról</p>
	</section>

	<div class="stats-grid">
		<div class="stat-card glass-panel">
			<div class="stat-icon">👥</div>
			<div class="stat-content">
				<span class="stat-value">{stats.totalUsers.toLocaleString()}</span>
				<span class="stat-label">Összes felhasználó</span>
			</div>
		</div>

		<div class="stat-card glass-panel">
			<div class="stat-icon">✅</div>
			<div class="stat-content">
				<span class="stat-value">{stats.activeUsers.toLocaleString()}</span>
				<span class="stat-label">Aktív felhasználó</span>
			</div>
		</div>

		<div class="stat-card glass-panel">
			<div class="stat-icon">📊</div>
			<div class="stat-content">
				<span class="stat-value">{stats.totalRequests.toLocaleString()}</span>
				<span class="stat-label">Összes kérés</span>
			</div>
		</div>

		<div class="stat-card glass-panel">
			<div class="stat-icon">⚡</div>
			<div class="stat-content">
				<span class="stat-value">{stats.avgResponseTime}</span>
				<span class="stat-label">Átlagos válaszidő</span>
			</div>
		</div>
	</div>

	<section class="chart-section flex-1">
		<h3>Aktivitás</h3>
		<div class="chart-placeholder">
			<p>📈 Grafikon helye</p>
			<p class="chart-note">A valós implementációban itt jelenne meg az aktivitási grafikon.</p>
		</div>
	</section>

	<section class="info-section">
		<p>
			<strong>Megjegyzés:</strong> Ez egy példa oldal. A valós implementációban az adatok a külső API-ból
			érkeznének és valós grafikonok jelennének meg.
		</p>
	</section>
</div>

<style>
	.page-header {
		margin-bottom: 0;
	}

	.stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 1.5rem;
	}

	.stat-card {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1.5rem;
	}

	.stat-icon {
		font-size: 2rem;
	}

	.stat-content {
		display: flex;
		flex-direction: column;
	}

	.stat-value {
		color: #f9fafb;
		font-weight: 600;
		font-size: 1.5rem;
	}

	.stat-label {
		color: #9ca3af;
		font-size: 0.875rem;
	}

	.chart-section {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		background-color: rgba(31, 41, 55, 0.5);
		padding: 1.5rem;
	}

	.chart-section h3 {
		margin: 0 0 1rem;
		color: #d1d5db;
		font-size: 1rem;
	}

	.chart-placeholder {
		border-radius: 8px;
		background-color: rgba(55, 65, 81, 0.5);
		padding: 3rem;
		text-align: center;
	}

	.chart-placeholder p {
		margin: 0;
		color: #9ca3af;
	}

	.chart-placeholder p:first-child {
		margin-bottom: 0.5rem;
		font-size: 2rem;
	}

	.chart-note {
		font-size: 0.875rem;
	}

	.info-section {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		background-color: rgba(31, 41, 55, 0.5);
		padding: 1rem 1.5rem;
	}

	.info-section p {
		margin: 0;
		color: #9ca3af;
		font-size: 0.875rem;
	}
</style>
