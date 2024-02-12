<?php

namespace Myrotvorets\WordPress\Sitemaps;

use WildWolf\Utils\Singleton;
use WP_Post_Type;
use WP_Sitemaps_Provider;

final class Plugin {
	use Singleton;

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
		add_action( 'init', [ $this, 'init' ] );
	}

	public function plugins_loaded(): void {
		add_filter( 'wp_sitemaps_enabled', [ $this, 'wp_sitemaps_enabled' ] );
	}

	public function init(): void {
		add_action( 'wp_sitemaps_init', [ WP_Sitemaps_Criminals::class, 'register' ] );
		add_filter( 'wp_sitemaps_post_types', [ $this, 'wp_sitemaps_post_types' ] );
		add_filter( 'wp_sitemaps_max_urls', [ $this, 'wp_sitemaps_max_urls' ], 10, 2 );
		add_filter( 'wp_sitemaps_add_provider', [ $this, 'wp_sitemaps_add_provider' ], 10, 2 );
	}

	/**
	 * Filters whether XML Sitemaps are enabled or not.
	 *
	 * @param bool $is_enabled Whether XML Sitemaps are enabled or not.
	 * @return bool
	 */
	public function wp_sitemaps_enabled( $is_enabled ): bool {
		/** @psalm-suppress RiskyTruthyFalsyComparison, RedundantCondition */
		if ( $is_enabled && ! empty( $_SERVER['HTTP_HOST'] ) && is_string( $_SERVER['HTTP_HOST'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$host       = strtolower( $_SERVER['HTTP_HOST'] );
			$is_enabled = 'myrotvorets.center' === $host || is_user_logged_in();
		}

		return (bool) $is_enabled;
	}

	/**
	 * Filters the list of post object sub types available within the sitemap.
	 *
	 * @param WP_Post_Type[] $post_types Array of registered post type objects keyed by their name.
	 * @return WP_Post_Type[]
	 * @psalm-param array<string, WP_Post_Type> $post_types
	 * @psalm-return array<string, WP_Post_Type>
	 */
	public function wp_sitemaps_post_types( array $post_types ): array {
		unset( $post_types['criminal'] );
		return $post_types;
	}

	/**
	 * Filters the maximum number of URLs displayed on a sitemap.
	 *
	 * @param int    $max_urls    The maximum number of URLs included in a sitemap. Default 2000.
	 * @param string $object_type Object type for sitemap to be filtered (e.g. 'post', 'term', 'user').
	 * @return int
	 */
	public function wp_sitemaps_max_urls( $max_urls, string $object_type ): int {
		$max_urls = ( 'criminal' === $object_type ) ? 10000 : (int) $max_urls;
		return $max_urls;
	}

	/**
	 * Filters the sitemap provider before it is added.
	 *
	 * @param WP_Sitemaps_Provider $provider Instance of a WP_Sitemaps_Provider.
	 * @param string               $name     Name of the sitemap provider.
	 * @return WP_Sitemaps_Provider|null
	 */
	public function wp_sitemaps_add_provider( ?WP_Sitemaps_Provider $provider, string $name ): ?WP_Sitemaps_Provider {
		if ( 'taxonomies' === $name || 'users' === $name ) {
			$provider = null;
		}

		return $provider;
	}
}
