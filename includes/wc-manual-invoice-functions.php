<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Helper function to show fallback values
function show_fallback_info( $setting_key, $current_value, $raw_value ) {
	if ( empty( $raw_value ) && ! empty( $current_value ) ) {
		echo '<p class="description" style="color: #0073aa; font-style: italic;">';
		echo '<span class="dashicons dashicons-info" style="font-size: 14px; margin-right: 5px;"></span>';
		printf( __( 'Currently using: %s (automatic)', 'wc-manual-invoices' ), '<strong>' . esc_html( $current_value ) . '</strong>' );
		echo '</p>';
	}
}
