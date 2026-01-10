<script lang="ts">
	// (admin)/admin/stats/+page.svelte
	// Admin - Statisztikák oldal
	// Követelmények: 5.3, 6.4

	import { getAuthState, logout } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}

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

<div class="admin-container">
	<header class="header">
		<h1>Statisztikák</h1>
		<nav class="nav">
			<a href="/" class="nav-link">Főoldal</a>
			<a href="/dashboard" class="nav-link">Dashboard</a>
			<a href="/admin" class="nav-link">Admin</a>
			<button onclick={handleLogout} class="logout-button">Kijelentkezés</button>
		</nav>
	</header>

	<main class="main">
		<section class="page-header">
			<h2>Rendszer Statisztikák</h2>
			<p>Áttekintés a rendszer teljesítményéről és használatáról</p>
		</section>

		<div class="stats-grid">
			<div class="stat-card">
				<div class="stat-icon">👥</div>
				<div class="stat-content">
					<span class="stat-value">{stats.totalUsers.toLocaleString()}</span>
					<span class="stat-label">Összes felhasználó</span>
				</div>
			</div>

			<div class="stat-card">
				<div class="stat-icon">✅</div>
				<div class="stat-content">
					<span class="stat-value">{stats.activeUsers.toLocaleString()}</span>
					<span class="stat-label">Aktív felhasználó</span>
				</div>
			</div>

			<div class="stat-card">
				<div class="stat-icon">📊</div>
				<div class="stat-content">
					<span class="stat-value">{stats.totalRequests.toLocaleString()}</span>
					<span class="stat-label">Összes kérés</span>
				</div>
			</div>

			<div class="stat-card">
				<div class="stat-icon">⚡</div>
				<div class="stat-content">
					<span class="stat-value">{stats.avgResponseTime}</span>
					<span class="stat-label">Átlagos válaszidő</span>
				</div>
			</div>
		</div>

		<section class="chart-section">
			<h3>Aktivitás</h3>
			<div class="chart-placeholder">
				<p>📈 Grafikon helye</p>
				<p class="chart-note">A valós implementációban itt jelenne meg az aktivitási grafikon.</p>
			</div>
		</section>

		<section class="info-section">
			<p>
				<strong>Megjegyzés:</strong> Ez egy példa oldal. A valós implementációban az adatok
				a külső API-ból érkeznének és valós grafikonok jelennének meg.
			</p>
		</section>
	</main>
</div>

<style>
	.admin-container {
		min-height: 100vh;
		font-family: system-ui, -apple-system, sans-serif;
		background-color: #111827;
		color: white;
	}

	.header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 2rem;
		background-color: #1f2937;
		border-bottom: 1px solid #374151;
	}

	.header h1 {
		margin: 0;
		font-size: 1.25rem;
		color: #f9fafb;
	}

	.nav {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.nav-link {
		color: #9ca3af;
		text-decoration: none;
		font-size: 0.875rem;
		transition: color 0.2s;
	}

	.nav-link:hover {
		color: white;
	}

	.logout-button {
		background: none;
		border: 1px solid #6b7280;
		color: #d1d5db;
		padding: 0.5rem 1rem;
		border-radius: 4px;
		cursor: pointer;
		font-size: 0.875rem;
		transition: all 0.2s;
	}

	.logout-button:hover {
		background-color: #374151;
		border-color: #9ca3af;
	}

	.main {
		max-width: 1000px;
		margin: 0 auto;
		padding: 2rem;
	}

	.page-header {
		margin-bottom: 2rem;
	}

	.page-header h2 {
		margin: 0 0 0.5rem;
		font-size: 1.5rem;
	}

	.page-header p {
		margin: 0;
		color: #9ca3af;
	}

	.stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 1.5rem;
		margin-bottom: 2rem;
	}

	.stat-card {
		background-color: #1f2937;
		padding: 1.5rem;
		border-radius: 8px;
		border: 1px solid #374151;
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.stat-icon {
		font-size: 2rem;
	}

	.stat-content {
		display: flex;
		flex-direction: column;
	}

	.stat-value {
		font-size: 1.5rem;
		font-weight: 600;
		color: #f9fafb;
	}

	.stat-label {
		font-size: 0.875rem;
		color: #9ca3af;
	}

	.chart-section {
		background-color: #1f2937;
		padding: 1.5rem;
		border-radius: 8px;
		border: 1px solid #374151;
		margin-bottom: 1.5rem;
	}

	.chart-section h3 {
		margin: 0 0 1rem;
		font-size: 1rem;
		color: #d1d5db;
	}

	.chart-placeholder {
		background-color: #374151;
		border-radius: 6px;
		padding: 3rem;
		text-align: center;
	}

	.chart-placeholder p {
		margin: 0;
		color: #9ca3af;
	}

	.chart-placeholder p:first-child {
		font-size: 2rem;
		margin-bottom: 0.5rem;
	}

	.chart-note {
		font-size: 0.875rem;
	}

	.info-section {
		background-color: #1f2937;
		padding: 1rem 1.5rem;
		border-radius: 8px;
		border: 1px solid #374151;
	}

	.info-section p {
		margin: 0;
		color: #9ca3af;
		font-size: 0.875rem;
	}
</style>
