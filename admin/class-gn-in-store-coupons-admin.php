<?php
/** Staff register, redemption, and administrator settings. */
class Gn_In_Store_Coupons_Admin {
	private $version;

	public function __construct( $name, $version ) { $this->version = $version; }

	public function menu() {
		add_menu_page( 'In-Store Coupons', 'In-Store Coupons', 'manage_woocommerce', 'gn-in-store-coupons', array( $this, 'register_page' ), 'dashicons-tickets-alt', 56 );
		add_submenu_page( 'gn-in-store-coupons', 'Coupons', 'Coupons', 'manage_woocommerce', 'gn-in-store-coupons', array( $this, 'register_page' ) );
		add_submenu_page( 'gn-in-store-coupons', 'Coupon Settings', 'Settings', 'manage_options', 'gn-coupon-settings', array( $this, 'settings_page' ) );
	}

	public function enqueue_styles() {
		if ( ! in_array( self::input( $_GET, 'page' ), array( 'gn-in-store-coupons', 'gn-coupon-settings' ), true ) ) { return; }
		wp_enqueue_style( 'gn-coupons-admin', plugin_dir_url( __FILE__ ) . 'css/gn-in-store-coupons-admin.css', array(), $this->version );
	}

	public function enqueue_scripts() {
		if ( ! in_array( self::input( $_GET, 'page' ), array( 'gn-in-store-coupons', 'gn-coupon-settings' ), true ) ) { return; }
		if ( current_user_can( 'manage_options' ) ) { wp_enqueue_media(); }
		wp_enqueue_script( 'gn-coupons-admin', plugin_dir_url( __FILE__ ) . 'js/gn-in-store-coupons-admin.js', array( 'jquery' ), $this->version, true );
		if ( 'gn-coupon-settings' === self::input( $_GET, 'page' ) && current_user_can( 'manage_options' ) ) {
			wp_enqueue_script( 'gn-coupons-marketing', plugin_dir_url( __FILE__ ) . 'js/gn-in-store-coupons-marketing.js', array(), $this->version, true );
		}
	}

	public static function input( $data, $key, $default = '' ) {
		return isset( $data[$key] ) && is_scalar( $data[$key] ) ? sanitize_text_field( wp_unslash( (string) $data[$key] ) ) : $default;
	}

