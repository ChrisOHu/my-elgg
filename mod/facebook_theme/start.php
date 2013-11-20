<?php

function facebook_theme_init() {
	elgg_register_plugin_hook_handler('index', 'system', 'facebook_theme_index_handler');
	elgg_register_page_handler('profile', 'facebook_theme_profile_page_handler');
	elgg_register_page_handler('dashboard', 'facebook_theme_dashboard_handler');
}

function facebook_theme_load() {
	static $loaded = false;

	if ($loaded == true)
		return $loaded;

	//register & load library
	elgg_register_library('elgg:facebook_theme', elgg_get_plugins_path() . 'facebook_theme/lib/lib.php');
	elgg_load_library('elgg:facebook_theme');

	//What a hack!  Overriding groups page handler without blowing away other plugins doing the same
	global $CONFIG, $facebook_theme_original_groups_page_handler;
	$facebook_theme_original_groups_page_handler = $CONFIG->pagehandler['groups'];
	elgg_register_page_handler('groups', 'facebook_theme_groups_page_handler');
	
	elgg_register_ajax_view('thewire/composer');
	elgg_register_ajax_view('messageboard/composer');
	elgg_register_ajax_view('blog/composer');
	elgg_register_ajax_view('file/composer');
	elgg_register_ajax_view('bookmarks/composer');
	
	/**
	 * Customize menus
	 */
	elgg_unregister_plugin_hook_handler('register', 'menu:river', 'likes_river_menu_setup');
	elgg_unregister_plugin_hook_handler('register', 'menu:river', 'elgg_river_menu_setup');
	
	elgg_register_plugin_hook_handler('register', 'menu:river', 'facebook_theme_river_menu_handler');
	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'facebook_theme_owner_block_menu_handler', 600);
	elgg_register_plugin_hook_handler('register', 'menu:composer', 'facebook_theme_composer_menu_handler');
	elgg_register_plugin_hook_handler('register', 'menu:river-categories', 'facebook_theme_river_categories_menu_handler');
	
	elgg_register_event_handler('pagesetup', 'system', 'facebook_theme_pagesetup_handler', 1000);
	
	/**
	 * Customize permissions
	 */
	elgg_register_plugin_hook_handler('permissions_check:annotate', 'all', 'facebook_theme_annotation_permissions_handler');
	elgg_register_plugin_hook_handler('container_permissions_check', 'all', 'facebook_theme_container_permissions_handler');
	elgg_register_plugin_hook_handler('access:collections:write', 'user', 'facebook_theme_write_access_collections_handler');
	
	/**
	 * Miscellaneous customizations
	 */
	//Small "correction" to groups profile -- brief description makes more sense to come first!
	elgg_register_plugin_hook_handler('profile:fields', 'group', 'facebook_theme_group_profile_fields', 1);
		
	//@todo report some of the extra patterns to be included in Elgg core
	elgg_extend_view('css/elgg', 'facebook_theme/css');
	elgg_extend_view('js/elgg', 'js/topbar');
	
	//Likes summary bar -- "You, John, and 3 others like this"
	if (elgg_is_active_plugin('likes')) {
		elgg_extend_view('river/elements/responses', 'likes/river_footer', 1);
	}
	
	elgg_extend_view('river/elements/responses', 'discussion/river_footer');
	
	//Elgg only includes the search bar in the header by default,
	//but we usually don't show the header when the user is logged in
	if (elgg_is_active_plugin('search')) {
		elgg_extend_view('page/elements/topbar', 'search/search_box');
		elgg_unextend_view('page/elements/header', 'search/search_box');
		
		if (!elgg_is_logged_in()) {
			elgg_unextend_view('page/elements/header', 'search/header');
		}
	}

	//not a good idea to cache themes, 'cause of themes' multifiles structure.
	$themes_url = 'mod/facebook_theme/vendors/jquery/themes/smoothness/jquery.ui.all';
	elgg_register_css('themes/smoothness', $themes_url);
	elgg_load_css('themes/smoothness');
	
	$facebook_theme_river_js = elgg_get_simplecache_url('js', 'facebook_theme.river');
	elgg_register_simplecache_view('js/facebook_theme.river');
	elgg_register_js('elgg.facebook_theme.river', $facebook_theme_river_js, 'footer');
	
	$facebook_theme_ui_js = elgg_get_simplecache_url('js', 'facebook_theme.ui');
	elgg_register_simplecache_view('js/facebook_theme.ui');
	elgg_register_js('elgg.facebook_theme.ui', $facebook_theme_ui_js);
	elgg_load_js("elgg.facebook_theme.ui");

	//register actions
	$actions_base = elgg_get_plugins_path() . 'facebook_theme/actions';
	elgg_register_action('river/delete_user_river_item', "$actions_base/river/delete_user_river_item.php");

	$loaded = true;
	return $loaded;
}

