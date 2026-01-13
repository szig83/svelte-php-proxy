<script lang="ts">
	// (admin)/admin/errors/+page.svelte
	// Hiba nézegető admin felület
	// Követelmények: 6.1, 6.2, 6.3, 6.4, 6.5

	import { onMount } from 'svelte';
	import { api } from '$lib/api';
	import type { ErrorEntry, ErrorType, ErrorSeverity } from '$lib/errors/types';

	/**
	 * Tárolt hiba típus (backend válasz)
	 */
	interface StoredError extends ErrorEntry {
		receivedAt: string;
	}

	/**
	 * Hibák lista válasz
	 */
	interface ErrorListResponse {
		errors: StoredError[];
		total: number;
		page: number;
		pageSize: number;
	}

	// Állapot
	let errors = $state<StoredError[]>([]);
	let selectedError = $state<StoredError | null>(null);
	let isLoading = $state(true);
	let loadError = $state<string | null>(null);
	let total = $state(0);
	let currentPage = $state(1);
	let pageSize = $state(20);

	// Szűrők
	let filters = $state({
		type: '' as '' | ErrorType,
		dateFrom: '',
		dateTo: ''
	});

	/**
	 * Hibák betöltése a backendről
	 * Követelmény: 6.1
	 */
	async function loadErrors() {
		isLoading = true;
		loadError = null;

		// Query paraméterek összeállítása
		const params = new URLSearchParams();
		params.set('page', currentPage.toString());
		params.set('pageSize', pageSize.toString());

		if (filters.type) {
			params.set('type', filters.type);
		}
		if (filters.dateFrom) {
			params.set('dateFrom', filters.dateFrom);
		}
		if (filters.dateTo) {
			params.set('dateTo', filters.dateTo);
		}

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

	/**
	 * Egy hiba részleteinek betöltése
	 * Követelmény: 6.3
	 */
	async function loadErrorDetails(id: string) {
		const response = await api.get<StoredError>(`/errors/${id}`);

		if (response.success && response.data) {
			selectedError = response.data;
		}
	}

	/**
	 * Hiba kiválasztása
	 */
	function selectError(error: StoredError) {
		selectedError = error;
	}

	/**
	 * Kiválasztás törlése
	 */
	function clearSelection() {
		selectedError = null;
	}

	/**
	 * Szűrők alkalmazása
	 * Követelmények: 6.4, 6.5
	 */
	function applyFilters() {
		currentPage = 1;
		loadErrors();
	}

	/**
	 * Szűrők törlése
	 */
	function clearFilters() {
		filters = {
			type: '',
			dateFrom: '',
			dateTo: ''
		};
		currentPage = 1;
		loadErrors();
	}

	/**
	 * Lapozás
	 */
	function goToPage(page: number) {
		currentPage = page;
		loadErrors();
	}

	/**
	 * Dátum formázása megjelenítéshez
	 */
	function formatDate(isoDate: string): string {
		try {
			const date = new Date(isoDate);
			return date.toLocaleString('hu-HU', {
				year: 'numeric',
				month: '2-digit',
				day: '2-digit',
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit'
			});
		} catch {
			return isoDate;
		}
	}

	/**
	 * Súlyosság badge színe
	 */
	function getSeverityColor(severity: ErrorSeverity): string {
		switch (severity) {
			case 'error':
				return '#ef4444';
			case 'warning':
				return '#f59e0b';
			case 'info':
				return '#3b82f6';
			default:
				return '#6b7280';
		}
	}

	/**
	 * Típus badge színe
	 */
	function getTypeColor(type: ErrorType): string {
		switch (type) {
			case 'javascript':
				return '#8b5cf6';
			case 'api':
				return '#10b981';
			case 'manual':
				return '#6366f1';
			case 'php':
				return '#ec4899';
			default:
				return '#6b7280';
		}
	}

	// Oldal betöltésekor hibák lekérése
	onMount(() => {
		loadErrors();
	});

	// Számított értékek
	const totalPages = $derived(Math.ceil(total / pageSize));
	const hasNextPage = $derived(currentPage < totalPages);
	const hasPrevPage = $derived(currentPage > 1);
</script>

<svelte:head>
	<title>Admin - Hibák</title>
</svelte:head>

<div class="flex h-full flex-col space-y-4">
	<section class="page-header">
		<h1 class="text-admin-text-primary mb-1 text-2xl font-bold">Hiba Nézegető</h1>
		<p class="text-admin-text-secondary">Frontend hibák megtekintése és elemzése</p>
	</section>
	<!-- Szűrők -->
	<section class="filters-section">
		<h2>Szűrők</h2>
		<div class="filters-grid">
			<div class="filter-group">
				<label for="type-filter">Típus</label>
				<select id="type-filter" bind:value={filters.type}>
					<option value="">Összes</option>
					<option value="javascript">JavaScript</option>
					<option value="api">API</option>
					<option value="manual">Manuális</option>
					<option value="php">PHP (Backend)</option>
				</select>
			</div>

			<div class="filter-group">
				<label for="date-from">Dátum kezdete</label>
				<input type="date" id="date-from" bind:value={filters.dateFrom} />
			</div>

			<div class="filter-group">
				<label for="date-to">Dátum vége</label>
				<input type="date" id="date-to" bind:value={filters.dateTo} />
			</div>

			<div class="filter-actions">
				<button class="btn btn-primary" onclick={applyFilters}>Szűrés</button>
				<button class="btn btn-secondary" onclick={clearFilters}>Törlés</button>
			</div>
		</div>
	</section>

	<div class="content-grid">
		<!-- Hiba lista -->
		<section class="errors-list-section">
			<h2>Hibák ({total} db)</h2>

			{#if isLoading}
				<div class="loading">Betöltés...</div>
			{:else if loadError}
				<div class="error-message">{loadError}</div>
			{:else if errors.length === 0}
				<div class="empty-message">Nincs találat a megadott szűrőkkel.</div>
			{:else}
				<ul class="errors-list">
					{#each errors as error (error.id)}
						<li>
							<button
								class="error-item"
								class:selected={selectedError?.id === error.id}
								onclick={() => selectError(error)}
							>
								<div class="error-item-header">
									<span
										class="badge severity-badge"
										style="background-color: {getSeverityColor(error.severity)}"
									>
										{error.severity}
									</span>
									<span
										class="badge type-badge"
										style="background-color: {getTypeColor(error.type)}"
									>
										{error.type}
									</span>
									<span class="error-time">{formatDate(error.timestamp)}</span>
								</div>
								<div class="error-message-preview">
									{error.message.length > 100
										? error.message.substring(0, 100) + '...'
										: error.message}
								</div>
							</button>
						</li>
					{/each}
				</ul>

				<!-- Lapozás -->
				{#if totalPages > 1}
					<div class="pagination">
						<button
							class="btn btn-small"
							disabled={!hasPrevPage}
							onclick={() => goToPage(currentPage - 1)}
						>
							Előző
						</button>
						<span class="page-info">{currentPage} / {totalPages}</span>
						<button
							class="btn btn-small"
							disabled={!hasNextPage}
							onclick={() => goToPage(currentPage + 1)}
						>
							Következő
						</button>
					</div>
				{/if}
			{/if}
		</section>

		<!-- Hiba részletek -->
		<section class="error-details-section">
			<h2>Részletek</h2>

			{#if selectedError}
				<div class="error-details">
					<button class="close-btn" onclick={clearSelection}>✕</button>

					<div class="detail-group">
						<span class="detail-label">ID</span>
						<code>{selectedError.id}</code>
					</div>

					<div class="detail-group">
						<span class="detail-label">Típus</span>
						<span class="badge" style="background-color: {getTypeColor(selectedError.type)}">
							{selectedError.type}
						</span>
					</div>

					<div class="detail-group">
						<span class="detail-label">Súlyosság</span>
						<span
							class="badge"
							style="background-color: {getSeverityColor(selectedError.severity)}"
						>
							{selectedError.severity}
						</span>
					</div>

					<div class="detail-group">
						<span class="detail-label">Időpont</span>
						<span>{formatDate(selectedError.timestamp)}</span>
					</div>

					<div class="detail-group">
						<span class="detail-label">Fogadva</span>
						<span>{formatDate(selectedError.receivedAt)}</span>
					</div>

					<div class="detail-group">
						<span class="detail-label">Üzenet</span>
						<p class="error-full-message">{selectedError.message}</p>
					</div>

					{#if selectedError.stack}
						<div class="detail-group">
							<span class="detail-label">Stack Trace</span>
							<pre class="stack-trace">{selectedError.stack}</pre>
						</div>
					{/if}

					<div class="detail-group">
						<span class="detail-label">Kontextus</span>
						<div class="context-details">
							<div class="context-item">
								<span class="context-label">URL:</span>
								<span>{selectedError.context.url}</span>
							</div>
							<div class="context-item">
								<span class="context-label">User Agent:</span>
								<span class="user-agent">{selectedError.context.userAgent}</span>
							</div>
							{#if selectedError.context.userId}
								<div class="context-item">
									<span class="context-label">Felhasználó ID:</span>
									<span>{selectedError.context.userId}</span>
								</div>
							{/if}
							{#if selectedError.context.appVersion}
								<div class="context-item">
									<span class="context-label">App verzió:</span>
									<span>{selectedError.context.appVersion}</span>
								</div>
							{/if}
							{#if selectedError.context.extra && Object.keys(selectedError.context.extra).length > 0}
								<div class="context-item">
									<span class="context-label">Extra:</span>
									<pre class="extra-context">{JSON.stringify(
											selectedError.context.extra,
											null,
											2
										)}</pre>
								</div>
							{/if}
						</div>
					</div>
				</div>
			{:else}
				<div class="no-selection">Válasszon ki egy hibát a részletek megtekintéséhez.</div>
			{/if}
		</section>
	</div>
</div>

<style>
	.page-header {
		margin-bottom: 0;
	}

	/* Szűrők */
	.filters-section {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		background-color: rgba(31, 41, 55, 0.5);
		padding: 1.25rem;
	}

	.filters-section h2 {
		margin: 0 0 1rem;
		color: #d1d5db;
		font-size: 1rem;
	}

	.filters-grid {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-end;
		gap: 1rem;
	}

	.filter-group {
		display: flex;
		flex-direction: column;
		gap: 0.375rem;
	}

	.filter-group label {
		color: #9ca3af;
		font-size: 0.75rem;
		letter-spacing: 0.05em;
		text-transform: uppercase;
	}

	.filter-group select,
	.filter-group input {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 6px;
		background-color: rgba(55, 65, 81, 0.5);
		padding: 0.5rem 0.75rem;
		min-width: 150px;
		color: white;
		font-size: 0.875rem;
	}

	.filter-group select:focus,
	.filter-group input:focus {
		outline: none;
		border-color: #00d4ff;
	}

	.filter-actions {
		display: flex;
		gap: 0.5rem;
	}

	/* Gombok */
	.btn {
		transition: all 0.2s;
		cursor: pointer;
		border: none;
		border-radius: 6px;
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
	}

	.btn-primary {
		background: linear-gradient(135deg, #00d4ff, #8b5cf6);
		color: white;
	}

	.btn-primary:hover {
		transform: scale(1.05);
		box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
	}

	.btn-secondary {
		border: 1px solid rgba(255, 255, 255, 0.1);
		background-color: rgba(55, 65, 81, 0.5);
		color: #d1d5db;
	}

	.btn-secondary:hover {
		background-color: rgba(75, 85, 99, 0.5);
	}

	.btn-small {
		background-color: rgba(55, 65, 81, 0.5);
		padding: 0.375rem 0.75rem;
		color: #d1d5db;
		font-size: 0.75rem;
	}

	.btn-small:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	/* Tartalom grid */
	.content-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		flex: 1;
		gap: 1.5rem;
	}

	@media (max-width: 1024px) {
		.content-grid {
			grid-template-columns: 1fr;
		}
	}

	/* Hiba lista */
	.errors-list-section,
	.error-details-section {
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 12px;
		background-color: rgba(31, 41, 55, 0.5);
		padding: 1.25rem;
	}

	.errors-list-section h2,
	.error-details-section h2 {
		margin: 0 0 1rem;
		color: #d1d5db;
		font-size: 1rem;
	}

	.errors-list {
		margin: 0;
		padding: 0;
		max-height: 500px;
		overflow-y: auto;
		list-style: none;
	}

	.error-item {
		transition: all 0.2s;
		cursor: pointer;
		margin-bottom: 0.5rem;
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 8px;
		background-color: rgba(55, 65, 81, 0.5);
		padding: 0.75rem;
		width: 100%;
		color: white;
		text-align: left;
	}

	.error-item:hover {
		background-color: rgba(75, 85, 99, 0.5);
	}

	.error-item.selected {
		border-color: #00d4ff;
		background-color: rgba(0, 212, 255, 0.1);
	}

	.error-item-header {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		margin-bottom: 0.5rem;
	}

	.badge {
		display: inline-block;
		border-radius: 9999px;
		padding: 0.125rem 0.5rem;
		color: white;
		font-weight: 600;
		font-size: 0.625rem;
		text-transform: uppercase;
	}

	.error-time {
		margin-left: auto;
		color: #9ca3af;
		font-size: 0.75rem;
	}

	.error-message-preview {
		color: #d1d5db;
		font-size: 0.875rem;
		word-break: break-word;
	}

	/* Lapozás */
	.pagination {
		display: flex;
		justify-content: center;
		align-items: center;
		gap: 1rem;
		margin-top: 1rem;
		border-top: 1px solid rgba(255, 255, 255, 0.1);
		padding-top: 1rem;
	}

	.page-info {
		color: #9ca3af;
		font-size: 0.875rem;
	}

	/* Hiba részletek */
	.error-details {
		position: relative;
	}

	.close-btn {
		position: absolute;
		top: 0;
		right: 0;
		cursor: pointer;
		border: none;
		background: none;
		padding: 0.25rem;
		color: #9ca3af;
		font-size: 1.25rem;
	}

	.close-btn:hover {
		color: white;
	}

	.detail-group {
		margin-bottom: 1rem;
	}

	.detail-group .detail-label {
		display: block;
		margin-bottom: 0.25rem;
		color: #9ca3af;
		font-size: 0.75rem;
		letter-spacing: 0.05em;
		text-transform: uppercase;
	}

	.detail-group code {
		border-radius: 6px;
		background-color: rgba(55, 65, 81, 0.5);
		padding: 0.25rem 0.5rem;
		font-size: 0.75rem;
		font-family: monospace;
	}

	.error-full-message {
		margin: 0;
		color: #f9fafb;
		word-break: break-word;
	}

	.stack-trace {
		margin: 0;
		border-radius: 8px;
		background-color: rgba(15, 23, 42, 0.8);
		padding: 1rem;
		max-height: 300px;
		overflow-x: auto;
		overflow-y: auto;
		color: #fca5a5;
		font-size: 0.75rem;
		font-family: monospace;
		white-space: pre-wrap;
		word-break: break-all;
	}

	.context-details {
		border-radius: 8px;
		background-color: rgba(55, 65, 81, 0.5);
		padding: 0.75rem;
	}

	.context-item {
		margin-bottom: 0.5rem;
		font-size: 0.875rem;
	}

	.context-item:last-child {
		margin-bottom: 0;
	}

	.context-label {
		margin-right: 0.5rem;
		color: #9ca3af;
	}

	.user-agent {
		font-size: 0.75rem;
		word-break: break-all;
	}

	.extra-context {
		margin: 0.5rem 0 0;
		border-radius: 6px;
		background-color: rgba(15, 23, 42, 0.8);
		padding: 0.5rem;
		overflow-x: auto;
		font-size: 0.75rem;
		font-family: monospace;
	}

	/* Állapot üzenetek */
	.loading,
	.error-message,
	.empty-message,
	.no-selection {
		padding: 2rem;
		color: #9ca3af;
		text-align: center;
	}

	.error-message {
		color: #fca5a5;
	}
</style>
