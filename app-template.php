<?php

/**
 * Template Name: App Listings.
 */

use Contempo\IDXPro\Api\BaseApi;
use Contempo\IDXPro\ArchiveApp;
use Contempo\IDXPro\Common\EnqueueAssets;
use Contempo\IDXPro\Common\TemplateWrapper;

EnqueueAssets::register_from_asset_file( 'app', CT_IDX_PP_FILE );

$app_data = ArchiveApp::get_data();

EnqueueAssets::vars( 'app', 'CT_IDX_APP', $app_data );

add_action( 'wp_enqueue_scripts', static function () {
	wp_dequeue_script( 'gmaps' );
	wp_deregister_script( 'gmaps' );
	wp_dequeue_script( 'gmapsPlaces' );
	wp_deregister_script( 'gmapsPlaces' );
}, 110 );

$listing_id = get_query_var( 'listing_id' );

$listing_data = false;


if( ! empty( $listing_id ) ) {
	$listing_id = sanitize_text_field( $listing_id );
	$listing_data = BaseApi::get_item_by_id( $listing_id );
	
	// $listing_data['listing']['photos'] = array_slice( $listing_data['listing']['photos'], 0, 2 );

	EnqueueAssets::vars( 'app', 'CT_IDX_APP_PRELOADED', $listing_data );

	remove_action('wp_head', '_wp_render_title_tag', 1);

	add_action( 'wp_head', static fn () => ct_listing_add_custom_meta_tags($listing_data), 0 );
	add_action( 'wp_head', static fn () => ct_listing_title_tag($listing_data), 0 );
	add_action( 'wp_head', static fn () => ct_listing_add_schema_markup($listing_data), 1 );
}

function ct_listing_title_tag($listing_data) {
    $listing = $listing_data['listing'] ?? [];
    $address = $listing['formatted_address'] ?? '';
	$mlsID = $listing['mls_listing_id'] ?? '';
    $site_title = get_bloginfo('name');

    if (!empty($address)) {
        echo "<title>$address | $mlsID | $site_title</title>";
    }
}

function ct_listing_add_custom_meta_tags( $listing_data ) {
	$listing = $listing_data['listing'] ?? [];
    $image_url = $listing['hero']['xlarge'] ?? '';
    $image_content_type = $listing['hero']['content_type'] ?? '';
    $image_caption = $listing['hero']['caption'] ?? '';

	$page_title = $listing['formatted_address'] . ' | ' . $listing['mls_listing_id'] . ' | ' . get_bloginfo( 'name' );
	$site_description_tabline = get_bloginfo( 'description' );

    echo '<meta property="og:locale" content="en_US" />' . "\n";
    echo '<meta property="og:type" content="article" />' . "\n";

	if( ! empty( $page_title ) ) {
    	echo '<meta property="og:title" content="' . esc_attr( $page_title ) . '" />' . "\n";
	}

	if( ! empty( $site_description_tabline ) ) {
    	echo '<meta property="og:description" content="' . esc_attr( $site_description_tabline ) . '" />' . "\n";
	}

	if( ! empty( $listing['url'] ) ) {
    	echo '<meta property="og:url" content="' . esc_url( $listing['url'] ) . '" />' . "\n";
	}

    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

	if( ! empty( $image_url ) ) {
		echo '<meta property="og:image" content="' .  esc_url( $image_url ) . '" />' . "\n";
		echo '<meta property="og:image:secure_url" content="' . esc_url( $image_url ) . '" />' . "\n";
		echo '<meta property="og:image:alt" content="' . esc_attr( ! empty( $image_caption ) ? $image_caption : $listing['formatted_address'] ) . '" />' . "\n";

		if( ! empty( $image_content_type ) ) {
			echo '<meta property="og:image:type" content="' . esc_attr( $image_content_type ) . '" />' . "\n";
		}
	}

    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";

	if( ! empty( $page_title ) ) {
    	echo '<meta name="twitter:title" content="' . esc_attr( $page_title ) . '" />' . "\n";
	}

	if( ! empty( $site_description_tabline ) ) {
    	echo '<meta name="twitter:description" content="' . esc_attr( $site_description_tabline ) . '" />' . "\n";
	}

	if( ! empty( $image_url ) ) {
    	echo '<meta name="twitter:image" content="' . $image_url . '" />' . "\n";
	}
}

function schema_entity_type($type) {
    $map = [
        'single' => 'SingleFamilyResidence',
        'condo' => 'Condominium',
        'townhouse' => 'SingleFamilyResidence',
        'multi' => 'MultiFamilyResidence',
        'mobile' => 'MobileHome',
        'land' => 'Place',
        'commercial' => 'Place',
        'lease' => 'Place',
        'other' => 'Place',
    ];
    return $map[strtolower($type)] ?? 'Place';
}

