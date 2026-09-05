<?php
/** Private bearer-link coupon view, without theme analytics or checkout integration. */
class Gn_In_Store_Coupons_Public {
	public function __construct( $name, $version ) {}

	public function display() {
		global $wpdb;
		if ( ! isset( $_GET['gn_store_coupon'] ) ) { return; }
		$token = Gn_In_Store_Coupons_Admin::input( $_GET, 'gn_store_coupon' );
		$c = preg_match( '/^[a-f0-9]{64}$/D', $token ) ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Gn_In_Store_Coupons_Store::table() . ' WHERE token = %s', $token ) ) : null;
		if ( ! $c ) {
			nocache_headers();
			header( 'X-Robots-Tag: noindex, nofollow' );
			wp_die( 'Coupon not found.', 'Coupon not found', array( 'response' => 404 ) );
		}
		$this->render( $c );
	}

	public function preview() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'gn_coupon_preview' );
		$s = Gn_In_Store_Coupons_Store::settings();
		$names = array();
		foreach ( $s['categories'] as $id ) { $term = get_term( $id, 'product_cat' ); if ( $term && ! is_wp_error( $term ) ) { $names[] = $term->name; } }
		$c = (object) array( 'status' => 'preview', 'code' => 'PREVIEW - NOT VALID', 'customer_name' => '', 'expires_at' => $s['days'] ? gmdate( 'Y-m-d H:i:s', time() + $s['days'] * DAY_IN_SECONDS ) : null,
			'offer' => wp_json_encode( array( 'brand' => $s['brand'], 'logo' => wp_get_attachment_image_url( $s['logo_id'], 'medium' ), 'color' => $s['color'], 'discount' => $s['discount'], 'categories' => $names, 'terms' => $s['terms'] ) ) );
		$this->render( $c );
	}

	private function render( $coupon ) {
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: DENY' );
		$offer = json_decode( $coupon->offer, true );
		$status = Gn_In_Store_Coupons_Store::status( $coupon );
		require plugin_dir_path( __FILE__ ) . 'partials/gn-in-store-coupons-public-display.php';
		exit;
	}
}
