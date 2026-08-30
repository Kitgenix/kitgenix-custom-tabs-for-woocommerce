<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher;

/**
 * Shared modal field markup used by both Product_Tabs (product-specific tabs)
 * and Settings_UI (global tabs + templates) – kept in one place so the two
 * modal instances can't drift out of sync with each other or with what
 * Tab_Matcher actually reads.
 */
trait Tab_Modal_Fields {

	/**
	 * Visibility conditions block – used for product tabs and global tabs,
	 * never for templates (a template is a reusable snippet, not a live rule).
	 */
	private static function render_visibility_modal_fields( string $id_prefix ): void {
		$roles = function_exists( 'wp_roles' ) ? wp_roles()->roles : [];
		?>
		<div class="kitgenix-custom-tabs-for-woocommerce-field kitgenix-custom-tabs-for-woocommerce-is-full" data-kitgenix-custom-tabs-for-woocommerce-visibility-section="1">
			<label><?php esc_html_e( 'Visibility conditions (optional)', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
			<p class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'Leave any of these on the default option to skip that condition.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
			<div class="kitgenix-custom-tabs-for-woocommerce-modal-grid">
				<div class="kitgenix-custom-tabs-for-woocommerce-field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>_visibility_auth"><?php esc_html_e( 'Visitor', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
					<select id="<?php echo esc_attr( $id_prefix ); ?>_visibility_auth" data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_auth">
						<option value=""><?php esc_html_e( 'Anyone', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="logged_in"><?php esc_html_e( 'Logged-in only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="logged_out"><?php esc_html_e( 'Logged-out only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
					</select>
				</div>
				<div class="kitgenix-custom-tabs-for-woocommerce-field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>_visibility_roles"><?php esc_html_e( 'User role (logged-in only)', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
					<select id="<?php echo esc_attr( $id_prefix ); ?>_visibility_roles" multiple size="4" data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_roles">
						<?php foreach ( $roles as $role_key => $role ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( (string) ( $role['name'] ?? $role_key ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="kitgenix-custom-tabs-for-woocommerce-field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>_visibility_stock"><?php esc_html_e( 'Stock status', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
					<select id="<?php echo esc_attr( $id_prefix ); ?>_visibility_stock" data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_stock">
						<option value=""><?php esc_html_e( 'Any', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="in_stock"><?php esc_html_e( 'In stock only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="out_of_stock"><?php esc_html_e( 'Out of stock only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
					</select>
				</div>
				<div class="kitgenix-custom-tabs-for-woocommerce-field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>_visibility_purchasable"><?php esc_html_e( 'Purchasable', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
					<select id="<?php echo esc_attr( $id_prefix ); ?>_visibility_purchasable" data-kitgenix-custom-tabs-for-woocommerce-modal-field="visibility_purchasable">
						<option value=""><?php esc_html_e( 'Any', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="yes"><?php esc_html_e( 'Purchasable only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="no"><?php esc_html_e( 'Not purchasable only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
					</select>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Product targeting block – global tabs only. Products use WooCommerce's own
	 * AJAX product search (wc-enhanced-select / woocommerce_json_search_products,
	 * the same mechanism the core Linked Products tab uses), so no custom AJAX
	 * endpoint is needed for search-as-you-type. Categories/tags are rendered as
	 * plain option lists enhanced client-side (no AJAX – term counts are small
	 * enough that this is cheap and keeps the query surface minimal).
	 */
	private static function render_target_modal_fields(): void {
		?>
		<div class="kitgenix-custom-tabs-for-woocommerce-field kitgenix-custom-tabs-for-woocommerce-is-full" data-kitgenix-custom-tabs-for-woocommerce-target-section="1">
			<label><?php esc_html_e( 'Product targeting (optional, global tabs only)', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
			<p class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'Leave a row empty to apply this tab to every product for that dimension. Exclude always wins over Include when both would otherwise match.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>

			<?php foreach ( self::target_dimensions() as $dimension => $label ) : ?>
				<div class="kitgenix-custom-tabs-for-woocommerce-target-row" data-kitgenix-custom-tabs-for-woocommerce-target-row="<?php echo esc_attr( $dimension ); ?>">
					<div class="kitgenix-custom-tabs-for-woocommerce-target-row__label"><?php echo esc_html( $label ); ?></div>
					<select data-kitgenix-custom-tabs-for-woocommerce-target-mode="<?php echo esc_attr( $dimension ); ?>" class="kitgenix-custom-tabs-for-woocommerce-target-mode">
						<option value="include"><?php esc_html_e( 'Include only', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
						<option value="exclude"><?php esc_html_e( 'Exclude', 'kitgenix-custom-tabs-for-woocommerce' ); ?></option>
					</select>
					<?php if ( 'products' === $dimension ) : ?>
						<select multiple class="wc-product-search kitgenix-custom-tabs-for-woocommerce-target-values" data-kitgenix-custom-tabs-for-woocommerce-target-values="products" data-action="woocommerce_json_search_products" data-placeholder="<?php echo esc_attr__( 'Search products&hellip;', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" style="width:100%;"></select>
					<?php elseif ( 'types' === $dimension ) : ?>
						<select multiple size="4" class="kitgenix-custom-tabs-for-woocommerce-target-values" data-kitgenix-custom-tabs-for-woocommerce-target-values="types">
							<?php foreach ( Tab_Matcher::allowed_product_types() as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<select multiple size="6" class="kitgenix-custom-tabs-for-woocommerce-target-values" data-kitgenix-custom-tabs-for-woocommerce-target-values="<?php echo esc_attr( $dimension ); ?>" style="width:100%;">
							<?php foreach ( self::target_taxonomy_terms( 'categories' === $dimension ? 'product_cat' : 'product_tag' ) as $term ) : ?>
								<option value="<?php echo esc_attr( (string) $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @return array<string,string>
	 */
	private static function target_dimensions(): array {
		return [
			'products'   => __( 'Products', 'kitgenix-custom-tabs-for-woocommerce' ),
			'categories' => __( 'Categories', 'kitgenix-custom-tabs-for-woocommerce' ),
			'tags'       => __( 'Tags', 'kitgenix-custom-tabs-for-woocommerce' ),
			'types'      => __( 'Product types', 'kitgenix-custom-tabs-for-woocommerce' ),
		];
	}

	/**
	 * @return array<int,\WP_Term>
	 */
	private static function target_taxonomy_terms( string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 500,
				'orderby'    => 'name',
			]
		);

		return is_array( $terms ) ? $terms : [];
	}
}
