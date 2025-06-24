<?php
/**
 * This file is used for rendering and saving plugin welcome settings.
 *
 * @package bp_stats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}
?>
<div class="wbcom-tab-content">
	<div class="wbcom-welcome-main-wrapper">
		<div class="wbcom-welcome-head">
			<p class="wbcom-welcome-description"><?php esc_html_e( 'Woo Document Preview enables you to add a document preview directly to a single product page, making it ideal for showcasing eBooks, PDFs, or other documents.', 'wc-document-preview' ); ?></p>
		</div><!-- .wbcom-welcome-head -->

		<div class="wbcom-welcome-content">
			<div class="wbcom-welcome-support-info">
				<h3><?php esc_html_e( 'Help &amp; Support Resources', 'wc-document-preview' ); ?></h3>
				<p><?php esc_html_e( 'If you need assistance, here are some helpful resources. Our documentation is a great place to start, and our support team is available if you require further help.', 'wc-document-preview' ); ?></p>
				<div class="wbcom-support-info-wrap">
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-book"></span><?php esc_html_e( 'Documentation', 'wc-document-preview' ); ?></h3>
						<p><?php esc_html_e( 'Explore our detailed guide on Woo Document Preview to understand all the features and how to make the most of them.', 'wc-document-preview' ); ?></p>
						<a href="<?php echo esc_url( 'https://docs.wbcomdesigns.com/doc_category/woo-document-preview/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Read Documentation', 'wc-document-preview' ); ?></a>
						</div>
					</div>

					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-sos"></span><?php esc_html_e( 'Support Center', 'wc-document-preview' ); ?></h3>
						<p><?php esc_html_e( 'Our support team is here to assist you with any questions or issues. Feel free to contact us anytime through our support center.', 'wc-document-preview' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/support/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Get Support', 'wc-document-preview' ); ?></a>
					</div>
					</div>
					<div class="wbcom-support-info-widgets">
						<div class="wbcom-support-inner">
						<h3><span class="dashicons dashicons-admin-comments"></span><?php esc_html_e( 'Share Your Feedback', 'wc-document-preview' ); ?></h3>
						<p><?php esc_html_e( 'We’d love to hear about your experience with the plugin. Your feedback and suggestions help us improve future updates.', 'wc-document-preview' ); ?></p>
						<a href="<?php echo esc_url( 'https://wbcomdesigns.com/submit-review/' ); ?>" class="button button-primary button-welcome-support" target="_blank"><?php esc_html_e( 'Send Feedback', 'wc-document-preview' ); ?></a>
					</div>
					</div>
				</div>
			</div>
		</div>
	</div><!-- .wbcom-welcome-main-wrapper -->
</div><!-- .wbcom-tab-content -->