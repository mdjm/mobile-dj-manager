<?php
	defined( 'ABSPATH' ) or die( "Direct access to this page is disabled!!!" );
	
/**
 * Manage the Event posts
 *
 *
 *
 */
		
/**
 * Define the columns to be displayed for event posts
 *
 * @params	arr		$columns	Array of column names
 *
 * @return	arr		$columns	Filtered array of column names
 */
function mdjm_event_post_columns( $columns ) {
	$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'title'			=> __( 'Event ID', 'mobile-dj-manager' ),
			'event_date'	=> __( 'Date', 'mobile-dj-manager' ),
			'client'		=> __( 'Client', 'mobile-dj-manager' ),
			'dj'			=> MDJM_DJ,
			'event_status'	=> __( 'Status', 'mobile-dj-manager' ),
			'event_type'	=> __( 'Event Type', 'mobile-dj-manager' ),
			'value'			=> __( 'Value', 'mobile-dj-manager' ),
			'balance'		=> __( 'Due', 'mobile-dj-manager' ),
			'playlist'		=> __( 'Playlist', 'mobile-dj-manager' ),
			'journal'		=> __( 'Journal', 'mobile-dj-manager' ),
		);
	
	if( !MDJM()->permissions->employee_can( 'manage_all_events' ) && isset( $columns['cb'] ) )
		unset( $columns['cb'] );
	
	return $columns;
} // mdjm_event_post_columns
add_filter( 'manage_mdjm-event_posts_columns' , 'mdjm_event_post_columns' );
		
/**
 * Define which columns are sortable for event posts
 *
 * @params	arr		$sortable_columns	Array of event post sortable columns
 *
 * @return	arr		$sortable_columns	Filtered Array of event post sortable columns
 */
function mdjm_event_post_sortable_columns( $sortable_columns )	{
	$sortable_columns['event_date'] = 'event_date';
	$sortable_columns['value'] = 'value';
	
	return $sortable_columns;
} // mdjm_event_post_sortable_columns
add_filter( 'manage_edit-mdjm-event_sortable_columns', 'mdjm_event_post_sortable_columns' );
		
/**
 * Define the data to be displayed in each of the custom columns for the Communications post types
 *
 * @param	str		$column_name	The name of the column to display
 *			int		$post_id		The current post ID
 * 
 *
 */
function mdjm_event_posts_custom_column( $column_name, $post_id )	{
	global $post;
	
	if( MDJM()->permissions->employee_can( 'edit_txns' ) && ( $column_name == 'value' || $column_name == 'balance' ) )
		$value = get_post_meta( $post->ID, '_mdjm_event_cost', true );
		
	switch ( $column_name ) {
		// Event Date
		case 'event_date':
			if( MDJM()->permissions->employee_can( 'read_events' ) )	{
				echo sprintf( '<a href="' . admin_url( 'post.php?post=%s&action=edit' ) . '">%s</a>', 
					$post_id, date( 'd M Y', strtotime( get_post_meta( $post_id, '_mdjm_event_date', true ) ) ) );
			}
			else
				echo date( 'd M Y', strtotime( get_post_meta( $post_id, '_mdjm_event_date', true ) ) );
		break;
			
		// Client
		// DJ
		case 'client':
		case 'dj':
			$user = get_userdata( get_post_meta( $post->ID, '_mdjm_event_' . $column_name, true ) );
			
			if( !empty( $user ) )	{
				if( MDJM()->permissions->employee_can( 'send_comms' ) )
					echo '<a href="' . mdjm_get_admin_page( 'comms') . '&to_user=' . 
						$user->ID . '&event_id=' . $post_id . '">' . 
						$user->display_name . '</a>';
						
				else
					echo $user->display_name;
			}
			else
				printf( __( '%sNot Assigned%s', 'mobile-dj-manager' ),
					'<span class="mdjm-form-error">',
					'</span>' );
		break;
								
		// Status
		case 'event_status':
			echo get_post_status_object( $post->post_status )->label;
			
			if( isset( $_GET['availability'] ) && $post_id == $_GET['e_id'] )	{
				if( is_dj() )
					$dj_avail = mdjm_availability_check( $_GET['availability'], $current_user->ID );
				else
					$dj_avail = mdjm_availability_check( $_GET['availability'] );
			}
		break;
			
		// Event Type
		case 'event_type':
			$event_types = get_the_terms( $post_id, 'event-types' );
			if( is_array( $event_types ) )	{
				foreach( $event_types as $key => $event_type ) {
					$event_types[$key] = $event_type->name;
				}
				echo implode( "<br/>", $event_types );
			}
		break;
			
		// Value
		case 'value':
			if( MDJM()->permissions->employee_can( 'edit_txns' ) )
				echo ( !empty( $value ) ? display_price( $value ) : '<span class="mdjm-form-error">' . display_price( '0.00' ) . '</span>' );
			
			else	
				echo '&mdash;';
		break;
			
		// Balance
		case 'balance':
			if( MDJM()->permissions->employee_can( 'edit_txns' ) )	{				
				$rcvd = MDJM()->txns->get_transactions( $post->ID, 'mdjm-income' );
				echo ( !empty( $rcvd ) && $rcvd != '0.00' ? display_price( ( $value - $rcvd ) ) : display_price( $value ) );
			}
			
			else	
				echo '&mdash;';
		break;
			
		/* -- Playlist -- */
		case 'playlist':
			if( MDJM()->permissions->employee_can( 'manage_events' ) )	{
				$total = MDJM()->events->count_playlist_entries( $post_id );
				echo '<a href="' . mdjm_get_admin_page( 'playlists' ) . $post_id . '">' . $total . ' ' . 
					_n( 'Song', 'Songs', $total, 'mobile-dj-manager' ) . '</a>' . "\r\n";
			}
			else
				echo '&mdash;';
		break;
		
		// Journal
		case 'journal':
			if( MDJM()->permissions->employee_can( 'read_events_all' ) )	{
				$total = wp_count_comments( $post_id )->approved;
				echo '<a href="' . admin_url( '/edit-comments.php?p=' . $post_id ) . '">' . 
					$total . ' ' . 
					_n( 'Entry', 'Entries', $total, 'mobile-dj-manager' ) . 
					'</a>' . "\r\n";
			}
			else
				echo '&mdash;';
		break;
	} // switch
	
} // mdjm_event_posts_custom_column
add_action( 'manage_mdjm-event_posts_custom_column' , 'mdjm_event_posts_custom_column', 10, 2 );
		
