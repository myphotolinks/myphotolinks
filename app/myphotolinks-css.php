<?php
/**
 * CSS imports
 *
 * This file contains PHP.
 *
 * @link        myphotolinks
 * @since       0.1.0
 *
 * @package     myphotolinks
 * @subpackage  myphotolinks/app
 */

if ( !function_exists( 'myphotolinks_css_backend' ) ) {

  /**
   * Attach CSS for Settings > myphotolinks
   *
   * @since       0.1.0
   */
  function myphotolinks_css_backend() {

    wp_enqueue_style( 'myphotolinks_css_backend',
      MYPHOTOLINKS_URL . 'views/admin/css/myphotolinks.css',
      array(),
      MYPHOTOLINKS_VERSION
      //'all'
    );
  }

  add_action( 'admin_head', 'myphotolinks_css_backend' );

}

if ( !function_exists( 'myphotolinks_css_frontend' ) ) {

  /**
   * Attach CSS for front-end widgets and shortcodes
   *
   * @since       0.1.0
   */
  function myphotolinks_css_frontend() {

    wp_enqueue_style( 'myphotolinks_css_frontend',
      MYPHOTOLINKS_URL . 'views/public/css/myphotolinks.css',
      array(),
      MYPHOTOLINKS_VERSION
      //'all'
    );

  }

  add_action( 'wp_enqueue_scripts', 'myphotolinks_css_frontend' );

}

?>
