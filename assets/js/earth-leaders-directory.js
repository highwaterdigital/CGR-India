/**
 * Earth Leaders Directory - Simplified JavaScript
 * Core search and filter functionality only
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        // Elements
        const searchInput = document.getElementById('cgr-search-input');
        const districtFilter = document.getElementById('cgr-district-filter');
        const yearFilter = document.getElementById('cgr-year-filter');
        const sortSelect = document.getElementById('cgr-sort-select');
        const resetBtn = document.getElementById('cgr-reset-btn');
        const tbody = document.getElementById('cgr-leaders-tbody');
        const noResults = document.getElementById('cgr-no-results');
        const tableWrapper = document.querySelector('.cgr-table-wrapper');
        const visibleCount = document.getElementById('cgr-visible-count');
        const showingCount = document.getElementById('cgr-showing-count');
        const loadMoreBtn = document.getElementById('cgr-load-more');
        const remainingCount = document.getElementById('cgr-remaining-count');
        const resetFiltersBtn = document.getElementById('cgr-reset-filters');

        if (!tbody || !searchInput) {
            console.error('CGR Directory: Required elements not found');
            return;
        }

        let allRows = Array.from(tbody.querySelectorAll('.cgr-leader-row'));
        const totalRows = allRows.length;
        const batchSize = 100;
        let currentlyVisible = allRows.filter(row => !row.classList.contains('cgr-hidden-row')).length;

        /**
         * Get value from row
         */
        function getRowValue(row, key) {
            const value = row.dataset[key] || '';
            if (key === 'year') {
                if (!value || value === '—') return -Infinity;
                return parseInt(value, 10);
            }
            return value.toString().toLowerCase();
        }

        /**
         * Check if row matches filters
         */
        function rowMatches(row) {
            // Search
            const search = searchInput.value.toLowerCase().trim();
            if (search) {
                const searchData = row.dataset.search || '';
                if (!searchData.includes(search)) return false;
            }

            // District
            if (districtFilter && districtFilter.value) {
                if (getRowValue(row, 'district') !== districtFilter.value.toLowerCase()) {
                    return false;
                }
            }

            // Year
            if (yearFilter && yearFilter.value) {
                if (row.dataset.year !== yearFilter.value) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Sort rows
         */
        function sortRows(rows) {
            const sortValue = sortSelect ? sortSelect.value : 'name_asc';
            const [sortKey, sortDir] = sortValue.split('_');

            return rows.sort((a, b) => {
                let valA = getRowValue(a, sortKey);
                let valB = getRowValue(b, sortKey);

                let cmp = 0;
                if (valA < valB) cmp = -1;
                else if (valA > valB) cmp = 1;

                if (sortDir === 'desc') cmp *= -1;

                // Secondary sort by name
                if (cmp === 0 && sortKey !== 'name') {
                    const nameA = getRowValue(a, 'name');
                    const nameB = getRowValue(b, 'name');
                    if (nameA < nameB) return -1;
                    if (nameA > nameB) return 1;
                }

                return cmp;
            });
        }

        /**
         * Apply filters and sort
         */
        function applyFilters() {
            // Get visible rows only
            const visibleRows = allRows.filter(row => !row.classList.contains('cgr-hidden-row'));

            // Filter
            const filtered = visibleRows.filter(rowMatches);

            // Sort
            const sorted = sortRows(filtered);

            // Hide all visible rows
            visibleRows.forEach(row => row.style.display = 'none');

            // Show filtered rows
            sorted.forEach(row => row.style.display = '');

            // Update counts
            if (visibleCount) visibleCount.textContent = sorted.length;
            if (showingCount) showingCount.textContent = sorted.length;

            // Show/hide no results
            if (noResults) {
                if (sorted.length === 0) {
                    noResults.style.display = 'block';
                    tableWrapper.querySelector('table').style.display = 'none';
                } else {
                    noResults.style.display = 'none';
                    tableWrapper.querySelector('table').style.display = 'table';
                }
            }
        }

        /**
         * Load more rows
         */
        function loadMore() {
            const hidden = tbody.querySelectorAll('.cgr-hidden-row');
            const toLoad = Math.min(100, hidden.length);

            for (let i = 0; i < toLoad; i++) {
                hidden[i].classList.remove('cgr-hidden-row');
            }

            currentlyVisible += toLoad;
            allRows = Array.from(tbody.querySelectorAll('.cgr-leader-row'));

            if (remainingCount) {
                const remaining = tbody.querySelectorAll('.cgr-hidden-row').length;
                remainingCount.textContent = remaining;
                if (remaining === 0 && loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }

            applyFilters();
        }

        /**
         * Reset all filters
         */
        function resetAll() {
            searchInput.value = '';
            if (districtFilter) districtFilter.value = '';
            if (yearFilter) yearFilter.value = '';
            if (sortSelect) sortSelect.value = 'name_asc';
            applyFilters();
            searchInput.focus();
        }

        // Event listeners
        searchInput.addEventListener('input', applyFilters);
        if (districtFilter) districtFilter.addEventListener('change', applyFilters);
        if (yearFilter) yearFilter.addEventListener('change', applyFilters);
        if (sortSelect) sortSelect.addEventListener('change', applyFilters);
        if (resetBtn) resetBtn.addEventListener('click', resetAll);
        if (resetFiltersBtn) resetFiltersBtn.addEventListener('click', resetAll);
        if (loadMoreBtn) loadMoreBtn.addEventListener('click', loadMore);

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                applyFilters();
            }
        });

        console.log(`CGR Directory initialized: ${totalRows} leaders (${currentlyVisible} visible)`);
    }
})();
