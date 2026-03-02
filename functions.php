<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Constants
 */
define( 'ASTRA_THEME_VERSION', '4.11.3' );
define( 'ASTRA_THEME_SETTINGS', 'astra-settings' );
define( 'ASTRA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'ASTRA_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );
define( 'ASTRA_THEME_ORG_VERSION', file_exists( ASTRA_THEME_DIR . 'inc/w-org-version.php' ) );

/**
 * Minimum Version requirement of the Astra Pro addon.
 * This constant will be used to display the notice asking user to update the Astra addon to the version defined below.
 */
define( 'ASTRA_EXT_MIN_VER', '4.11.1' );

/**
 * Load in-house compatibility.
 */
if ( ASTRA_THEME_ORG_VERSION ) {
	require_once ASTRA_THEME_DIR . 'inc/w-org-version.php';
}

/**
 * Setup helper functions of Astra.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-theme-options.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-theme-strings.php';
require_once ASTRA_THEME_DIR . 'inc/core/common-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-icons.php';

define( 'ASTRA_WEBSITE_BASE_URL', 'https://wpastra.com' );

/**
 * ToDo: Deprecate constants in future versions as they are no longer used in the codebase.
 */
define( 'ASTRA_PRO_UPGRADE_URL', ASTRA_THEME_ORG_VERSION ? astra_get_pro_url( '/pricing/', 'free-theme', 'dashboard', 'upgrade' ) : 'https://woocommerce.com/products/astra-pro/' );
define( 'ASTRA_PRO_CUSTOMIZER_UPGRADE_URL', ASTRA_THEME_ORG_VERSION ? astra_get_pro_url( '/pricing/', 'free-theme', 'customizer', 'upgrade' ) : 'https://woocommerce.com/products/astra-pro/' );

/**
 * Update theme
 */
require_once ASTRA_THEME_DIR . 'inc/theme-update/astra-update-functions.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-background-updater.php';

/**
 * Fonts Files
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-font-families.php';
if ( is_admin() ) {
	require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts-data.php';
}

require_once ASTRA_THEME_DIR . 'inc/lib/webfont/class-astra-webfont-loader.php';
require_once ASTRA_THEME_DIR . 'inc/lib/docs/class-astra-docs-loader.php';
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts.php';

require_once ASTRA_THEME_DIR . 'inc/dynamic-css/custom-menu-old-header.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/container-layouts.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/astra-icons.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-walker-page.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-enqueue-scripts.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-gutenberg-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-wp-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/block-editor-compatibility.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/inline-on-mobile.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/content-background.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/dark-mode.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-dynamic-css.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-global-palette.php';

// Enable NPS Survey only if the starter templates version is < 4.3.7 or > 4.4.4 to prevent fatal error.
if ( ! defined( 'ASTRA_SITES_VER' ) || version_compare( ASTRA_SITES_VER, '4.3.7', '<' ) || version_compare( ASTRA_SITES_VER, '4.4.4', '>' ) ) {
	// NPS Survey Integration
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-notice.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-survey.php';
}

/**
 * Custom template tags for this theme.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-attr.php';
require_once ASTRA_THEME_DIR . 'inc/template-tags.php';

require_once ASTRA_THEME_DIR . 'inc/widgets.php';
require_once ASTRA_THEME_DIR . 'inc/core/theme-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/admin-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/sidebar-manager.php';

/**
 * Markup Functions
 */
require_once ASTRA_THEME_DIR . 'inc/markup-extras.php';
require_once ASTRA_THEME_DIR . 'inc/extras.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog-config.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog.php';
require_once ASTRA_THEME_DIR . 'inc/blog/single-blog.php';

/**
 * Markup Files
 */
require_once ASTRA_THEME_DIR . 'inc/template-parts.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-loop.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-mobile-header.php';

/**
 * Functions and definitions.
 */
require_once ASTRA_THEME_DIR . 'inc/class-astra-after-setup-theme.php';

// Required files.
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-helper.php';

require_once ASTRA_THEME_DIR . 'inc/schema/class-astra-schema.php';

