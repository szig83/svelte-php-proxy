<script lang="ts">
	import { onMount } from 'svelte';
	import { api } from '$lib/api';
	import type { ErrorEntry, ErrorType, ErrorSeverity } from '$lib/errors/types';

	interface StoredError extends ErrorEntry {
		receivedAt: string;
	}

	interface ErrorListResponse {
		errors: StoredError[];
		total: number;
		page: number;
		pageSize: number;
	}

	let errors = $state<StoredError[]>([]);
	let selectedError = $state<StoredError | null>(null);
	let isLoading = $state(true);
	let loadError = $state<string | null>(null);
	let total = $state(0);
	let currentPage = $state(1);
	let pageSize = $state(20);

	let filters = $state({
		type: '' as '' | ErrorType,
		dateFrom: '',
		dateTo: ''
	});

	async function loadErrors() {
		isLoading = true;
		loadError = null;

		const params = new URLSearchParams();
		params.set('page', currentPage.toString());
		params.set('pageSize', pageSize.toString());

		if (filters.type) params.set('type', filters.type);
		if (filters.dateFrom) params.set('dateFrom', filters.dateFrom);
		if (filters.dateTo) params.set('dateTo', filters.dateTo);

		const response = await api.get<ErrorListResponse>(`/errors?${params.toString()}`);

		if (response.success && response.data) {
			errors = response.data.errors;
			total = response.data.total;
			currentPage = response.data.page;
		} else {
			loadError = response.error?.message || 'Hiba történt a hibák betöltésekor';
			errors = [];
		}

		isLoading = false;
	}

	function selectError(error: StoredError) {
		selectedError = error;
	}

	function clearSelection() {
		selectedError = null;
	}

	function applyFilters() {
		currentPage = 1;
		loadErrors();
	}

	function clearFilters() {
		filters = { type: '', dateFrom: '', dateTo: '' };
		currentPage = 1;
		loadErrors();
	}

	function goToPage(page: number) {
		currentPage = page;
		loadErrors();
	}

	function formatDate(isoDate: string): string {
		try {
			return new Date(isoDate).toLocaleString('hu-HU');
		} catch {
			return isoDate;
		}
	}

	function getSeverityColor(severity: ErrorSeverity): string {
		switch (severity) {
			case 'error':
				return 'bg-red-100 text-red-700';
			case 'warning':
				return 'bg-amber-100 text-amber-700';
			case 'info':
				return 'bg-blue-100 text-blue-700';
			default:
				return 'bg-slate-100 text-slate-700';
		}
	}

	function getTypeColor(type: ErrorType): string {
		switch (type) {
			case 'javascript':
				return 'bg-purple-100 text-purple-700';
			case 'api':
				return 'bg-emerald-100 text-emerald-700';
			case 'manual':
				return 'bg-indigo-100 text-indigo-700';
			case 'php':
				return 'bg-pink-100 text-pink-700';
			default:
				return 'bg-slate-100 text-slate-700';
		}
	}

	onMount(() => {
		loadErrors();
	});

	const totalPages = $derived(Math.ceil(total / pageSize));
	const hasNextPage = $derived(currentPage < totalPages);
	const hasPrevPage = $derived(currentPage > 1);
</script>

<svelte:head>
	<title>Admin - Hibák</title>
</svelte:head>

