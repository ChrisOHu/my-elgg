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
?>
<div class='login_register_tabs'>
    <span class="login_tab active">Login</span>
    <span class="register_tab">Register</span>
    <div style="clear:both;"></div>
</div>
<div style="clear:both;"></div>
<div class='registerform'>
    <?php echo elgg_view_form('register'); ?>
</div>
<div class='loginform'>
    <?php echo elgg_view_form('login'); ?>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $(".login_tab").live('click',function(){
            $(".loginform").fadeIn('slow');
            $(".registerform").hide();
            $(".login_tab").addClass('active');
            $(".register_tab").removeClass('active');
        });
        $(".register_tab").live('click',function(){
            $(".loginform").hide();
            $(".registerform").fadeIn('slow'); 
            $(".login_tab").removeClass('active');
            $(".register_tab").addClass('active');
        });
    });
</script>