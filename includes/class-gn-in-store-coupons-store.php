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
			'enabled' => 0, 'customer_enabled' => 0, 'customer_list' => 0, 'discount' => 15, 'minimum_purchase' => 150, 'days' => 0,
			'lists' => array(), 'brand' => get_bloginfo( 'name' ),
			'logo_id' => get_theme_mod( 'custom_logo', 0 ), 'color' => '#2271b1',
			'terms' => 'Valid in store only. Present this coupon before payment. One use only. Cannot be exchanged for cash.',
		) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function discount_label( $offer ) {
		return 'fixed' === ( $offer['discount_type'] ?? 'percent' )
			? self::euros( $offer['discount'] ) : $offer['discount'] . '%';
	}

	public static function reference( $coupon ) {
		return empty( $coupon->id ) ? 'CPN-PREVIEW' : 'CPN-' . str_pad( (string) $coupon->id, 6, '0', STR_PAD_LEFT );
	}

	public static function euros( $amount ) {
		return html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ) . number_format( (float) $amount, (float) $amount == (int) $amount ? 0 : 2, '.', ',' );
	}

	public static function purchase_label( $offer ) {
		return empty( $offer['minimum_purchase'] ) ? '' : 'On purchases of ' . self::euros( $offer['minimum_purchase'] ) . ' or more';
	}

	public static function status( $coupon ) {
		return 'valid' === $coupon->status && $coupon->expires_at && $coupon->expires_at <= gmdate( 'Y-m-d H:i:s' ) ? 'expired' : $coupon->status;
	}

	public static function issue( $email, $name, $source, $user_id = 0 ) {
		global $wpdb;
		$settings = self::settings();
		if ( ! $settings['enabled'] && ! ( 'woocommerce' === $source && $settings['customer_enabled'] ) ) {
			return new WP_Error( 'paused', 'Coupon issuance is paused.' );
		}
		$email = strtolower( trim( $email ) );
		if ( ! is_email( $email ) || strlen( $email ) > 254 ) {
			return new WP_Error( 'email', 'A valid email address is required.' );
		}
		$discount = (float) $settings['discount'];
		$minimum = (float) $settings['minimum_purchase'];
		if ( ! is_finite( $discount ) || ! is_finite( $minimum ) || $discount < 0.01 || $minimum < $discount ) {
			return new WP_Error( 'discount', 'Set a positive coupon amount and a minimum purchase at least equal to it.' );
		}
		$hash = hash( 'sha256', $email );
		$table = self::table();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email_hash = %s OR (user_id = %d AND user_id > 0)", $hash, $user_id ) );
		if ( $existing ) {
			return new WP_Error( 'already_issued', 'This customer has already received their lifetime coupon.' );
		}
		$offer = array(
			'discount' => round( $discount, 2 ), 'discount_type' => 'fixed', 'currency' => 'EUR', 'minimum_purchase' => round( $minimum, 2 ),
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
		$settings = self::settings();
		if ( ( ! $settings['enabled'] && ! $settings['customer_enabled'] ) || ! Gn_In_Store_Coupons_Eligibility::ready() ) {
			return;
		}
		$table = self::table();
		$source_filter = $settings['enabled'] ? '' : " AND source = 'woocommerce'";
		$ids = $wpdb->get_col( "SELECT id FROM $table WHERE mail_status = 'pending' AND status = 'valid' AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP()) $source_filter ORDER BY id LIMIT 25" );
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
			$body = '<h1>' . esc_html( $offer['brand'] ) . '</h1><p>Your in-store coupon is ready.</p><p><strong>' . esc_html( self::discount_label( $offer ) ) . ' off</strong></p>';
			$body .= '<p>' . esc_html( self::purchase_label( $offer ) ) . '</p>';
			$body .= '<p>Coupon ID: <strong>' . esc_html( self::reference( $coupon ) ) . '</strong></p>';
			$body .= '<p>Code: <strong>' . esc_html( $coupon->code ) . '</strong></p><p><a href="' . esc_url( self::url( $coupon ) ) . '">View your coupon</a></p>';
			$body .= '<p>' . ( $coupon->expires_at ? 'Valid until ' . esc_html( get_date_from_gmt( $coupon->expires_at, 'j M Y H:i' ) ) : 'No expiry date' ) . '</p>';
			if ( $offer['logo'] ) { $body = '<p><img width="180" src="' . esc_url( $offer['logo'] ) . '" alt="' . esc_attr( $offer['brand'] ) . '"></p>' . $body; }
			$body .= '<p>' . esc_html( $offer['terms'] ) . '</p><p>In-store use only. This code does not work at online checkout.</p>';
			$sent = wp_mail( $coupon->email, sprintf( 'Your %s in-store coupon [%s]', $offer['brand'], self::reference( $coupon ) ), $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			$wpdb->update( $table, array( 'mail_status' => $sent ? 'sent' : 'failed' ), array( 'id' => $id ) );
		}
		if ( count( $ids ) === 25 && ! wp_next_scheduled( 'gn_coupons_send' ) ) {
			wp_schedule_single_event( time() + 60, 'gn_coupons_send' );
		}
	}
}
