<?php

/*
 *
 * Project Name:  Pearl
 *
 * @package: pearl
 * @author Clifton Barron - SocialApparatus
 * @license Commercial
 * @copyright Copyright (c) 2012, Clifton Barron
 *
 * @link http://socia.us
 *
 */

echo elgg_view('page/elements/header_logo', $vars);

// drop-down login
echo elgg_view('core/account/login_dropdown');


// insert site-wide navigation
echo elgg_view_menu('site');