/**
 * Remove the edit bulk action from the event posts list
 *
 * @params	arr		$actions	Array of actions
 *
 * @return	arr		$actions	Filtered Array of actions
 */
function mdjm_event_bulk_action_list( $actions )	{
	unset( $actions['edit'] );
	
	return $actions;
} // mdjm_event_bulk_action_list
add_filter( 'bulk_actions-edit-mdjm-event', 'mdjm_event_bulk_action_list' );
		
/**
 * Add the filter dropdowns to the event post list
 *
 * @params
 *
 * @return
 */
function mdjm_event_post_filter_list()	{
	if( !isset( $_GET['post_type'] ) || $_GET['post_type'] != MDJM_EVENT_POSTS )
		return;
	
	mdjm_event_date_filter_dropdown();
	mdjm_event_type_filter_dropdown();
	if( MDJM_MULTI == true && MDJM()->permissions->employee_can( 'manage_employees' ) )
		mdjm_event_dj_filter_dropdown();
		
	if( MDJM()->permissions->employee_can( 'list_all_clients' ) )
		mdjm_event_client_filter_dropdown();	
} // mdjm_event_post_filter_list
add_action( 'restrict_manage_posts', 'mdjm_event_post_filter_list' );
		
/**
 * Display the filter drop down list to enable user to select and filter event by month/year
 * 
 * @params
 *
 * @return
 */
function mdjm_event_date_filter_dropdown()	{
	global $wpdb, $wp_locale;
	
	$month_query = "SELECT DISTINCT YEAR( meta_value ) as year, MONTH( meta_value ) as month 
		FROM `" . $wpdb->postmeta . "` WHERE `meta_key` = '_mdjm_event_date'";
																	
	$months = $wpdb->get_results( $month_query );
		
	$month_count = count( $months );
	
	if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
		return;

	$m = isset( $_GET['mdjm_filter_date'] ) ? (int) $_GET['mdjm_filter_date'] : 0;
	
	?>
	<label for="filter-by-date" class="screen-reader-text">Filter by Date</label>
	<select name="mdjm_filter_date" id="filter-by-date">
		<option value="0"><?php _e( 'All Dates', 'mobile-dj-manager' ); ?></option>
	<?php
	foreach ( $months as $arc_row ) {
		if ( 0 == $arc_row->year )
			continue;

		$month = zeroise( $arc_row->month, 2 );
		$year = $arc_row->year;

		printf( 
			"<option %s value='%s'>%s</option>\r\n",
			selected( $m, $year . $month, false ),
			esc_attr( $arc_row->year . $month ),
			/* translators: 1: month name, 2: 4-digit year */
			sprintf( 
				__( '%1$s %2$d', 'mobile-dj-manager' ),
				$wp_locale->get_month( $month ),
				$year )
		);
	}
	?>
	</select>
	<?php
} // mdjm_event_date_filter_dropdown
		