function facebook_theme_groups_page_handler($segments, $handle) {
	$pages_dir = dirname(__FILE__) . '/pages';

	switch ($segments[0]) {
		case 'profile':
			elgg_set_page_owner_guid($segments[1]);
			require_once "$pages_dir/groups/wall.php";
			break;
			
		case 'info':
			elgg_set_page_owner_guid($segments[1]);
			require_once "$pages_dir/groups/info.php";
			break;
			
		case 'discussion':
			elgg_set_page_owner_guid($segments[1]);
			require_once "$pages_dir/groups/discussion.php";
			break;
			
		default:
			global $facebook_theme_original_groups_page_handler;
			return call_user_func($facebook_theme_original_groups_page_handler, $segments, $handle);
	}
	return true;
}

function facebook_theme_pagesetup_handler() {
	//register page

	$owner = elgg_get_page_owner_entity();

	if (elgg_is_logged_in()) {
		$user = elgg_get_logged_in_user_entity();
		
		//facebook_theme_register_page_omenu($user, $owner);
		facebook_theme_register_page_menu($user, $owner);
		facebook_theme_register_friends_page_menu($user, $owner);
		facebook_theme_register_extras_menu($user, $owner);
		facebook_theme_register_user_profile_menu($user, $owner);
		facebook_theme_register_topbar_menu($user, $owner);
		facebook_theme_register_group_page_menu($user, $owner);
	}
}

function facebook_theme_dashboard_handler() {
	facebook_theme_load();
	require_once dirname(__FILE__) . '/pages/dashboard.php';
	return true;
}

function facebook_theme_index_handler() {
	facebook_theme_load();
	if (elgg_is_logged_in()) {
		forward('/dashboard');
	}
}

function facebook_theme_container_permissions_handler($hook, $type, $result, $params) {
	$container = $params['container'];
	$subtype = $params['subtype'];
	
	if ($container instanceof ElggGroup) {
		if ($subtype == 'thewire') {
			return false;
		}
	}
}

function facebook_theme_annotation_permissions_handler($hook, $type, $result, $params) {
	$entity = $params['entity'];
	$user = $params['user'];
	$annotation_name = $params['annotation_name'];
	
	//Users should not be able to post on their own message board
	if ($annotation_name == 'messageboard' && $user->guid == $entity->guid) {
		return false;
	}
	
	//No "commenting" on users, must use messageboard
	if ($annotation_name == 'generic_comment' && $entity instanceof ElggUser) {
		return false;
	}
	
	//No "commenting" on forum topics, must use special "reply" annotation
	if ($annotation_name == 'generic_comment' && elgg_instanceof($entity, 'object', 'groupforumtopic')) {
		return false;
	}
	
	//Definitely should be able to "like" a forum topic!
	if ($annotation_name == 'likes' && elgg_instanceof($entity, 'object', 'groupforumtopic')) {
		return true;
	}
	
	if ($annotation_name == 'group_topic_post' && !elgg_instanceof($entity, 'object', 'groupforumtopic')) {
		return false;
	}
}

/**
 * When user creates something, forbid him/her the options to write to public or loggedin-nonfriend-users' collections/river.
 *
 * @todo Unit test.
 */
