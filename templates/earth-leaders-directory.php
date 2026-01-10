<?php
/**
 * Earth Leaders Directory Template - Version 2.0
 * Complete rewrite with modern search, filters, and UX
 * 
 * @package CGR_Child
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all earth leaders with optimized query
$args = array(
    'post_type' => 'earth_leader',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'no_found_rows' => true,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => false,
);

$leaders_query = new WP_Query($args);
$leaders_data = array();
$districts = array();
$years = array();
$organizations = array();

if ($leaders_query->have_posts()) {
    while ($leaders_query->have_posts()) {
        $leaders_query->the_post();
        
        $name = get_the_title();
        $district = get_post_meta(get_the_ID(), '_cgr_district', true) ?: '—';
        $year = get_post_meta(get_the_ID(), '_cgr_training_year', true) ?: '';
        $org = get_post_meta(get_the_ID(), '_cgr_organization', true) ?: 'CGR';
        $email = get_post_meta(get_the_ID(), '_cgr_email', true) ?: '';
        
        $leaders_data[] = array(
            'name' => $name,
            'district' => $district,
            'year' => $year,
            'org' => $org,
            'email' => $email,
            'url' => get_permalink(),
        );
        
        if ($district && $district !== '—') {
            $districts[$district] = true;
        }
        if ($year && $year !== '') {
            $years[$year] = true;
        }
        if ($org && $org !== '') {
            $organizations[$org] = true;
        }
    }
    wp_reset_postdata();
}

$total_count = count($leaders_data);
$districts_count = count($districts);
$latest_year = !empty($years) ? max(array_keys($years)) : date('Y');
$batch_size = 100;
?>

<div class="cgr-directory-container">
    <!-- Statistics Cards -->
    <div class="cgr-stats-grid">
        <div class="cgr-stat-card">
            <div class="cgr-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="cgr-stat-content">
                <div class="cgr-stat-value" id="cgr-total-count"><?php echo $total_count; ?></div>
                <div class="cgr-stat-label">Total Leaders</div>
            </div>
        </div>
        
        <div class="cgr-stat-card">
            <div class="cgr-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div class="cgr-stat-content">
                <div class="cgr-stat-value"><?php echo $districts_count; ?></div>
                <div class="cgr-stat-label">Districts</div>
            </div>
        </div>
        
        <div class="cgr-stat-card">
            <div class="cgr-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="cgr-stat-content">
                <div class="cgr-stat-value"><?php echo $latest_year; ?></div>
                <div class="cgr-stat-label">Latest Year</div>
            </div>
        </div>
        
        <div class="cgr-stat-card cgr-stat-card-highlight">
            <div class="cgr-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="cgr-stat-content">
                <div class="cgr-stat-value" id="cgr-visible-count"><?php echo min($batch_size, $total_count); ?></div>
                <div class="cgr-stat-label">Currently Showing</div>
            </div>
        </div>
    </div>

    <!-- Filters and Controls -->
    <div class="cgr-controls-panel">
        <!-- Search Bar -->
        <div class="cgr-search-section">
            <div class="cgr-search-wrapper">
                <svg class="cgr-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input 
                    type="text" 
                    id="cgr-search-input" 
                    class="cgr-search-input"
                    placeholder="Type to search by name, district, or organization..."
                    aria-label="Search earth leaders"
                    autocomplete="off"
                />
                <button 
                    type="button" 
                    id="cgr-clear-search" 
                    class="cgr-clear-btn"
                    aria-label="Clear search"
                    title="Clear search (Esc)"
                    style="display: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="cgr-search-hint">
                <kbd>Ctrl</kbd> + <kbd>K</kbd> to focus • <kbd>Esc</kbd> to clear
            </div>
        </div>

        <!-- Filter Row -->
        <div class="cgr-filters-row">
            <div class="cgr-filter-group">
                <label for="cgr-district-filter" class="cgr-filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    District
                </label>
                <select id="cgr-district-filter" class="cgr-filter-select">
                    <option value="">All Districts</option>
                    <?php 
                    $district_names = array_keys($districts);
                    sort($district_names);
                    foreach ($district_names as $district_name): 
                    ?>
                        <option value="<?php echo esc_attr(strtolower($district_name)); ?>">
                            <?php echo esc_html($district_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cgr-filter-group">
                <label for="cgr-year-filter" class="cgr-filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Training Year
                </label>
                <select id="cgr-year-filter" class="cgr-filter-select">
                    <option value="">All Years</option>
                    <?php 
                    $year_values = array_keys($years);
                    rsort($year_values);
                    foreach ($year_values as $year_value): 
                        if ($year_value):
                    ?>
                        <option value="<?php echo esc_attr($year_value); ?>">
                            <?php echo esc_html($year_value); ?>
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>

            <div class="cgr-filter-group">
                <label for="cgr-org-filter" class="cgr-filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Organization
                </label>
                <select id="cgr-org-filter" class="cgr-filter-select">
                    <option value="">All Organizations</option>
                    <?php 
                    $org_names = array_keys($organizations);
                    sort($org_names);
                    foreach ($org_names as $org_name): 
                    ?>
                        <option value="<?php echo esc_attr(strtolower($org_name)); ?>">
                            <?php echo esc_html($org_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cgr-filter-group">
                <label for="cgr-sort-select" class="cgr-filter-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="7" y1="12" x2="21" y2="12"></line>
                        <line x1="11" y1="18" x2="21" y2="18"></line>
                    </svg>
                    Sort By
                </label>
                <select id="cgr-sort-select" class="cgr-filter-select">
                    <option value="name_asc">Name (A → Z)</option>
                    <option value="name_desc">Name (Z → A)</option>
                    <option value="district_asc">District (A → Z)</option>
                    <option value="district_desc">District (Z → A)</option>
                    <option value="year_desc">Year (Newest)</option>
                    <option value="year_asc">Year (Oldest)</option>
                </select>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div id="cgr-active-filters" class="cgr-active-filters" style="display: none;">
            <div class="cgr-active-filters-header">
                <span class="cgr-active-filters-label">Active Filters:</span>
                <button type="button" id="cgr-clear-all-filters" class="cgr-clear-all-btn">
                    Clear All
                </button>
            </div>
            <div id="cgr-active-filters-tags" class="cgr-active-filters-tags"></div>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="cgr-results-summary">
        <div class="cgr-results-text">
            Showing <strong id="cgr-showing-count"><?php echo min($batch_size, $total_count); ?></strong> 
            of <strong><?php echo $total_count; ?></strong> leaders
        </div>
        <div class="cgr-results-actions">
            <button type="button" id="cgr-export-btn" class="cgr-export-btn" title="Export visible results">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="cgr-table-container">
        <!-- Loading Overlay -->
        <div id="cgr-loading-overlay" class="cgr-loading-overlay" style="display: none;">
            <div class="cgr-spinner"></div>
            <p>Loading...</p>
        </div>

        <div class="cgr-table-wrapper">
            <table id="cgr-leaders-table" class="cgr-leaders-table">
                <thead>
                    <tr>
                        <th class="cgr-th-name" data-sort="name">
                            <div class="cgr-th-content">
                                <span>Name</span>
                                <svg class="cgr-sort-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 3 18 9"></polyline>
                                    <polyline points="6 15 12 21 18 15"></polyline>
                                </svg>
                            </div>
                        </th>
                        <th class="cgr-th-district" data-sort="district">
                            <div class="cgr-th-content">
                                <span>District</span>
                                <svg class="cgr-sort-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 3 18 9"></polyline>
                                    <polyline points="6 15 12 21 18 15"></polyline>
                                </svg>
                            </div>
                        </th>
                        <th class="cgr-th-year" data-sort="year">
                            <div class="cgr-th-content">
                                <span>Training Year</span>
                                <svg class="cgr-sort-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 3 18 9"></polyline>
                                    <polyline points="6 15 12 21 18 15"></polyline>
                                </svg>
                            </div>
                        </th>
                        <th class="cgr-th-org" data-sort="org">
                            <div class="cgr-th-content">
                                <span>Organization</span>
                                <svg class="cgr-sort-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 3 18 9"></polyline>
                                    <polyline points="6 15 12 21 18 15"></polyline>
                                </svg>
                            </div>
                        </th>
                        <th class="cgr-th-email">Email</th>
                    </tr>
                </thead>
                <tbody id="cgr-leaders-tbody">
                    <?php 
                    foreach ($leaders_data as $index => $leader): 
                        $is_visible = ($index < $batch_size);
                        $row_class = $is_visible ? 'cgr-leader-row cgr-visible-row' : 'cgr-leader-row cgr-hidden-row';
                        $row_style = $is_visible ? '' : 'display: none;';
                    ?>
                    <tr 
                        class="<?php echo $row_class; ?>"
                        <?php if ($row_style): ?>style="<?php echo $row_style; ?>"<?php endif; ?>
                        data-name="<?php echo esc_attr(strtolower($leader['name'])); ?>"
                        data-district="<?php echo esc_attr(strtolower($leader['district'])); ?>"
                        data-year="<?php echo esc_attr($leader['year']); ?>"
                        data-org="<?php echo esc_attr(strtolower($leader['org'])); ?>"
                        data-email="<?php echo esc_attr(strtolower($leader['email'])); ?>"
                        data-search="<?php echo esc_attr(strtolower($leader['name'] . ' ' . $leader['district'] . ' ' . $leader['org'] . ' ' . $leader['email'])); ?>"
                    >
                        <td class="cgr-td-name">
                            <a href="<?php echo esc_url($leader['url']); ?>" class="cgr-leader-link">
                                <?php echo esc_html($leader['name']); ?>
                            </a>
                        </td>
                        <td class="cgr-td-district"><?php echo esc_html($leader['district']); ?></td>
                        <td class="cgr-td-year"><?php echo $leader['year'] ? esc_html($leader['year']) : '—'; ?></td>
                        <td class="cgr-td-org"><?php echo esc_html($leader['org']); ?></td>
                        <td class="cgr-td-email">
                            <?php if ($leader['email']): ?>
                                <a href="mailto:<?php echo esc_attr($leader['email']); ?>" class="cgr-email-link">
                                    <?php echo esc_html($leader['email']); ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- No Results Message -->
            <div id="cgr-no-results" class="cgr-no-results" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <h3>No Leaders Found</h3>
                <p>No leaders match your current search and filter criteria.</p>
                <button type="button" id="cgr-reset-filters" class="cgr-btn-reset">Reset All Filters</button>
            </div>
        </div>
        
        <!-- Load More Section -->
        <?php if ($total_count > $batch_size): ?>
        <div class="cgr-load-more-section">
            <div class="cgr-progress-bar">
                <div id="cgr-progress-fill" class="cgr-progress-fill" style="width: <?php echo ($batch_size / $total_count) * 100; ?>%;"></div>
            </div>
            <button type="button" id="cgr-load-more" class="cgr-btn-load-more">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="8 12 12 16 16 12"></polyline>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                </svg>
                Load More Leaders (<span id="cgr-remaining-count"><?php echo $total_count - $batch_size; ?></span> remaining)
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