/**
 * Display the filter drop down list to enable user to select and filter event by type
 * 
 * @params
 *
 * @return
 */
function mdjm_event_type_filter_dropdown()	{			
	$event_types = get_categories(
						array(
							'type'			  => MDJM_EVENT_POSTS,
							'taxonomy'		  => 'event-types',
							'pad_counts'		=> false,
							'hide_empty'		=> true,
							'orderby'		  => 'name' ) );
	
	foreach( $event_types as $event_type )	{
		$values[$event_type->term_id] = $event_type->name;
	}
	?>
	<select name="mdjm_filter_type">
		<option value=""><?php echo __( 'All Event Types', 'mobile-dj-manager' ); ?></option>
		<?php
		$current_v = isset( $_GET['mdjm_filter_type'] ) ? $_GET['mdjm_filter_type'] : '';
		if( !empty( $values ) )	{
			foreach( $values as $value => $label ) {
				printf(
					'<option value="%s"%s>%s (%s)</option>',
					$value,
					$value == $current_v ? ' selected="selected"' : '',
					$label,
					$label );
			}
		}
		?>
	</select>
	<?php
} // mdjm_event_type_filter_dropdown
		
/**
 * Display the filter drop down list to enable user to select and filter event by DJ
 * 
 * @params
 *
 * @return
 */
function mdjm_event_dj_filter_dropdown()	{
	global $wpdb;
	
	$dj_query = "SELECT DISTINCT meta_value FROM `" . $wpdb->postmeta . 
		"` WHERE `meta_key` = '_mdjm_event_dj'";
							
	$djs = $wpdb->get_results( $dj_query );
	$dj_count = count( $djs );
	
	if ( !$dj_count || 1 == $dj_count )
		return;

	$artist = isset( $_GET['mdjm_filter_dj'] ) ? (int) $_GET['mdjm_filter_dj'] : 0;
	
	?>
	<label for="filter-by-dj" class="screen-reader-text">Filter by <?php echo MDJM_DJ; ?></label>
	<select name="mdjm_filter_dj" id="filter-by-dj">
		<option value="0"<?php selected( $artist, 0, false ); ?>><?php printf( __( 'All %s', 'mobile-dj-manager' ), MDJM_DJ . '\'s' ); ?></option>
	<?php
	foreach( $djs as $dj ) {
		$djinfo = get_userdata( $dj->meta_value );
		if( empty( $djinfo->display_name ) )
			continue;
			
		printf( "<option %s value='%s'>%s</option>\n",
			selected( $artist, $dj->meta_value, false ),
			$dj->meta_value,
			$djinfo->display_name
		);
	}
	?>
	</select>
	<?php			
} // mdjm_event_dj_filter_dropdown
		
/**
 * Display the filter drop down list to enable user to select and filter event by Client
 * 
 * @params
 *
 * @return
 */
function mdjm_event_client_filter_dropdown()	{
	global $wpdb;
					
	$client_query = "SELECT DISTINCT meta_value FROM `" . $wpdb->postmeta . 
		"` WHERE `meta_key` = '_mdjm_event_client'";
											
	$clients = $wpdb->get_results( $client_query );
	$client_count = count( $clients );
	
	if ( !$client_count || 1 == $client_count )
		return;

	$c = isset( $_GET['mdjm_filter_client'] ) ? (int) $_GET['mdjm_filter_client'] : 0;
	
	?>
	<label for="filter-by-client" class="screen-reader-text">Filter by <?php _e( 'Client', 'mobile-dj-manager' ); ?></label>
	<select name="mdjm_filter_client" id="mdjm_filter_client-by-dj">
		<option value="0"<?php selected( $c, 0, false ); ?>><?php _e( "All Client's", 'mobile-dj-manager' ); ?></option>
	<?php
	foreach( $clients as $client ) {
		$clientinfo = get_userdata( $client->meta_value );
		if( empty( $clientinfo->display_name ) )
			continue;
		
		printf( "<option %s value='%s'>%s</option>\n",
			selected( $c, $client->meta_value, false ),
			$client->meta_value,
			$clientinfo->display_name
		);
	}
	?>
	</select>
	<?php
} // mdjm_event_client_filter_dropdown
		
/**
 * Customise the view filter counts
 *
 * @called	views_edit-post hook
 *
 *
 */
