<script lang="ts">
	import { logError, logWarning, logInfo, logApiError } from '$lib/errors';

	// Error logging tests
	function triggerJsError() {
		// @ts-ignore - szándékos hiba
		const obj = null;
		obj.nonExistentMethod();
	}

	function triggerPromiseRejection() {
		Promise.reject(new Error('Teszt Promise rejection'));
	}

	function triggerManualError() {
		logError(new Error('Manuálisan naplózott hiba'), {
			component: 'TestPage',
			action: 'triggerManualError',
			customData: 'Ez egy teszt'
		});
		alert('Hiba naplózva!');
	}

	function triggerWarning() {
		logWarning('Ez egy teszt figyelmeztetés', {
			component: 'TestPage',
			reason: 'Tesztelés'
		});
		alert('Figyelmeztetés naplózva!');
	}

	function triggerInfo() {
		logInfo('Ez egy teszt info üzenet', {
			component: 'TestPage',
			action: 'userAction'
		});
		alert('Info naplózva!');
	}

	function triggerApiError() {
		logApiError('/api/test-endpoint', 500, {
			message: 'Internal Server Error',
			code: 'TEST_ERROR',
			details: { reason: 'Szimulált API hiba' }
		});
		alert('API hiba naplózva!');
	}

	function triggerMultipleErrors() {
		for (let i = 0; i < 5; i++) {
			logError(new Error(`Tömeges hiba #${i + 1}`), { index: i });
		}
		alert('5 hiba naplózva!');
	}
</script>

<div class="container">
	<h1>🧪 Hiba Tesztelés</h1>
	<p>
		Ezekkel a gombokkal különböző típusú hibákat tudsz generálni a hibaoldal és a naplózó rendszer
		teszteléséhez.
	</p>

	<div class="section">
		<h2>Hibaoldal Tesztelése</h2>
		<p class="description">
			Kattints ezekre a linkekre, hogy lásd a különböző hibakódok megjelenítését.
		</p>
		<div class="buttons">
			<a href="/admin/test-errors/404" class="error"> 🔍 404 - Not Found </a>
			<a href="/admin/test-errors/403" class="error"> 🚫 403 - Forbidden </a>
			<a href="/admin/test-errors/500" class="error"> ⚠️ 500 - Server Error </a>
			<a href="/admin/test-errors/400" class="error"> ❌ 400 - Bad Request </a>
		</div>
	</div>

	<div class="section">
		<h2>Automatikusan Elkapott Hibák</h2>
		<p class="description">Ezeket a hibákat a globális hibakezelő automatikusan elkapja.</p>
		<div class="buttons">
			<button class="error" onclick={triggerJsError}> 💥 JavaScript Hiba </button>
			<button class="error" onclick={triggerPromiseRejection}> ⚡ Promise Rejection </button>
		</div>
	</div>

	<div class="section">
		<h2>Manuálisan Naplózott Események</h2>
		<p class="description">Ezeket a hibákat a kódból manuálisan naplózzuk.</p>
		<div class="buttons">
			<button class="error" onclick={triggerManualError}> 🔴 Hiba (error) </button>
			<button class="warning" onclick={triggerWarning}> 🟡 Figyelmeztetés (warning) </button>
			<button class="info" onclick={triggerInfo}> 🔵 Info </button>
		</div>
	</div>

	<div class="section">
		<h2>API Hibák</h2>
		<p class="description">Szimulált API hiba naplózása.</p>
		<div class="buttons">
			<button class="error" onclick={triggerApiError}> 🌐 API Hiba (500) </button>
		</div>
	</div>

	<div class="section">
		<h2>Tömeges Teszt</h2>
		<p class="description">Több hiba egyszerre a rate limiter teszteléséhez.</p>
		<div class="buttons">
			<button class="warning" onclick={triggerMultipleErrors}> 📦 5 Hiba Egyszerre </button>
		</div>
	</div>

	<div class="section">
		<h2>Eredmények Megtekintése</h2>
		<p class="description">A naplózott hibákat az admin felületen tudod megtekinteni.</p>
		<div class="buttons">
			<a href="/admin/errors" class="link-button"> 📋 Admin Hiba Nézegető </a>
		</div>
	</div>
</div>

<style>
	.container {
		margin: 0 auto;
		padding: 2rem;
		max-width: 800px;
	}

	h1 {
		margin-bottom: 0.5rem;
	}

	.section {
		margin: 2rem 0;
		border-radius: 8px;
		background: #f5f5f5;
		padding: 1.5rem;
	}

	.section h2 {
		margin-top: 0;
		font-size: 1.2rem;
	}

	.description {
		margin-bottom: 1rem;
		color: #666;
	}

	.buttons {
		display: flex;
		flex-wrap: wrap;
		gap: 1rem;
	}

	button,
	.link-button,
	a.error,
	a.warning,
	a.info {
		display: inline-block;
		transition:
			transform 0.1s,
			box-shadow 0.1s;
		cursor: pointer;
		border: none;
		border-radius: 6px;
		padding: 0.75rem 1.5rem;
		font-weight: 500;
		font-size: 1rem;
		text-decoration: none;
	}

	button:hover,
	.link-button:hover,
	a.error:hover,
	a.warning:hover,
	a.info:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}

	button:active,
	a.error:active,
	a.warning:active,
	a.info:active {
		transform: translateY(0);
	}

	.error,
	a.error {
		background: #ef4444;
		color: white;
	}

	.warning {
		background: #f59e0b;
		color: white;
	}

	.info {
		background: #3b82f6;
		color: white;
	}

	.link-button {
		background: #10b981;
		color: white;
	}
</style>
