<?php
/*
Plugin Name: Latest Posts with Order Option
Plugin URI: http://wordpress.org/plugins/latest-posts-with-order-option/
Description: Widget for listing your latest posts in the order you choose from widget options.
Version: 1.0
Author: <a href="http://www.henrich.ro">Grávuj Miklós Henrich</a>, <a href="http://profiles.wordpress.org/seldar/">Victor Teodor Butiu</a>
*/

define( 'LPWOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function lpwoo() {
	if ( !function_exists( 'register_sidebar_widget' ) ) {
		return;
	}
	function lpwoo_register() {
		if ( !$options = get_option( 'widget_lpwoo' ) ) {
			$options = array();
		}
		$widget_ops = array(
			'classname'		=> 'widget_lpwoo',
			'description'	=> __('Latest Posts with Order Option')
		);
		$control_ops = array(
			'width'		=> 200,
			'height'	=> 300,
			'id_base'	=> 'lpwoo'
		);
		$name = __( 'Latest Posts with Order Option' );
		$registered = false;
		
		if ( count( $options ) ) {
			foreach ( array_keys( $options ) as $o ) {
				if ( isset( $options[$o]['title'] ) ) {
					$id = "lpwoo-$o";
					$registered = true;
					wp_register_sidebar_widget( $id, $name, 'widget_lpwoo', $widget_ops, array( 'number' => $o ) );
					wp_register_widget_control( $id, $name, 'widget_lpwoo_control', $control_ops, array( 'number' => $o ) );
				}
			}
		}
		if ( !$registered ) {
			wp_register_sidebar_widget( 'lpwoo-1', $name, 'widget_lpwoo', $widget_ops, array( 'number' => 1 ) );
			wp_register_widget_control( 'lpwoo-1', $name, 'widget_lpwoo_control', $control_ops, array( 'number' => 1 ) );
		}
	}
	function widget_lpwoo( $args, $widget_args = 1 ) {
		global $post, $lpwoo_widget_styles;
		extract( $args, EXTR_SKIP );
		if ( is_numeric( $widget_args ) ) $widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) ); 
		extract( $widget_args, EXTR_SKIP );
		$options			= get_option('widget_lpwoo');
		$css_style			= "widget_lpwoo";
		$post_type			= "post";
		$title				= $options[$number]['title'];
		$howmany 			= $options[$number]['howmany'] ? $options[$number]['howmany'] : 5;
		$have_description	= intval($options[$number]['have_description']) ? intval($options[$number]['have_description']) : 0;
		$description_text	= $options[$number]['description_text'];
		$order_by			= $options[$number]['orderby'] ? $options[$number]['orderby'] : "date";
		$order_type			= $options[$number]['order'] ? $options[$number]['order'] : "desc";
		
		// output
		echo $before_widget;
		echo "<div class='".$css_style."'>";
		if ( $title ) echo '<h2>'.$title.'</h2>';
		if ( $have_description ) {
			echo "<p class='posts_custom_listing'>".$description_text."</p>";
		}

		$lpwoo_query = new WP_Query(
			array(
				'post_type'	=> $post_type,
				'showposts'	=> $howmany,
				'orderby'	=> $order_by,
				'order'		=> $order_type
			)
		);
		if ( $lpwoo_query->have_posts() ) :
		?>
        <ul class="posts_custom_listing">
			<?php
            while( $lpwoo_query->have_posts() ) : $lpwoo_query->the_post();
			$short_title		= get_the_title();
			$valid_short_title	= substr( $short_title, 0, 75 );
			?>
            <li>
            	<a href="<?php the_permalink(); ?>" rel="bookmark" class="widget-element-item" title="<?php echo __("View")." - ".$short_title; ?>">
                	<?php echo $valid_short_title; ?>
                </a>
            </li>
            <?php
			wp_reset_query();
			endwhile;
			?>
        </ul>
        <?php endif; ?>
        <br />
        <?php
		echo "</div>";
		echo $after_widget;
	}
	
	function widget_lpwoo_control( $widget_args = 1 ) {
		global $wp_registered_widgets, $wpdb, $defaultOptions;
		static $updated = false;
		static $categories = false;
		if ( is_numeric( $widget_args ) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$options = get_option('widget_lpwoo');
		if ( !is_array( $options ) ) {
			$options = array();
		}
		
		if ( !$updated && !empty( $_POST['sidebar'] ) ) {
			$sidebar = (string) $_POST['sidebar'];
			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset( $sidebars_widgets[$sidebar] ) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();
				
			foreach ( $this_sidebar as $_widget_id ) {
				if ( 'widget_lpwoo' == $wp_registered_widgets[$_widget_id]['callback'] && isset( $wp_registered_widgets[$_widget_id]['params'][0]['number'] ) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "lpwoo-$widget_number", $_POST['widget-id'] ) )
						unset( $options[$widget_number] );
				}
			}
			
			foreach ( (array) $_POST['widget-lpwoo'] as $widget_number => $lpwoo_instance ) {
				if ( !isset($lpwoo_instance['title']) && isset( $options[$widget_number] ) )
				continue;
				unset( $lpwoo_instance['submit'] );
				$lpwoo_instance['widget_number']=$_POST['widget_number'];
				$lpwoo_instance['multi_number']=$_POST['multi_number'];
				if ( $_POST['multi_number'] ) {
					$options[$_POST['multi_number']] = $lpwoo_instance;
				} else {
					$options[$_POST['widget_number']] = $lpwoo_instance;
				}
			}
			update_option( 'widget_lpwoo', $options );
		}
		
		if ( -1 == $number ) {
			if ( !is_array( $options ) ) {
				$options = $defaultOptions;
			}
		}
		
		$title				= $options[$number]['title'];
		$howmany			= intval($options[$number]['howmany']);
		$have_description	= intval($options[$number]['have_description']);
		$description_text   = $options[$number]['description_text'];
		$order_by			= strlen($options[$number]['orderby']) > 0 ? $options[$number]['orderby'] : "date";
		$order_type			= strlen($options[$number]['order']) > 0 ? $options[$number]['order'] : "desc";
		?>
        <p>
        	<label for="widget-lpwoo-<?php echo $number; ?>-title" class="lpwoo_css">
            	<?php echo __('Widget Title'); ?>
                <input id="lpwoo-title" name="widget-lpwoo[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" size="39" />
            </label>
        </p>
        <p>
        	<label for="lpwoo-<?php echo $number; ?>[have_description]" class="lpwoo_css">
            	<?php echo __("Activate Description? <em>(check below)</em>"); ?> <br />
                <input type="checkbox" name="widget-lpwoo[<?php echo $number; ?>][have_description]" value="1" <?php echo $have_description ? 'checked="checked"' : ""; ?> />
            </label>
        </p>
        <p>
        	<label for="widget-lpwoo-description-text" class="lpwoo_css">
            	<?php echo __('Widget Description'); ?>
                <textarea id="lpwoo-<?php echo $number; ?>_description-text" name="widget-lpwoo[<?php echo $number;?>][description_text]" style="display:<?php echo $have_description ? 'block' : 'block'; ?>" rows="5" cols="35"><?php echo $description_text; ?></textarea>
            </label>
        </p>
        <p>
        	<label for="widget-lpwoo-<?php echo $number; ?>-orderby" class="lpwoo_css">
            	<?php echo __('Order by: '); ?>
                <select name="widget-lpwoo[<?php echo $number; ?>][orderby]">
                	<option value="">
						<?php echo __("Select one"); ?>:
                    </option>
                    <option value="menu_order" <?php echo ( $order_by == "menu_order" ) ? 'selected="selected"' : ""; ?>>
                    	Entry
                    </option>
                    <option value="ID" <?php echo ( $order_by == "ID" ) ? 'selected="selected"' : ""; ?>>
                    	Id
                    </option>
                    <option value="title" <?php echo ( $order_by == "title" ) ? 'selected="selected"' : ""; ?>>
                    	Title
                    </option>
                    <option value="date" <?php echo ( $order_by == "date" ) ? 'selected="selected"' : ""; ?>>
                    	Date
                    </option>
                </select>
                <select name="widget-lpwoo[<?php echo $number; ?>][order]">
                	<option value="desc" <?php echo ( $order_type == "desc" ) ? 'selected="selected"' : ""; ?>>
                    	Desc
                    </option>
                    <option value="asc" <?php echo ( $order_type == "asc" ) ? 'selected="selected"' : ""; ?>>
                    	Asc
                    </option>
                </select>
            </label>
        </p>
        <p>
        	<label for="widget-lpwoo-<?php echo $number; ?>-howmany" class="lpwoo_css">
            	<?php echo __( 'Number of posts to show' ); ?>
                <input name="widget-lpwoo[<?php echo $number; ?>][howmany]" type="text" value="<?php echo $howmany; ?>" size="2" />
                <br />
                <em>
					<?php echo __( 'Highest suggested value: <strong>10</strong>' ); ?>
                </em>
            </label>
        </p>
        <input type="hidden" id="widget-lpwoo-submit-<?php echo $number; ?>" name="widget-lpwoo[<?php echo $number; ?>][submit]" value="1" />
	<?php
	}
	lpwoo_register();
}

function lpwoo_css() {
	wp_register_style( 'lpwoo.css', LPWOO_PLUGIN_URL . 'lpwoo.css', array(), '1.0' );
	wp_enqueue_style( 'lpwoo.css' );
}

add_action( 'admin_enqueue_scripts', 'lpwoo_css' );

add_action( 'widgets_init', 'lpwoo' );

?>