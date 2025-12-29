<?php
/**
 * Template Name: Single Event
 * Post Type: cgr_event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();
?>

<div class="cgr-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    <?php
    while ( have_posts() ) :
        the_post();
        
        $event_date = get_post_meta( get_the_ID(), '_cgr_event_date', true );
        $event_location = get_post_meta( get_the_ID(), '_cgr_event_location', true );
        $event_link = get_post_meta( get_the_ID(), '_cgr_event_link', true );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('cgr-single-event'); ?>>
            
            <div class="cgr-event-header" style="margin-bottom: 30px;">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="cgr-event-image" style="margin-bottom: 20px; border-radius: 8px; overflow: hidden;">
                        <?php the_post_thumbnail( 'full', array( 'style' => 'width: 100%; height: auto; display: block;' ) ); ?>
                    </div>
                <?php endif; ?>

                <h1 class="cgr-event-title" style="font-size: 2.5rem; color: var(--cgr-primary, #333); margin-bottom: 15px;"><?php the_title(); ?></h1>

                <div class="cgr-event-meta" style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid var(--cgr-primary, #2c5e2e); display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                    
                    <?php if ( $event_date ) : ?>
                        <div class="meta-item">
                            <strong>Date:</strong> 
                            <span><?php echo date_i18n( get_option( 'date_format' ), strtotime( $event_date ) ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $event_location ) : ?>
                        <div class="meta-item">
                            <strong>Location:</strong> 
                            <span><?php echo esc_html( $event_location ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $event_link ) : ?>
                        <div class="meta-item">
                            <a href="<?php echo esc_url( $event_link ); ?>" target="_blank" class="button" style="background-color: var(--cgr-primary, #2c5e2e); color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                View Event Link &rarr;
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="cgr-event-content entry-content" style="font-size: 1.1rem; line-height: 1.6;">
                <?php the_content(); ?>
            </div>

        </article>

    <?php endwhile; ?>
</div>

<?php
get_footer();
