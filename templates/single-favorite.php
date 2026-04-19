<?php
/**
 * Single Favorite Post Template
 *
 * Default template for displaying favorite link posts.
 * Theme developers can override this by creating single-favorite.php in their theme.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'get_header' ) ) {
	get_header();
}
?>

<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'feed-favorite-post' ); ?>>
			<header class="entry-header">
				<?php
				$show_emoji = get_post_meta( get_the_ID(), '_feed_favorites_show_emoji', true );
				if ( '' === $show_emoji ) {
					$show_emoji = Config::get( 'default_show_emoji', true );
				} else {
					$show_emoji = (bool) $show_emoji;
				}

				$entry_title = get_the_title();
				$emoji       = '';

				if ( ! empty( $entry_title ) ) {
					// Extract emoji from title if present (Unicode emoji range).
					$emoji_pattern = '/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u';
					if ( preg_match( $emoji_pattern, $entry_title, $matches ) ) {
						$emoji = $matches[0];
						// Remove emoji from title.
						$entry_title = preg_replace( $emoji_pattern, '', $entry_title );
						$entry_title = trim( $entry_title );
					}

					// If show_emoji is true and no emoji found, add default emoji.
					if ( $show_emoji && empty( $emoji ) ) {
						$emoji = '⭐'; // Default star emoji.
					}

					// If show_emoji is false, don't display emoji even if found.
					if ( ! $show_emoji ) {
						$emoji = '';
					}
				}
				?>
				<h1 class="entry-title">
					<?php if ( ! empty( $emoji ) ) : ?>
						<span class="entry-emoji"><?php echo esc_html( $emoji ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( $entry_title ); ?>
				</h1>
			</header>

			<div class="entry-content">
				<?php
				// Link summary section.
				$link_summary = Template_Tags::get_summary();
				if ( ! empty( $link_summary ) ) :
					?>
					<div class="feed-favorite-summary">
						<?php Template_Tags::the_summary(); ?>
					</div>
					<?php
				endif;

				// Commentary section.
				$link_commentary = Template_Tags::get_commentary();
				if ( ! empty( $link_commentary ) ) :
					?>
					<div class="feed-favorite-commentary">
						<?php Template_Tags::the_commentary(); ?>
					</div>
					<?php
				endif;

				// External link button.
				$external_url = Template_Tags::get_external_url();
				if ( ! empty( $external_url ) ) :
					$link_text = __( 'Read Original', 'feed-favorites' );
					?>
					<div class="feed-favorite-link">
						<?php Template_Tags::the_external_link( null, $link_text, 'feed-favorites-external-link button' ); ?>
					</div>
					<?php
				endif;

				// Source attribution.
				$source_author = Template_Tags::get_source_author();
				$source_site   = Template_Tags::get_source_site();
				if ( ! empty( $source_author ) || ! empty( $source_site ) ) :
					?>
					<div class="feed-favorite-source">
						<?php Template_Tags::the_source_attribution(); ?>
					</div>
					<?php
				endif;
				?>
			</div>

			<footer class="entry-footer">
				<?php
				// Post meta (date, author, etc.).
				?>
				<span class="posted-on">
					<?php
					/* translators: %s: Post date */
					printf( esc_html__( 'Published on %s', 'feed-favorites' ), get_the_date() );
					?>
				</span>
			</footer>
		</article>

		<?php
	endwhile;
	?>
</main>

<?php
if ( function_exists( 'get_footer' ) ) {
	get_footer();
}

