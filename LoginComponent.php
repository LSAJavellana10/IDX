<?php

namespace Contempo\IDXPro;

use Contempo\IDXPro\Common\EnqueueAssets;

class LoginComponent {
	public function __construct() {
		add_action( 'init', [$this, 'init'] );
	}

	public function init() {
		if ( !is_admin() && !wp_doing_ajax() ) {
			EnqueueAssets::register_from_asset_file( 'component-login', CT_IDX_PP_FILE );
			EnqueueAssets::vars( 'component-login', 'CT_IDX_APP', ArchiveApp::get_data( ['is_widget' => true] ) );
		}
	}
}