function facebook_theme_write_access_collections_handler($hook, $type, $access_array, $params)  {
	unset($access_array[ACCESS_PUBLIC]);
	unset($access_array[ACCESS_LOGGED_IN]);
	return $access_array;
}
/**
 * Adds menu items to the "composer" at the top of the "wall".  Need to also add
 * the forms that these items point to.
 * 
 * @todo Get the composer concept integrated into core
 */
function facebook_theme_composer_menu_handler($hook, $type, $items, $params) {
	$entity = $params['entity'];
	
	if (elgg_is_active_plugin('thewire') && $entity->canWriteToContainer(0, 'object', 'thewire')) {
		$items[] = ElggMenuItem::factory(array(
			'name' => 'thewire',
			'href' => "/ajax/view/thewire/composer?container_guid=$entity->guid",
			'text' => elgg_view_icon('share') . elgg_echo("composer:object:thewire"),
			'priority' => 100,
		));
		
		//trigger any javascript loads that we might need
		elgg_view('thewire/composer');
	}
	
	if (elgg_is_active_plugin('messageboard') && $entity->canAnnotate(0, 'messageboard')) {
		$items[] = ElggMenuItem::factory(array(
			'name' => 'messageboard',
			'href' => "/ajax/view/messageboard/composer?entity_guid=$entity->guid",
			'text' => elgg_view_icon('speech-bubble-alt') . elgg_echo("composer:annotation:messageboard"),
			'priority' => 200,
		));
		
		//trigger any javascript loads that we might need
		elgg_view('messageboard/composer');
	}
	
	if (elgg_is_active_plugin('bookmarks') && $entity->canWriteToContainer(0, 'object', 'bookmarks')) {
		$items[] = ElggMenuItem::factory(array(
			'name' => 'bookmarks',
			'href' => "/ajax/view/bookmarks/composer?container_guid=$entity->guid",
			'text' => elgg_view_icon('push-pin') . elgg_echo("composer:object:bookmarks"),
			'priority' => 300,
		));
		
		//trigger any javascript loads that we might need
		elgg_view('bookmarks/composer');
	}
	
	if (elgg_is_active_plugin('blog') && $entity->canWriteToContainer(0, 'object', 'blog')) {
		$items[] = ElggMenuItem::factory(array(
			'name' => 'blog',
			'href' => "/ajax/view/blog/composer?container_guid=$entity->guid",
			'text' => elgg_view_icon('speech-bubble') . elgg_echo("composer:object:blog"),
			'priority' => 600,
		));
		
		//trigger any javascript loads that we might need
		elgg_view('blog/composer');
	}
	
	if (elgg_is_active_plugin('file') && $entity->canWriteToContainer(0, 'object', 'file')) {
		$items[] = ElggMenuItem::factory(array(
			'name' => 'file',
			'href' => "/ajax/view/file/composer?container_guid=$entity->guid",
			'text' => elgg_view_icon('clip') . elgg_echo("composer:object:file"),
			'priority' => 700,
		));
		
		//trigger any javascript loads that we might need
		elgg_view('file/composer');
	}
	
	return $items;
}

function facebook_theme_group_profile_fields($hook, $type, $fields, $params) {
	return array(
		'briefdescription' => 'text',
		'description' => 'longtext',
		'interests' => 'tags',
	);
}

