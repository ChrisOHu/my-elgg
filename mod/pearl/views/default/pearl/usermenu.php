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

elgg_load_js('hoverintent');
?>
<div id="user_menu">
    <div class="user_menu_holder">
        <?php
        if (elgg_is_logged_in()) {
            echo elgg_view('pearl/loggedin');
        } else {
            echo elgg_view('pearl/loggedout');
        }
        ?>
    </div>
    <?php
    if (elgg_is_logged_in()) {
        echo "<div class='user_menu_tag'>Your Account</div>";
    } else {
        echo "<div class='user_menu_tag'>Login/Register</div>";
    }
    ?>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $(".user_menu_tag").toggle(function(){
            $(".user_menu_holder").slideDown('slow');
        },function(){
            $(".user_menu_holder").slideUp('slow');
        });
    });
</script>