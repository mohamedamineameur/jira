import { describe, expect, it } from 'vitest';

import { applyListView, getFieldValue } from '../../resources/js/lib/list-utils';

describe('list-utils', () => {
    it('reads nested fields safely', () => {
        const user = { profile: { email: 'amine@example.com' } };
        expect(getFieldValue(user, 'profile.email')).toBe('amine@example.com');
        expect(getFieldValue(user, 'profile.missing')).toBeUndefined();
        expect(getFieldValue(null, 'profile.email')).toBeUndefined();
    });

    it('filters by search and respects listed fields', () => {
        const items = [
            { id: '1', action: 'Created', performer: { email: 'amine@example.com' } },
            { id: '2', action: 'Deleted', performer: { email: 'john@example.com' } },
            { id: '3', action: 'updated', performer: { email: 'richard@example.com' } },
        ];

        const view = applyListView(
            items,
            { search: 'ATED', page: 1, perPage: 10 },
            ['action', 'performer.email'],
        );

        expect(view.total).toBe(2);
        expect(view.items.map((item) => item.id)).toEqual(['1', '3']);
        expect(view.search).toBe('ATED');
    });

    it('ignores search when term is empty and defaults perPage', () => {
        const items = Array.from({ length: 7 }, (_, index) => ({ id: String(index + 1), name: `item-${index + 1}` }));
        const view = applyListView(items, { search: '', page: 1, perPage: 0 }, ['name']);

        expect(view.perPage).toBe(6);
        expect(view.totalPages).toBe(2);
        expect(view.items).toHaveLength(6);
        expect(view.items[0].name).toBe('item-1');
    });

    it('falls back to an empty list when items is not an array', () => {
        const view = applyListView({ foo: 'bar' }, { search: '', page: 1, perPage: 5 }, ['foo']);

        expect(view.total).toBe(0);
        expect(view.items).toHaveLength(0);
        expect(view.page).toBe(1);
        expect(view.totalPages).toBe(1);
    });

    it('clamps the requested page between 1 and totalPages', () => {
        const items = Array.from({ length: 11 }, (_, index) => ({ id: String(index + 1), name: `item-${index + 1}` }));
        const view = applyListView(items, { search: '', page: 99, perPage: 5 }, ['name']);

        expect(view.total).toBe(11);
        expect(view.totalPages).toBe(3);
        expect(view.page).toBe(3);
        expect(view.items).toHaveLength(1);
        expect(view.items[0].name).toBe('item-11');
    });
});
