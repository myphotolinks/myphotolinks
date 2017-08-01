<?php
/*
Plugin Name:  My Photo Links
Plugin URI:   https://myphotolinks.com
Description:  Share private posts with groups of friends, they can only see the posts they are added to
Version:      0.6.3
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
  define( 'MYPHOTOLINKS_VERSION', '0.6.3' );
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
      add_meta_box( 'meta-box-id', __( 'Send Private Links To...', 'myphotolinks' ), 'myphotolinks_my_display_callback', 'post' );
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
    $outline .= '<p>'. esc_attr($email_addresses) .'</p>';
    echo $outline;
  }
 
  /**
   * Meta box display callback.
   *
   * @param WP_Post $post Current post object.
   */
  function myphotolinks_my_display_callback( $post ) {
    $outline = '<label for="email_addresses" style="width:150px; display:inline-block;">'. esc_html__('Email Addresses', 'text-domain') .'</label>';
      $email_addresses = get_post_meta( $post->ID, 'myphotolinks_email_addresses', true );
      $outline .= '<textarea name="email_addresses" id="email_addresses" class="email_addresses" rows="5" cols="60" placeholder="grandma@example.com &lt;friend@yahoo.net&gt; &quot;auntie@hotmail.com&quot;"></textarea>';
 
      echo $outline;
  }
 
  /**
   * Save email addresses with post, and send private links
   *
   * @param int $post_id Post ID
   */
  function myphotolinks_save_meta_box( $post_id ) {
    if ( isset( $_POST['post_type'] ) && 'post' === $_POST['post_type'] ) {
      $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
      preg_match_all($pattern, strtolower($_POST['email_addresses']), $matches);
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
      error_log($email_addresses);
      if( empty($fname)){
          $full_name = $lname;
      } elseif( empty( $lname )){
          $full_name = $fname;
      } else {
          $full_name = "{$fname} {$lname}";
      }
      if (empty($full_name)) $full_name = $curr->display_name;
      if (empty($full_name)) $full_name = 'My Photo Links';
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
        if (!empty($tok)) $token = $token;
        update_post_meta( $post_id, 'myphotolinks_token'.$user_id, $token );
        update_post_meta( $post_id, 'myphotolinks_token_expiry'.$user_id, 0 );
        $arr_params = array( 'uid', 'token', 'nonce' );
        $pageURL = get_permalink($post_id);
        $url = remove_query_arg( $arr_params, $pageURL );
        $url_params = array('uid' => $user->ID, 'token' => $token, 'nonce' => $nonce);
        $url = add_query_arg($url_params, $url);
        $my_post = get_post( $post_id );
        $headers = array();
        $to = $user->display_name . ' <' . $user_email . '>';
        $subject = sprintf( __( 'Photos for you from %s', 'myphotolinks'), $full_name );
        $message = sprintf( __( 'Hi %s!', 'myphotolinks' ), $user->display_name ) . "\r\n" ." \r\n" .
        sprintf( __( '%s has shared some new photos with you privately:', 'myphotolinks' ), $full_name ) . "\r\n" ." \r\n" .
        sprintf( __( '%s', 'myphotolinks' ), $url ) . "\r\n" . " \r\n" .
        sprintf( __( 'Sent with My Photo Links, an ad-free photo sharing tool for Parents, Teachers and Photographers who want to keep their photos safe and private.', 'myphotolinks' )) . "\r\n"." \r\n" ."www.myphotolinks.com\r\n\r\n";   
        $headers[] = 'From: '.$full_name.' <'.$curr->user_email.'>';
        $headers[] = 'Reply-To: '.$curr->user_email;
        if( wp_mail( $to, $subject, $message, $headers ) ) {
          $email_addresses .= ' '.$user_email;
          error_log('2'.$email_addresses);
        } else {
          error_log('failed to email '.$user_email);
        }
      }
      update_post_meta( $post_id, 'myphotolinks_email_addresses', $email_addresses );
      error_log('3'.$email_addresses);
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
        wp_set_auth_cookie( $user_id );
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
