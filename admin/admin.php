<div class="wrap">

	<h1><?php echo __( 'Simple WP Rating Settings', 'simple-wp-rating' ); ?></h1>

	<form method="post" action="">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="swr_postsperpage"><?php echo __( 'Posts per page', 'simple-wp-rating' ); ?></label>
					</th>
					<td>
						<input name="swr_postsperpage" type="number" id="swr_postsperpage" value="<?php echo esc_attr($swr_options['posts_perpage']); ?>" class="small-text">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="swr_ratingapproved"><?php echo __( 'Update post rating only when comment is approved', 'simple-wp-rating' ); ?></label>
					</th>
					<td>
						<?php $checked = (empty($swr_options['rating_approved'])) ? '' : 'checked';  ?>
						<input name="swr_ratingapproved" type="checkbox" id="swr_ratingapproved" <?php echo $checked; ?>>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<?php wp_nonce_field('options_page_nonce', 'options_page_nonce_field'); ?>
			<input type="submit" name="swr_options_submit" class="button button-primary" value="<?php echo __( 'Save changes', 'simple-wp-rating' ); ?>">
		</p>

	</form>

</div>