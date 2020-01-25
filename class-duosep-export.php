<?php
/**
 * Export page into xml.
 * This class is modified version of export class in the WordPress Core file.
 *
 * @package SimpleExportPage
 * @see wp-admin/includes/export.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Duosep Export.
 */
class Duosep_Export {

	/**
	 * Export page by post ids.
	 *
	 * @param  array $post_ids Array of post ids.
	 */
	public function export_page( $post_ids ) {
		global $wpdb, $post;

		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '.';
		}
		$date        = gmdate( 'Y-m-d' );
		$wp_filename = $sitename . 'WordPress.' . $date . '.xml';

		$filename = apply_filters( 'export_wp_filename', $wp_filename, $sitename, $date );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

		// Start generate export code.

		echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . "\" ?>\n";

		the_generator( 'export' );
		?>
		<rss version="2.0"
			xmlns:excerpt="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/excerpt/"
			xmlns:content="http://purl.org/rss/1.0/modules/content/"
			xmlns:wfw="http://wellformedweb.org/CommentAPI/"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:wp="http://wordpress.org/export/<?php echo WXR_VERSION; ?>/"
			>
			<channel>
				<title><?php bloginfo_rss( 'name' ); ?></title>
				<link><?php bloginfo_rss( 'url' ); ?></link>
				<description><?php bloginfo_rss( 'description' ); ?></description>
				<pubDate><?php echo gmdate( 'D, d M Y H:i:s +0000' ); ?></pubDate>
				<language><?php bloginfo_rss( 'language' ); ?></language>
				<wp:wxr_version><?php echo WXR_VERSION; ?></wp:wxr_version>
				<wp:base_site_url><?php echo $this->wxr_site_url(); ?></wp:base_site_url>
				<wp:base_blog_url><?php bloginfo_rss( 'url' ); ?></wp:base_blog_url>

				<?php
				/** This action is documented in wp-includes/feed-rss2.php */
				do_action( 'rss2_head' );
				?>

