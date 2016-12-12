<?php
/* 
* ConvertPlug Popup Table list
* @Version: 0.0.1
*/

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


if(!class_exists("Bsfm_Rules_Table")){
	class Bsfm_Rules_Table extends WP_List_Table {
		
		/**
		 * Number of items of the initial data set (before sort, search, and pagination).
		 *
		 * @since 1.0.0
		 * @var int
		 */
		protected $items_count = 0;

		/**
		 * Initialize the List Table.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			
			parent::__construct( array(
				'singular'	=> 'rule',		// Singular name of the listed records.
				'plural'	=> 'rules', // Plural name of the listed records.
				'ajax'		=> false,					// Does this list table support AJAX?
			) );
		}
	

		function column_default( $item, $column_name ) {
		  switch( $column_name ) { 
		    case 'post_title':
		    case 'post_author':
		      return $item[ $column_name ];
		    default:
		      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		  }
		}

		/** Text displayed when no rule data is available */
		public function no_items() {
		  _e( 'No rules avaliable.', 'bsfmautic' );
		}

		/**
		 * Render the bulk edit checkbox
		 *
		 * @param array $item
		 *
		 * @return string
		 */
		function column_cb( $item ) {
		  return sprintf(
		    '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		  );
		}

		function column_post_author( array $item ) {
			if ( '' === trim( $item['post_author'] ) ) {
				$item['post_author'] = __( '(no post_author)', 'bsfmautic' );
			}

			$author = get_the_author_meta( 'display_name', $item['post_author'] );

			return esc_html( $author );
		}

		function column_post_title( array $item ) {
			if ( '' === trim( $item['post_title'] ) ) {
				$item['post_title'] = __( '(no post_title)', 'bsfmautic' );
			}

			// $post_link = get_edit_post_link( $item['ID'] );

			$post_link = admin_url( '/options-general.php?page=bsf-mautic&action=edit&post=' . $item['ID'] );

			$post_title = "<a href='". $post_link ."'>".$item['post_title']."</a>";

			$row_actions = array();
			
			$row_actions['edit'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $post_link, esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'bsfmautic' ), $item['post_title'] ) ), __( 'Edit', 'bsfmautic' ) );

			$wpnonce = wp_create_nonce( 'delete-rule'.$item['ID'] );	
			
			$delete_url = admin_url( "admin-post.php?action=bsfm_delete_rule&rule_id=".$item['ID'] . "&_wpnonce=" .$wpnonce );

			$row_actions['delete'] = sprintf( '<a href="%1$s" title="%2$s" class="delete-link">%3$s</a>', $delete_url, esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;', 'bsfmautic' ), $item['post_title'] ) ), __( 'Delete', 'bsfmautic' ) );

			return $post_title . $this->row_actions( $row_actions );
		}

		/**
		 * Get a list of columns in this List Table.
		 *
		 * Format: 'internal-name' => 'Column Title'.
		 *
		 * @since 1.0.0
		 *
		 * @return array List of columns in this List Table.
		 */
		public function get_columns() {
			 $columns = array(
			 	'cb'          => '<input type="checkbox" />',
			    'post_title'  => 'Title',
			    'post_author' => 'Author'
			  );
			  return $columns;
		}

		/**
		 * Get a list of columns that are sortable.
		 *
		 * Format: 'internal-name' => array( $field for $item[ $field ], true for already sorted ).
		 *
		 * @since 1.0.0
		 *
		 * @return array List of sortable columns in this List Table.
		 */
		protected function get_sortable_columns() {
			

			$sortable_columns = array(
				'post_title' => array( 'post_title', true ),
				'post_author' => array( 'post_author', false ),
			);
			return $sortable_columns;
		}

		/**
		 * Get a list (name => title) bulk actions that are available.
		 *
		 * @since 1.0.0
		 *
		 * @return array Bulk actions for this table.
		 */
		protected function get_bulk_actions() {
			$actions = [
			    'bulk-delete' => 'Delete'
			];

  			return $actions;
		}

		protected function bulk_actions( $which = '' ) {
			if ( is_null( $this->_actions ) ) {
				$no_new_actions = $this->_actions = $this->get_bulk_actions();
				/** This filter is documented in the WordPress function WP_List_Table::bulk_actions() in wp-admin/includes/class-wp-list-table.php */
				$this->_actions = apply_filters( 'bulk_actions-' . $this->screen->id, $this->_actions );
				$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
				$two = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			$name_id = "bulk-action-{$which}";
			echo "<label for='{$name_id}' class='screen-reader-text'>" . __( 'Select Bulk Action', 'bsfmautic' ) . "</label>\n";
			echo "<select name='{$name_id}' id='{$name_id}'>\n";
			echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions', 'bsfmautic' ) . "</option>\n";
			foreach ( $this->_actions as $name => $title ) {
				echo "\t<option value='{$name}'>{$title}</option>\n";
			}
			echo "</select>\n";
			submit_button( __( 'Apply', 'bsfmautic' ), 'action', '', false, array( 'id' => "doaction{$two}" ) );
			echo "\n";
		}


		/**
		 * Prepares the list of items for displaying, by maybe searching and sorting, and by doing pagination.
		 *
		 * @since 1.0.0
		 */
		function prepare_items() {
			$columns = $this->get_columns();

			$this->process_bulk_action();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->items = $this->get_rules();
			
		}

		public function get_rules() {

			global $wpdb;
			$page_number = $this->get_pagenum();

			$query = "SELECT ID,post_title,post_author,post_modified_gmt FROM {$wpdb->prefix}posts where post_type='bsf-mautic-rule' && post_status = 'publish'";

			if( isset($_GET['s']) && !empty($_GET['s']) ) {
				$seachkey  = trim( $_GET['s'] );
				$query .= " && post_title LIKE '%".$seachkey."%'";
			}

			$total_items = count ( $wpdb->get_results( $query, ARRAY_A ) );

			$perpage = 10;

			//How many pages do we have in total?
			$totalpages = ceil( $total_items / $perpage );

			/* -- Register the pagination -- */
			$this->set_pagination_args( array(
				"total_items" => $total_items,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );
			
			$orderby = !empty($_GET["orderby"]) ? esc_attr($_GET["orderby"]) : 'ASC';

			$order = !empty($_GET["order"]) ? esc_attr($_GET["order"]) : '';
			if( !empty($orderby) & !empty($order)){ 
				$query .= ' ORDER BY '.$orderby.' '.$order; 
			}
			
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? esc_attr($_GET["paged"]) : '';

			//Page Number
			if( empty($paged) || !is_numeric($paged) || $paged<=0 ) { 
				$paged = 1; 
			}
			
			//adjust the query to take pagination into account
			if( !empty($paged) && !empty($perpage) ) {
				$offset = ( $paged -1 ) * $perpage;
				$query .=' LIMIT '. (int)$offset . ',' . (int)$perpage;
			}

			$result = $wpdb->get_results( $query, ARRAY_A );

			return $result;
		}
	}
}