/* Setup API */
require_once ASTRA_THEME_DIR . 'admin/includes/class-astra-api-init.php';

if ( is_admin() ) {
	/**
	 * Admin Menu Settings
	 */
	require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-settings.php';
	require_once ASTRA_THEME_DIR . 'admin/class-astra-admin-loader.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
}

/**
 * Metabox additions.
 */
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-boxes.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-box-operations.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-elementor-editor-settings.php';

/**
 * Customizer additions.
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-customizer.php';

/**
 * Astra Modules.
 */
require_once ASTRA_THEME_DIR . 'inc/modules/posts-structures/class-astra-post-structures.php';
require_once ASTRA_THEME_DIR . 'inc/modules/related-posts/class-astra-related-posts.php';

/**
 * Compatibility
 */
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gutenberg.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-jetpack.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/woocommerce/class-astra-woocommerce.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/edd/class-astra-edd.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/lifterlms/class-astra-lifterlms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/learndash/class-astra-learndash.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bb-ultimate-addon.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-contact-form-7.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-visual-composer.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-site-origin.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gravity-forms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bne-flyout.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-ubermeu.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-divi-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-amp.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-yoast-seo.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/surecart/class-astra-surecart.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-starter-content.php';
require_once ASTRA_THEME_DIR . 'inc/addons/transparent-header/class-astra-ext-transparent-header.php';
require_once ASTRA_THEME_DIR . 'inc/addons/breadcrumbs/class-astra-breadcrumbs.php';
require_once ASTRA_THEME_DIR . 'inc/addons/scroll-to-top/class-astra-scroll-to-top.php';
require_once ASTRA_THEME_DIR . 'inc/addons/heading-colors/class-astra-heading-colors.php';
require_once ASTRA_THEME_DIR . 'inc/builder/class-astra-builder-loader.php';

// Elementor Compatibility requires PHP 5.4 for namespaces.
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor-pro.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-web-stories.php';
}

// Beaver Themer compatibility requires PHP 5.3 for anonymous functions.
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-themer.php';
}

require_once ASTRA_THEME_DIR . 'inc/core/markup/class-astra-markup.php';

/**
 * Load deprecated functions
 */
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-filters.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-functions.php';

// Register IDX shortcode
// function flatfeel_idx_shortcode() {
//     // Enqueue IDX scripts and styles
//     wp_enqueue_script(
//         'ct-idx-app',
//         'https://flatfeelv.jenocabrera.tech/wp-content/plugins/ct-idx-pro-plus/build/app.js?ver=88fcf7ee0fe453f5b39c',
//         array('jquery'), // dependencies
//         null,
//         true // load in footer
//     );

//     wp_enqueue_style(
//         'ct-idx-style',
//         'https://flatfeelv.jenocabrera.tech/wp-content/plugins/ct-idx-pro-plus/build/app.css?ver=88fcf7ee0fe453f5b39c',
//         array(),
//         null
//     );

//     // Return the IDX container
//     return '<div id="ct-idx-app"></div>';
// }
// add_shortcode('flatfeel_idx', 'flatfeel_idx_shortcode');

add_filter( 'gform_field_input', 'gf_phone_with_pattern', 10, 5 );
function gf_phone_with_pattern( $input, $field, $value, $lead_id, $form_id ) {
    if ( $field->type == 'phone' ) {
        $input = sprintf(
            '<input name="input_%d" id="input_%d_%d" type="tel" value="%s" class="medium" 
            pattern="[0-9\+\-\(\)\s]*" inputmode="tel" 
            oninput="this.value=this.value.replace(/[^0-9\+\-\(\)\s]/g, \'\')" />',
            $field->id,
            $form_id,
            $field->id,
            esc_attr( $value )
        );
    }
    return $input;
}

function my_elementor_container_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'id' => '' ], $atts );
    if ( empty( $atts['id'] ) ) return '';
    return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $atts['id'] );
}
add_shortcode( 'elementor_template', 'my_elementor_container_shortcode' );


