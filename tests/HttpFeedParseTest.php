<?php
/**
 * Tests for RSS/Atom feed parsing.
 *
 * @package FeedFavorites
 */

/**
 * Http::parse_feed_document tests.
 */
class Http_Feed_Parse_Test extends WP_UnitTestCase {

	/**
	 * RSS 2.0 document yields items.
	 */
	public function test_parse_rss_document() {
		$body = '<?xml version="1.0"?>
		<rss version="2.0"><channel>
			<item>
				<title>T</title>
				<link>https://example.test/a</link>
				<description>D</description>
				<pubDate>Mon, 01 Jan 2024 00:00:00 GMT</pubDate>
			</item>
		</channel></rss>';

		$parsed = Http::parse_feed_document( $body );

		$this->assertIsArray( $parsed );
		$this->assertSame( 'rss', $parsed['type'] );
		$this->assertCount( 1, $parsed['items'] );
	}

	/**
	 * Atom document yields entries.
	 */
	public function test_parse_atom_document() {
		$body = '<?xml version="1.0" encoding="UTF-8"?>
		<feed xmlns="http://www.w3.org/2005/Atom">
			<entry>
				<title>A</title>
				<link rel="alternate" href="https://example.test/b"/>
				<updated>2024-01-01T12:00:00Z</updated>
				<summary>S</summary>
			</entry>
		</feed>';

		$parsed = Http::parse_feed_document( $body );

		$this->assertIsArray( $parsed );
		$this->assertSame( 'atom', $parsed['type'] );
		$this->assertCount( 1, $parsed['items'] );
	}
}
