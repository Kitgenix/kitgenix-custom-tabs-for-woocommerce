<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Event_Log;
use KitgenixCustomTabsForWooCommerce\Core\Settings;
use KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher;

final class Product_Tabs {
	use Tab_Modal_Fields;
	private const META_KEY = 'kitgenix_custom_tabs_for_woocommerce_tabs';
	private const NONCE_ACTION = 'kitgenix_custom_tabs_for_woocommerce_tabs_save';
	private const NONCE_NAME = 'kitgenix_custom_tabs_for_woocommerce_tabs_nonce';
	private const USAGE_STATS_TRANSIENT = 'kitgenix_custom_tabs_for_woocommerce_usage_stats';

	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', [ self::class, 'product_data_tabs' ] );
		add_action( 'woocommerce_product_data_panels', [ self::class, 'product_data_panels' ] );
		add_action( 'woocommerce_process_product_meta', [ self::class, 'save_product_tabs' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ], 20 );
		add_action( 'before_delete_post', [ self::class, 'flush_usage_stats_cache_for_product' ] );
	}

	/**
	 * @param array<string,mixed> $tabs
	 * @return array<string,mixed>
	 */
	public static function product_data_tabs( array $tabs ): array {
		if ( ! Settings::enabled() ) {
			return $tabs;
		}

		$tabs['kitgenix_custom_tabs_for_woocommerce_tabs'] = [
			'label'    => __( 'Custom Tabs', 'kitgenix-custom-tabs-for-woocommerce' ),
			'target'   => 'kitgenix_custom_tabs_for_woocommerce_tabs',
			'class'    => [ 'kitgenix-custom-tabs-for-woocommerce-product-data-tab' ],
			'priority' => 120,
		];

		return $tabs;
	}

	public static function enqueue_assets( string $hook ): void {
		if ( ! Settings::enabled() ) {
			return;
		}

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		global $post_type;
		if ( $post_type !== 'product' ) {
			return;
		}

		// Quill is bundled locally (assets/vendor/quill/) rather than loaded from a
		// third-party CDN: wp-admin should not depend on an external host being
		// reachable to edit a product, and loading executable JS from a CDN into
		// wp-admin is both a reliability risk and best avoided for a WordPress.org
		// plugin. See assets/vendor/quill/quill.min.js for the upstream license header.
		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			plugins_url( 'assets/vendor/quill/quill.snow.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			'1.3.7'
		);

		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			plugins_url( 'assets/vendor/quill/quill.min.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			'1.3.7',
			true
		);

		$ver = defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION : null;
		$base_dir = defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_DIR' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_DIR : '';
		$admin_css_file = $base_dir ? $base_dir . 'assets/css/admin.css' : '';
		$admin_css_ver  = ( $admin_css_file && file_exists( $admin_css_file ) ) ? (string) filemtime( $admin_css_file ) : $ver;
		$product_css_file = $base_dir ? $base_dir . 'assets/css/product-edit.css' : '';
		$product_css_ver  = ( $product_css_file && file_exists( $product_css_file ) ) ? (string) filemtime( $product_css_file ) : $ver;
		$product_js_file = $base_dir ? $base_dir . 'assets/js/admin.js' : '';
		$product_js_ver  = ( $product_js_file && file_exists( $product_js_file ) ) ? (string) filemtime( $product_js_file ) : $ver;

		wp_enqueue_style( 'kitgenix-custom-tabs-for-woocommerce-admin-ui' );

		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-admin-shared',
			plugins_url( 'assets/css/admin.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			$admin_css_ver
		);
		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-product-edit',
			plugins_url( 'assets/css/product-edit.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			$product_css_ver
		);
		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-product-edit',
			plugins_url( 'assets/js/admin.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[ 'kitgenix-custom-tabs-for-woocommerce-quill' ],
			$product_js_ver,
			true
		);

		wp_localize_script(
			'kitgenix-custom-tabs-for-woocommerce-product-edit',
			'kitgenix_custom_tabs_for_woocommerce_admin',
			[
				'maxTabs' => Settings::max_tabs(),
				'priorityBase' => Settings::priority_base(),
				'priorityStep' => Settings::priority_step(),
				'i18n'    => [
					'confirmRemove' => __( 'Remove this tab?', 'kitgenix-custom-tabs-for-woocommerce' ),
					'maxReached'    => __( 'You have reached the maximum number of tabs for this product.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'titleRequired' => __( 'Please enter a tab title.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'contentRequired' => __( 'Please enter tab content.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'duplicateTab' => __( 'Duplicate', 'kitgenix-custom-tabs-for-woocommerce' ),
					'chooseTemplate' => __( 'Choose a saved template', 'kitgenix-custom-tabs-for-woocommerce' ),
					'insertTemplate' => __( 'Insert template', 'kitgenix-custom-tabs-for-woocommerce' ),
					'templateToolbarLabel' => __( 'Template library', 'kitgenix-custom-tabs-for-woocommerce' ),
					'copyLabel' => __( 'Copy', 'kitgenix-custom-tabs-for-woocommerce' ),
					'moveUp' => __( 'Move up', 'kitgenix-custom-tabs-for-woocommerce' ),
					'moveDown' => __( 'Move down', 'kitgenix-custom-tabs-for-woocommerce' ),
					'disableTab' => __( 'Disable', 'kitgenix-custom-tabs-for-woocommerce' ),
					'enableTab' => __( 'Enable', 'kitgenix-custom-tabs-for-woocommerce' ),
					'disabledBadge' => __( 'Disabled', 'kitgenix-custom-tabs-for-woocommerce' ),
				],
			]
		);
	}

	public static function product_data_panels(): void {
		if ( ! Settings::enabled() ) {
			return;
		}

		global $post;
		$product_id = ( $post instanceof \WP_Post ) ? (int) $post->ID : 0;
		$tabs       = self::get_product_tabs( $product_id );

		$max_tabs = Settings::max_tabs();
		$base     = Settings::priority_base();
		$step     = Settings::priority_step();
		$empty_message = __( 'No custom tabs yet. Click Add new tab to create one.', 'kitgenix-custom-tabs-for-woocommerce' );

		echo '<div id="kitgenix_custom_tabs_for_woocommerce_tabs" class="panel woocommerce_options_panel">';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="options_group">';

		$settings_url  = admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=settings' );
		$templates_url = admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=templates' );
		$global_url    = admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=global-tabs' );
		$support_url   = admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=support' );

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-panel-header">'
			. '<span class="kitgenix-custom-tabs-for-woocommerce-panel-header__title">' . esc_html__( 'Custom tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-panel-header__actions kitgenix-button-group">'
			. '<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' . esc_url( $settings_url ) . '">'
			. '<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>'
			. esc_html__( 'Tab settings', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</a>'
			. '<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' . esc_url( $templates_url ) . '">'
			. '<span class="dashicons dashicons-layout" aria-hidden="true"></span>'
			. esc_html__( 'Templates', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</a>'
			. '<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' . esc_url( $support_url ) . '">'
			. '<span class="dashicons dashicons-sos" aria-hidden="true"></span>'
			. esc_html__( 'Help & FAQ', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</a>'
			. '<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="' . esc_url( $global_url ) . '">'
			. '<span class="dashicons dashicons-admin-site-alt2" aria-hidden="true"></span>'
			. esc_html__( 'Global tabs', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</a>'
			. '<button class="button button-primary" type="button" data-kitgenix-custom-tabs-for-woocommerce-add="1">'
			. '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>'
			. esc_html__( 'Add new tab', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</button>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-panel-intro">'
			. '<p class="description kitgenix-custom-tabs-for-woocommerce-panel-intro__text">'
			. esc_html__( 'Add product-specific tabs. These will appear on the product page tabs area.', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</p>'
			. '<p class="description kitgenix-custom-tabs-for-woocommerce-panel-intro__text">'
			. esc_html__( 'Use saved templates for repeated snippets like sizing guides, warranty terms, ingredients, or care instructions, then duplicate a tab whenever a product only needs a quick variation.', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</p>'
			. '<p class="description kitgenix-custom-tabs-for-woocommerce-panel-intro__text">'
			. esc_html__( 'Tip: Global tabs that apply to every product can be managed under Kitgenix → Custom Tabs → Global Tabs.', 'kitgenix-custom-tabs-for-woocommerce' )
			. '</p>'
			. '</div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-manager kitgenix-custom-tabs-for-woocommerce-scope"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager="1"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager-type="product"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-base="kitgenix_custom_tabs_for_woocommerce_tabs"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-max="' . esc_attr( (string) $max_tabs ) . '"'
			. self::get_templates_dataset_attribute() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped data-* attributes only.
			. ' data-kitgenix-custom-tabs-for-woocommerce-empty-message="' . esc_attr( $empty_message ) . '"'
			. '>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-table-wrap kitgenix-custom-tabs-for-woocommerce-panel-table kitgenix-table-wrap">'
			. '<table class="kitgenix-table">'
			. '<thead><tr>'
			. '<th scope="col">' . esc_html__( 'Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Slug', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Actions', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '</tr></thead>'
			. '<tbody data-kitgenix-custom-tabs-for-woocommerce-body="1">';

		if ( empty( $tabs ) ) {
			$tabs = [];
		}

		$index = 0;
		$fields_html = '';
		foreach ( $tabs as $tab ) {
			$title    = isset( $tab['title'] ) ? (string) $tab['title'] : '';
			$nickname = isset( $tab['nickname'] ) ? (string) $tab['nickname'] : '';
			$slug     = isset( $tab['slug'] ) ? (string) $tab['slug'] : '';
			$priority = isset( $tab['priority'] ) ? (int) $tab['priority'] : Settings::compute_priority_for_index( $base, $step, $index );
			$content  = isset( $tab['content'] ) ? (string) $tab['content'] : '';
			$enabled  = ! array_key_exists( 'enabled', $tab ) || ! empty( $tab['enabled'] );
			$visibility = isset( $tab['visibility'] ) && is_array( $tab['visibility'] ) ? $tab['visibility'] : null;
			$hide_title = ! empty( $tab['hide_title'] );

			self::render_table_row( $index, $title, $nickname, $slug, $priority, $enabled );
			ob_start();
			self::render_hidden_fields( $index, $title, $nickname, $slug, $priority, $content, $enabled, $visibility, $hide_title );
			$fields_html .= (string) ob_get_clean();
			$index++;
		}
		if ( $index === 0 ) {
			echo '<tr class="kitgenix-custom-tabs-for-woocommerce-empty-row" data-kitgenix-custom-tabs-for-woocommerce-empty="1">'
				. '<td class="kitgenix-custom-tabs-for-woocommerce-empty-cell" colspan="4">'
				. '<div class="kitgenix-empty-state">'
				. '<p class="kitgenix-empty-state-desc">' . esc_html( $empty_message ) . '</p>'
				. '</div>'
				. '</td>'
				. '</tr>';
		}

		echo '</tbody></table></div>';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1" class="kitgenix-custom-tabs-for-woocommerce-tabs-fields-wrap">'
			. $fields_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. '</div>';

		self::render_backbone_modal_template();
		self::render_editor_dock();

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	private static function render_table_row( int $index, string $title, string $nickname, string $slug, int $priority, bool $enabled = true ): void {
		$display_title = $nickname !== '' ? $nickname : ( $title !== '' ? $title : __( 'Untitled', 'kitgenix-custom-tabs-for-woocommerce' ) );
		$subtitle      = ( $nickname !== '' && $title !== '' && $nickname !== $title ) ? $title : '';
		$display_slug  = $slug !== '' ? $slug : '–';
		$row_class     = $enabled ? '' : ' kitgenix-custom-tabs-for-woocommerce-row-disabled';

		echo '<tr data-kitgenix-custom-tabs-for-woocommerce-row="1" data-index="' . esc_attr( (string) $index ) . '" class="' . esc_attr( trim( $row_class ) ) . '">'
			. '<td>'
			. '<strong data-kitgenix-custom-tabs-for-woocommerce-row-title="1">' . esc_html( $display_title ) . '</strong>'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-tabs-subtitle" data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1">' . esc_html( $subtitle ) . '</div>'
			. ( $enabled ? '' : '<span class="kitgenix-badge neutral" data-kitgenix-custom-tabs-for-woocommerce-row-disabled-badge="1">' . esc_html__( 'Disabled', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>' )
			. '</td>'
			. '<td><span class="kitgenix-custom-tabs-for-woocommerce-code" data-kitgenix-custom-tabs-for-woocommerce-row-slug="1">' . esc_html( $display_slug ) . '</span></td>'
			. '<td><span data-kitgenix-custom-tabs-for-woocommerce-row-position="1">' . esc_html( (string) $priority ) . '</span></td>'
			. '<td class="kitgenix-custom-tabs-for-woocommerce-actions">'
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-up="1" aria-label="' . esc_attr__( 'Move up', 'kitgenix-custom-tabs-for-woocommerce' ) . '">&#8593;</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-down="1" aria-label="' . esc_attr__( 'Move down', 'kitgenix-custom-tabs-for-woocommerce' ) . '">&#8595;</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-edit="1">' . esc_html__( 'Edit', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-toggle-enabled="1">' . ( $enabled ? esc_html__( 'Disable', 'kitgenix-custom-tabs-for-woocommerce' ) : esc_html__( 'Enable', 'kitgenix-custom-tabs-for-woocommerce' ) ) . '</a> '
			. '<a href="#" class="button button-link-delete" data-kitgenix-custom-tabs-for-woocommerce-remove="1">' . esc_html__( 'Remove', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</td>'
			. '</tr>';
	}

	/**
	 * @param array<string,mixed>|null $visibility
	 */
	private static function render_hidden_fields( int $index, string $title, string $nickname, string $slug, int $priority, string $content, bool $enabled = true, ?array $visibility = null, bool $hide_title = false ): void {
		$prefix = 'kitgenix_custom_tabs_for_woocommerce_tabs[' . esc_attr( (string) $index ) . ']';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[title]" value="' . esc_attr( $title ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[hide_title]" value="' . ( $hide_title ? '1' : '' ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[nickname]" value="' . esc_attr( $nickname ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[slug]" value="' . esc_attr( $slug ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[priority]" value="' . esc_attr( (string) $priority ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[enabled]" value="' . ( $enabled ? '1' : '0' ) . '" data-kitgenix-custom-tabs-for-woocommerce-enabled-field="1" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[visibility]" value="' . esc_attr( (string) wp_json_encode( $visibility ?? Tab_Matcher::default_visibility() ) ) . '" data-kitgenix-custom-tabs-for-woocommerce-visibility-field="1" />'
			. '<textarea name="' . esc_attr( $prefix ) . '[content]" data-kitgenix-custom-tabs-for-woocommerce-content="1">' . esc_textarea( $content ) . '</textarea>'
			. '</div>';
	}

	private static function render_backbone_modal_template(): void {
		$position_help_url = esc_url( 'https://woocommerce.com/document/woocommerce-product-tabs/' );

		// Render as a persistent Kitgenix modal (shared kitgenix-modal-* library markup).
		?>
		<div id="kitgenix-custom-tabs-for-woocommerce-modal" class="kitgenix-modal" hidden role="dialog" aria-modal="true">
			<div class="kitgenix-modal-backdrop" data-kitgenix-modal-close="1"></div>
			<div class="kitgenix-modal-dialog kitgenix-modal-dialog-lg" role="document">
				<div class="kitgenix-modal-header">
					<h2 class="kitgenix-modal-title" data-kitgenix-custom-tabs-for-woocommerce-modal-title="1"><?php esc_html_e( 'Edit Tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h2>
					<button type="button" class="kitgenix-modal-close" data-kitgenix-modal-close="1" aria-label="<?php echo esc_attr__( 'Close', 'kitgenix-custom-tabs-for-woocommerce' ); ?>">&times;</button>
				</div>

				<div class="kitgenix-modal-body">
					<input type="hidden" data-kitgenix-custom-tabs-for-woocommerce-modal-index="1" value="" />
					<div class="kitgenix-custom-tabs-for-woocommerce-modal-scope">
						<div class="kitgenix-custom-tabs-for-woocommerce-modal-grid">
							<div class="kitgenix-custom-tabs-for-woocommerce-field kitgenix-custom-tabs-for-woocommerce-is-full">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_title"><?php esc_html_e( 'Tab title', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_title" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="title" value="" maxlength="50" placeholder="<?php echo esc_attr__( 'Title for tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
								<div class="kitgenix-custom-tabs-for-woocommerce-field__error" data-kitgenix-custom-tabs-for-woocommerce-error="title" aria-live="polite"></div>
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field kitgenix-custom-tabs-for-woocommerce-is-full">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_editor"><?php esc_html_e( 'Tab content', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<div class="kitgenix-custom-tabs-for-woocommerce-editor-slot" data-kitgenix-custom-tabs-for-woocommerce-editor-slot="1"></div>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__error" data-kitgenix-custom-tabs-for-woocommerce-error="content" aria-live="polite"></div>
							</div>

							<details class="kitgenix-custom-tabs-for-woocommerce-advanced kitgenix-custom-tabs-for-woocommerce-is-full">
								<summary class="kitgenix-custom-tabs-for-woocommerce-advanced__summary"><?php esc_html_e( 'Advanced settings', 'kitgenix-custom-tabs-for-woocommerce' ); ?></summary>
								<div class="kitgenix-custom-tabs-for-woocommerce-advanced__body">
									<div class="kitgenix-custom-tabs-for-woocommerce-modal-grid">
										<div class="kitgenix-custom-tabs-for-woocommerce-field">
											<label class="kitgenix-custom-tabs-for-woocommerce-field__checkbox">
												<input type="checkbox" id="kitgenix_custom_tabs_for_woocommerce_modal_hide_title" data-kitgenix-custom-tabs-for-woocommerce-modal-field="hide_title" value="1" />
												<?php esc_html_e( 'Hide title inside tab content', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
											</label>
											<div class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'The tab still shows this title in the tab bar; this only hides the heading repeated inside the panel.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></div>
										</div>
										<div class="kitgenix-custom-tabs-for-woocommerce-field">
											<label for="kitgenix_custom_tabs_for_woocommerce_modal_priority"><?php esc_html_e( 'Tab position', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
											<div class="kitgenix-custom-tabs-for-woocommerce-field__inline">
												<input type="number" min="0" step="1" id="kitgenix_custom_tabs_for_woocommerce_modal_priority" class="small-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="priority" value="" />
												<a class="kitgenix-custom-tabs-for-woocommerce-modal__help-link" href="<?php echo esc_url( $position_help_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about positioning', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
											</div>
										</div>
										<div class="kitgenix-custom-tabs-for-woocommerce-field">
											<label for="kitgenix_custom_tabs_for_woocommerce_modal_nickname"><?php esc_html_e( 'Tab nickname (optional)', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
											<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_nickname" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="nickname" value="" placeholder="<?php echo esc_attr__( 'Nickname for tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
										</div>
										<div class="kitgenix-custom-tabs-for-woocommerce-field">
											<label for="kitgenix_custom_tabs_for_woocommerce_modal_slug"><?php esc_html_e( 'Tab slug', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
											<div class="kitgenix-custom-tabs-for-woocommerce-field__inline">
												<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_slug" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="slug" value="" placeholder="<?php echo esc_attr__( 'Tab slug', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
												<a href="#" class="kitgenix-custom-tabs-for-woocommerce-modal__help-link" data-kitgenix-custom-tabs-for-woocommerce-slug-generate="1"><?php esc_html_e( 'Generate from title', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
											</div>
											<div class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'Allowed: letters, numbers, and hyphens.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></div>
										</div>
									</div>
									<?php self::render_visibility_modal_fields( 'kitgenix_custom_tabs_for_woocommerce_modal' ); ?>
								</div>
							</details>
						</div>
					</div>
				</div>

				<div class="kitgenix-modal-footer">
					<button type="button" class="button" data-kitgenix-custom-tabs-for-woocommerce-cancel="1" data-kitgenix-modal-close="1"><?php esc_html_e( 'Cancel', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" data-kitgenix-custom-tabs-for-woocommerce-save="1"><?php esc_html_e( 'Done', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_editor_dock(): void {
		// No-op with Quill. Editor markup is created lazily in the modal slot by admin.js.
	}

	public static function save_product_tabs( int $post_id, \WP_Post $post ): void {
		if ( ! Settings::enabled() ) {
			// Preserve any existing saved tabs when disabled.
			Event_Log::record( 'product-tabs-save', 'skipped', __( 'Custom Tabs is turned off in Settings; the save was skipped.', 'kitgenix-custom-tabs-for-woocommerce' ), 'feature_disabled' );
			return;
		}

		if ( $post->post_type !== 'product' ) {
			Event_Log::record( 'product-tabs-save', 'skipped', __( 'Post being saved was not a product; no tab data applied.', 'kitgenix-custom-tabs-for-woocommerce' ), 'wrong_post_type' );
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			Event_Log::record( 'product-tabs-save', 'error', __( 'Current user did not have permission to edit this product; save blocked.', 'kitgenix-custom-tabs-for-woocommerce' ), 'no_capability' );
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( '' === $nonce ) {
			Event_Log::record( 'product-tabs-save', 'skipped', __( 'No security token was submitted with the save (likely a cached edit-product page).', 'kitgenix-custom-tabs-for-woocommerce' ), 'nonce_missing' );
			return;
		}
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			Event_Log::record( 'product-tabs-save', 'skipped', __( 'Security token did not match (likely a stale/cached edit-product page).', 'kitgenix-custom-tabs-for-woocommerce' ), 'nonce_invalid' );
			return;
		}

		$max = Settings::max_tabs();

		// Read the incoming tab rows from POST using filter_input to avoid
		// direct superglobal usage flagged by static analysis tools.
		$incoming = filter_input( INPUT_POST, 'kitgenix_custom_tabs_for_woocommerce_tabs', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$incoming = is_array( $incoming ) ? wp_unslash( $incoming ) : null;
		if ( ! is_array( $incoming ) ) {
			delete_post_meta( $post_id, self::META_KEY );
			self::flush_usage_stats_cache();
			Event_Log::record( 'product-tabs-save', 'error', __( 'Submitted tab data was not in the expected format; no tabs were saved for this product.', 'kitgenix-custom-tabs-for-woocommerce' ), 'invalid_payload' );
			return;
		}

		// Sanitize via the same shared sanitizer global tabs and templates use
		// (context 'product' also sanitizes the `visibility` sub-fields, but not
		// `target`, which only applies to global tabs).
		$out = Settings::sanitize_tabs_rows( $incoming, $max, 'product' );

		if ( empty( $out ) ) {
			delete_post_meta( $post_id, self::META_KEY );
			self::flush_usage_stats_cache();
			return;
		}

		update_post_meta( $post_id, self::META_KEY, $out );
		self::flush_usage_stats_cache();
	}

	public static function flush_usage_stats_cache(): void {
		delete_transient( self::USAGE_STATS_TRANSIENT );
	}

	public static function flush_usage_stats_cache_for_product( int $post_id ): void {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		self::flush_usage_stats_cache();
	}

	public static function get_usage_stats_cache_key(): string {
		return self::USAGE_STATS_TRANSIENT;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function get_product_tabs( int $product_id ): array {
		if ( $product_id <= 0 ) {
			return [];
		}

		$val = get_post_meta( $product_id, self::META_KEY, true );
		return is_array( $val ) ? $val : [];
	}

	private static function get_templates_dataset_attribute(): string {
		$templates = [];

		foreach ( Settings::tab_templates() as $template ) {
			if ( ! is_array( $template ) ) {
				continue;
			}

			$title = isset( $template['title'] ) ? trim( (string) $template['title'] ) : '';
			if ( '' === $title ) {
				continue;
			}

			$templates[] = [
				'title'      => $title,
				'nickname'   => isset( $template['nickname'] ) ? (string) $template['nickname'] : '',
				'slug'       => isset( $template['slug'] ) ? (string) $template['slug'] : '',
				'priority'   => isset( $template['priority'] ) ? (int) $template['priority'] : 0,
				'content'    => isset( $template['content'] ) ? (string) $template['content'] : '',
				'hide_title' => empty( $template['hide_title'] ) ? 0 : 1,
				'label'      => isset( $template['nickname'] ) && (string) $template['nickname'] !== ''
					? (string) $template['nickname']
					: $title,
			];
		}

		if ( empty( $templates ) ) {
			return '';
		}

		return ' data-kitgenix-custom-tabs-for-woocommerce-templates="' . esc_attr( wp_json_encode( $templates ) ) . '"';
	}
}
