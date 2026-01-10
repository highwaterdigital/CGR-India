<?php
/**
 * Earth Leaders Directory - Simplified Version
 * Clean, functional search and filter interface
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
    'order' => 'ASC',
);

$leaders_query = new WP_Query($args);
$leaders_data = array();
$districts = array();
$years = array();

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
    }
    wp_reset_postdata();
}

$total_count = count($leaders_data);
$districts_count = count($districts);
$latest_year = !empty($years) ? max(array_keys($years)) : date('Y');
$batch_size = 100;
?>

<div class="cgr-directory-wrapper">
    <!-- Header Stats -->
    <div class="cgr-header-stats">
        <div class="cgr-stat">
            <div class="cgr-stat-number"><?php echo $total_count; ?></div>
            <div class="cgr-stat-label">Total Leaders</div>
        </div>
        <div class="cgr-stat">
            <div class="cgr-stat-number"><?php echo $districts_count; ?></div>
            <div class="cgr-stat-label">Districts</div>
        </div>
        <div class="cgr-stat">
            <div class="cgr-stat-number"><?php echo $latest_year; ?></div>
            <div class="cgr-stat-label">Latest Year</div>
        </div>
        <div class="cgr-stat cgr-stat-highlight">
            <div class="cgr-stat-number" id="cgr-visible-count"><?php echo min($batch_size, $total_count); ?></div>
            <div class="cgr-stat-label">Currently Showing</div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="cgr-controls">
        <div class="cgr-search-box">
            <input 
                type="text" 
                id="cgr-search-input" 
                class="cgr-input"
                placeholder="Search by name, district, or organization..."
                autocomplete="off"
            />
        </div>

        <div class="cgr-filters">
            <div class="cgr-filter">
                <select id="cgr-district-filter" class="cgr-select">
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

            <div class="cgr-filter">
                <select id="cgr-year-filter" class="cgr-select">
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

            <div class="cgr-filter">
                <select id="cgr-sort-select" class="cgr-select">
                    <option value="name_asc">Name (A → Z)</option>
                    <option value="name_desc">Name (Z → A)</option>
                    <option value="district_asc">District (A → Z)</option>
                    <option value="district_desc">District (Z → A)</option>
                    <option value="year_desc">Year (Newest)</option>
                    <option value="year_asc">Year (Oldest)</option>
                </select>
            </div>

            <button type="button" id="cgr-reset-btn" class="cgr-reset-btn">Reset</button>
        </div>
    </div>

    <!-- Results Counter -->
    <div class="cgr-results-info">
        Showing <strong id="cgr-showing-count"><?php echo min($batch_size, $total_count); ?></strong> of <strong><?php echo $total_count; ?></strong> leaders
    </div>

    <!-- Table -->
    <div class="cgr-table-wrapper">
        <table class="cgr-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>District</th>
                    <th>Training Year</th>
                    <th>Organization</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody id="cgr-leaders-tbody">
                <?php 
                foreach ($leaders_data as $index => $leader): 
                    $is_visible = ($index < $batch_size);
                    $row_class = $is_visible ? 'cgr-leader-row' : 'cgr-leader-row cgr-hidden-row';
                    $row_style = $is_visible ? '' : 'style="display: none;"';
                ?>
                <tr 
                    class="<?php echo $row_class; ?>"
                    <?php if (!$is_visible): ?>style="display: none;"<?php endif; ?>
                    data-name="<?php echo esc_attr(strtolower($leader['name'])); ?>"
                    data-district="<?php echo esc_attr(strtolower($leader['district'])); ?>"
                    data-year="<?php echo esc_attr($leader['year']); ?>"
                    data-org="<?php echo esc_attr(strtolower($leader['org'])); ?>"
                    data-search="<?php echo esc_attr(strtolower($leader['name'] . ' ' . $leader['district'] . ' ' . $leader['org'])); ?>"
                >
                    <td class="cgr-td-name">
                        <a href="<?php echo esc_url($leader['url']); ?>">
                            <?php echo esc_html($leader['name']); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($leader['district']); ?></td>
                    <td><?php echo $leader['year'] ? esc_html($leader['year']) : '—'; ?></td>
                    <td><?php echo esc_html($leader['org']); ?></td>
                    <td>
                        <?php if ($leader['email']): ?>
                            <a href="mailto:<?php echo esc_attr($leader['email']); ?>">
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

        <!-- No Results -->
        <div id="cgr-no-results" class="cgr-no-results" style="display: none;">
            <p>No leaders found matching your criteria.</p>
            <button type="button" id="cgr-reset-filters" class="cgr-reset-btn">Reset Filters</button>
        </div>
    </div>

    <!-- Load More -->
    <?php if ($total_count > $batch_size): ?>
    <div class="cgr-load-more-wrapper">
        <button type="button" id="cgr-load-more" class="cgr-load-more-btn">
            Load More Leaders (<span id="cgr-remaining-count"><?php echo $total_count - $batch_size; ?></span> remaining)
        </button>
    </div>
    <?php endif; ?>
</div>