// Shortcode to include IDX popup script
function idx_popup_script_shortcode() {
    ob_start(); // Start output buffering
    ?>
    <script>
    jQuery(document).ready(function() {
        jQuery('.floating-link').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // <-- prevent the click from reaching the global click listener

            // Close the popup
            jQuery('#idxPopup').fadeOut(200);
            const iframe = document.querySelector('#idxPopup iframe');
            if (iframe) iframe.src = "";

            // Redirect the main page
            if (window.self !== window.top) {
                // inside iframe → change parent page
                window.top.location.href = '/property-search/listings/';
            } else {
                // normal page → redirect
                window.location.href = '/property-search/listings/';
            }
        });
    });

    document.addEventListener('click', function(e) {

         if (e.target.closest('.floating-link')) return;
         if (e.target.closest('.property-share a')) return;
         if (e.target.closest('.menu-item')) return;

        const path = e.composedPath();

        // Find the first <a> element with href
        const linkEl = path.find(el => el && el.tagName === 'A' && el.getAttribute('href'));
        if (!linkEl) return;

        const href = linkEl.getAttribute('href');

        // Only intercept property links
        if (href.includes('/property-search/listings/detail/')) {
            // Stop navigation immediately
            e.stopImmediatePropagation(); // stop other click handlers
            e.preventDefault();           // prevent default navigation

            console.log("Popup URL:", href);

            const iframe = document.querySelector('#idxPopup iframe');

            // Reset state
            jQuery('.dots-container').show();
            jQuery('#idxPopup iframe').hide();

            // Set iframe src
            iframe.src = href;

            // Show popup
            jQuery('#idxPopup').fadeIn(200);

            // Detect when iframe has fully loaded
            iframe.onload = function() {
                jQuery('.dots-container').fadeOut(200, function() {
                    jQuery('#idxPopup iframe').fadeIn(200);
                });

                const doc = iframe.contentDocument || iframe.contentWindow.document;

                // Select the share links
                const fbLink = doc.querySelector('.share-facebook');
                const twLink = doc.querySelector('.share-x');
                const liLink = doc.querySelector('.share-linkedin');
                const emailLink = doc.querySelector('.share-email');
                const hiddenHref = doc.querySelector('#currentPropertyHref');

                // Set href dynamically
                if(fbLink) fbLink.href = `https://www.facebook.com/sharer/sharer.php?u=${href}`;
                if(twLink) twLink.href = `https://x.com/intent/tweet?url=${href}&text=Check out this property!`;
                if(liLink) liLink.href = `https://www.linkedin.com/sharing/share-offsite/?url=${href}`;
                if(emailLink) emailLink.href = `mailto:?subject=Property Listing&body=Check out this property: ${href}`;
                    
                if (hiddenHref) {
                    hiddenHref.value = href;
                }
            };

        }
    }, true); // <-- notice the `true` for capturing phase

    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.pathname.includes("/property-search/listings/detail/")) {

            // Use the current page URL
            const href = window.location.href;
            console.log("href:", href); // This will now log the full URL

            // Select the share links
            const fbLink = document.querySelector('.share-facebook');
            const twLink = document.querySelector('.share-x');
            const liLink = document.querySelector('.share-linkedin');
            const emailLink = document.querySelector('.share-email');

            // Set href dynamically
            if (fbLink) fbLink.href = `https://www.facebook.com/sharer/sharer.php?u=${href}`;
            if (twLink) twLink.href = `https://x.com/intent/tweet?url=${href}&text=Check out this property!`;
            if (liLink) liLink.href = `https://www.linkedin.com/sharing/share-offsite/?url=${href}`;
            if (emailLink) emailLink.href = `mailto:?subject=Property Listing&body=Check out this property: ${href}`;
        }
    });

    // Open Email Share Popup
    document.addEventListener('DOMContentLoaded', function() {
        const emailBtn = document.querySelector('.share-email');
        if(emailBtn) {
            emailBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('senderEmail').value = window.currentUserEmail || "";
                document.getElementById('shareEmailPopup').style.display = 'flex';
            });
        }
    });


    // Close popup (X button)
    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.querySelector('.email-popup-close');
        if(closeBtn) {
            closeBtn.addEventListener('click', function() {
                document.getElementById('shareEmailPopup').style.display = 'none';
            });
        }
    });


    // Click outside popup to close
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('shareEmailPopup');
        if(popup) {
            popup.addEventListener('click', function(e) {
                if(e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
    });


    // Send email
    document.body.addEventListener('click', function(e) {
        if (e.target.closest('#sendEmailShare')) {
            const currentHrefInput = document.querySelector('#currentPropertyHref');
            const href = window.currentSharedHref || 
                        document.querySelector('#currentPropertyHref')?.value || '';
            
            const fromEmail = document.getElementById('senderEmail')?.value || '';
            const toEmail = document.getElementById('recipientEmail')?.value || '';

            if (!toEmail) {
                alert("Please enter a recipient email.");
                return;
            }

            const subject = encodeURIComponent("Flat Fee - Property Listing");
            const body = encodeURIComponent(
                `Check out this property:\n${href}\n\nSent by: ${fromEmail}`
            );

            const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${toEmail}&su=${subject}&body=${body}`;
            window.open(gmailUrl, "_blank");

            const popup = document.getElementById('shareEmailPopup');
            if (popup) popup.style.display = 'none';
        }
    });

    // CLOSE popup
    document.addEventListener('click', function(e) {
        const path = e.composedPath();
        const popup = document.getElementById('idxPopup');
        const content = popup.querySelector('.popup-content');

        // Close icon clicked
        if (path.some(el => el && el.classList && el.classList.contains('close-popup'))) {
            jQuery('#idxPopup').fadeOut(200);
            document.querySelector('#idxPopup iframe').src = "";
            return;
        }

        // Click outside popup-content
        if (
            popup.style.display !== "none" &&
            path.includes(popup) &&
            !path.includes(content)
        ) {
            jQuery('#idxPopup').fadeOut(200);
            document.querySelector('#idxPopup iframe').src = "";
        }
    });
    </script>
    <?php
    return ob_get_clean(); // Return buffered content
}
add_shortcode('idx_popup_script', 'idx_popup_script_shortcode');

add_action('wp_footer', 'gravity_form_popup_script');
function gravity_form_popup_script() {
    ?>
    <script>
    jQuery(document).ready(function($){
        // Close button (event delegation)
        $(document).on('click', '#close-popup', function(){
            $('#confirmation-popup').fadeOut();
        });

        // Show popup after any Gravity Form submission
        $(document).on('gform_confirmation_loaded', function(event, formId){
            $('#confirmation-popup').fadeIn();
            // Check if #bookPopupContact exists
            if ($('#bookPopupContact').length) {
                $('#bookPopupContact').removeClass('active');
            }
        });
    });
    </script>
    <?php
}

add_action('wp_footer', 'gravity_form_popup_html');
function gravity_form_popup_html() {
    ?>
    <!-- Modal Popup -->
    <div id="confirmation-popup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
        background: rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
      <div style="background:#fff; padding:30px; border-radius:8px; max-width:500px; width:90%; margin: 100px auto; position:relative; text-align:center;">
        <button id="close-popup" style="position:absolute; top:10px; right:10px; font-size:16px;color: #000; background: none; box-shadow: none;">✖</button>
        <h2 style="line-height:1;">Thank you! <br>We’ve received your inquiry.</h2>
        <p>One of our agents will be reaching out shortly to assist you.</p>
        <p style="margin-bottom:0;"><b>Want to skip the wait?</b></p>
        <p>You can book a free consultation right now with one of our licensed agents — choose a quick phone call, a Zoom appointment, or an in-person meeting at your convenience.</p>
        
        <a href="https://calendly.com/alessiteam/alessirealtygroup-15-minute-meeting?_pxl=djoxLGM6Yjg1NzVlMzIzNTM3NjIzOCxhOjE&back=1&month=2025-12"
           target="_blank"
           style="display:inline-block; padding:12px 25px; background:#0073e6; color:#fff; border-radius:5px; font-weight:bold; text-decoration:none; margin-top:15px;">
           📅 Book Your Appointment Now
        </a>
      </div>
    </div>
    <?php
}

add_action( 'wp_footer', function () {
    // Only on the Home Valuation page
    if ( is_page( 'home-valuation' ) && is_user_logged_in() ) {
        $user = wp_get_current_user();
        ?>
        <script>
            window.wpUser = {
                isLoggedIn: true,
                name: "<?php echo esc_js( $user->display_name ); ?>",
                email: "<?php echo esc_js( $user->user_email ); ?>"
            };
        </script>
        <?php
    }
});

/**
 * Plugin Name: IDX Password Reset Redirect
 * Description: Preserve listing URL through IDX Pro+ password reset flow
 */

// STEP 2 – Inject redirect into reset email
add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {

    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    // Get redirect URL from cookie, default to /property-search/
    $redirect = !empty($_COOKIE['idx_reset_redirect'])
        ? urldecode($_COOKIE['idx_reset_redirect'])
        : site_url('/property-search/');

    // Your custom reset password page
    $reset_page = site_url('/resetpass/');

    // Build the reset URL
    $reset_url = add_query_arg([
        'key'         => $key,
        'login'       => rawurlencode($user_login),
        'redirect_to' => rawurlencode($redirect),
    ], $reset_page);

    // Build the email
    $message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
    $message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
    $message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
    $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
    $message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
    $message .= $reset_url . "\r\n";

    return $message;

}, 10, 4);


// STEP 3 – Preserve redirect during reset form

add_action('template_redirect', function () {

    // Only run on reset password page
    if (!is_page('resetpass')) return; // Replace with your page slug

    // If cookie not set and redirect_to exists
    if (empty($_COOKIE['idx_reset_redirect']) && !empty($_GET['redirect_to'])) {
        $redirect = sanitize_text_field($_GET['redirect_to']);

        // Set cookie for 1 hour
        setcookie('idx_reset_redirect', $redirect, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        // Update $_COOKIE so it is available in this request
        $_COOKIE['idx_reset_redirect'] = $redirect;
    }

});

add_action('login_form_rp', 'idx_preserve_redirect');
add_action('login_form_resetpass', 'idx_preserve_redirect');

function idx_preserve_redirect() {
    if (!empty($_REQUEST['redirect_to'])) {
        echo '<input type="hidden" name="redirect_to" value="' .
             esc_attr($_REQUEST['redirect_to']) . '">';
    }
}



// STEP 4 – Redirect after successful login
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {

    if (!is_object($user)) {
        return $redirect_to;
    }

    /**
     * 1️⃣ Highest priority: IDX cookie
     */
    if (!empty($_COOKIE['idx_reset_redirect'])) {
        return esc_url_raw(urldecode($_COOKIE['idx_reset_redirect']));
    }

    /**
     * 2️⃣ Ignore WordPress default /wp-admin redirect
     */
    if ($requested_redirect_to === admin_url() || $requested_redirect_to === '/wp-admin/') {
        return site_url('/property-search/');
    }

    /**
     * 3️⃣ Respect valid custom redirect_to
     */
    if (!empty($requested_redirect_to)) {
        return esc_url_raw($requested_redirect_to);
    }

    /**
     * 4️⃣ Final fallback
     */
    return site_url('/property-search/');

}, 20, 3);

/* Home valuation page logout */
add_action('template_redirect', function() { 
    $logout_path = '/home-valuation/';

    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $query        = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

    if ($current_path === $logout_path && (empty($query) || strpos($query, 'v=') === false)) {

        if (is_user_logged_in()) {

            $user = wp_get_current_user();

            // ✅ Do NOT logout admins
            if (in_array('administrator', (array) $user->roles, true)) {
                return;
            }

            // Log out WordPress
            wp_logout();

            // Clear the wordpress_logged_in_* cookies
            foreach ($_COOKIE as $key => $value) {
                if (strpos($key, 'wordpress_logged_in_') === 0) {
                    setcookie($key, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
                    setcookie($key, '', time() - 3600, SITECOOKIEPATH, COOKIE_DOMAIN);
                }
            }

            // Redirect after logout
            wp_redirect(home_url('/home-valuation/?v=' . time()));
            exit;
        }
    }
});
