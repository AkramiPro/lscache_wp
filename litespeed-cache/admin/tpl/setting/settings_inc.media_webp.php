<?php
if ( ! defined( 'WPINC' ) ) die ;

?>
	<tr>
		<th class="litespeed-padding-left"><?php echo __( 'Image WebP Replacement', 'litespeed-cache' ) ; ?></th>
		<td>
			<?php $this->build_switch( LiteSpeed_Cache_Config::O_IMG_OPTM_WEBP_REPLACE ) ; ?>
			<div class="litespeed-desc">
				<?php echo sprintf( __( 'Significantly improve load time by replacing images with their optimized %s versions.', 'litespeed-cache' ), '.webp' ) ; ?>
				<br /><font class="litespeed-warning">
					⚠️
					<?php echo __('This setting will edit the .htaccess file.', 'litespeed-cache'); ?>
				</font>
			</div>
		</td>
	</tr>
