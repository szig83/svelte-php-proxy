<script lang="ts">
	import type { Snippet } from 'svelte';
	import Sidebar from './Sidebar.svelte';
	import Header from './Header.svelte';

	interface Props {
		children: Snippet;
	}

	let { children }: Props = $props();
</script>

<div class="flex h-screen bg-slate-700">
	<!-- Sidebar - appears as background layer -->
	<Sidebar />

	<!-- Main content area - appears as foreground layer, edge to edge -->
	<div class="-ml-6 flex flex-1 flex-col overflow-hidden rounded-l-3xl bg-slate-100 shadow-xl">
		<!-- Header -->
		<div class="shrink-0 px-6 pt-4">
			<Header />
		</div>

		<!-- Page content - scrollable -->
		<main class="flex-1 overflow-y-auto p-6">
			<div class="mx-auto max-w-7xl">
				{@render children()}
			</div>
		</main>
	</div>
</div>
