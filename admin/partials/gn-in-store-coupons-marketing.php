<?php
/** Administrator-only, saved-offer marketing exports. */
if ( ! defined( 'ABSPATH' ) || ! current_user_can( 'manage_options' ) ) { return; }
$marketing = array(
	'brand' => $s['brand'], 'amount' => (float) $s['discount'],
	'minimum' => (float) $s['minimum_purchase'], 'days' => (int) $s['days'],
	'color' => sanitize_hex_color( $s['color'] ) ?: '#db3340',
	'logo' => set_url_scheme( wp_get_attachment_image_url( $s['logo_id'], 'full' ) ?: '' ),
	'website' => wp_parse_url( home_url(), PHP_URL_HOST ),
);
$campaign_id = absint( get_option( 'gn_coupons_marketing_campaign_id', 0 ) );
?>
<section id="gn-marketing" class="gn-marketing" data-offer="<?php echo esc_attr( wp_json_encode( $marketing ) ); ?>" aria-labelledby="gn-marketing-title">
	<h2 id="gn-marketing-title">Marketing</h2>
	<?php if ( $campaign_id ) : ?>
	<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mrm-admin#/campaign/regular/edit/' . $campaign_id ) ); ?>"><span class="dashicons dashicons-email" aria-hidden="true"></span> Review Mail Mint campaign</a></p>
	<?php endif; ?>
	<fieldset class="gn-marketing-formats"><legend class="screen-reader-text">Image format</legend>
		<label><input type="radio" name="gn-marketing-format" value="square" checked> Square · 1080 × 1080</label>
		<label><input type="radio" name="gn-marketing-format" value="story"> Story · 1080 × 1920</label>
	</fieldset>
	<p id="gn-marketing-status" role="status">Preparing images...</p>
	<div class="gn-marketing-grid">
	<?php foreach ( array( 'el' => 'Ελληνικά', 'en' => 'English' ) as $language => $label ) : ?>
		<article class="gn-marketing-asset" lang="<?php echo esc_attr( $language ); ?>">
			<h3><?php echo esc_html( $label ); ?></h3>
			<canvas width="1080" height="1080" data-language="<?php echo esc_attr( $language ); ?>" role="img" aria-label="<?php echo esc_attr( $label . ' promotional image' ); ?>"></canvas>
			<p><button type="button" class="button gn-download-asset" data-language="<?php echo esc_attr( $language ); ?>" disabled><span class="dashicons dashicons-download" aria-hidden="true"></span> Download PNG</button></p>
			<label for="gn-caption-<?php echo esc_attr( $language ); ?>">Social caption</label>
			<textarea id="gn-caption-<?php echo esc_attr( $language ); ?>" class="large-text" rows="7" readonly></textarea>
			<button type="button" class="button gn-copy-caption" data-language="<?php echo esc_attr( $language ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span> Copy caption</button>
		</article>
	<?php endforeach; ?>
	</div>
</section>
