import { error } from '@sveltejs/kit';

export function load() {
	error(403, 'Nincs jogosultságod ehhez az erőforráshoz');
}
