<?php
/**
 * Tests for Import format detection and Sync cron schedules.
 *
 * @package FeedFavorites
 */

/**
 * Import and Sync unit tests.
 */
class Import_Sync_Test extends WP_UnitTestCase {

	/**
	 * @var Import
	 */
	private $import;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->import = new Import();
	}

	/**
	 * Simple JSON array format is normalized.
	 */
	public function test_import_detect_simple_format() {
		$data = array(
			array(
				'id'        => '1',
				'title'     => 'Hello',
				'url'       => 'https://example.org/article',
				'content'   => 'Summary text',
				'published' => '2020-01-01 12:00:00',
				'author'    => 'Ada',
			),
		);

		$result = $this->import->detect_and_extract_entries( $data );

		$this->assertIsArray( $result );
		$this->assertSame( 'Hello', $result[0]['title'] );
		$this->assertSame( 'https://example.org/article', $result[0]['url'] );
		$this->assertSame( 'Summary text', $result[0]['content'] );
		$this->assertSame( 'Ada', $result[0]['author'] );
	}

	/**
	 * FreshRSS-style items format is normalized.
	 */
	public function test_import_detect_freshrss_format() {
		$data = array(
			'items' => array(
				array(
					'title'     => 'Item',
					'canonical' => array(
						array( 'href' => 'https://example.com/x' ),
					),
					'content'   => array( 'content' => 'Body' ),
					'published' => 946684800,
					'author'    => 'Bob',
					'origin'    => array(
						'title'   => 'Feed Name',
						'htmlUrl' => 'https://feed.example',
					),
				),
			),
		);

		$result = $this->import->detect_and_extract_entries( $data );

		$this->assertIsArray( $result );
		$this->assertSame( 'Item', $result[0]['title'] );
		$this->assertSame( 'https://example.com/x', $result[0]['url'] );
		$this->assertSame( 'Body', $result[0]['content'] );
		$this->assertSame( 'Feed Name', $result[0]['feed_title'] );
	}

	/**
	 * Unrecognized structure returns WP_Error.
	 */
	public function test_import_detect_invalid_format() {
		$result = $this->import->detect_and_extract_entries( array( 'foo' => 'bar' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_format', $result->get_error_code() );
	}

	/**
	 * Sync registers custom cron interval from configuration.
	 */
	public function test_sync_add_cron_intervals_uses_config() {
		update_option( 'feed_favorites_sync_interval', '900' );

		$sync      = new Sync();
		$schedules = $sync->add_cron_intervals( array() );

		$this->assertArrayHasKey( 'feed_favorites_interval', $schedules );
		$this->assertSame( 900, $schedules['feed_favorites_interval']['interval'] );
	}
}
