<script lang="ts">
	// +page.svelte
	// Főoldal (publikus)
	// Követelmények: 5.3

	import { getAuthState, logout } from '$lib/auth';
	import { goto } from '$app/navigation';

	const authState = getAuthState();

	async function handleLogout() {
		await logout();
		goto('/login');
	}
</script>

<svelte:head>
	<title>Főoldal</title>
</svelte:head>

<div class="home-container">
	<header class="header">
		<h1>Svelte + PHP Auth Rendszer</h1>
		<nav class="nav">
			{#if authState.isAuthenticated}
				<span class="user-info">Üdv, {authState.user?.name}!</span>
				<a href="/dashboard" class="nav-link">Dashboard</a>
				{#if authState.user?.permissions.includes('admin')}
					<a href="/admin" class="nav-link">Admin</a>
				{/if}
				<button onclick={handleLogout} class="logout-button">Kijelentkezés</button>
			{:else}
				<a href="/login" class="nav-link login-link">Bejelentkezés</a>
			{/if}
		</nav>
	</header>

	<main class="main">
		<section class="hero">
			<h2>Üdvözöljük!</h2>
			<p>Ez egy biztonságos webalkalmazás Svelte 5 frontenddel és PHP proxy backenddel.</p>

			{#if !authState.isAuthenticated}
				<a href="/login" class="cta-button">Bejelentkezés</a>
			{:else}
				<a href="/dashboard" class="cta-button">Dashboard megnyitása</a>
			{/if}
		</section>

		<section class="features">
			<h3>Funkciók</h3>
			<ul>
				<li>JWT alapú autentikáció</li>
				<li>Automatikus token megújítás</li>
				<li>Jogosultság-alapú hozzáférés-vezérlés</li>
				<li>CSRF védelem</li>
				<li>Biztonságos session kezelés</li>
			</ul>
		</section>
	</main>
</div>

<style>
	.home-container {
		min-height: 100vh;
		font-family: system-ui, -apple-system, sans-serif;
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

	.user-info {
		color: #9ca3af;
		font-size: 0.875rem;
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

	.login-link {
		background-color: #3b82f6;
		color: white;
		padding: 0.5rem 1rem;
		border-radius: 4px;
	}

	.login-link:hover {
		background-color: #2563eb;
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

	.hero {
		text-align: center;
		padding: 3rem 0;
	}

	.hero h2 {
		font-size: 2rem;
		color: #1f2937;
		margin-bottom: 1rem;
	}

	.hero p {
		color: #6b7280;
		font-size: 1.125rem;
		margin-bottom: 2rem;
	}

	.cta-button {
		display: inline-block;
		background-color: #3b82f6;
		color: white;
		padding: 0.75rem 1.5rem;
		border-radius: 6px;
		text-decoration: none;
		font-weight: 500;
		transition: background-color 0.2s;
	}

	.cta-button:hover {
		background-color: #2563eb;
	}

	.features {
		background-color: #f9fafb;
		padding: 2rem;
		border-radius: 8px;
	}

	.features h3 {
		color: #1f2937;
		margin-bottom: 1rem;
	}

	.features ul {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.features li {
		padding: 0.5rem 0;
		color: #4b5563;
		position: relative;
		padding-left: 1.5rem;
	}

	.features li::before {
		content: '✓';
		position: absolute;
		left: 0;
		color: #10b981;
	}
</style>