<div class="space-y-6">
	<section>
		<h1 class="mb-1 text-2xl font-bold text-slate-800">Hiba Nézegető</h1>
		<p class="text-slate-500">Frontend hibák megtekintése és elemzése</p>
	</section>

	<!-- Filters -->
	<section class="rounded-xl bg-white p-4 shadow-sm">
		<h2 class="mb-3 text-sm font-semibold text-slate-700">Szűrők</h2>
		<div class="flex flex-wrap items-end gap-4">
			<div class="flex flex-col gap-1">
				<label for="type-filter" class="text-xs font-medium text-slate-500">Típus</label>
				<select
					id="type-filter"
					bind:value={filters.type}
					class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-purple-500 focus:outline-none"
				>
					<option value="">Összes</option>
					<option value="javascript">JavaScript</option>
					<option value="api">API</option>
					<option value="manual">Manuális</option>
					<option value="php">PHP (Backend)</option>
				</select>
			</div>

			<div class="flex flex-col gap-1">
				<label for="date-from" class="text-xs font-medium text-slate-500">Dátum kezdete</label>
				<input
					type="date"
					id="date-from"
					bind:value={filters.dateFrom}
					class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-purple-500 focus:outline-none"
				/>
			</div>

			<div class="flex flex-col gap-1">
				<label for="date-to" class="text-xs font-medium text-slate-500">Dátum vége</label>
				<input
					type="date"
					id="date-to"
					bind:value={filters.dateTo}
					class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-purple-500 focus:outline-none"
				/>
			</div>

			<div class="flex gap-2">
				<button
					onclick={applyFilters}
					class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700"
					>Szűrés</button
				>
				<button
					onclick={clearFilters}
					class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
					>Törlés</button
				>
			</div>
		</div>
	</section>

	<div class="grid gap-6 lg:grid-cols-2">
		<!-- Error List -->
		<section class="rounded-xl bg-white p-4 shadow-sm">
			<h2 class="mb-3 text-sm font-semibold text-slate-700">Hibák ({total} db)</h2>

			{#if isLoading}
				<div class="py-8 text-center text-slate-500">Betöltés...</div>
			{:else if loadError}
				<div class="py-8 text-center text-red-500">{loadError}</div>
			{:else if errors.length === 0}
				<div class="py-8 text-center text-slate-500">Nincs találat a megadott szűrőkkel.</div>
			{:else}
				<ul class="max-h-[500px] space-y-2 overflow-y-auto">
					{#each errors as error (error.id)}
						<li>
							<button
								class="w-full rounded-lg border p-3 text-left transition-colors {selectedError?.id ===
								error.id
									? 'border-purple-500 bg-purple-50'
									: 'border-slate-200 hover:bg-slate-50'}"
								onclick={() => selectError(error)}
							>
								<div class="mb-2 flex items-center gap-2">
									<span
										class="rounded-full px-2 py-0.5 text-xs font-medium {getSeverityColor(
											error.severity
										)}">{error.severity}</span
									>
									<span
										class="rounded-full px-2 py-0.5 text-xs font-medium {getTypeColor(error.type)}"
										>{error.type}</span
									>
									<span class="ml-auto text-xs text-slate-400">{formatDate(error.timestamp)}</span>
								</div>
								<p class="text-sm text-slate-700">
									{error.message.length > 100
										? error.message.substring(0, 100) + '...'
										: error.message}
								</p>
							</button>
						</li>
					{/each}
				</ul>

				{#if totalPages > 1}
					<div class="mt-4 flex items-center justify-center gap-4 border-t border-slate-200 pt-4">
						<button
							class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 disabled:opacity-50"
							disabled={!hasPrevPage}
							onclick={() => goToPage(currentPage - 1)}>Előző</button
						>
						<span class="text-sm text-slate-500">{currentPage} / {totalPages}</span>
						<button
							class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 disabled:opacity-50"
							disabled={!hasNextPage}
							onclick={() => goToPage(currentPage + 1)}>Következő</button
						>
					</div>
				{/if}
			{/if}
		</section>

		<!-- Error Details -->
		<section class="rounded-xl bg-white p-4 shadow-sm">
			<h2 class="mb-3 text-sm font-semibold text-slate-700">Részletek</h2>

			{#if selectedError}
				<div class="relative">
					<button
						onclick={clearSelection}
						class="absolute top-0 right-0 text-slate-400 hover:text-slate-600">✕</button
					>

					<div class="space-y-4">
						<div>
							<p class="text-xs font-medium text-slate-500">ID</p>
							<code class="text-sm text-slate-700">{selectedError.id}</code>
						</div>

						<div class="flex gap-4">
							<div>
								<p class="text-xs font-medium text-slate-500">Típus</p>
								<span
									class="rounded-full px-2 py-0.5 text-xs font-medium {getTypeColor(
										selectedError.type
									)}">{selectedError.type}</span
								>
							</div>
							<div>
								<p class="text-xs font-medium text-slate-500">Súlyosság</p>
								<span
									class="rounded-full px-2 py-0.5 text-xs font-medium {getSeverityColor(
										selectedError.severity
									)}">{selectedError.severity}</span
								>
							</div>
						</div>

						<div>
							<p class="text-xs font-medium text-slate-500">Időpont</p>
							<p class="text-sm text-slate-700">{formatDate(selectedError.timestamp)}</p>
						</div>

						<div>
							<p class="text-xs font-medium text-slate-500">Üzenet</p>
							<p class="text-sm text-slate-700">{selectedError.message}</p>
						</div>

						{#if selectedError.stack}
							<div>
								<p class="text-xs font-medium text-slate-500">Stack Trace</p>
								<pre
									class="max-h-48 overflow-auto rounded-lg bg-slate-800 p-3 text-xs text-red-300">{selectedError.stack}</pre>
							</div>
						{/if}

						<div>
							<p class="text-xs font-medium text-slate-500">Kontextus</p>
							<div class="rounded-lg bg-slate-50 p-3 text-sm">
								<p><span class="text-slate-500">URL:</span> {selectedError.context.url}</p>
								<p class="truncate">
									<span class="text-slate-500">User Agent:</span>
									{selectedError.context.userAgent}
								</p>
								{#if selectedError.context.userId}
									<p>
										<span class="text-slate-500">Felhasználó ID:</span>
										{selectedError.context.userId}
									</p>
								{/if}
							</div>
						</div>
					</div>
				</div>
			{:else}
				<div class="py-8 text-center text-slate-500">
					Válasszon ki egy hibát a részletek megtekintéséhez.
				</div>
			{/if}
		</section>
	</div>
</div>
