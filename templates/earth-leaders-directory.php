<?php
/**
 * Earth Leaders Directory Template
 * 
 * @package CGR_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all earth leaders
$args = array(
    'post_type' => 'earth_leader',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'DESC', // Newest first
);

$leaders_query = new WP_Query($args);
$leaders_data = array();
$districts = array();

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
    }
    wp_reset_postdata();
}

// Reverse to show latest at bottom (so load more adds newer ones)
$leaders_data = array_reverse($leaders_data);

$total_count = count($leaders_data);
$districts_count = count($districts);
$latest_year = !empty($leaders_data) ? max(array_column($leaders_data, 'year')) : date('Y');
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
                <div class="cgr-stat-value"><?php echo $latest_year ?: '2025'; ?></div>
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
                <div class="cgr-stat-value" id="cgr-visible-count"><?php echo $total_count; ?></div>
                <div class="cgr-stat-label">Visible Now</div>
            </div>
        </div>
    </div>

    <!-- Filters and Controls -->
    <div class="cgr-controls-panel">
        <div class="cgr-controls-row">
            <!-- Search -->
            <div class="cgr-control-group cgr-search-group">
                <label for="cgr-search-input" class="cgr-control-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    Search Leaders
                </label>
                <div class="cgr-search-wrapper">
                    <input 
                        type="text" 
                        id="cgr-search-input" 
                        class="cgr-control-input"
                        placeholder="Search by name, district, or organization..."
                        aria-label="Search earth leaders"
                    />
                    <button 
                        type="button" 
                        id="cgr-clear-search" 
                        class="cgr-clear-btn"
                        aria-label="Clear search"
                        title="Clear search"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- District Filter -->
            <div class="cgr-control-group">
                <label for="cgr-district-filter" class="cgr-control-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                        <line x1="8" y1="2" x2="8" y2="18"></line>
                        <line x1="16" y1="6" x2="16" y2="22"></line>
                    </svg>
                    District
                </label>
                <select id="cgr-district-filter" class="cgr-control-select">
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

            <!-- Sort -->
            <div class="cgr-control-group">
                <label for="cgr-sort-select" class="cgr-control-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="7" y1="12" x2="21" y2="12"></line>
                        <line x1="11" y1="18" x2="21" y2="18"></line>
                    </svg>
                    Sort By
                </label>
                <select id="cgr-sort-select" class="cgr-control-select">
                    <option value="name_asc">Name (A → Z)</option>
                    <option value="name_desc">Name (Z → A)</option>
                    <option value="district_asc">District (A → Z)</option>
                    <option value="district_desc">District (Z → A)</option>
                    <option value="year_desc">Training Year (Newest)</option>
                    <option value="year_asc">Training Year (Oldest)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="cgr-table-container">
        <div class="cgr-table-wrapper">
            <table id="cgr-leaders-table" class="cgr-leaders-table">
                <thead>
                    <tr>
                        <th class="cgr-th-name">Name</th>
                        <th class="cgr-th-district">District</th>
                        <th class="cgr-th-year">Training Year</th>
                        <th class="cgr-th-org">Organization</th>
                        <th class="cgr-th-email">Email</th>
                    </tr>
                </thead>
                <tbody id="cgr-leaders-tbody">
                    <?php 
                    // Initially show only first 100 (reversed, so these are the oldest)
                    $initial_display = array_slice($leaders_data, 0, 100);
                    foreach ($initial_display as $leader): 
                    ?>
                    <tr 
                        class="cgr-leader-row"
                        data-name="<?php echo esc_attr(strtolower($leader['name'])); ?>"
                        data-district="<?php echo esc_attr(strtolower($leader['district'])); ?>"
                        data-year="<?php echo esc_attr($leader['year']); ?>"
                        data-org="<?php echo esc_attr(strtolower($leader['org'])); ?>"
                        data-email="<?php echo esc_attr(strtolower($leader['email'])); ?>"
                        data-search="<?php echo esc_attr(strtolower($leader['name'] . ' ' . $leader['district'] . ' ' . $leader['org'])); ?>"
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
                    
                    <?php 
                    // Hidden rows for load more
                    $hidden_leaders = array_slice($leaders_data, 100);
                    foreach ($hidden_leaders as $leader): 
                    ?>
                    <tr 
                        class="cgr-leader-row cgr-hidden-row"
                        style="display: none;"
                        data-name="<?php echo esc_attr(strtolower($leader['name'])); ?>"
                        data-district="<?php echo esc_attr(strtolower($leader['district'])); ?>"
                        data-year="<?php echo esc_attr($leader['year']); ?>"
                        data-org="<?php echo esc_attr(strtolower($leader['org'])); ?>"
                        data-email="<?php echo esc_attr(strtolower($leader['email'])); ?>"
                        data-search="<?php echo esc_attr(strtolower($leader['name'] . ' ' . $leader['district'] . ' ' . $leader['org'])); ?>"
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
            
            <div id="cgr-no-results" class="cgr-no-results" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <p>No leaders found matching your criteria.</p>
                <button type="button" id="cgr-reset-filters" class="cgr-btn-reset">Reset Filters</button>
            </div>
        </div>
        
        <?php if (count($leaders_data) > 100): ?>
        <div class="cgr-load-more-container">
            <button type="button" id="cgr-load-more" class="cgr-btn-load-more">
                Load More Leaders (<?php echo count($leaders_data) - 100; ?> remaining)
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
