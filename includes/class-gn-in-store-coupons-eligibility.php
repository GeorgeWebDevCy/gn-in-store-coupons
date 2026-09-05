<?php
/** Mail Mint adapters and new-customer event handlers. */
class Gn_In_Store_Coupons_Eligibility {

	public static function ready() {
		return class_exists( 'WooCommerce' ) && class_exists( '\Mint\MRM\DataBase\Models\ContactModel' ) && class_exists( '\Mint\MRM\DataBase\Models\ContactGroupModel' ) && class_exists( '\Mint\MRM\DataBase\Models\ContactGroupPivotModel' ) && class_exists( '\Mint\MRM\DataStores\ContactData' );
	}

	public static function lists() {
		return self::ready() ? (array) \Mint\MRM\DataBase\Models\ContactGroupModel::get_all_lists_or_tags( 'lists' ) : array();
	}

	public static function customer( $id, $attempt = 0 ) {
		$settings = Gn_In_Store_Coupons_Store::settings();
		if ( ! $settings['enabled'] && ! $settings['customer_enabled'] ) {
			return;
		}
		$user = get_userdata( $id );
		if ( ! $user || ! in_array( 'customer', (array) $user->roles, true ) ) { return; }
		$synced = false;
		$result = new WP_Error( 'dependencies', 'Coupon dependencies unavailable.' );
		if ( self::ready() ) {
			try {
				$synced = self::sync_customer( $user, (int) $settings['customer_list'] );
			} catch ( \Throwable $error ) {
				$synced = false;
			}
			$result = Gn_In_Store_Coupons_Store::issue( $user->user_email, $user->display_name, 'woocommerce', $id );
		}
		$failed = ! $synced || ( is_wp_error( $result ) && 'already_issued' !== $result->get_error_code() );
		if ( $failed && $attempt < 3 ) {
			$args = array( (int) $id, (int) $attempt + 1 );
			if ( ! wp_next_scheduled( 'gn_coupons_customer', $args ) ) {
				wp_schedule_single_event( time() + 60 * ( $attempt + 1 ), 'gn_coupons_customer', $args );
			}
		}
	}

	public static function sync_customer( $user, $list_id ) {
		if ( ! $list_id ) { return true; }
		if ( ! in_array( $list_id, array_map( 'intval', wp_list_pluck( self::lists(), 'id' ) ), true ) ) { return false; }
		$email = strtolower( trim( $user->user_email ) );
		$id = \Mint\MRM\DataBase\Models\ContactModel::get_id_by_email( $email );
		if ( ! $id ) {
			// Store policy subscribes new contacts; this is not an explicit consent record.
			$data = new \Mint\MRM\DataStores\ContactData( $email, array(
				'first_name' => $user->first_name, 'last_name' => $user->last_name,
				'wp_user_id' => $user->ID, 'source' => 'WooCommerce', 'status' => 'subscribed',
			) );
			$id = \Mint\MRM\DataBase\Models\ContactModel::insert( $data );
		}
		if ( ! $id ) { return false; }
		\Mint\MRM\DataBase\Models\ContactGroupPivotModel::add_groups_to_contact( array( array( 'contact_id' => (int) $id, 'group_id' => $list_id ) ) );
		$groups = \Mint\MRM\DataBase\Models\ContactGroupPivotModel::get_groups_to_contact( $id );
		return in_array( $list_id, array_map( 'intval', wp_list_pluck( (array) $groups, 'group_id' ) ), true );
	}

	public static function registration( $id ) {
		$s = Gn_In_Store_Coupons_Store::settings();
		if ( $s['enabled'] || $s['customer_enabled'] ) {
			wp_schedule_single_event( time() + 60, 'gn_coupons_customer', array( (int) $id ) );
		}
	}