function ct_listing_add_schema_markup( $listing_data ) {
	if ( ! $listing_data || !isset($listing_data['listing'])) {
		return;
	}

	$listing = $listing_data['listing'];
	
	// Determine availability based on listing status
	$availability = 'https://schema.org/InStock'; // default
	$listingStatus = strtolower($listing['status'] ?? '');
	if (in_array($listingStatus, ['sold', 'closed', 'withdrawn'])) {
		$availability = 'https://schema.org/SoldOut';
	} elseif (in_array($listingStatus, ['pending', 'under_contract', 'backup'])) {
		$availability = 'https://schema.org/LimitedAvailability';
	}

	$longitude = $listing['location'][0] ?? '';
	$latitude = $listing['location'][1] ?? '';
	
	$listingUrl = $listing['url'] ?? '';
	
	$schema = [
		'@context' => 'https://schema.org',
		'@type' => 'RealEstateListing',
		'@id' => $listingUrl,
		'name' => $listing['formatted_address'] ?? '',
		'description' => $listing['description'] ?? '',
		'url' => $listingUrl,
		'image' => array_values(array_filter(array_map(function($photo) {
			return $photo['xlarge'] ?? $photo['large'] ?? $photo['medium'] ?? '';
		}, $listing['photos'] ?? []))),
		'datePosted' => $listing['list_date'] ?? '',
		'dateModified' => $listing['mls_update_date'] ?? '',
		'provider' => [
			'@type' => 'Organization',
			'name' => $listing['mls']['name'] ?? '',
			'url' => $listing['mls']['url'] ?? ''
		],
		'sourceOrganization' => [
			'@type' => 'Organization',
			'name' => $listing['mls']['name'] ?? ''
		],
		'listingAgent' => [
			'@type' => 'RealEstateAgent',
			'@id' => $listingUrl . '#agent',
			'name' => $listing['agents']['listing_agent']['name'] ?? '',
			'telephone' => $listing['agents']['listing_agent']['phone'] ?? '',
			'email' => $listing['agents']['listing_agent']['email'] ?? '',
			'worksFor' => [
				'@type' => 'RealEstateAgent',
				'@id' => $listingUrl . '#office',
				'name' => $listing['agents']['listing_agent']['office']['name'] ?? '',
				'telephone' => $listing['agents']['listing_agent']['office']['phone'] ?? ''
			]
		],
		'mainEntity' => [
			'@type' => schema_entity_type($listing['property_type'] ?? ''),
			'@id' => $listingUrl . '#property',
			'name' => $listing['formatted_address'] ?? '',
			'description' => $listing['description'] ?? '',
			'address' => [
				'@type' => 'PostalAddress',
				'streetAddress' => $listing['address'] ?? '',
				'addressLocality' => $listing['city'] ?? '',
				'addressRegion' => $listing['state'] ?? '',
				'postalCode' => $listing['zip'] ?? '',
				'addressCountry' => 'US'
			],
			'geo' => [
				'@type' => 'GeoCoordinates',
				'latitude' => $latitude,
				'longitude' => $longitude
			],
			'floorSize' => [
				'@type' => 'QuantitativeValue',
				'value' => $listing['square_feet'] ?? '',
				'unitText' => 'square feet'
			],
			'numberOfRooms' => ($listing['bedrooms'] ?? 0) + ($listing['bathrooms'] ?? 0),
			'numberOfBedrooms' => $listing['bedrooms'] ?? '',
			'numberOfBathroomsTotal' => $listing['bathrooms'] ?? '',
			'yearBuilt' => $listing['year_built'] ?? '',
			'amenityFeature' => array_values(array_filter(array_map(function($feature) {
				return $feature ? [
					'@type' => 'LocationFeatureSpecification',
					'name' => $feature,
					'value' => true
				] : null;
			}, [
				($listing['pool'] ?? false) ? 'Pool' : null,
				($listing['ac'] ?? false) ? 'Air Conditioning' : null,
				($listing['heater'] ?? false) ? 'Heating' : null,
				($listing['fireplace'] ?? false) ? 'Fireplace' : null,
				($listing['waterfront'] ?? false) ? 'Waterfront' : null,
				($listing['view'] ?? false) ? 'View' : null
			]))),
			'offers' => [
				'@type' => 'Offer',
				'price' => $listing['price'] ?? '',
				'priceCurrency' => 'USD',
				'availability' => $availability,
				'itemCondition' => 'https://schema.org/NewCondition',
				'validFrom' => $listing['list_date'] ?? '',
				'businessFunction' => 'https://schema.org/Sell',
				'priceSpecification' => [
					'@type' => 'PriceSpecification',
					'price' => $listing['price'] ?? '',
					'priceCurrency' => 'USD'
				],
				'seller' => [
					'@type' => 'RealEstateAgent',
					'@id' => $listingUrl . '#agent',
					'name' => $listing['agents']['listing_agent']['name'] ?? '',
					'telephone' => $listing['agents']['listing_agent']['phone'] ?? '',
					'email' => $listing['agents']['listing_agent']['email'] ?? '',
					'worksFor' => [
						'@type' => 'RealEstateAgent',
						'@id' => $listingUrl . '#office',
						'name' => $listing['agents']['listing_agent']['office']['name'] ?? '',
						'telephone' => $listing['agents']['listing_agent']['office']['phone'] ?? ''
					]
				]
			]
		],
		'identifier' => [
			'@type' => 'PropertyValue',
			'name' => 'MLS ID',
			'value' => $listing['mls_listing_id'] ?? ''
		]
	];

	echo '<script type="application/ld+json">';
	echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	echo '</script>' . "\n";
}

function getField($data, $name) {
    if (isset($data['agents']['contact_agent']) && array_key_exists($name, $data['agents']['contact_agent'])) {
        return $data['agents']['contact_agent'][$name];
    } elseif (isset($data['agents']['listing_agent']) && array_key_exists($name, $data['agents']['listing_agent'])) {
        return $data['agents']['listing_agent'][$name];
    }
    
    return '';
}

function formatPropertyType($type) {
	$types = [
        'single' => 'Single Family Home',
        'condo' => 'Condo',
        'townhouse' => 'Townhouse',
        'multi' => 'Multi-Family',
        'mobile' => 'Mobile/Manufactured',
        'land' => 'Land',
        'commercial' => 'Commercial',
        'lease' => 'Lease',
        'other' => 'Other'
    ];

    return isset($types[$type]) ? $types[$type] : ucwords($type);
}

function calculateBadgeText($listing) {
    $now = new DateTime();
    $listDate = new DateTime($listing['list_date']);
    $interval = $now->diff($listDate);
    $hoursSinceListed = ($interval->days * 24) + $interval->h;

    if ($hoursSinceListed < 24) {
        return "New - " . esc_html($hoursSinceListed) . " hours ago";
    } else {
        return esc_html(str_replace('_', ' ', $listing['status']));
    }
}