function facebook_theme_owner_block_menu_handler($hook, $type, $items, $params) {
	$owner = elgg_get_page_owner_entity();
	
	if ($owner instanceof ElggGroup) {
		$items['info'] = ElggMenuItem::factory(array(
			'name' => 'info', 
			'text' => elgg_view_icon('info') . elgg_echo('profile:info'), 
			'href' => "/groups/info/$owner->guid/" . elgg_get_friendly_title($owner->name),
			'priority' => 2,
		));
		
		$items['profile'] = ElggMenuItem::factory(array(
			'name' => 'profile',
			'text' => elgg_view_icon('speech-bubble') . elgg_echo('profile:wall'),
			'href' => "/groups/profile/$owner->guid/" . elgg_get_friendly_title($owner->name),
			'priority' => 1,
		));
	}
	
	if ($owner instanceof ElggUser) {
		$items['info'] = ElggMenuItem::factory(array(
			'name' => 'info', 
			'text' => elgg_view_icon('info') . elgg_echo('profile:info'), 
			'href' => "/profile/$owner->username/info",
			'priority' => 2,
		));
		
		$items['profile'] = ElggMenuItem::factory(array(
			'name' => 'profile',
			'text' => elgg_echo('profile:wall'),
			'href' => "/profile/$owner->username",
			'priority' => 1,
		));
		
		$items['friends'] = ElggMenuItem::factory(array(
			'name' => 'friends',	
			'text' => elgg_view_icon('users') . elgg_echo('friends'),
			'href' => "/friends/$owner->username"
		));
	}
	
	$top_level_pages = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'page_top',
		'container_guid' => $owner->guid,
		'limit' => 0,
	));
	
	foreach ($top_level_pages as $page) {
		$items["pages-$page->guid"] = ElggMenuItem::factory(array(
			'name' => "pages-$page->guid",
			'href' => $page->getURL(),
			'text' => elgg_view_icon('page') . elgg_view('output/text', array('value' => $page->title)),
		));
	}
	
	return $items;
	
}

function facebook_theme_river_menu_handler($hook, $type, $items, $params) {
	$item = $params['item'];
	$owner_guid = elgg_get_page_owner_guid();
	$logged_in_guid = elgg_get_logged_in_user_guid();

	$object = $item->getObjectEntity();
	if (!elgg_in_context('widgets') && !($item instanceof ElggAnnotation) /*!$item->annotation_id*/ && $object instanceof ElggEntity) {
		
		if (elgg_is_active_plugin('likes') && $object->canAnnotate(0, 'likes')) {
			if (!elgg_annotation_exists($object->getGUID(), 'likes')) {
				// user has not liked this yet
				$options = array(
					'name' => 'like',
					'href' => "action/likes/add?guid={$object->guid}",
					'title' => elgg_echo('like this'),
					'text' => elgg_view_icon('thumbs-up'),//elgg_echo('likes:likethis'),
					'class' => "elgg-likes-submit-add",
					'is_action' => true,
					'priority' => 100,
				);
			} else {
				// user has liked this
				$options = array(
					'name' => 'like',
					'href' => "action/likes/delete?guid={$object->guid}",
					'title' => elgg_echo('no more like this'),
					'text' => elgg_view_icon('thumbs-down'),//elgg_echo('likes:remove'),
					'class' => "elgg-likes-submit-delete",
					'is_action' => true,
					'priority' => 100,
				);
			}
			
			$items[] = ElggMenuItem::factory($options);
		}
		
		if ($object->canAnnotate(0, 'generic_comment')) {
			$items[] = ElggMenuItem::factory(array(
				'name' => 'comment',
				'href' => "#comments-add-$object->guid",
				'text' => elgg_view_icon('speech-bubble'),//elgg_echo('comment'),
				'title' => elgg_echo('comment:this'),
				'rel' => "toggle",
				'priority' => 50,
			));
		}
		
		if ($object instanceof ElggUser && !$object->isFriend()) {
			$items[] = ElggMenuItem::factory(array(
				'name' => 'addfriend',
				'href' => "/action/friends/add?friend=$object->guid",
				'title' => elgg_echo('add friend'),
				'text' => elgg_view_icon('round-plus'),//elgg_echo('friend:user:add', array($object->name)),
				'is_action' => TRUE,
			));
		}
		
		if (elgg_instanceof($object, 'object', 'groupforumtopic')) {
			$items[] = ElggMenuItem::factory(array(
				'name' => 'reply',
				'href' => "#groups-reply-$object->guid",
				'title' => elgg_echo('reply:this'),
				'text' => elgg_echo('reply'),
			));
		}

		if ($owner_guid == $logged_in_guid && $item instanceof ElggRiverItem/*elgg_instanceof($item, 'river', 'item')*/ && $item->id) {
			$items[] = ElggMenuItem::factory(array(
				'name' => 'delete',
				'href' => "/action/river/delete_user_river_item?id=$item->id",
				'title' => elgg_echo('delete this'),
				'text' => elgg_view_icon('trash'),//elgg_echo('delete'),
				'class' => "elgg-river-item-delete",
				'is_action' => TRUE,
			));
		}
	} 
	return $items;
}

