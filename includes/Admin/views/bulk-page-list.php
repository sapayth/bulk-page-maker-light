<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php _e( 'All Bulk Pages', 'bulk-page-maker' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url('admin.php?page=bulk-page-maker&action=new') ); ?>" class="page-title-action">
		<?php _e( 'Add New', 'bulk-page-maker' ); ?>
	</a>
</ul>

	<form action="" method="post">
		<?php
			$table = new Bulk\Page\Maker\Admin\Pages_List();
			$table->prepare_items();
			$table->display();
		?>
	</form>
</div>