function ct_listing_to_markup( $data, $listing_id, $app_data ) {
	$site_url = $app_data['site_url'];
	$url_prefix = $app_data['url_prefix'];

	$prepareUrl = function($prefix, $url = []) use ($site_url) {
		array_unshift($url, $prefix);
		array_push($url, '');
		$urlString = implode('/', array_filter($url));
		return $site_url . '/' . preg_replace('/\/+/', '/', $urlString);
	};

	$html = '';

    if ( ! $data || !isset($data['listing'])) {
        $html .= '<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; padding: 6px;">';
        $html .= '<div style="font-size: large; font-weight: bold; margin-bottom: 2px; text-align: center;">Oops! We couldn\'t find a listing with the MLS # ' . $listing_id . '</div>';
        $html .= '<div style="font-size: medium;">But don\'t worry! We have many more waiting for you.</div>';
        $html .= '<a href="' . $prepareUrl($url_prefix, []) . '" style="margin-top: 6px;"><button style="background-color: blue; color: white;">Search MLS</button></a>';
        $html .= '</div>';

        return $html;
    }

    $listing = $data['listing'];
    
    // NOTE: Schema.org JSON-LD is now added to <head> via ct_listing_add_schema_markup()

    $address = $listing['formatted_address'] ?? '';
    $state = $listing['state'] ?? '';
    $city = $listing['city'] ?? '';
    $cityId = $listing['geo_data']['city_id'] ?? '';
    $neighborhood = $listing['geo_data']['neighborhood_name'] ?? '';
    $neighborhoodId = $listing['geo_data']['neighborhood_id'] ?? '';

    $html .= '<div class="listing-breadcrumb">';
		$html .= '<div style="font-size: small;display: flex; justify-content: space-between; align-items: center;">';
		$html .= '<div>Listings</div>';
		$html .= '<div>';
		$html .= '<a href="' . $prepareUrl('', []) . '">Home</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '">Listings</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '?dv=' . $state . '">' . $state . '</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '?cityId=' . $cityId . '&dv=' . $city . '">' . $city . '</a>';

		if ($neighborhood) {
			$html .= ' > <a href="' . $prepareUrl($url_prefix, []) . '?neighborhoodId=' . $neighborhoodId . '&dv=' . $neighborhood . '">' . $neighborhood . '</a>';
		}

		$html .= ' > <span>' . $address . '</span>';
		$html .= '</div>';
		$html .= '</div>';
    $html .= '</div>';

	// Header
	$title = $listing['formatted_address'];
	
	$formattedSquareFeet = false;

	if( isset( $listing['square_feet'] ) && $listing['square_feet'] > 0 ) {
		$pricePerSqFt = round($listing['price'] / $listing['square_feet']);
		$pricePerSqFtDisplay = '$' . number_format($pricePerSqFt, 0, '.', ',') . ' per ft²';

		$formattedSquareFeet = number_format($listing['square_feet'], 0, '.', ',');
	}

    $listingDetails = [];
    if ($listing['property_type'] === 'land') {
        $listingDetails = $listing['lot_size_display'] ? [$listing['lot_size_display'] . ' Acres'] : [];
    } elseif ($listing['property_type'] === 'commercial') {
        $squareFeetDisplay = $formattedSquareFeet ? $formattedSquareFeet . ' Sq Ft' : null;
        $lotSizeDisplay = $listing['lot_size_display'] ? $listing['lot_size_display'] . ' Acres' : null;
        $listingDetails = [$squareFeetDisplay, $lotSizeDisplay];
    } else {
        $bedroomDisplay = $listing['bedrooms'] ? $listing['bedrooms'] . ' Bed' : null;
        $bathroomDisplay = $listing['bathrooms'] ? $listing['bathrooms'] . ' Bath' : null;
        $squareFeetDisplay = $formattedSquareFeet ? $formattedSquareFeet . ' Sq Ft' : null;
        $lotSizeDisplay = $listing['lot_size_display'] ? $listing['lot_size_display'] . ' Acres' : null;
        $listingDetails = [$bedroomDisplay, $bathroomDisplay, $squareFeetDisplay, $lotSizeDisplay];
    }
	
	$listingDetails = array_filter($listingDetails);

    $listingDetailsHtml = '';
    foreach ($listingDetails as $detail) {
        if ($detail) {
            $listingDetailsHtml .= "<div class='listing-detail'>" . esc_html($detail) . "</div>";
        }
    }

	$badgeText = calculateBadgeText($listing);

    $html .= "
        <div class='listing-detail-container'>
			<div class='badge'>" . esc_html($badgeText) . "</div>
            <h1>$title</h1>
            <div class='listing-price'>" . esc_html($listing['price_display']) . "</div>
            <div class='price-per-sqft'>" . esc_html($pricePerSqFtDisplay) . "</div>
            " . ($listingDetailsHtml) . "
            <div class='listing-office'>Listing Office: ". esc_html($listing['agents']['listing_agent']['office']['name']) . "</div>
        </div>
    ";

	// Images 
	$photos = $listing['photos'];

	$html .= '<div class="listing-photos">';
		$html .= '<div class="listing-photos-container">';
			$html .= '<div class="listing-photos-slider">';

			foreach ($photos as $i => $photo) {
				$html .= '<div class="listing-photo" style="position: relative;">';
				$html .= '<img src="' . esc_url($photo['xlarge']) . '" alt="Image ' . ($i + 1) . ' of property listing at ' . esc_attr($listing['formatted_address']) . '">';

				if (isset($listing['mls']['idx_logo_small'])) {
					$html .= '<img src="' . esc_url($listing['mls']['idx_logo_small']) . '" alt="' . esc_attr($listing['mls']['name']) . '" style="position: absolute; top: 3px; left: 3px; width: 40px; padding: 1px; border-radius: 4px; background-color: white;">';
				}

				$html .= '</div>';
			}

			$html .= '</div>';
		$html .= '</div>';
	$html .= '</div>';


	// Description part
	$title = $listing['formatted_address'];
    $description = $listing['description'];
    $listDate = (new DateTime($listing['list_date']))->format('m/d/Y h:i:s a');
    $mlsUpdateDate = (new DateTime($listing['mls_update_date']))->format('m/d/Y h:i:s a');
    $daysOnMarket = (new DateTime())->diff(new DateTime($listDate))->format('%a') + 1;

    $listingAgentName = $listing['agents']['listing_agent']['name'] ?? '';
    $listingAgentLicense = $listing['agents']['listing_agent']['license_number'] ?? '';
    $listingAgentOffice = $listing['agents']['listing_agent']['office']['name'] ?? '';
    $listingAgentOfficePhone = $listing['agents']['listing_agent']['office']['phone'] ?? '';
    $mlsName = $listing['mls']['name'];
    $mlsListingId = $listing['mls_listing_id'];

    $html .= "
	<div class='listing-detail'>
		<div class='description'>" . esc_html($description) . "</div>
		<div class='listing-info'>
			<div class='agent-info'>
				<p><strong>Listing Agent:</strong> " . esc_html($listingAgentName) . "</p>
				<p><strong>License Number:</strong> " . esc_html($listingAgentLicense) . "</p>
				<p><strong>Offered by:</strong> " . esc_html($listingAgentOffice) . "</p>
				<p><strong>Office Phone:</strong> " . esc_html($listingAgentOfficePhone) . "</p>
			</div>
			<div class='mls-info'>
				<p><strong>MLS:</strong> #" . esc_html($mlsListingId) . "</p>
				<p>" . esc_html($mlsName) . "</p>
				<p><strong>Date Added:</strong> " . esc_html($listDate) . "</p>
				<p><strong>Last Update:</strong> " . esc_html($mlsUpdateDate) . "</p>
				<p><strong>Days on Market:</strong> " . esc_html($daysOnMarket) . "</p>
			</div>
		</div>
	</div>";

	if( ! is_array( $listing['features'] ) ) {
		$listing['features'] = [];
	}

	$yearBuilt = esc_html($listing['year_built']);
    $propertyType = formatPropertyType(esc_html($listing['property_type']));
    $styleDisplay = esc_html($listing['style']);
    $neighborhoodName = esc_html($listing['geo_data']['neighborhood_name']);
    $parcelNumber = esc_html($listing['parcel_number']);

	$listing['features'][] = [
		"Property" => [
			[ "Year Built" => $yearBuilt ],
			[ "Type"=> $propertyType ],
			[ "Style"=> $styleDisplay ],
			[ "Neighborhood"=> $neighborhoodName ],
			[ "Parcel #"=> $parcelNumber ]
		]
	];

	if( ! empty( $listing['schools'] ) ) {
		$schools = $listing['schools'];
		$schoolsList = [];

		if( isset( $schools['school_district'] ) ) {
			$schoolsList[] = [ "District" => $schools['school_district'] ];
		}

		if( isset( $schools['elementary_school'] ) ) {
			$schoolsList[] = [ "Elementary" => $schools['elementary_school'] ];
		}

		if( isset( $schools['junior_high_school'] ) ) {
			$schoolsList[] = [ "Middle" => $schools['junior_high_school'] ];
		}

		if( isset( $schools['high_school'] ) ) {
			$schoolsList[] = [ "High" => $schools['high_school'] ];
		}

		if( ! empty( $schoolsList ) ) {
			$listing['features'][] = [
				"Schools" => $schoolsList
			];
		}
	}

	// Features
	if (isset($listing['features']) && is_array($listing['features'])) {
        $html .= '<div class="features">';

        foreach ($listing['features'] as $features) {
            foreach ($features as $featureCategory => $feature) {
				$html .= '<h3 class="feature-category">' . esc_html($featureCategory) . '</h3>';
            	$html .= '<ul>';
				
				foreach ($feature as $featureItem) {
					foreach ($featureItem as $key => $value) {
						if( empty( $value ) ) {
							continue;
						}

						$html .= '<li>' . esc_html($key) . ': ' . esc_html($value) . '</li>';
					}
				}

				$html .= '</ul>';
            }
        }
        $html .= '</div>';
    }

	// What's Nearby
	$yelp_types = $app_data['yelp_types'] ?? [];

	foreach ($yelp_types as $category) {
		$resultData = BaseApi::get_yelp_data($category, $listing['formatted_address'], 3);

		if (empty($resultData) || isset($resultData['error'])) {
			continue;
		}

		if ( $resultData['businesses'] && count($resultData['businesses']) > 0) {
			$html .= "<div>";
				$html .= "<h3>";
					$html .= ucwords(str_replace(['_', ' '], ' ', $category));
				$html .= "</h3>";
				$html .= "<ul style='list-style-type: none; padding: 0;'>";
					foreach ($resultData['businesses'] as $business) {
						$html .= "<li style='margin-bottom: 6px;'>";
							$html .= "<div>";
								$html .= "<div>";
									$html .= "<a href='" . htmlspecialchars($business['url']) . "' target='_blank'>" . htmlspecialchars($business['name']) . "</a>";
									$html .= "<span style='text-align: right;'>" . number_format($business['distance'] * 0.000621371, 2) . " miles</span>";
								$html .= "</div>";
								$html .= "<div style='display: flex; justify-content: space-between; width: 100%; align-items: center;'>";
									$html .= "<a href='" . htmlspecialchars($business['url']) . "' target='_blank' style='color: #718096; text-align: right;'>";
										$html .= htmlspecialchars($business['review_count']) . " reviews";
									$html .= "</a>";
								$html .= "</div>";
							$html .= "</div>";
						$html .= "</li>";
					}
				$html .= "</ul>";
			$html .= "</div>";
		}
	}

	if( ! empty( $yelp_types ) ) {
		$yelp_logo_url = plugins_url('public/yelp_logo.svg', CT_IDX_PP_FILE);

		$html .= '<div style="display:flex;">';
			$html .= '<p style="color: #718096; font-size: 12px; margin-right: 4px;">powered by</p>';
			$html .= '<a href="https://yelp.com/" target="_blank">';
				$html .= '<img src="' . esc_url($yelp_logo_url) . '" style="width:40px;" />';
			$html .= '</a>';
		$html .= '</div>';
	}

	if( ! empty( $data['history'] ) ) {
		$changes = $data['history'][0]['listing_history_changes'] ?? [];

		$filteredChanges = array_filter($changes, function ($change) {
			return in_array($change['change_type'], ['STATUS', 'PRICE']);
		});

		usort($filteredChanges, function ($a, $b) {
			return strtotime($b['change_date']) - strtotime($a['change_date']);
		});

		$html .= "<div style='width: 100%; margin-top: 0; margin-bottom: 30px;'>";
			$html .= "<div>"; 

			foreach ($filteredChanges as $index => $change) {
				$date = formatDate($change['change_date']);
				$value = formatValue($change, $listing['square_feet'], $index === 0);

				$html .= "<div style='display: flex; justify-content: space-between;'>";
					$html .= "<span style='color: gray;'>{$date}</span>";
					$html .= $value;
				$html .= "</div>";
			}

			$html .= "</div>";
		$html .= "</div>";
	}

	// Broker
	$html .= '<div class="broker">';
		$html .= '<h3>Listing Broker</h3>';

		$agentName = getField( $listing, 'name');
		$officeName = getField( $listing, 'office')['name'] ?? '';
		$licenseNumber = getField( $listing, 'license_number');
		$email = getField( $listing, 'email');
		$phone = getField( $listing, 'phone');
		$title = getField( $listing, 'title') || 'Broker';
		$location = getField( $listing, 'location');
		$tagline = getField( $listing, 'tagline');
		$bio = getField( $listing, 'bio');
		$tags = getField( $listing, 'tags') ?? [];

		if ($agentName) {
			$html .= '<div><strong>' . esc_html($agentName) . '</strong></div>';
		}
		if ($officeName || $title) {
			$html .= '<div>' . esc_html($title) . ' @ ' . esc_html($officeName) . '</div>';
		}

		$html .= '<div>';
		if ($licenseNumber) {
			$html .= '<div>License #: ' . esc_html($licenseNumber) . '</div>';
		}
		if ($email) {
			$html .= '<div>Email: <a href="mailto:' . esc_html($email) . '">' . esc_html($email) . '</a></div>';
		}
		if ($phone) {
			$html .= '<div>Phone: <a href="tel:' . esc_html($phone) . '">' . esc_html($phone) . '</a></div>';
		}
		if ($location) {
			$html .= '<div>Location: ' . esc_html($location) . '</div>';
		}
		$html .= '</div>';

		if ($tagline) {
			$html .= '<div class="broker_tagline">' . esc_html($tagline) . '</div>';
		}
		if ($bio) {
			$html .= '<div class="broker_bio">' . esc_html($bio) . '</div>';
		}

		if (!empty($tags)) {
			$html .= '<div class="broker_tags">';
			foreach ($tags as $tag) {
				$html .= '<span>' . esc_html($tag) . '</span>';
			}
			$html .= '</div>';
		}

	$html .= '</div>';

	$hide_search_property_types = isset( $app_data['hide_search_property_types'] ) && is_array( $app_data['hide_search_property_types'] ) ? $app_data['hide_search_property_types'] : [];

	// Links

	$city_query = [];

    if ( isset ( $listing['geo_data'] ) && isset ( $listing['geo_data']['city_id'] ) ) {
        $city_query[] = 'cityId=' . $listing['geo_data']['city_id'];
    }
    
    if( ! empty( $city ) ) {
        $city_query[] = 'dv=' . $city;
    }

    $city_query = implode( '&', $city_query );

	$html .= '<div class="listing-links">';
		$html .= '<h3>Property For Sale</h3>';
	
		$html .= '<div>';

		$links = ( [
			[ 'All Listings in ' . $city, $prepareUrl($url_prefix) . '?dv=' . $city ],
			!in_array('single', $hide_search_property_types, true) ? [ 'Single Family Homes in ' . $city, $prepareUrl($url_prefix) . '?propertyTypes=single&' . $city_query ] : null,
			!in_array('condo', $hide_search_property_types, true) ? [ 'Condos in ' . $city, $prepareUrl($url_prefix) . '?propertyTypes=condo&' . $city_query ] : null,
			!in_array('townhouse', $hide_search_property_types, true) ? [ 'Townhouses in ' . $city, $prepareUrl($url_prefix) . '?propertyTypes=townhouse&' . $city_query ] : null,
			!in_array('multi-family', $hide_search_property_types, true) ? [ 'Multi-Family in ' . $city, $prepareUrl($url_prefix) . '?propertyTypes=multi-family&' . $city_query ] : null,
			!in_array('land', $hide_search_property_types, true) ? [ 'Land in ' . $city, $prepareUrl($url_prefix) . '?propertyTypes=land&' . $city_query ] : null,
		] );

		foreach( $links as [$link, $url] ) {
			if( empty( $link ) ) {
				continue;
			}
			$html .= '<a href="' . $url . '">' . $link . '</a><br/>';
		}

		$html .= '</div>';
	$html .= '</div>';

	$html .= '<div class="listing-links">';
		$html .= '<h3>Homes in ' . $city . ' by Price Range</h3>';
	
		$html .= '<div>';

		$links = ( [
			[ 'Homes Under $100k in ' . $city, $prepareUrl($url_prefix) . '?maxPrice=100000&propertyTypes=single,condo,townhouse&' . $city_query ],
			[ 'Homes Under $200k in ' . $city, $prepareUrl($url_prefix) . '?maxPrice=200000&propertyTypes=single,condo,townhouse&' . $city_query ],
			[ 'Homes Under $300k in ' . $city, $prepareUrl($url_prefix) . '?maxPrice=300000&propertyTypes=single,condo,townhouse&' . $city_query ],
			[ 'Homes Under $400k in ' . $city, $prepareUrl($url_prefix) . '?maxPrice=400000&propertyTypes=single,condo,townhouse&' . $city_query ],
			[ 'Homes Under $500k in ' . $city, $prepareUrl($url_prefix) . '?maxPrice=500000&propertyTypes=single,condo,townhouse&' . $city_query ],
			[ 'Homes Over $500k in ' . $city, $prepareUrl($url_prefix) . '?minPrice=500000&propertyTypes=single,condo,townhouse&' . $city_query ],
		] );

		foreach( $links as [$link, $url] ) {
			if( empty( $link ) ) {
				continue;
			}
			$html .= '<a href="' . $url . '">' . $link . '</a><br/>';
		}

		$html .= '</div>';

	$html .= '</div>';

	$html .= '<div class="listing-links">';
		$html .= '<h3>Homes in ' . $city . ' by Bedrooms</h3>';
	
		$html .= '<div>';

		$links = ( [
			[ '1 Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?maxBedrooms=1&' . $city_query ],
			[ '2 Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?maxBedrooms=2&' . $city_query ],
			[ '3 Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?maxBedrooms=3&' . $city_query ],
			[ '4 Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?maxBedrooms=4&' . $city_query ],
			[ '5 Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?maxBedrooms=5&' . $city_query ],
			[ '5+ Bedroom Homes in ' . $city, $prepareUrl($url_prefix) . '?minBedrooms=5&' . $city_query ],
		] );

		foreach( $links as [$link, $url] ) {
			if( empty( $link ) ) {
				continue;
			}
			$html .= '<a href="' . $url . '">' . $link . '</a><br/>';
		}

		$html .= '</div>';
	$html .= '</div>';

	// Footer
	$mls = $listing['mls'] ?? [];
    $agents = $listing['agents'] ?? [];
    $listing_agent = $agents['listing_agent'] ?? [];
    $formattedAddress = esc_html($listing['formatted_address']);
    $mlsListingId = esc_html($listing['mls_listing_id']);
    $mlsName = esc_html($mls['name']);
    
    $complianceText = esc_html($mls['compliance_text']);
    $propertyType = esc_html(formatPropertyType($listing['property_type']));
    $yearBuilt = esc_html($listing['year_built']);
    
	$listingDetailsString = implode(', ', $listingDetails);

	$html .= "<div class='mls-info'>";

		if (!empty($listing_agent['name'])) {
			$agentName = esc_html($listing_agent['name']);
			$agentLicense = esc_html($listing_agent['license_number']);
			$agentPhone = esc_html($listing_agent['phone']);
			$agentOfficeName = esc_html($listing_agent['office']['name'] ?? '');

			$html .= "<p>Last Updated: $mlsUpdateDate</p>";
			$html .= "<p>Offered by: $agentName, $agentLicense, $agentPhone @ $agentOfficeName</p>";
		}

		$html .= "<p>MLS: #$mlsListingId</p>";
		$html .= "<p>Source: $mlsName</p>";
		$html .= "<p>$complianceText</p>";
		$html .= "<a href='" . $listing['url'] . "' rel='canonical'>View Listing</a>";
		
	$html .= "</div>";

	$html .= '<div class="listing-breadcrumb">';
		$html .= '<a href="' . $prepareUrl('', []) . '">Home</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '">Listings</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '?dv=' . $state . '">' . $state . '</a> > ';
		$html .= '<a href="' . $prepareUrl($url_prefix, []) . '?cityId=' . $cityId . '&dv=' . $city . '">' . $city . '</a>';

		if ($neighborhood) {
			$html .= ' > <a href="' . $prepareUrl($url_prefix, []) . '?neighborhoodId=' . $neighborhoodId . '&dv=' . $neighborhood . '">' . $neighborhood . '</a>';
		}

		$html .= ' > <span>' . $address . '</span>';
	$html .= '</div>';

	$html .= "<div class='property-details'>";
	$html .= "<p>$formattedAddress is a $listingDetailsString, $propertyType built in $yearBuilt.</p>";
	$html .= "<p>This property is currently available and was listed by $mlsName on $listDate. The MLS # for the property is MLS #$mlsListingId.</p>";
	
	$html .= 'Powered by <a href="https://contempothemes.com/real-estate-idx/" target="_blank">CT IDX Pro+</a> & RealtyWatch Solutions.';

	$html .= "</div>";


    return $html;
}

