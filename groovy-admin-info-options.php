<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', array('Groovy_Admin_Info_Page_Options', 'gai_add_admin_menu') );
add_action( 'admin_init', array('Groovy_Admin_Info_Page_Options', 'gai_settings_init')  );

class Groovy_Admin_Info_Page_Options {
	const GAI_PAGETITLE = 'Настройки плагина Groovy Admin Info';
	const GAI_MENUTITLE = 'Grv Admin Info Page';
	const GAI_SLUG = 'gai_options';

	/**
	 * Creates admin menu single item
	 * @return void
	 */
	public function gai_add_admin_menu() { 
		add_options_page( self::GAI_PAGETITLE, self::GAI_MENUTITLE, 'manage_options', self::GAI_SLUG, array(__CLASS__, 'gai_options_page') );		
	}

	/**
	 * Init for basic set of options
	 * @return void
	 */
	public function gai_settings_init() { 
		register_setting( 'pluginPage', 'gai_settings' );
				
		add_settings_field( 
			'gai_checkbox_menu_enable', 
			__( 'Элемент в меню админ-панели', 'wordpress' ), 
			array(__CLASS__, 'gai_checkbox_menu_enable_render'), 
			'pluginPage',
			'gai_pluginPage_section' 
		);						
		add_settings_field( 
			'gai_checkbox_dashboard_enable', 
			__( 'Виджет в консоли', 'wordpress' ), 
			array(__CLASS__, 'gai_checkbox_dashboard_enable_render'), 
			'pluginPage',
			'gai_pluginPage_section' 
		);	
		add_settings_section(
			'gai_pluginPage_section', 
			__( 'Опции Groovy Admin Info Page', 'wordpress' ), 
			array(__CLASS__, 'gai_settings_section_callback'), 
			'pluginPage'
		);
		add_settings_field( 
			'gai_select_material', 
			__( 'Выберите материал', 'wordpress' ), 
			array(__CLASS__, 'gai_select_field_material_render'), 
			'pluginPage',
			'gai_pluginPage_section' 
		);
		add_settings_field( 
			'gai_checkbox_lock_post', 
			__( 'Сделать неудаляемым', 'wordpress' ), 
			array(__CLASS__, 'gai_checkbox_lock_render'), 
			'pluginPage',
			'gai_pluginPage_section' 
		);

		add_settings_field( 
			'gai_text_menu_name', 
			__( 'Название пункта меню', 'wordpress' ), 
			array(__CLASS__, 'gai_text_menu_name_render'), 
			'pluginPage', 
			'gai_pluginPage_section' 
		);
	}

	/**
	 * Renders admin menu enable option checkbox
	 * @return void
	 */
	public function gai_checkbox_menu_enable_render() { 
		self::gai_create_checkbox( 'gai_checkbox_menu_enable' );
	}

	/**
	 * Renders dashboard widget option checkbox
	 * @return void
	 */
	public function gai_checkbox_dashboard_enable_render() { 
		self::gai_create_checkbox( 'gai_checkbox_dashboard_enable' );
	}

	/**
	 * Renders lock post checkbox
	 * @return void
	 */
	public function gai_checkbox_lock_render() { 
		self::gai_create_checkbox( 'gai_checkbox_lock_post' );
	}

	/**
	 * Renders checkbox with specified option_name
	 * @param  string $option_name option name slug
	 * @return void
	 */
	public function gai_create_checkbox( $option_name ) {
		if (is_null($option_name)) {
			return false;
		}

		$options = get_option( 'gai_settings' );
		if ( is_array($options) && array_key_exists($option_name, $options) ) : ?>
			<input type='checkbox' name='gai_settings[<?=$option_name?>]' checked='checked' value='1'>
		<?php else : ?>
			<input type='checkbox' name="gai_settings[<?=$option_name?>]" value='1'>
		<?php endif;
	}

	/**
	 * Renders select box which contains all possible materials with title
	 * @return void
	 */
	public function gai_select_field_material_render() { 
		$options = get_option( 'gai_settings' );

		$post_types = get_post_types();
		$args = array(
			'posts_per_page' => -1,
			'post_type' => $post_types
		);
		$my_query = null;
		$my_query = new WP_Query($args);
		$select_data = null;
		foreach($my_query->posts as $i => $p) {
			if (!empty($p->post_title)){
				$select_data[$i]['id'] = $p->ID;
				$select_data[$i]['title'] = $p->post_title;
			}	
		}
		if (count($select_data)) :
		?>
			
		<select name='gai_settings[gai_select_material]'>
			<?php foreach ($select_data as $s) : 
				if (is_array($s)) : 
					if(is_array($options)) :?>
						<option value='<?=$s['id']?>' <?php selected( $options['gai_select_material'], $s['id'] ); ?>><?=$s['title']?></option>
					<?php else : ?>
						<option value='<?=$s['id']?>'><?=$s['title']?></option>
					<?php endif;?>

			<?php endif; 
			endforeach; ?>
		</select>

	<?php
		endif;
		
	}

	/**
	 * Renders text input field for customizing admin menu item's name
	 * @return void
	 */
	public function gai_text_menu_name_render() { 
		$options = get_option( 'gai_settings' );
		if (is_array($options)) :?>
			<input type='text' name='gai_settings[gai_text_menu_name]' value='<?php echo $options['gai_text_menu_name']; ?>'>
		<?php else : ?>
			<input type='text' name='gai_settings[gai_text_menu_name]' value=''>
		<?php endif;
	}

	/**
	 * Creates a callback and echoes description
	 * @return void
	 */
	public function gai_settings_section_callback() { 
		echo __( 'Выберите материал для использования в качестве "Информации" и отметьте стоит ли его блокировать', 'wordpress' );
	}

	/**
	 * Creates form with options
	 * @return void
	 */
	public function gai_options_page() { 

		?>
		<form action='options.php' method='post'>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php
	}

} //class