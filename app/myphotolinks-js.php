<?php
/**
 * JS imports
 *
 * This file contains PHP.
 *
 * @link        myphotolinks
 * @see         https://codex.wordpress.org/AJAX_in_Plugins
 * @since       0.1.0
 *
 * @package     myphotolinks
 * @subpackage  myphotolinks/app
 */

if ( !function_exists( 'myphotolinks_frontend_js' ) ) {

  /**
   * Attach JS for front-end widgets and shortcodes
   *    Generate a configuration object which the JavaScript can access.
   *    When an Ajax command is submitted, pass it to our function via the Admin Ajax page.
   *
   * @since       0.1.0
   * @see         https://codex.wordpress.org/AJAX_in_Plugins
   * @see         https://codex.wordpress.org/Function_Reference/wp_localize_script
   */
  function myphotolinks_frontend_js() {

    wp_enqueue_script( 'myphotolinks_frontend_js',
      MYPHOTOLINKS_URL . 'views/public/js/myphotolinks.js',
      array('jquery'),
      MYPHOTOLINKS_VERSION,
      true
    );

    wp_localize_script( 'myphotolinks_frontend_js',
      'myphotolinks_config',
      array(
        'ajax_url' => admin_url( 'admin-ajax.php' ) // myphotolinks_config.ajax_url
      )
    );

  }

  add_action( 'wp_enqueue_scripts', 'myphotolinks_frontend_js' );

}

?>