function formatDate($date) {
    $d = new DateTime($date);
    return $d->format('m/d/Y');
}

function formatValue($change, $sqft, $isFirstChange) {
    switch ($change['change_type']) {
        case 'PRICE':
			
			if (!isset($change['new_value']) || !is_numeric($change['new_value']) || $change['new_value'] === 'null') {
                return "<span>Price Not Available</span>";
            }

            $formattedPrice = '$' . number_format($change['new_value'], 0, '.', ',');

			$formattedPercentChange = 'Listed';

			$html = "<span>{$formattedPrice}</span>";

			if( isset($change['new_value']) && $change['new_value'] === 'null' ) {
				unset( $change['new_value'] );
			}

			if( isset($change['old_value']) && $change['old_value'] === 'null' ) {
				unset( $change['old_value'] );
			}

			$iconComponent = false;

			if( isset($change['new_value']) && isset($change['old_value']) && $change['old_value'] > 0 ) {
				$percentChange = ((int) $change['new_value'] - (int) $change['old_value']) / (int) $change['old_value'] * 100;
				$formattedPercentChange = abs($percentChange) . '%';
				$isUpwardsTrend = $percentChange > 0;
				$iconComponent = $isUpwardsTrend ? '↑' : '↓';
			}

			if ($sqft !== null && $sqft > 0) {
				$pricePerSqFt = round($change['new_value'] / $sqft);
				$html .= "<span style='color: gray;'>@ $pricePerSqFt per ft²</span>";

				if( $iconComponent ) {
					$html .= "<span style='color: gray;'>{$iconComponent}</span>";
				}
				
				$html .= "<span style='color: gray;'>{$formattedPercentChange}</span>";
			}

			return $html;

            break;
        case 'STATUS':
            $newValue = ucwords(str_replace('_', ' ', strtolower($change['new_value'])));
            return "<span>{$newValue}</span>";
        default:
            return "<span>{$change['new_value']}</span>";
    }
}