	public function register_settings() {
		register_setting( 'gn_coupons', 'gn_coupons_settings', array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	public function sanitize( $input ) {
		$old = Gn_In_Store_Coupons_Store::settings();
		if ( ! is_array( $input ) ) { return $old; }
		$discount = (float) self::input( $input, 'discount' );
		$minimum = (float) self::input( $input, 'minimum_purchase' );
		if ( ! is_finite( $discount ) || ! is_finite( $minimum ) || $discount < 0.01 || $minimum < $discount ) {
			add_settings_error( 'gn_coupons', 'discount', 'Set a positive coupon amount and a minimum purchase at least equal to it.' );
			return $old;
		}
		$list_ids = array_map( 'intval', wp_list_pluck( Gn_In_Store_Coupons_Eligibility::lists(), 'id' ) );
		$lists = array_filter( (array) ( $input['lists'] ?? array() ), 'is_scalar' );
		$logo = absint( self::input( $input, 'logo_id' ) );
		$settings = array(
			'enabled' => empty( $input['enabled'] ) ? 0 : 1,
			'customer_enabled' => empty( $input['customer_enabled'] ) ? 0 : 1,
			'customer_list' => in_array( absint( self::input( $input, 'customer_list' ) ), $list_ids, true ) ? absint( self::input( $input, 'customer_list' ) ) : 0,
			'discount' => round( $discount, 2 ), 'minimum_purchase' => round( $minimum, 2 ), 'days' => min( 3650, absint( self::input( $input, 'days' ) ) ),
			'lists' => array_values( array_intersect( array_map( 'absint', $lists ), $list_ids ) ),
			'brand' => self::input( $input, 'brand' ) ?: get_bloginfo( 'name' ),
			'logo_id' => wp_attachment_is_image( $logo ) ? $logo : 0,
			'color' => sanitize_hex_color( self::input( $input, 'color' ) ) ?: '#2271b1',
			'terms' => isset( $input['terms'] ) && is_string( $input['terms'] ) ? sanitize_textarea_field( $input['terms'] ) : '',
		);
		if ( ( $settings['enabled'] || $settings['customer_enabled'] ) && ! Gn_In_Store_Coupons_Eligibility::ready() ) {
			$settings['enabled'] = 0;
			$settings['customer_enabled'] = 0;
			add_settings_error( 'gn_coupons', 'dependencies', 'WooCommerce and Mail Mint must be active before enabling issuance.' );
		}
		if ( $settings['lists'] !== $old['lists'] || $settings['enabled'] !== $old['enabled'] ) { update_option( 'gn_coupons_scan_offset', 0, false ); }
		if ( $settings['enabled'] && ! wp_next_scheduled( 'gn_coupons_scan_soon' ) ) { wp_schedule_single_event( time() + 60, 'gn_coupons_scan_soon' ); }
		return $settings;
	}

	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
		$s = Gn_In_Store_Coupons_Store::settings();
		$lists = Gn_In_Store_Coupons_Eligibility::lists();
		?>
		<div class="wrap gn-coupons"><h1>Coupon Settings</h1><?php settings_errors(); ?>
		<form action="options.php" method="post"><?php settings_fields( 'gn_coupons' ); ?>
		<h2>Issuance</h2><table class="form-table" role="presentation"><tbody>
		<tr><th scope="row">Automatic email issuance</th><td><label><input type="checkbox" name="gn_coupons_settings[enabled]" value="1" <?php checked( $s['enabled'] ); ?>> Enabled for new customers and subscribed contacts in the selected lists</label></td></tr>
		<tr><th scope="row">New customer issuance</th><td><label><input type="checkbox" name="gn_coupons_settings[customer_enabled]" value="1" <?php checked( $s['customer_enabled'] ); ?>> Email new WooCommerce customers even when full issuance is paused</label></td></tr>
		<tr><th scope="row"><label for="gn-customer-list">New customer Mail Mint list</label></th><td><select id="gn-customer-list" name="gn_coupons_settings[customer_list]"><option value="0">No list</option><?php foreach ( $lists as $list ) : ?><option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( $s['customer_list'], $list['id'] ); ?>><?php echo esc_html( $list['title'] ); ?></option><?php endforeach; ?></select></td></tr>
		<tr><th scope="row">Mail Mint lists</th><td><fieldset class="gn-options"><?php foreach ( $lists as $list ) : ?>
		<label><input type="checkbox" name="gn_coupons_settings[lists][]" value="<?php echo esc_attr( $list['id'] ); ?>" <?php checked( in_array( (int) $list['id'], $s['lists'], true ) ); ?>> <?php echo esc_html( $list['title'] ); ?></label>
		<?php endforeach; if ( ! $lists ) { echo '<p>No Mail Mint lists available.</p>'; } ?></fieldset></td></tr>
		<tr><th scope="row"><label for="gn-discount">Coupon amount (EUR)</label></th><td><input id="gn-discount" type="number" min="0.01" step="0.01" required name="gn_coupons_settings[discount]" value="<?php echo esc_attr( $s['discount'] ); ?>"></td></tr>
		<tr><th scope="row"><label for="gn-minimum">Minimum purchase (EUR)</label></th><td><input id="gn-minimum" type="number" min="0.01" step="0.01" required name="gn_coupons_settings[minimum_purchase]" value="<?php echo esc_attr( $s['minimum_purchase'] ); ?>"></td></tr>
		<tr><th scope="row"><label for="gn-days">Validity (days; 0 = no expiry)</label></th><td><input id="gn-days" type="number" min="0" max="3650" required name="gn_coupons_settings[days]" value="<?php echo esc_attr( $s['days'] ); ?>"></td></tr>
		</tbody></table>
		<h2>Store Branding</h2><table class="form-table" role="presentation"><tbody>
		<tr><th scope="row"><label for="gn-brand">Store name</label></th><td><input id="gn-brand" class="regular-text" name="gn_coupons_settings[brand]" required value="<?php echo esc_attr( $s['brand'] ); ?>"></td></tr>
		<tr><th scope="row">Logo</th><td><input id="gn-logo" type="hidden" name="gn_coupons_settings[logo_id]" value="<?php echo esc_attr( $s['logo_id'] ); ?>"><div id="gn-logo-preview"><?php echo wp_get_attachment_image( $s['logo_id'], 'thumbnail' ); ?></div><button type="button" class="button" id="gn-select-logo"><span class="dashicons dashicons-format-image" aria-hidden="true"></span> Select logo</button> <button type="button" class="button" id="gn-remove-logo">Remove</button></td></tr>
		<tr><th scope="row"><label for="gn-color">Brand color</label></th><td><input id="gn-color" type="color" name="gn_coupons_settings[color]" value="<?php echo esc_attr( $s['color'] ); ?>"></td></tr>
		<tr><th scope="row"><label for="gn-terms">Coupon terms</label></th><td><textarea id="gn-terms" class="large-text" rows="4" name="gn_coupons_settings[terms]"><?php echo esc_textarea( $s['terms'] ); ?></textarea></td></tr>
		</tbody></table><?php submit_button(); ?></form>
		<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gn_coupon_preview' ), 'gn_coupon_preview' ) ); ?>">Preview saved coupon</a>
		<?php require __DIR__ . '/partials/gn-in-store-coupons-marketing.php'; ?>
		</div><?php
	}

	public function register_page() {
		global $wpdb;
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
		if ( absint( self::input( $_GET, 'coupon' ) ) ) { $this->detail( absint( self::input( $_GET, 'coupon' ) ) ); return; }
		$table = Gn_In_Store_Coupons_Store::table();
		$status = self::input( $_GET, 'status', 'valid' );
		if ( ! in_array( $status, array( 'all', 'valid', 'redeemed', 'expired', 'revoked' ), true ) ) { $status = 'valid'; }
		$search = self::input( $_GET, 's' );
		$where = '1=1';
		if ( 'valid' === $status ) { $where .= " AND status = 'valid' AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())"; }
		elseif ( 'expired' === $status ) { $where .= " AND status = 'valid' AND expires_at <= UTC_TIMESTAMP()"; }
		elseif ( 'all' !== $status ) { $where .= $wpdb->prepare( ' AND status = %s', $status ); }
		if ( $search ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where .= $wpdb->prepare( ' AND (code LIKE %s OR email LIKE %s OR customer_name LIKE %s)', $like, $like, $like ); }
		$page = max( 1, absint( self::input( $_GET, 'paged', '1' ) ) );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT 25 OFFSET %d", ( $page - 1 ) * 25 ) );
		?>
		<div class="wrap gn-coupons"><h1>In-Store Coupons</h1>
		<p><strong><?php $s = Gn_In_Store_Coupons_Store::settings(); echo $s['enabled'] ? 'Automatic issuance enabled' : ( $s['customer_enabled'] ? 'New customer issuance enabled; list issuance paused' : 'Automatic issuance paused' ); ?></strong></p>
		<nav class="nav-tab-wrapper" aria-label="Coupon status"><?php foreach ( array( 'valid', 'redeemed', 'expired', 'revoked', 'all' ) as $tab ) : ?>
		<a class="nav-tab <?php echo $tab === $status ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'gn-in-store-coupons', 'status' => $tab ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( ucfirst( $tab ) ); ?></a>
		<?php endforeach; ?></nav>
		<form method="get" class="gn-search"><input type="hidden" name="page" value="gn-in-store-coupons"><input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>"><label class="screen-reader-text" for="gn-search">Code, customer, or email</label><input type="search" id="gn-search" name="s" placeholder="Code, customer, or email" value="<?php echo esc_attr( $search ); ?>"><button class="button">Search</button></form>
		<div class="gn-table"><table class="widefat striped"><thead><tr><th>Code</th><th>Customer</th><th>Discount</th><th>Issued</th><th>Expires</th><th>Status</th><th>Email</th></tr></thead><tbody>
		<?php foreach ( $rows as $c ) : $offer = json_decode( $c->offer, true ); ?>
		<tr><td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'gn-in-store-coupons', 'coupon' => $c->id ), admin_url( 'admin.php' ) ) ); ?>"><code><?php echo esc_html( $c->code ); ?></code></a></td><td><?php echo esc_html( $c->customer_name ); ?><br><?php echo esc_html( $c->email ); ?></td><td><?php echo esc_html( Gn_In_Store_Coupons_Store::discount_label( $offer ) ); ?></td><td><?php echo esc_html( self::date( $c->issued_at ) ); ?></td><td><?php echo esc_html( self::date( $c->expires_at ) ); ?></td><td><?php echo esc_html( ucfirst( Gn_In_Store_Coupons_Store::status( $c ) ) ); ?></td><td><?php echo esc_html( self::mail_label( $c->mail_status ) ); ?></td></tr>
		<?php endforeach; if ( ! $rows ) { echo '<tr><td colspan="7">No coupons found.</td></tr>'; } ?></tbody></table></div>
		<div class="tablenav"><span><?php echo esc_html( $total ); ?> coupons</span> <?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => (int) ceil( $total / 25 ) ) ) ); ?></div></div>
		<?php
	}

	public static function date( $date ) { return $date ? get_date_from_gmt( $date, 'j M Y H:i' ) : 'No expiry'; }
	public static function mail_label( $status ) {
		$labels = array( 'pending' => 'Queued', 'sending' => 'Sending / unconfirmed', 'sent' => 'Accepted by mailer', 'failed' => 'Failed', 'cancelled' => 'Cancelled' );
		return $labels[$status] ?? $status;
	}

	private function detail( $id ) {
		$c = Gn_In_Store_Coupons_Store::get( $id );
		if ( ! $c ) { wp_die( 'Coupon not found.', '', array( 'response' => 404 ) ); }
		$offer = json_decode( $c->offer, true );
		$status = Gn_In_Store_Coupons_Store::status( $c );
		?><div class="wrap gn-coupons"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gn-in-store-coupons' ) ); ?>">Back to coupons</a><h1><?php echo esc_html( $c->code ); ?></h1>
		<?php if ( self::input( $_GET, 'result' ) ) : ?><div class="notice notice-info"><p><?php echo 'ok' === self::input( $_GET, 'result' ) ? 'Coupon updated.' : 'No change made. Check the current status before trying again.'; ?></p></div><?php endif; ?>
		<table class="form-table" role="presentation"><tbody><?php
		$actor = $c->changed_by ? get_userdata( $c->changed_by ) : null;
		foreach ( array( 'Status' => ucfirst( $status ), 'Customer' => $c->customer_name, 'Email' => $c->email, 'Discount' => Gn_In_Store_Coupons_Store::discount_label( $offer ), 'Purchase requirement' => Gn_In_Store_Coupons_Store::purchase_label( $offer ) ?: '-', 'Issued' => self::date( $c->issued_at ), 'Expires' => self::date( $c->expires_at ), 'Source' => $c->source, 'Email status' => self::mail_label( $c->mail_status ), 'Last status change' => $c->changed_at ? self::date( $c->changed_at ) : '-', 'Changed by' => $actor ? $actor->display_name : ( $c->changed_by ?: '-' ), 'Note' => $c->note ) as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		?></tbody></table><p><a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( Gn_In_Store_Coupons_Store::url( $c ) ); ?>">View customer coupon</a></p>
		<?php if ( 'valid' === $status ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gn-action" data-confirm="This coupon can never be reissued. Continue?">
		<?php wp_nonce_field( 'gn_coupon_' . $id ); ?><input type="hidden" name="action" value="gn_coupon_action"><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
		<p><label for="gn-note">Receipt reference / note</label><br><input id="gn-note" class="regular-text" name="note" maxlength="500"></p>
		<button class="button button-primary" name="operation" value="redeemed">Mark redeemed</button> <button class="button" name="operation" value="revoked">Revoke</button></form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="gn-action" data-confirm="Email the same coupon again to this customer?">
		<?php wp_nonce_field( 'gn_coupon_' . $id ); ?><input type="hidden" name="action" value="gn_coupon_action"><input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"><button class="button" name="operation" value="resend">Resend same coupon</button></form>
		<?php endif; ?></div><?php
	}

	public function action() {
		global $wpdb;
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Access denied.', '', array( 'response' => 403 ) ); }
		$id = absint( self::input( $_POST, 'id' ) );
		check_admin_referer( 'gn_coupon_' . $id );
		$op = self::input( $_POST, 'operation' );
		$ok = false;
		if ( 'resend' === $op && Gn_In_Store_Coupons_Store::settings()['enabled'] ) {
			$table = Gn_In_Store_Coupons_Store::table();
			$ok = 1 === $wpdb->query( $wpdb->prepare( "UPDATE $table SET mail_status = 'pending' WHERE id = %d AND status = 'valid' AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP()) AND (mail_status IN ('sent','failed') OR (mail_status = 'sending' AND mail_attempted_at < %s))", $id, gmdate( 'Y-m-d H:i:s', time() - 600 ) ) );
			if ( $ok && ! wp_next_scheduled( 'gn_coupons_send' ) ) { wp_schedule_single_event( time() + 10, 'gn_coupons_send' ); }
		} elseif ( in_array( $op, array( 'redeemed', 'revoked' ), true ) ) {
			$ok = Gn_In_Store_Coupons_Store::transition( $id, $op, self::input( $_POST, 'note' ) );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'gn-in-store-coupons', 'coupon' => $id, 'result' => $ok ? 'ok' : 'unchanged' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
