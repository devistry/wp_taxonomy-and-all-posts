<?php
/**
 * This function creates a new Tools Option page that lists all
 * taxonomies and all posts related to that taxonomy term
 */

add_action( 'admin_menu', 'taap_add_taxonomy_browse_all_posts_page' );
add_filter( 'set-screen-option', 'taap_taxonomy_set_screen_option', 10, 3);
 
function taap_add_taxonomy_browse_all_posts_page() {

  global $taxonomy_page;

	$taxonomy_page = add_management_page(
		'Taxonomy and all Related Posts',
		'Taxonomy and all Related Posts',
		'manage_options',
		'taxonomy-browse-all-posts',
		'taap_taxonomy_browse_all_posts'
  );

  //add the screen option
  add_action( 'load-' . $taxonomy_page, 'taap_taxonomy_screen_option' );
  
}


/**
* Create a screen option
*/
function taap_taxonomy_screen_option() {

  //set globals and add screen option if taxonomy present
  global $taxnow;
  if ( !is_empty($taxnow) ) {
    $tax = get_taxonomy( $taxnow );
    $option = 'per_page';
    $args = [
        'default' => 25,
        'option' => 'edit_' . $tax->name . '_per_page',
        'label' => 'Number of ' . $tax->labels->name . ' per page',
    ];
    add_screen_option( $option, $args);
  }

} //end function taap_taxonomy_screen_option() {


/**
 * Save the screen options
 */  
function taap_taxonomy_set_screen_option($status, $option, $value) {
  return $value;
}


/**
 * Retrieve all Taxonomy Terms and show in table
 */
function taap_taxonomy_browse_all_posts() {

  //set global variables
  global $wpdb;

  //see if taxonomy exists, if not show list of available taxonomies
  $taxonomy = ( isset($_GET['taxonomy']) ) ? $_GET['taxonomy'] : '';
  $term_id = ( isset($_GET['term_id']) ) ? $_GET['term_id'] : '';

  //show all taxonomy terms
  if ( is_empty($taxonomy) ) {

    //get taxonomies
    $querystr = "SELECT DISTINCT $wpdb->term_taxonomy.taxonomy FROM $wpdb->term_taxonomy WHERE $wpdb->term_taxonomy.taxonomy <> 'nav_menu'";
    $taxonomies = $wpdb->get_results($querystr, OBJECT);

    ?>
    <h1>Taxonomy List</h1>
    <h3>Step 1 - Select a taxonomy from the list below to view all terms for that taxonomy.</h3>

    <table class="wp-list-table widefat fixed striped tags" style="max-width: 850px;">
    <thead><tr><th>Taxonomy Name</th></tr></thead>
    <tbody>
      <?php
      //go through each taxonomy and create link to taxonomy terms
      foreach ($taxonomies as $tax) {
        $term = get_taxonomy( $tax->taxonomy );

        //as long as the taxonomy exists show it in the list
        if ( !is_empty($term) ) {
          echo '<tr><td><a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts&taxonomy=' . $tax->taxonomy . '">' . $term->labels->name . '</a></td></tr>';
        }
      } //end foreach ($taxonomies as $tax) {
      ?>
    </tbody>
    </table>
    <?php

  //if a taxonomy has been selected and a term has not been selected
  } elseif ( is_empty($term_id) ) {

    //get taxonomy
    $tax = get_taxonomy( $taxonomy );

    //set screen parameters so the WP_Terms_List_Table will display
    get_current_screen()->id = "edit-tags";
    get_current_screen()->parent_base = "edit";
    get_current_screen()->parent_file = "edit.php";
    get_current_screen()->post_type = "post";

    //setup the table - establish the table, get current page number, set title
    //$wp_list_table = _get_list_table('WP_Terms_List_Table');  --use below so can override built in functions
    $wp_list_table = new TAAP_Terms_List_Table;
    $pagenum = $wp_list_table->get_pagenum();
    $title = $tax->labels->name;

    //set taxonomy edit links
    $parent_file = 'edit.php';
	  $submenu_file = "edit-tags.php?taxonomy=$taxonomy";

    //DM - removed referer information and location information
    //prepare items and get total number of pages
    $wp_list_table->prepare_items();
    $total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
    
    ?>
    <br />
    <a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts">< Back to Taxonomy List</a><br />
    <h1>Term List for <?=$title?></h1>
    <h3>Step 2 - Select a term from the table below to view post types and count for each term.</h3>
    <style>.row-actions .inline, .row-actions .delete { display: none; } th#name, th#slug, th#posts { width: 33%; } td.column-posts { text-align: left; }</style>
    <div id="taap-step2-container" style="max-width: 850px;">
    <?php

    if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
      /* translators: %s: search keywords */
      printf( '<h3>' . __( 'Search results for &#8220;%s&#8221;' ) . '</h3>', esc_html( wp_unslash( $_REQUEST['s'] ) ) );
      echo '<a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts&taxonomy=' . $taxonomy . '">Clear Search Results</a>';
    }

    ?>
    <form class="search-form wp-clearfix" method="get">
    <input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
    <input type="hidden" name="page" value="taxonomy-browse-all-posts" />

    <?php $wp_list_table->search_box( $tax->labels->search_items, 'tag' ); ?>

    </form>
    <?php

    $wp_list_table->views();
    $wp_list_table->display();

    //end the container div
    echo '</div>';

  //if a taxonomy and term has been selected
  } else {

    //get the term details
    $term = get_term( $term_id );

    //SQL query for post types and count
    $querystr = "SELECT $wpdb->posts.post_type, COUNT($wpdb->posts.post_type) as count FROM $wpdb->posts WHERE $wpdb->posts.ID IN (SELECT $wpdb->term_relationships.object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = " . $term_id . ") AND $wpdb->posts.post_status = 'publish' GROUP BY $wpdb->posts.post_type";
    $pageposts = $wpdb->get_results($querystr, OBJECT);

    ?>
    <br />
    <a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts">< Back to Taxonomy List</a> / <a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts&taxonomy=<?=$taxonomy?>">Back to Term List</a><br />
    <h1>Posts for term <?=$term->name?></h1>
    <h3>Step 3 - Select a content type from the list below.</h3>
    <table class="wp-list-table widefat fixed striped tags" style="max-width: 850px;">
    <thead><tr><th>Post Type</th><th>Count / View Posts</th></tr></thead>
    <tbody>
      <?php
      //go through each post type and show post type and count
      foreach ( $pageposts as $postcount ) {

        //run query to determine number of posts
        $args = array(
          'post_type' => $postcount->post_type,
          'tax_query' => array(
            array(
              'taxonomy' => $taxonomy,
              'field'    => 'slug',
              'terms'    => $term->slug,
              'include_children' => false
            ),
          ),
        );
        $query = new WP_Query( $args );

        echo '<tr><td>' . $postcount->post_type . '</td><td><a href="/wp-admin/edit.php?' . $taxonomy . '=' . $term->slug . '&post_type=' . $postcount->post_type . '">' . $query->found_posts . '</a></td></tr>';

      }
      ?>
    </tbody>
    </table>
    <?php

  } //end if ( is_empty($taxonomy) ) {

} //end function taap_taxonomy_browse_all_posts() {


