<?php
/** Permanent issuance ledger and atomic in-store redemption. */
class Gn_In_Store_Coupons_Store {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'gn_in_store_coupons';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::table();
		$collate = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_hash char(64) NOT NULL,
			email varchar(254) NOT NULL,
			customer_name varchar(200) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned DEFAULT NULL,
			code varchar(32) NOT NULL,
			token char(64) NOT NULL,
			source varchar(20) NOT NULL,
			offer longtext NOT NULL,
			status varchar(16) NOT NULL DEFAULT 'valid',
			issued_at datetime NOT NULL,
			expires_at datetime DEFAULT NULL,
			changed_at datetime DEFAULT NULL,
			changed_by bigint(20) unsigned DEFAULT NULL,
			note text NOT NULL,
			mail_status varchar(16) NOT NULL DEFAULT 'pending',
			mail_attempted_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email_hash (email_hash),
			UNIQUE KEY user_id (user_id),
			UNIQUE KEY code (code),
			UNIQUE KEY token (token),
			KEY status (status),
			KEY mail_status (mail_status)
		) $collate;" );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
			update_option( 'gn_coupons_db_version', '1', false );
		}
	}

	public static function settings() {
		return wp_parse_args( get_option( 'gn_coupons_settings', array() ), array(
			'enabled' => 0, 'discount' => 10, 'days' => 30, 'categories' => array(),
			'lists' => array(), 'brand' => get_bloginfo( 'name' ),
			'logo_id' => get_theme_mod( 'custom_logo', 0 ), 'color' => '#2271b1',
			'terms' => 'Valid in store only. Present this coupon before payment. One use only. Cannot be exchanged for cash.',
		) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function status( $coupon ) {
		return 'valid' === $coupon->status && $coupon->expires_at && $coupon->expires_at <= gmdate( 'Y-m-d H:i:s' ) ? 'expired' : $coupon->status;
	}

	public static function issue( $email, $name, $source, $user_id = 0 ) {
		global $wpdb;
		$settings = self::settings();
		if ( ! $settings['enabled'] ) {
			return new WP_Error( 'paused', 'Coupon issuance is paused.' );
		}
		$email = strtolower( trim( $email ) );
		if ( ! is_email( $email ) || strlen( $email ) > 254 ) {
			return new WP_Error( 'email', 'A valid email address is required.' );
		}
		$discount = (float) $settings['discount'];
		if ( $discount <= 0 || $discount > 100 ) {
			return new WP_Error( 'discount', 'Set a discount between 0.01 and 100.' );
		}
		$hash = hash( 'sha256', $email );
		$table = self::table();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email_hash = %s OR (user_id = %d AND user_id > 0)", $hash, $user_id ) );
		if ( $existing ) {
			return new WP_Error( 'already_issued', 'This customer has already received their lifetime coupon.' );
		}
		$categories = array();
		foreach ( $settings['categories'] as $id ) {
			$term = get_term( $id, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				return new WP_Error( 'category', 'An eligible category no longer exists. Review coupon settings.' );
			}
			$categories[] = $term->name;
		}
		$offer = array(
			'discount' => $discount, 'categories' => $categories, 'category_ids' => $settings['categories'],
			'brand' => $settings['brand'], 'logo' => wp_get_attachment_image_url( $settings['logo_id'], 'medium' ),
			'color' => $settings['color'], 'terms' => $settings['terms'],
		);
		// Unique indexes arbitrate concurrent registration and Mail Mint events.
		$result = $wpdb->insert( $table, array(
			'email_hash' => $hash, 'email' => $email, 'customer_name' => mb_substr( sanitize_text_field( $name ), 0, 200 ),
			'user_id' => $user_id ? absint( $user_id ) : null,
			'code' => 'GN-' . strtoupper( bin2hex( random_bytes( 8 ) ) ), 'token' => bin2hex( random_bytes( 32 ) ),
			'source' => $source, 'offer' => wp_json_encode( $offer ), 'status' => 'valid',
			'issued_at' => gmdate( 'Y-m-d H:i:s' ),
			'expires_at' => $settings['days'] ? gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS * $settings['days'] ) : null,
			'note' => '', 'mail_status' => 'pending',
		) );
		if ( false === $result ) {
			return new WP_Error( 'not_issued', 'No coupon was issued. The customer may already have one; check the register.' );
		}
		$id = $wpdb->insert_id;
		if ( ! wp_next_scheduled( 'gn_coupons_send' ) ) {
			wp_schedule_single_event( time() + 10, 'gn_coupons_send' );
		}
		return $id;
	}

	public static function transition( $id, $status, $note = '' ) {
		global $wpdb;
		if ( ! current_user_can( 'manage_woocommerce' ) || ! in_array( $status, array( 'redeemed', 'revoked' ), true ) ) {
			return false;
		}
		$table = self::table();
		// A single conditional write prevents two tills redeeming the same coupon.
		return 1 === $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET status = %s, changed_at = %s, changed_by = %d, note = %s
			WHERE id = %d AND status = 'valid' AND (expires_at IS NULL OR expires_at > %s)",
			$status, gmdate( 'Y-m-d H:i:s' ), get_current_user_id(), sanitize_textarea_field( $note ), $id, gmdate( 'Y-m-d H:i:s' )
		) );
	}

	public static function url( $coupon ) {
		return add_query_arg( 'gn_store_coupon', $coupon->token, home_url( '/' ) );
	}

	public static function send_pending() {
		global $wpdb;
		if ( ! self::settings()['enabled'] || ! Gn_In_Store_Coupons_Eligibility::ready() ) {
			return;
		}
		$table = self::table();
		$ids = $wpdb->get_col( "SELECT id FROM $table WHERE mail_status = 'pending' AND status = 'valid' AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP()) ORDER BY id LIMIT 25" );
		foreach ( $ids as $id ) {
			$claimed = $wpdb->query( $wpdb->prepare( "UPDATE $table SET mail_status = 'sending', mail_attempted_at = %s WHERE id = %d AND mail_status = 'pending'", gmdate( 'Y-m-d H:i:s' ), $id ) );
			if ( 1 !== $claimed ) {
				continue;
			}
			$coupon = self::get( $id );
			if ( 'valid' !== self::status( $coupon ) ) {
				$wpdb->update( $table, array( 'mail_status' => 'cancelled' ), array( 'id' => $id ) );
				continue;
			}
			$offer = json_decode( $coupon->offer, true );
			$body = '<h1>' . esc_html( $offer['brand'] ) . '</h1><p>Your in-store coupon is ready.</p><p><strong>' . esc_html( $offer['discount'] ) . '% off</strong></p>';
			$body .= '<p>Code: <strong>' . esc_html( $coupon->code ) . '</strong></p><p><a href="' . esc_url( self::url( $coupon ) ) . '">View your coupon</a></p>';
			$body .= '<p>Categories: ' . esc_html( implode( ', ', $offer['categories'] ) ?: 'All product categories' ) . '</p>';
			$body .= '<p>' . ( $coupon->expires_at ? 'Valid until ' . esc_html( get_date_from_gmt( $coupon->expires_at, 'j M Y H:i' ) ) : 'No expiry date' ) . '</p>';
			if ( $offer['logo'] ) { $body = '<p><img width="180" src="' . esc_url( $offer['logo'] ) . '" alt="' . esc_attr( $offer['brand'] ) . '"></p>' . $body; }
			$body .= '<p>' . esc_html( $offer['terms'] ) . '</p><p>In-store use only. This code does not work at online checkout.</p>';
			$sent = wp_mail( $coupon->email, sprintf( 'Your %s in-store coupon', $offer['brand'] ), $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			$wpdb->update( $table, array( 'mail_status' => $sent ? 'sent' : 'failed' ), array( 'id' => $id ) );
		}
		if ( count( $ids ) === 25 && ! wp_next_scheduled( 'gn_coupons_send' ) ) {
			wp_schedule_single_event( time() + 60, 'gn_coupons_send' );
		}
	}
}
