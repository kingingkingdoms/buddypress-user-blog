<?php

$bookmark_post = buddyboss_bba()->option( 'bookmark_post' );

if ( $bookmark_post != 'on' ) {
	return;
}

// JS
if ( !function_exists( 'bba_bookmark_scripts' ) ) {

	function bba_bookmark_scripts() {
		if ( is_user_logged_in() && is_singular() ) {
			wp_enqueue_script( 'bookmark-it', buddyboss_bba()->assets_url . '/js/bookmark-it.js', array( 'jquery' ) );
			wp_localize_script( 'bookmark-it', 'bookmark_it_vars', array(
				'ajaxurl'		 => admin_url( 'admin-ajax.php' ),
				'nonce'			 => wp_create_nonce( 'bookmark-it-nonce' ),
				'error_message'	 => __( 'Sorry, there was a problem processing your request.', 'bp-user-article' )
			)
			);
		}
	}

	add_action( 'wp_enqueue_scripts', 'bba_bookmark_scripts' );
}

// processes the ajax request
if ( !function_exists( 'bba_process_bookmark' ) ) {

	function bba_process_bookmark() {

		$nonce	 = $_POST[ 'bookmark_it_nonce' ];
		$action	 = $_POST[ 'user_action' ];
		$post_id = $_POST[ 'item_id' ];
		$user_id = $_POST[ 'user_id' ];

		if ( !wp_verify_nonce( $nonce, 'bookmark-it-nonce' ) ) {
			die( 'Busted!' );
		}

		if ( isset( $post_id ) ) {

			if ( 'remove-bookmark' === $action ) {
				bba_remove_post_as_bookmarked( $post_id, $user_id );
			} elseif ( 'add-bookmark' === $action ) {
				bba_mark_post_as_bookmarked( $post_id, $user_id );
			}
		}

		die();
	}

	add_action( 'wp_ajax_bookmark_it', 'bba_process_bookmark' );
}

// check whether a user has bookmarked an item
if ( !function_exists( 'bba_user_has_bookmarked_post' ) ) {

	function bba_user_has_bookmarked_post( $user_id, $post_id ) {

		// get all item IDs the user has bookmarked
		$bookmarked = get_user_option( 'bba_user_bookmarks', $user_id );

		if ( is_array( $bookmarked ) && in_array( $post_id, $bookmarked ) ) {
			return true; // user has bookmarked post
		}

		return false; // user has not bookmarked post
	}

}

// Adds the bookmarked ID to the users meta.
if ( !function_exists( 'bba_store_bookmarked_id_for_user' ) ) {

	function bba_store_bookmarked_id_for_user( $user_id, $post_id ) {

		$bookmarked = get_user_option( 'bba_user_bookmarks', $user_id );

		if ( is_array( $bookmarked ) ) {
			$bookmarked[] = $post_id;
		} else {
			$bookmarked = array( $post_id );
		}

		update_user_option( $user_id, 'bba_user_bookmarks', $bookmarked );
	}

}

// Remove the bookmarked ID to the users meta.
if ( !function_exists( 'bba_remove_bookmarked_id_for_user' ) ) {

	function bba_remove_bookmarked_id_for_user( $user_id, $post_id ) {

		$bookmarked	 = get_user_option( 'bba_user_bookmarks', $user_id );
		$key		 = array_search( $post_id, $bookmarked );

		if ( false !== $key ) {
			unset( $bookmarked[ $key ] );
		}

		update_user_option( $user_id, 'bba_user_bookmarks', $bookmarked );
	}

}

// Remove the bookmarked ID when post is deleted.
if ( !function_exists( 'bba_remove_bookmark_on_post_delete' ) ) {
    
    function bba_remove_bookmark_on_post_delete($post_id){
        $all_users = get_users();
        foreach ( $all_users as $user ) {
            $user_id = $user->ID;
            $bookmarked	 = get_user_option( 'bba_user_bookmarks', $user_id );
            if(!empty($bookmarked)) {
                $key		 = array_search( $post_id, $bookmarked );

                if ( false !== $key ) {
                    unset( $bookmarked[ $key ] );
                }

                update_user_option( $user_id, 'bba_user_bookmarks', $bookmarked );
            }
        }
    }
}

add_action( 'wp_trash_post', 'bba_remove_bookmark_on_post_delete' );

// Store this post as bookmarked for $user_id
if ( !function_exists( 'bba_mark_post_as_bookmarked' ) ) {

	function bba_mark_post_as_bookmarked( $post_id, $user_id ) {
		bba_store_bookmarked_id_for_user( $user_id, $post_id );
	}

}

// Remove this post as bookmarked for $user_id
if ( !function_exists( 'bba_remove_post_as_bookmarked' ) ) {

	function bba_remove_post_as_bookmarked( $post_id, $user_id ) {
		bba_remove_bookmarked_id_for_user( $user_id, $post_id );
	}

}

// Get Bookmarks Button
if ( !function_exists( 'bba_get_bookmark_button' ) ) {

	function bba_get_bookmark_button() {
		// only show the link when user is logged in and on a singular page
		if ( is_user_logged_in() && is_single() ) {

			$user_ID			 = get_current_user_id();
			$has_bookmarked_post = bba_user_has_bookmarked_post( $user_ID, get_the_ID() );

			$class			 = ( $has_bookmarked_post ) ? ' bookmarked' : '';
			$icon			 = ( $has_bookmarked_post ) ? ' fa-bookmark' : ' fa-bookmark-o';
			$bookmark_action = ( $has_bookmarked_post ) ? 'remove-bookmark' : 'add-bookmark';

			$button = '<span class="bookmark-link-container">';
			$button .= '<a href="#" title="' . __( 'Bookmark this story to read later', 'bp-user-article' ) . '" class="bookmark-it' . $class . '" data-action="' . $bookmark_action . '" data-post-id="' . get_the_ID() . '" data-user-id="' . $user_ID . '">';
			$button .= '<span class="fa bb-helper-icon' . $icon . '"></span>';
			$button .= '<span> ' . __( 'Bookmark Article', 'bp-user-article' ) . '</span>';
			$button .= '</a>';
			$button .= '</span>';

			return $button;
		}
	}

}

// Bookmarks Button
if ( !function_exists( 'bba_bookmark_button' ) ) {

	function bba_bookmark_button() {
		echo bba_get_bookmark_button();
	}

}

//Add Bookmark Button Single Post
if ( !function_exists( 'bba_add_bookmark_button_after_post' ) ) {

	function bba_add_bookmark_button_after_post( $content ) {

		if ( is_singular( 'article' ) && get_post_status(get_the_ID()) == 'publish' ) {
			$content .= bba_get_bookmark_button();
		}

		return $content;
	}

	add_filter( 'the_content', 'bba_add_bookmark_button_after_post' );
}

// Bookmarks Shortcode
if ( !function_exists( 'bba_bookmark_posts' ) ) {

	function bba_bookmark_posts() {
		bba_load_template_multiple_times( 'bba-bookmarks-shortcode' );
	}

	add_shortcode( 'bookmarked_articles', 'bba_bookmark_posts' );
}
