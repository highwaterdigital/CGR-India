<?php
/**
 * Template: Single Publication (Newsletter, Pudami, Annual Report)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    $pdf_url = get_post_meta( get_the_ID(), '_cgr_publication_file', true );
    $year = get_post_meta( get_the_ID(), '_cgr_publication_year', true );
    
    // Resolve Attachment ID to URL if needed
    if ( is_numeric( $pdf_url ) ) {
        $pdf_url = wp_get_attachment_url( $pdf_url );
    }

    // Get Taxonomy Terms (Type)
    $terms = get_the_terms( get_the_ID(), 'publication_type' );
    $type_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Publication';
?>

<style>
    /* --- Layout & Variables --- */
    :root {
        --cgr-green-dark: #1a3c28;
        --cgr-green: #2E6B3F;
        --cgr-gold: #C49A6C;
        --cgr-gray: #f4f4f4;
    }

    #primary, .site-main, .content-area {
        width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important;
    }

    /* --- Hero --- */
    .pub-hero-strip {
        background-color: var(--cgr-green-dark);
        padding: 40px 0;
        color: #fff;
        text-align: center;
        margin-bottom: 60px;
        border-bottom: 5px solid var(--cgr-green);
    }
    .pub-hero-title {
        font-size: 2.5rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin: 0;
    }

    /* --- Container --- */
    .pub-container {
        max-width: 1100px;
        margin: 0 auto 80px;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 60px;
        align-items: start;
    }

    /* --- Sidebar (Cover) --- */
    .pub-cover-frame {
        background: #fff;
        padding: 10px;
        border: 1px solid #ddd;
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        border-radius: 4px;
    }
    .pub-cover-frame img {
        width: 100%; height: auto; display: block;
    }
    .pub-placeholder {
        width: 100%; aspect-ratio: 3/4; background: #eee;
        display: flex; align-items: center; justify-content: center;
        color: #ccc;
    }
    .pub-placeholder .dashicons { font-size: 80px; width: 80px; height: 80px; }

    /* --- Content --- */
    .pub-meta-tag {
        display: inline-block;
        background: var(--cgr-gold);
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 15px;
    }
    .pub-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #333;
        margin: 0 0 20px;
        line-height: 1.2;
    }
    .pub-year {
        font-size: 1.2rem; color: #666; margin-bottom: 30px; font-weight: 600;
    }
    .pub-body {
        font-size: 1.1rem; line-height: 1.8; color: #444; margin-bottom: 40px;
    }
    
    /* --- Button --- */
    .pub-download-btn {
        display: inline-flex; align-items: center; gap: 10px;
        background: var(--cgr-green); color: #fff;
        padding: 15px 30px; border-radius: 30px;
        font-weight: 700; text-decoration: none;
        transition: background 0.3s ease, transform 0.2s ease;
    }
    .pub-download-btn:hover {
        background: var(--cgr-green-dark); color: #fff; transform: translateY(-3px);
    }

    @media (max-width: 800px) {
        .pub-container { grid-template-columns: 1fr; gap: 40px; }
        .pub-cover-frame { max-width: 300px; margin: 0 auto; }
        .pub-content { text-align: center; }
    }
</style>

<main id="primary" class="site-main">

    <div class="pub-hero-strip">
        <h1 class="pub-hero-title"><?php echo esc_html($type_name); ?></h1>
    </div>

    <div class="pub-container">
        
        <!-- Sidebar: Cover Image -->
        <aside class="pub-sidebar">
            <div class="pub-cover-frame">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php else : ?>
                    <div class="pub-placeholder">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <article class="pub-content">
            <span class="pub-meta-tag"><?php echo esc_html($type_name); ?></span>
            
            <h1 class="pub-title"><?php the_title(); ?></h1>
            
            <?php if($year): ?>
                <div class="pub-year">Published: <?php echo esc_html($year); ?></div>
            <?php endif; ?>

            <div class="pub-body">
                <?php the_content(); ?>
            </div>

            <?php if($pdf_url): ?>
                <a href="<?php echo esc_url($pdf_url); ?>" class="pub-download-btn" target="_blank">
                    <span class="dashicons dashicons-pdf"></span> Download / View PDF
                </a>
            <?php endif; ?>
        </article>

    </div>

</main>

<?php
endwhile;
get_footer();
?>