TemplateWrapper::get_header();

?>

<?php $current_url = $_SERVER['REQUEST_URI']; ?>
<!--  if ( strpos( $current_url, '/property-search/listings/detail/' ) === false ) : ?>
	  echo do_shortcode('[elementor_template id="26245"]'); ?>
 endif; ?>  -->

<div id="ct-idx-app">
    <div aria-hidden="true" style="position: absolute; overflow: hidden; width: 0; height: 0;">
        <?php echo ct_listing_to_markup( $listing_data, $listing_id, $app_data ); ?>
    </div>
</div>

<?php if ( strpos( $current_url, '/property-search/listings/' ) !== false ) : ?>
<a href="/property-search/listings/" class="floating-link">
    Back to Search
</a>

<!-- Share Section -->
<div class="property-share-div">
    <p>Share this property:</p>
    <div class="property-share">
        <a href="" class="share-facebook" target="_blank" title="Share on Facebook">
            <img src="https://cdn-icons-png.flaticon.com/24/733/733547.png" alt="Facebook">
        </a>
        <a href="" class="share-x" target="_blank" title="Share on X">
            <img class="x" src="https://cdn-icons-png.flaticon.com/24/5968/5968958.png" alt="X">
        </a>
        <a href="" class="share-linkedin" target="_blank" title="Share on LinkedIn">
            <img src="https://cdn-icons-png.flaticon.com/24/3536/3536505.png" alt="LinkedIn">
        </a>
        <a href="" class="share-email" target="_blank" title="Share via Email">
            <img src="https://cdn-icons-png.flaticon.com/24/732/732200.png" alt="Email">
        </a>
    </div>
