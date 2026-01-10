<script lang="ts">
	// (admin)/admin/users/+page.svelte
	// Admin - Felhasználók kezelése oldal
	// Követelmények: 5.3, 6.4

	import { getAuthState, logout } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}

	// Placeholder user data for demonstration
	const users = [
		{ id: '1', name: 'Admin User', email: 'admin@example.com', permissions: ['admin', 'read', 'write'] },
		{ id: '2', name: 'Regular User', email: 'user@example.com', permissions: ['read'] },
		{ id: '3', name: 'Editor', email: 'editor@example.com', permissions: ['read', 'write'] }
	];
</script>

<svelte:head>
	<title>Admin - Felhasználók</title>
</svelte:head>

<div class="admin-container">
	<header class="header">
		<h1>Felhasználók kezelése</h1>
		<nav class="nav">
			<a href="/" class="nav-link">Főoldal</a>
			<a href="/dashboard" class="nav-link">Dashboard</a>
			<a href="/admin" class="nav-link">Admin</a>
			<button onclick={handleLogout} class="logout-button">Kijelentkezés</button>
		</nav>
	</header>

	<main class="main">
		<section class="page-header">
			<h2>Felhasználók</h2>
			<p>Felhasználók listája és kezelése</p>
		</section>

		<section class="users-section">
			<div class="table-container">
				<table class="users-table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Név</th>
							<th>E-mail</th>
							<th>Jogosultságok</th>
							<th>Műveletek</th>
						</tr>
					</thead>
					<tbody>
						{#each users as user}
							<tr>
								<td>{user.id}</td>
								<td>{user.name}</td>
								<td>{user.email}</td>
								<td>
									<div class="permissions-list">
										{#each user.permissions as permission}
											<span class="permission-badge">{permission}</span>
										{/each}
									</div>
								</td>
								<td>
									<button class="action-button">Szerkesztés</button>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</section>

		<section class="info-section">
			<p>
				<strong>Megjegyzés:</strong> Ez egy példa oldal. A valós implementációban az adatok
				a külső API-ból érkeznének.
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

	.users-section {
		background-color: #1f2937;
		border-radius: 8px;
		border: 1px solid #374151;
		overflow: hidden;
		margin-bottom: 1.5rem;
	}

	.table-container {
		overflow-x: auto;
	}

	.users-table {
		width: 100%;
		border-collapse: collapse;
	}

	.users-table th,
	.users-table td {
		padding: 1rem;
		text-align: left;
		border-bottom: 1px solid #374151;
	}

	.users-table th {
		background-color: #374151;
		font-weight: 500;
		font-size: 0.875rem;
		color: #d1d5db;
	}

	.users-table td {
		font-size: 0.875rem;
		color: #e5e7eb;
	}

	.users-table tbody tr:hover {
		background-color: #374151;
	}

	.permissions-list {
		display: flex;
		flex-wrap: wrap;
		gap: 0.25rem;
	}

	.permission-badge {
		background-color: #4b5563;
		color: #d1d5db;
		padding: 0.125rem 0.5rem;
		border-radius: 9999px;
		font-size: 0.75rem;
	}

	.action-button {
		background-color: #3b82f6;
		color: white;
		border: none;
		padding: 0.375rem 0.75rem;
		border-radius: 4px;
		font-size: 0.75rem;
		cursor: pointer;
		transition: background-color 0.2s;
	}

	.action-button:hover {
		background-color: #2563eb;
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
