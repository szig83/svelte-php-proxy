<script lang="ts">
	interface Props {
		title: string;
		description?: string;
	}

	let { title, description = 'Ez az oldal fejlesztés alatt áll.' }: Props = $props();
</script>

<div class="space-y-6">
	<div>
		<h1 class="text-2xl font-semibold text-slate-800">{title}</h1>
		<p class="mt-1 text-sm text-slate-500">{description}</p>
	</div>

	<div class="flex flex-col items-center justify-center rounded-xl bg-white p-12 shadow-sm">
		<div class="rounded-full bg-slate-100 p-4">
			<svg class="h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path
					stroke-linecap="round"
					stroke-linejoin="round"
					stroke-width="1.5"
					d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
				/>
			</svg>
		</div>
		<h2 class="mt-4 text-lg font-medium text-slate-700">Hamarosan elérhető</h2>
		<p class="mt-2 text-center text-sm text-slate-500">
			Ez a funkció jelenleg fejlesztés alatt áll.<br />
			Kérjük, látogasson vissza később.
		</p>
	</div>
</div>
