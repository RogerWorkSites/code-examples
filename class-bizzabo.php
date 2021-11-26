<?php //--28.04
/**
 * MAIN CLASS BIZZABO
 * 
 * acf field - TOKEN in site option
 * CPT events in taxonomy.php
 * acf fields to events post_type
 * cron to this link - __site_url__/wp-admin/admin-ajax.php?action=bizzabo_events_import
 */

class UniBizzaboApi
{

	private $THEME_URL;
	private $TOKEN;
	private $BIZZABO_CURL = 'https://api.bizzabo.com/api/events/';
	private $SCRIPT_VER = '1.0.0';


	public function __construct()
	{
		/*Themes URLs*/
		$this->THEME_URL = CHILD_THEME_URI;
		$this->THEME_URL_BIZZABO = CHILD_THEME_URI . '/bizzabo_api';
		$this->TOKEN = get_field('bizzabo_api_token', 'option');

		/*Start Function*/
		$this->load();
	}

	/**
	 * Cron bizzabo import
	 */
	public function uni_bizzabo_events_import()
	{
		$cpt_events = '';
		$cpt_events_ids = [];
		$cpt_events_args = array(
			'post_type' => 'uni_events',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'fields' => 'ids',
		);
		$cpt_events = get_posts($cpt_events_args);
		if (!empty($cpt_events)) {
			foreach ($cpt_events as $event_id) {
				$bizz_id = get_field('event_bizz_id', $event_id);
				$cpt_events_ids[$event_id] = $bizz_id;
			}
		}
		$this->uni_update_bizzabo_events($cpt_events_ids);

		die();
	}


