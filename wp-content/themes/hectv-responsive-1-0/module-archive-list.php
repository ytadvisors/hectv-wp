<?php function hectv_create_archives_list(){ ?>

<div id="archives" class="module">

	<?php $args = array( 'type' => 'monthly',
	'limit' => '22',
	'order' => 'DESC'
	); ?>

	<div class="inner">

		<h2>Blog<br/>Archives</h2>

		<ul><?php wp_get_archives( $args ); ?></ul>

	</div>

 </div> <!--End Archives Module-->

<?php } ?>