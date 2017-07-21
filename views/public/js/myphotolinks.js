/**
 * Scripts for the public front-end
 *
 * This file contains JavaScript.
 *    PHP variables are provided in wpdtrt_soundcloud_pages_config.
 *
 * @link        myphotolinks
 * @since       0.1.0
 *
 * @package     myphotolinks
 * @subpackage  myphotolinks/views
 */

jQuery(document).ready(function($){

	$('.myphotolinks-badge').hover(function() {
		$(this).find('.myphotolinks-badge-info').stop(true, true).fadeIn(200);
	}, function() {
		$(this).find('.myphotolinks-badge-info').stop(true, true).fadeOut(200);
	});

  $.post( myphotolinks_config.ajax_url, {
    action: 'myphotolinks_data_refresh'
  }, function( response ) {
    //console.log( 'Ajax complete' );
  });

});
