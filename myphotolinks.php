<?php
/*
Plugin Name:  My Photo Links
Plugin URI:   https://myphotolinks.com
Description:  Share private posts with groups of friends, they can only see the posts they are added to
Version:      0.7.5
Author:       Brian Hendrickson
Author URI:   http://hoverkitty.com
License:      MIT License
License URI:  https://opensource.org/licenses/MIT
Text Domain:  myphotolinks
Domain Path:  /languages
*/

/**
 * Plugin version
 * @example $plugin_data = get_plugin_data( __FILE__ ); $plugin_version = $plugin_data['Version'];
 * @link https://wordpress.stackexchange.com/questions/18268/i-want-to-get-a-plugin-version-number-dynamically
 */
if( ! defined( 'MYPHOTOLINKS_VERSION' ) ) {
  define( 'MYPHOTOLINKS_VERSION', '0.7.5' );
}

/**
 * plugin_dir_path
 * @param string $file
 * @return The filesystem directory path (with trailing slash)
 * @link https://developer.wordpress.org/reference/functions/plugin_dir_path/
 * @link https://developer.wordpress.org/plugins/the-basics/best-practices/#prefix-everything
 */
if( ! defined( 'MYPHOTOLINKS_PATH' ) ) {
  define( 'MYPHOTOLINKS_PATH', plugin_dir_path( __FILE__ ) );
}

/**
 * The version information is only available within WP Admin
 * @param string $file
 * @return The URL (with trailing slash)
 * @link https://codex.wordpress.org/Function_Reference/plugin_dir_url
 * @link https://developer.wordpress.org/plugins/the-basics/best-practices/#prefix-everything
 */
if( ! defined( 'MYPHOTOLINKS_URL' ) ) {
  define( 'MYPHOTOLINKS_URL', plugin_dir_url( __FILE__ ) );
}


/**
 * Store all of our plugin options in an array
 * So that we only use have to consume one row in the WP Options table
 * WordPress automatically serializes this (into a string)
 * because MySQL does not support arrays as a data type
 */
  $myphotolinks_options = array();

