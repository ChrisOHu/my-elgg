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

elgg_load_js('nivo');
elgg_load_css('nivo');
elgg_load_css('nivo-default');
?>
<div id="wrapper">

    <div class="slider-wrapper theme-default">
        <div id="slider" class="nivoSlider">
            <img src="<?php echo $CONFIG->url; ?>mod/pearl/graphics/slide1.jpg" alt="" />
            <img src="<?php echo $CONFIG->url; ?>mod/pearl/graphics/slide2.jpg" alt="" />
            <img src="<?php echo $CONFIG->url; ?>mod/pearl/graphics/slide3.jpg" alt="" />
        </div>
    </div>

</div>
<script type="text/javascript">
    $(window).load(function() {
        $('#slider').nivoSlider();
    });
    $("#slider").css("border-radius","30px");
    $("#slider").css("box-shadow","0px 0px 2px #dddddd")
</script>