<?php
/**
 * Template: Single Earth Scientist Profile
 * Style: Full Width, Modern "About President" Aesthetic
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    // Retrieve Meta Data
    $spec = get_post_meta( get_the_ID(), '_cgr_specialization', true );
    $inst = get_post_meta( get_the_ID(), '_cgr_institution', true );
    $loc  = get_post_meta( get_the_ID(), '_cgr_location', true );
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
    .profile-placeholder .dashicons { font-size: 80px; width: 80px; height: 80px; color: #ccc; }

    /* Contact Table */
    .contact-info-box {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
        overflow: hidden;
    }

    .contact-row {
        display: flex;
        border-bottom: 1px solid #eee;
        font-size: 0.95rem;
    }
    .contact-row:last-child { border-bottom: none; }

    .contact-label {
        background: #f9f9f9;
        padding: 12px 15px;
        width: 100px;
        font-weight: 700;
        color: var(--cgr-green);
        border-right: 1px solid #eee;
        display: flex;
        align-items: center;
    }
    
    .contact-value {
        padding: 12px 15px;
        color: #555;
        flex-grow: 1;
        word-break: break-all;
    }

    /* --- 6. CONTENT AREA --- */
    .profile-content-area {
        padding-top: 10px;
    }

    .profile-name {
        font-size: 3rem;
        font-weight: 800;
        color: var(--cgr-green);
        text-transform: uppercase;
        margin: 0 0 5px;
        line-height: 1.1;
    }

    .profile-role {
        font-size: 1.1rem;
        font-weight: 600;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 25px;
        display: block;
    }

    /* Social/Action Buttons */
    .profile-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
    }
    .action-icon {
        width: 40px; height: 40px;
        background: #f36b24; /* Orange from example */
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        border-radius: 4px;
        text-decoration: none;
        transition: 0.3s;
    }
    .action-icon.secondary { background: #eeaeca; } /* Light pink/orange variant */
    .action-icon:hover { transform: translateY(-3px); opacity: 0.9; color: #fff; }

    /* Bio Text */
    .profile-bio {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #333;
    }
    .profile-bio p { margin-bottom: 20px; }

    /* Back Button */
    .btn-back {
        display: inline-block;
        margin-top: 40px;
        padding: 12px 30px;
        border: 2px solid var(--cgr-green);
        color: var(--cgr-green);
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-back:hover {
        background: var(--cgr-green);
        color: #fff;
    }

    /* --- 7. RESPONSIVE --- */
    @media (max-width: 900px) {
        .profile-container { grid-template-columns: 1fr; gap: 40px; }
        .profile-sidebar { max-width: 400px; margin: 0 auto; }
        .profile-name { font-size: 2.2rem; text-align: center; }
        .profile-role { text-align: center; }
        .profile-actions { justify-content: center; }
        .profile-hero-title { font-size: 2rem; }
    }
</style>

<main id="primary" class="site-main">

    <div class="profile-hero-strip">
        <div class="container">
            <h1 class="profile-hero-title">Scientist Profile</h1>
        </div>
    </div>

    <div class="profile-container">
        
        <div class="profile-sidebar">
            <div class="profile-image-frame">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail('large', ['class' => 'img-responsive']); ?>
                <?php else : ?>
                    <div class="profile-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="contact-info-box">
                <?php if($loc): ?>
                <div class="contact-row">
                    <div class="contact-label">Location</div>
                    <div class="contact-value"><?php echo esc_html($loc); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if($email): ?>
                <div class="contact-row">
                    <div class="contact-label">Email</div>
                    <div class="contact-value"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
                </div>
                <?php endif; ?>

                <?php if($spec): ?>
                <div class="contact-row">
                    <div class="contact-label">Field</div>
                    <div class="contact-value"><?php echo esc_html($spec); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-content-area">
            <h2 class="profile-name"><?php the_title(); ?></h2>
            
            <?php if($inst || $spec): ?>
                <span class="profile-role">
                    <?php echo esc_html($spec ? $spec : 'Earth Scientist'); ?> 
                    <?php echo ($inst) ? ' &mdash; ' . esc_html($inst) : ''; ?>
                </span>
            <?php endif; ?>

            <div class="profile-actions">
                <a href="#" class="action-icon"><span class="dashicons dashicons-facebook-alt"></span></a>
                <a href="#" class="action-icon secondary"><span class="dashicons dashicons-whatsapp"></span></a>
                <a href="#" class="action-icon" style="background:#555;"><span class="dashicons dashicons-twitter"></span></a>
            </div>

            <div class="profile-bio">
                <?php the_content(); ?>
            </div>

            <a href="<?php echo home_url('/earth-scientists/'); ?>" class="btn-back">&larr; Back to Directory</a>
        </div>

    </div>

</main>

<?php endwhile; get_footer(); ?>