export function getFieldValue(item, fieldPath) {
    return String(fieldPath || '')
        .split('.')
        .reduce((acc, key) => acc?.[key], item);
}

export function applyListView(items, listState, fields) {
    const list = Array.isArray(items) ? items : [];
    const searchTerm = String(listState?.search || '')
        .trim()
        .toLowerCase();
    const perPageRaw = Number(listState?.perPage);
    const perPage = Number.isFinite(perPageRaw) && perPageRaw > 0 ? perPageRaw : 6;

    const filtered = searchTerm
        ? list.filter((item) =>
              fields.some((field) =>
                  String(getFieldValue(item, field) ?? '')
                      .toLowerCase()
                      .includes(searchTerm),
              ),
          )
        : list;

    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    const pageRaw = Number(listState?.page);
    const page = Math.min(Math.max(1, Number.isFinite(pageRaw) ? pageRaw : 1), totalPages);
    const start = (page - 1) * perPage;
    const end = start + perPage;

    return {
        items: filtered.slice(start, end),
        total,
        page,
        totalPages,
        perPage,
        search: String(listState?.search || ''),
    };
}
