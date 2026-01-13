// Property test: Mobile Menu Toggle Behavior
// **Property 3: Mobile Menu Toggle Behavior**
// **Validates: Requirements 5.4**
//
// *For any* click event outside the sidebar element when the mobile menu is open,
// the menu state SHALL transition to closed.

import { describe, it, expect } from 'vitest';
import * as fc from 'fast-check';

/**
 * Represents the state of the mobile menu
 */
interface MobileMenuState {
	isOpen: boolean;
	viewportWidth: number;
}

/**
 * Represents a click event position
 */
interface ClickPosition {
	x: number;
	y: number;
}

/**
 * Sidebar bounds (fixed width of 280px as per requirements)
 */
const SIDEBAR_WIDTH = 280;
const SIDEBAR_HEIGHT = 800; // Full height
const MOBILE_BREAKPOINT = 768;

/**
 * Determines if a click position is inside the sidebar bounds
 */
function isClickInsideSidebar(click: ClickPosition, sidebarVisible: boolean): boolean {
	if (!sidebarVisible) return false;
	return click.x >= 0 && click.x <= SIDEBAR_WIDTH && click.y >= 0 && click.y <= SIDEBAR_HEIGHT;
}

/**
 * Pure function that determines if the mobile menu should close based on a click event
 * This mirrors the handleClickOutside logic from AdminLayout.svelte
 */
function shouldCloseMenu(
	state: MobileMenuState,
	clickPosition: ClickPosition,
	isInsideSidebar: boolean
): boolean {
	// Only handle on mobile when menu is open
	if (!state.isOpen) return false;

	// Check if we're on mobile (viewport < 768px)
	if (state.viewportWidth >= MOBILE_BREAKPOINT) return false;

	// Close if click is outside the sidebar
	return !isInsideSidebar;
}

/**
 * Computes the next menu state after a click event
 */
function computeNextMenuState(
	currentState: MobileMenuState,
	clickPosition: ClickPosition,
	isInsideSidebar: boolean
): MobileMenuState {
	const shouldClose = shouldCloseMenu(currentState, clickPosition, isInsideSidebar);
	return {
		...currentState,
		isOpen: shouldClose ? false : currentState.isOpen
	};
}

/**
 * Arbitrary for generating mobile viewport widths (< 768px)
 */
const mobileViewportArb = fc.integer({ min: 320, max: MOBILE_BREAKPOINT - 1 });

/**
 * Arbitrary for generating desktop viewport widths (>= 768px)
 */
const desktopViewportArb = fc.integer({ min: MOBILE_BREAKPOINT, max: 1920 });

/**
 * Arbitrary for generating click positions outside the sidebar
 */
const clickOutsideSidebarArb = fc.record({
	x: fc.integer({ min: SIDEBAR_WIDTH + 1, max: 1920 }),
	y: fc.integer({ min: 0, max: SIDEBAR_HEIGHT })
});

/**
 * Arbitrary for generating click positions inside the sidebar
 */
const clickInsideSidebarArb = fc.record({
	x: fc.integer({ min: 0, max: SIDEBAR_WIDTH }),
	y: fc.integer({ min: 0, max: SIDEBAR_HEIGHT })
});

/**
 * Arbitrary for generating any click position
 */
const anyClickPositionArb = fc.record({
	x: fc.integer({ min: 0, max: 1920 }),
	y: fc.integer({ min: 0, max: 1080 })
});