/**
 * Include plugin logic
 */

  // API data
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-api.php');

  // Views
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-options-page.php');
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-widget.php');

  // Theming
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-html.php');
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-css.php');
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-js.php');

  // Shortcode
  require_once(MYPHOTOLINKS_PATH . 'app/myphotolinks-shortcode.php');

  /**
   * Register meta box(es).
   */
  function myphotolinks_register_meta_boxes() {
      add_meta_box( 'meta-box-id', __( 'Send Personalized Auto-Login Links To...', 'myphotolinks' ), 'myphotolinks_my_display_callback', 'post' );
      add_meta_box( 'meta-box-id-sent', __( 'Received This Post', 'myphotolinks' ), 'myphotolinks_my_display_callback_sent', 'post' );
  }
  add_action( 'add_meta_boxes', 'myphotolinks_register_meta_boxes' );

  /**
   * Meta box display callback - sent
   *
   * @param WP_Post $post Current post object.
   */
  function myphotolinks_my_display_callback_sent( $post ) {
    $outline = '';
    $email_addresses = get_post_meta( $post->ID, 'myphotolinks_email_addresses', true );
    $phone_numbers = get_post_meta( $post->ID, 'myphotolinks_phone_numbers', true );
    $arr = explode(' ',$email_addresses);
    $arr_ph = explode(' ',$phone_numbers);
    $pageURL = get_permalink($post->ID);
    $url = remove_query_arg( $arr_params, $pageURL );
    foreach($arr as $e) {
      if ('' == trim($e)) continue;
      $url_params = array('myphotolinks' => 1, 'action' => 're_send', 'acct'=>$e, 'post_id'=>$post->ID);
      $resend_url = add_query_arg($url_params, $url);
      $outline .= '<p>'.$e.'&nbsp;<a href="'.$resend_url.'">Re-send Personalized Auto-Login Link</a></p>';
    }
    foreach($arr_ph as $p) {
      if ('' == trim($p)) continue;
      $url_params = array('myphotolinks' => 1, 'action' => 're_send', 'phone'=>$p, 'post_id'=>$post->ID);
      $resend_url = add_query_arg($url_params, $url);
      $outline .= '<p>'.$p.'&nbsp;<a href="'.$resend_url.'">Re-send Personalized Auto-Login Link</a></p>';
    }
    echo $outline;
  }
 
  function myphotolinks_resend_template_redirect() {
    if (strpos($_SERVER['REQUEST_URI'], 'myphotolinks=1') !== false) {
      if ($_GET['action'] == 're_send') {
        $edit_url = get_edit_post_link($_GET['post_id']);
        if (isset($_GET['acct'])) {
          $user = get_user_by( 'email', $_GET['acct'] );
        } else {
          $user = reset(
           get_users(
            array(
             'meta_key' => 'phone',
             'meta_value' => $_GET['phone'],
             'number' => 1,
             'count_total' => false
            )
           )
          );
        }
        $user_id = $user->ID;
        $nonce = wp_create_nonce( 'myphotolinks_email' );
        $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $arr_params = array('myphotolinks','acct','action','post_id','phone');
        $url = remove_query_arg( $arr_params, $url );
        $post_id = myphotolinks_url_to_postid( $url );
        $tok = get_post_meta( $post_id, 'myphotolinks_token'.$user_id );
        if (!empty($tok[0])) $token = $tok[0];
        $my_post = get_post( $post_id );
        $author = get_user_by('id', $my_post->post_author);
        $headers = array();
        $url_params = array('uid' => $user->ID, 'token' => $token, 'nonce' => $nonce);
        $url = add_query_arg($url_params, $url);
        $my_post = get_post( $post_id );
        $headers = array();
        $curr = wp_get_current_user();
        $fname = $curr->first_name;
        $lname = $curr->last_name;
        $full_name = '';
        if( empty($fname)){
            $full_name = $lname;
        } elseif( empty( $lname )){
            $full_name = $fname;
        } else {
            $full_name = "{$fname} {$lname}";
        }
        if (empty($full_name)) $full_name = $curr->display_name;
        if (empty($full_name)) $full_name = 'My Photo Links';
        if (isset($_GET['acct'])) {
          myphotolinks_send_email_notification($user,$user->user_email,$full_name,$my_post,$url,$curr);
        } else {
          myphotolinks_send_sms_notification($user,$_GET['phone'],$full_name,$my_post,$url,$curr);
        }
        wp_redirect( $edit_url );
        exit;
      }
    }
  }
  add_action('template_redirect','myphotolinks_resend_template_redirect');

  function myphotolinks_send_email_notification($user,$user_email,$full_name,$my_post,$url,$curr) {
    $headers = array();
    $to = $user->display_name . ' <' . $user_email . '>';
    $subject = sprintf( __( 'Photos for you from %s: %s', 'myphotolinks'), $full_name, $my_post->post_title );
    $message = sprintf( __( 'Hi %s!', 'myphotolinks' ), $user->display_name ) . "\r\n" ." \r\n" .
    sprintf( __( '%s has shared some new photos with you privately:', 'myphotolinks' ), $full_name ) . "\r\n" ." \r\n" .
    sprintf( __( '%s', 'myphotolinks' ), $url ) . "\r\n" . " \r\n" .
    sprintf( __( 'Sent with My Photo Links, a photo sharing tool for anyone who wants to keep their photos safe and private.', 'myphotolinks' )) . "\r\n"." \r\n" ."myphotolinks.com\r\n\r\n";   
    $headers[] = 'From: '.$full_name.' <'.$curr->user_email.'>';
    $headers[] = 'Reply-To: '.$curr->user_email;
    if( wp_mail( $to, $subject, $message, $headers ) ) {
      return true;
    } else {
      error_log('myphotolinks failed to email '.$user_email);
      return false;
    }
  }
  
  function myphotolinks_send_sms_notification($user,$phone_number,$full_name,$my_post,$url,$curr) {
    $message = sprintf( __( '%s has shared some photos with you: %s ', 'myphotolinks' ), $full_name, $my_post->post_title ) .
    sprintf( __( '%s', 'myphotolinks' ), $url );
    $args = array( 
    	'number_to' => $phone_number,
    	'message' => $message,
    ); 
    $res = twl_send_sms( $args );
    if ($res) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Meta box display callback.
   *
   * @param WP_Post $post Current post object.
   */
  function myphotolinks_my_display_callback( $post ) {
    $outline = '<label for="email_addresses" style="width:99%" display:inline-block;">'. esc_html__('Email Addresses or Phone Numbers', 'text-domain') .'</label>';
      $email_addresses = get_post_meta( $post->ID, 'myphotolinks_email_addresses', true );
      $outline .= '<textarea name="email_addresses" id="email_addresses" class="email_addresses" rows="5" cols="60" style="width:99%" placeholder="grandma@example.com &lt;friend@yahoo.net&gt; 501-211-4214 &quot;auntie@hotmail.com&quot; 5142232424"></textarea>';
 
      echo $outline;
  }
 
  /**
   * Save email addresses with post, and send private links
   *
   * @param int $post_id Post ID
   */
  function myphotolinks_save_meta_box( $post_id ) {
    if ( isset( $_POST['post_type'] ) && 'post' === $_POST['post_type'] ) {
      $pattern = '/[.a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
      preg_match_all($pattern, strtolower($_POST['email_addresses']), $matches);
      $pattern = '/\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{4})/';
      preg_match_all($pattern, strtolower($_POST['email_addresses']), $phone_matches);
      $result = add_role(
        'read_post_'.$post_id,
        __( 'Read Post '.$post_id ),
        array(
          'read_post_'.$post_id => true,
        )
      );
      $curr = wp_get_current_user();
      $fname = $curr->first_name;
      $lname = $curr->last_name;
      $full_name = '';
      $email_addresses = get_post_meta( $post_id, 'myphotolinks_email_addresses', true );
      $phone_numbers = get_post_meta( $post_id, 'myphotolinks_phone_numbers', true );
      if( empty($fname)){
          $full_name = $lname;
      } elseif( empty( $lname )){
          $full_name = $fname;
      } else {
          $full_name = "{$fname} {$lname}";
      }
      if (empty($full_name)) $full_name = $curr->display_name;
      if (empty($full_name)) $full_name = 'My Photo Links';
      foreach($phone_matches[0] as $user_phone) {
        $user_phone = str_replace('-', '', $user_phone);
        $user = reset(
         get_users(
          array(
           'meta_key' => 'phone',
           'meta_value' => $user_phone,
           'number' => 1,
           'count_total' => false
          )
         )
        );
        if (!$user) {
          $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
          $user_id = wp_create_user( $user_phone, $random_password, $user_email );
          update_usermeta( $user_id, 'phone', $user_phone );
        } else {
          $user_id = $user->ID;
        }
        $user = get_user_by('ID', $user_id );
        $time = time();
        $nonce = wp_create_nonce( 'myphotolinks_email' );
        $user->add_role('read_post_'.$post_id);
        $pass = wp_generate_password( 20, false );
        require_once( ABSPATH . 'wp-includes/class-phpass.php');
        $wp_hasher = new PasswordHash(8, TRUE);
        $str = $pass . 'myphotolinks_email' . $time;
        $token  = wp_hash( $str );
        $tok = get_post_meta( $post_id, 'myphotolinks_token'.$user_id );
        if (!empty($tok[0])) $token = $tok[0];
        update_post_meta( $post_id, 'myphotolinks_token'.$user_id, $token );
        update_post_meta( $post_id, 'myphotolinks_token_expiry'.$user_id, 0 );
        $arr_params = array( 'uid', 'token', 'nonce' );
        $pageURL = get_permalink($post_id);
        $url = remove_query_arg( $arr_params, $pageURL );
        $url_params = array('uid' => $user->ID, 'token' => $token, 'nonce' => $nonce);
        $url = add_query_arg($url_params, $url);
        $my_post = get_post( $post_id );
        myphotolinks_send_sms_notification($user,$user_phone,$full_name,$my_post,$url,$curr);
        $phone_numbers .= ' '.$user_phone;
      }
      foreach($matches[0] as $user_email) {
        $exists = email_exists($user_email);
        if ( $exists ) {
        } else {
          $user_name = preg_replace("/[^a-z]/", '', strtolower($user_email));
          $user_id = username_exists( $user_name );
          if ( !$user_id and email_exists($user_email) == false ) {
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $user_id = wp_create_user( $user_name, $random_password, $user_email );
          }
        }
        $user_id = email_exists($user_email);
        $time = time();
        $nonce = wp_create_nonce( 'myphotolinks_email' );
        $user = get_user_by( 'email', $user_email );
        $user->add_role('read_post_'.$post_id);
        $pass = wp_generate_password( 20, false );
        require_once( ABSPATH . 'wp-includes/class-phpass.php');
        $wp_hasher = new PasswordHash(8, TRUE);
        $str = $pass . 'myphotolinks_email' . $time;
        $token  = wp_hash( $str );
        $tok = get_post_meta( $post_id, 'myphotolinks_token'.$user_id );
        if (!empty($tok[0])) $token = $tok[0];
        update_post_meta( $post_id, 'myphotolinks_token'.$user_id, $token );
        update_post_meta( $post_id, 'myphotolinks_token_expiry'.$user_id, 0 );
        $arr_params = array( 'uid', 'token', 'nonce' );
        $pageURL = get_permalink($post_id);
        $url = remove_query_arg( $arr_params, $pageURL );
        $url_params = array('uid' => $user->ID, 'token' => $token, 'nonce' => $nonce);
        $url = add_query_arg($url_params, $url);
        $my_post = get_post( $post_id );
        myphotolinks_send_email_notification($user,$user_email,$full_name,$my_post,$url,$curr);
        $email_addresses .= ' '.$user_email;
      }
      update_post_meta( $post_id, 'myphotolinks_email_addresses', $email_addresses );
      update_post_meta( $post_id, 'myphotolinks_phone_numbers', $phone_numbers );
      $curr->add_role('read_post_'.$post_id);
    }
  }
  add_action( 'post_updated', 'myphotolinks_save_meta_box' );
  
  /**
   * Map meta capability
   *
   */
  function myphotolinks_map_meta_cap( $caps, $cap, $user_id, $args ) {
    if ( 'read_post' == $cap ){
      $post_id = $args[0];
      $caps = array();
      $caps[] = 'read_post_'.$post_id;
    }
    return $caps;
  }
  add_filter( 'map_meta_cap', 'myphotolinks_map_meta_cap', 10, 4 );
  
  /**
   * Log user in with a login-link
   *
   */
  function myphotolinks_link_login(){
    if( isset( $_GET['token'] ) && isset( $_GET['uid'] ) && isset( $_GET['nonce'] ) ){
      $user_id = sanitize_key( $_GET['uid'] );
      $token  =  sanitize_key( $_REQUEST['token'] );
      $nonce  = sanitize_key( $_REQUEST['nonce'] );
      $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
      $arr_params = array( 'uid', 'token', 'nonce' );
      $url = remove_query_arg( $arr_params, $url );
      $post_id = myphotolinks_url_to_postid( $url );
      $tok = get_post_meta( $post_id, 'myphotolinks_token'.$user_id );
      if ( $tok[0] !== $token){
        error_log('BAD LOGIN TOKEN');
      } else {
        $my_post = get_post( $post_id );
        $author = get_user_by('id', $my_post->post_author);
        $user = get_user_by('id', $user_id);
        $headers = array();
        $to = $author->display_name . ' <' . $author->user_email . '>';
        $subject = sprintf( __( '%s logged in - %s', 'myphotolinks'), $user->display_name, $my_post->post_title );
        $message = sprintf( __( 'Hi %s!', 'myphotolinks' ), $author->display_name ) . "\r\n" ." \r\n" .
        sprintf( __( '%s logged in - %s', 'myphotolinks' ), $user->display_name, $my_post->post_title ) . "\r\n" ." \r\n";
        $headers[] = 'From: My Photo Links <info@myphotolinks.com>';
        $headers[] = 'Reply-To: info@myphotolinks.com';
        if( wp_mail( $to, $subject, $message, $headers ) ) {
        } else {
          error_log('failed to notify '.$author->user_email);
        }
        wp_set_auth_cookie( $user->ID );
        wp_redirect( $url );
        exit;
      }
    }
  }
  add_action( 'init', 'myphotolinks_link_login' );

  /**
   * Hide private posts the user can't read
   *
   */
  function myphotolinks_hide_some_private_posts( $query ) {
    if ( $query->is_home() && $query->is_main_query() ) {
      $user = wp_get_current_user();
      $args = array( 'posts_per_page' => -1, 'post_status' => 'private'); 
      $posts = get_posts($args);
      $privates = array();
      foreach($posts as $post) {
        if ( !in_array( 'read_post_'.$post->ID, (array) $user->roles ) ) {
          $privates[] = $post->ID;
        }
      }
      $query->set('post__not_in',$privates);
    }
  }
  add_action( 'pre_get_posts', 'myphotolinks_hide_some_private_posts');
  /**
   * Get an attachment ID given a URL.
   * 
   * @param string $url
   *
   * @return int Attachment ID on success, 0 on failure
   */
  function get_attachment_id( $url ) {
  	$attachment_id = 0;
  	$dir = wp_upload_dir();
  	if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
  		$file = basename( $url );
  		$query_args = array(
  			'post_type'   => 'attachment',
  			'post_status' => 'inherit',
  			'fields'      => 'ids',
  			'meta_query'  => array(
  				array(
  					'value'   => $file,
  					'compare' => 'LIKE',
  					'key'     => '_wp_attachment_metadata',
  				),
  			)
  		);
  		$query = new WP_Query( $query_args );
  		if ( $query->have_posts() ) {
  			foreach ( $query->posts as $post_id ) {
  				$meta = wp_get_attachment_metadata( $post_id );
  				$original_file       = basename( $meta['file'] );
  				$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
  				if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
  					$attachment_id = $post_id;
  					break;
  				}
  			}
  		}
  	}
  	return $attachment_id;
  }
  function myphotolinks_template_redirect() {
    if (strpos($_SERVER['REQUEST_URI'], 'myphotolinks=1') !== false) {
      $user = wp_get_current_user();
      $post_id = get_the_id();
      $url = get_permalink($post_id);
      if ( user_can($user->ID, 'read_post_'.$post_id) ) {
        if ($_GET['action'] == 'download') {

          $filename = 'photos-'.$user->ID.'_'.$post_id."_file.zip";
          $upload_dir = wp_upload_dir();
          $filepath = $upload_dir['basedir'].'/';
          $files_to_zip = array();
          $post_id = get_the_id();
          $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $post_id
          );
          $zip = new ZipArchive;
          $zip->open($filepath.$filename, ZipArchive::CREATE);
          $content = get_post_field('post_content', $post_id);
          if ($content == null) {
              return null;
          }
          $dom = new DOMDocument();
          $dom->loadHTML($content);
          $links = $dom->getElementsByTagName("a");
          foreach($links as $l) {
            $id = get_attachment_id((string)$l->getAttribute('href'));
                $thisfile = get_attached_file( $id );
                $thisfilename = substr($thisfile, strrpos($thisfile, '/')+1);
               $zip->addFile($thisfile,$thisfilename);
               error_log($thisfile.' '.$thisfilename);
          }
          $zip->close();
          header("Pragma: public");
          header("Expires: 0");
          header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-type: application/octet-stream");
          header("Content-Disposition: attachment; filename=\"".$filename."\"");
          header("Content-Transfer-Encoding: binary");
          header("Content-Length: ".filesize($filepath.$filename));
          @readfile($filepath.$filename);
          //unlink($filepath.$filename);
          $my_post = get_post( $post_id );
          $author = get_user_by('id', $my_post->post_author);
          $headers = array();
          $to = $author->display_name . ' <' . $author->user_email . '>';
          $subject = sprintf( __( '%s downloaded photos - %s', 'myphotolinks'), $user->display_name, $my_post->post_title );
          $message = sprintf( __( 'Hi %s!', 'myphotolinks' ), $author->display_name ) . "\r\n" ." \r\n" .
          sprintf( __( '%s downloaded photos - %s', 'myphotolinks' ), $user->display_name, $my_post->post_title ) . "\r\n" ." \r\n";
          $headers[] = 'From: My Photo Links <info@myphotolinks.com>';
          $headers[] = 'Reply-To: info@myphotolinks.com';
          if( wp_mail( $to, $subject, $message, $headers ) ) {
          } else {
            error_log('failed to notify '.$author->user_email);
          }
          wp_set_auth_cookie( $user->ID );
          wp_redirect( $url );
          exit;
        }
      }
    }
  }
  add_action('template_redirect','myphotolinks_template_redirect');
  
  function myphotolinks_add_download_link($content) {
    if (!is_page( )){
      $user = wp_get_current_user();
      $post_id = get_the_id();
      if ( user_can($user->ID, 'read_post_'.$post_id) ) {
        $arr_params = array( 'uid', 'token', 'nonce' );
        $pageURL = get_permalink($post_id);
        $url = remove_query_arg( $arr_params, $pageURL );
        $url_params = array('myphotolinks' => 1, 'action' => 'download');
        $url = add_query_arg($url_params, $url);
        $content .= '<a href="'.$url.'" style="font-size:24px;color:white;background-color:#555;padding:10px;">Download Photos</a><br>';
      }
    }
    return $content;
  }      
  add_filter('the_content', 'myphotolinks_add_download_link');
	
  function myphotolinks_add_who_can_see_this($content) {
    if (!is_page( )){
      $user = wp_get_current_user();
      $post_id = get_the_id();
      if ( user_can($user->ID, 'read_post_'.$post_id) ) {
        $arr_params = array( 'uid', 'token', 'nonce' );
        $pageURL = get_permalink($post_id);
        $email_addresses = get_post_meta( $post_id, 'myphotolinks_email_addresses', true );
        $phone_numbers = get_post_meta( $post_id, 'myphotolinks_phone_numbers', true );
        $status = get_post_status($post_id);
        if ($status == 'private') {
          $content .= '<br clear="all"><div style="border:1px solid black;padding:13px;"><h3>Who can see this post and its comments?</h3><br /><p>'.str_replace(" ","<br>",$email_addresses).str_replace(" ","<br>",$phone_numbers).'</p></div>';
        }
      }
    }
    return $content;
  }      
  add_filter('the_content', 'myphotolinks_add_who_can_see_this');
  
  /**
   * URL to Post ID
   *
   * @param URL $url Post URL
   */
  function myphotolinks_url_to_postid( $url ) {
    global $wp_rewrite;
    $url = apply_filters( 'url_to_postid', $url );
    if ( preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values) )  {
      $id = absint($values[2]);
      if ( $id )
        return $id;
    }
    $url_split = explode('#', $url);
    $url = $url_split[0];
    $url_split = explode('?', $url);
    $url = $url_split[0];
    $scheme = parse_url( home_url(), PHP_URL_SCHEME );
    $url = set_url_scheme( $url, $scheme );
    if ( false !== strpos(home_url(), '://www.') && false === strpos($url, '://www.') )
      $url = str_replace('://', '://www.', $url);
    if ( false === strpos(home_url(), '://www.') )
      $url = str_replace('://www.', '://', $url);
    if ( trim( $url, '/' ) === home_url() && 'page' == get_option( 'show_on_front' ) ) {
      $page_on_front = get_option( 'page_on_front' );
      if ( $page_on_front && get_post( $page_on_front ) instanceof WP_Post ) {
        return (int) $page_on_front;
      }
    }
    $rewrite = $wp_rewrite->wp_rewrite_rules();
    if ( empty($rewrite) )
      return 0;
    if ( !$wp_rewrite->using_index_permalinks() )
      $url = str_replace( $wp_rewrite->index . '/', '', $url );
    if ( false !== strpos( trailingslashit( $url ), home_url( '/' ) ) ) {
      $url = str_replace(home_url(), '', $url);
    } else {
      $home_path = parse_url( home_url( '/' ) );
      $home_path = isset( $home_path['path'] ) ? $home_path['path'] : '' ;
      $url = preg_replace( sprintf( '#^%s#', preg_quote( $home_path ) ), '', trailingslashit( $url ) );
    }
    $url = trim($url, '/');
    $request = $url;
    $post_type_query_vars = array();
    foreach ( get_post_types( array() , 'objects' ) as $post_type => $t ) {
      if ( ! empty( $t->query_var ) )
        $post_type_query_vars[ $t->query_var ] = $post_type;
    }
    $request_match = $request;
    foreach ( (array)$rewrite as $match => $query) {
      if ( !empty($url) && ($url != $request) && (strpos($match, $url) === 0) )
        $request_match = $url . '/' . $request;
      if ( preg_match("#^$match#", $request_match, $matches) ) {
        if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
          $page = get_page_by_path( $matches[ $varmatch[1] ] );
          if ( ! $page ) {
            continue;
          }
          $post_status_obj = get_post_status_object( $page->post_status );
          if ( ! $post_status_obj->public && ! $post_status_obj->protected
             && $post_status_obj->exclude_from_search ) {
            continue;
          }
        }
        $query = preg_replace("!^.+\?!", '', $query);
        $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));
        global $wp;
        parse_str( $query, $query_vars );
        $query = array();
        foreach ( (array) $query_vars as $key => $value ) {
          if ( in_array( $key, $wp->public_query_vars ) ){
            $query[$key] = $value;
            if ( isset( $post_type_query_vars[$key] ) ) {
              $query['post_type'] = $post_type_query_vars[$key];
              $query['name'] = $value;
            }
          }
        }
        $query = wp_resolve_numeric_slug_conflicts( $query );
        $query['post_status'] = 'private';
        $query = new WP_Query( $query );
        if ( ! empty( $query->posts ) && $query->is_singular )
          return $query->post->ID;
        else
          return 0;
      }
    }
    return 0;
  }
  
  
?>