<?php

namespace Contempo\IDXPro;

use Contempo\IDXPro\Common\EnqueueAssets;

class LoginComponent {
	public function __construct() {
		add_action( 'init', [$this, 'init'] );
	}

	public function init() {
		if ( !is_admin() && !wp_doing_ajax() ) {
			// Add timestamp as version to bust cache
			$version = filemtime( CT_IDX_PP_FILE );

			EnqueueAssets::register_from_asset_file( 'component-login', CT_IDX_PP_FILE, $version );

			EnqueueAssets::vars(
				'component-login',
				'CT_IDX_APP',
				array_merge(
					ArchiveApp::get_data( ['is_widget' => true] ),
					[
						'server_time' => $version,  // optional for JS use
					]
				)
			);
		}
	}
}
