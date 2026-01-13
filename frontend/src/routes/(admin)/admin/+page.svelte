<script lang="ts">
	// (admin)/admin/+page.svelte
	// Admin főoldal
	// Követelmények: 5.3, 6.4

	import { getAuthState, logout } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}
</script>

<svelte:head>
	<title>Admin - Vezérlőpult</title>
</svelte:head>

<div class="admin-container">
	<header class="header">
		<h1>Admin Vezérlőpult</h1>
		<nav class="nav">
			<a href="/" class="nav-link">Főoldal</a>
			<a href="/dashboard" class="nav-link">Dashboard</a>
			<a href="/admin/users" class="nav-link">Felhasználók</a>
			<a href="/admin/stats" class="nav-link">Statisztikák</a>
			<a href="/admin/errors" class="nav-link">Hibák</a>
			<button onclick={handleLogout} class="logout-button">Kijelentkezés</button>
		</nav>
	</header>

	<main class="main">
		<section class="welcome-section">
			<h2>Admin Vezérlőpult</h2>
			<p>Üdvözöljük az admin felületen, {authState.user?.name}!</p>
		</section>

		<div class="cards-grid">
			<a href="/admin/users" class="card">
				<div class="card-icon">👥</div>
				<h3>Felhasználók</h3>
				<p>Felhasználók kezelése és jogosultságok beállítása</p>
			</a>

			<a href="/admin/stats" class="card">
				<div class="card-icon">📊</div>
				<h3>Statisztikák</h3>
				<p>Rendszer statisztikák és jelentések megtekintése</p>
			</a>

			<a href="/admin/errors" class="card">
				<div class="card-icon">🐛</div>
				<h3>Hibák</h3>
				<p>Frontend hibák megtekintése és elemzése</p>
			</a>
		</div>

		<section class="permissions-section">
			<h3>Az Ön jogosultságai</h3>
			<div class="permissions-list">
				{#each authState.user?.permissions || [] as permission}
					<span class="permission-badge">{permission}</span>
				{/each}
			</div>
		</section>
	</main>
</div>

<style>
	.admin-container {
		background-color: #111827;
		min-height: 100vh;
		color: white;
		font-family:
			system-ui,
			-apple-system,
			sans-serif;
	}

	.header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-bottom: 1px solid #374151;
		background-color: #1f2937;
		padding: 1rem 2rem;
	}

	.header h1 {
		margin: 0;
		color: #f9fafb;
		font-size: 1.25rem;
	}

	.nav {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.nav-link {
		transition: color 0.2s;
		color: #9ca3af;
		font-size: 0.875rem;
		text-decoration: none;
	}

	.nav-link:hover {
		color: white;
	}

	.logout-button {
		transition: all 0.2s;
		cursor: pointer;
		border: 1px solid #6b7280;
		border-radius: 4px;
		background: none;
		padding: 0.5rem 1rem;
		color: #d1d5db;
		font-size: 0.875rem;
	}

	.logout-button:hover {
		border-color: #9ca3af;
		background-color: #374151;
	}

	.main {
		margin: 0 auto;
		padding: 2rem;
		max-width: 1000px;
	}

	.welcome-section {
		margin-bottom: 2rem;
		border-radius: 8px;
		background: linear-gradient(135deg, #3b82f6, #8b5cf6);
		padding: 2rem;
	}

	.welcome-section h2 {
		margin: 0 0 0.5rem;
		font-size: 1.5rem;
	}

	.welcome-section p {
		opacity: 0.9;
		margin: 0;
	}

	.cards-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 1.5rem;
		margin-bottom: 2rem;
	}

	.card {
		transition:
			transform 0.2s,
			box-shadow 0.2s;
		border: 1px solid #374151;
		border-radius: 8px;
		background-color: #1f2937;
		padding: 1.5rem;
		color: white;
		text-decoration: none;
	}

	.card:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
		border-color: #4b5563;
	}

	.card-icon {
		margin-bottom: 0.75rem;
		font-size: 2rem;
	}

	.card h3 {
		margin: 0 0 0.5rem;
		font-size: 1.125rem;
	}

	.card p {
		margin: 0;
		color: #9ca3af;
		font-size: 0.875rem;
	}

	.permissions-section {
		border: 1px solid #374151;
		border-radius: 8px;
		background-color: #1f2937;
		padding: 1.5rem;
	}

	.permissions-section h3 {
		margin: 0 0 1rem;
		color: #d1d5db;
		font-size: 1rem;
	}

	.permissions-list {
		display: flex;
		flex-wrap: wrap;
		gap: 0.5rem;
	}

	.permission-badge {
		border-radius: 9999px;
		background-color: #374151;
		padding: 0.25rem 0.75rem;
		color: #d1d5db;
		font-weight: 500;
		font-size: 0.75rem;
	}
</style>
