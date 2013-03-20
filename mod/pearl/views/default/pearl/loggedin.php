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

global $CONFIG;
$messages = messages_count_unread();
$user = elgg_get_logged_in_user_entity();
$icon = elgg_view_entity_icon($user, 'medium', array(
    'use_hover' => false,
    'use_link' => false,
        ));
$logout = elgg_add_action_tokens_to_url($CONFIG->url . 'action/logout');
echo "<div class='user_menu_avatar'>";
echo $icon;
echo "</div>";
?>
<div class="user_menu_icons">
    <span class="your_dashboard pearl_button">
        <span class="user_menu_tooltip">Your&nbsp;Dashboard</span>
    </span>
    <span class="your_profile pearl_button">
        <span class="user_menu_tooltip">Your&nbsp;Profile</span>
    </span>
    <span class="your_inbox pearl_button">
        <span class="user_menu_tooltip">Your&nbsp;Inbox</span>
    </span>
    <span class="your_friends pearl_button">
        <span class="user_menu_tooltip">Your&nbsp;Friends</span>
    </span>
    <div style="clear:left;">
    </div>
    <span class="your_settings pearl_button">
        <span class="user_menu_tooltip">Your&nbsp;Settings</span>
    </span>
    <span class="edit_profile pearl_button">
        <span class="user_menu_tooltip">Edit&nbsp;Profile</span>

    </span>
    <span class="edit_avatar pearl_button">
        <span class="user_menu_tooltip">Edit&nbsp;Avatar</span>

    </span>
    <span class="logout pearl_button">
        <span class="user_menu_tooltip">Logout</span>
    </span>
</div>
<script>
    $(document).ready(function() {
        var messages = "<?php echo $messages; ?>";
        var username = "<?php echo $user->username; ?>";
        var url = "<?php echo $CONFIG->url; ?>";
        var logout = "<?php echo $logout; ?>";
        if (messages > 0) {
            $('.your_inbox').css('background-color','#80D5A7');
            $('.your_inbox').css('background-position','-120px 0px');
        }
        $(".your_dashboard").live('click', function() {
            window.location = url + 'dashboard';
        });
        $(".your_profile").live('click', function() {
            window.location = url + 'profile/' + username;
        });
        $(".your_inbox").live('click', function() {
            window.location = url + 'messages/inbox/' + username;
        });
        $(".your_friends").live('click', function() {
            window.location = url + 'friends/' + username;
        });
        $(".your_settings").live('click', function() {
            window.location = url + 'settings/user/' + username;
        });
        $(".edit_profile").live('click', function() {
            window.location = url + 'profile/' + username + '/edit';
        });
        $(".edit_avatar").live('click', function() {
            window.location = url + 'avatar/edit/' + username;
        });
        $(".logout").live('click', function() {
            window.location = logout;
        });
        $(".pearl_button").hoverIntent({
            over: tooltip_show,
            timeout: 500,
            out: tooltip_hide,
            interval: 500
        });
        function tooltip_show() {
            $(this).find('.user_menu_tooltip').fadeIn('slow');
            var width = $(this).find('.user_menu_tooltip').width();
            $(this).find('.user_menu_tooltip').css({left: -((width / 2) - 12)});
        }
        function tooltip_hide() {
            $(this).find('.user_menu_tooltip').hide();
        }
    });
</script>