function facebook_theme_river_categories_menu_handler($hook, $type, $items, $params) {
	if (!is_array($items) || !$items) {
		$items = array();
	}

	$items[] = ElggMenuItem::factory(array(
		'name' => 'All',
		'href' => "",
		'text' => elgg_view_icon('share') . elgg_echo("river-categories:all"),
		'priority' => 1,
		'class' => 'elgg-river-all',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Voices',
		'href' => "",
		'text' => elgg_view_icon('speech-bubble') . elgg_echo("river-categories:voices"),
		'priority' => 2,
		'class' => 'elgg-river-voices',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Blogs',
		'href' => "",
		'text' => elgg_view_icon('eye') . elgg_echo("river-categories:blogs"),
		'priority' => 3,
		'class' => 'elgg-river-blogs',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Galleries',
		'href' => "",
		'text' => elgg_view_icon('photo') . elgg_echo("river-categories:galleries"),
		'priority' => 4,
		'class' => 'elgg-river-galleries',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Videos',
		'href' => "",
		'text' => elgg_view_icon('video') . elgg_echo("river-categories:videos"),
		'priority' => 5,
		'class' => 'elgg-river-videos',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Musics',
		'href' => "",
		'text' => elgg_view_icon('star') . elgg_echo("river-categories:musics"),
		'priority' => 6,
		'class' => 'elgg-river-musics',
	));

	$items[] = ElggMenuItem::factory(array(
		'name' => 'Webmarks',
		'href' => "",
		'text' => elgg_view_icon('push-pin') . elgg_echo("river-categories:webmarks"),
		'priority' => 7,
		'class' => 'elgg-river-webmarks',
	));

	return $items;
}

/**
 * Profile page handler
 *
 * @param array $page Array of page elements, forwarded by the page handling mechanism
 */
function facebook_theme_profile_page_handler($page) {
	facebook_theme_load();

	if (isset($page[0])) {
		$username = $page[0];
		$user = get_user_by_username($username);
		elgg_set_page_owner_guid($user->guid);
	}

	// short circuit if invalid or banned username
	if (!$user || ($user->isBanned() && !elgg_is_admin_logged_in())) {
		register_error(elgg_echo('profile:notfound'));
		forward();
	}

	$action = NULL;
	if (isset($page[1])) {
		$action = $page[1];
	}

	switch ($action) {
		case 'edit':
			// use for the core profile edit page
			global $CONFIG;
			require $CONFIG->path . 'pages/profile/edit.php';
			break;
		
		case 'info':
			require dirname(__FILE__) . '/pages/profile/info.php';
			break;
			
		case 'wall':
			require dirname(__FILE__) . '/pages/profile/wall.php';
			break;
			
		default:
			if (elgg_is_logged_in()) {
				require dirname(__FILE__) . '/pages/profile/wall.php';
			} else {
				require dirname(__FILE__) . '/pages/profile/info.php';
			}
			break;
	}
	
	return true;
}

function facebook_theme_register_page_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner)
		return false;

	/**	
	 * New page menu, currently just a skull 
	 *   Circles
	 *    My Friends
	 *    My groups
	 *    Following
	 *    Followers
	 *
	 *   Activities
	 *    Compose Blogs
	 *    Share Photos
	 *    Share Musics
	 *    Share Videos
	 *    Share Links
	 *
	 *   Miscellaneous
	 *    ...
	 */