	public static function contact( $id ) {
		$settings = Gn_In_Store_Coupons_Store::settings();
		if ( ! self::ready() || ! $settings['enabled'] || ! $settings['lists'] ) {
			return;
		}
		$contact = \Mint\MRM\DataBase\Models\ContactModel::get( $id );
		if ( empty( $contact['email'] ) || 'subscribed' !== $contact['status'] ) {
			return;
		}
		$groups = \Mint\MRM\DataBase\Models\ContactGroupPivotModel::get_groups_to_contact( $id );
		$ids = array_map( 'intval', wp_list_pluck( (array) $groups, 'group_id' ) );
		if ( array_intersect( $ids, $settings['lists'] ) ) {
			$user = get_user_by( 'email', $contact['email'] );
			Gn_In_Store_Coupons_Store::issue( $contact['email'], trim( ( $contact['first_name'] ?? '' ) . ' ' . ( $contact['last_name'] ?? '' ) ), 'mail_mint', $user ? $user->ID : 0 );
		}
	}

	public static function list_applied( $lists, $ids ) {
		// Imports can apply lists to thousands of contacts; the bounded scan handles them.
		if ( Gn_In_Store_Coupons_Store::settings()['enabled'] && ! wp_next_scheduled( 'gn_coupons_scan_soon' ) ) {
			wp_schedule_single_event( time() + 30, 'gn_coupons_scan_soon' );
		}
	}

	public static function scan() {
		self::campaign_scan();
		$settings = Gn_In_Store_Coupons_Store::settings();
		if ( ! self::ready() ) {
			return;
		}
		if ( $settings['enabled'] && $settings['lists'] ) {
			$offset = (int) get_option( 'gn_coupons_scan_offset', 0 );
			$result = \Mint\MRM\DataBase\Models\ContactModel::get_filtered_contacts( array( 'subscribed' ), array(), $settings['lists'], 50, $offset );
			if ( is_array( $result ) && isset( $result['data'] ) ) {
				foreach ( $result['data'] as $contact ) {
					self::contact( $contact['id'] );
				}
				update_option( 'gn_coupons_scan_offset', $offset + 50 < (int) $result['count'] ? $offset + 50 : 0, false );
				if ( $offset + 50 < (int) $result['count'] && ! wp_next_scheduled( 'gn_coupons_scan_soon' ) ) {
					wp_schedule_single_event( time() + 60, 'gn_coupons_scan_soon' );
				}
			}
		}
		Gn_In_Store_Coupons_Store::send_pending();
	}

	public static function campaign_wake() {
		if ( get_option( 'gn_coupons_delivery_campaign_id', 0 ) && ! wp_next_scheduled( 'gn_coupons_campaign' ) ) {
			wp_schedule_single_event( time() + 60, 'gn_coupons_campaign' );
		}
	}

	public static function campaign_scan() {
		global $wpdb;
		$campaign = absint( get_option( 'gn_coupons_delivery_campaign_id', 0 ) );
		if ( ! $campaign || ! self::ready() ) { return; }
		$table = Gn_In_Store_Coupons_Store::table();
		// Read actual sent recipients, never the draft audience or unsent/test emails.
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT b.email_address, c.first_name, c.last_name, u.ID AS user_id
			FROM {$wpdb->prefix}mint_broadcast_emails b
			JOIN {$wpdb->prefix}mint_contacts c ON c.id = b.contact_id AND c.email = b.email_address
			LEFT JOIN {$wpdb->users} u ON u.user_email = b.email_address
			WHERE b.campaign_id = %d AND b.email_type = 'campaign' AND b.status = 'sent' AND c.status = 'subscribed'
			AND NOT EXISTS (SELECT 1 FROM $table g WHERE g.email_hash = SHA2(LOWER(TRIM(b.email_address)), 256) OR (u.ID > 0 AND g.user_id = u.ID))
			LIMIT 50", $campaign
		) );
		foreach ( (array) $rows as $row ) {
			$name = trim( $row->first_name . ' ' . $row->last_name );
			if ( ! $name && $row->user_id ) {
				$user = get_userdata( $row->user_id );
				if ( $user ) { $name = trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name; }
			}
			Gn_In_Store_Coupons_Store::issue( $row->email_address, $name, 'mail_mint_campaign', (int) $row->user_id );
		}
		Gn_In_Store_Coupons_Store::send_pending();
		$pending = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}mint_broadcast_emails WHERE campaign_id = %d AND status IN ('scheduled','sending') LIMIT 1", $campaign ) );
		if ( $pending || count( (array) $rows ) ) { self::campaign_wake(); }
	}
}