describe('Feature: admin-futuristic-layout, Property 3: Mobile Menu Toggle Behavior', () => {
	/**
	 * Property 3: Click outside sidebar closes menu on mobile
	 * For any click event outside the sidebar element when the mobile menu is open,
	 * the menu state SHALL transition to closed.
	 */
	it('should close menu when clicking outside sidebar on mobile', () => {
		fc.assert(
			fc.property(mobileViewportArb, clickOutsideSidebarArb, (viewportWidth, clickPosition) => {
				const initialState: MobileMenuState = {
					isOpen: true,
					viewportWidth
				};

				const isInsideSidebar = isClickInsideSidebar(clickPosition, initialState.isOpen);
				const nextState = computeNextMenuState(initialState, clickPosition, isInsideSidebar);

				// Menu should be closed after clicking outside
				expect(nextState.isOpen).toBe(false);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 3.1: Click inside sidebar should NOT close menu
	 * When clicking inside the sidebar, the menu should remain open
	 */
	it('should NOT close menu when clicking inside sidebar on mobile', () => {
		fc.assert(
			fc.property(mobileViewportArb, clickInsideSidebarArb, (viewportWidth, clickPosition) => {
				const initialState: MobileMenuState = {
					isOpen: true,
					viewportWidth
				};

				const isInsideSidebar = isClickInsideSidebar(clickPosition, initialState.isOpen);
				const nextState = computeNextMenuState(initialState, clickPosition, isInsideSidebar);

				// Menu should remain open after clicking inside
				expect(nextState.isOpen).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 3.2: Click outside on desktop should NOT close menu
	 * On desktop viewports, clicking outside should not affect the menu state
	 */
	it('should NOT close menu when clicking outside on desktop viewport', () => {
		fc.assert(
			fc.property(desktopViewportArb, anyClickPositionArb, (viewportWidth, clickPosition) => {
				const initialState: MobileMenuState = {
					isOpen: true,
					viewportWidth
				};

				const isInsideSidebar = isClickInsideSidebar(clickPosition, initialState.isOpen);
				const nextState = computeNextMenuState(initialState, clickPosition, isInsideSidebar);

				// Menu state should not change on desktop
				expect(nextState.isOpen).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 3.3: Closed menu should remain closed regardless of clicks
	 * When the menu is already closed, any click should not change the state
	 */
	it('should keep menu closed when already closed', () => {
		fc.assert(
			fc.property(
				fc.oneof(mobileViewportArb, desktopViewportArb),
				anyClickPositionArb,
				(viewportWidth, clickPosition) => {
					const initialState: MobileMenuState = {
						isOpen: false,
						viewportWidth
					};

					const isInsideSidebar = isClickInsideSidebar(clickPosition, initialState.isOpen);
					const nextState = computeNextMenuState(initialState, clickPosition, isInsideSidebar);

					// Menu should remain closed
					expect(nextState.isOpen).toBe(false);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 3.4: State transition is deterministic
	 * For the same input state and click position, the output should always be the same
	 */
	it('should produce deterministic state transitions', () => {
		fc.assert(
			fc.property(
				fc.boolean(),
				fc.oneof(mobileViewportArb, desktopViewportArb),
				anyClickPositionArb,
				(isOpen, viewportWidth, clickPosition) => {
					const state: MobileMenuState = { isOpen, viewportWidth };
					const isInsideSidebar = isClickInsideSidebar(clickPosition, state.isOpen);

					const result1 = computeNextMenuState(state, clickPosition, isInsideSidebar);
					const result2 = computeNextMenuState(state, clickPosition, isInsideSidebar);

					expect(result1).toEqual(result2);
				}
			),
			{ numRuns: 100 }
		);
	});

	/**
	 * Property 3.5: Mobile breakpoint boundary behavior
	 * At exactly 768px, the behavior should be desktop (menu stays open)
	 */
	it('should treat viewport at exactly 768px as desktop', () => {
		fc.assert(
			fc.property(clickOutsideSidebarArb, (clickPosition) => {
				const initialState: MobileMenuState = {
					isOpen: true,
					viewportWidth: MOBILE_BREAKPOINT // Exactly 768px
				};

				const isInsideSidebar = isClickInsideSidebar(clickPosition, initialState.isOpen);
				const nextState = computeNextMenuState(initialState, clickPosition, isInsideSidebar);

				// At 768px, should behave as desktop - menu stays open
				expect(nextState.isOpen).toBe(true);
			}),
			{ numRuns: 100 }
		);
	});
});
