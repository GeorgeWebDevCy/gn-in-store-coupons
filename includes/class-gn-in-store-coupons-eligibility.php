<?php
/** Mail Mint adapters and new-customer event handlers. */
class Gn_In_Store_Coupons_Eligibility {

	public static function ready() {
		return class_exists( 'WooCommerce' ) && class_exists( '\Mint\MRM\DataBase\Models\ContactModel' ) && class_exists( '\Mint\MRM\DataBase\Models\ContactGroupModel' );
	}

	public static function lists() {
		return self::ready() ? (array) \Mint\MRM\DataBase\Models\ContactGroupModel::get_all_lists_or_tags( 'lists' ) : array();
	}

	public static function customer( $id ) {
		if ( ! self::ready() ) {
			return;
		}
		$user = get_userdata( $id );
		if ( $user && in_array( 'customer', (array) $user->roles, true ) ) {
			Gn_In_Store_Coupons_Store::issue( $user->user_email, $user->display_name, 'woocommerce', $id );
		}
	}

	public static function registration( $id ) {
		if ( Gn_In_Store_Coupons_Store::settings()['enabled'] ) {
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
		$settings = Gn_In_Store_Coupons_Store::settings();
		if ( ! self::ready() || ! $settings['enabled'] ) {
			return;
		}
		if ( $settings['lists'] ) {
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
}
