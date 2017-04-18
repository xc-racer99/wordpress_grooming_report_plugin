<?php
function lhgr_settings_page_html()
{
	// check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?= esc_html(get_admin_page_title()); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "lhgr_settings"
			settings_fields('lhgr_settings');
			// output setting sections and their fields
			// (sections are registered for "lhgr_settings", each field is registered to a specific section)
			do_settings_sections('lhgr_settings');
			?>
			<fieldset>
			<legend>Map Settings</legend>
			<table>
			<tr>
			<td><label for="map_lat">Latitude</label></td>
			<td><input type="text" name="map_lat" id="map_lat" value="<?php echo esc_attr(get_option('map_lat'));?>"/></td>
			</tr>

			<tr>
			<td><label for="map_lng">Longitude</label></td>
			<td><input type="text" name="map_lng" id="map_lng" value="<?php echo esc_attr(get_option('map_lng'));?>"/></td>
			</tr>

			<tr>
			<td><label for="map_default_zoom">Default Zoom</label></td>
			<td><input type="text" name="map_default_zoom" id="map_default_zoom" value="<?php echo esc_attr(get_option('map_default_zoom'));?>"/></td>
			</tr>

			<tr>
			<td><label for="map_tiles">Map Tiles URL</label></td>
			<td><input type="text" name="map_tiles" id="map_tiles" value="<?php echo esc_attr(get_option('map_tiles'));?>"/></td>
			</tr>

			<tr>
			<td><label for="map_max_zoom">Max Zoom Level</label></td>
			<td><input type="text" name="map_max_zoom" id="map_max_zoom" value="<?php echo esc_attr(get_option('map_max_zoom'));?>"/></td>
			</tr>

			<tr>
			<td><label for="map_attribute">Map Attribution</label></td>
			<td><input type="text" name="map_attribute" id="map_attribute" value="<?php echo esc_attr(get_option('map_attribute'));?>"/></td>
			</tr>

			<tr>
			<td><label for="inreach_link">inReach KML Feed URL</label></td>
			<td><input type="url" name="inreach_link" id="inreach_link" value="<?php echo esc_attr(get_option('inreach_link'));?>"/></td>
			</tr>
			</table>
			</fieldset>

			<?php
			// output save settings button
			submit_button('Save Settings');
			?>
		</form>
	</div>
	<?php
}

function lhgr_settings_page()
{
	add_submenu_page(
		'options-general.php',
		'Grooming Report Settings',
		'Grooming Report',
		'manage_options',
		'lhgr_settings',
		'lhgr_settings_page_html'
	);
}
add_action('admin_menu', 'lhgr_settings_page');

function lhgr_plugin_settings_init()
{
	// Parameters for the initial setup of the map
	register_setting( 'lhgr_settings', 'map_lat' );
	register_setting( 'lhgr_settings', 'map_lng' );
	register_setting( 'lhgr_settings', 'map_default_zoom' );
	register_setting( 'lhgr_settings', 'map_tiles' );
	register_setting( 'lhgr_settings', 'map_max_zoom' );
	register_setting( 'lhgr_settings', 'map_attribute' );

	// Parameters for the inReach KML feed
	register_setting( 'lhgr_settings', 'inreach_link' );
}
add_action('admin_init', 'lhgr_plugin_settings_init');
