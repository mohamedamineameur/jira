import { describe, expect, it } from 'vitest';

import { applyListView, getFieldValue } from '../../resources/js/lib/list-utils';

describe('list-utils', () => {
    it('reads nested fields safely', () => {
        const user = { profile: { email: 'amine@example.com' } };
        expect(getFieldValue(user, 'profile.email')).toBe('amine@example.com');
        expect(getFieldValue(user, 'profile.missing')).toBeUndefined();
        expect(getFieldValue(null, 'profile.email')).toBeUndefined();
    });

    it('filters by search and supports nested fields', () => {
        const items = [
            { id: '1', action: 'created', performer: { email: 'amine@example.com' } },
            { id: '2', action: 'deleted', performer: { email: 'john@example.com' } },
        ];
        const view = applyListView(items, { search: 'john', page: 1, perPage: 10 }, ['action', 'performer.email']);

        expect(view.total).toBe(1);
        expect(view.items).toHaveLength(1);
        expect(view.items[0].id).toBe('2');
    });

    it('paginates and clamps page bounds', () => {
        const items = Array.from({ length: 11 }, (_, index) => ({ id: String(index + 1), name: `item-${index + 1}` }));
        const view = applyListView(items, { search: '', page: 99, perPage: 5 }, ['name']);

        expect(view.total).toBe(11);
        expect(view.totalPages).toBe(3);
        expect(view.page).toBe(3);
        expect(view.items).toHaveLength(1);
        expect(view.items[0].name).toBe('item-11');
    });
});
