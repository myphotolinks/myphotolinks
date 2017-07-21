<?php
/**
 * Generate a shortcode, to embed the widget inside a content area.
 *
 * This file contains PHP.
 *
 * @link        myphotolinks
 * @link        https://generatewp.com/shortcodes/
 * @since       0.1.0
 *
 * @example     [myphotolinks_blocks number="4" enlargement="yes"]
 * @example     do_shortcode( '[myphotolinks_blocks number="4" enlargement="yes"]' );
 *
 * @package     myphotolinks
 * @subpackage  myphotolinks/app
 */

if ( !function_exists( 'myphotolinks_blocks_shortcode' ) ) {

  /**
   * add_shortcode
   * @param       string $tag
   *    Shortcode tag to be searched in post content.
   * @param       callable $func
   *    Hook to run when shortcode is found.
   *
   * @since       0.1.0
   * @uses        ../../../../wp-includes/shortcodes.php
   * @see         https://codex.wordpress.org/Function_Reference/add_shortcode
   * @see         http://php.net/manual/en/function.ob-start.php
   * @see         http://php.net/manual/en/function.ob-get-clean.php
   */
  function myphotolinks_blocks_shortcode( $atts, $content = null ) {

    // post object to get info about the post in which the shortcode appears
    global $post;

    // prevent error when the front-end.php is used
    // by a shortcode which doesn't pass these variables
    $before_widget = $before_title = $title = $after_title = $after_widget = null;

    extract( shortcode_atts(
      array(
        'number' => '4',
        'enlargement' => 'yes'
      ),
      $atts,
      ''
    ) );

    if ( $enlargement === 'yes') {
      $enlargement = '1';
    }

    if ( $enlargement === 'no') {
      $enlargement = '0';
    }

    $myphotolinks_options = get_option('myphotolinks');
    $myphotolinks_data = $myphotolinks_options['myphotolinks_data'];

    /**
     * ob_start — Turn on output buffering
     * This stores the HTML template in the buffer
     * so that it can be output into the content
     * rather than at the top of the page.
     */
    ob_start();

    require(MYPHOTOLINKS_PATH . 'views/public/partials/myphotolinks-front-end.php');

    /**
     * ob_get_clean — Get current buffer contents and delete current output buffer
     */
    $content = ob_get_clean();

    return $content;
  }

  add_shortcode( 'myphotolinks_blocks', 'myphotolinks_blocks_shortcode' );

}

?>