	/**
	 * GET all Events
	 */
	private function uni_bizzabo_get_events($other_filters = '', $page_num = 0, $size_num = 200, $sort_by = 'name', $status = 'published')
	{

		$curl = curl_init();
		$event_status = '?filter=status=' . $status;
		$page = '&page=' . $page_num;
		$size = '&size=' . $size_num; // 50 - for default
		$sort = '&sort=' . $sort_by . ',asc';
		$curl_url = $this->BIZZABO_CURL . $event_status . $page . $size . $sort . $other_filters;
		//file_put_contents(__DIR__ . '/A-curl.txt', print_r($curl_url, true));
		curl_setopt_array($curl, array(
			CURLOPT_URL => $curl_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $this->TOKEN
			),
		));
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		// after update
		$current_day = date('d-m-Y H:i:s');
		$file_imp_arr = $current_day . ' - import - ' . $response->page->totalElements . "\n";
		file_put_contents(__DIR__ . '/A_bizz_events_import.log', print_r($file_imp_arr, true), FILE_APPEND);
		return $response;
	}

	/**
	 * Update / Delete Bizzabo Event
	 */
	private function uni_update_bizzabo_events($cpt_events_ids)
	{

		$response = $this->uni_bizzabo_get_events();

		if (isset($response->content)) {
			//file_put_contents(__DIR__ . '/A-events.txt', print_r($response->content, true));
			foreach ($response->content as $bizz_event) {

				$bizz_category = $bizz_country = $bizz_state = $bizz_city = $bizz_address = $bizz_location = $bizz_descr = '';
				$types_search_arr = ['Virtual', 'virtual', 'In-person', 'In-Person', 'in-person', 'in-Person'];
				$types_search = FALSE;

				$bizz_id = $bizz_event->id;
				$bizz_name = $bizz_event->name;
				$bizz_start = date('Y-m-d H:i:s', strtotime($bizz_event->startDate));
				$bizz_end = date('Y-m-d H:i:s', strtotime($bizz_event->endDate));
				$bizz_url = $bizz_event->websiteUrl ?? '';
				$bizz_supportemail = $bizz_event->supportEmail ?? '';
				if (isset($bizz_event->description))
					$bizz_descr = $bizz_event->description;
				if (isset($bizz_event->venue->city))
					$bizz_city = $bizz_event->venue->city;
				if (isset($bizz_event->venue->state))
					$bizz_state = $bizz_event->venue->state;
				if (isset($bizz_event->venue->country))
					$bizz_country = $bizz_event->venue->country;
				if (isset($bizz_event->venue->displayAddress))
					$bizz_address = $bizz_event->venue->displayAddress;
				if (isset($bizz_event->type))
					$bizz_category = $bizz_event->type;

				//--search Virtual and person events
				if (!empty($bizz_category)) {
					foreach ($types_search_arr as $s_type) {
						$res = array_search($s_type, $bizz_category);
						if ($res !== FALSE) $types_search = TRUE;
					}
				}

				if ($types_search) {

					//--return id/false cpt event 
					$find_bizz = array_search($bizz_id, $cpt_events_ids);
					if ($find_bizz == FALSE) {
						//--Add new Event
						$new_event = array(
							'post_title'    => wp_strip_all_tags($bizz_name),
							'post_status'   => 'publish',
							'post_type'     => 'uni_events',
							'post_content'  => $bizz_descr
						);
						$new_event_id = wp_insert_post($new_event);
						if (isset($new_event_id) && !empty($new_event_id)) {
							$upd_event_id = $new_event_id;
							update_field('event_bizz_id', $bizz_id, $upd_event_id);
						}
					} else {
						//--Update this Event
						$upd_event_id = $find_bizz;
						$upd_event = array();
						$upd_event['ID'] = $upd_event_id;
						$upd_event['title'] = wp_strip_all_tags($bizz_name);
						$upd_event['post_content'] = $bizz_descr;
						wp_update_post(wp_slash($upd_event));
						unset($cpt_events_ids[$upd_event_id]);
					}

					//--add new taxonomy
					if (!empty($bizz_category) && $types_search) {
						wp_set_object_terms($upd_event_id, $bizz_category, 'events_cat');

						$state_abbr = json_decode(file_get_contents(CHILD_THEME_URI . '/functions/class/bizzabo/state_abbr.json'), ARRAY_A);
						if (!empty($bizz_state) && (!empty($state_abbr) && isset($state_abbr))) {
							if (array_key_exists($bizz_state, $state_abbr)) {
								wp_set_object_terms($upd_event_id, $state_abbr[$bizz_state], 'events_state');
							}
						}
					}

					//--update acf fields
					update_field('event_startdate', $bizz_start, $upd_event_id);
					update_field('event_enddate', $bizz_end, $upd_event_id);
					update_field('event_websiteurl', $bizz_url, $upd_event_id);
					update_field('event_supportemail', $bizz_supportemail, $upd_event_id);
					update_field('event_city', $bizz_city, $upd_event_id);
					update_field('event_state', $bizz_state, $upd_event_id);
					update_field('event_country', $bizz_country, $upd_event_id);

					$event_address = get_field('event_address', $upd_event_id);
					if (!empty($bizz_address) && ($bizz_country != $event_address)) {
						//--add coordinates
						$bizz_location = $this->get_event_coordinates($bizz_address);
						update_field('event_address', $bizz_address, $upd_event_id);
						update_field('event_location', $bizz_location, $upd_event_id);
					}
				}
			}
		}

		//--delete draft post after import
		if ($cpt_events_ids) {
			foreach ($cpt_events_ids as $cpt_event_id => $bizz_id) {
				wp_delete_post($cpt_event_id);
			}
		}
	}


	/**
	 * Events map
	 */
	private function bizz_events_map($ev_loc, $event_item_html = '', $type_class = '', $element = 'other')
	{

		$loc_cord = $loc_x = $loc_x = $marker_html = '';
		$loc_cord = explode(',', $ev_loc);
		$loc_x = intval($loc_cord[0]);
		$loc_y = intval($loc_cord[1]);
		if ($element == 'first') {
			$marker_html .= '<div id="map-canvas-1" class="map-wrapper type1" data-lat="' . $loc_x . '" data-lng="' . $loc_y . '" data-zoom="5" data-img-cluster="' . CHILD_THEME_URI . '/img/cluster"></div>';
		}
		if (!empty($event_item_html)) {
			$marker_html .= '<a class="marker" data-rel="map-canvas-1" data-lat="' . $loc_x . '" data-lng="' . $loc_y . '" data-image="' . CHILD_THEME_URI . '/img/event-' . $type_class . '.png" data-string=\'' . $event_item_html . '\'></a>';
		}
		return $marker_html;
	}

	/**
	 * Render events Function
	 */
	public function uni_render_events($events_ids)
	{
		$all_events = $states_arr = [];
		$events_map = $all_events_html = '';
		$qty_virtual = $qty_person = 0;
		if (!empty($events_ids)) {
			foreach ($events_ids as $event_id) {
				$ev_name = get_the_title($event_id);
				$ev_start = get_field('event_startdate', $event_id);
				$ev_start = get_field('event_startdate', $event_id);
				$ev_start_date = date('M j, Y', strtotime($ev_start));
				$ev_start_time = date('ga', strtotime($ev_start));
				$ev_end = get_field('event_enddate', $event_id);
				$ev_end_date = date('M j, Y', strtotime($ev_end));
				$ev_url = get_field('event_websiteurl', $event_id);
				$ev_supp_mail = get_field('event_supportemail', $event_id);
				$ev_city = get_field('event_city', $event_id);
				$ev_state = get_field('event_state', $event_id);
				$ev_country = get_field('event_country', $event_id);
				$ev_descr = get_the_excerpt($event_id);
				$state_terms = get_the_terms($event_id, 'events_state');
				$ev_loc = get_field('event_location', $event_id);

				$data_state = '';

				if (!empty($state_terms)) {
					foreach ($state_terms as $term) {
						$data_state = $term->slug;
						if (array_key_exists($term->slug, $states_arr)) {
							$states_arr[$term->slug]['qty'] = $states_arr[$term->slug]['qty'] + 1;
						} else {
							$states_arr[$term->slug] = ['qty' => 1, 'name' => $term->name, 'id' => $term->term_id];
						}
					}
					//file_put_contents(__DIR__ . '/A-states.txt', print_r($states_arr, true));
				}

				if (has_term('virtual', 'events_cat', $event_id)) {
					$type_class = 'blue';
					$type_text = __('Virtual', 'unilock');
					$type = 'virtual';
					$qty_virtual++;
				} elseif (has_term('in-person', 'events_cat', $event_id)) {
					$type_class = 'red';
					$type_text = __('In-Person', 'unilock');
					$type = 'in-person';
					$qty_person++;
				} else {
					$type = $type_text = $type_class = '';
				}

				$event_item = $this->event_item($event_id, $ev_url, $ev_name, $ev_start_date, $ev_start_time, $ev_city, $ev_state, $ev_descr, $type, $type_text, $type_class, $data_state);
				$all_events_html .= $event_item;

				if (!empty($ev_loc) && !empty($event_item)) {
					if (empty($events_map)) $events_map .= $this->bizz_events_map($ev_loc, $event_item, $type_class, 'first');
					else $events_map .= $this->bizz_events_map($ev_loc, $event_item, $type_class);
				}
			}
			$all_events['events'] = $all_events_html;
		}
		if (empty($all_events['events']) || !isset($all_events['events'])) {
			$all_events['events'] = '<p>' . __('No events found', 'unilock') . '</p>';
		}
		if (empty($events_map)) {
			$america_center = '44.86869889033095,-104.18335117430588';
			$events_map =  $this->bizz_events_map($america_center, '', '', 'first');
		}
		$all_events['virtual'] = $qty_virtual;
		$all_events['person'] =  $qty_person;
		$all_events['map'] = $events_map;
		$all_events['states_filter'] =  $this->uni_render_events_states($states_arr);


		return $all_events;
	}

	/**
	 * event item function for events and map
	 */
	private function event_item($event_id, $ev_url, $ev_name, $ev_start_date, $ev_start_time, $ev_city, $ev_state, $ev_descr, $type, $type_text, $type_class, $data_state)
	{
		$event_item_html = '';
		ob_start(); ?>
		<div class="event-item" data-event-id="<?php echo $event_id; ?>" data-event-type="<?php echo $type; ?>" data-event-state="<?php echo $data_state; ?>">
			<div class="event-item-header">
				<a href="<?php echo esc_url($ev_url); ?>" class="event-title" target="_blank"><?php echo esc_html($ev_name); ?></a>
				<div class="event-item-date">
					<?php echo esc_html($ev_start_date); ?><br>
					<?php /*echo '<br>' . esc_html($ev_end_date) . '<br>'; */ ?>
					<span><?php echo esc_html($ev_start_time); ?></span>
					<?php if (!empty($type)) : ?>
						<div class="type <?php echo $type_class; ?>"><?php echo $type_text; ?></div>
					<?php endif; ?>
				</div>
			</div>
			<div class="event-content">
				<?php if (!empty($ev_descr)) : ?>
					<div class="description text-cut-3"><?php echo mb_strimwidth(wp_kses_post($ev_descr), 0, 160, "..."); ?></div>
				<?php endif; ?>
				<?php if (!empty($ev_city)) : ?>
					<div class="city"><?php echo esc_html($ev_city) . ', ' . esc_html($ev_state); ?></div>
				<?php endif; ?>
				<a href="<?php echo esc_url($ev_url); ?>" class="link" target="_blank"><?php _e('Event Details', 'unilock'); ?></a>
			</div>
		</div>
		<?php $event_item_html = ob_get_clean();
		return $event_item_html;
	}



	/**
	 * Render states filter 
	 */
	private function uni_render_events_states($states_arr)
	{
		$qty_s = 1;
		$states_filter = '';
		if (!empty($states_arr)) {
			ob_start(); ?>
			<div class="sf-title"><?php _e('State / Province', 'unilock'); ?></div>
			<div class="sf-list">
				<?php foreach ($states_arr as $state_slug => $state_inf) : ?>
					<label class="sf-checkbox">
						<input type="checkbox" name="eventState" value="<?php echo $state_slug; ?>" hidden data-qty=<?php echo $qty_s; ?>>
						<span class="check-text"><?php echo $state_inf['name']; ?> <span class="qty">(<?php echo $state_inf['qty']; ?>)</span></span>
					</label>
				<?php $qty_s++;
				endforeach; ?>
			</div>
<?php $states_filter = ob_get_clean();
		}
		return $states_filter;
	}



	/**
	 * events get lat lag
	 */
	private function get_event_coordinates($address = '')
	{
		$location = '';
		if (!empty($address)) {
			$address = str_replace(" ", "%20", $address);
			$key = CHILD_GOOGLE_MAPS_API_KEY;
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&key=' .  $key . '&language=en',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			if (!empty($response)) {
				$res = json_decode($response, ARRAY_A);

				if (isset($res['results'][0]['geometry']))
					$location = $res['results'][0]['geometry']['location']['lat'] . ',' . $res['results'][0]['geometry']['location']['lng'];
			}
		}
		return $location;
	}


	/**
	 * events ajax filters
	 */
	public function uni_bizz_events_filters()
	{
		$filters = (isset($_POST['filters']) ? $_POST['filters'] : '');
		$current_day = date('Y-m-d H:i:s');
		$cpt_events_args = array(
			'post_type' => 'uni_events',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'event_enddate',
					'value'   => $current_day,
					'compare' => '>=',
					'type'    => 'DATETIME'
				),
				//  array(
				// 	'key'     => 'event_startdate',
				// 	'value'   => date('Y-m-d H:i:s', strtotime($filters['startDate'])),
				// 	'compare' => '>=',
				// 	'type'    => 'DATETIME'
				// ),
			)
		);

		if (!empty($filters['startDate'])) {
			$cpt_events_args['meta_query'][] = array(
				'key'     => 'event_startdate',
				'value'   => date('Y-m-d H:i:s', strtotime($filters['startDate'])),
				'compare' => '>=',
				'type'    => 'DATETIME'
			);
		}

		if (!empty($filters['endDate']) && $filters['endDate'] != $filters['startDate']) {
			$cpt_events_args['meta_query'][] = array(
				'key' => 'event_enddate',
				'value'   => date('Y-m-d 23:59:59', strtotime($filters['endDate'])),
				'compare' => '<=',
				'type'    => 'DATETIME'
			);
		}

		if (!empty($filters['eventType'])) {
			$cpt_events_args['tax_query'][] = array(
				'taxonomy' => 'events_cat',
				'field' => 'slug',
				'terms' => $filters['eventType'],
			);
		}
		if (!empty($filters['eventState'])) {
			$cpt_events_args['tax_query'][] = array(
				'taxonomy' => 'events_state',
				'field' => 'slug',
				'terms' => $filters['eventState'],
			);
		}

		$cpt_events = get_posts($cpt_events_args);
		$events_count = count($cpt_events);
		$all_events_arr = $this->uni_render_events($cpt_events);
		echo json_encode(array('success' => true, 'count' => $events_count, 'content' => $all_events_arr['events'], 'virtual' => $all_events_arr['virtual'], 'person' => $all_events_arr['person'], 'states_filter' => $all_events_arr['states_filter'], 'map' => $all_events_arr['map']));
		die();
	}

	/**
	 * Loading Ajax Function
	 */
	public function load()
	{
		/*cron import bizzabo to cpt*/
		add_action('wp_ajax_bizzabo_events_import', array($this, 'uni_bizzabo_events_import'));
		add_action('wp_ajax_nopriv_bizzabo_events_import', array($this, 'uni_bizzabo_events_import'));
		/*event filter*/
		add_action('wp_ajax_bizz_events_filters', array($this, 'uni_bizz_events_filters'));
		add_action('wp_ajax_nopriv_bizz_events_filters', array($this, 'uni_bizz_events_filters'));
	}
}


new UniBizzaboApi();
