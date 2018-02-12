<?php function hectv_create_tag_cloud( $taxonomy = false ){ ?>

<div class="module categories">

    <div class="inner">

        <h2>Categories</h2>

            <div class="cloud-categories">

	            <?php $taxonomyPref = ( $taxonomy ) ? array( 'taxonomy' => $taxonomy ) : array() ; ?>

	            <?php $args = array_merge( array( 'smallest' => 8, 'number' => 9 ), $taxonomyPref ); ?>

                <?php wp_tag_cloud( $args ); ?>

            </div>

    </div>

</div> <!-- End Categories -->

<?php } ?>