/*	//news, section priority: p1
	elgg_register_menu_item('page', array(
		'name' => 'river-de-all',
		'section' => 'p1riverde',
		'text' => elgg_view_icon('share') . elgg_echo('river-de-all'),
		'href' => '/dashboard',
		'priority' => 100,
	));

	elgg_register_menu_item('page', array(
		'name' => 'river-de-me',
		'section' => 'p1riverde',
		'text' => elgg_view_icon('home') . elgg_echo('river-de-me'),
		'href' => '/dashboard',
		'priority' => 150,
	));

	elgg_register_menu_item('page', array(
		'name' => 'river-de-friends',
		'section' => 'p1riverde',
		'text' => elgg_echo('river-de-friends'),
		'href' => '',
		'priority' => 200,
	));

	elgg_register_menu_item('page', array(
		'name' => 'river-de-groups',
		'section' => 'p1riverde',
		'text' => elgg_echo('river-de-groups'),
		'href' => '',
		'priority' => 300,
	));

	elgg_register_menu_item('page', array(
		'name' => 'river-de-followings',
		'section' => 'p1riverde',
		'text' => elgg_echo('river-de-followings'),
		'href' => '',
		'priority' => 400,
	));
 */

	//circles, section priority: p4
	elgg_register_menu_item('page', array(
		'name' => 'circle-world',
		'section' => 'p4circles',
		'text' => elgg_echo('circle-world'),//img_icon('main', 'nav'),
		'href' => "",
		'priority' => 400,
	));

	elgg_register_menu_item('page', array(
		'name' => 'circle-friends',
		'section' => 'p4circles',
		'text' => elgg_echo('circle-friends'),
		'href' => "/friends/$user->username",
		'priority' => 500,
	));
	
	elgg_register_menu_item('page', array(
		'name' => 'circle-groups',
		'section' => 'p4circles',
		'text' => elgg_echo('circle-groups'),
		'href' => "",
		'priority' => 600,
	));

	elgg_register_menu_item('page', array(
		'name' => 'circle-following',
		'section' => 'p4circles',
		'text' => elgg_echo('circle-followings'),
		'href' => "",
		'priority' => 700,
	));
	
	elgg_register_menu_item('page', array(
		'name' => 'circle-followers',
		'section' => 'p4circles',
		'text' => elgg_echo('circle-followers'),
		'href' => "",
		'priority' => 800,
	));

	//activities, section priority: p7	
	elgg_register_menu_item('page', array(
		'name' => 'compose-blog',
		'section' => 'p7activities',
		'text' => elgg_echo('compose-blog'),
		'href' => "",
		'priority' => 900,
	));

	elgg_register_menu_item('page', array(
		'name' => 'share-photos',
		'section' => 'p7activities',
		'text' => elgg_echo('share-photos'),
		'href' => "",
		'priority' => 1000,
	));

	elgg_register_menu_item('page', array(
		'name' => 'share-musics',
		'section' => 'p7activities',
		'text' => elgg_echo('share-musics'),
		'href' => "",
		'priority' => 1100,
	));

	elgg_register_menu_item('page', array(
		'name' => 'share-videos',
		'section' => 'p7activities',
		'text' => elgg_echo('share-videos'),
		'href' => "",
		'priority' => 1200,
	));

	elgg_register_menu_item('page', array(
		'name' => 'share-links',
		'section' => 'p7activities',
		'text' => elgg_echo('share-links'),
		'href' => "",
		'priority' => 1300,
	));

}

