import { error } from '@sveltejs/kit';

export function load() {
	error(400, 'Hibás kérés');
}
