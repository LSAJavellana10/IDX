<?php

namespace Contempo\IDXPro\Api;

use Contempo\IDXPro\Admin\SettingsPage;
use Contempo\IDXPro\Common\License;

class BaseApi {
	protected static $ids_to_save = [];
	protected static $shut_down_registered = false;

	public static function get_by_by_ids( $ids ) {
		$ids = array_filter( $ids );

		$ids = array_map( 'trim', $ids );

		if ( empty( $ids ) ) {
			return [];
		}

		$size = \count( $ids );

		$url = "api/mls-search/v1/listings?size={$size}&number=0&view=detailed";

		$url = add_query_arg( 'mlsListings', $ids, $url );

		$response = self::fetch_api( $url );

		$result = [];

		if ( isset( $response['content'], $response['content']['listings'] ) ) {
			$result = $response['content']['listings'];
		}

		foreach ( $result as &$listing ) {
			self::prepareListing( $listing, $response['content']['mls'] );
		}

		return $result;
	}

	public static function encrypt_data( $data ) {
		// Convert single digits to string and pad with zeros to ensure consistent encryption
		if (is_numeric($data) && strlen((string)$data) < 5) {
			$data = str_pad((string)$data, 5, '0', STR_PAD_LEFT);
		}

		$cipher = 'chacha20';
		$auth_salt = defined('AUTH_SALT') ? AUTH_SALT : 'no-key';
		$key = substr( hash( 'sha256', $auth_salt, true ), 0, 32 );
		
		$encrypted = openssl_encrypt( $data, $cipher, $key, OPENSSL_RAW_DATA, '' );
		
		if( false === $encrypted ) {
			return $data;
		}

		return bin2hex( $encrypted );
	}

	public static function decrypt_data( $data ) {
		$cipher = 'chacha20';

		if ( is_numeric( $data ) && $data < 1000 ) {
			return (int) $data;
		}

		$auth_salt = defined('AUTH_SALT') ? AUTH_SALT : 'no-key';

		$key = substr( hash( 'sha256', $auth_salt, true ), 0, 32 );

		$dataHex = hex2bin( $data );

		return openssl_decrypt( $dataHex, $cipher, $key, OPENSSL_RAW_DATA, '' );
	}

	public static function http_build_query(
		$query_data,
		$numeric_prefix = '',
		$arg_separator = null
	) {
		$query_data = \is_object( $query_data ) ? get_object_vars( $query_data ) : $query_data;

		$arg_separator = $arg_separator ?? \ini_get( 'arg_separator.output' );

		$query = [];
		foreach ( $query_data as $name => $value ) {
			$value = (array) $value;
			$name = \is_int( $name ) ? $numeric_prefix.$name : $name;
			array_walk_recursive( $value, static function ( $value ) use ( &$query, $name ) {
				$query[] = $name.'='.$value;
			} );
		}

		return implode( $arg_separator, $query );
	}

	public static function array_parse_str( $str, &$arr ) {
		$pairs = explode( '&', $str );

		foreach ( $pairs as $i ) {
			list( $name, $value ) = explode( '=', $i, 2 );

			if ( isset( $arr[$name] ) ) {
				if ( \is_array( $arr[$name] ) ) {
					$arr[$name][] = $value;
				} else {
					$arr[$name] = [$arr[$name], $value];
				}
			} else {
				$arr[$name] = $value;
			}
		}

		return $arr;
	}

	public static function add_query_arg_one( $key, $value, $url ) {
		$parsed_url = parse_url( $url );
		$query = [];

		if ( isset( $parsed_url['query'] ) ) {
			self::array_parse_str( $parsed_url['query'], $query );
		}

		$query[$key] = $value;

		$new_query = self::http_build_query( $query );
		$new_url = $parsed_url['scheme'].'://'.$parsed_url['host'];

		if ( isset( $parsed_url['path'] ) ) {
			$new_url .= $parsed_url['path'];
		}

		$new_url .= '?'.$new_query;

		return $new_url;
	}

	public static function get_mls_ids() {
		$mls_ids = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_mls_ids' );

		return self::decode_mls_ids($mls_ids);
	}

	public static function decode_mls_ids( $mls_ids ) {
		if ( !empty( $mls_ids ) && \is_array( $mls_ids ) ) {
			$mls_ids = array_map( [self::class, 'decrypt_data'], $mls_ids );

			$mls_ids = array_filter( $mls_ids, static function ( $mls_id ) {
				return is_numeric( $mls_id );
			} );
		}

		return $mls_ids ?? [];
	}

