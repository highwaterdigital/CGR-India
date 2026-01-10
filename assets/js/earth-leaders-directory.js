/**
 * Earth Leaders Directory JavaScript
 * Handles search, filter, sort, and load more functionality
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', initDirectory);

    function initDirectory() {
        // Get elements
        const searchInput = document.getElementById('cgr-search-input');
        const clearBtn = document.getElementById('cgr-clear-search');
        const districtFilter = document.getElementById('cgr-district-filter');
        const sortSelect = document.getElementById('cgr-sort-select');
        const table = document.getElementById('cgr-leaders-table');
        const tbody = document.getElementById('cgr-leaders-tbody');
        const noResults = document.getElementById('cgr-no-results');
        const resetBtn = document.getElementById('cgr-reset-filters');
        const visibleCount = document.getElementById('cgr-visible-count');
        const totalCount = document.getElementById('cgr-total-count');
        const loadMoreBtn = document.getElementById('cgr-load-more');

        // Validate elements exist
        if (!searchInput || !table || !tbody) {
            console.error('CGR Directory: Required elements not found');
            return;
        }

        // Get all rows
        let allRows = Array.from(tbody.querySelectorAll('.cgr-leader-row'));
        const totalRows = allRows.length;
        let currentlyLoaded = 100;

        // Initialize
        if (totalCount) {
            totalCount.textContent = totalRows;
        }

        /**
         * Load more rows
         */
        function loadMoreRows() {
            const hiddenRows = tbody.querySelectorAll('.cgr-hidden-row');
            const toLoad = Math.min(100, hiddenRows.length);
            
            for (let i = 0; i < toLoad; i++) {
                hiddenRows[i].classList.remove('cgr-hidden-row');
                hiddenRows[i].style.display = '';
            }
            
            currentlyLoaded += toLoad;
            allRows = Array.from(tbody.querySelectorAll('.cgr-leader-row'));
            
            // Update button
            if (loadMoreBtn) {
                const remaining = tbody.querySelectorAll('.cgr-hidden-row').length;
                if (remaining > 0) {
                    loadMoreBtn.textContent = `Load More Leaders (${remaining} remaining)`;
                } else {
                    loadMoreBtn.style.display = 'none';
                }
            }
            
            // Re-apply filters to new rows
            applyFilters();
        }

        /**
         * Get data value from row
         */
        function getRowValue(row, key) {
            const value = row.dataset[key] || '';
            
            if (key === 'year') {
                if (value === '' || value === '—') {
                    return -Infinity; // Sort empty years to the end
                }
                return parseInt(value, 10);
            }
            
            return value.toString().toLowerCase();
        }

        /**
         * Check if row matches current filters
         */
        function rowMatchesFilters(row) {
            // Search term
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                const searchData = row.dataset.search || '';
                if (!searchData.includes(searchTerm)) {
                    return false;
                }
            }

            // District filter
            if (districtFilter) {
                const selectedDistrict = districtFilter.value.toLowerCase();
                if (selectedDistrict) {
                    const rowDistrict = getRowValue(row, 'district');
                    if (rowDistrict !== selectedDistrict) {
                        return false;
                    }
                }
            }

            return true;
        }

        /**
         * Sort rows based on selected criteria
         */
        function sortRows(rows) {
            const sortValue = sortSelect ? sortSelect.value : 'name_asc';
            const [sortKey, sortDir] = sortValue.split('_');

            return rows.sort((a, b) => {
                let valueA = getRowValue(a, sortKey);
                let valueB = getRowValue(b, sortKey);

                // Primary sort
                let comparison = 0;
                if (valueA < valueB) {
                    comparison = -1;
                } else if (valueA > valueB) {
                    comparison = 1;
                }

                // Apply sort direction
                if (sortDir === 'desc') {
                    comparison *= -1;
                }

                // Secondary sort by name if not already sorting by name
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
         * Apply filters and update display
         */
        function applyFilters() {
            // Show loading state
            table.classList.add('cgr-table-loading');

            // Use setTimeout to prevent blocking UI
            setTimeout(() => {
                // Get only visible (non-hidden) rows for filtering
                const visibleRows = allRows.filter(row => !row.classList.contains('cgr-hidden-row'));
                
                // Filter rows
                const filteredRows = visibleRows.filter(rowMatchesFilters);
                
                // Sort filtered rows
                const sortedRows = sortRows(filteredRows);

                // Hide all visible rows first
                visibleRows.forEach(row => row.style.display = 'none');

                // Show sorted filtered rows
                sortedRows.forEach(row => row.style.display = '');

                // Update visible count
                if (visibleCount) {
                    visibleCount.textContent = filteredRows.length;
                }

                // Show/hide no results message
                if (noResults) {
                    if (filteredRows.length === 0) {
                        noResults.style.display = 'flex';
                        table.style.display = 'none';
                    } else {
                        noResults.style.display = 'none';
                        table.style.display = 'table';
                    }
                }

                // Update clear button visibility
                updateClearButton();

                // Remove loading state
                table.classList.remove('cgr-table-loading');
            }, 10);
        }

        /**
         * Update clear button visibility
         */
        function updateClearButton() {
            if (!clearBtn) return;

            if (searchInput.value.trim()) {
                clearBtn.classList.add('visible');
            } else {
                clearBtn.classList.remove('visible');
            }
        }

        /**
         * Reset all filters
         */
        function resetFilters() {
            searchInput.value = '';
            if (districtFilter) {
                districtFilter.value = '';
            }
            if (sortSelect) {
                sortSelect.value = 'name_asc';
            }
            applyFilters();
            searchInput.focus();
        }

        /**
         * Debounce function
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

        // Event listeners
        searchInput.addEventListener('input', debounce(applyFilters, 300));

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                applyFilters();
                searchInput.focus();
            });
        }

        if (districtFilter) {
            districtFilter.addEventListener('change', applyFilters);
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', applyFilters);
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', resetFilters);
        }

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMoreRows);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }

            // Escape to clear search when search is focused
            if (e.key === 'Escape' && document.activeElement === searchInput) {
                if (searchInput.value) {
                    searchInput.value = '';
                    applyFilters();
                } else {
                    searchInput.blur();
                }
            }
        });

        // Initial setup
        updateClearButton();
        
        // Log initialization
        console.log(`CGR Directory initialized with ${totalRows} leaders (${currentlyLoaded} initially loaded)`);
    }
})();
