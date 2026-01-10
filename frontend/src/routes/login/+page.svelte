<script lang="ts">
	// login/+page.svelte
	// Bejelentkezési oldal
	// Követelmények: 3.1

	import { login, getAuthState } from '$lib/auth';
	import { navigateAfterLogin } from '$lib/auth/guard';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';

	// Form state
	let email = $state('');
	let password = $state('');
	let isSubmitting = $state(false);
	let errorMessage = $state('');

	// Redirect if already authenticated
	onMount(() => {
		const authState = getAuthState();
		if (authState.isAuthenticated) {
			navigateAfterLogin('/');
		}
	});

	async function handleSubmit(event: Event) {
		event.preventDefault();

		// Reset error
		errorMessage = '';

		// Validate inputs
		if (!email.trim() || !password.trim()) {
			errorMessage = 'Kérjük, töltse ki az összes mezőt.';
			return;
		}

		isSubmitting = true;

		try {
			const result = await login({ email, password });

			if (result.success) {
				// Navigate to redirect URL or home
				await navigateAfterLogin('/');
			} else {
				// Show error message
				errorMessage = result.error?.message || 'Bejelentkezés sikertelen.';
			}
		} catch (error) {
			errorMessage = 'Hálózati hiba történt. Kérjük, próbálja újra.';
		} finally {
			isSubmitting = false;
		}
	}
</script>

<svelte:head>
	<title>Bejelentkezés</title>
</svelte:head>

<div class="login-container">
	<div class="login-card">
		<h1>Bejelentkezés</h1>

		{#if errorMessage}
			<div class="error-message" role="alert">
				{errorMessage}
			</div>
		{/if}

		<form onsubmit={handleSubmit}>
			<div class="form-group">
				<label for="email">E-mail cím</label>
				<input
					type="email"
					id="email"
					bind:value={email}
					placeholder="pelda@email.com"
					autocomplete="email"
					disabled={isSubmitting}
					required
				/>
			</div>

			<div class="form-group">
				<label for="password">Jelszó</label>
				<input
					type="password"
					id="password"
					bind:value={password}
					placeholder="••••••••"
					autocomplete="current-password"
					disabled={isSubmitting}
					required
				/>
			</div>

			<button type="submit" class="submit-button" disabled={isSubmitting}>
				{#if isSubmitting}
					Bejelentkezés...
				{:else}
					Bejelentkezés
				{/if}
			</button>
		</form>
	</div>
</div>

<style>
	.login-container {
		display: flex;
		justify-content: center;
		align-items: center;
		min-height: 100vh;
		padding: 1rem;
		background-color: #f5f5f5;
		font-family: system-ui, -apple-system, sans-serif;
	}

	.login-card {
		background: white;
		padding: 2rem;
		border-radius: 8px;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		width: 100%;
		max-width: 400px;
	}

	h1 {
		margin: 0 0 1.5rem;
		font-size: 1.5rem;
		text-align: center;
		color: #333;
	}

	.error-message {
		background-color: #fee2e2;
		border: 1px solid #ef4444;
		color: #dc2626;
		padding: 0.75rem;
		border-radius: 4px;
		margin-bottom: 1rem;
		font-size: 0.875rem;
	}

	.form-group {
		margin-bottom: 1rem;
	}

	label {
		display: block;
		margin-bottom: 0.5rem;
		font-weight: 500;
		color: #374151;
		font-size: 0.875rem;
	}

	input {
		width: 100%;
		padding: 0.75rem;
		border: 1px solid #d1d5db;
		border-radius: 4px;
		font-size: 1rem;
		transition: border-color 0.2s, box-shadow 0.2s;
		box-sizing: border-box;
	}

	input:focus {
		outline: none;
		border-color: #3b82f6;
		box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
	}

	input:disabled {
		background-color: #f3f4f6;
		cursor: not-allowed;
	}

	.submit-button {
		width: 100%;
		padding: 0.75rem;
		background-color: #3b82f6;
		color: white;
		border: none;
		border-radius: 4px;
		font-size: 1rem;
		font-weight: 500;
		cursor: pointer;
		transition: background-color 0.2s;
		margin-top: 0.5rem;
	}

	.submit-button:hover:not(:disabled) {
		background-color: #2563eb;
	}

	.submit-button:disabled {
		background-color: #9ca3af;
		cursor: not-allowed;
	}
</style>
