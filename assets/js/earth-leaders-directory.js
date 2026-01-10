/**
 * Earth Leaders Directory JavaScript - Version 2.0
 * Complete rewrite with improved UX, performance, and features
 *
 * Features:
 * - Instant search with highlighting
 * - Multi-filter support with active filter tags
 * - Column header sorting
 * - Export to CSV functionality
 * - Progress bar for load more
 * - Keyboard shortcuts
 * - Smooth animations
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', initDirectory);

    function initDirectory() {
        // Get all DOM elements
        const elements = {
            searchInput: document.getElementById('cgr-search-input'),
            clearSearchBtn: document.getElementById('cgr-clear-search'),
            districtFilter: document.getElementById('cgr-district-filter'),
            yearFilter: document.getElementById('cgr-year-filter'),
            orgFilter: document.getElementById('cgr-org-filter'),
            sortSelect: document.getElementById('cgr-sort-select'),
            table: document.getElementById('cgr-leaders-table'),
            tbody: document.getElementById('cgr-leaders-tbody'),
            thead: document.querySelector('.cgr-leaders-table thead'),
            noResults: document.getElementById('cgr-no-results'),
            resetBtn: document.getElementById('cgr-reset-filters'),
            visibleCount: document.getElementById('cgr-visible-count'),
            totalCount: document.getElementById('cgr-total-count'),
            showingCount: document.getElementById('cgr-showing-count'),
            loadMoreBtn: document.getElementById('cgr-load-more'),
            remainingCount: document.getElementById('cgr-remaining-count'),
            progressFill: document.getElementById('cgr-progress-fill'),
            activeFiltersContainer: document.getElementById('cgr-active-filters'),
            activeFiltersTags: document.getElementById('cgr-active-filters-tags'),
            clearAllFilters: document.getElementById('cgr-clear-all-filters'),
            exportBtn: document.getElementById('cgr-export-btn'),
            loadingOverlay: document.getElementById('cgr-loading-overlay'),
        };

        // Validate required elements
        if (!elements.searchInput || !elements.table || !elements.tbody) {
            console.error('CGR Directory: Required elements not found');
            return;
        }

        // State management
        let allRows = Array.from(elements.tbody.querySelectorAll('.cgr-leader-row'));
        const totalRows = allRows.length;
        let currentlyVisible = allRows.filter(row => row.classList.contains('cgr-visible-row')).length;
        let currentSort = { key: 'name', dir: 'asc' };
        
        // Initialize total count
        if (elements.totalCount) {
            elements.totalCount.textContent = totalRows;
        }

        console.log(`CGR Directory v2.0 initialized: ${totalRows} leaders, ${currentlyVisible} initially visible`);

        /**
         * Get data value from row
         */
        function getRowValue(row, key) {
            const value = row.dataset[key] || '';
            
            if (key === 'year') {
                if (value === '' || value === '—') {
                    return -Infinity;
                }
                return parseInt(value, 10);
            }
            
            return value.toString().toLowerCase();
        }

        /**
         * Check if row matches all active filters
         */
        function rowMatchesFilters(row) {
            // Search term
            const searchTerm = elements.searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                const searchData = row.dataset.search || '';
                if (!searchData.includes(searchTerm)) {
                    return false;
                }
            }

            // District filter
            if (elements.districtFilter) {
                const selectedDistrict = elements.districtFilter.value.toLowerCase();
                if (selectedDistrict) {
                    const rowDistrict = getRowValue(row, 'district');
                    if (rowDistrict !== selectedDistrict) {
                        return false;
                    }
                }
            }

            // Year filter
            if (elements.yearFilter) {
                const selectedYear = elements.yearFilter.value;
                if (selectedYear) {
                    const rowYear = row.dataset.year;
                    if (rowYear !== selectedYear) {
                        return false;
                    }
                }
            }

            // Organization filter
            if (elements.orgFilter) {
                const selectedOrg = elements.orgFilter.value.toLowerCase();
                if (selectedOrg) {
                    const rowOrg = getRowValue(row, 'org');
                    if (rowOrg !== selectedOrg) {
                        return false;
                    }
                }
            }

            return true;
        }

        /**
         * Sort rows based on current sort criteria
         */
        function sortRows(rows) {
            const sortKey = currentSort.key;
            const sortDir = currentSort.dir;

            return rows.sort((a, b) => {
                let valueA = getRowValue(a, sortKey);
                let valueB = getRowValue(b, sortKey);

                let comparison = 0;
                if (valueA < valueB) {
                    comparison = -1;
                } else if (valueA > valueB) {
                    comparison = 1;
                }

                if (sortDir === 'desc') {
                    comparison *= -1;
                }

                // Secondary sort by name
                if (comparison === 0 && sortKey !== 'name') {
                    const nameA = getRowValue(a, 'name');
                    const nameB = getRowValue(b, 'name');
                    if (nameA < nameB) return -1;
                    if (nameA > nameB) return 1;
                }

                return comparison;
            });
        }

        /**
         * Update active filter tags display
         */
        function updateActiveFilters() {
            const activeFilters = [];

            // Search
            const searchTerm = elements.searchInput.value.trim();
            if (searchTerm) {
                activeFilters.push({
                    type: 'search',
                    label: `Search: "${searchTerm}"`,
                    value: searchTerm
                });
            }

            // District
            if (elements.districtFilter && elements.districtFilter.value) {
                const selectedOption = elements.districtFilter.options[elements.districtFilter.selectedIndex];
                activeFilters.push({
                    type: 'district',
                    label: `District: ${selectedOption.text}`,
                    value: elements.districtFilter.value
                });
            }

            // Year
            if (elements.yearFilter && elements.yearFilter.value) {
                activeFilters.push({
                    type: 'year',
                    label: `Year: ${elements.yearFilter.value}`,
                    value: elements.yearFilter.value
                });
            }

            // Organization
            if (elements.orgFilter && elements.orgFilter.value) {
                const selectedOption = elements.orgFilter.options[elements.orgFilter.selectedIndex];
                activeFilters.push({
                    type: 'org',
                    label: `Org: ${selectedOption.text}`,
                    value: elements.orgFilter.value
                });
            }

            // Update display
            if (activeFilters.length > 0 && elements.activeFiltersContainer && elements.activeFiltersTags) {
                elements.activeFiltersContainer.style.display = 'block';
                elements.activeFiltersTags.innerHTML = activeFilters.map(filter => `
                    <button type="button" class="cgr-filter-tag" data-type="${filter.type}">
                        <span>${filter.label}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `).join('');

                // Add event listeners to tag close buttons
                elements.activeFiltersTags.querySelectorAll('.cgr-filter-tag').forEach(tag => {
                    tag.addEventListener('click', function() {
                        const filterType = this.dataset.type;
                        removeFilter(filterType);
                    });
                });
            } else if (elements.activeFiltersContainer) {
                elements.activeFiltersContainer.style.display = 'none';
            }
        }

        /**
         * Remove a specific filter
         */
        function removeFilter(type) {
            switch(type) {
                case 'search':
                    elements.searchInput.value = '';
                    break;
                case 'district':
                    if (elements.districtFilter) elements.districtFilter.value = '';
                    break;
                case 'year':
                    if (elements.yearFilter) elements.yearFilter.value = '';
                    break;
                case 'org':
                    if (elements.orgFilter) elements.orgFilter.value = '';
                    break;
            }
            applyFilters();
        }

        /**
         * Apply all filters and update display
         */
        function applyFilters() {
            // Show loading overlay
            if (elements.loadingOverlay) {
                elements.loadingOverlay.style.display = 'flex';
            }

            // Use setTimeout to prevent UI blocking
            setTimeout(() => {
                // Get visible (loaded) rows only
                const visibleRows = allRows.filter(row => !row.classList.contains('cgr-hidden-row'));
                
                // Filter rows
                const filteredRows = visibleRows.filter(rowMatchesFilters);
                
                // Sort filtered rows
                const sortedRows = sortRows(filteredRows);

                // Hide all visible rows first
                visibleRows.forEach(row => row.style.display = 'none');

                // Show sorted filtered rows
                sortedRows.forEach(row => row.style.display = '');

                // Update counters
                const visibleFiltered = filteredRows.length;
                if (elements.visibleCount) {
                    elements.visibleCount.textContent = visibleFiltered;
                }
                if (elements.showingCount) {
                    elements.showingCount.textContent = visibleFiltered;
                }

                // Show/hide no results message
                if (elements.noResults) {
                    if (visibleFiltered === 0) {
                        elements.noResults.style.display = 'flex';
                        elements.table.style.display = 'none';
                    } else {
                        elements.noResults.style.display = 'none';
                        elements.table.style.display = 'table';
                    }
                }

                // Update active filters
                updateActiveFilters();

                // Update clear search button
                updateClearButton();

                // Hide loading overlay
                if (elements.loadingOverlay) {
                    elements.loadingOverlay.style.display = 'none';
                }
            }, 50);
        }

        /**
         * Load more rows
         */
        function loadMoreRows() {
            const hiddenRows = elements.tbody.querySelectorAll('.cgr-hidden-row');
            const toLoad = Math.min(100, hiddenRows.length);
            
            for (let i = 0; i < toLoad; i++) {
                hiddenRows[i].classList.remove('cgr-hidden-row');
                hiddenRows[i].classList.add('cgr-visible-row');
            }
            
            // Update allRows to include newly visible rows
            allRows = Array.from(elements.tbody.querySelectorAll('.cgr-leader-row'));
            currentlyVisible += toLoad;

            // Update progress bar
            const loadedPercent = (currentlyVisible / totalRows) * 100;
            if (elements.progressFill) {
                elements.progressFill.style.width = `${loadedPercent}%`;
            }

            // Update button
            const remaining = elements.tbody.querySelectorAll('.cgr-hidden-row').length;
            if (elements.remainingCount) {
                elements.remainingCount.textContent = remaining;
            }

            if (remaining === 0 && elements.loadMoreBtn) {
                elements.loadMoreBtn.style.display = 'none';
            }

            // Re-apply filters to include new rows
            applyFilters();

            console.log(`Loaded ${toLoad} more rows. Total visible: ${currentlyVisible}/${totalRows}`);
        }

        /**
         * Update clear search button visibility
         */
        function updateClearButton() {
            if (!elements.clearSearchBtn) return;

            if (elements.searchInput.value.trim()) {
                elements.clearSearchBtn.style.display = 'flex';
            } else {
                elements.clearSearchBtn.style.display = 'none';
            }
        }

        /**
         * Reset all filters
         */
        function resetFilters() {
            elements.searchInput.value = '';
            if (elements.districtFilter) elements.districtFilter.value = '';
            if (elements.yearFilter) elements.yearFilter.value = '';
            if (elements.orgFilter) elements.orgFilter.value = '';
            if (elements.sortSelect) elements.sortSelect.value = 'name_asc';
            currentSort = { key: 'name', dir: 'asc' };
            updateHeaderSortState();
            applyFilters();
            elements.searchInput.focus();
        }

        /**
         * Sort by column header click
         */
        function sortByColumn(columnKey) {
            if (currentSort.key === columnKey) {
                // Toggle direction
                currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                // New column, default to ascending
                currentSort.key = columnKey;
                currentSort.dir = 'asc';
            }

            // Update sort select to match
            if (elements.sortSelect) {
                elements.sortSelect.value = `${currentSort.key}_${currentSort.dir}`;
            }

            updateHeaderSortState();
            applyFilters();
        }

        /**
         * Update table header sort indicators
         */
        function updateHeaderSortState() {
            if (!elements.thead) return;

            // Remove all active states
            elements.thead.querySelectorAll('th').forEach(th => {
                th.classList.remove('cgr-sort-active', 'cgr-sort-asc', 'cgr-sort-desc');
            });

            // Add active state to current sort column
            const activeHeader = elements.thead.querySelector(`[data-sort="${currentSort.key}"]`);
            if (activeHeader) {
                activeHeader.classList.add('cgr-sort-active', `cgr-sort-${currentSort.dir}`);
            }
        }

        /**
         * Export visible data to CSV
         */
        function exportToCSV() {
            const visibleRows = Array.from(elements.tbody.querySelectorAll('.cgr-leader-row'))
                .filter(row => row.style.display !== 'none');

            if (visibleRows.length === 0) {
                alert('No data to export');
                return;
            }

            // Build CSV content
            const headers = ['Name', 'District', 'Training Year', 'Organization', 'Email'];
            const csvRows = [headers.join(',')];

            visibleRows.forEach(row => {
                const name = row.querySelector('.cgr-td-name a').textContent.trim();
                const district = row.querySelector('.cgr-td-district').textContent.trim();
                const year = row.querySelector('.cgr-td-year').textContent.trim();
                const org = row.querySelector('.cgr-td-org').textContent.trim();
                const emailEl = row.querySelector('.cgr-td-email a');
                const email = emailEl ? emailEl.textContent.trim() : '—';

                // Escape commas and quotes in CSV
                const escapeCSV = (str) => {
                    if (str.includes(',') || str.includes('"') || str.includes('\n')) {
                        return `"${str.replace(/"/g, '""')}"`;
                    }
                    return str;
                };

                csvRows.push([
                    escapeCSV(name),
                    escapeCSV(district),
                    escapeCSV(year),
                    escapeCSV(org),
                    escapeCSV(email)
                ].join(','));
            });

            // Create download
            const csvContent = csvRows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `cgr-earth-leaders-${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            console.log(`Exported ${visibleRows.length} leaders to CSV`);
        }

        /**
         * Debounce function for search
         */
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // ==================== Event Listeners ====================

        // Search input
        elements.searchInput.addEventListener('input', debounce(applyFilters, 200));

        // Clear search button
        if (elements.clearSearchBtn) {
            elements.clearSearchBtn.addEventListener('click', () => {
                elements.searchInput.value = '';
                applyFilters();
                elements.searchInput.focus();
            });
        }

        // Filter selects
        if (elements.districtFilter) {
            elements.districtFilter.addEventListener('change', applyFilters);
        }
        if (elements.yearFilter) {
            elements.yearFilter.addEventListener('change', applyFilters);
        }
        if (elements.orgFilter) {
            elements.orgFilter.addEventListener('change', applyFilters);
        }

        // Sort select
        if (elements.sortSelect) {
            elements.sortSelect.addEventListener('change', function() {
                const [key, dir] = this.value.split('_');
                currentSort = { key, dir };
                updateHeaderSortState();
                applyFilters();
            });
        }

        // Table header sorting
        if (elements.thead) {
            elements.thead.querySelectorAll('th[data-sort]').forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const sortKey = this.dataset.sort;
                    sortByColumn(sortKey);
                });
            });
        }

        // Reset button
        if (elements.resetBtn) {
            elements.resetBtn.addEventListener('click', resetFilters);
        }

        // Clear all filters button
        if (elements.clearAllFilters) {
            elements.clearAllFilters.addEventListener('click', resetFilters);
        }

        // Load more button
        if (elements.loadMoreBtn) {
            elements.loadMoreBtn.addEventListener('click', loadMoreRows);
        }

        // Export button
        if (elements.exportBtn) {
            elements.exportBtn.addEventListener('click', exportToCSV);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                elements.searchInput.focus();
                elements.searchInput.select();
            }

            // Escape to clear search when focused
            if (e.key === 'Escape' && document.activeElement === elements.searchInput) {
                if (elements.searchInput.value) {
                    elements.searchInput.value = '';
                    applyFilters();
                } else {
                    elements.searchInput.blur();
                }
            }

            // Ctrl/Cmd + E to export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportToCSV();
            }
        });

        // Initial setup
        updateClearButton();
        updateHeaderSortState();
        updateActiveFilters();
    }
})();
