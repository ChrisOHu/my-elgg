<?php
/**
 * Elgg river.
 * Activity stream functions.
 *
 * @package Elgg.Core
 * @subpackage SocialModel.River
 */

/**
 * Adds an item to the river.
 *
 * @param string $view          The view that will handle the river item (must exist)
 * @param string $action_type   An arbitrary string to define the action (eg 'comment', 'create')
 * @param int    $subject_guid  The GUID of the entity doing the action
 * @param int    $object_guid   The GUID of the entity being acted upon
 * @param int    $access_id     The access ID of the river item (default: same as the object)
 * @param int    $posted        The UNIX epoch timestamp of the river item (default: now)
 * @param int    $annotation_id The annotation ID associated with this river entry
 *
 * @return int/bool River ID or false on failure
 */
function add_to_river($view, $action_type, $subject_guid, $object_guid, $access_id = "",
$posted = 0, $annotation_id = 0) {

	global $CONFIG;

	// use default viewtype for when called from web services api
	if (!elgg_view_exists($view, 'default')) {
		return false;
	}
	if (!($subject = get_entity($subject_guid))) {
		return false;
	}
	if (!($object = get_entity($object_guid))) {
		return false;
	}
	if (empty($action_type)) {
		return false;
	}
	if ($posted == 0) {
		$posted = time();
	}
	if ($access_id === "") {
		/**To replace the original default access level: We want river-items' default access is ACCESS_FRIEND
		 *$access_id = $object->access_id;
		 */
		$access_id = ACCESS_FRIENDS;
	}
	$type = $object->getType();
	$subtype = $object->getSubtype();

	$view = sanitise_string($view);
	$action_type = sanitise_string($action_type);
	$subject_guid = sanitise_int($subject_guid);
	$object_guid = sanitise_int($object_guid);
	$access_id = sanitise_int($access_id);
	$posted = sanitise_int($posted);
	$annotation_id = sanitise_int($annotation_id);

	$values = array(
		'type' => $type,
		'subtype' => $subtype,
		'action_type' => $action_type,
		'access_id' => $access_id,
		'view' => $view,
		'subject_guid' => $subject_guid,
		'object_guid' => $object_guid,
		'annotation_id' => $annotation_id,
		'posted' => $posted,
	);

	// return false to stop insert
	$values = elgg_trigger_plugin_hook('creating', 'river', null, $values);
	if ($values == false) {
		// inserting did not fail - it was just prevented
		return true;
	}

	extract($values);

	// Attempt to save river item; return success status
	$id = insert_data("insert into {$CONFIG->dbprefix}river " .
		" set type = '$type', " .
		" subtype = '$subtype', " .
		" action_type = '$action_type', " .
		" access_id = $access_id, " .
		" view = '$view', " .
		" subject_guid = $subject_guid, " .
		" object_guid = $object_guid, " .
		" annotation_id = $annotation_id, " .
		" posted = $posted, " .
		" ref_count = 1");
	//Update creater's river in table(river-peruser)
	$rst = insert_data("insert into {$CONFIG->dbprefix}river_peruser " .
		" set user_guid = $subject_guid, " .
		" river_item_id = $id, " .
		" isCreater = 1");
	echo "rst=" . $rst;
	if ($rst === false) {
		return false;
	}

	// update the entities which had the action carried out on it
	// @todo shouldn't this be down elsewhere? Like when an annotation is saved?
	if ($id) {
		update_entity_last_action($object_guid, $posted);
		
		$river_items = elgg_get_river(array('id' => $id));
		if ($river_items) {
			elgg_trigger_event('created', 'river', $river_items[0]);
		}
		return $id;
	} else {
		return false;
	}
}

/**
 * Update rivers 
 * 
 * @param
 * 	event		  =>created
 * 	object_type	  =>river
 * 	object		  =>ElggRiverItem
 * @return 
 * 	bool
 */
