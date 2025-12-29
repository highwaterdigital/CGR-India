<?php
/**
 * Template: Single Earth Leader Profile
 * Style: Full Width, Modern "About President" Aesthetic
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    // Retrieve Meta Data
    $district = get_post_meta( get_the_ID(), '_cgr_district', true );
    $org = get_post_meta( get_the_ID(), '_cgr_organization', true );
    $year = get_post_meta( get_the_ID(), '_cgr_training_year', true );
    $email = get_post_meta( get_the_ID(), '_cgr_email', true );
?>

<style>
    /* --- 1. FORCE FULL WIDTH LAYOUT --- */
    #primary, .site-main, .content-area {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden;
    }

    /* --- 2. VARIABLES --- */
    :root {
        --cgr-green-dark: #1a3c28; /* Dark header bg */
        --cgr-green: #2E6B3F;      /* Primary brand green */
        --cgr-gold: #C49A6C;       /* Accent color */
        --cgr-gray: #f4f4f4;       /* Light bg */
        --cgr-text: #333;
    }

    /* --- 3. HERO STRIP --- */
    .profile-hero-strip {
        background-color: var(--cgr-green-dark);
        padding: 40px 0;
        color: #fff;
        text-align: center;
        margin-bottom: 60px;
        border-bottom: 5px solid var(--cgr-green);
    }

    .profile-hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin: 0;
    }

    /* --- 4. MAIN CONTAINER --- */
    .profile-container {
        max-width: 1200px;
        margin: 0 auto 80px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 350px 1fr; /* Fixed Sidebar + Fluid Content */
        gap: 60px;
        align-items: start;
    }

    /* --- 5. SIDEBAR (Image & Contact) --- */
    .profile-sidebar {
        position: relative;
    }

    /* Framed Image Style */
    .profile-image-frame {
        position: relative;
        border-radius: 20px;
        border: 4px solid var(--cgr-green);
        padding: 5px;
        background: #fff;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .profile-image-frame img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 15px; /* Inner radius */
        object-fit: cover;
        aspect-ratio: 3/4; /* Portrait ratio */
    }

    .profile-placeholder {
        width: 100%;
        aspect-ratio: 3/4;
        background: #eee;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
    }
    .profile-placeholder .dashicons { font-size: 80px; color: #ccc; width: 80px; height: 80px; }

    /* Contact Box */
    .profile-contact-box {
        background: var(--cgr-gray);
        padding: 25px;
        border-radius: 15px;
        border-left: 5px solid var(--cgr-gold);
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 1rem;
        color: #555;
    }
    .contact-item:last-child { margin-bottom: 0; }
    .contact-item .dashicons { color: var(--cgr-green); font-size: 22px; }

    /* --- 6. CONTENT AREA --- */
    .profile-content {
        padding-top: 10px;
    }

    .profile-name {
        font-size: 3rem;
        font-weight: 800;
        color: var(--cgr-green-dark);
        margin: 0 0 10px;
        line-height: 1.1;
    }

    .profile-role {
        font-size: 1.4rem;
        color: var(--cgr-gold);
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 30px;
        display: inline-block;
        border-bottom: 2px solid var(--cgr-gray);
        padding-bottom: 5px;
    }

    .profile-bio-section {
        margin-bottom: 40px;
    }
    
    .profile-bio-section h3 {
        font-size: 1.5rem;
        color: var(--cgr-green);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .profile-bio-section h3::before {
        content: ''; display: block; width: 8px; height: 30px; background: var(--cgr-gold); border-radius: 4px;
    }

    .profile-bio-text {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
    }

    /* --- 7. RESPONSIVE --- */
    @media (max-width: 900px) {
        .profile-container { grid-template-columns: 1fr; gap: 40px; }
        .profile-sidebar { max-width: 400px; margin: 0 auto; }
        .profile-name { font-size: 2.2rem; text-align: center; }
        .profile-role { display: block; text-align: center; }
    }
</style>

<main id="primary" class="site-main">

    <!-- Hero Strip -->
    <div class="profile-hero-strip">
        <h1 class="profile-hero-title">Earth Leader Profile</h1>
    </div>

    <div class="profile-container">
        
        <!-- Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-image-frame">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php else : ?>
                    <div class="profile-placeholder">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-contact-box">
                <?php if($district): ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-location"></span>
                        <span><strong>District:</strong> <?php echo esc_html($district); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if($org): ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-building"></span>
                        <span><strong>Org:</strong> <?php echo esc_html($org); ?></span>
                    </div>
                <?php endif; ?>

                <?php if($year): ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><strong>Training Year:</strong> <?php echo esc_html($year); ?></span>
                    </div>
                <?php endif; ?>

                <?php if($email): ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-email"></span>
                        <span><?php echo esc_html($email); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <article class="profile-content">
            <h1 class="profile-name"><?php the_title(); ?></h1>
            <div class="profile-role">Earth Leader</div>

            <div class="profile-bio-section">
                <h3>About the Leader</h3>
                <div class="profile-bio-text">
                    <?php 
                    if(get_the_content()) {
                        the_content(); 
                    } else {
                        echo '<p>No biography available for this leader yet.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Additional Details Section (Optional) -->
            <div class="profile-bio-section">
                <h3>Impact & Activities</h3>
                <div class="profile-bio-text">
                    <p>As a certified Earth Leader, <?php the_title(); ?> is actively involved in promoting environmental awareness and sustainable practices within their community in <?php echo $district ? esc_html($district) : 'their region'; ?>.</p>
                </div>
            </div>

        </article>

    </div>

</main>

<?php
endwhile;
get_footer();
?>