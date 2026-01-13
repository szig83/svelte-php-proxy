// src/lib/api/index.ts
// API client exports

export { fetchMenu, type MenuItem, type MenuType, type MenuResponse } from './menu';
export { default as api } from './client';
export type { ApiResponse, ApiError, RequestOptions } from './client';