if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'WP_Terms_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-terms-list-table.php' );
}

class TAAP_Terms_List_Table extends WP_Terms_List_Table {

  private $level;

  /**
   * @return array
   */
  public function get_columns() {
    $columns = array(
        //'cb'          => '<input type="checkbox" />',
        'name'        => __( 'Name / Edit Term' ),
        'slug'        => __( 'Slug' ),
        'posts'       => __( 'Count / View Post Types' )
    );
    return $columns;
  }

  /**
   * Remove Bulk action delete
   * 
   * @return array
   */
  protected function get_bulk_actions() {
    $actions = array();
    /*if ( current_user_can( get_taxonomy( $this->screen->taxonomy )->cap->delete_terms ) ) {
      $actions['delete'] = __( 'Delete' );
    }*/
    return $actions;
  }

  /**
   * Setup View Posts column and link
   * 
   * @param WP_Term $tag Term object.
   * @return string
   */
  public function column_posts( $tag ) {

    //set global
    global $wpdb;

    //set counter to 0
    $count = 0;

    //get the term details
    $term = get_term( $tag->term_id );

    //show clickable tag count if more than one exists
    if ( $tag->count > 0 ) {

      //SQL query for post types and count
      $querystr = "SELECT $wpdb->posts.post_type FROM $wpdb->posts WHERE $wpdb->posts.ID IN (SELECT $wpdb->term_relationships.object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = " . $tag->term_id . ") AND $wpdb->posts.post_status = 'publish' GROUP BY $wpdb->posts.post_type";
      $pageposts = $wpdb->get_results($querystr, OBJECT);

      //go through each post type and get count
      foreach ( $pageposts as $posttype ) {
        $args = array(
          'post_type' => $posttype->post_type,
          'posts_per_page' => 1,
          'tax_query' => array(
            array(
              'taxonomy' => $term->taxonomy,
              'field'    => 'slug',
              'terms'    => $term->slug,
              'include_children' => false
            ),
          ),
        );
        $query = new WP_Query( $args );
        $count = $count + $query->found_posts;
      }
      return '<a href="/wp-admin/tools.php?page=taxonomy-browse-all-posts&taxonomy=' . $tag->taxonomy . '&term_id=' . $tag->term_id . '">' . $count . '</a>';

    //otherwise just show 0
    } else {
      return '0';

    } //end if ( $tag->count > 0 ) {
  }
}