	public static function fetch_api( $url, $params = [] ) {
		if ( !License::is_active() ) {
			return [];
		}

		if ( false === self::$shut_down_registered ) {
			add_action( 'shutdown', [__CLASS__, 'save_ids'] );
			self::$shut_down_registered = true;
		}

		$url = 'https://services.realigned.co/'.$url;

		$api_key = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_api_key' );

		foreach ( $params as $key => $value ) {
			$url = self::add_query_arg_one( $key, $value, $url );
		}

		$mls_ids = ! empty( $params['mlses'] ) && is_array( $params['mlses'] ) ? $params['mlses'] :  [];

		if( empty( $mls_ids ) ) {
			$mls_ids = self::get_mls_ids();
		}


		if ( !empty( $mls_ids ) ) {
			$url = self::add_query_arg_one( 'mlses', $mls_ids, $url );
		}


		$ct_idx_domain = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_domain' );
		if ( !empty( $ct_idx_domain ) ) {
			$url = self::add_query_arg_one( 'user_id', $ct_idx_domain, $url );
		}

		$args = [
			'timeout' => 50,
			'method' => 'GET',
			'headers' => [
				'Content-Type' => 'application/json',
				'X-Api-Key' => $api_key,
			],
		];

		$response = wp_safe_remote_get( $url, $args );
		
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$response = wp_remote_retrieve_body( $response );

		$data = json_decode( $response, true );

		$url = str_replace( 'https://services.realigned.co/', '', $url );

		$data['url'] = $url;

		return $data;
	}

	public static function prepareListing(&$listing, $mls = null) {
		//error_log('IDX Detail: prepareListing called with listing keys: ' . (is_array($listing) ? implode(', ', array_keys($listing)) : 'not an array'));
		
		$listing_id = $listing['id'] ?? null;
		$mls_listing_id = isset($listing['mls_listing_id']) ? strtolower($listing['mls_listing_id']) : '';

		//error_log('IDX Detail: prepareListing - listing_id: ' . ($listing_id ?? 'null') . ', mls_listing_id: ' . $mls_listing_id);

		if ($listing_id === null || $mls_listing_id === '') {
			//error_log('IDX Detail: prepareListing - missing required fields, returning early');
			return;
		}

		$optimized_mls_listing_id = str_replace('-', '', $mls_listing_id);
		
		//error_log('IDX Detail: prepareListing - adding to ids_to_save: ' . $listing_id . ' -> ' . $optimized_mls_listing_id);
		self::$ids_to_save[$listing_id] = $optimized_mls_listing_id;
		
		//error_log('IDX Detail: prepareListing - current ids_to_save count: ' . count(self::$ids_to_save));

		if ($mls && !isset($listing['mls'])) {
			$listing['mls'] = current(array_filter($mls, static function ($item) use ($listing) {
				return isset($item['id']) && $item['id'] === $listing['mls_id'];
			}));
		}

		unset($listing['mls_id']);

		if (isset($listing['mls'])) {
			unset($listing['mls']['id']);
		}

		if (!is_array($listing['location'] ?? null)) {
			$listing['location'] = [];
		}

		$slug = \sanitize_title(implode('-', [
			$listing['address'] ?? '',
			$listing['city'] ?? '',
			$listing['state'] ?? '',
			$listing['zip'] ?? '',
			$optimized_mls_listing_id
		]));

		$listing['photos'] = array_values(array_filter($listing['photos'] ?? [], static function ($photo) {
			return isset($photo['content_type']) && strpos($photo['content_type'], 'image/') === 0;
		}));

		$listing['image'] = $listing['hero']['xlarge'] ?? $listing['hero']['medium'] ?? '';

		if (isset($listing['hero']['content_type']) && strpos($listing['hero']['content_type'], 'image/') === false && count($listing['photos'])) {
			$listing['hero'] = array_shift($listing['photos']);
		}

		$listing['slug'] = $slug;
		$listing['url'] = \home_url("/property-search/listings/detail/{$slug}/");
		//$listing['url'] = '#idx-' . \home_url("/property-search/listings/detail/{$slug}/");

		//error_log('IDX Detail: prepareListing - generated slug: ' . $slug);
		//error_log('IDX Detail: prepareListing - generated URL: ' . $listing['url']);

		$listing = self::process_agent($listing);
		
		//error_log('IDX Detail: prepareListing completed');

		return $listing;
	}

	public static function save_ids() {
		$wpdb2 = new \Contempo\IDXPro\Common\Wpdb2();

		$column = $wpdb2->get_col( "SELECT listing_id FROM {$wpdb2->idxIdsTable} WHERE mls_listing_id IN ({$wpdb2->in( array_values( self::$ids_to_save ), '%s' )})" );

		foreach ( $column as $listing_id ) {
			unset( self::$ids_to_save[$listing_id] );
		}

		foreach ( self::$ids_to_save as $listing_id => $mls_listing_id ) {
			$wpdb2->insert( $wpdb2->idxIdsTable, [
				'mls_listing_id' => $mls_listing_id,
				'listing_id' => $listing_id,
			] );
		}
	}

	public static function leave_only_existing_ids( $ids, $cb_onremove = null ) {
		$listings = self::get_by_by_ids( $ids );

		$existing_listings = array_map( static function ( $listing ) {
			return $listing['mls_listing_id'];
		}, $listings );

		$ids = array_filter( $ids, static function ( $id ) use ( $existing_listings, $cb_onremove ) {
			if ( !\in_array( $id, $existing_listings, true ) ) {
				if ( $cb_onremove ) {
					$cb_onremove( $id );
				}

				return false;
			}

			return true;
		} );

		return array_values( array_unique( $ids ) );
	}

