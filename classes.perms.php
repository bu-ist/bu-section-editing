<?php

/*

Storing group permissions

[post_type] => array( '123', '100', '200' );

Each ID and it's children are editable


OR

Do we store the editable property as post meta?

update_post_meta( $post->ID, '_buse_editable', true );

For hierarchical types, this would waterfall down to children...

In this case

*/

?>