function facebook_theme_register_page_omenu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner)
		return false;

	elgg_register_menu_item('page', array(
		'name' => 'news',
		'text' => elgg_echo('newsfeed'),
		'href' => '/dashboard',
		'priority' => 100,
		'contexts' => array('dashboard'),
	));
		
	if (elgg_is_active_plugin('messages')) {
		elgg_register_menu_item('page', array(
				'name' => 'messages',
				'text' => elgg_view_icon('mail') . elgg_echo('messages'),
				'href' => "/messages/inbox/$user->username",
				'contexts' => array('dashboard'),
			));
	}
		
	elgg_register_menu_item('page', array(
		'name' => 'friends',
		'text' => elgg_view_icon('users') . elgg_echo('friends'),
		'href' => "/friends/$user->username",
		'priority' => 500,
		'contexts' => array('dashboard'),
	));
	
	if (elgg_is_active_plugin('groups')) {
		$groups = $user->getGroups('', 4);
			
		foreach ($groups as $group) {
			elgg_register_menu_item('page', array(
				'section' => 'groups',
				'name' => "group-$group->guid",
				'text' => elgg_view_icon('users') . $group->name,
				'href' => $group->getURL(),
				'contexts' => array('dashboard'),
			));
		}
			
		elgg_register_menu_item('page', array(
			'name' => 'groups-add',
			'section' => 'groups',
			'text' => elgg_echo('groups:add'),
			'href' => "/groups/add",
			'contexts' => array('dashboard'),
			'priority' => 499,
		));
			
		elgg_register_menu_item('page', array(
			'section' => 'groups',
			'name' => 'groups',
			'text' => elgg_echo('see:all'),
			'href' => "/groups/member/$user->username",
			'contexts' => array('dashboard'),
			'priority' => 500,
		));
	}
		
	if (elgg_is_active_plugin('tidypics')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',	
			'name' => 'photos',
			'text' => elgg_view_icon('photo') . elgg_echo("photos"),
			'href' => "/photos/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}
		
	if (elgg_is_active_plugin('bookmarks')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',
			'name' => 'bookmarks',
			'text' => elgg_view_icon('link') . elgg_echo('bookmarks'),	
			'href' => "/bookmarks/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}
		
	if (elgg_is_active_plugin('blog')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',	
			'name' => 'blog',
			'text' => elgg_view_icon('speech-bubble-alt') . elgg_echo('blog'),
			'href' => "/blog/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}
		
	if (elgg_is_active_plugin('pages')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',	
			'name' => 'pages',
			'text' => elgg_view_icon('list') . elgg_echo('pages'),
			'href' => "/pages/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}
		
	if (elgg_is_active_plugin('file')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',	
			'name' => 'files',
			'text' => elgg_view_icon('clip') . elgg_echo('files'),
			'href' => "/file/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}

	if (elgg_is_active_plugin('thewire')) {
		elgg_register_menu_item('page', array(
			'section' => 'more',
			'name' => 'thewire',
			'text' => elgg_view_icon('speech-bubble') . elgg_echo('Wire'),
			'href' => "/thewire/friends/$user->username",
			'contexts' => array('dashboard'),
		));
	}
}

function facebook_theme_register_friends_page_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner || !($owner instanceof ElggUser))
		return false;
	if ($owner->guid != $user->guid) {
	
		if (check_entity_relationship($user->guid, 'friend', $owner->guid)) {
			elgg_register_menu_item('extras', array(
				'name' => 'removefriend',
				'text' => elgg_echo('friend:remove'),
				'href' => "/action/friends/remove?friend=$owner->guid",
				'is_action' => TRUE,
				'contexts' => array('profile'),
			));
		} else {
			elgg_register_menu_item('title', array(
				'name' => 'addfriend',
				'text' => elgg_view_icon('users') . elgg_echo('friend:add'),
				'href' => "/action/friends/add?friend=$owner->guid",
				'is_action' => TRUE,
				'link_class' => 'elgg-button elgg-button-special',
				'contexts' => array('profile'),
				'priority' => 1,
			));
		}
			
		if (elgg_is_active_plugin('messages')) {
			elgg_register_menu_item('title', array(
				'name' => 'message',
				'text' => elgg_view_icon('speech-bubble-alt') . elgg_echo('messages:message'),
				'href' => "/messages/compose?send_to=$owner->guid",
				'link_class' => 'elgg-button elgg-button-action',
				'contexts' => array('profile'),
			));
		}
	}
}

function facebook_theme_register_extras_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser))
		return false;

	$address = urlencode(current_page_url());
		
	if (elgg_is_active_plugin('bookmarks')) {
		elgg_register_menu_item('extras', array(
			'name' => 'bookmark',
			'text' => elgg_view_icon('link') . elgg_echo('bookmarks:this'),
			'href' => "bookmarks/add/$user->guid?address=$address",
			'title' => elgg_echo('bookmarks:this'),
			'rel' => 'nofollow',
		));
	}
		
	if (elgg_is_active_plugin('reportedcontent')) {
		elgg_unregister_menu_item('footer', 'report_this');
		
		$href = "javascript:elgg.forward('reportedcontent/add'";
		$href .= "+'?address='+encodeURIComponent(location.href)";
		$href .= "+'&title='+encodeURIComponent(document.title));";
				
		elgg_register_menu_item('extras', array(
			'name' => 'report_this',
			'href' => $href,
			'text' => elgg_view_icon('report-this') . elgg_echo('reportedcontent:this'),
			'title' => elgg_echo('reportedcontent:this:tooltip'),
			'priority' => 500,
		));
	}

	elgg_register_menu_item('extras', array(
		'name' => 'rss',
		'text' => elgg_view_icon('rss') . elgg_echo("rss:subscribe"),
		'href' => '?view=rss',
	));
}