	public static function fetch_items( $args = [] ) {
		// Add debugging at the beginning of the function
		//error_log('DEBUG BaseApi - fetch_items args: ' . print_r($args, true));
		
		// Check if this is a map boundary search and use larger size limit
		$isMapSearch = isset($args['polygonMap']) && !empty($args['polygonMap']);
		
		$data = [
			'size' => 50, // Keep default as 50, will be overridden if size is passed
			'view' => 'detailed',
			'mlses' => '',
			'minPhotos' => '1',
		];

		$sort = sanitize_text_field( $args['sort'] ?? '' );

		if ( !empty( $sort ) && \in_array( $sort[0], ['-', '+'], true ) ) {
			$sortBy = '+' === substr( $sort, 0, 1 ) ? '+' : '-';
			$sort = substr( $sort, 1 );

			$data['sort'] = $sortBy.$sort;
		}

		foreach ( ['neighborhoodId', 'since', 'size', 'number', 'state', 'text', 'zipId', 'keywords', 'city', 'mlses', 'cityId', 'neighborhoodName', 'propertyTypes', 'minPrice', 'maxPrice', 'statuses', 'zip', 'subdivisions', 'maxBedrooms', 'maxBathrooms', 'minBedrooms', 'minBathrooms', 'currentPage', 'minSqFt', 'maxSqFt', 'minLotSize', 'maxLotSize', 'minYearBuilt', 'maxYearBuilt', 'order', 'polygonMap', 'listings', 'polygon', 'mlsListings', 'pool', 'waterfront', 'ac', 'heater', 'fireplace', 'view', 'daysOnMarket', 'listingOfficeUid', 'text', 'officeUids'] as $key ) {
			if ( isset( $args[$key] ) && !empty( $args[$key] ) ) {
				switch ( $key ) {
					case 'minPrice':
					case 'maxPrice':
					case 'maxBedrooms':
					case 'minBedrooms':
					case 'maxBathrooms':
					case 'minBathrooms':
					case 'minSqFt':
					case 'maxSqFt':
					case 'minYearBuilt':
					case 'maxYearBuilt':
						$data[$key] = (int) $args[$key];

						break;

					case 'minLotSize':
					case 'maxLotSize':
						$data[$key] = (float) $args[$key];

						break;

					case 'keywords':
					case 'text':
						if ( !isset( $data['neighborhoodId'] ) && !isset( $data['zipId'] ) && !isset( $data['cityId'] ) ) {
							$data['text'] = trim( ( $data['text'] ?? '' ).' '.sanitize_text_field( $args[$key] ) );
						}

						if( $key === 'keywords' ) {
							$keywords = explode( ',', $args[$key] );
							$keywords = array_map( 'trim', $keywords );
							$keywords = array_filter( $keywords );
							$data['text'] = implode( ' ', $keywords );
						}

						break;

					case 'state':
						$data[$key] = strtoupper( sanitize_text_field( $args[$key] ) );

						// no break
					case 'neighborhoodName':
					case 'city':
					case 'neighborhoodId':
					case 'zipId':
					case 'cityId':
					case 'listings':
					case 'mlsListings':
					case 'officeUids':
					case 'listingOfficeUid':
					case 'since':
						$data[$key] = sanitize_text_field( $args[$key] );

						break;

					case 'mlses':
						$data[$key] = sanitize_text_field( $args[$key] );
						$data[$key] = explode( ',', $data[$key] );

						break;

					case 'currentPage':
						$data['number'] = (int) $args[$key] - 1;

						break;

					case 'size':
						$data['size'] = (int) $args[$key];

						break;

					case 'number':
						$data['number'] = (int) $args[$key];
						break;

					case 'minBath':
						$data['minBathrooms'] = (int) $args[$key];

						break;

					case 'polygon':
					case 'polygonMap':
						$json = json_decode( sanitize_text_field( wp_unslash( $args[$key] ) ), true );

						if( is_string($json) ) {
							$json = json_decode( $json, true );
						}

						$jsonAdaptation = [];

						if ( \count( $json ) >= 3 ) {
							$jsonAdaptation = array_reduce( $json, static function ( $carry, $item ) {
								$carry[] = $item['lng'];
								$carry[] = $item['lat'];

								return $carry;
							}, [] );

							$jsonAdaptation[] = $json[0]['lng'];
							$jsonAdaptation[] = $json[0]['lat'];
						}

						$data['polygon'] = $jsonAdaptation;

						break;

					case 'zip':
						$data[$key] = sanitize_text_field( $args[$key] );
						
						break;

					case 'subdivisions':
						$subdivisions_value = sanitize_text_field( $args[$key] );
						$data[$key] = array_map( 'trim', explode( ',', $subdivisions_value ) );

						break;

					case 'propertyTypes':
					case 'statuses':
						$statuses_value = sanitize_text_field( $args[$key] );
						$data[$key] = array_map( 'trim', explode( ',', $statuses_value ) );
						break;
						
					case 'text':
					case 'order':
						$data[$key] = sanitize_text_field( $args[$key] );

						break;

					case 'pool':
					case 'waterfront':
					case 'ac':
					case 'heater':
					case 'fireplace':
					case 'view':
						$data['text'] = trim( ( $data['text'] ?? '' ).' '.$key );

						break;

					case 'daysOnMarket':
						$timezone = wp_timezone();
						$date = new \DateTime( 'now', $timezone );
						$daysOnMarket = (int) $args[$key];
						$interval = new \DateInterval( 'P'.$daysOnMarket.'D' );
						$date->sub( $interval );
						$date->setTimezone( new \DateTimeZone( '-0500' ) );
						$formattedDate = $date->format( 'Y-m-d\TH:i:sP' );
						$data['since'] = $formattedDate;

						break;
				}
			}
		}

		$listingOfficeUid = isset( $args['listingOfficeUid'] ) ? sanitize_text_field( $args['listingOfficeUid'] ?? '' ) : false;

		$ct_idx_search_show_only_office_listings = SettingsPage::getSetting( SettingsPage::SEARCH_SETITNGS_PAGE, 'ct_idx_search_show_only_office_listings' );

		if ( $ct_idx_search_show_only_office_listings && empty( $listingOfficeUid ) ) {
			$ct_idx_office_uids_data = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_office_uids_data' );
			
			if( ! empty( $ct_idx_office_uids_data ) && is_array( $ct_idx_office_uids_data ) ) {
				$office = array_shift( $ct_idx_office_uids_data );

				if( ! empty( $office ) && isset( $office['uid'] ) ) {
					$listingOfficeUid = $office['uid'];
				}
			}
		}

		if ( ! empty( $listingOfficeUid ) ) {
			$data['officeUids'] = $listingOfficeUid;

			$ct_idx_office_uids_data = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_office_uids_data' );

			foreach( $ct_idx_office_uids_data as $ct_idx_office_uids_data_item ) {
				if( is_array( $ct_idx_office_uids_data_item ) && ! empty( $ct_idx_office_uids_data_item['mls_ids'] ) && $ct_idx_office_uids_data_item['uid'] === $listingOfficeUid ) {
					$data['mlses'] = BaseApi::decode_mls_ids( $ct_idx_office_uids_data_item['mls_ids'] );
				}
			}
		}

		$agentId = isset( $args['agentId'] ) ? sanitize_text_field( $args['agentId'] ?? '' ) : false;

		if ( ! empty( $agentId ) ) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[BaseApi fetch_items] Agent ID (license) provided: ' . $agentId);
				error_log('[BaseApi fetch_items] Calling tryFindAgentIdByLicenseAndOffice...');
			}
			