				<?php
				if ( $post_ids ) {
					global $wp_query;

					// Fake being in the loop.
					$wp_query->in_the_loop = true;

					// Fetch 20 posts at a time rather than loading the entire table into memory.
					while ( $next_posts = array_splice( $post_ids, 0, 20 ) ) {
						$where = 'WHERE ID IN (' . join( ',', $next_posts ) . ')';
						$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->posts} $where" );

						// Begin Loop.
						foreach ( $posts as $post ) {
							setup_postdata( $post );

							/** This filter is documented in wp-includes/feed.php */
							$title     = apply_filters( 'the_title_rss', $post->post_title );
							$content   = $this->wxr_cdata( apply_filters( 'the_content_export', $post->post_content ) );
							$excerpt   = $this->wxr_cdata( apply_filters( 'the_excerpt_export', $post->post_excerpt ) );
							$is_sticky = is_sticky( $post->ID ) ? 1 : 0;
							?>
							<item>
								<title><?php echo $title; ?></title>
								<link><?php the_permalink_rss(); ?></link>
								<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
								<dc:creator><?php echo $this->wxr_cdata( get_the_author_meta( 'login' ) ); ?></dc:creator>
								<guid isPermaLink="false"><?php the_guid(); ?></guid>
								<description></description>
								<content:encoded><?php echo $content; ?></content:encoded>
								<excerpt:encoded><?php echo $excerpt; ?></excerpt:encoded>
								<wp:post_id><?php echo intval( $post->ID ); ?></wp:post_id>
								<wp:post_date><?php echo $this->wxr_cdata( $post->post_date ); ?></wp:post_date>
								<wp:post_date_gmt><?php echo $this->wxr_cdata( $post->post_date_gmt ); ?></wp:post_date_gmt>
								<wp:comment_status><?php echo $this->wxr_cdata( $post->comment_status ); ?></wp:comment_status>
								<wp:ping_status><?php echo $this->wxr_cdata( $post->ping_status ); ?></wp:ping_status>
								<wp:post_name><?php echo $this->wxr_cdata( $post->post_name ); ?></wp:post_name>
								<wp:status><?php echo $this->wxr_cdata( $post->post_status ); ?></wp:status>
								<wp:post_parent><?php echo intval( $post->post_parent ); ?></wp:post_parent>
								<wp:menu_order><?php echo intval( $post->menu_order ); ?></wp:menu_order>
								<wp:post_type><?php echo $this->wxr_cdata( $post->post_type ); ?></wp:post_type>
								<wp:post_password><?php echo $this->wxr_cdata( $post->post_password ); ?></wp:post_password>
								<wp:is_sticky><?php echo intval( $is_sticky ); ?></wp:is_sticky>
								<?php if ( 'attachment' === $post->post_type ) : ?>
									<wp:attachment_url><?php echo $this->wxr_cdata( wp_get_attachment_url( $post->ID ) ); ?></wp:attachment_url>
								<?php endif; ?>
								<?php $this->wxr_post_taxonomy(); ?>
								<?php
								$postmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );
								foreach ( $postmeta as $meta ) :
									?>
									<wp:postmeta>
									<wp:meta_key><?php echo $this->wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
									<wp:meta_value><?php echo $this->wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
									</wp:postmeta>
									<?php
								endforeach;

								$_comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved <> 'spam'", $post->ID ) );
								$comments  = array_map( 'get_comment', $_comments );
								foreach ( $comments as $c ) :
									?>
									<wp:comment>
										<wp:comment_id><?php echo intval( $c->comment_ID ); ?></wp:comment_id>
										<wp:comment_author><?php echo $this->wxr_cdata( $c->comment_author ); ?></wp:comment_author>
										<wp:comment_author_email><?php echo $this->wxr_cdata( $c->comment_author_email ); ?></wp:comment_author_email>
										<wp:comment_author_url><?php echo esc_url_raw( $c->comment_author_url ); ?></wp:comment_author_url>
										<wp:comment_author_IP><?php echo $this->wxr_cdata( $c->comment_author_IP ); ?></wp:comment_author_IP>
										<wp:comment_date><?php echo $this->wxr_cdata( $c->comment_date ); ?></wp:comment_date>
										<wp:comment_date_gmt><?php echo $this->wxr_cdata( $c->comment_date_gmt ); ?></wp:comment_date_gmt>
										<wp:comment_content><?php echo $this->wxr_cdata( $c->comment_content ); ?></wp:comment_content>
										<wp:comment_approved><?php echo $this->wxr_cdata( $c->comment_approved ); ?></wp:comment_approved>
										<wp:comment_type><?php echo $this->wxr_cdata( $c->comment_type ); ?></wp:comment_type>
										<wp:comment_parent><?php echo intval( $c->comment_parent ); ?></wp:comment_parent>
										<wp:comment_user_id><?php echo intval( $c->user_id ); ?></wp:comment_user_id>
										<?php
										$c_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->commentmeta WHERE comment_id = %d", $c->comment_ID ) );
										foreach ( $c_meta as $meta ) :
											?>
											<wp:commentmeta>
											<wp:meta_key><?php echo $this->wxr_cdata( $meta->meta_key ); ?></wp:meta_key>
											<wp:meta_value><?php echo $this->wxr_cdata( $meta->meta_value ); ?></wp:meta_value>
											</wp:commentmeta>
										<?php endforeach; ?>
									</wp:comment>
								<?php endforeach; ?>
							</item>
							<?php
						}
					}
				}
				?>
			</channel>
		</rss>
		<?php
	}

	/**
	 * Wrap data with CDATA.
	 *
	 * @param  string $str String of data.
	 * @return string      String of data.
	 */
	private function wxr_cdata( $str ) {
		if ( ! seems_utf8( $str ) ) {
			$str = utf8_encode( $str );
		}

		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}

	/**
	 * Get site url.
	 *
	 * @return string Site URL.
	 */
	private function wxr_site_url() {
		if ( is_multisite() ) {
			// Multisite: the base URL.
			return network_home_url();
		} else {
			// WordPress (single site): the blog URL.
			return get_bloginfo_rss( 'url' );
		}
	}

	/**
	 * Get category name.
	 *
	 * @param  WP_Term $category Category term object.
	 * @return void
	 */
	private function wxr_cat_name( $category ) {
		if ( empty( $category->name ) ) {
			return;
		}

		echo '<wp:cat_name>' . $this->wxr_cdata( $category->name ) . "</wp:cat_name>\n";
	}

	/**
	 * Get category description.
	 *
	 * @param  WP_Term $category Category term object.
	 * @return void
	 */
	private function wxr_category_description( $category ) {
		if ( empty( $category->description ) ) {
			return;
		}

		echo '<wp:category_description>' . $this->wxr_cdata( $category->description ) . "</wp:category_description>\n";
	}

	/**
	 * Get tag name.
	 *
	 * @param  WP_Term $tag Tag term object.
	 * @return void
	 */
	private function wxr_tag_name( $tag ) {
		if ( empty( $tag->name ) ) {
			return;
		}

		echo '<wp:tag_name>' . $this->wxr_cdata( $tag->name ) . "</wp:tag_name>\n";
	}

	/**
	 * Get tag description.
	 *
	 * @param  WP_Term $tag Tag term object.
	 * @return void
	 */
	private function wxr_tag_description( $tag ) {
		if ( empty( $tag->description ) ) {
			return;
		}

		echo '<wp:tag_description>' . $this->wxr_cdata( $tag->description ) . "</wp:tag_description>\n";
	}

	/**
	 * Get term name.
	 *
	 * @param  WP_Term $term Term object.
	 * @return void
	 */
	private function wxr_term_name( $term ) {
		if ( empty( $term->name ) ) {
			return;
		}

		echo '<wp:term_name>' . $this->wxr_cdata( $term->name ) . "</wp:term_name>\n";
	}

	/**
	 * Get term description.
	 *
	 * @param  WP_Term $term Term object.
	 * @return void
	 */
	private function wxr_term_description( $term ) {
		if ( empty( $term->description ) ) {
			return;
		}

		echo "\t\t<wp:term_description>" . $this->wxr_cdata( $term->description ) . "</wp:term_description>\n";
	}

	/**
	 * Get term meta.
	 *
	 * @param  WP_Term $term Term object.
	 * @return void
	 */
	private function wxr_term_meta( $term ) {
		global $wpdb;

		$termmeta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->termmeta WHERE term_id = %d", $term->term_id ) );

		foreach ( $termmeta as $meta ) {

			printf( "\t\t<wp:termmeta>\n\t\t\t<wp:meta_key>%s</wp:meta_key>\n\t\t\t<wp:meta_value>%s</wp:meta_value>\n\t\t</wp:termmeta>\n", $this->wxr_cdata( $meta->meta_key ), $this->wxr_cdata( $meta->meta_value ) );
		}
	}

	/**
	 * Get post taxonomy.
	 *
	 * @return void
	 */
	private function wxr_post_taxonomy() {
		$post = get_post();

		$taxonomies = get_object_taxonomies( $post->post_type );
		if ( empty( $taxonomies ) ) {
			return;
		}
		$terms = wp_get_object_terms( $post->ID, $taxonomies );

		foreach ( (array) $terms as $term ) {
			echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" . $this->wxr_cdata( $term->name ) . "</category>\n";
		}
	}
}