</div>

<div id="shareEmailPopup" class="email-popup-overlay">
    <input type="hidden" id="currentPropertyHref" name="currentPropertyHref" value="">
    <div class="email-popup">
        <button class="email-popup-close">&times;</button>

        <h3>Share via Email</h3>

        <label>Your Email</label>
        <input type="email" id="senderEmail" readonly>

        <label>Recipient Email</label>
        <input type="email" id="recipientEmail" placeholder="Enter recipient email">

        <button id="sendEmailShare" class="email-send-btn">Send</button>
    </div>
</div>


<?php endif; ?>

<?php if ( strpos( $current_url, '/property-search/listings/' ) !== false ) : ?>
<?php echo do_shortcode('[idx_popup_script]'); ?>
<style>
div#ct-idx-app {
    width: 100%;
}

.floating-link {
    display: none;
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: #054F8F;
    color: #fff;
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    z-index: 999999;
}

.floating-link:hover {
    background: #132F55;
    color: #fff !important;
}

#idxPopup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    z-index: 999999;
}

#idxPopup .popup-content {
    position: relative;
    width: 90%;
    height: 100%;
    margin: 0px auto;
    background: #fff;
    border-radius: 0px;
    padding: 0;
    overflow: hidden;
}

#idxPopup iframe {
    width: 100%;
    height: 100%;
}

#idxPopup .close-popup {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 28px;
    cursor: pointer;
    z-index: 999999;
    background-color: #fff;
    line-height: 1;
    border-radius: 100px;
    width: 28px;
    height: 28px;
    text-align: center;
}

:root {
    --dot-color-start: #054F8F;
    --dot-color-end: #132F55;
    --dot-shadow: rgba(178, 212, 252, 0.7);
}

.dots-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    gap: 10px;
    width: 100%;
}

.dot {
    height: 20px;
    width: 20px;
    margin-right: 10px;
    border-radius: 10px;
    background-color: var(--dot-color-start);
    animation: pulse 1.5s infinite ease-in-out;
    transition: background-color 0.3s;
}

