<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a saved tab row (global tab or product-specific tab) should
 * render for a given product/visitor, and provides the matching sanitizers
 * for the `target` (global tabs only) and `visibility` (any tab) row fields.
 *
 * Matching is designed to run once per tab per product-page render using data
 * already available on the loaded `$product` object (get_type(), is_in_stock(),
 * is_purchasable()) and cached taxonomy lookups (has_term()) – no additional
 * database queries are issued per match, so this stays cheap even with many
 * global tabs and a large catalogue.
 */
final class Tab_Matcher {

	/**
	 * Default (empty/no-restriction) target rule set.
	 *
	 * @return array<string,array{include:array<int,int|string>,exclude:array<int,int|string>}>
	 */
	public static function default_target(): array {
		// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- plugin's own target-rule shape, never passed to get_posts()/WP_Query.
		return [
			'products'   => [ 'include' => [], 'exclude' => [] ],
			// `include_children` defaults to false here so that a global tab's
			// `target` array saved before this flag existed (which always has a
			// `categories` key, just never this one) keeps matching exactly the
			// same products it always did when it's read back without being
			// resaved through sanitize_target() – see matches_target().
			'categories' => [ 'include' => [], 'exclude' => [], 'include_children' => false ],
			'tags'       => [ 'include' => [], 'exclude' => [] ],
			'types'      => [ 'include' => [], 'exclude' => [] ],
		];
		// phpcs:enable
	}

	/**
	 * Default (no-restriction) visibility condition set.
	 *
	 * @return array{auth:string,roles:array<int,string>,stock:string,purchasable:string}
	 */
	public static function default_visibility(): array {
		return [
			'auth'        => '',
			'roles'       => [],
			'stock'       => '',
			'purchasable' => '',
		];
	}

	/** Product types selectable for targeting. */
	public static function allowed_product_types(): array {
		return [ 'simple', 'variable', 'grouped', 'external' ];
	}

	/**
	 * Sanitize a raw `target` payload (global tabs only). Any dimension not
	 * present, or containing only invalid values, sanitizes to "no restriction"
	 * for that dimension – which preserves pre-2.0.0 global tabs (no `target`
	 * key at all) as "applies to every product", unchanged.
	 *
	 * @param mixed $raw
	 * @return array<string,array{include:array<int,int|string>,exclude:array<int,int|string>}>
	 */
	public static function sanitize_target( $raw ): array {
		$out = self::default_target();
		if ( ! is_array( $raw ) ) {
			return $out;
		}

		foreach ( [ 'products', 'categories', 'tags' ] as $dimension ) {
			if ( ! isset( $raw[ $dimension ] ) || ! is_array( $raw[ $dimension ] ) ) {
				continue;
			}
			foreach ( [ 'include', 'exclude' ] as $side ) {
				$ids = isset( $raw[ $dimension ][ $side ] ) && is_array( $raw[ $dimension ][ $side ] )
					? $raw[ $dimension ][ $side ]
					: [];
				$out[ $dimension ][ $side ] = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			}
		}

		if ( isset( $raw['categories']['include_children'] ) ) {
			$out['categories']['include_children'] = ! empty( $raw['categories']['include_children'] );
		}

		if ( isset( $raw['types'] ) && is_array( $raw['types'] ) ) {
			$allowed = self::allowed_product_types();
			foreach ( [ 'include', 'exclude' ] as $side ) {
				$types = isset( $raw['types'][ $side ] ) && is_array( $raw['types'][ $side ] ) ? $raw['types'][ $side ] : [];
				$types = array_map( 'sanitize_key', $types );
				$out['types'][ $side ] = array_values( array_intersect( $allowed, $types ) );
			}
		}

		return $out;
	}

	/**
	 * Sanitize a raw `visibility` payload (any tab row).
	 *
	 * @param mixed $raw
	 * @return array{auth:string,roles:array<int,string>,stock:string,purchasable:string}
	 */
	public static function sanitize_visibility( $raw ): array {
		$out = self::default_visibility();
		if ( ! is_array( $raw ) ) {
			return $out;
		}

		$auth = isset( $raw['auth'] ) ? sanitize_key( (string) $raw['auth'] ) : '';
		$out['auth'] = in_array( $auth, [ 'logged_in', 'logged_out' ], true ) ? $auth : '';

		if ( isset( $raw['roles'] ) && is_array( $raw['roles'] ) ) {
			$valid_roles = function_exists( 'wp_roles' ) ? array_keys( wp_roles()->roles ) : [];
			$roles       = array_map( 'sanitize_key', $raw['roles'] );
			$out['roles'] = array_values( array_intersect( $valid_roles, $roles ) );
		}

		$stock = isset( $raw['stock'] ) ? sanitize_key( (string) $raw['stock'] ) : '';
		$out['stock'] = in_array( $stock, [ 'in_stock', 'out_of_stock' ], true ) ? $stock : '';

		$purchasable = isset( $raw['purchasable'] ) ? sanitize_key( (string) $raw['purchasable'] ) : '';
		$out['purchasable'] = in_array( $purchasable, [ 'yes', 'no' ], true ) ? $purchasable : '';

		return $out;
	}

