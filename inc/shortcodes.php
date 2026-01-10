<?php
/**
 * Custom Shortcodes
 *
 * @package CGR Child Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registration Form Shortcode
 * Usage: [cgr_registration_form]
 */
function cgr_registration_form_shortcode() {
    ob_start();
    
    // Load dependencies
    require_once CGR_CHILD_DIR . '/integrations/sheets/registration-logger.php';
    require_once CGR_CHILD_DIR . '/integrations/sheets/class-cgr-sheet-sync.php';

    $registration_success = false;
    $registration_error   = false;
    $registration_type    = '';

    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unified_registration_nonce'])) {
        if (wp_verify_nonce($_POST['unified_registration_nonce'], 'cgr_unified_registration')) {

            $registration_type = sanitize_text_field($_POST['registration_type'] ?? '');
            $full_name = sanitize_text_field($_POST['full_name'] ?? '');

            $registration_data = array(
                'timestamp'         => current_time('mysql'),
                'full_name'         => $full_name,
                'email'             => sanitize_email($_POST['email'] ?? ''),
                'phone'             => sanitize_text_field($_POST['phone'] ?? ''),
                'city'              => sanitize_text_field($_POST['city'] ?? ''),
                'state'             => sanitize_text_field($_POST['state'] ?? ''),
                'registration_type' => $registration_type,
                'source'            => 'CGR Website',
                'ip_address'        => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            );

            if ($registration_type === 'earth_scientist') {
                $registration_data['affiliation'] = sanitize_text_field($_POST['affiliation'] ?? '');
                $registration_data['expertise']   = sanitize_text_field($_POST['expertise'] ?? '');
                $registration_data['sheet_name']  = 'EarthScientists';
            } elseif ($registration_type === 'volunteer') {
                $registration_data['availability'] = sanitize_text_field($_POST['availability'] ?? '');
                $registration_data['skills']       = sanitize_text_field($_POST['skills'] ?? '');
                $registration_data['sheet_name']   = 'Volunteers';
            } elseif ($registration_type === 'member') {
                $registration_data['org']        = sanitize_text_field($_POST['org'] ?? '');
                $registration_data['interest']   = sanitize_text_field($_POST['interest'] ?? '');
                $registration_data['sheet_name'] = 'Members';
            }

            try {
                $result = CGR_Sheet_Sync::append_volunteer_data($registration_data);
                if (!empty($result['success'])) {
                    $registration_success = true;
                    // Log success if logger exists
                    if (class_exists('CGR_Registration_Logger')) {
                        CGR_Registration_Logger::log('cgr_registration_success', ucfirst($registration_type), $registration_data);
                    }
                } else {
                    $registration_error = true;
                    if (class_exists('CGR_Registration_Logger')) {
                        CGR_Registration_Logger::log('cgr_registration_error', 'Failed to sync', $result);
                    }
                }
            } catch (Exception $e) {
                $registration_error = true;
                if (class_exists('CGR_Registration_Logger')) {
                    CGR_Registration_Logger::log('cgr_registration_error', 'Exception', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    // Output Styles
    ?>
    <style>
        /* CGR Styling: lean colors, simple cards */
        .registration-content-section { max-width: 100%; margin: 0 auto; }
        .tabs-nav-container { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .tab-btn { border: 1px solid #dfe3eb; background: #fff; padding: 10px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; color: #1f252b; }
        .tab-btn.active { background: #2b4c7e; color: #fff; border-color: #2b4c7e; }
        .form-container { background: #fff; border: 1px solid #e1e6ed; border-radius: 12px; padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr; } }
        .full-width { grid-column: 1 / -1; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #dfe3eb; border-radius: 8px; font-size: 1rem; }
        .submit-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #2b4c7e, #e85d75); color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .alert-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .alert-box { background: #fff; padding: 30px; border-radius: 12px; max-width: 400px; text-align: center; }
        .alert-box.success h3 { color: green; }
        .alert-box.error h3 { color: red; }
    </style>

    <div class="registration-content-section">
        <?php if ($registration_success): ?>
        <div class="alert-overlay" id="alert-overlay">
            <div class="alert-box success">
                <h3>Registration successful!</h3>
                <p>Thank you for registering as a <?php echo esc_html($registration_type); ?>.</p>
                <button onclick="document.getElementById('alert-overlay').style.display='none';" class="submit-btn" style="width:auto;">Close</button>
            </div>
        </div>
        <?php elseif ($registration_error): ?>
        <div class="alert-overlay" id="alert-overlay">
            <div class="alert-box error">
                <h3>Registration Failed</h3>
                <p>There was an error processing your request. Please try again later.</p>
                <button onclick="document.getElementById('alert-overlay').style.display='none';" class="submit-btn" style="width:auto;">Close</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="tabs-nav-container">
            <button class="tab-btn active" onclick="openTab(event, 'volunteer')">Volunteer</button>
            <button class="tab-btn" onclick="openTab(event, 'earth_scientist')">Earth Scientist</button>
            <button class="tab-btn" onclick="openTab(event, 'member')">Member</button>
        </div>

        <!-- Volunteer Form -->
        <div id="volunteer" class="tab-panel active">
            <div class="form-container">
                <form method="POST" action="">
                    <?php wp_nonce_field('cgr_unified_registration', 'unified_registration_nonce'); ?>
                    <input type="hidden" name="registration_type" value="volunteer">
                    <div class="form-grid">
                        <input type="text" name="full_name" class="form-input" placeholder="Full Name" required>
                        <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                        <input type="tel" name="phone" class="form-input" placeholder="Phone Number" required>
                        <input type="text" name="city" class="form-input" placeholder="City" required>
                        <input type="text" name="state" class="form-input" placeholder="State" required>
                        <input type="text" name="availability" class="form-input" placeholder="Availability (e.g., Weekends)">
                        <textarea name="skills" class="form-input full-width" placeholder="Skills / Interests" rows="3"></textarea>
                        <button type="submit" class="submit-btn full-width">Register as Volunteer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Earth Scientist Form -->
        <div id="earth_scientist" class="tab-panel">
            <div class="form-container">
                <form method="POST" action="">
                    <?php wp_nonce_field('cgr_unified_registration', 'unified_registration_nonce'); ?>
                    <input type="hidden" name="registration_type" value="earth_scientist">
                    <div class="form-grid">
                        <input type="text" name="full_name" class="form-input" placeholder="Full Name" required>
                        <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                        <input type="tel" name="phone" class="form-input" placeholder="Phone Number" required>
                        <input type="text" name="city" class="form-input" placeholder="City" required>
                        <input type="text" name="state" class="form-input" placeholder="State" required>
                        <input type="text" name="affiliation" class="form-input" placeholder="Institution / Affiliation">
                        <textarea name="expertise" class="form-input full-width" placeholder="Area of Expertise" rows="3"></textarea>
                        <button type="submit" class="submit-btn full-width">Register as Earth Scientist</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Member Form -->
        <div id="member" class="tab-panel">
            <div class="form-container">
                <form method="POST" action="">
                    <?php wp_nonce_field('cgr_unified_registration', 'unified_registration_nonce'); ?>
                    <input type="hidden" name="registration_type" value="member">
                    <div class="form-grid">
                        <input type="text" name="full_name" class="form-input" placeholder="Full Name" required>
                        <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                        <input type="tel" name="phone" class="form-input" placeholder="Phone Number" required>
                        <input type="text" name="city" class="form-input" placeholder="City" required>
                        <input type="text" name="state" class="form-input" placeholder="State" required>
                        <input type="text" name="org" class="form-input" placeholder="Organization (Optional)">
                        <textarea name="interest" class="form-input full-width" placeholder="Why do you want to join?" rows="3"></textarea>
                        <button type="submit" class="submit-btn full-width">Register as Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-panel");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.className += " active";
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_registration_form', 'cgr_registration_form_shortcode' );

/**
 * Gallery Shortcode
 * Usage: [cgr_gallery id="123"]
 */
function cgr_gallery_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => get_the_ID(),
    ), $atts, 'cgr_gallery' );

    $gallery_id = intval( $atts['id'] );
    if ( ! $gallery_id ) {
        return '';
    }

    $assets = cgr_get_gallery_assets( $gallery_id );

    if ( empty( $assets ) ) {
        return '';
    }

    ob_start();
    ?>
    <?php
    $gallery_field = 'cgr-gallery-' . $gallery_id;
    $primary_asset = $assets[0];
    $additional_assets = array_slice( $assets, 1 );
    $total_assets = count( $assets );
    $primary_thumb = $primary_asset['thumb'] ? $primary_asset['thumb'] : $primary_asset['url'];
    $primary_title = $primary_asset['title'] ?? '';
    $gallery_title = get_the_title( $gallery_id );
    $gallery_title_text = $gallery_title ? $gallery_title : __( 'Gallery', 'cgr-child' );
    ?>
    <style>
        .cgr-gallery-preview-shell {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
        }
        .cgr-gallery-preview__title {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: none;
            margin-bottom: 14px;
            color: #1f252b;
            letter-spacing: 0.02em;
            text-align: left;
            padding-left: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .cgr-gallery-preview {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.16);
        }
        .cgr-gallery-preview__link {
            display: block;
            position: relative;
            overflow: hidden;
        }
        .cgr-gallery-preview__image {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.35s ease;
            background-color: #f4f4f4;
        }
        .cgr-gallery-preview__link:hover .cgr-gallery-preview__image {
            transform: scale(1.02);
        }
        .cgr-gallery-preview__caption {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(0deg, rgba(0,0,0,0.75), transparent);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .cgr-gallery-preview__caption span:first-child {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cgr-gallery-preview__button {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid rgba(255,255,255,0.7);
            padding: 6px 12px;
            border-radius: 999px;
        }
        .cgr-gallery-preview__count {
            background: rgba(0,0,0,0.65);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.85rem;
        }
        .cgr-gallery-hidden {
            display: none;
        }
        .cgr-gallery-title--hidden {
            display: none !important;
        }
        @media (max-width: 640px) {
            .cgr-gallery-preview-shell {
                max-width: 100%;
            }
        }
    </style>
    <div class="cgr-gallery-preview-shell">
        <div class="cgr-gallery-preview__title" title="<?php echo esc_attr( $gallery_title_text ); ?>"><?php echo esc_html( $gallery_title_text ); ?></div>
        <div class="cgr-gallery-preview">
            <a class="cgr-gallery-preview__link" href="<?php echo esc_url( $primary_asset['url'] ); ?>" data-lightbox="<?php echo esc_attr( $gallery_field ); ?>" title="<?php echo esc_attr( $primary_title ); ?>">
                <img class="cgr-gallery-preview__image" src="<?php echo esc_url( $primary_thumb ); ?>" alt="<?php echo esc_attr( $primary_title ); ?>">
                <div class="cgr-gallery-preview__caption">
                    <span><?php echo esc_html( $primary_title ?: __( 'View gallery', 'cgr-child' ) ); ?></span>
                    <span class="cgr-gallery-preview__button"><?php esc_html_e( 'View slideshow', 'cgr-child' ); ?></span>
                    <?php if ( $total_assets > 1 ) : ?>
                        <span class="cgr-gallery-preview__count"><?php echo sprintf( esc_html__( '+%d more', 'cgr-child' ), $total_assets - 1 ); ?></span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php if ( ! empty( $additional_assets ) ) : ?>
            <div class="cgr-gallery-hidden" aria-hidden="true">
                <?php foreach ( $additional_assets as $asset ) : ?>
                    <a href="<?php echo esc_url( $asset['url'] ); ?>" data-lightbox="<?php echo esc_attr( $gallery_field ); ?>" title="<?php echo esc_attr( $asset['title'] ?? '' ); ?>"></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
    (function() {
        if (window.cgrGalleryTitleOnce) {
            return;
        }
        window.cgrGalleryTitleOnce = true;
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cgr-gallery-preview-shell').forEach(function(shell) {
                var parent = shell.parentElement;
                if (!parent) {
                    return;
                }
                parent.querySelectorAll('.cgr-gallery-title').forEach(function(titleEl) {
                    titleEl.classList.add('cgr-gallery-title--hidden');
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_gallery', 'cgr_gallery_shortcode' );

/**
 * Earth Leaders Directory Shortcode
 * Usage: [cgr_earth_leaders_directory]
 */
function cgr_earth_leaders_directory_shortcode() {
    ob_start();
    
    $leaders_query = new WP_Query([
        'post_type'      => 'earth_leader',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $leaders      = [];
    $districts    = [];
    $years        = [];

    if ( $leaders_query->have_posts() ) {
        while ( $leaders_query->have_posts() ) {
            $leaders_query->the_post();

            $district  = trim( (string) get_post_meta( get_the_ID(), '_cgr_district', true ) );
            $org       = trim( (string) get_post_meta( get_the_ID(), '_cgr_organization', true ) );
            $year      = trim( (string) get_post_meta( get_the_ID(), '_cgr_training_year', true ) );
            $email     = trim( (string) get_post_meta( get_the_ID(), '_cgr_email', true ) );

            $leaders[] = [
                'id'       => get_the_ID(),
                'name'     => get_the_title(),
                'district' => $district,
                'org'      => $org,
                'year'     => $year,
                'email'    => $email,
                'link'     => get_permalink(),
            ];

            if ( $district ) {
                $districts[ $district ] = true;
            }
            if ( $year && is_numeric( $year ) ) {
                $years[] = (int) $year;
            }
        }
        wp_reset_postdata();
    }

    $total_count      = count( $leaders );
    $unique_districts = count( $districts );
    $latest_year      = ! empty( $years ) ? max( $years ) : null;
    ?>
    <div class="cgr-directory-wrapper">
        <div class="cgr-table-stats">
            <div class="stat">
                <span class="label">Total Registered</span>
                <span class="value" id="cgr-total-count"><?php echo esc_html( $total_count ); ?></span>
            </div>
            <div class="stat">
                <span class="label">Districts</span>
                <span class="value"><?php echo esc_html( $unique_districts ); ?></span>
            </div>
            <div class="stat">
                <span class="label">Latest Year</span>
                <span class="value"><?php echo $latest_year ? esc_html( $latest_year ) : '—'; ?></span>
            </div>
            <div class="stat">
                <span class="label">Visible Now</span>
                <span class="value" id="cgr-visible-count"><?php echo esc_html( $total_count ); ?></span>
            </div>
        </div>

        <div class="cgr-table-shell">
            <div class="cgr-table-controls">
                <label class="cgr-search-field" aria-label="Search Earth Leaders">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="cgr-table-search" placeholder="Search by name, district, organization, or email..." />
                    <button type="button" id="cgr-clear-search" aria-label="Clear search">&times;</button>
                </label>

                <div class="cgr-sort-field">
                    <label for="cgr-table-sort">Sort</label>
                    <select id="cgr-table-sort">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="district_asc">District (A-Z)</option>
                        <option value="year_desc">Training Year (Newest)</option>
                        <option value="year_asc">Training Year (Oldest)</option>
                    </select>
                </div>
            </div>

            <div class="cgr-table-wrapper">
                <table id="cgr-leaders-table" class="cgr-data-table">
                    <thead>
                        <tr>
                            <th data-key="name" data-type="string">Name</th>
                            <th data-key="district" data-type="string">District</th>
                            <th data-key="year" data-type="number">Training Year</th>
                            <th data-key="org" data-type="string">Organization</th>
                            <th data-key="email" data-type="string">E-Mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $leaders as $leader ) :
                            $search_blob = strtolower( implode( ' ', array_filter( [
                                $leader['name'],
                                $leader['district'],
                                $leader['org'],
                                $leader['year'],
                                $leader['email'],
                            ] ) ) );
                            $year_attr = is_numeric( $leader['year'] ) ? (int) $leader['year'] : '';
                        ?>
                        <tr
                            data-name="<?php echo esc_attr( strtolower( $leader['name'] ) ); ?>"
                            data-district="<?php echo esc_attr( strtolower( $leader['district'] ) ); ?>"
                            data-year="<?php echo esc_attr( $year_attr ); ?>"
                            data-org="<?php echo esc_attr( strtolower( $leader['org'] ) ); ?>"
                            data-email="<?php echo esc_attr( strtolower( $leader['email'] ) ); ?>"
                            data-search="<?php echo esc_attr( $search_blob ); ?>"
                        >
                            <td>
                                <a href="<?php echo esc_url( $leader['link'] ); ?>" class="cgr-table-link">
                                    <?php echo esc_html( $leader['name'] ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $leader['district'] ?: '—' ); ?></td>
                            <td><?php echo esc_html( $leader['year'] ?: '—' ); ?></td>
                            <td><?php echo esc_html( $leader['org'] ?: '—' ); ?></td>
                            <td><?php echo esc_html( $leader['email'] ?: '—' ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ( empty( $leaders ) ) : ?>
                            <tr><td colspan="5" class="no-results">No Earth Leaders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('cgr-table-search');
        if(!searchInput) return;
        const clearBtn = document.getElementById('cgr-clear-search');
        const sortSelect = document.getElementById('cgr-table-sort');
        const table = document.getElementById('cgr-leaders-table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => !r.classList.contains('no-results'));
        const visibleCount = document.getElementById('cgr-visible-count');
        const totalCount = document.getElementById('cgr-total-count');

        // Toggle clear button visibility
        function toggleClearButton() {
            if (searchInput.value.trim()) {
                clearBtn.classList.add('visible');
            } else {
                clearBtn.classList.remove('visible');
            }
        }

        function getValue(row, key) {
            const raw = row.dataset[key] || '';
            if (key === 'year') {
                return raw === '' ? -Infinity : parseInt(raw, 10);
            }
            return raw;
        }

        function applyFilters() {
            const term = (searchInput.value || '').toLowerCase().trim();
            const [sortKey, sortDir] = (sortSelect.value || 'name_asc').split('_');

            let filtered = rows;
            if (term) {
                filtered = rows.filter(row => row.dataset.search.includes(term));
            }

            filtered.sort((a, b) => {
                const type = sortKey === 'year' ? 'number' : 'string';
                let va = getValue(a, sortKey);
                let vb = getValue(b, sortKey);

                if (type === 'string') {
                    va = va.toString();
                    vb = vb.toString();
                }

                if (va < vb) return sortDir === 'asc' ? -1 : 1;
                if (va > vb) return sortDir === 'asc' ? 1 : -1;
                const na = getValue(a, 'name');
                const nb = getValue(b, 'name');
                if (na < nb) return -1;
                if (na > nb) return 1;
                return 0;
            });

            tbody.innerHTML = '';
            filtered.forEach(row => tbody.appendChild(row));

            if (visibleCount) visibleCount.textContent = filtered.length;
            if (totalCount) totalCount.textContent = rows.length;

            if (filtered.length === 0) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 5;
                td.className = 'no-results';
                td.textContent = 'No matches for your search.';
                tr.appendChild(td);
                tbody.appendChild(tr);
            }
            
            toggleClearButton();
        }

        searchInput.addEventListener('input', applyFilters);

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });

        sortSelect.addEventListener('change', applyFilters);
        
        // Initial toggle
        toggleClearButton();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('cgr_earth_leaders_directory', 'cgr_earth_leaders_directory_shortcode');

/**
 * Earth Scientists Directory Shortcode
 * Usage: [cgr_earth_scientists_directory]
 */
function cgr_earth_scientists_directory_shortcode() {
    ob_start();
    
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $args = array(
        'post_type'      => 'earth_scientist',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    $query = new WP_Query( $args );
    ?>
    <style>
        /* --- VARIABLES & BASICS --- */
        :root {
            --cgr-primary: #2E6B3F;  /* Forest Green */
            --cgr-dark: #1a1a1a;
            --cgr-light: #f8f9fa;
            --cgr-accent: #FFD700;   /* Gold for buttons */
            --card-radius: 10px;     /* Strict 10px radius */
        }

        /* --- SEARCH & FILTER BAR --- */
        .cgr-filter-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto 60px;
            position: relative;
            z-index: 10;
            padding: 0 20px;
        }

        .cgr-search-box {
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px; /* Strict 10px */
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: center;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .cgr-search-input {
            flex-grow: 1;
            border: none;
            background: transparent;
            padding: 10px;
            font-size: 1rem;
            color: var(--cgr-dark);
            outline: none;
        }

        .cgr-sort-select {
            padding: 5px 10px;
            border: 1px solid #eee;
            border-radius: 10px;
            background: #f9f9f9;
            color: #555;
            font-size: 0.9rem;
            cursor: pointer;
            outline: none;
        }

        /* --- GRID LAYOUT --- */
        .cgr-grid-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto 100px;
            padding: 0 20px;
        }

        .cgr-scientists-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr); /* 5 items per line desktop */
            gap: 30px 20px; /* Narrower gaps */
        }

        @media (max-width: 1200px) {
            .cgr-scientists-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 992px) {
            .cgr-scientists-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .cgr-scientists-grid { grid-template-columns: repeat(2, 1fr); gap: 20px 15px; } /* 2 items mobile */
        }

        /* --- MODERN CARD DESIGN (Core Team Style) --- */
        .cgr-modern-card {
            background: transparent;
            border: none;
            text-align: center;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .cgr-modern-card:hover { transform: translateY(-5px); }

        /* Image Frame */
        .cgr-modern-frame {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1.1; /* Consistent aspect ratio */
            margin: 0 auto 15px;
        }

        .cgr-modern-frame a {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: var(--card-radius);
            border: 2px solid var(--cgr-primary); /* Thinner border */
            overflow: hidden;
            position: relative;
            z-index: 1;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .cgr-modern-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .cgr-modern-card:hover .cgr-modern-img { transform: scale(1.05); }

        /* Placeholder if no image */
        .cgr-modern-placeholder {
            width: 100%; height: 100%; background: #f4f4f4;
            display: flex; align-items: center; justify-content: center;
        }
        .cgr-modern-placeholder .dashicons { font-size: 40px; color: #ccc; width:40px; height:40px; }

        /* Typography */
        .cgr-modern-name {
            margin: 0 0 5px;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--cgr-dark);
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .cgr-modern-name a { color: inherit; text-decoration: none; }
        .cgr-modern-name a:hover { color: var(--cgr-primary); }

        .cgr-modern-role {
            font-size: 0.8rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 10px;
            letter-spacing: 0.5px;
            position: relative;
            display: inline-block;
            padding-bottom: 5px;
        }
        
        /* Green underline under role */
        .cgr-modern-role::after {
            content: ''; display: block;
            width: 30px; height: 2px;
            background: var(--cgr-primary);
            margin: 5px auto 0;
            border-radius: 2px;
        }

        .cgr-modern-inst {
            font-size: 0.9rem;
            color: #555;
            font-style: italic;
        }

        /* Social Icons */
        .cgr-modern-social {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            opacity: 0; transform: translateY(10px);
            transition: all 0.3s ease;
        }
        .cgr-modern-card:hover .cgr-modern-social { opacity: 1; transform: translateY(0); }

        .cgr-social-icon {
            width: 30px; height: 30px;
            background: #eee; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #666; cursor: pointer; transition: 0.3s;
        }
        .cgr-social-icon:hover { background: var(--cgr-primary); color: #fff; }

        /* --- LOAD MORE & ANIMATION --- */
        .reveal-on-scroll { opacity: 0; animation: fadeInUp 0.6s ease forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .btn-load-more {
            padding: 15px 40px;
            background: transparent;
            border: 2px solid var(--cgr-primary);
            color: var(--cgr-primary);
            border-radius: 30px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-load-more:hover { background: var(--cgr-primary); color: #fff; }
        .loading-spinner { display: none; color: var(--cgr-primary); margin: 20px; }
    </style>

    <div class="cgr-filter-container">
        <div class="cgr-search-box">
            <span class="dashicons dashicons-search" style="color:#ccc; font-size:24px;"></span>
            <input type="text" id="cgr-search" class="cgr-search-input" placeholder="Search scientists by name or specialization...">
            
            <select class="cgr-sort-select" id="cgr-sort">
                <option value="title_asc">Name (A-Z)</option>
                <option value="title_desc">Name (Z-A)</option>
                <option value="date_desc">Newest First</option>
            </select>
        </div>
    </div>

    <div class="cgr-grid-wrapper">
        <div id="cgr-scientists-grid" class="cgr-scientists-grid">
            <?php
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $spec = get_post_meta( get_the_ID(), '_cgr_specialization', true );
                    $inst = get_post_meta( get_the_ID(), '_cgr_institution', true );
                    $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                    ?>
                    <div class="cgr-modern-card reveal-on-scroll">
                        <div class="cgr-modern-frame">
                            <a href="<?php the_permalink(); ?>">
                                <?php if ( $thumb_url ) : ?>
                                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" class="cgr-modern-img">
                                <?php else : ?>
                                    <div class="cgr-modern-placeholder">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="cgr-modern-info">
                            <h3 class="cgr-modern-name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <?php if($spec): ?><div class="cgr-modern-role"><?php echo esc_html($spec); ?></div><?php endif; ?>
                            <?php if($inst): ?><div class="cgr-modern-inst"><?php echo esc_html($inst); ?></div><?php endif; ?>
                            
                            <div class="cgr-modern-social">
                                <span class="cgr-social-icon"><i class="dashicons dashicons-email"></i></span>
                                <span class="cgr-social-icon"><i class="dashicons dashicons-share"></i></span>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
            else :
                echo '<p class="no-results" style="text-align:center; grid-column:1/-1;">No scientists found.</p>';
            endif;
            wp_reset_postdata();
            ?>
        </div>

        <div id="cgr-load-more-container" style="text-align: center; margin-top: 60px;">
            <?php
            if ( $query->max_num_pages > 1 ) {
                echo '<button id="cgr-load-more-btn" class="btn-load-more" data-next="2" data-max="' . $query->max_num_pages . '">View More Scientists</button>';
                echo '<div class="loading-spinner"><span class="dashicons dashicons-update" style="animation: spin 2s infinite linear;"></span> Loading...</div>';
            }
            ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var searchTimeout;

        // Live Search
        $('#cgr-search').on('input', function() {
            var val = $(this).val();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadScientists(val, 1, true);
            }, 500);
        });

        // Sort Change
        $('#cgr-sort').on('change', function() {
            var search = $('#cgr-search').val();
            loadScientists(search, 1, true);
        });

        // Load More Click
        $(document).on('click', '.btn-load-more', function() {
            var btn = $(this);
            var next = btn.data('next');
            var max = btn.data('max');
            var search = $('#cgr-search').val();
            
            if(next <= max) {
                loadScientists(search, next, false);
                btn.data('next', next + 1); // Increment for next click
            } else {
                btn.hide();
            }
        });

        function loadScientists(search, paged, replace) {
            var container = $('#cgr-scientists-grid');
            var loadMoreContainer = $('#cgr-load-more-container');
            var spinner = $('.loading-spinner');
            var btn = $('.btn-load-more');
            var sort = $('#cgr-sort').val();

            if(replace) {
                container.css('opacity', '0.5');
            } else {
                btn.hide();
                spinner.show();
            }

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'cgr_filter_earth_scientists',
                    search: search,
                    sort: sort,
                    paged: paged
                },
                success: function(response) {
                    if(replace) {
                        container.html(response).css('opacity', '1');
                        if(response.trim() === '' || $(response).find('.no-results').length > 0) {
                            btn.hide();
                        } else {
                            btn.show().data('next', 2); 
                        }
                    } else {
                        container.append(response);
                        spinner.hide();
                        if(response.trim() === '') {
                            btn.hide();
                        } else {
                            btn.show();
                        }
                    }
                    
                    $('.reveal-on-scroll').css('animation', 'none');
                    setTimeout(function(){ $('.reveal-on-scroll').css('animation', ''); }, 10);
                }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('cgr_earth_scientists_directory', 'cgr_earth_scientists_directory_shortcode');

/**
 * CGR Team Directory Shortcode
 * Usage: [cgr_team_directory]
 */
function cgr_team_directory_shortcode() {
    ob_start();
    
    $args = array(
        'post_type'      => 'cgr_member',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );
    $query = new WP_Query( $args );
    ?>
    <div class="cgr-team-shell">
        <?php
        $sections = array(
            'Board of Trustees'    => 'Board of Trustees',
            'Advisory Board'       => 'Advisory Board',
            'Core Members'         => 'Expert Panel Members',
        );
        foreach ( $sections as $group_label => $group_value ) :
            $section_query = new WP_Query( array(
                'post_type'      => 'cgr_member',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
                'meta_query'     => array(
                    array(
                        'key'     => '_cgr_role_type',
                        'value'   => $group_value,
                        'compare' => '=',
                    ),
                ),
            ) );

            if ( ! $section_query->have_posts() ) {
                wp_reset_postdata();
                continue;
            }
            ?>
            <section class="cgr-team-section">
                <header class="cgr-team-section__header">
                    <h2 class="cgr-team-section__title"><?php echo esc_html( $group_label ); ?></h2>
                </header>

                <div class="cgr-team-grid">
                    <?php
                    $members = $section_query->get_posts();
                    usort( $members, function ( $a, $b ) {
                        $order_value = function( $post ) {
                            $order = intval( $post->menu_order );
                            return $order > 0 ? $order : PHP_INT_MAX;
                        };

                        $order_compare = $order_value( $a ) <=> $order_value( $b );
                        if ( 0 === $order_compare ) {
                            return strcmp( $a->post_date, $b->post_date );
                        }
                        return $order_compare;
                    } );

                    foreach ( $members as $member ) :
                        setup_postdata( $member );
                        $designation = get_post_meta( $member->ID, '_cgr_designation', true );
                        $thumb_url = get_the_post_thumbnail_url( $member->ID, 'large' );
                        ?>
                        <article class="cgr-team-card">
                            <a href="<?php echo esc_url( get_permalink( $member ) ); ?>" class="cgr-team-card__image-link">
                                <?php if ( $thumb_url ) : ?>
                                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title( $member ) ); ?>" class="cgr-team-card__image">
                                <?php else : ?>
                                    <div class="cgr-team-card__placeholder">
                                        <span class="dashicons dashicons-businessperson"></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <div class="cgr-team-card__body">
                                <span class="cgr-team-card__badge"><?php echo esc_html( $group_label ); ?></span>
                                <h3 class="cgr-team-card__name"><a href="<?php echo esc_url( get_permalink( $member ) ); ?>"><?php echo esc_html( get_the_title( $member ) ); ?></a></h3>
                                <?php if ( $designation ) : ?>
                                    <p class="cgr-team-card__designation"><?php echo esc_html( $designation ); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php
                    endforeach;
                    wp_reset_postdata();
                    ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cgr_team_directory', 'cgr_team_directory_shortcode');

/**
 * Gallery Grid Shortcode
 * Usage: [cgr_gallery_grid]
 */
function cgr_gallery_grid_shortcode() {
    ob_start();
    
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $args = array(
        'post_type'      => 'cgr_gallery',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $query = new WP_Query( $args );
    ?>
    <style>
        .cgr-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .cgr-gallery-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .cgr-gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.14);
        }
        .cgr-gallery-thumb {
            position: relative;
            background: linear-gradient(135deg, #f6f8fb 0%, #e9eef5 100%);
            overflow: hidden;
            border-radius: 12px;
        }
        .cgr-gallery-thumb::before {
            content: '';
            display: block;
            padding-top: 62%;
        }
        .cgr-gallery-thumb a {
            position: absolute;
            inset: 0;
            display: block;
        }
        .cgr-gallery-thumb img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cgr-gallery-info {
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .cgr-gallery-title {
            margin: 0 0 10px;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .cgr-gallery-title a {
            text-decoration: none;
            color: #333;
        }
        .cgr-gallery-meta {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
    <div class="cgr-gallery-grid">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                // If no featured image, try to get first image from assets
                if ( ! $thumb_url ) {
                    $assets = cgr_get_gallery_assets( get_the_ID() );
                    if ( ! empty( $assets ) ) {
                        $thumb_url = $assets[0]['thumb'];
                    }
                }
            ?>
                <div class="cgr-gallery-card">
                    <div class="cgr-gallery-thumb">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>">
                            <?php else : ?>
                                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#ccc;">
                                    <span class="dashicons dashicons-format-gallery" style="font-size:40px;width:40px;height:40px;"></span>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="cgr-gallery-info">
                        <h3 class="cgr-gallery-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="cgr-gallery-meta"><?php echo get_the_date(); ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p>No galleries found.</p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cgr_gallery_grid', 'cgr_gallery_grid_shortcode');

/**
 * Home Page Sections Shortcode
 * Usage: [cgr_home_sections]
 * DISABLED: To allow Elementor to control the home page.
 */
function cgr_home_sections_shortcode() {
    return ''; // Disabled
    /*
    ob_start();
    
    // Hero section
    $hero_path = locate_template( array( 'sections/section-hero.php' ) );
    if ( $hero_path && file_exists( $hero_path ) ) {
        include( $hero_path );
    }

    // Impact section
    $impact_path = locate_template( array( 'sections/section-impact.php' ) );
    if ( $impact_path && file_exists( $impact_path ) ) {
        include( $impact_path );
    }

    // About section
    $about_path = locate_template( array( 'sections/section-about.php' ) );
    if ( $about_path && file_exists( $about_path ) ) {
        include( $about_path );
    }

    // Our Initiatives Section
    $initiatives_path = locate_template( array( 'sections/section-OurInitiatives.php' ) );
    if ( $initiatives_path && file_exists( $initiatives_path ) ) {
        include( $initiatives_path );
    }

    // Upcoming Events Section
    $events_path = locate_template( array( 'sections/section-Upcoming Events' ) );
    if ( $events_path && file_exists( $events_path ) ) {
        include( $events_path );
    }

    // Blog section
    $blog_path = locate_template( array( 'sections/section-blog.php' ) );
    if ( $blog_path && file_exists( $blog_path ) ) {
        include( $blog_path );
    }

    // Appreciations section
    $appreciations_path = locate_template( array( 'sections/section-testimonials.php' ) );
    if ( $appreciations_path && file_exists( $appreciations_path ) ) {
        include( $appreciations_path );
    }

    return ob_get_clean();
    */
}
add_shortcode( 'cgr_home_sections', 'cgr_home_sections_shortcode' );

/**
 * Publications Grid Shortcode
 * Usage: [cgr_publications] or [cgr_publications type="pudami"]
 */
function cgr_publications_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => -1,
        'type'           => '', // Taxonomy slug (e.g., 'pudami', 'newsletter')
    ), $atts, 'cgr_publications' );

    $args = array(
        'post_type'      => 'cgr_publication',
        'posts_per_page' => $atts['posts_per_page'],
        'orderby'        => 'meta_value_num',
        'meta_key'       => '_cgr_publication_year',
        'order'          => 'DESC',
    );

    if ( ! empty( $atts['type'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'publication_type',
                'field'    => 'slug',
                'terms'    => $atts['type'],
            ),
        );
    }

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <style>
        .cgr-pub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        .cgr-pub-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #eee;
        }
        .cgr-pub-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        .cgr-pub-thumb {
            position: relative;
            padding-top: 130%; /* Portrait Aspect Ratio */
            background: #f4f4f4;
            overflow: hidden;
        }
        .cgr-pub-thumb img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .cgr-pub-card:hover .cgr-pub-thumb img {
            transform: scale(1.05);
        }
        .cgr-pub-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .cgr-pub-card:hover .cgr-pub-overlay {
            opacity: 1;
        }
        .cgr-pub-btn {
            background: #fff;
            color: #333;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        .cgr-pub-card:hover .cgr-pub-btn {
            transform: translateY(0);
        }
        .cgr-pub-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .cgr-pub-year {
            font-size: 0.85rem;
            color: #e74c3c;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .cgr-pub-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 15px;
            line-height: 1.4;
            color: #2c3e50;
        }
        .cgr-pub-desc {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .cgr-pub-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: auto;
        }
        .cgr-pub-link {
            color: #2E6B3F;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .cgr-pub-link:hover {
            text-decoration: underline;
        }
        
        /* Placeholder Icon */
        .cgr-pub-placeholder {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: #ccc;
        }
        .cgr-pub-placeholder .dashicons { font-size: 60px; width: 60px; height: 60px; }
    </style>

    <div class="cgr-pub-grid">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $pdf_url = get_post_meta( get_the_ID(), '_cgr_publication_file', true );
                $year = get_post_meta( get_the_ID(), '_cgr_publication_year', true );
                
                // If ID is stored, get URL
                if ( is_numeric( $pdf_url ) ) {
                    $pdf_url = wp_get_attachment_url( $pdf_url );
                }
            ?>
                <div class="cgr-pub-card">
                    <div class="cgr-pub-thumb">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large' ); ?>
                        <?php else : ?>
                            <div class="cgr-pub-placeholder">
                                <span class="dashicons dashicons-media-document"></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $pdf_url ) : ?>
                        <div class="cgr-pub-overlay">
                            <a href="<?php the_permalink(); ?>" class="cgr-pub-btn">
                                <span class="dashicons dashicons-visibility"></span> View Details
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="cgr-pub-overlay">
                            <a href="<?php the_permalink(); ?>" class="cgr-pub-btn">
                                <span class="dashicons dashicons-visibility"></span> Read More
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cgr-pub-content">
                        <?php if ( $year ) : ?>
                            <div class="cgr-pub-year"><?php echo esc_html( $year ); ?></div>
                        <?php endif; ?>
                        
                        <h3 class="cgr-pub-title"><a href="<?php the_permalink(); ?>" style="color:inherit; text-decoration:none;"><?php the_title(); ?></a></h3>
                        
                        <div class="cgr-pub-desc">
                            <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                        </div>

                        <div class="cgr-pub-footer">
                            <a href="<?php the_permalink(); ?>" class="cgr-pub-link">
                                Read Full Content &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No publications found.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_publications', 'cgr_publications_shortcode' );

/**
 * Press Coverage Grid Shortcode
 * Usage: [cgr_press_coverage]
 */
function cgr_press_coverage_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => -1,
    ), $atts, 'cgr_press_coverage' );

    $args = array(
        'post_type'      => 'cgr_press_coverage',
        'posts_per_page' => $atts['posts_per_page'],
        'orderby'        => 'meta_value',
        'meta_key'       => '_cgr_press_date',
        'order'          => 'DESC',
    );

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <style>
        .cgr-press-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        .cgr-press-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        .cgr-press-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .cgr-press-thumb {
            position: relative;
            height: 200px;
            background: #f9f9f9;
            overflow: hidden;
        }
        .cgr-press-thumb img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.5s ease;
        }
        .cgr-press-card:hover .cgr-press-thumb img {
            transform: scale(1.05);
        }
        .cgr-press-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .cgr-press-meta {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cgr-press-pub {
            font-weight: 700;
            color: #2E6B3F;
            text-transform: uppercase;
        }
        .cgr-press-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 15px;
            line-height: 1.4;
            color: #333;
        }
        .cgr-press-title a {
            color: inherit; text-decoration: none;
        }
        .cgr-press-title a:hover {
            color: #2E6B3F;
        }
        .cgr-press-link {
            margin-top: auto;
            display: inline-block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #C49A6C;
            text-decoration: none;
        }
        .cgr-press-link:hover {
            text-decoration: underline;
        }
        .cgr-press-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: #ddd;
        }
        .cgr-press-placeholder .dashicons { font-size: 50px; width: 50px; height: 50px; }
    </style>

    <div class="cgr-press-grid">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $pub_name = get_post_meta( get_the_ID(), '_cgr_press_publication', true );
                $pub_date = get_post_meta( get_the_ID(), '_cgr_press_date', true );
                $pub_url  = get_post_meta( get_the_ID(), '_cgr_press_url', true );
                
                // Format date if exists
                if ( $pub_date ) {
                    $pub_date = date_i18n( 'M j, Y', strtotime( $pub_date ) );
                }
            ?>
                <div class="cgr-press-card">
                    <div class="cgr-press-thumb">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large' ); ?>
                            <?php else : ?>
                                <div class="cgr-press-placeholder">
                                    <span class="dashicons dashicons-format-aside"></span>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="cgr-press-content">
                        <div class="cgr-press-meta">
                            <?php if ( $pub_name ) : ?>
                                <span class="cgr-press-pub"><?php echo esc_html( $pub_name ); ?></span>
                            <?php endif; ?>
                            <?php if ( $pub_date ) : ?>
                                <span class="cgr-press-date"><?php echo esc_html( $pub_date ); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="cgr-press-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <a href="<?php the_permalink(); ?>" class="cgr-press-link">Read Full Story &rarr;</a>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No press coverage found.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_press_coverage', 'cgr_press_coverage_shortcode' );

/**
 * Events Grid Shortcode
 * Usage: [cgr_events]
 */
function cgr_events_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => 4, // Default to 4 items
    ), $atts, 'cgr_events' );

    $today = current_time( 'Y-m-d' );
    $args  = array(
        'post_type'      => 'cgr_event',
        'post_status'    => 'publish',
        'posts_per_page' => $atts['posts_per_page'],
        'meta_key'       => '_cgr_event_date',
        'meta_type'      => 'DATE',
        'meta_query'     => array(
            array(
                'key'     => '_cgr_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
        'orderby' => array(
            'meta_value' => 'ASC',
            'date'       => 'DESC',
        ),
    );

    $query = new WP_Query( $args );

    ob_start();
    ?>
    <style>
        .cgr-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* Adjusted for better 4-column fit */
            gap: 20px;
            margin: 40px 0;
        }
        .cgr-event-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        .cgr-event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .cgr-event-thumb {
            position: relative;
            height: 200px;
            background: #f9f9f9;
            overflow: hidden;
        }
        .cgr-event-thumb img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.5s ease;
        }
        .cgr-event-card:hover .cgr-event-thumb img {
            transform: scale(1.05);
        }
        .cgr-event-date-badge {
            position: absolute;
            top: 15px; right: 15px;
            background: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            color: #2E6B3F;
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cgr-event-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .cgr-event-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .cgr-event-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 15px;
            line-height: 1.4;
            color: #333;
        }
        .cgr-event-title a {
            color: inherit; text-decoration: none;
        }
        .cgr-event-desc {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .cgr-event-link {
            margin-top: auto;
            color: #C49A6C;
            font-weight: 600;
            text-decoration: none;
        }
        .cgr-event-link:hover {
            text-decoration: underline;
        }
        .cgr-event-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: #ddd;
            background: #f0f0f0;
        }
        .cgr-event-placeholder .dashicons { font-size: 40px; width: 40px; height: 40px; }
        .cgr-event-calendar {
            margin-top: 32px;
            background: #fbfcfd;
            border: 1px solid #e4e7ec;
            border-radius: 14px;
            padding: 24px;
        }
        .cgr-event-calendar__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .cgr-event-calendar__title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2e6b3f;
        }
        .cgr-event-calendar__subtitle {
            font-size: 0.9rem;
            color: #5f6b7a;
        }
        .cgr-event-calendar__grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 4px;
            margin-bottom: 18px;
        }
        .cgr-event-calendar__weekday {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c7787;
            text-transform: uppercase;
        }
        .cgr-event-calendar__cell {
            min-height: 54px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #f0f2f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: #1f252b;
            position: relative;
        }
        .cgr-event-calendar__cell--placeholder {
            background: transparent;
            border: none;
        }
        .cgr-event-calendar__cell--event {
            background: #2e6b3f;
            color: #fff;
            border-color: #1f4b2b;
            text-decoration: none;
        }
        .cgr-event-calendar__cell-day {
            font-weight: 700;
        }
        .cgr-event-calendar__cell-multi {
            font-size: 0.65rem;
            position: absolute;
            bottom: 6px;
            right: 6px;
            background: rgba(255, 255, 255, 0.35);
            padding: 1px 6px;
            border-radius: 999px;
        }
        .cgr-event-calendar__list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .cgr-event-calendar__list li {
            border-bottom: 1px solid #f0f2f6;
            padding-bottom: 8px;
        }
        .cgr-event-calendar__list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .cgr-event-calendar__list a {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            text-decoration: none;
            color: #1f252b;
        }
        .cgr-event-calendar__list-date {
            color: #5f6b7a;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .cgr-event-calendar__list-location {
            display: block;
            font-size: 0.78rem;
            color: #6c7787;
            margin-top: 2px;
        }
    </style>

    <div class="cgr-events-grid">
        <?php $calendar_events = array(); ?>
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $event_date = get_post_meta( get_the_ID(), '_cgr_event_date', true );
                $location   = get_post_meta( get_the_ID(), '_cgr_event_location', true );

                if ( $event_date ) {
                    $calendar_events[] = array(
                        'date'     => $event_date,
                        'link'     => get_permalink(),
                        'title'    => get_the_title(),
                        'location' => $location,
                    );
                }

                $formatted_date = '';
                if ( $event_date ) {
                    $formatted_date = date_i18n( 'M j, Y', strtotime( $event_date ) );
                }
            ?>
                <div class="cgr-event-card">
                    <div class="cgr-event-thumb">
                        <a href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large' ); ?>
                            <?php else : ?>
                                <div class="cgr-event-placeholder">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        <?php if ( $formatted_date ) : ?>
                            <div class="cgr-event-date-badge"><?php echo esc_html( $formatted_date ); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cgr-event-content">
                <?php if ( $location ) : ?>
                    <div class="cgr-event-meta">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html( $location ); ?>
                    </div>
                <?php endif; ?>
                
                <h3 class="cgr-event-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <div class="cgr-event-desc">
                    <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                </div>
                
                <a href="<?php the_permalink(); ?>" class="cgr-event-link">View Event Details &rarr;</a>
            </div>
        </div>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No events found.</p>
        <?php endif; ?>
    </div>
    <?php if ( ! empty( $calendar_events ) ) : ?>
        <?php
            $calendar_event_map = array();
            foreach ( $calendar_events as $event_item ) {
                if ( empty( $event_item['date'] ) ) {
                    continue;
                }
                $calendar_event_map[ $event_item['date'] ][] = $event_item;
            }

            $first_event_date = $calendar_events[0]['date'] ?? '';
            $calendar_timestamp = $first_event_date ? strtotime( $first_event_date ) : false;
            if ( false === $calendar_timestamp ) {
                $calendar_timestamp = current_time( 'timestamp' );
            }
            $calendar_year  = (int) date_i18n( 'Y', $calendar_timestamp );
            $calendar_month = (int) date_i18n( 'n', $calendar_timestamp );
            $calendar_label = date_i18n( 'F Y', mktime( 0, 0, 0, $calendar_month, 1, $calendar_year ) );
            $first_weekday  = (int) date_i18n( 'w', mktime( 0, 0, 0, $calendar_month, 1, $calendar_year ) );
            $days_in_month  = (int) date_i18n( 't', mktime( 0, 0, 0, $calendar_month, 1, $calendar_year ) );
            $weekday_labels = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
        ?>
        <div class="cgr-event-calendar">
            <div class="cgr-event-calendar__header">
                <div class="cgr-event-calendar__title"><?php echo esc_html( $calendar_label ); ?></div>
                <div class="cgr-event-calendar__subtitle">Upcoming events highlighted on the calendar</div>
            </div>
            <div class="cgr-event-calendar__grid">
                <?php foreach ( $weekday_labels as $label ) : ?>
                    <span class="cgr-event-calendar__weekday"><?php echo esc_html( $label ); ?></span>
                <?php endforeach; ?>

                <?php for ( $blank = 0; $blank < $first_weekday; $blank++ ) : ?>
                    <span class="cgr-event-calendar__cell cgr-event-calendar__cell--placeholder"></span>
                <?php endfor; ?>

                <?php for ( $day = 1; $day <= $days_in_month; $day++ ) :
                    $date_key       = sprintf( '%04d-%02d-%02d', $calendar_year, $calendar_month, $day );
                    $events_for_day = $calendar_event_map[ $date_key ] ?? array();
                    $has_events     = ! empty( $events_for_day );
                    $event_titles   = $has_events ? wp_list_pluck( $events_for_day, 'title' ) : array();
                    $title_attr     = $has_events ? implode( ', ', array_slice( $event_titles, 0, 3 ) ) : '';
                    if ( $has_events && count( $event_titles ) > 3 ) {
                        $title_attr .= ' +' . ( count( $event_titles ) - 3 ) . ' more';
                    }
                    $title_attr = $title_attr ? wp_trim_words( $title_attr, 20, '...' ) : '';
                ?>
                    <?php if ( $has_events ) : ?>
                        <a
                            href="<?php echo esc_url( $events_for_day[0]['link'] ); ?>"
                            class="cgr-event-calendar__cell cgr-event-calendar__cell--event"
                            title="<?php echo esc_attr( $title_attr ); ?>"
                        >
                            <span class="cgr-event-calendar__cell-day"><?php echo esc_html( $day ); ?></span>
                            <?php if ( count( $events_for_day ) > 1 ) : ?>
                                <span class="cgr-event-calendar__cell-multi">+<?php echo count( $events_for_day ) - 1; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else : ?>
                        <span class="cgr-event-calendar__cell">
                            <span class="cgr-event-calendar__cell-day"><?php echo esc_html( $day ); ?></span>
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <ul class="cgr-event-calendar__list">
                <?php foreach ( $calendar_events as $event_item ) :
                    $event_display_date = '';
                    if ( ! empty( $event_item['date'] ) ) {
                        $event_display_date = date_i18n( 'M j, Y', strtotime( $event_item['date'] ) );
                    }
                ?>
                    <li>
                        <a href="<?php echo esc_url( $event_item['link'] ); ?>">
                            <strong><?php echo esc_html( $event_item['title'] ); ?></strong>
                            <?php if ( $event_display_date ) : ?>
                                <span class="cgr-event-calendar__list-date"><?php echo esc_html( $event_display_date ); ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if ( ! empty( $event_item['location'] ) ) : ?>
                            <span class="cgr-event-calendar__list-location"><?php echo esc_html( $event_item['location'] ); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_events', 'cgr_events_shortcode' );

/**
 * Awards & Achievements shortcode.
 * Renders the admin-registered awards and wires up the full-width popup per card.
 */
function cgr_awards_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit' => -1,
    ), $atts, 'cgr_awards' );

    $limit = intval( $atts['limit'] );
    $query_args = array(
        'post_type'      => 'cgr_award',
        'post_status'    => 'publish',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'meta_key'       => '_cgr_award_year',
        'orderby'        => array(
            'meta_value_num' => 'DESC',
            'date'           => 'DESC',
        ),
    );

    $awards = get_posts( $query_args );
    if ( empty( $awards ) ) {
        return '<p class="cgr-awards-empty">' . esc_html__( 'Awards will appear here once they are added in the admin.', 'cgr-child' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="cgr-awards-shell">
        <div class="cgr-awards-grid">
            <?php foreach ( $awards as $award ) :
                $year        = get_post_meta( $award->ID, '_cgr_award_year', true );
                $issuer      = get_post_meta( $award->ID, '_cgr_award_issuer', true );
                $type        = get_post_meta( $award->ID, '_cgr_award_type', true );
                $link        = esc_url( get_post_meta( $award->ID, '_cgr_award_link', true ) );
                $summary     = get_the_excerpt( $award );
                $summary     = $summary ? $summary : wp_trim_words( wp_strip_all_tags( $award->post_content ), 24 );
                $detail_html = wp_kses_post( apply_filters( 'the_content', $award->post_content ) );
                $thumbnail_url = get_the_post_thumbnail_url( $award, 'large' );

                $payload = wp_json_encode( array(
                    'title'  => get_the_title( $award ),
                    'year'   => $year,
                    'issuer' => $issuer,
                    'type'   => $type,
                    'detail' => $detail_html,
                    'link'   => $link,
                ) );
                if ( false === $payload ) {
                    $payload = '{}';
                }
                ?>
                <div class="cgr-award-card" data-award-meta="<?php echo esc_attr( $payload ); ?>">
                    <?php if ( $year ) : ?>
                        <div class="cgr-award-card__year"><?php echo esc_html( $year ); ?></div>
                    <?php endif; ?>
                    <h3 class="cgr-award-card__title"><?php echo esc_html( get_the_title( $award ) ); ?></h3>
                    <div class="cgr-award-card__meta">
                        <?php if ( $type ) : ?>
                            <span class="cgr-award-card__tag"><?php echo esc_html( $type ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $summary ) : ?>
                        <p class="cgr-award-card__summary"><?php echo esc_html( $summary ); ?></p>
                    <?php endif; ?>
                    <?php if ( $issuer ) : ?>
                        <div class="cgr-award-card__issuer"><?php echo esc_html( $issuer ); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cgr-awards-popup" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="cgr-awards-popup__backdrop"></div>
            <div class="cgr-awards-popup__content">
                <button type="button" class="cgr-awards-popup__close" aria-label="<?php esc_attr_e( 'Close award details', 'cgr-child' ); ?>">&times;</button>
                <h3 class="cgr-awards-popup__heading" data-award-title></h3>
                <div class="cgr-awards-popup__subhead">
                    <span data-award-year></span>
                    <span data-award-type></span>
                    <span data-award-issuer></span>
                </div>
                <div class="cgr-awards-popup__detail" data-award-detail></div>
                <a class="cgr-awards-popup__link" data-award-link target="_blank" rel="noopener noreferrer" style="display:none;">
                    <?php esc_html_e( 'View full details', 'cgr-child' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'cgr_awards', 'cgr_awards_shortcode' );

/**
 * People directory shortcode.
 * Displays the defined sections in the requested order with featured image thumbs.
 */
function cgr_people_shortcode( $atts ) {
    $sections = array(
        'board-of-trustees'    => __( 'Board of Trustees', 'cgr-child' ),
        'advisory-board'       => __( 'Advisory Board', 'cgr-child' ),
        'expert-panel-members' => __( 'Core Members', 'cgr-child' ),
    );
    $default_thumb = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
        '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">'
        . '<rect width="400" height="300" fill="#f4f4f4"/>'
        . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#b0bec5" font-family="Inter, Arial" font-size="24">'
        . 'Person'
        . '</text></svg>'
    );

    ob_start();
    ?>
    <div class="cgr-people-shell">
        <?php foreach ( $sections as $slug => $label ) :
            $term = get_term_by( 'slug', $slug, 'people_section' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }

            $people_query = new WP_Query( array(
                'post_type'      => 'cgr_person',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'date'       => 'DESC',
                ),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'people_section',
                        'field'    => 'slug',
                        'terms'    => $slug,
                    ),
                ),
            ) );

            if ( ! $people_query->have_posts() ) {
                continue;
            }
            ?>
            <section class="cgr-people-section">
                <h3 class="cgr-people-section__heading"><?php echo esc_html( $label ); ?></h3>
                <div class="cgr-people-grid">
                    <?php while ( $people_query->have_posts() ) : $people_query->the_post(); ?>
                        <?php
                        $designation = get_post_meta( get_the_ID(), '_cgr_person_designation', true );
                        $organization = get_post_meta( get_the_ID(), '_cgr_person_organization', true );
                        $notes = get_post_meta( get_the_ID(), '_cgr_person_notes', true );
                        $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
                        $thumb_url = $thumb_url ? $thumb_url : $default_thumb;
                        ?>
                        <article class="cgr-person-card">
                            <div class="cgr-person-card__media">
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
                            </div>
                            <h4 class="cgr-person-card__name"><?php the_title(); ?></h4>
                            <?php if ( $designation ) : ?>
                                <p class="cgr-person-card__designation"><?php echo esc_html( $designation ); ?></p>
                            <?php endif; ?>
                            <?php if ( $organization ) : ?>
                                <p class="cgr-person-card__organization"><?php echo esc_html( $organization ); ?></p>
                            <?php endif; ?>
                            <?php if ( $notes ) : ?>
                                <p class="cgr-person-card__notes"><?php echo esc_html( $notes ); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php wp_reset_postdata(); ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'cgr_people', 'cgr_people_shortcode' );
