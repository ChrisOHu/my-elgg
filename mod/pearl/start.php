<?php
/*
 *
 * Elgg Pearl
 *
 * @package pearl
 * @author Clifton Barron - SocialApparatus
 * @license Commercial
 * @copyright Copyright (c) 2012, Clifton Barron
 *
 * @link http://socia.us
 *
 */

elgg_register_event_handler('init', 'system', 'pearl_init');

function pearl_init() {
    global $CONFIG;
    if (elgg_get_context() != 'admin') {
        elgg_register_css('pearl', $CONFIG->url . 'mod/pearl/css/pearl.css');
        elgg_register_css('ubuntu', 'http://fonts.googleapis.com/css?family=Ubuntu:400,300,300italic,400italic,500,500italic,700,700italic');
        elgg_register_css('oleo','http://fonts.googleapis.com/css?family=Oleo+Script+Swash+Caps:700');
        elgg_register_css('nivo-default', $CONFIG->url . 'mod/pearl/css/default.css');
        elgg_register_css('nivo', $CONFIG->url . 'mod/pearl/css/nivo-slider.css');
        elgg_load_css('pearl');
        elgg_load_css('ubuntu');
        elgg_load_css('oleo');
    } else {
        elgg_unregister_css('pearl');
        elgg_unregister_css('ubuntu');
        elgg_unregister_css('oleo');
    }
    elgg_register_js('hoverintent', $CONFIG->url . 'mod/pearl/js/jquery.hoverIntent.minified.js');
    elgg_register_js('nivo', $CONFIG->url . 'mod/pearl/js/jquery.nivo.slider.js');
    elgg_extend_view('page/elements/header', 'pearl/usermenu', 0);
    elgg_unregister_js('jquery');
    elgg_unregister_js('jquery-ui');
    elgg_register_css('jquery-ui', 'http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css');
    elgg_load_css('jquery-ui');
    elgg_register_js('jquery', 'http://code.jquery.com/jquery-1.8.3.js', 'head');
    elgg_load_js('jquery');
    elgg_register_js('jquery-ui', 'http://code.jquery.com/ui/1.9.2/jquery-ui.js', 'head');
    elgg_load_js('jquery-ui');
}

?>
