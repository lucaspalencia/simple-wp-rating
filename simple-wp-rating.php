<?php  
/**
 * Plugin Name: Simple WP Rating
 * Description: WordPress Plugin for rating posts and order by average rating.
 * Version: 1.0.0
 * Author: Lucas Palencia
 * Author URI: https://github.com/lucaspalencia
 * License: GPLv2 or later
 * Text Domain: simple-wp-rating
 * Domain Path: languages/
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('SWR_VERSION', '1.0.0');
define('SWR_DIR', plugin_dir_path( __FILE__ ) );
define('SWR_URL', plugins_url('', __FILE__) );


if ( !class_exists('Simple_WP_Rating') ) {

	class Simple_WP_Rating { 

		public static $swr_posts_perpage = 10;
		public $swr_rating_approved;
		protected static $instance = null;

		public function __construct() {

			$swr_options = get_option('swr_plugin_options');

			if ($swr_options) {
				
				if ( $swr_options['posts_perpage'] ) 
					self::$swr_posts_perpage = $swr_options['posts_perpage'];
				
				if ( $swr_options['rating_approved'] )
					$this->swr_rating_approved = $swr_options['rating_approved'];
			
			}
			
			add_filter( 'comment_form_default_fields', array( $this, 'swr_form_comment' ) );
			
			add_action( 'comment_post', array( $this, 'swr_send_comment' ), 1 );

			if ($this->swr_rating_approved) {
				add_action('transition_comment_status', array( $this, 'swr_approve_comment' ), 10, 3 );
			}
			
			add_filter( 'comment_text', array( $this, 'swr_show_comment' ) );
			
			add_shortcode('swr_posts', array( $this, 'swr_posts_shortcode' ) );

			add_shortcode('swr_stars', array( $this, 'swr_stars_shortcode' ) );
			
			add_action('admin_menu', array( $this, 'swr_admin_menu' ) );
			
			add_action( 'wp_enqueue_scripts', array( $this, 'swr_scripts' ) );

			add_action( 'plugins_loaded', array( $this, 'swr_load_textdomain' ) );

		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Install plugin
		 */
		public function swr_install() {
			
			global $wp_version;

			if ( version_compare($wp_version, '3.8', '<') ) {
				wp_die('Este plugin requer no mínimo a versão 3.8 do WordPress');
			}

			add_option('swr_plugin_version', SWR_VERSION);

		}

		/**
		 * Displays rating stars in comment form
		 *
		 * @param array $fields The fields of the comment form
		*/
		public function swr_form_comment($fields) {
			
			$fields['star'] = '<p class="comment-form-star swr-stars swr-stars-form"><label>' . __( 'Rating', 'simple-wp-rating' ) . '</label>' .
        	'<input type="radio" name="stars" value="5"><span class="swr-star">☆</span> <input type="radio" name="stars" value="4"><span class="swr-star">☆</span> <input type="radio" name="stars" value="3"><span class="swr-star">☆</span> <input type="radio" name="stars" value="2"><span class="swr-star">☆</span> <input type="radio" name="stars" value="1" checked><span class="swr-star active">☆</span>
        	</p>';

    		return $fields;
		
		}

		/**
		 * Calculate post average rating
		 *
		 * @param int $post_id The post id
		*/
		private function swr_average_rating($post_id) {

			$post_rating = get_post_meta($post_id, '_post_rating');
			
			$count_rating = count($post_rating);
			$count_total = 0;
			foreach ($post_rating as $rating) {
				$count_total = $count_total + $rating;
			}
			
			$result = round($count_total / $count_rating);
			
			return $result;

		}

		/**
		 * Save rating stars to comment meta, post meta and calculate average post rating on comment submitted
		 * 
		 * @param int $comment_id The comment id
		*/
		public function swr_send_comment($comment_id) {

		    if( isset($_POST['stars']) ) {

		    	$rating_star = (int) wp_filter_nohtml_kses($_POST['stars']);
		        
		        if ($this->swr_rating_approved) {

		        	add_comment_meta($comment_id, 'post_rating', $rating_star, false);

		        } else {

			        $comment = get_comment( $comment_id );
	 				$post_id = $comment->comment_post_ID;
			        
			        add_post_meta($post_id, '_post_rating', $rating_star, false);
			        add_comment_meta($comment_id, 'post_rating', $rating_star, false);

			        $average_rating = $this->swr_average_rating($post_id);
			        update_post_meta( $post_id, '_post_average_rating', $average_rating);

		        }
		    
		    }

		}


		/**
		 * Save rating stars to post meta and calculate average post rating on comment approved
		 *
		 * @param string $new_status The new status of comment
		 * @param string $old_status The old status of comment
		 * @param object $comment The comment object
		 */
		public function swr_approve_comment($new_status, $old_status, $comment) {

			if ($new_status == 'approved') {
				
				$comment_rating_star = get_comment_meta( $comment->comment_ID, 'post_rating', true );
				$comment_post_id = $comment->comment_post_ID;

				add_post_meta($comment_post_id, '_post_rating', $comment_rating_star, false);
				$average_rating = $this->swr_average_rating($comment_post_id);
		        update_post_meta( $comment_post_id, '_post_average_rating', $average_rating);
			
			}

		} 

		/**
		 * Show comment with rating stars.
		 *
		 * @param string $text The comment content
		*/
		public function swr_show_comment($text) {

			$comment_rating = get_comment_meta( get_comment_ID(), 'post_rating', true );
			
			if ($comment_rating) {
				
				$rating_html = ' <div class="swr-stars rating">';
				for ($i=0; $i < $comment_rating ; $i++) { 
					$rating_html .= '<span class="swr-star active">☆</span>';
				}
				$rating_html .= '</div>';

				$text = $text . $rating_html;

			}

			return $text;
			
		}

		/**
		 * Query posts order by post meta _post_average_rating 
		 */
		private static function swr_get_posts() {

			$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;

			$args = array(
				'posts_per_page' => self::$swr_posts_perpage,
				'paged' => $paged,
				'meta_key' => '_post_average_rating',
				'orderby' => array('meta_value_num' => 'DESC', 'title' => 'ASC')
 			);

 			$query = new Wp_Query($args);

 			return $query;

		}

		/**
		 * Loop posts swr_get_posts
		 */
		public static function swr_show_posts() {

			$posts_swr = self::swr_get_posts();

			if( $posts_swr->have_posts() ) {

				while( $posts_swr->have_posts() ) {
					$posts_swr->the_post();

					echo '<header class="entry-header">';
					
					echo '<h2 class="entry-title"> <a href="'.get_permalink().'">'.get_the_title().'</a></h2>';

					self::swr_show_post_stars();

					echo '</header>';

					if ( has_post_thumbnail() ) {
						echo '<a class="post-thumbnail swr-post-thumbnail" href="'.get_permalink().'">';
						the_post_thumbnail('medium');
						echo '</a>';
					}

					echo '<div class="entry-content">';
						the_content();
					echo '</div>';

				}

				echo '<div class="entry-footer">';
					if ($posts_swr->max_num_pages > 1) { // check if the max number of pages is greater than 1  ?>
					  	<nav>
						    <ul class="swr-pagination">
						        <li class="swr-previous-link"><?php previous_posts_link( '&laquo; '.__( 'Previous page', 'simple-wp-rating').'', $posts_swr->max_num_pages) ?></li> 
						        <li class="swr-next-link"><?php next_posts_link( ' '.__( 'Next page', 'simple-wp-rating').' &raquo;', $posts_swr->max_num_pages) ?></li>
						    </ul>
						</nav>
					<?php }
				echo '</div>';

			} else {

				echo '<header class="entry-header">';
					echo '<h2 class="entry-title">'.__( 'No posts found', 'simple-wp-rating' ).'</h2>';
				echo '</header>';

			}

			wp_reset_postdata();

		}


		/**
		 * Show post stars average
		 */
		public static function swr_show_post_stars() {

			$post_rating = get_post_meta(get_the_ID(), '_post_average_rating');
					
			if ($post_rating) {
				
				$rating_html = ' <div class="swr-stars swr-stars-single rating">';
				for ($i=0; $i < $post_rating[0]; $i++) { 
					$rating_html .= '<span class="swr-star active">☆</span>';
				}
				$rating_html .= '</div>';
				
				echo $rating_html;

			}

		}

		/**
		 * Add shortcode show posts by rating
		 */
		public function swr_posts_shortcode() {

			ob_start();

			self::swr_show_posts();

			//get e clean buffe
			$content = ob_get_clean();

			return $content;

		}

		/**
		 * Add shortcode show post stars average
		 */
		public function swr_stars_shortcode() {

			ob_start();

			self::swr_show_post_stars();

			//get e clean buffe
			$content = ob_get_clean();

			return $content;

		}

		/**
		 * Admin options
		 */
		public function swr_admin_menu() {
			
			add_submenu_page(
		        'tools.php',
		        'Simple WP Rating Settings',
		        'Simple WP Rating',
		        'manage_options',
		        'swr-submenu-page',
		        array( $this, 'swr_admin_layout' ) 
		    );

		}

		/**
		 * Admin template
		 */
		public function swr_admin_layout() {

			if ( !empty($_POST) && isset($_POST['swr_options_submit']) ) {

				check_admin_referer('options_page_nonce', 'options_page_nonce_field');

				$swr_options = array();

				if ( isset($_POST['swr_postsperpage']) )
					$swr_options['posts_perpage'] = intval($_POST['swr_postsperpage']);

				if ( isset($_POST['swr_ratingapproved']) )
					$swr_options['rating_approved'] = $_POST['swr_ratingapproved'];

				update_option('swr_plugin_options', $swr_options);

			}

			$swr_default_options = array(
				'posts_perpage' => 10
			);

			$swr_options = get_option('swr_plugin_options');

			$swr_options = wp_parse_args($swr_options, $swr_default_options);

			require_once( SWR_DIR . '/admin/admin.php' );

		}

		/**
		 * Plugin enqueue script and style.
		*/
		public function swr_scripts() {
		    wp_enqueue_style('swr-style', SWR_URL.'/assets/css/main.css');
		    wp_enqueue_script('swr-script', SWR_URL.'/assets/js/main.js', array('jquery'), '', true);
		}

		/**
		 * Plugin translate option
		 */
		public function swr_load_textdomain() {
		    load_plugin_textdomain( 'simple-wp-rating', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

 
	}

	/**
	 * Plugin activation and deactivation methods.
	 */
	register_activation_hook( __FILE__, array( 'Simple_WP_Rating', 'swr_install' ) );

	/**
	 * Initialize the plugin.
	 */
	add_action( 'plugins_loaded', array( 'Simple_WP_Rating', 'get_instance' ) );

}