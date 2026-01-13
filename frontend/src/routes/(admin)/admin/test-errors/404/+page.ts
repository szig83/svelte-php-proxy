import { error } from '@sveltejs/kit';

export function load() {
	error(404, 'Ez egy teszt 404-es hiba');
}
