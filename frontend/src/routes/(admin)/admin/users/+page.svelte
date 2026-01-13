<script lang="ts">
	// (admin)/admin/users/+page.svelte
	// Admin - Felhasználók kezelése oldal
	// Követelmények: 5.3, 6.4

	// Placeholder user data for demonstration
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

<div class="flex h-full flex-col space-y-6">
	<section class="page-header">
		<h1 class="text-admin-text-primary mb-1 text-2xl font-bold">Felhasználók</h1>
		<p class="text-admin-text-secondary">Felhasználók listája és kezelése</p>
	</section>

	<section class="users-section flex-1">
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
			<strong>Megjegyzés:</strong> Ez egy példa oldal. A valós implementációban az adatok a külső API-ból
			érkeznének.
		</p>
	</section>
</div>

<style>
	.page-header {
		margin-bottom: 0;
	}

	.users-section {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		background-color: rgba(31, 41, 55, 0.5);
		overflow: hidden;
	}

	.table-container {
		overflow-x: auto;
	}

	.users-table {
		border-collapse: collapse;
		width: 100%;
	}

	.users-table th,
	.users-table td {
		border-bottom: 1px solid rgba(255, 255, 255, 0.1);
		padding: 1rem;
		text-align: left;
	}

	.users-table th {
		background-color: rgba(55, 65, 81, 0.5);
		color: #d1d5db;
		font-weight: 500;
		font-size: 0.875rem;
	}

	.users-table td {
		color: #e5e7eb;
		font-size: 0.875rem;
	}

	.users-table tbody tr:hover {
		background-color: rgba(55, 65, 81, 0.5);
	}

	.permissions-list {
		display: flex;
		flex-wrap: wrap;
		gap: 0.25rem;
	}

	.permission-badge {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 9999px;
		background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(139, 92, 246, 0.2));
		padding: 0.125rem 0.5rem;
		color: #d1d5db;
		font-size: 0.75rem;
	}

	.action-button {
		transition: all 0.2s;
		cursor: pointer;
		border: none;
		border-radius: 6px;
		background: linear-gradient(135deg, #00d4ff, #8b5cf6);
		padding: 0.375rem 0.75rem;
		color: white;
		font-size: 0.75rem;
	}

	.action-button:hover {
		transform: scale(1.05);
		box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
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