function facebook_theme_register_user_profile_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner || !($owner instanceof ElggUser))
		return false;

	if ($owner->guid == $user->guid) {
		elgg_register_menu_item('title', array(
			'name' => 'editprofile',
			'href' => "/profile/$user->username/edit",
			'text' => elgg_echo('profile:edit'),
			'link_class' => 'elgg-button elgg-button-action',
			'contexts' => array('profile'),
		));
	}
}

function facebook_theme_register_topbar_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner || !($owner instanceof ElggEntity))
		return false;

	/**
	* TOPBAR customizations
	*/
	//Want our logo present, not Elgg's

	$site = elgg_get_site_entity();
	elgg_unregister_menu_item('topbar', 'elgg_logo');
	elgg_register_menu_item('topbar', array(
		'href' => '/',
		'name' => 'logo',
		'priority' => 1,
		'section' => 'alt',
		'text' => "<h1 id=\"facebook-topbar-logo\">$site->name</h1>",
	));

/*	elgg_register_menu_item('topbar', array(
		'href' => '/dashboard',
		'name' => 'home',
		'priority' => 1,
		//'section' => 'alt',
		'text' => elgg_echo('home'),
	));
 */
		
	if (elgg_is_active_plugin('profile')) {
		elgg_unregister_menu_item('topbar', 'profile');
		elgg_register_menu_item('topbar', array(
			'name' => 'profile',
			//'section' => 'alt',
			'text' => "<img src=\"{$user->getIconURL('topbar')}\" class=\"elgg-icon elgg-inline-block\" alt=\"$user->name\"/>" . $user->name,
			'href' => "/profile/$user->username",
			'priority' => 2,
		));
	}
		
	elgg_register_menu_item('topbar', array(
		'href' => "#",
		'name' => 'account',
		'priority' => 3,
		//'section' => 'alt',
		'text' => '',
	));

	elgg_unregister_menu_item('topbar', 'usersettings');
	elgg_register_menu_item('topbar', array(
		'href' => "/settings/user/$user->username",
		'name' => 'usersettings',
		'parent_name' => 'account',
		//'section' => 'alt',
		'text' => elgg_echo('settings:user'),
	));

	elgg_unregister_menu_item('topbar', 'administration');
	elgg_register_menu_item('topbar', array(
		'href' => '/admin',
		'name' => 'administration',
		'parent_name' => 'account',
		//'section' => 'alt',
		'text' => elgg_echo('admin'),
	));
		
	if (elgg_is_active_plugin('notifications')) {
		elgg_register_menu_item('topbar', array(
			'href' => "/notifications/personal",
			'name' => 'notifications',
			'parent_name' => 'account',
			//'section' => 'alt',
			'text' => elgg_echo('notifications:personal'),
		));
	}
		
	elgg_unregister_menu_item('topbar', 'logout');
	elgg_register_menu_item('topbar', array(
		'href' => '/action/logout',
		'is_action' => TRUE,
		'name' => 'logout',
		'parent_name' => 'account',
		'priority' => 1000, //want this to be at the bottom of the list no matter what
		//'section' => 'alt',
		'text' => elgg_echo('logout'),
	));

	elgg_unregister_menu_item('topbar', 'friends');
}

function facebook_theme_register_group_page_menu($user, $owner) {
	if (!$user || !($user instanceof ElggUser) || !$owner || !($owner instanceof ElggGroup))
		return false;
}

elgg_register_event_handler('init', 'system', 'facebook_theme_init');
