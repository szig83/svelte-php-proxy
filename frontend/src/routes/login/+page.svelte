<script lang="ts">
	import { login, getAuthState } from '$lib/auth';
	import { navigateAfterLogin } from '$lib/auth/guard.svelte';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import Logo from '$lib/components/Logo.svelte';
	import Decor from './Decor.svelte';

	let userId = $state('');
	let password = $state('');
	let isSubmitting = $state(false);
	let errorMessage = $state('');

	/**
	 * Meghatározza a bejelentkezés utáni céloldalt a jogosultságok alapján
	 * - Ha van "user" jog: protected rész (/)
	 * - Ha csak "admin" jog van: admin felület (/admin)
	 */
	function getTargetPath(permissions: string[]): string {
		const hasUserPermission = permissions.includes('user');
		const hasAdminPermission = permissions.includes('admin');

		// Ha van user jog (akár admin-nal együtt is), a protected részre megy
		if (hasUserPermission) {
			return '/';
		}

		// Ha csak admin jog van, az admin felületre megy
		if (hasAdminPermission) {
			return '/admin';
		}

		// Fallback - nem kellene ide jutni, mert üres permissions-t már ellenőriztük
		return '/';
	}

	onMount(() => {
		const authState = getAuthState();
		if (authState.isAuthenticated && authState.user) {
			const targetPath = getTargetPath(authState.user.permissions);
			goto(targetPath);
		}
	});

	async function handleSubmit(event: Event) {
		event.preventDefault();
		errorMessage = '';

		if (!userId.trim() || !password.trim()) {
			errorMessage = 'Kérjük, töltse ki az összes mezőt.';
			return;
		}

		isSubmitting = true;

		try {
			const result = await login({ email: userId, password });
			if (result.success && result.user) {
				const targetPath = getTargetPath(result.user.permissions);
				// Ha van redirect paraméter és van user jog, azt használjuk
				if (result.user.permissions.includes('user')) {
					await navigateAfterLogin(targetPath);
				} else {
					// Admin-only: mindig az admin oldalra
					await goto(targetPath);
				}
			} else {
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
	<title>Bejelentkezés - UniBank</title>
</svelte:head>

<div class="flex min-h-screen items-center justify-center bg-gray-100 p-4">
	<div class="flex w-[70%] max-w-[1000px] overflow-hidden rounded-2xl shadow-2xl">
		<!-- Bal oldal - Form -->
		<div class="flex w-full flex-col justify-between bg-white p-8 lg:w-2/5 lg:p-10">
			<!-- Logo -->
			<Logo />

			<!-- Form -->
			<div class="mx-auto mt-8 w-full max-w-sm">
				{#if errorMessage}
					<div
						class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600"
						role="alert"
					>
						{errorMessage}
					</div>
				{/if}

				<form onsubmit={handleSubmit} class="space-y-6">
					<div>
						<label for="userId" class="mb-1 block text-xs text-gray-500">Azonosító</label>
						<input
							type="text"
							id="userId"
							bind:value={userId}
							placeholder="876546532"
							autocomplete="username"
							disabled={isSubmitting}
							required
							class="w-full border-b border-gray-300 bg-transparent py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:outline-none"
						/>
					</div>

					<div>
						<label for="password" class="mb-1 block text-xs text-gray-500">Jelszó</label>
						<input
							type="password"
							id="password"
							bind:value={password}
							placeholder="••••••••••••"
							autocomplete="current-password"
							disabled={isSubmitting}
							required
							class="w-full border-b border-gray-300 bg-transparent py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:outline-none"
						/>
					</div>

					<div class="flex items-center justify-between pt-4">
						<button type="button" class="text-xs text-gray-500 hover:text-blue-600"
							>Problémája van a belépéssel?</button
						>
						<button
							type="submit"
							disabled={isSubmitting}
							class="rounded-full bg-blue-900 px-8 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
						>
							{#if isSubmitting}
								Betöltés...
							{:else}
								Belépés
							{/if}
						</button>
					</div>
				</form>
			</div>

			<!-- Warning -->
			<div class="mt-8">
				<p class="mb-1 text-xs font-semibold text-orange-500">Figyelem!</p>
				<p class="text-xs leading-relaxed text-gray-400">
					Online felületünk nem igényel semmilyen további szoftver telepítését. Javasoljuk, hogy
					védekezzen az ügyfélszolgálattól származónak tűnő rosszindulatú programok ellen.
				</p>
				<button
					class="mt-3 hidden rounded-full border border-gray-300 px-6 py-1.5 text-xs text-gray-500 transition-colors hover:border-gray-400 hover:text-gray-600"
				>
					MORE
				</button>
			</div>
		</div>

		<!-- Jobb oldal - Dekoratív háttér -->
		<div class="relative hidden overflow-hidden lg:block lg:w-3/5">
			<Decor />

			<!-- Szöveg tartalom -->
			<div class="relative z-10 flex h-full flex-col items-center justify-center px-8 text-center">
				<h1 class="mb-4 font-serif text-3xl font-light tracking-wide text-white">
					Üdvözöljünk az <span class="block font-normal text-white">Új Pillér Egészségpénztár</span> online
					felületen
				</h1>
				<p class="text-base text-gray-300">Fiókja eléréshez kérjük jelentkezzen be.</p>
			</div>
		</div>
	</div>
</div>
