<?php
/**
 * Feed Favorites Template Functions
 *
 * Public wrapper functions for theme developers.
 *
 * @package FeedFavorites
 * @since 1.0.0
 */

// Security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get external URL.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string External URL.
 */
function feed_favorites_get_url( $post_id = null ) {
	return Template_Tags::get_external_url( $post_id );
}

/**
 * Display external URL.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_url( $post_id = null ) {
	Template_Tags::the_external_url( $post_id );
}

/**
 * Get link summary.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string Link summary.
 */
function feed_favorites_get_summary( $post_id = null ) {
	return Template_Tags::get_summary( $post_id );
}

/**
 * Display link summary.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_summary( $post_id = null ) {
	Template_Tags::the_summary( $post_id );
}

/**
 * Get link commentary.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string Link commentary.
 */
function feed_favorites_get_commentary( $post_id = null ) {
	return Template_Tags::get_commentary( $post_id );
}

/**
 * Display link commentary.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_commentary( $post_id = null ) {
	Template_Tags::the_commentary( $post_id );
}

/**
 * Get external link HTML.
 *
 * @param int    $post_id Optional. Post ID. Defaults to current post.
 * @param string $text Optional. Link text. Defaults to URL.
 * @param string $class Optional. CSS class.
 * @return string Link HTML.
 */
function feed_favorites_get_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' ) {
	return Template_Tags::get_external_link( $post_id, $text, $class );
}

/**
 * Display external link.
 *
 * @param int    $post_id Optional. Post ID. Defaults to current post.
 * @param string $text Optional. Link text. Defaults to URL.
 * @param string $class Optional. CSS class.
 * @return void
 */
function feed_favorites_the_external_link( $post_id = null, $text = null, $class = 'feed-favorites-external-link' ) {
	Template_Tags::the_external_link( $post_id, $text, $class );
}

/**
 * Get source author.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string Source author.
 */
function feed_favorites_get_source_author( $post_id = null ) {
	return Template_Tags::get_source_author( $post_id );
}

/**
 * Display source author.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_source_author( $post_id = null ) {
	Template_Tags::the_source_author( $post_id );
}

/**
 * Get source site.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string Source site.
 */
function feed_favorites_get_source_site( $post_id = null ) {
	return Template_Tags::get_source_site( $post_id );
}

/**
 * Display source site.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_source_site( $post_id = null ) {
	Template_Tags::the_source_site( $post_id );
}

/**
 * Get source attribution HTML.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return string Attribution HTML.
 */
function feed_favorites_get_source_attribution( $post_id = null ) {
	return Template_Tags::get_source_attribution( $post_id );
}

/**
 * Display source attribution.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return void
 */
function feed_favorites_the_source_attribution( $post_id = null ) {
	Template_Tags::the_source_attribution( $post_id );
}

/**
 * Check if post is a link post.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return bool True if link post.
 */
function feed_favorites_is_link( $post_id = null ) {
	return Template_Tags::is_link( $post_id );
}

/**
 * Check if post is manually created.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return bool True if manually created.
 */
function feed_favorites_is_manual( $post_id = null ) {
	return Template_Tags::is_manual( $post_id );
}

/**
 * Check if post is RSS imported.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 * @return bool True if RSS imported.
 */
function feed_favorites_is_rss_import( $post_id = null ) {
	return Template_Tags::is_rss_import( $post_id );
}

