<?php
/**
 * The template for displaying 404 pages (not found)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">

    <section class="error-404 not-found" style="padding: 150px 0; text-align: center;">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title" style="font-size: 4rem; color: var(--cgr-green-primary); margin-bottom: 1rem;">404</h1>
                <h2 style="font-size: 2rem; margin-bottom: 2rem;">Page Not Found</h2>
            </header>

            <div class="page-content">
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">It looks like nothing was found at this location. Maybe try a search?</p>
                
                <?php get_search_form(); ?>
                
                <div style="margin-top: 3rem;">
                    <a href="<?php echo home_url(); ?>" class="btn btn-primary">Back to Home</a>
                </div>
                
                <!-- Debug Info -->
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <div style="margin-top: 50px; padding: 20px; background: #f0f0f0; border: 1px solid #ccc; text-align: left; font-family: monospace;">
                        <strong>Debug Info:</strong><br>
                        Requested URL: <?php echo $_SERVER['REQUEST_URI']; ?><br>
                        Template: 404.php<br>
                        Query Vars: <pre><?php print_r($wp_query->query_vars); ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();
?>
