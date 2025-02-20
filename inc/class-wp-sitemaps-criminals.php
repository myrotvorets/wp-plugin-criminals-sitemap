<?php

namespace Myrotvorets\WordPress\Sitemaps;

use WP_Sitemaps_Provider;
use wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined

class WP_Sitemaps_Criminals extends WP_Sitemaps_Provider {
	public const int CACHE_TTL = 300;

	public static function register(): void {
		$provider = new self();
		wp_register_sitemap_provider( $provider->name, $provider );
	}

	private function __construct() {
		$this->name        = 'criminals';
		$this->object_type = 'criminal';
	}

	/**
	 * Gets a URL list for a sitemap.
	 *
	 * @param int    $page_num       Page of results.
	 * @param string $object_subtype Optional. Object subtype name. Default empty.
	 * @return array[] Array of URL information for a sitemap.
	 * @psalm-return list<array{loc: string, lastmod: string}>
	 */
	public function get_url_list( $page_num, $object_subtype = '' ): array {
		/** @var wpdb $wpdb */
		global $wpdb;

		$key = sprintf( 'criminals:sitemap:page:%u', $page_num );
		/** @var mixed */
		$result = wp_cache_get( $key, 'myrotvorets' );
		if ( ! is_array( $result ) || empty( $result ) ) {
			$limit  = wp_sitemaps_get_max_urls( $this->object_type );
			$offset = ( $page_num - 1 ) * $limit;
			$result = [];

			/** @psalm-var object{slug: string, last_modified: string}[] */
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT slug, last_modified FROM criminals WHERE name <> '' AND status = 'publish' ORDER BY id LIMIT %d, %d", $offset, $limit
				)
			);

			foreach ( $entries as $row ) {
				$result[] = [
					'loc'     => 'https://myrotvorets.center/criminal/' . $row->slug . '/',
					'lastmod' => gmdate( 'c', (int) strtotime( $row->last_modified ) ),
				];
			}

			wp_cache_set( $key, $result, 'myrotvorets', self::CACHE_TTL );
		}

		/** @psalm-var list<array{loc: string, lastmod: string}> */
		return $result;
	}

	/**
	 * Gets the max number of pages available for the object type.
	 *
	 * @param string $object_subtype Optional. Object subtype. Default empty.
	 * @return int Total number of pages.
	 */
	public function get_max_num_pages( $object_subtype = '' ): int {
		/** @var wpdb */
		global $wpdb;

		$count = (int) wp_cache_get( 'criminals:active', 'myrotvorets' );

		if ( ! $count ) {
			$count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM criminals WHERE status = 'publish' AND name <> ''"
			);
			
			wp_cache_set( 'criminals:active', $count, 'myrotvorets', self::CACHE_TTL );
		}

		return (int) ceil( $count / wp_sitemaps_get_max_urls( $this->object_type ) );
	}

	/**
	 * Lists sitemap pages exposed by this provider.
	 *
	 * The returned data is used to populate the sitemap entries of the index.
	 *
	 * @return array[] Array of sitemap entries.
	 * @psalm-return list<array{loc: string, lastmod: string}>
	 */
	public function get_sitemap_entries(): array {
		/** @var wpdb $wpdb */
		global $wpdb;

		/** @var mixed */
		$sitemaps = wp_cache_get( 'criminals:sitemaps', 'myrotvorets' );
		if ( ! is_array( $sitemaps ) || empty( $sitemaps ) ) {
			$sitemaps  = [];
			$limit     = wp_sitemaps_get_max_urls( $this->object_type );
			$num_pages = $this->get_max_num_pages();
			$last_id   = 0;
	
			for ( $page = 1; $page <= $num_pages; ++$page ) {
				/** @psalm-var object{id: numeric-string|int, lastmod: string} */
				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT MAX(id) AS id, MAX(last_modified) AS lastmod FROM (SELECT id, last_modified FROM criminals WHERE name <> '' AND status = 'publish' AND id > %d ORDER BY id LIMIT %d) AS t",
						$last_id, $limit
					)
				);

				$last_id = $row->id;

				$sitemap_entry = [
					'loc'     => $this->get_sitemap_url( '', $page ),
					'lastmod' => gmdate( 'c', (int) strtotime( $row->lastmod ) ),
				];

				$sitemaps[] = $sitemap_entry;
			}

			wp_cache_set( 'criminals:sitemaps', $sitemaps, 'myrotvorets', self::CACHE_TTL );
		}

		/** @psalm-var list<array{loc: string, lastmod: string}> */
		return $sitemaps;
	}
}