function mdjm_event_view_filters( $views )	{
	// We only run this filter if the user has restrictive caps and the post type is mdjm-event
	if( MDJM()->permissions->employee_can( 'read_events_all' ) || !is_post_type_archive( MDJM_EVENT_POSTS ) )
		return $views;
	
	// The All filter
	$views['all'] = preg_replace( '/\(.+\)/U', '(' . count( MDJM()->events->dj_events() ) . ')', $views['all'] ); 
				
	$event_stati = mdjm_all_event_status();
	
	foreach( $event_stati as $status => $label )	{
		$events = MDJM()->events->dj_events( '', '', '', $status );
		
		if( empty( $events ) )	{
			if( isset( $views[$status] ) )
				unset( $views[$status] );
			
			continue;
		}
			
		$views[$status] = preg_replace( '/\(.+\)/U', '(' . count( $events ) . ')', $views[$status] );	
	}
	
	// Only show the views we want
	foreach( $views as $status => $link )	{
		if( $status != 'all' && !array_key_exists( $status, $event_stati ) )
			unset( $views[$status] );	
	}
	
	return $views;
} // mdjm_event_view_filters
add_filter( 'views_edit-mdjm-event' , 'mdjm_event_view_filters' );
		
/**
 * Customise the event post query 
 *
 * @called	pre_get_posts
 *
 * @params	obj		$query		The WP_Query
 *
 * @return	obj		$query		The customised WP_Query
 */
function mdjm_custom_event_post_query( $query )	{
	global $pagenow;
	
	if( !is_post_type_archive( MDJM_EVENT_POSTS ) || !$query->is_main_query() || !$query->is_admin || 'edit.php' != $pagenow )
		return;
	
	/**
	 * If searching it's only useful if we include clients and employees
	 */
	if( $query->is_search() )	{
		$users = new WP_User_Query(
			array(
				'search'			=> $_GET['s'],
				'search_columns'	=> array(
					'user_login',
					'user_email',
					'user_nicename',
					'display_name'
				)
			)
		); // WP_User_Query
						
		// Loop through WP_User_Query search looking for events where user is client or employee
		if( !empty( $users->results ) )	{
			foreach( $users->results as $user )	{
				$results = get_posts(
					array(
						'post_type'      => MDJM_EVENT_POSTS,
						'post_status'	=> 'any',
						'meta_query'	=> array(
							'relation'	=> 'OR',
							array(
								'key'		=> '_mdjm_event_dj',
								'value'  	=> $user->ID,
								'compare'	=> '==',
								'type'		=> 'NUMERIC'
							),
							array(
								'key'		=> '_mdjm_event_client',
								'value'  	=> $user->ID,
								'compare'	=> '==',
								'type'		=> 'NUMERIC'
							)
						)
					)
				); // get_posts
				
				if( !empty( $results ) )	{
					foreach( $results as $result )	{
						$events[] = $result->ID;								
					}
				}
				
			} // foreach( $users as $user )
		} // if( !empty( $users ) )
		if( !empty( $events ) )	{
			$query->set( 'post__in', $events );
			$query->set( 'post_status', array( 'mdjm-unattended', 'mdjm-enquiry', 'mdjm-contract', 'mdjm-approved', 'mdjm-failed', 'mdjm-rejected', 'mdjm-completed' ) );
		}

	} // if( $query->is_search() 
	//wp_die( print_r( $query ) );
	/**
	 * If current user is restricted, filter to their own events only
	 */	
	if( !MDJM()->permissions->employee_can( 'read_events_all' ) )	{
		global $user_ID;
		
		$query->set(
			'meta_query',
			array(
				'relation' => 'AND',
				array(
					'key'		=> '_mdjm_event_dj',
					'value'  	  => $user_ID,
					'compare'	=> '=='
				)
			)
		);
	}
} // mdjm_custom_event_post_query
add_action( 'pre_get_posts', 'mdjm_custom_event_post_query' );

/**
 * Save the meta data for the event
 *
 * @called	save_post_mdjm-event
 *
 * @param	int		$ID				The current post ID.
 *			obj		$post			The current post object (WP_Post).
 *			bool	$update			Whether this is an existing post being updated or not.
 * 
 * @return	void
 */
