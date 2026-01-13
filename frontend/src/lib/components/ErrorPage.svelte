<script lang="ts">
	// Reusable error page content component
	interface Props {
		status: number;
		message?: string;
		homeUrl?: string;
		homeLabel?: string;
	}

	let { status, message, homeUrl = '/', homeLabel = 'Vissza a főoldalra' }: Props = $props();

	const errorTitle = $derived(
		status === 404
			? 'Az oldal nem található'
			: status === 403
				? 'Nincs jogosultságod ehhez az oldalhoz'
				: status === 500
					? 'Szerver hiba történt'
					: 'Hiba történt'
	);

	const errorDescription = $derived(
		status === 404
			? 'A keresett oldal nem létezik vagy el lett távolítva.'
			: message || 'Kérjük, próbáld újra később.'
	);

	// Background text and icon based on error type
	const backgroundText = $derived(status === 404 ? '404' : 'HIBA');
</script>

<div class="space-y-6">
	<div>
		<h1 class="text-2xl font-semibold text-slate-800">Hiba történt</h1>
	</div>

	<div class="relative overflow-hidden rounded-xl bg-white p-12 shadow-sm">
		<div class="relative flex flex-col items-center justify-center py-8">
			<!-- Large background text -->
			<div class="absolute inset-0 flex items-center justify-center overflow-hidden">
				<span
					class="text-[280px] leading-none font-bold text-slate-100 select-none"
					style="letter-spacing: -0.05em;"
				>
					{backgroundText}
				</span>
			</div>

			<!-- Decorative elements -->
			<div class="absolute top-1/4 left-1/4">
				<div class="h-3 w-3 rounded-full border-2 border-red-200"></div>
			</div>
			<div class="absolute top-1/3 right-1/3">
				<div class="h-2 w-2 rounded-full bg-red-300"></div>
			</div>
			<div class="absolute bottom-1/3 left-1/3">
				<div class="h-4 w-4 rotate-45 border-2 border-slate-200"></div>
			</div>
			<div class="absolute right-1/4 bottom-1/4">
				<div class="h-2.5 w-2.5 rotate-12 border-2 border-slate-300"></div>
			</div>

			<!-- Icon based on error type -->
			<div class="relative z-10 mb-6">
				{#if status === 404}
					<!-- Magnifying glass for 404 -->
					<svg
						class="h-48 w-48 text-slate-800"
						fill="none"
						stroke="currentColor"
						viewBox="0 0 24 24"
					>
						<circle cx="11" cy="11" r="8" stroke-width="2" />
						<path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35" />
					</svg>
				{:else}
					<!-- Alert triangle for other errors -->
					<svg
						class="h-48 w-48 text-slate-800"
						fill="none"
						stroke="currentColor"
						viewBox="0 0 24 24"
					>
						<path
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="2"
							d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
						/>
					</svg>
				{/if}
			</div>

			<!-- Error text -->
			<div class="relative z-10 text-center">
				<p class="text-lg font-medium text-slate-700">{errorTitle}</p>
				<p class="mt-2 text-sm text-slate-500">{errorDescription}</p>
			</div>
		</div>

		<!-- Action buttons -->
		<div class="relative z-10 mt-8 flex justify-center gap-3">
			<a
				href={homeUrl}
				class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
			>
				{homeLabel}
			</a>
			<button
				onclick={() => window.history.back()}
				class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
			>
				Vissza az előző oldalra
			</button>
		</div>
	</div>
</div>