function elgg_update_rivers($event, $object_type, $object) {
	if (!($object) || !($object instanceof ElggRiverItem))
		return false;
	if ($object->access_id == ACCESS_PRIVATE)
		return false;

	if ($object->access_id == ACCESS_PUBLIC || $object->access_id == ACCESS_LOGGED_IN) {
		$site = elgg_get_site_entity();
		if ($site && $site instanceof ElggSite) {
			$notifiees = $site->getSiteUserIds();
			return elgg_add_to_user_rivers($notifiees, $object);
		}

		return false;
	}
	else if ($object->access_id == ACCESS_FRIENDS) {
		$user = elgg_get_logged_in_user_entity();
		if ($user && $user instanceof ElggUser) {
			$notifiees = $user->getFriendIds();
			return elgg_add_to_user_rivers($notifiees, $objects);
		}
		return false;
	}
	else 
		return false;
}

/**
 * add to users' rivers
 *
 * @param
 * 	array 		$ids
 * 	ElggRiverItem 	$object
 *
 * @return
 * 	bool
 */
function elgg_add_to_user_rivers($ids, $object) {
	global $CONFIG;
	if (!$ids || !is_array($ids) || !$object || !($object instanceof ElggRiverItem))
		return false;

	$count = 0;
	$valid_ids = array();
	foreach ($ids as $id) {
		if (get_user($id)) {
			$valid_ids[$count++] = $id;
		}
	}

	if ($count > 0)	{
		$rv_id = $object->id;
		foreach ($valid_ids as $key => $id) {
			if ($key < $count)
				$value .= "($id, $rv_id, 0),";
			else
				$value .= "($id, $rv_id, 0)";
		}
		$result = insert_data("INSERT INTO {$CONFIG->dbprefix}river_peruser(user_guid, river_item_id, isCreater) VALUES " . $value);
		if ($result != false) {
			$result = update_data("UPDATE {$CONFIG->dbprefix}river SET ref_count=ref_count+$count+1 WHERE id=$rv_id");
			if ($result != false)
				return true;
		}	
	}
	return false;
}

/**
 * delete from user' river
 *
 * @param
 * 	int		$user_id
 * 	ElggRiverItem	$object
 *
 * @return 
 * 	bool
 */
function elgg_delete_from_user_river($id, $object) {
	global $CONFIG;

	if (get_user($id) && $object && $object instanceof ElggRiverItem)
	{
		$oid = $object->id;
		$result = delete_data("DELETE FROM {$CONFIG->dbprefix}river_peruser WHERE user_guid=$id AND river_item_id=$oid");
		if ($result != false) {
			$rv = elgg_get_river(array('id' => $oid));		
			$rvitem = $rv[0];
			if ($rvitem && $rvitem instanceof ElggRiverItem)
			{
				if (($rvitem->ref_count-1) <= 0) 
					$result = delete_data("DELETE FROM {$CONFIG->dbprefix}river WHERE id=$oid AND ref_count<=1");
				else
					$result = update_data("UPDATE {$CONFIG->dbprefix}river SET ref_count=ref_count-1 WHERE id=$oid");

				if ($result != false)
					return true;
			}
		}
	}
	
	return false;
}

/**
 * Delete river items
 *
 * @warning not checking access (should we?)
 *
 * @param array $options Parameters:
 *   ids                  => INT|ARR River item id(s)
 *   subject_guids        => INT|ARR Subject guid(s)
 *   object_guids         => INT|ARR Object guid(s)
 *   annotation_ids       => INT|ARR The identifier of the annotation(s)
 *   action_types         => STR|ARR The river action type(s) identifier
 *   views                => STR|ARR River view(s)
 *
 *   types                => STR|ARR Entity type string(s)
 *   subtypes             => STR|ARR Entity subtype string(s)
 *   type_subtype_pairs   => ARR     Array of type => subtype pairs where subtype
 *                                   can be an array of subtype strings
 * 
 *   posted_time_lower    => INT     The lower bound on the time posted
 *   posted_time_upper    => INT     The upper bound on the time posted
 *
 * @return bool
 * @since 1.8.0
 */
