<script lang="ts">
	// PermissionGate.svelte
	// Permission-based UI rendering component
	// Követelmények: 6.3, 6.4, 6.5

	import { hasPermission, getAuthState, getIsAdmin } from '$lib/auth';
	import type { Snippet } from 'svelte';

	/**
	 * Props for PermissionGate component
	 */
	interface Props {
		/** Required permission(s) - user must have ALL of these */
		permission?: string | string[];
		/** Require admin permission */
		requireAdmin?: boolean;
		/** Behavior when permission is denied: 'hide' (default) or 'disable' */
		fallbackBehavior?: 'hide' | 'disable';
		/** Content to render when user has permission */
		children: Snippet;
		/** Optional fallback content when permission is denied (only for 'hide' behavior) */
		fallback?: Snippet;
	}

	let {
		permission,
		requireAdmin = false,
		fallbackBehavior = 'hide',
		children,
		fallback
	}: Props = $props();

	/**
	 * Check if user has all required permissions
	 */
	function checkPermissions(): boolean {
		const authState = getAuthState();

		// Must be authenticated
		if (!authState.isAuthenticated || !authState.user) {
			return false;
		}

		// Check admin requirement
		if (requireAdmin && !getIsAdmin()) {
			return false;
		}

		// Check specific permissions
		if (permission) {
			const permissions = Array.isArray(permission) ? permission : [permission];
			return permissions.every((perm) => hasPermission(perm));
		}

		return true;
	}

	// Reactive permission check
	let hasAccess = $derived(checkPermissions());
</script>

{#if hasAccess}
	<!-- User has permission - render content normally -->
	{@render children()}
{:else if fallbackBehavior === 'disable'}
	<!-- Disable mode - render content but disabled -->
	<div class="permission-disabled" aria-disabled="true">
		{@render children()}
	</div>
{:else if fallback}
	<!-- Hide mode with fallback content -->
	{@render fallback()}
{/if}
<!-- Hide mode without fallback - render nothing -->

<style>
	.permission-disabled {
		opacity: 0.5;
		pointer-events: none;
		user-select: none;
	}

	.permission-disabled :global(*) {
		cursor: not-allowed !important;
	}
</style>
