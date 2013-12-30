<?php
/*
Plugin Name: Trim Widget Descriptions
Description: Limit the length of the description under each widget on the the <a href="widgets.php">Widgets</a> page.
Author: Eric King
Version: 0.2.1
Author URI: http://webdeveric.com/

@todo Add an activation hook with a WP & PHP compatibility check.

*/

define('TWD_PLUGIN', plugin_basename( __FILE__ ) );

if( ! function_exists('ellipsis') ){
	function ellipsis( $str, $max_len, $ellipsis="&hellip;" ){
		$str = trim( $str );
		$str_len = strlen( $str );
		if( $str_len<=$max_len ){
			return $str;
		} else {
			$ellipsis_len=strlen( $ellipsis );
			if( $ellipsis == '&hellip;' || $ellipsis == '&#8230;' )
				$ellipsis_len = 2;
			return substr( $str, 0, $max_len-$ellipsis_len ).$ellipsis;
		}
	}
}

class Trim_Widget_Descriptions {

	private static $description_options = array(
		'normal' => 'No change (default)',
		'trim' => 'Make them shorter',
		'hide' => 'Hide descriptions'
	);

	const USER_META_KEY = 'twd_descriptions';

	public static function options(){
		$user_id = get_current_user_id();

		if( empty( $twd_descriptions ) )
			$twd_descriptions = 'normal';

		if( isset( $_POST['twd_noncename'] ) ){
			// Do a basic verification and check to see if the passed value is actually one of the values I was expecting.
			if( ! wp_verify_nonce( $_POST['twd_noncename'], TWD_PLUGIN ) || ! isset( self::$description_options[ $_POST['twd_descriptions'] ] ) ){
				self::redirect( admin_url('widgets.php?error=0') );
			}
			update_user_meta( $user_id, self::USER_META_KEY, $_POST['twd_descriptions'] );
			self::redirect( admin_url('widgets.php?message=0') );
		}
		$twd_descriptions = get_user_meta( $user_id, self::USER_META_KEY, true );
	?>
		<form method="post" action="">
			<fieldset>
				<input type="hidden" name="twd_noncename" value="<?php echo wp_create_nonce( TWD_PLUGIN ); ?>" />
				<p class="description">
					<label for="twd_descriptions">Widget Descriptions:</label>
					<select name="twd_descriptions" id="twd_descriptions" class="wide">
					<?php
						foreach( self::$description_options as $value => $label ){
							printf('<option value="%s" %s>%s</option>', $value, selected($twd_descriptions, $value, false ), $label );
						}
					?>
					</select>
					<button type="submit" class="button-secondary">Save</button>
				</p>
			</fieldset>
		</form>
	<?php
	}

	public static function trim_descriptions(){
		global $wp_registered_widgets;
		//printf('<pre>%s</pre>', print_r( $wp_registered_widgets, true ) );

		$twd_descriptions = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );
		if( $twd_descriptions == 'normal' || $twd_descriptions == '' )
			return;

		// This wont work since esc_html() is called on the value of the widget description.
		// $wp_registered_widgets[$id]['description'] = sprintf('<span title="%s">%s</span>', $wp_registered_widgets[$id]['description'], ellipsis( $wp_registered_widgets[$id]['description'], 40 ) );

		foreach( $wp_registered_widgets as $id => $widget ){
			if( $twd_descriptions == 'hide' ){
				$wp_registered_widgets[ $id ]['description'] = ' ';
				continue;
			}
			if( ! isset( $widget['description'] ) )
				$widget['description'] = $widget['name'];
			$wp_registered_widgets[ $id ]['description'] = ellipsis( $widget['description'] , 40 );
		}
	}

	public static function deactivate(){
		// Clean up after yourself...
		delete_metadata('user', 0, self::USER_META_KEY, '', true );
	}
	
	public static function redirect( $url ){
		if( ! headers_sent() ){
			wp_redirect( $url );
		} else {
			printf('<script>window.location.replace("%1$s");</script><noscript><meta http-equiv="refresh" content="0;url=%1$s"></noscript>', $url );
		}
		exit;
	}

}

register_deactivation_hook( __FILE__, array('Trim_Widget_Descriptions', 'deactivate' ) );

add_action('widgets_admin_page', array('Trim_Widget_Descriptions', 'options') );
add_action('widgets_admin_page', array('Trim_Widget_Descriptions', 'trim_descriptions') );








/*
	This is a work in progress.

	@date 2013-12-30
	@todo It looks like the screen options core code has been updated to allow additional settings to be manually added on the widgets page.
	Move the select box to the screen options.


	public function screen_options( $screen_settings, $screen ){
		$widget_screen = convert_to_screen('widgets.php');
		if ( $screen->id == $widget_screen->id ){

			$options = array();
			foreach( self::$description_options as $value => $label ){
				$options[] = sprintf('<option value="%s" %s>%s</option>', $value, selected($twd_descriptions, $value, false ), $label );
			}

			$options = implode('', $options );


			$args = array(
				'option'	=> Trim_Widget_Descriptions::USER_META_KEY
			);
			$screen->add_option( Trim_Widget_Descriptions::USER_META_KEY, $args );

$screen_settings.=<<<TWD

		<p class="description">
			<label for="twd_descriptions">Widget Descriptions:</label>
			<select name="twd_descriptions" id="twd_descriptions" class="wide">{$options}</select>
			<button type="submit" class="button-secondary">Save</button>
		</p>

TWD;

		}
		return $screen_settings;
	}
	
	add_filter('screen_settings', array('Trim_Widget_Descriptions', 'screen_options'), 10, 2);

*/



/**
	@todo add settings to Screen Options to hide Widget description or just show the Widget name.
	@date 2011-10-18

	@note This wont work this way. The _screen_options are overwritten specifically for the widgets screen. The Screen Options needs a decent API.
	@see wp-admin/includes/screen.php:777
	@date 2011-11-05
*/


/*
function twd_admin_menu(){
	add_action("load-widgets.php", "twd_screen_options");	
}
add_action('twd_admin_menu', 'pippin_sample_settings_menu');


function twd_screen_options(){

	$args = array(
			'label'		=> __('Widget Descriptions'),
			'default'	=> 10,
			'option'	=> Trim_Widget_Descriptions::USER_META_KEY
	);
	add_screen_option( Trim_Widget_Descriptions::USER_META_KEY, $args );

}
*/