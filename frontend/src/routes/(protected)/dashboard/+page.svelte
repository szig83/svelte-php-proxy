<script lang="ts">
	// (protected)/dashboard/+page.svelte
	// Dashboard oldal (védett)
	// Követelmények: 5.3

	import { getAuthState, logout } from '$lib/auth';
	import { PermissionGate } from '$lib/components';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}
</script>

<svelte:head>
	<title>Dashboard</title>
</svelte:head>

<div class="dashboard-container">
	<header class="header">
		<h1>Dashboard</h1>
		<nav class="nav">
			<a href="/" class="nav-link">Főoldal</a>
			<PermissionGate permission="admin">
				<a href="/admin" class="nav-link">Admin</a>
			</PermissionGate>
			<button onclick={handleLogout} class="logout-button">Kijelentkezés</button>
		</nav>
	</header>

	<main class="main">
		<section class="welcome-section">
			<h2>Üdvözöljük, {authState.user?.name}!</h2>
			<p>Ez a védett dashboard oldal. Csak bejelentkezett felhasználók láthatják.</p>
		</section>

		<section class="user-info-section">
			<h3>Felhasználói adatok</h3>
			<div class="info-card">
				<div class="info-row">
					<span class="label">ID:</span>
					<span class="value">{authState.user?.id}</span>
				</div>
				<div class="info-row">
					<span class="label">E-mail:</span>
					<span class="value">{authState.user?.email}</span>
				</div>
				<div class="info-row">
					<span class="label">Név:</span>
					<span class="value">{authState.user?.name}</span>
				</div>
				<div class="info-row">
					<span class="label">Jogosultságok:</span>
					<span class="value">
						{#if authState.user?.permissions.length}
							{authState.user.permissions.join(', ')}
						{:else}
							Nincs
						{/if}
					</span>
				</div>
			</div>
		</section>

		<PermissionGate permission="admin">
			<section class="admin-notice">
				<h3>Admin hozzáférés</h3>
				<p>Admin jogosultsággal rendelkezik. <a href="/admin">Ugrás az admin felületre →</a></p>
			</section>
		</PermissionGate>
	</main>
</div>

<style>
	.dashboard-container {
		min-height: 100vh;
		font-family: system-ui, -apple-system, sans-serif;
		background-color: #f3f4f6;
	}

	.header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1rem 2rem;
		background-color: #1f2937;
		color: white;
	}

	.header h1 {
		margin: 0;
		font-size: 1.25rem;
	}

	.nav {
		display: flex;
		align-items: center;
		gap: 1rem;
	}

	.nav-link {
		color: #d1d5db;
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
		max-width: 800px;
		margin: 0 auto;
		padding: 2rem;
	}

	.welcome-section {
		background: white;
		padding: 2rem;
		border-radius: 8px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		margin-bottom: 1.5rem;
	}

	.welcome-section h2 {
		margin: 0 0 0.5rem;
		color: #1f2937;
	}

	.welcome-section p {
		margin: 0;
		color: #6b7280;
	}

	.user-info-section {
		background: white;
		padding: 2rem;
		border-radius: 8px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		margin-bottom: 1.5rem;
	}

	.user-info-section h3 {
		margin: 0 0 1rem;
		color: #1f2937;
	}

	.info-card {
		background-color: #f9fafb;
		padding: 1rem;
		border-radius: 6px;
	}

	.info-row {
		display: flex;
		padding: 0.5rem 0;
		border-bottom: 1px solid #e5e7eb;
	}

	.info-row:last-child {
		border-bottom: none;
	}

	.label {
		font-weight: 500;
		color: #374151;
		width: 120px;
		flex-shrink: 0;
	}

	.value {
		color: #6b7280;
	}

	.admin-notice {
		background: linear-gradient(135deg, #3b82f6, #8b5cf6);
		padding: 1.5rem 2rem;
		border-radius: 8px;
		color: white;
	}

	.admin-notice h3 {
		margin: 0 0 0.5rem;
	}

	.admin-notice p {
		margin: 0;
		opacity: 0.9;
	}

	.admin-notice a {
		color: white;
		font-weight: 500;
	}
</style>