			// Use office UIDs for lookup if provided (from agent profile API)
			// Otherwise fall back to the single office UID from search settings
			$office_uids_for_lookup = isset($args['office_uids_for_lookup']) && is_array($args['office_uids_for_lookup']) 
				? $args['office_uids_for_lookup'] 
				: ($listingOfficeUid ? [$listingOfficeUid] : []);
			
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[BaseApi fetch_items] Office UIDs for agent lookup: ' . print_r($office_uids_for_lookup, true));
			}
			
			$foundAgentId = self::tryFindAgentIdByLicenseAndOffice( $agentId, $office_uids_for_lookup );

			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[BaseApi fetch_items] tryFindAgentIdByLicenseAndOffice returned: ' . ($foundAgentId ? $foundAgentId : 'FALSE/EMPTY'));
			}

			if ( !empty( $foundAgentId ) ) {
				$agentId = $foundAgentId;
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[BaseApi fetch_items] Using MLS agent ID: ' . $agentId);
				}
			} else {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[BaseApi fetch_items] WARNING: Could not find MLS agent ID for license: ' . $agentId);
					error_log('[BaseApi fetch_items] Will attempt to use license number as agent ID (may not work)');
				}
			}
		}

		if ( !empty( $agentId ) ) {
			$data['listingAgentId'] = $agentId;
			unset( $data['officeUids'] );
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[BaseApi fetch_items] Final listingAgentId in request: ' . $agentId);
			}
		}

		if( isset( $data['officeUids'] ) && isset( $data['listingOfficeUid'] ) ) {
			unset( $data['listingOfficeUid'] );
		}

		$result = self::fetch_api( 'api/mls-search/v1/listings', $data );

		// Add debug logging for the API response
		if (!isset($result['content'])) {
			error_log('DEBUG BaseApi - API response error: ' . print_r($result, true));
		}

		if ( isset( $result['content']['listings'] ) ) {
			foreach ( $result['content']['listings'] as &$listing ) {
				self::prepareListing( $listing, $result['content']['mls'] );
			}
		}

		// Log the final request parameters
		//error_log('IDX Final API Request: ' . print_r($data, true));
		
		return $result;
	}

	public static function tryFindAgentIdByLicenseAndOffice( $license_number, $office_uids ) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[tryFindAgentId] === START ===');
			error_log('[tryFindAgentId] License number: ' . $license_number);
			error_log('[tryFindAgentId] Office UIDs: ' . print_r($office_uids, true));
		}
		
		// Normalize office_uids to array
		if (!is_array($office_uids)) {
			$office_uids = !empty($office_uids) ? [$office_uids] : [];
		}
		
		$license_numbers_cache = get_transient( 'ct_idx_pp_license_numbers' ) ?? [];

		if( !is_array($license_numbers_cache)  ) {
			$license_numbers_cache = [];
		}

		if ( isset( $license_numbers_cache[ $license_number ] ) ) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[tryFindAgentId] Found in cache: ' . $license_numbers_cache[ $license_number ]);
				error_log('[tryFindAgentId] === END (cached) ===');
			}
			return $license_numbers_cache[ $license_number ];
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[tryFindAgentId] Not in cache, searching MLS...');
		}

		$mls_ids = self::get_mls_ids();
		
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[tryFindAgentId] MLS IDs to search: ' . print_r($mls_ids, true));
		}

		$foundAgentId = false;

		// If we have office UIDs, search within each office
		if (!empty($office_uids)) {
			foreach ($office_uids as $office_uid) {
				if (empty($office_uid)) continue;
				
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[tryFindAgentId] Searching in office: ' . $office_uid);
				}
				
				foreach ( $mls_ids as $mls_id ) {
					$fetch_args = [
						'size' => 500,
						'view' => 'card',
						'mlses' => $mls_id,
						'listingOfficeUid' => $office_uid,
					];
					
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('[tryFindAgentId] Fetching listings for MLS: ' . $mls_id . ', Office: ' . $office_uid);
					}
					
					$listings = self::fetch_items( $fetch_args );
					
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('[tryFindAgentId] Fetched ' . count($listings['content']['listings'] ?? []) . ' listings');
					}
			
					if ( isset( $listings['content']['listings'] ) ) {
						foreach ( $listings['content']['listings'] as $listing ) {
							// Check listing agent
							if ( isset( $listing['agents']['listing_agent']['license_number'] ) && $listing['agents']['listing_agent']['license_number'] === $license_number ) {
								$foundAgentId = $listing['agents']['listing_agent']['id'];
								if (defined('WP_DEBUG') && WP_DEBUG) {
									error_log('[tryFindAgentId] FOUND as listing_agent! MLS Agent ID: ' . $foundAgentId);
								}
								break 3;
							}
							// Check co-listing agent
							if ( isset( $listing['agents']['co_listing_agent']['license_number'] ) && $listing['agents']['co_listing_agent']['license_number'] === $license_number ) {
								$foundAgentId = $listing['agents']['co_listing_agent']['id'];
								if (defined('WP_DEBUG') && WP_DEBUG) {
									error_log('[tryFindAgentId] FOUND as co_listing_agent! MLS Agent ID: ' . $foundAgentId);
								}
								break 3;
							}
							// Check selling agent
							if ( isset( $listing['agents']['selling_agent']['license_number'] ) && $listing['agents']['selling_agent']['license_number'] === $license_number ) {
								$foundAgentId = $listing['agents']['selling_agent']['id'];
								if (defined('WP_DEBUG') && WP_DEBUG) {
									error_log('[tryFindAgentId] FOUND as selling_agent! MLS Agent ID: ' . $foundAgentId);
								}
								break 3;
							}
							// Check co-selling agent
							if ( isset( $listing['agents']['co_selling_agent']['license_number'] ) && $listing['agents']['co_selling_agent']['license_number'] === $license_number ) {
								$foundAgentId = $listing['agents']['co_selling_agent']['id'];
								if (defined('WP_DEBUG') && WP_DEBUG) {
									error_log('[tryFindAgentId] FOUND as co_selling_agent! MLS Agent ID: ' . $foundAgentId);
								}
								break 3;
							}
						}
					}
				}
			}
		} else {
			// No office UIDs provided - search across all MLSs without office filter
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[tryFindAgentId] No office UIDs provided, searching across all MLSs');
			}
			
			foreach ( $mls_ids as $mls_id ) {
				$fetch_args = [
					'size' => 500,
					'view' => 'card',
					'mlses' => $mls_id,
				];
				
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[tryFindAgentId] Fetching listings for MLS: ' . $mls_id);
				}
				
				$listings = self::fetch_items( $fetch_args );
				
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[tryFindAgentId] Fetched ' . count($listings['content']['listings'] ?? []) . ' listings');
				}
		
				if ( isset( $listings['content']['listings'] ) ) {
					foreach ( $listings['content']['listings'] as $listing ) {
						// Check listing agent
						if ( isset( $listing['agents']['listing_agent']['license_number'] ) && $listing['agents']['listing_agent']['license_number'] === $license_number ) {
							$foundAgentId = $listing['agents']['listing_agent']['id'];
							if (defined('WP_DEBUG') && WP_DEBUG) {
								error_log('[tryFindAgentId] FOUND as listing_agent! MLS Agent ID: ' . $foundAgentId);
							}
							break 2;
						}
						// Check co-listing agent
						if ( isset( $listing['agents']['co_listing_agent']['license_number'] ) && $listing['agents']['co_listing_agent']['license_number'] === $license_number ) {
							$foundAgentId = $listing['agents']['co_listing_agent']['id'];
							if (defined('WP_DEBUG') && WP_DEBUG) {
								error_log('[tryFindAgentId] FOUND as co_listing_agent! MLS Agent ID: ' . $foundAgentId);
							}
							break 2;
						}
						// Check selling agent
						if ( isset( $listing['agents']['selling_agent']['license_number'] ) && $listing['agents']['selling_agent']['license_number'] === $license_number ) {
							$foundAgentId = $listing['agents']['selling_agent']['id'];
							if (defined('WP_DEBUG') && WP_DEBUG) {
								error_log('[tryFindAgentId] FOUND as selling_agent! MLS Agent ID: ' . $foundAgentId);
							}
							break 2;
						}
						// Check co-selling agent
						if ( isset( $listing['agents']['co_selling_agent']['license_number'] ) && $listing['agents']['co_selling_agent']['license_number'] === $license_number ) {
							$foundAgentId = $listing['agents']['co_selling_agent']['id'];
							if (defined('WP_DEBUG') && WP_DEBUG) {
								error_log('[tryFindAgentId] FOUND as co_selling_agent! MLS Agent ID: ' . $foundAgentId);
							}
							break 2;
						}
					}
				}
			}
		}

		if( ! empty( $foundAgentId ) ) {
			$license_numbers_cache[ $license_number ] = $foundAgentId;
			set_transient( 'ct_idx_pp_license_numbers', $license_numbers_cache, 60 * 60 * 24 );
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[tryFindAgentId] Cached agent ID: ' . $foundAgentId);
			}
		} else {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('[tryFindAgentId] AGENT NOT FOUND! License number ' . $license_number . ' not found in any listings');
			}
		}
		
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[tryFindAgentId] === END ===');
		}

		return $foundAgentId;
	}

	public static function process_agent( $listing ) {
		$listing['agents'] = $listing['agents'] ?? [];
		$listing_agent = $listing['agents']['listing_agent'] ?? [];

		$ct_idx_global_assigned_user = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_global_assigned_user' );
		$ct_idx_global_assigned_user_offices = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_idx_global_assigned_user_offices' );

		$listing_office_uid = $listing['agents']['listing_agent']['office']['uid'] ?? '';

		$use_agent_data_mode = false;

		if( is_array( $ct_idx_global_assigned_user_offices ) ) {
			foreach ( $ct_idx_global_assigned_user_offices as $ct_idx_global_assigned_user_office ) {
				if ( is_array( $ct_idx_global_assigned_user_office ) && isset( $ct_idx_global_assigned_user_office['uid'] ) && 
					isset( $ct_idx_global_assigned_user_office['use_agent_data_mode'] ) && 
					$ct_idx_global_assigned_user_office['uid'] === $listing_office_uid && $ct_idx_global_assigned_user_office['use_agent_data_mode'] ) {
					$use_agent_data_mode = $ct_idx_global_assigned_user_office['use_agent_data_mode'];
					break;
				}
			}
		}

		if( empty( $use_agent_data_mode ) ) {
			$contact_agent = self::prepare_contact_agent_for_api_user( $ct_idx_global_assigned_user );
			$contact_agent['use_agent_data_mode'] = false;
		} else if( $use_agent_data_mode === 'from_api' ) {
			$contact_agent = [
				'use_agent_data_mode' => $use_agent_data_mode,
				'name' => isset( $listing_agent['name'] ) ? $listing_agent['name'] : '',
				'license_number' => isset( $listing_agent['license_number'] ) ? $listing_agent['license_number'] : '',
				'email' => isset( $listing_agent['email'] ) ? $listing_agent['email'] : '',
				'phone' => isset( $listing_agent['phone'] ) ? $listing_agent['phone'] : '',
				'location' => isset( $listing_agent['location'] ) ? $listing_agent['location'] : '',
				'bio' => isset( $listing_agent['bio'] ) ? $listing_agent['bio'] : '',
				'tags' => isset( $listing_agent['tags'] ) ? $listing_agent['tags'] : [],
				'photo' => isset( $listing['hero']['xlarge'] ) ? $listing['hero']['xlarge'] : '',
				'office' => [
					'name' => isset( $listing_agent['office']['name'] ) ? $listing_agent['office']['name'] : '',
					'phone' => isset( $listing_agent['office']['phone'] ) ? $listing_agent['office']['phone'] : '',
				],
			];
		} else if( $use_agent_data_mode === 'wp_users' ) {
			$contact_agent_from_wp = self::get_contact_agent_from_wp_users( $listing_agent['license_number'] );

			if( $contact_agent_from_wp ) {
				$contact_agent_from_wp['use_agent_data_mode'] = 'wp_users';
				$contact_agent = $contact_agent_from_wp;
			} else {
				$contact_agent = self::prepare_contact_agent_for_api_user( $ct_idx_global_assigned_user );
				$contact_agent['use_agent_data_mode'] = false;
			}
		}
		
		$listing['agents']['contact_agent'] = $contact_agent ?? [];

		return $listing;
	}

	static public function get_contact_agent_from_wp_users( $license_number ) {
		$agentlicenses_and_wp_users = \get_transient( 'agentlicenses_and_wp_users' ) ?? [];

		$contact_agent = false;

		foreach( $agentlicenses_and_wp_users as $agentlicenses_and_wp_user ) {
			
			if( $agentlicenses_and_wp_user['license_number'] === $license_number ) {
				$contact_agent = $agentlicenses_and_wp_user;
				$contact_agent['use_agent_data_mode'] = 'wp_users';
			}
		}

		return $contact_agent;
	}

	static public function prepare_contact_agent_for_api_user( $user_id ) {
		$current_user = get_user_by( 'id', $user_id );
		$first_name = $current_user->first_name;
		$last_name = $current_user->last_name;

		$description = get_user_meta( $current_user->ID, 'description', true );
		$agentlicense = get_user_meta( $current_user->ID, 'agentlicense', true );
		$mobile = get_user_meta( $current_user->ID, 'mobile', true );
		$city = get_user_meta( $current_user->ID, 'city', true );
		$state = get_user_meta( $current_user->ID, 'state', true );
		$title = get_user_meta( $current_user->ID, 'title', true );
		$logo = get_user_meta( $current_user->ID, 'ct_profile_url', true );

		$specialties = get_user_meta( $current_user->ID, 'specialties', true );
		$tagline = get_user_meta( $current_user->ID, 'tagline', true );

		$tags = explode( ',', $specialties );

		$tags = array_filter( array_map( 'trim', $tags ) );

		$companyname = get_user_meta( $current_user->ID, 'companyname', true );

		$email = $current_user->user_email;

		$contact_agent = [
			'name' => implode( ' ', [$first_name, $last_name] ),
			'license_number' => $agentlicense,
			'email' => $email,
			'phone' => $mobile,
			'location' => implode( ', ', array_filter( [$city, $state] ) ),
			'bio' => $description,
			'title' => $title,
			'tagline' => $tagline,
			'tags' => $tags,
			'photo' => $logo,
			'office' => [
				'name' => $companyname,
				'phone' => !empty( $companyname ) ? $mobile : '',
			],
		];

		return $contact_agent;
	}


	public static function get_walkscore_data( $address, $lat, $lon ) {
		$transient_name = 'ct_walkscore_data_'.md5( $address . $lat . $lon );

		$data = \wp_cache_get( $transient_name );

		if ( false === $data ) {
			$data = self::get_walkscore_data_fetch( $address, $lat, $lon );

			if ( $data && isset( $data['success'] ) ) {
				\wp_cache_set( $transient_name, $data, 60 * 60 * 24 );
			}
		}

		return $data;
	}


	public static function get_yelp_data( $category, $location, $limit ) {
		$transient_name = 'ct_yelp_data_'.md5( $category.$location.$limit );

		$yelp_data = \wp_cache_get( $transient_name );

		if ( false === $yelp_data ) {
			$yelp_data = self::get_yelp_data_fetch( $category, $location, $limit );

			if ( $yelp_data && isset( $yelp_data['success'] ) ) {
				\wp_cache_set( $transient_name, $yelp_data, 60 * 60 * 24 );
			}
		}

		return $yelp_data;
	}


	public static function get_walkscore_data_fetch( $address, $lat, $lon ) {
		// const url = `https://api.walkscore.com/score?format=json&address=${encodeURIComponent(address)}&lat=${lat}&lon=${lon}&transit=1&bike=1&wsapikey=${wsApiKey}`;
		$url = 'https://api.walkscore.com/score?format=json';

		$url = add_query_arg( [
			'format' => 'json',
			'transit' => '1',
			'bike' => '1',
			'address' => $address,
			'lat' => $lat,
			'lon' => $lon,
			'wsapikey' => SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_walkscore_api_key' ),
		], $url );

		$args = [
			'timeout' => 50,
			'method' => 'GET',
			'user-agent' => 'Server',
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		$response = wp_safe_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$response = wp_remote_retrieve_body( $response );

		$json = json_decode( $response, true );

		return $json;
	}


	public static function get_yelp_data_fetch( $category, $location, $limit ) {
		$url = 'https://api.yelp.com/v3/businesses/search';

		$url = add_query_arg( [
			'term' => $category,
			'location' => $location,
			'limit' => $limit,
		], $url );

		$api_key = SettingsPage::getSetting( SettingsPage::GENERAL_SETITNGS_PAGE, 'ct_yelp_api_key' );

		$args = [
			'timeout' => 50,
			'method' => 'GET',
			'user-agent' => 'Server',
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => "Bearer {$api_key}",
			],
		];

		$response = wp_safe_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
			];
		}

		$response = wp_remote_retrieve_body( $response );

		$json = json_decode( $response, true );

		return $json;
	}

	public static function get_leads() {
		if( !self::can_assign_lead() ) {
			return [];
		}

		$users = get_users( [
			'role__in' => ['buyer'],
			'orderby' => 'display_name',
			'order' => 'ASC',
		] );

		$users = array_map( static function ( $user ) {
			return [
				'user_id' => $user->ID,
				'user_name' => $user->display_name,
			];
		}, $users );

		return $users;
	}

	public static function can_assign_lead() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user = wp_get_current_user();

		return is_super_admin() 
			||in_array( 'administrator', (array) $user->roles ) 
			|| in_array( 'editor', (array) $user->roles ) 
			|| in_array( 'author', (array) $user->roles ) 
			|| in_array( 'contributor', (array) $user->roles ) 
			|| in_array( 'seller', (array) $user->roles ) 
			|| in_array( 'agent', (array) $user->roles ) 
			|| in_array( 'broker', (array) $user->roles );
	}

	public static function get_item_by_id( $mls_listing_id ) {
		//error_log('IDX Detail: get_item_by_id called with MLS listing ID: ' . $mls_listing_id);
		
		$wpdb2 = new \Contempo\IDXPro\Common\Wpdb2();

		$internalID = $wpdb2->get_var( "SELECT listing_id FROM {$wpdb2->idxIdsTable} WHERE mls_listing_id = %s", $mls_listing_id );
		
		//error_log('IDX Detail: Database lookup for MLS ID "' . $mls_listing_id . '" returned internal ID: ' . ($internalID ?? 'null'));

		if ( $mls_listing_id && $internalID ) {
			//error_log('IDX Detail: Making API call for internal ID: ' . $internalID);
			
			$listing = self::fetch_api( 'api/mls-search/v1/listings/'.$internalID );
			
			//error_log('IDX Detail: Listing API response type: ' . gettype($listing));
			//error_log('IDX Detail: Listing API response: ' . json_encode($listing));
			
			if ( !$listing || (isset($listing['success']) && !$listing['success']) ) {
				//error_log('IDX Detail: Failed to fetch listing data from API');
				return false;
			}

			self::prepareListing( $listing );
			
			//error_log('IDX Detail: Fetching similar listings...');
			$similarListings = self::fetch_api( "api/mls-search/v1/listings/{$internalID}/similar?size=4&view=detailed&distance=5mi&tolerance=0.2" );
			//error_log('IDX Detail: Similar listings response type: ' . gettype($similarListings));

			//error_log('IDX Detail: Fetching comparable sales...');
			$comparableSales = self::fetch_api( "api/mls-search/v1/listings/{$internalID}/similar?size=9&view=summary&distance=5mi&tolerance=0.2&statuses=sold" );
			//error_log('IDX Detail: Comparable sales response type: ' . gettype($comparableSales));

			$history = false;

			$rupid = $listing['rupid'] ?? null;
			//error_log('IDX Detail: Listing rupid: ' . ($rupid ?? 'null'));

			if( !empty( $rupid) ) {
				//error_log('IDX Detail: Fetching listing history...');
				$history = self::fetch_api( "api/mls-search/v1/listing-history/?rupid={$rupid}&dateSince=1900-09-14T00%3A00Z&view=detailed" );
				//error_log('IDX Detail: History response type: ' . gettype($history));
			}

			$result = [
				'id' => $internalID,
				'listing' => $listing,
				// 'similarListings' => $similarListings ? $similarListings['listings'] : [],
				'comparableSales' => $comparableSales ? $comparableSales['listings'] : [],
				'history' => $history ? $history['content'] : [],
			];
			
			//error_log('IDX Detail: Returning result with keys: ' . implode(', ', array_keys($result)));
			//error_log('IDX Detail: Listing data has keys: ' . (is_array($listing) ? implode(', ', array_keys($listing)) : 'not an array'));
			
			return $result;
		}

		//error_log('IDX Detail: Failed to find listing - MLS ID: ' . $mls_listing_id . ', Internal ID: ' . ($internalID ?? 'null'));
		return false;
	}


	static public function get_item_by_slug( $slug ) {
		//error_log('IDX Detail: get_item_by_slug called with slug: ' . $slug);
		
		$matches = [];
		
		preg_match( '/^(.+?)-([^-]+)$/', $slug, $matches );
		
		//error_log('IDX Detail: Regex matches: ' . json_encode($matches));

		if( ! isset( $matches[2] ) ) {
			//error_log('IDX Detail: No MLS listing ID found in slug');
			return false;
		}
		
		$mls_listing_id = $matches[2];
		//error_log('IDX Detail: Extracted MLS listing ID: ' . $mls_listing_id);

		$data = BaseApi::get_item_by_id( $mls_listing_id );
		
		//error_log('IDX Detail: get_item_by_id returned: ' . (is_array($data) ? 'array with keys: ' . implode(', ', array_keys($data)) : gettype($data)));

		if ( $data ) {
			$result = [
				'listing' => $data['listing'],
				'similarListings' => $data['similarListings'],
				'comparableSales' => $data['comparableSales'],
			];
			
			//error_log('IDX Detail: Returning slug result with keys: ' . implode(', ', array_keys($result)));

			return $result;
		}

		//error_log('IDX Detail: No data returned for MLS listing ID: ' . $mls_listing_id);
		return false;
	}
}
