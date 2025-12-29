<?php
/**
 * Template: Single Press Coverage
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    $pub_name = get_post_meta( get_the_ID(), '_cgr_press_publication', true );
    $pub_date = get_post_meta( get_the_ID(), '_cgr_press_date', true );
    $pub_url  = get_post_meta( get_the_ID(), '_cgr_press_url', true );

    if ( $pub_date ) {
        $pub_date_fmt = date_i18n( 'F j, Y', strtotime( $pub_date ) );
    } else {
        $pub_date_fmt = '';
    }
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
    .press-hero-strip {
        background-color: #f8f9fa;
        padding: 40px 0;
        color: #333;
        text-align: center;
        margin-bottom: 60px;
        border-bottom: 1px solid #eee;
    }
    .press-hero-title {
        font-size: 2rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0; color: var(--cgr-green);
    }

    /* --- Container --- */
    .press-container {
        max-width: 900px;
        margin: 0 auto 80px;
        padding: 0 20px;
    }

    /* --- Header Info --- */
    .press-header {
        text-align: center; margin-bottom: 40px;
    }
    .press-meta {
        font-size: 1.1rem; color: #666; margin-bottom: 15px; font-weight: 600;
    }
    .press-pub-name { color: var(--cgr-gold); text-transform: uppercase; }
    
    .press-title {
        font-size: 2.8rem; font-weight: 800; color: #222; line-height: 1.2; margin-bottom: 30px;
    }

    /* --- Featured Image --- */
    .press-image {
        width: 100%; height: auto; border-radius: 8px; margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    /* --- Content --- */
    .press-body {
        font-size: 1.2rem; line-height: 1.8; color: #333; margin-bottom: 50px;
    }
    
    /* --- Button --- */
    .press-link-btn {
        display: inline-block;
        background: #fff; color: var(--cgr-green);
        border: 2px solid var(--cgr-green);
        padding: 12px 25px; border-radius: 30px;
        font-weight: 700; text-decoration: none;
        transition: all 0.3s ease;
    }
    .press-link-btn:hover {
        background: var(--cgr-green); color: #fff;
    }

</style>

<main id="primary" class="site-main">

    <div class="press-hero-strip">
        <h1 class="press-hero-title">In The News</h1>
    </div>

    <div class="press-container">
        
        <header class="press-header">
            <div class="press-meta">
                <?php if($pub_name): ?><span class="press-pub-name"><?php echo esc_html($pub_name); ?></span><?php endif; ?>
                <?php if($pub_name && $pub_date_fmt): ?> | <?php endif; ?>
                <?php if($pub_date_fmt): ?><span><?php echo esc_html($pub_date_fmt); ?></span><?php endif; ?>
            </div>
            <h1 class="press-title"><?php the_title(); ?></h1>
        </header>

        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail('large', array('class' => 'press-image')); ?>
        <?php endif; ?>

        <article class="press-body">
            <?php the_content(); ?>
        </article>

        <?php if($pub_url): ?>
            <div style="text-align: center;">
                <a href="<?php echo esc_url($pub_url); ?>" class="press-link-btn" target="_blank">
                    Read Original Article &rarr;
                </a>
            </div>
        <?php endif; ?>

    </div>

</main>

<?php
endwhile;
get_footer();
?>