.dot:last-child {
    margin-right: 0;
}

.dot:nth-child(1) {
    animation-delay: -0.3s;
}

.dot:nth-child(2) {
    animation-delay: -0.1s;
}

.dot:nth-child(3) {
    animation-delay: 0.1s;
}

@keyframes pulse {
    0% {
        transform: scale(0.8);
        background-color: var(--dot-color-start);
        box-shadow: 0 0 0 0 var(--dot-shadow);
    }

    50% {
        transform: scale(1.2);
        background-color: var(--dot-color-end);
        box-shadow: 0 0 0 10px rgba(178, 212, 252, 0);
    }

    100% {
        transform: scale(0.8);
        background-color: var(--dot-color-start);
        box-shadow: 0 0 0 0 var(--dot-shadow);
    }
}

@media screen and (max-width: 769px){
	#ct-idxpp-search-form-header{
		z-index: 0;
	}
 	#idxPopup .popup-content {
        margin: 10px auto;
    }
	#idxPopup iframe {
        width: 100%;
        height: 100%;
    }
}
</style>

<div id="idxPopup" style="display:none;">
    <div class="popup-content">
        <span class="close-popup">&times;</span>

        <div class="dots-container">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

        <iframe src="" width="100%" height="800" style="border:0;display:none;" loading="eager" allowfullscreen>
        </iframe>
    </div>
</div>
<?php endif; ?>
<!--  if ( strpos( $current_url, '/property-search/listings/detail/' ) === false ) : ?>
	  echo do_shortcode('[elementor_template id="26248"]'); ?>
	  echo do_shortcode('[elementor_template id="26254"]'); ?>
	  echo do_shortcode('[elementor_template id="26260"]'); ?>
	  echo do_shortcode('[elementor_template id="26263"]'); ?>
 endif; ?> -->

<style>
body.in-iframe .elementor-element-d7864fe {
    display: none !important;
}

body.in-iframe div#wpadminbar {
    display: none !important;
}

body.in-iframe .page-template-app-template header#masthead {
    height: 0px;
}

body.in-iframe .page-template-app-template .site-content {
	margin: 0px auto!important;
}

.page-template-app-template .site-content {
	margin: 30px auto;
}

body.in-iframe footer#colophon {
    display: none !important;
}

body.in-iframe #similar-listings {
    display: none !important;
}

body.in-iframe header#masthead {
    height: 0;
    display: none;
}

body.recently-viewed {
    overflow: hidden;
}
@media screen and (max-width: 769px){
	#ct-idxpp-search-form-header{
		z-index: 0;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const checkModal = setInterval(() => {

        const modalContent = document.querySelector('.chakra-modal__content-container');
        if (!modalContent) return;

		if (modalContent) {

 			const observer = new MutationObserver(() => {
				clearInterval(checkModal);

				// --- Forgot password ---
				const forgotLinks = modalContent.querySelectorAll('.chakra-modal__footer a');
				forgotLinks.forEach(link => {
					if (link.textContent.trim() === "Forgot password?") {
						link.addEventListener('click', function() {

							const waitForForm = setInterval(() => {
								const form = document.querySelector('#forgot-email')
									?.closest('form');
								if (!form) return;

								if (!form.dataset.idxListenerAttached) {
									form.addEventListener('submit', function() {
										document.cookie =
											'idx_reset_redirect=' +
											encodeURIComponent(window.location
												.href) +
											'; path=/; max-age=3600';
									});
									form.dataset.idxListenerAttached = 'true';
								}

								clearInterval(waitForForm);
							}, 100);

						});
					}
				});

				// --- Sign up ---
				const signupLinks = modalContent.querySelectorAll('.chakra-link');

				signupLinks.forEach(link => {
					if (link.textContent.trim() === "Sign up") {
						link.addEventListener('click', function () {

							const waitForForm = setInterval(() => {
								const mobileInput = document.querySelector('#register-mobile');
								const form = mobileInput?.closest('form');
								const submitButton = form?.querySelector('.chakra-button');

								if (!mobileInput || !form || !submitButton) return;

								// Always apply required & listeners to current input
								mobileInput.setAttribute('required', 'required');
								mobileInput.placeholder = 'Mobile*';

								mobileInput.addEventListener('keydown', function(e) {
									// Allow control keys: backspace, delete, tab, arrows
									const allowedKeys = [8, 9, 37, 38, 39, 40, 46]; 
									if (allowedKeys.includes(e.keyCode)) return;

									// // Allow Ctrl/Cmd + A, C, V, X
									// if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;

									// Only allow numbers, parentheses, hyphen, and space
									if (!/[0-9()\-\s]/.test(e.key)) {
										e.preventDefault(); // block any other character
									}
								});

								clearInterval(waitForForm);

							}, 100);

						});
					}
				});
			});

			observer.observe(modalContent, { childList: true, subtree: true });
		}
    }, 100);

});
</script>


<script>
	window.currentUserEmail = "<?php echo wp_get_current_user()->user_email; ?>";
</script>

<script>
	document.addEventListener("DOMContentLoaded", function() {

		window.currentUserEmail = "<?php echo wp_get_current_user()->user_email; ?>";

		if (window.location.pathname.includes("/my/recently-viewed/")) {
			document.body.classList.add("recently-viewed");
		}

		if (window.self !== window.top) {
			document.body.classList.add("in-iframe");
		}

		if (document.referrer === "https://flatfeelv.com/property-search/") {
			const floatingLink = document.querySelector(".floating-link");
			const shareDiv = document.querySelector(".property-share-div");

			if (floatingLink) {
				if (window.currentUserEmail === "") {
					floatingLink.style.display = "none";
				} else {
					floatingLink.style.display = "block";
				}
			}

			if (shareDiv) {
				if (window.currentUserEmail === "") {
					shareDiv.style.display = "none";
				} else {
					shareDiv.style.display = "block";
				}
			}
		}

		if (document.referrer.includes('/property-search/listings/')) {
			if (window.currentUserEmail === "") {
				document.querySelector(".property-share-div").style.display = "none";
			} else {
				document.querySelector(".property-share-div").style.display = "block";
			}
		}

		if (window.location.pathname.includes("/property-search/listings/detail/")) {
			if (window.self === window.top) {
				document.body.classList.add("notin-iframe");
			}

			if (document.body.classList.contains('notin-iframe')) {
				if (window.currentUserEmail === "") {
					document.querySelector(".property-share-div").style.display = "none";
					document.querySelector(".floating-link").style.display = "none";
				} else {
					document.querySelector(".property-share-div").style.display = "block";
					document.querySelector(".floating-link").style.display = "block";
				}
			}
		}

	});
