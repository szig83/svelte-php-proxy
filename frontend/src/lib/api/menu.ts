// src/lib/api/menu.ts
// Menu API client
// Követelmények: 5.1, 5.2

import { api, type ApiResponse } from './client';

/**
 * Menu item interface supporting nested menu structure
 */
export interface MenuItem {
	/** Menu item label */
	label: string;
	/** Menu item URL/href */
	href: string;
	/** Icon identifier */
	icon: string;
	/** Icon type: 'custom' or 'lucide' */
	icon_type?: 'custom' | 'lucide';
	/** Optional nested children menu items */
	children?: MenuItem[];
}

/**
 * Menu type identifier
 */
export type MenuType = 'protected' | 'admin' | string;

/**
 * Menu API response data
 */
export interface MenuData {
	items: MenuItem[];
}

/**
 * Menu API response
 */
export interface MenuResponse {
	success: boolean;
	items?: MenuItem[];
	error?: {
		code: string;
		message: string;
	};
}

/**
 * Fetch menu items from the backend
 * The backend will forward the request to the external API
 * which will use the access token to determine user permissions
 * and return the appropriate menu structure
 *
 * @param type - Menu type (protected, admin, etc.)
 * @returns Promise with menu items
 */
export async function fetchMenu(type: MenuType = 'protected'): Promise<MenuResponse> {
	const response: ApiResponse<MenuData> = await api.post<MenuData>('/menu', { type });

	if (response.success && response.data?.items) {
		return {
			success: true,
			items: response.data.items
		};
	}

	return {
		success: false,
		error: response.error || {
			code: 'FETCH_ERROR',
			message: 'Failed to fetch menu'
		}
	};
}