function elgg_delete_river(array $options = array()) {
	global $CONFIG;

	$defaults = array(
		'ids'                  => ELGG_ENTITIES_ANY_VALUE,

		'subject_guids'	       => ELGG_ENTITIES_ANY_VALUE,
		'object_guids'         => ELGG_ENTITIES_ANY_VALUE,
		'annotation_ids'       => ELGG_ENTITIES_ANY_VALUE,

		'views'                => ELGG_ENTITIES_ANY_VALUE,
		'action_types'         => ELGG_ENTITIES_ANY_VALUE,

		'types'	               => ELGG_ENTITIES_ANY_VALUE,
		'subtypes'             => ELGG_ENTITIES_ANY_VALUE,
		'type_subtype_pairs'   => ELGG_ENTITIES_ANY_VALUE,

		'posted_time_lower'	   => ELGG_ENTITIES_ANY_VALUE,
		'posted_time_upper'	   => ELGG_ENTITIES_ANY_VALUE,

		'wheres'               => array(),
		'joins'                => array(),

	);

	$options = array_merge($defaults, $options);

	$singulars = array('id', 'subject_guid', 'object_guid', 'annotation_id', 'action_type', 'view', 'type', 'subtype');
	$options = elgg_normalise_plural_options_array($options, $singulars);

	$wheres = $options['wheres'];

	$wheres[] = elgg_get_guid_based_where_sql('rv.id', $options['ids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.subject_guid', $options['subject_guids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.object_guid', $options['object_guids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.annotation_id', $options['annotation_ids']);
	$wheres[] = elgg_river_get_action_where_sql($options['action_types']);
	$wheres[] = elgg_river_get_view_where_sql($options['views']);
	$wheres[] = elgg_get_river_type_subtype_where_sql('rv', $options['types'],
		$options['subtypes'], $options['type_subtype_pairs']);

	if ($options['posted_time_lower'] && is_int($options['posted_time_lower'])) {
		$wheres[] = "rv.posted >= {$options['posted_time_lower']}";
	}

	if ($options['posted_time_upper'] && is_int($options['posted_time_upper'])) {
		$wheres[] = "rv.posted <= {$options['posted_time_upper']}";
	}

	// see if any functions failed
	// remove empty strings on successful functions
	foreach ($wheres as $i => $where) {
		if ($where === FALSE) {
			return FALSE;
		} elseif (empty($where)) {
			unset($wheres[$i]);
		}
	}

	// remove identical where clauses
	$wheres = array_unique($wheres);

	$query = "DELETE rv.* FROM {$CONFIG->dbprefix}river rv ";

	// remove identical join clauses
	$joins = array_unique($options['joins']);
	
	// add joins
	foreach ($joins as $j) {
		$query .= " $j ";
	}

	// add wheres
	$query .= ' WHERE ';

	foreach ($wheres as $w) {
		$query .= " $w AND ";
	}
	$query .= "1=1";

	$result = delete_data($query);
	if ($result != false) {
		return delete_data("DELETE FROM {$CONFIG->dbprefix}river_peruser WHERE river_item_id NOT IN (SELECT id FROM {$CONFIG->dbprefix}entroriver)");
	}
	else
		return false;
}

/**
 * Get river items
 *
 * @note If using types and subtypes in a query, they are joined with an AND.
 *
 * @param array $options Parameters:
 *   ids                  => INT|ARR River item id(s)
 *   subject_guids        => INT|ARR Subject guid(s)
 *   object_guids         => INT|ARR Object guid(s)
 *   annotation_ids       => INT|ARR The identifier of the annotation(s)
 *   action_types         => STR|ARR The river action type(s) identifier
 *   posted_time_lower    => INT     The lower bound on the time posted
 *   posted_time_upper    => INT     The upper bound on the time posted
 *
 *   types                => STR|ARR Entity type string(s)
 *   subtypes             => STR|ARR Entity subtype string(s)
 *   type_subtype_pairs   => ARR     Array of type => subtype pairs where subtype
 *                                   can be an array of subtype strings
 *
 *   relationship         => STR     Relationship identifier
 *   relationship_guid    => INT|ARR Entity guid(s)
 *   inverse_relationship => BOOL    Subject or object of the relationship (false)
 *
 * 	 limit                => INT     Number to show per page (20)
 *   offset               => INT     Offset in list (0)
 *   count                => BOOL    Count the river items? (false)
 *   order_by             => STR     Order by clause (rv.posted desc)
 *   group_by             => STR     Group by clause
 *
 * @return array|int
 * @since 1.8.0
 */
function elgg_get_river(array $options = array()) {
	global $CONFIG;

	$defaults = array(
		'ids'                  => ELGG_ENTITIES_ANY_VALUE,

		'subject_guids'	       => ELGG_ENTITIES_ANY_VALUE,
		'object_guids'         => ELGG_ENTITIES_ANY_VALUE,
		'annotation_ids'       => ELGG_ENTITIES_ANY_VALUE,
		'action_types'         => ELGG_ENTITIES_ANY_VALUE,

		'relationship'         => NULL,
		'relationship_guid'    => NULL,
		'inverse_relationship' => FALSE,

		'types'	               => ELGG_ENTITIES_ANY_VALUE,
		'subtypes'             => ELGG_ENTITIES_ANY_VALUE,
		'type_subtype_pairs'   => ELGG_ENTITIES_ANY_VALUE,

		'posted_time_lower'	   => ELGG_ENTITIES_ANY_VALUE,
		'posted_time_upper'	   => ELGG_ENTITIES_ANY_VALUE,

		'limit'                => 20,
		'offset'               => 0,
		'count'                => FALSE,

		'order_by'             => 'rv.posted desc',
		'group_by'             => ELGG_ENTITIES_ANY_VALUE,

		'wheres'               => array(),
		'joins'                => array(),
	);

	$options = array_merge($defaults, $options);

	$singulars = array('id', 'subject_guid', 'object_guid', 'annotation_id', 'action_type', 'type', 'subtype');
	$options = elgg_normalise_plural_options_array($options, $singulars);

	$wheres = $options['wheres'];

	$wheres[] = elgg_get_guid_based_where_sql('rv.id', $options['ids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.subject_guid', $options['subject_guids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.object_guid', $options['object_guids']);
	$wheres[] = elgg_get_guid_based_where_sql('rv.annotation_id', $options['annotation_ids']);
	$wheres[] = elgg_river_get_action_where_sql($options['action_types']);
	$wheres[] = elgg_get_river_type_subtype_where_sql('rv', $options['types'],
		$options['subtypes'], $options['type_subtype_pairs']);

	if ($options['posted_time_lower'] && is_int($options['posted_time_lower'])) {
		$wheres[] = "rv.posted >= {$options['posted_time_lower']}";
	}

	if ($options['posted_time_upper'] && is_int($options['posted_time_upper'])) {
		$wheres[] = "rv.posted <= {$options['posted_time_upper']}";
	}

	$joins = $options['joins'];

	if ($options['relationship_guid']) {
		$clauses = elgg_get_entity_relationship_where_sql(
				'rv.subject_guid',
				$options['relationship'],
				$options['relationship_guid'],
				$options['inverse_relationship']);
		if ($clauses) {
			$wheres = array_merge($wheres, $clauses['wheres']);
			$joins = array_merge($joins, $clauses['joins']);
		}
	}

	// see if any functions failed
	// remove empty strings on successful functions
	foreach ($wheres as $i => $where) {
		if ($where === FALSE) {
			return FALSE;
		} elseif (empty($where)) {
			unset($wheres[$i]);
		}
	}

	// remove identical where clauses
	$wheres = array_unique($wheres);

	if (!$options['count']) {
		$query = "SELECT DISTINCT rv.* FROM {$CONFIG->dbprefix}river rv ";
	} else {
		$query = "SELECT count(DISTINCT rv.id) as total FROM {$CONFIG->dbprefix}river rv ";
	}

	// add joins
	foreach ($joins as $j) {
		$query .= " $j ";
	}

	// add wheres
	$query .= ' WHERE ';

	foreach ($wheres as $w) {
		$query .= " $w AND ";
	}

	$query .= elgg_river_get_access_sql();

	if (!$options['count']) {
		$options['group_by'] = sanitise_string($options['group_by']);
		if ($options['group_by']) {
			$query .= " GROUP BY {$options['group_by']}";
		}

		$options['order_by'] = sanitise_string($options['order_by']);
		$query .= " ORDER BY {$options['order_by']}";

		if ($options['limit']) {
			$limit = sanitise_int($options['limit']);
			$offset = sanitise_int($options['offset'], false);
			$query .= " LIMIT $offset, $limit";
		}

		$river_items = get_data($query, 'elgg_row_to_elgg_river_item');
		_elgg_prefetch_river_entities($river_items);

		return $river_items;
	} else {
		$total = get_data_row($query);
		return (int)$total->total;
	}
}

/**
 * Prefetch entities that will be displayed in the river.
 *
 * @param ElggRiverItem[] $river_items
 * @access private
 */
function _elgg_prefetch_river_entities(array $river_items) {
	// prefetch objects and subjects
	$guids = array();
	foreach ($river_items as $item) {
		if ($item->subject_guid && !retrieve_cached_entity($item->subject_guid)) {
			$guids[$item->subject_guid] = true;
		}
		if ($item->object_guid && !retrieve_cached_entity($item->object_guid)) {
			$guids[$item->object_guid] = true;
		}
	}
	if ($guids) {
		// avoid creating oversized query
		// @todo how to better handle this?
		$guids = array_slice($guids, 0, 300, true);
		// return value unneeded, just priming cache
		elgg_get_entities(array(
			'guids' => array_keys($guids),
			'limit' => 0,
		));
	}

	// prefetch object containers
	$guids = array();
	foreach ($river_items as $item) {
		$object = $item->getObjectEntity();
		if ($object->container_guid && !retrieve_cached_entity($object->container_guid)) {
			$guids[$object->container_guid] = true;
		}
	}
	if ($guids) {
		$guids = array_slice($guids, 0, 300, true);
		elgg_get_entities(array(
			'guids' => array_keys($guids),
			'limit' => 0,
		));
	}
}

/**
 * List river items
 *
 * @param array $options Any options from elgg_get_river() plus:
 * 	 pagination => BOOL Display pagination links (true)
 *
 * @return string
 * @since 1.8.0
 */
function elgg_list_river(array $options = array()) {
	global $autofeed;
	$autofeed = true;

	$defaults = array(
		'offset'     => (int) max(get_input('offset', 0), 0),
		'limit'      => (int) max(get_input('limit', 20), 0),
		'pagination' => TRUE,
		'list_class' => 'elgg-list-river elgg-river', // @todo remove elgg-river in Elgg 1.9
	);

	$options = array_merge($defaults, $options);

	$options['count'] = TRUE;
	$count = elgg_get_river($options);

	$options['count'] = FALSE;
	$items = elgg_get_river($options);

	$options['count'] = $count;
	$options['items'] = $items;
	return elgg_view('page/components/list', $options);
}

/**
 * Get user's river news, if logged-in user then return her full river or specific news depending on current context(dashboard or
 * profile), otherwise just pageowner's specific news (profile).
 *
 * @param int $guid
 * @param string $what, 'all' or 'own', here own is adjective
 * @return array of int, river item ids
 */
function elgg_get_user_river_ids($guid, $what) {
	global $CONFIG;

	if ($guid > 0) {
		if ($what == 'all') {
			$rv_item_ids = get_data("SELECT river_item_id FROM {$CONFIG->dbprefix}river_peruser WHERE user_guid=$guid");
		}
		else if ($what = 'own') {
			$rv_item_ids = get_data("SELECT river_item_id FROM {$CONFIG->dbprefix}river_peruser WHERE user_guid=$guid AND isCreater=1");
		}
		else
			return null;
		return elgg_get_value_array_of_object_array($rv_item_ids);
	}

	return null;
}

/**
 * Get user's whole river, just a wrapper of elgg_list_river with pageowner river-item-ids passed in.
 * Owner: current user: dashboard  profile
 * 	  otheruser   : profile
 * Caution: Dont do this to groups, as all group members share the same river news which means non but super can delete a news
 *
 * @return string(river list view)
 */
function elgg_get_pageowner_river($what = 'own') {
	$pageowner = elgg_get_page_owner_guid();
	$entity = elgg_get_page_owner_entity();
	$logged_in = elgg_get_logged_in_user_guid();

	if ($pageowner != $logged_in && !elgg_instanceof($entity, 'group'))
		$what = 'own';

	$options = array();
	$options['ids'] = elgg_get_user_river_ids($pageowner, $what);

	if (!isset($options['ids']) || !is_array($options['ids']))
		return "";
	return elgg_list_river($options);
}

/**
 * Convert a database row to a new ElggRiverItem
 *
 * @param stdClass $row Database row from the river table
 *
 * @return ElggRiverItem
 * @since 1.8.0
 * @access private
 */
function elgg_row_to_elgg_river_item($row) {
	if (!($row instanceof stdClass)) {
		return NULL;
	}

	return new ElggRiverItem($row);
}

/**
 * Get the river's access where clause
 *
 * @return string
 * @since 1.8.0
 * @access private
 */
function elgg_river_get_access_sql() {
	// rewrite default access where clause to work with river table
	return str_replace("and enabled='yes'", '',
		str_replace('owner_guid', 'rv.subject_guid',
		str_replace('access_id', 'rv.access_id', get_access_sql_suffix())));
}

/**
 * Returns SQL where clause for type and subtype on river table
 *
 * @internal This is a simplified version of elgg_get_entity_type_subtype_where_sql()
 * which could be used for all queries once the subtypes have been denormalized.
 *
 * @param string     $table    'rv'
 * @param NULL|array $types    Array of types or NULL if none.
 * @param NULL|array $subtypes Array of subtypes or NULL if none
 * @param NULL|array $pairs    Array of pairs of types and subtypes
 *
 * @return string
 * @since 1.8.0
 * @access private
 */
function elgg_get_river_type_subtype_where_sql($table, $types, $subtypes, $pairs) {
	// short circuit if nothing is requested
	if (!$types && !$subtypes && !$pairs) {
		return '';
	}

	$types_wheres = array();
	$subtypes_wheres = array();

	// if no pairs, use types and subtypes
	if (!is_array($pairs)) {
		if ($types) {
			if (!is_array($types)) {
				$types = array($types);
			}
			foreach ($types as $type) {
				$type = sanitise_string($type);
				$types_wheres[] = "({$table}.type = '$type')";
			}
		}

		if ($subtypes) {
			if (!is_array($subtypes)) {
				$subtypes = array($subtypes);
			}
			foreach ($subtypes as $subtype) {
				$subtype = sanitise_string($subtype);
				$subtypes_wheres[] = "({$table}.subtype = '$subtype')";
			}
		}

		if (is_array($types_wheres) && count($types_wheres)) {
			$types_wheres = array(implode(' OR ', $types_wheres));
		}

		if (is_array($subtypes_wheres) && count($subtypes_wheres)) {
			$subtypes_wheres = array('(' . implode(' OR ', $subtypes_wheres) . ')');
		}

		$wheres = array(implode(' AND ', array_merge($types_wheres, $subtypes_wheres)));

	} else {
		// using type/subtype pairs
		foreach ($pairs as $paired_type => $paired_subtypes) {
			$paired_type = sanitise_string($paired_type);
			if (is_array($paired_subtypes)) {
				$paired_subtypes = array_map('sanitise_string', $paired_subtypes);
				$paired_subtype_str = implode("','", $paired_subtypes);
				if ($paired_subtype_str) {
					$wheres[] = "({$table}.type = '$paired_type'"
						. " AND {$table}.subtype IN ('$paired_subtype_str'))";
				}
			} else {
				$paired_subtype = sanitise_string($paired_subtypes);
				$wheres[] = "({$table}.type = '$paired_type'"
					. " AND {$table}.subtype = '$paired_subtype')";
			}
		}
	}

	if (is_array($wheres) && count($wheres)) {
		$where = implode(' OR ', $wheres);
		return "($where)";
	}

	return '';
}

/**
 * Get the where clause based on river action type strings
 *
 * @param array $types Array of action type strings
 *
 * @return string
 * @since 1.8.0
 * @access private
 */
function elgg_river_get_action_where_sql($types) {
	if (!$types) {
		return '';
	}

	if (!is_array($types)) {
		$types = sanitise_string($types);
		return "(rv.action_type = '$types')";
	}

	// sanitize types array
	$types_sanitized = array();
	foreach ($types as $type) {
		$types_sanitized[] = sanitise_string($type);
	}

	$type_str = implode("','", $types_sanitized);
	return "(rv.action_type IN ('$type_str'))";
}

/**
 * Get the where clause based on river view strings
 *
 * @param array $views Array of view strings
 *
 * @return string
 * @since 1.8.0
 * @access private
 */
function elgg_river_get_view_where_sql($views) {
	if (!$views) {
		return '';
	}

	if (!is_array($views)) {
		$views = sanitise_string($views);
		return "(rv.view = '$views')";
	}

	// sanitize views array
	$views_sanitized = array();
	foreach ($views as $view) {
		$views_sanitized[] = sanitise_string($view);
	}

	$view_str = implode("','", $views_sanitized);
	return "(rv.view IN ('$view_str'))";
}

/**
 * Sets the access ID on river items for a particular object
 *
 * @param int $object_guid The GUID of the entity
 * @param int $access_id   The access ID
 *
 * @return bool Depending on success
 */
function update_river_access_by_object($object_guid, $access_id) {
	// Sanitise
	$object_guid = (int) $object_guid;
	$access_id = (int) $access_id;

	// Load config
	global $CONFIG;

	// Remove
	$query = "update {$CONFIG->dbprefix}river
		set access_id = {$access_id}
		where object_guid = {$object_guid}";
	return update_data($query);
}

/**
 * Page handler for activiy
 *
 * @param array $page
 * @return bool
 * @access private
 */
function elgg_river_page_handler($page) {
	global $CONFIG;

	elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());

	// make a URL segment available in page handler script
	$page_type = elgg_extract(0, $page, 'all');
	$page_type = preg_replace('[\W]', '', $page_type);
	if ($page_type == 'owner') {
		$page_type = 'mine';
	}
	set_input('page_type', $page_type);

	// content filter code here
	$entity_type = '';
	$entity_subtype = '';

	require_once("{$CONFIG->path}pages/river.php");
	return true;
}

/**
 * Register river unit tests
 * @access private
 */
function elgg_river_test($hook, $type, $value) {
	global $CONFIG;
	$value[] = $CONFIG->path . 'engine/tests/api/river.php';
	return $value;
}

/**
 * Initialize river library
 * @access private
 */
function elgg_river_init() {
	elgg_register_page_handler('activity', 'elgg_river_page_handler');
	$item = new ElggMenuItem('activity', elgg_echo('activity'), 'activity');
	elgg_register_menu_item('site', $item);
	
	elgg_register_widget_type('river_widget', elgg_echo('river:widget:title'), elgg_echo('river:widget:description'));

	elgg_register_action('river/delete', '', 'admin');

	elgg_register_plugin_hook_handler('unit_test', 'system', 'elgg_river_test');

	elgg_register_event_handler('created', 'river', 'elgg_update_rivers');
}

elgg_register_event_handler('init', 'system', 'elgg_river_init');
