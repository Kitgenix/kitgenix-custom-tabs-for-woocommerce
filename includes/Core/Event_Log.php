<?php
namespace KitgenixCustomTabsForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Simple per-plugin activity log for Kitgenix Custom Tabs for WooCommerce.
 *
 * Records admin actions (settings saves, tab CRUD, template operations) to a
 * capped WP option so they are visible in the admin Log tab.
 *
 * Storage format (per entry):
 *   [ 'time' => int, 'context' => string, 'outcome' => string, 'note' => string, 'code' => string ]
 */
final class Event_Log {

    private const OPTION_KEY  = 'kitgenix_custom_tabs_for_woocommerce_event_log';
    private const MAX_ENTRIES = 100;

    /**
     * Record an event to the log.
     *
     * @param string $context  Short slug (e.g. 'settings-saved', 'tab-created').
     * @param string $outcome  'success', 'error', or any short outcome label.
     * @param string $note     Optional plain-English detail.
     * @param string $code     Optional structured code (e.g. 'nonce_invalid'); see get_category_and_note().
     */
    public static function record( string $context, string $outcome, string $note = '', string $code = '' ): void {
        $log   = self::get_raw_log();
        $log[] = [
            'time'    => time(),
            'context' => sanitize_text_field( $context ),
            'outcome' => sanitize_text_field( $outcome ),
            'note'    => sanitize_text_field( $note ),
            'code'    => sanitize_key( $code ),
        ];

        if ( count( $log ) > self::MAX_ENTRIES ) {
            $log = array_slice( $log, -self::MAX_ENTRIES );
        }

        update_option( self::OPTION_KEY, $log, false );
    }

    /**
     * Return all stored entries (oldest first).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_raw_log(): array {
        $log = get_option( self::OPTION_KEY, [] );
        return is_array( $log ) ? $log : [];
    }

    /** Delete all stored entries. */
    public static function clear(): void {
        delete_option( self::OPTION_KEY );
    }

    /**
     * Return the log as a formatted multi-line string for the admin textarea.
     */
    public static function get_log_text(): string {
        $entries = self::get_raw_log();
        if ( empty( $entries ) ) {
            return __( 'No recent events recorded yet.', 'kitgenix-custom-tabs-for-woocommerce' );
        }

        $format = (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' );
        $lines  = [ '# Columns: timestamp | context | outcome | code | note' ];

        foreach ( array_reverse( $entries ) as $entry ) {
            $time = isset( $entry['time'] ) ? (int) $entry['time'] : 0;
            if ( function_exists( 'wp_date' ) ) {
                $when = $time ? (string) wp_date( $format, $time ) : '';
            } else {
                $when = $time ? (string) date_i18n( $format, $time ) : '';
            }

            $lines[] = sprintf(
                '%1$s | %2$s | %3$s | %4$s | %5$s',
                $when ?: __( 'Unknown time', 'kitgenix-custom-tabs-for-woocommerce' ),
                (string) ( $entry['context'] ?? '' ),
                (string) ( $entry['outcome'] ?? '' ),
                (string) ( $entry['code']    ?? '' ),
                (string) ( $entry['note']    ?? '' )
            );
        }

        return implode( "\n", $lines );
    }

    /**
     * Map a structured event code to a short category slug and a plain-English note.
     *
     * Distinguishes genuine problems (need action) from likely false positives
     * (stale nonce on a cached edit-product page, routine value clamping).
     *
     * @return array{category: string, note: string}
     */
    public static function get_category_and_note( string $code ): array {
        $map = [
            'settings-saved'    => [
                'category' => 'saved',
                'note'     => __( 'Plugin settings were saved via the admin settings page.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'feature_disabled'  => [
                'category' => 'disabled',
                'note'     => __( 'Custom Tabs is turned off in Settings, so the save was skipped and existing tabs were left untouched.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'wrong_post_type'   => [
                'category' => 'not-applicable',
                'note'     => __( 'The saved post was not a product, so no tab data applied. This is expected on non-product screens.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'no_capability'     => [
                'category' => 'permission-denied',
                'note'     => __( 'The current user did not have permission to edit this product, so the tab save was blocked.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'nonce_missing'     => [
                'category' => 'cached-or-expired-page',
                'note'     => __( 'No security token was submitted with the save. Most common on a cached product edit page. Usually not a problem – reload the page and save again.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'nonce_invalid'     => [
                'category' => 'cached-or-expired-page',
                'note'     => __( 'The security token did not match. Most common on a stale/cached product edit page or after a login/session change. Usually not a problem – reload the page and save again.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'invalid_payload'   => [
                'category' => 'save-error',
                'note'     => __( 'The submitted tab data was not in the expected format, so nothing was saved. If this repeats, check for a plugin/theme conflict on the product edit screen.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'value_clamped'     => [
                'category' => 'auto-corrected',
                'note'     => __( 'A settings value was outside its allowed range and was automatically adjusted back into range. Not an error – just a safeguard.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
            'import_success'    => [
                'category' => 'imported',
                'note'     => __( 'Settings, global tabs, or templates were imported from a JSON/CSV file via the Portability tab.', 'kitgenix-custom-tabs-for-woocommerce' ),
            ],
        ];

        if ( isset( $map[ $code ] ) ) {
            return $map[ $code ];
        }

        return [
            'category' => 'unknown',
            'note'     => __( 'Event recorded – no further detail is mapped for this code.', 'kitgenix-custom-tabs-for-woocommerce' ),
        ];
    }
}