</script>
<script>
	// document.addEventListener("DOMContentLoaded", function() {
	// 	setTimeout(() => {
	// 		const target = document.querySelector('div#ct-idx-app .css-lvox8v');
	// 		if (target) {
	// 		const observer = new MutationObserver(() => {
	// 			document.querySelectorAll('.property-card.css-o8nrp7').forEach(card => {
	// 			if (!card.querySelector('.css-79z73n')) {
	// 				const body = card.querySelector('.property-card__body');
	// 				body?.classList.add('no-inner');
	// 			}
	// 			});
	// 		});

	// 		observer.observe(target, {
	// 			childList: true,
	// 			subtree: true
	// 		});
	// 		}
	// 	},10000);
	// });

	(function waitForShadowRoot() {
		const app = document.querySelector('#ct-idx-app');
		if (!app || !app.shadowRoot) {
		
			requestAnimationFrame(waitForShadowRoot);
			return;
		}

		const shadow = app.shadowRoot;

		// Function to inject styles (only once)
		function injectStyles() {
			if (!shadow.querySelector('#custom-idx-style')) {
				const style = document.createElement('style');
				style.id = 'custom-idx-style';
				style.textContent = `
					header.css-1hvsmbz {
						background: #064f8e!important;
						color: #fff!important;
					}
					h1.chakra-heading {
						font-weight: 600 !important;
					}
					header.css-1hvsmbz .css-1y3f6ad {
						max-width: 1180px !important;
						padding-left: 30px !important;
						padding-right: 30px !important;
						margin: 0 auto !important;
					}
					header.css-1hvsmbz button.chakra-button.css-1edb76o {
						background: #fff !important;
						color: #064f8e !important;
					}
					header.css-1hvsmbz button.chakra-button.css-1edb76o:hover {
						background: #ffffffe6 !important;
					}
					header.css-1hvsmbz button.chakra-button.css-n3canj {
						border: 1px solid #fff !important;
						color: #fff !important;
					}
					header.css-1hvsmbz button.chakra-button.css-n3canj:hover {
						background: #ffffff33 !important;
					}
					.css-kdhan3 .chakra-stack img.chakra-image:hover {
						filter: brightness(0.7)!important;
					}
					.css-kdhan3 .chakra-stack img.chakra-image {
						display: block;
						filter: brightness(1);
						transition: filter 0.5s ease-in-out;
						will-change: filter;
					}
					.chakra-skeleton img.chakra-image:hover{
						filter: brightness(0.7)!important;
					}
					.chakra-skeleton img.chakra-image:hover{
						display: block;
						filter: brightness(1);
						transition: filter 0.5s ease-in-out;
						will-change: filter;
					}
					#ct-idxpp-search-form-header .chakra-button, #ct-search-typeahead, .css-kdhan3 .chakra-stack .css-39jlyn {
						font-weight: 700 !important;
					}
					#ct-idxpp-search-form-header {
						z-index: 98 !important;
					}
					div#ct-search-typeahead ul {
						box-shadow: 6px 7px 15px 0 rgba(0, 0, 0, 0.15);
					}
					#ct-idxpp-searching-params-results .property-card.css-o8nrp7 .property-card__body.no-inner{
						padding-top: 56.5%;
						display: block;
						background: #f1f1f1;
						background-image: url(https://flatfeelv.com/wp-content/uploads/2025/06/New-Logo-1024x232.png);
						background-size: 450px;
						background-repeat: no-repeat;
						background-position: 50% 25%;
					}
					@media screen and (max-width: 769px){
						.page-template-app-template .site-content {
							margin: 60px auto 30px auto;
						}	
						.chakra-modal__content-container {
							overflow: scroll!important;
						}
						section.chakra-modal__content {
							width: 95%!important;
						}
						body.recently-viewed.in-iframe .css-16qtscf, body.recently-viewed.in-iframe .css-vtnthr{
							padding-left: 0!important;
							padding-right: 0!important;
						}
					}
				`;
				shadow.appendChild(style);
			}
		}

		// Initial injection
		injectStyles();

		// Watch for dynamic changes inside shadow root (IDX often renders asynchronously)
		const observer = new MutationObserver(() => {
			injectStyles(); // Re-apply style if new nodes appear
		});

		observer.observe(shadow, {
			childList: true,
			subtree: true
		});
	})();
</script>


<style>
.property-share-div {
    display: none;
    position: fixed;
    bottom: 80px;
    right: 20px;
    font-family: inherit;
    background: #e4e4e4;
    padding: 6px 10px 10px 10px;
    border-radius: 6px;
}

.property-share-div:hover {
    z-index: 999999;
}

.property-share {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center
}

.property-share img.x {
    background-color: #ffffff;
    border-radius: 3px;
    filter: invert(1);
    padding: 4px;
}

.property-share span {
    font-weight: bold;
}

.property-share a img {
    width: 24px;
    height: 24px;
    transition: transform 0.2s;
}

.property-share a img:hover {
    transform: scale(1.2);
}

/* Overlay */
.email-popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.65);
    z-index: 99999;
    justify-content: center;
    align-items: center;
}

/* Popup box */
.email-popup {
    width: 350px;
    max-width: 90%;
    background: #fff;
    padding: 20px 25px;
    border-radius: 12px;
    position: relative;
    animation: popupFade 0.25s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}

/* Fade animation */
@keyframes popupFade {
    from {
        transform: scale(0.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

/* Close button */
.email-popup-close {
    position: absolute;
    right: 10px;
    top: 10px;
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #333;
}

.email-popup-close:hover {
    background: none;
    color: #000;
}

/* Inputs */
.email-popup input {
    width: 100%;
    padding: 10px;
    margin-bottom: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

/* Send button */
.email-send-btn {
    width: 100%;
    padding: 12px;
    background: #0073e6;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.email-send-btn:hover {
    background: #005bb5;
}

.chakra-modal__content .chakra-link {
    font-weight: 600;
}

input[type=password], #register-password, #login-password {
	border-radius: 0.375rem!important;
	border-color: #e2e8f0!important;
}

input#login-password:focus, input#login-password:focus-within, input#login-password:active,
input#register-password:focus, input#register-password:focus-within, input#register-password:active {
    border: 2px solid #045cb4 !important;
}

@media screen and (max-width: 769px){
	.chakra-modal__content-container {
		overflow: scroll!important;
	}
	section.chakra-modal__content {
		width: 95%!important;
	}
}
</style>

<?php TemplateWrapper::get_footer(); ?>