	/**
	 * Whether a global tab's targeting rules allow it on the given product.
	 * Product-specific tabs and templates don't carry a `target` key and are
	 * always considered a match here (targeting only ever restricts global tabs).
	 *
	 * @param array<string,mixed> $row
	 */
	public static function matches_target( array $row, \WC_Product $product ): bool {
		if ( ! isset( $row['target'] ) || ! is_array( $row['target'] ) ) {
			return true;
		}

		$target     = array_merge( self::default_target(), $row['target'] );
		// The top-level array_merge() above replaces 'categories' wholesale
		// whenever the saved row already has that key – true for every global
		// tab saved since targeting existed – so it alone would silently drop
		// a default like `include_children` for a row saved before that flag
		// existed. Merge the categories sub-array's own keys explicitly instead.
		$target['categories'] = array_merge(
			[ 'include' => [], 'exclude' => [], 'include_children' => false ],
			is_array( $row['target']['categories'] ?? null ) ? $row['target']['categories'] : []
		);
		$product_id = (int) $product->get_id();

		$category_exclude = $target['categories']['exclude'];
		$category_include = $target['categories']['include'];
		if ( ! empty( $target['categories']['include_children'] ) ) {
			$category_exclude = self::expand_with_children( $category_exclude, 'product_cat' );
			$category_include = self::expand_with_children( $category_include, 'product_cat' );
		}

		// Exclusions always win, checked first and independently of include rules.
		if ( self::in_list( $product_id, $target['products']['exclude'] ) ) {
			return false;
		}
		if ( self::has_any_term( $product_id, 'product_cat', $category_exclude ) ) {
			return false;
		}
		if ( self::has_any_term( $product_id, 'product_tag', $target['tags']['exclude'] ) ) {
			return false;
		}
		if ( in_array( $product->get_type(), $target['types']['exclude'], true ) ) {
			return false;
		}

		// A dimension with a non-empty include list restricts to that list; a
		// dimension left empty imposes no restriction. All non-empty dimensions
		// must match (AND across dimensions, OR within a dimension's own list).
		if ( ! empty( $target['products']['include'] ) && ! self::in_list( $product_id, $target['products']['include'] ) ) {
			return false;
		}
		if ( ! empty( $category_include ) && ! self::has_any_term( $product_id, 'product_cat', $category_include ) ) {
			return false;
		}
		if ( ! empty( $target['tags']['include'] ) && ! self::has_any_term( $product_id, 'product_tag', $target['tags']['include'] ) ) {
			return false;
		}
		if ( ! empty( $target['types']['include'] ) && ! in_array( $product->get_type(), $target['types']['include'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Expand a list of term IDs to include every descendant term, for the
	 * "Include sub-categories" target option. Cached in the object cache
	 * (5 minutes – cheap to recompute, and category trees rarely change
	 * minute to minute) since this would otherwise run on every product page
	 * load for every global tab that targets categories with this flag on.
	 *
	 * @param array<int,int> $term_ids
	 * @return array<int,int>
	 */
	private static function expand_with_children( array $term_ids, string $taxonomy ): array {
		if ( empty( $term_ids ) ) {
			return $term_ids;
		}

		$cache_key_ids = $term_ids;
		sort( $cache_key_ids );
		$cache_key = 'kctw_term_children_' . $taxonomy . '_' . md5( implode( ',', $cache_key_ids ) );

		$cached = wp_cache_get( $cache_key, 'kitgenix_custom_tabs_for_woocommerce' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$expanded = $term_ids;
		foreach ( $term_ids as $term_id ) {
			$children = get_term_children( $term_id, $taxonomy );
			if ( is_array( $children ) ) {
				foreach ( $children as $child_id ) {
					$expanded[] = (int) $child_id;
				}
			}
		}
		$expanded = array_values( array_unique( array_map( 'absint', $expanded ) ) );

		wp_cache_set( $cache_key, $expanded, 'kitgenix_custom_tabs_for_woocommerce', 5 * MINUTE_IN_SECONDS );

		return $expanded;
	}

	/**
	 * Whether a tab's visibility conditions allow it for the current
	 * visitor/product. Works for global tabs and product-specific tabs alike.
	 *
	 * @param array<string,mixed> $row
	 */
	public static function is_visible( array $row, \WC_Product $product ): bool {
		if ( isset( $row['enabled'] ) && empty( $row['enabled'] ) ) {
			return false;
		}

		if ( ! isset( $row['visibility'] ) || ! is_array( $row['visibility'] ) ) {
			return true;
		}

		$visibility = array_merge( self::default_visibility(), $row['visibility'] );

		if ( 'logged_in' === $visibility['auth'] && ! is_user_logged_in() ) {
			return false;
		}
		if ( 'logged_out' === $visibility['auth'] && is_user_logged_in() ) {
			return false;
		}

		if ( ! empty( $visibility['roles'] ) && 'logged_out' !== $visibility['auth'] ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}
			$user = wp_get_current_user();
			if ( ! array_intersect( $visibility['roles'], (array) $user->roles ) ) {
				return false;
			}
		}

		if ( 'in_stock' === $visibility['stock'] && ! $product->is_in_stock() ) {
			return false;
		}
		if ( 'out_of_stock' === $visibility['stock'] && $product->is_in_stock() ) {
			return false;
		}

		if ( 'yes' === $visibility['purchasable'] && ! $product->is_purchasable() ) {
			return false;
		}
		if ( 'no' === $visibility['purchasable'] && $product->is_purchasable() ) {
			return false;
		}

		return true;
	}

	/**
	 * Combined eligibility check: target (global tabs) + visibility (all tabs).
	 * This is the single entry point Frontend\Tabs should call per row.
	 *
	 * @param array<string,mixed> $row
	 */
	public static function is_eligible( array $row, \WC_Product $product ): bool {
		if ( ! self::matches_target( $row, $product ) ) {
			return false;
		}
		if ( ! self::is_visible( $row, $product ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<int,int> $ids
	 */
	private static function in_list( int $needle, array $ids ): bool {
		return in_array( $needle, $ids, true );
	}

	/**
	 * @param array<int,int> $term_ids
	 */
	private static function has_any_term( int $product_id, string $taxonomy, array $term_ids ): bool {
		if ( empty( $term_ids ) ) {
			return false;
		}

		return (bool) has_term( $term_ids, $taxonomy, $product_id );
	}
}