function mdjm_save_event_post( $ID, $post, $update )	{
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;
	
	if( empty( $update ) )
		return;
		
	// Permission Check
	if( !MDJM()->permissions->employee_can( 'manage_events' ) )	{
		if( MDJM_DEBUG == true )
			MDJM()->debug->log_it( 'PERMISSION ERROR: User ' . get_current_user_id() . ' is not allowed to edit venues' );
		 
		return;
	}
	
	// Remove the save post action to avoid loops.
	remove_action( 'save_post_mdjm-event', 'mdjm_save_event_post', 10, 3 );
	
	// Fire our pre-save hook
	do_action( 'mdjm_before_event_save', $ID, $post, $update );
	
	if( MDJM_DEBUG == true )
		MDJM()->debug->log_it( 'Starting Event Save', true );
				
	// Get current meta data for the post so we can track changes within the journal.
	$current_meta = get_post_meta( $ID );
	
	/**
	 * Get the Client ID and store it in the event data array.
	 * If a client has been selected from the dropdown, we simply use that ID.
	 * If adding a new client, call the method and use the returned user ID.
	 */
	$event_data['_mdjm_event_client'] = ( $_POST['client_name'] != 'add_new' ? 
		$_POST['client_name'] : MDJM()->events->mdjm_add_client() );
		
	if( !empty( $update ) && !empty( $_POST['client_name'] ) && $_POST['client_name'] != $current_meta['_mdjm_event_client'][0] )
		$field_updates[] = '     | Client changed to ' . $_POST['client_name'];
		
	/**
	 * For new events we fire the 'mdjm_add_new_event' action
	 */
	if( empty( $update ) )
		do_action( 'mdjm_add_new_event', $post );
	
	/**
	 * If the client is flagged to have their password reset, generate the new password and set the flag.
	 * The flag will be checked and called during the content filter process.
	 */
	if( !empty( $_POST['mdjm_reset_pw'] ) )	{
		if( MDJM_DEBUG == true )
			MDJM()->debug->log_it( 'User ' . $event_data['_mdjm_event_client'] . ' flagged for password reset' );
			
		$pass_reset = MDJM()->user->prepare_user_pass_reset( $event_data['_mdjm_event_client'] );
	}
	
	/**
	* Determine the Venue ID if an existing venue was selected.
	* Otherwise, determine if we're using the client's address or adding a manual venue address
	*/
	if( $_POST['venue_id'] != 'manual' && $_POST['venue_id'] != 'client' )
		$event_data['_mdjm_event_venue_id'] = $_POST['venue_id'];
		
	elseif( !empty( $_POST['_mdjm_event_venue_id'] ) && $_POST['_mdjm_event_venue_id'] == 'client' )
		$event_data['_mdjm_event_venue_id'] = 'client';
		
	else
		$event_data['_mdjm_event_venue_id'] = 'manual';
	
	if( empty( $update ) && 
		isset( $current_meta['_mdjm_event_venue_id'][0] ) && 
		$_POST['venue_id'] != $current_meta['_mdjm_event_venue_id'][0] )	{
	
		$field_updates[] = 'Venue changed from ' . ( $current_meta['_mdjm_event_venue_id'][0] != 'manual' ?
			get_the_title( $current_meta['_mdjm_event_venue_id'][0] ) : $current_meta['_mdjm_event_venue_name'][0] ) 				. ' to ' . ( is_numeric( $_POST['venue_id'] ) && $this->post_exists( $_POST['venue_id'] ) ?
			get_the_title( $_POST['venue_id'] ) : $_POST['venue_id'] );
	}
	
	/**
	 * If the option was selected to save the venue, prepare the post and post meta data
	 * for the venue.
	 */
	if( $_POST['venue_id'] == 'manual' && !empty( $_POST['save_venue'] ) )	{
		foreach( $_POST as $venue_key => $venue_value )	{
			if( substr( $venue_key, 0, 6 ) == 'venue_' )	{
				$venue_meta[$venue_key] = $venue_value;
				
				if( $venue_key == 'venue_postcode' && !empty( $venue_value ) )
					$venue_meta[$venue_key] = strtoupper( $venue_value );
				
				if( $venue_key == 'venue_email' && !empty( $venue_value ) )
					$venue_meta[$venue_key] = sanitize_email( $venue_value );
					
				else
					$venue_meta[$venue_key] = sanitize_text_field( ucwords( $venue_value ) );
			}
		}
		// Create the venue post and store the post ID
		if( MDJM_DEBUG == true )
			MDJM()->debug->log_it( 'New venue to be created' );
		
		// Remove the save post hook for venue posts and insert the new venue
		remove_action( 'save_post_mdjm-venue', 'mdjm_save_venue_post', 10, 3 );
		$event_data['_mdjm_event_venue_id'] = MDJM()->events->mdjm_add_venue( 
																	array( 'venue_name' => $_POST['venue_name'] ), 
																	$venue_meta );
		add_action( 'save_post_mdjm-venue', 'mdjm_save_venue_post', 10, 3 );
	}
	
	// The venue is set to manual or client for this event so store the values in event post meta data.
	else	{
		// Manual venue address entry
		if( $_POST['venue_id'] != 'client' )	{ 
			$event_data['_mdjm_event_venue_name'] = sanitize_text_field( ucwords( $_POST['venue_name'] ) );
			$event_data['_mdjm_event_venue_contact'] = sanitize_text_field( ucwords( $_POST['venue_contact'] ) );
			$event_data['_mdjm_event_venue_phone'] = sanitize_text_field( $_POST['venue_phone'] );
			$event_data['_mdjm_event_venue_email'] = sanitize_email( strtolower( $_POST['venue_email'] ) );
			$event_data['_mdjm_event_venue_address1'] = sanitize_text_field( ucwords( $_POST['venue_address1'] ) );
			$event_data['_mdjm_event_venue_address2'] = sanitize_text_field( ucwords( $_POST['venue_address2'] ) );
			$event_data['_mdjm_event_venue_town'] = sanitize_text_field( ucwords( $_POST['venue_town'] ) );
			$event_data['_mdjm_event_venue_county'] = sanitize_text_field( ucwords( $_POST['venue_county'] ) );
			$event_data['_mdjm_event_venue_postcode'] = strtoupper( sanitize_text_field( $_POST['venue_postcode'] ) );
		}
		// Using clients address
		else	{
			$client_data = get_userdata( $event_data['_mdjm_event_client'] );
			$event_data['_mdjm_event_venue_name'] = __( 'Client Address', 'mobile-dj-manager' );
			$event_data['_mdjm_event_venue_contact'] = !empty( $client_data->first_name ) ? sanitize_text_field( $client_data->first_name ) : '';
			$event_data['_mdjm_event_venue_contact'] .= ' ' . !empty( $client_data->last_name ) ? sanitize_text_field( $client_data->last_name ) : '';
			$event_data['_mdjm_event_venue_phone'] = !empty( $client_data->phone1 ) ? $client_data->phone1 : '';
			$event_data['_mdjm_event_venue_email'] = !empty( $client_data->user_email ) ? $client_data->user_email : '';
			$event_data['_mdjm_event_venue_address1'] = !empty( $client_data->address1 ) ? $client_data->address1 : '';
			$event_data['_mdjm_event_venue_address2'] = !empty( $client_data->address2 ) ? $client_data->address2 : '';
			$event_data['_mdjm_event_venue_town'] = !empty( $client_data->town ) ? $client_data->town : '';
			$event_data['_mdjm_event_venue_county'] = !empty( $client_data->county ) ? $client_data->county : '';
			$event_data['_mdjm_event_venue_postcode'] = !empty( $client_data->postcode ) ? $client_data->postcode : '';
		}
	}
	
	/**
	 * Prepare the remaining event meta data.
	 */
	$event_data['_mdjm_event_last_updated_by'] = get_current_user_id();
	
	/**
	 * Event name.
	 * If no name is defined, use the event type.
	 */
	if( empty( $_POST['_mdjm_event_name'] ) )
		$_POST['_mdjm_event_name'] = get_term( $_POST['mdjm_event_type'], 'event-types' )->name;
		
	// Generate the playlist reference for guest access						
	if( empty( $update ) || empty( $current_meta['_mdjm_event_playlist_access'][0] ) )
		$event_data['_mdjm_event_playlist_access'] = MDJM()->events->playlist_ref();
	
	// Set whether or not the playlist is enabled for the event
	$event_data['_mdjm_event_playlist'] = !empty( $_POST['enable_playlist'] ) ? $_POST['enable_playlist'] : 'N';
	
	/**
	 * All the remaining custom meta fields are prefixed with '_mdjm_event_'.
	 * Loop through all $_POST data and put all event meta fields into the $event_data array
	 */
	foreach( $_POST as $key => $value )	{
		if( substr( $key, 0, 12 ) == '_mdjm_event_' )
			$event_data[$key] = $value;	
	}
	
	/**
	 * We store all times in H:i:s but the user may prefer a different format so we
	 * determine their time format setting and adjust to H:i:s for saving.
	 */
	if( MDJM_TIME_FORMAT == 'H:i' )	{ // 24 Hr
		$event_data['_mdjm_event_start'] = date( 'H:i:s', strtotime( $_POST['event_start_hr'] . ':' . $_POST['event_start_min'] ) ); 
		$event_data['_mdjm_event_finish'] = date( 'H:i:s', strtotime( $_POST['event_finish_hr'] . ':' . $_POST['event_finish_min'] ) );
		$event_data['_mdjm_event_djsetup_time'] = date( 'H:i:s', strtotime( $_POST['dj_setup_hr'] . ':' . $_POST['dj_setup_min'] ) );
	}
	
	else	{ // 12 hr
		$event_data['_mdjm_event_start'] = date( 'H:i:s', strtotime( $_POST['event_start_hr'] . ':' . $_POST['event_start_min'] . $_POST['event_start_period'] ) );
		$event_data['_mdjm_event_finish'] = date( 'H:i:s', strtotime( $_POST['event_finish_hr'] . ':' . $_POST['event_finish_min'] . $_POST['event_finish_period'] ) );
		$event_data['_mdjm_event_djsetup_time'] = date( 'H:i:s', strtotime( $_POST['dj_setup_hr'] . ':' . $_POST['dj_setup_min'] . $_POST['dj_setup_period'] ) );
	}
	
	/**
	 * Set the event end date.
	 * If the finish time is less than the start time, assume following day.
	 */
	if( date( 'H', strtotime( $event_data['_mdjm_event_finish'] ) ) > date( 'H', strtotime( $event_data['_mdjm_event_start'] ) ) )
		$event_data['_mdjm_event_end_date'] = $_POST['_mdjm_event_date'];
		
	else // End date is following day
		$event_data['_mdjm_event_end_date'] = date( 'Y-m-d', strtotime( '+1 day', strtotime( $_POST['_mdjm_event_date'] ) ) );
		
	/**
	 * Determine the state of the Deposit & Balance payments.
	 * 
	 */
	$event_data['_mdjm_event_deposit_status'] = !empty( $_POST['deposit_paid'] ) ? $_POST['deposit_paid'] : 'Due';
	$event_data['_mdjm_event_balance_status'] = !empty( $_POST['balance_paid'] ) ? $_POST['balance_paid'] : 'Due';
	
	$deposit_payment = $event_data['_mdjm_event_deposit_status'] == 'Paid' && $current_meta['_mdjm_event_deposit_status'][0] != 'Paid' ? true : false;
	
	$balance_payment = $event_data['_mdjm_event_balance_status'] == 'Paid' && $current_meta['_mdjm_event_deposit_status'][0] != 'Paid' ? true : false;
	
	// Add-Ons
	if( MDJM_PACKAGES == true )
		$event_data['_mdjm_event_addons'] = !empty( $_POST['event_addons'] ) ? $_POST['event_addons'] : '';
	
	// Assign the event type
	$existing_event_type = wp_get_object_terms( $post->ID, 'event-types' );
	
	if( !isset( $existing_event_type[0] ) || $existing_event_type[0]->term_id != $_POST['mdjm_event_type'] )
		$field_updates[] = 'Event Type changed to ' . get_term( $_POST['mdjm_event_type'], 'event-types' )->name;
	
	MDJM()->events->mdjm_assign_event_type( $_POST['mdjm_event_type'] );
	
	if( MDJM_DEBUG == true )
		 MDJM()->debug->log_it( 'Beginning Meta Updates' );
	
	/**
	 * Loop through the $event_data array, arrange the data and then store it.
	 */ 
	foreach( $event_data as $event_meta_key => $event_meta_value )	{
		// Cost, deposit and main employee wage.		
		if( $event_meta_key == '_mdjm_event_cost' || $event_meta_key == '_mdjm_event_deposit'
			|| $event_meta_key == '_mdjm_event_dj_wage' )
			$event_meta_value = $event_meta_value;
		
		// Postcodes are uppercase.
		if( $event_meta_key == 'venue_postcode' && !empty( $event_meta_value ) )
			$event_meta_value = strtoupper( $event_meta_value );
		
		// Emails are lowercase	.
		if( $event_meta_key == 'venue_email' && !empty( $event_meta_value ) )
			$event_meta_value = strtolower( $event_meta_value );
		
		if( $event_meta_key == '_mdjm_event_package' )
			$event_meta_value = sanitize_text_field( strtolower( $event_meta_value ) );	
			
		elseif( $event_meta_key == '_mdjm_event_addons' )
			$event_meta_value = $event_meta_value;
			
		elseif( !strpos( $event_meta_key, 'notes' ) )
			$event_meta_value = sanitize_text_field( ucwords( $event_meta_value ) );
			
		else
			$event_meta_value = sanitize_text_field( ucfirst( $event_meta_value ) );
		
		// If we have a value and the key did not exist previously, add it.
		if ( !empty( $event_meta_value ) && ( empty( $current_meta[$event_meta_key] ) || empty( $current_meta[$event_meta_key][0] ) ) )	{
			add_post_meta( $ID, $event_meta_key, $event_meta_value );
			
			if( !empty( $update ) )
				$field_updates[] = 'Field ' . $event_meta_key . ' added: ' . $event_meta_value;
		}
		// If a value existed, but has changed, update it.
		elseif ( !empty( $event_meta_value ) && $event_meta_value != $current_meta[$event_meta_key][0] )	{
			update_post_meta( $ID, $event_meta_key, $event_meta_value );
			if( !empty( $update ) )
				$field_updates[] = 'Field ' . $event_meta_key . ' updated: ' . $current_meta[$event_meta_key][0] . ' replaced with ' . $event_meta_value;
		}
			
		// If there is no new meta value but an old value exists, delete it.
		elseif ( empty( $event_meta_value ) && !empty( $current_meta[$event_meta_key][0] ) )	{
			delete_post_meta( $ID, $event_meta_key, $event_meta_value );
			
			if( !empty( $update ) )
				$field_updates[] = 'Field ' . $event_meta_key . ' updated: ' . $current_meta[$event_meta_key][0] . ' removed';
		}
	}
	
	if( MDJM_DEBUG == true )	{
		MDJM()->debug->log_it( 'Meta Updates Completed     ' . PHP_EOL . '| ' .
			( !empty( $field_updates ) ? implode( "\r\n" . '     | ', $field_updates ) : '' )  );
	}
	
	/**
	 * Check for manual payment received & process.
	 * This needs to be completed before we send any emails to ensure shortcodes are correct.
	 */
	if( $deposit_payment == true || $balance_payment == true )	{
		if( $balance_payment == true )
			$type = MDJM_BALANCE_LABEL;
			
		else
			$type = MDJM_DEPOSIT_LABEL;
		
		// Insert the event transaction
		MDJM()->txns->manual_event_payment( $type, $ID );
	}
	
	// Set the event status & initiate tasks based on the status
	if( $_POST['original_post_status'] != $_POST['mdjm_event_status'] )	{
		$event_stati = mdjm_all_event_status();
		
		$field_updates[] = 'Event status ' . 
			( isset( $event_stati[$_POST['original_post_status']] ) ? 'set ' : 'changed from ' ) . 
				( $_POST['original_post_status'] != 'auto-draft' ? $event_stati[$_POST['original_post_status']] : 'new' ) . 
			' to ' . $event_stati[$_POST['mdjm_event_status']];
		
		wp_transition_post_status( $_POST['mdjm_event_status'], $_POST['original_post_status'], $post );
		wp_update_post( array( 'ID' => $ID, 'post_status' => $_POST['mdjm_event_status'] ) );
		
		$method = 'status_' . substr( $_POST['mdjm_event_status'], 5 );
		
		if( method_exists( MDJM()->events, $method ) )
			MDJM()->events->$method( $ID, $post, $event_data, $field_updates );
		
		// Remove password reset flag if set
		if( !empty( $pass_reset ) )	{
			if( MDJM_DEBUG == true )
				MDJM()->debug->log_it( 'Removing password reset flag' );
			
			delete_user_meta( $event_data['_mdjm_event_client'], 'mdjm_pass_action' );
		}	
	} // if( $_POST['original_post_status'] != $_POST['mdjm_event_status'] )
	
	// Event status is un-changed so simply log the changes to the journal
	else	{		
		if( MDJM_JOURNAL == true )	{
			if( MDJM_DEBUG == true )
				MDJM()->debug->log_it( 'Adding journal entry' );
				
			MDJM()->events->add_journal( array(
						'user' 			=> get_current_user_id(),
						'comment_content' => 'Event ' . ( empty( $update ) ? 
												'created' : 'updated' ) . ' via Admin <br /><br />' .
											 	( isset( $field_updates ) ? 
													implode( '<br />', $field_updates ) : 
												'' ) . '<br />(' . time() . ')',
						'comment_type' 	=> 'mdjm-journal',
						),
						array(
							'type' 		  => 'create-event',
							'visibility'	=> '1',
						) );
		}
		else	{
			if( MDJM_DEBUG == true )
				MDJM()->debug->log_it( 'Journalling is disabled' );	
		}
	}
	
	// Fire the save event hook
	do_action( 'mdjm_save_event', $post, $_POST['mdjm_event_status'] );
	
	// Fire our post save hook
	do_action( 'mdjm_after_event_save', $ID, $post, $update );
	
	// Re-add the save post action to avoid loops
	add_action( 'save_post_mdjm-event', 'mdjm_save_event_post', 10, 3 );
	
	if( MDJM_DEBUG == true )
		MDJM()->debug->log_it( 'Completed Event Save', true );
	
} // mdjm_save_event_post
add_action( 'save_post_mdjm-event', 'mdjm_save_event_post', 10, 3 );
?>