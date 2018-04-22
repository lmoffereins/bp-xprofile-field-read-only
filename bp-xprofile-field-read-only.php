<?php

/**
 * The BuddyPress XProfile Field Read Only Plugin
 * 
 * @package BP XProfile Field Read Only
 * @subpackage Main
 */

/**
 * Plugin Name:       BP XProfile Field Read Only
 * Description:       Make BuddyPress XProfile fields uneditable or hidden for non-admins
 * Plugin URI:        https://github.com/lmoffereins/bp-xprofile-field-read-only/
 * Version:           1.2.1
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       bp-xprofile-field-read-only
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/bp-xprofile-field-read-only
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_XProfile_Field_Read_Only' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class BP_XProfile_Field_Read_Only {

	/**
	 * The plugin's main setting meta key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $main_setting = 'readonly';

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_Read_Only::setup_globals()
	 * @uses BP_XProfile_Field_Read_Only::setup_actions()
	 * @return The single BP_XProfile_Field_Read_Only
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_XProfile_Field_Read_Only;
			$instance->setup_globals();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/
		
		$this->version      = '1.2.1';
		
		/** Paths *************************************************************/
		
		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );
		
		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes'  );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes'  );
		
		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );
		
		/** Misc **************************************************************/
		
		$this->extend       = new stdClass();
		$this->domain       = 'bp-xprofile-field-read-only';
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Bail when XProfile component is not active
		if ( ! bp_is_active( 'xprofile' ) )
			return;

		// Plugin
		add_action( 'bp_init',          array( $this, 'load_textdomain' ), 11    );
		add_filter( 'bp_map_meta_caps', array( $this, 'map_meta_caps'   ), 10, 4 );

		// Admin
		add_action( 'xprofile_field_after_sidebarbox',  array( $this, 'admin_add_metabox'  ) );
		add_action( 'xprofile_fields_saved_field',      array( $this, 'admin_save_metabox' ) );
		add_action( 'xprofile_admin_field_name_legend', array( $this, 'field_name_legend'  ) );

		// Not on the registration page
		if ( ! bp_is_register_page() ) {

			// Filter field attributes
			add_filter( 'bp_xprofile_get_groups', array( $this, 'filter_profile_groups' ), 10, 2 );
		}
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 */
	public function load_textdomain() {
	
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );
	
		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-xprofile-field-read-only/' . $mofile;
	
		// Look in global /wp-content/languages/bp-xprofile-field-read-only folder
		load_textdomain( $this->domain, $mofile_global );
	
		// Look in local /wp-content/plugins/bp-xprofile-field-read-only/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	
		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/**
	 * Map meta caps for this plugin
	 *
	 * @since 1.2.1
	 *
	 * @uses apply_filters() Calls 'bp_xprofile_field_read_only_map_meta_caps'
	 *
	 * @param array  $caps    Mapped caps
	 * @param string $cap     Requested meta cap
	 * @param int    $user_id User ID
	 * @param mixed  $args    Additional arguments
	 * @return array Actual capabilities for meta capability. See {@link WP_User::has_cap()}.
	 */
	public function map_meta_caps( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {
			case 'bp_xprofile_field_read_only_moderate' :

				// Default moderation caps to 'bp_moderate'
				$caps = array( 'bp_moderate' );
				break;
		}

		return apply_filters( 'bp_xprofile_field_read_only_map_meta_caps', $caps, $cap, $user_id, $args );
	}

	/** Public methods **************************************************/

	/**
	 * Return whether the given field is marked read-only
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'bp_xprofile_is_field_read_only'
	 * 
	 * @param int|object $field_id Optional. Field ID or field object. Defaults to the current field.
	 * @param bool $admin_only Optional. Whether to require admin-only status. Defaults to false.
	 * @return bool Field is marked read-only
	 */
	public function is_field_read_only( $field_id = 0, $admin_only = false ) {

		// Get ID from field object
		if ( is_object( $field_id ) ) {
			$field_id = $field_id->id;
		}

		// Default to current profile field
		if ( empty( $field_id ) ) {
			$field_id = bp_get_the_profile_field_id();
		}

		// Get the field readonly setting
		$readonly = bp_xprofile_get_meta( $field_id, 'field', $this->main_setting );
		$readonly = $admin_only ? $readonly == 2 : (bool) $readonly;

		return (bool) apply_filters( 'bp_xprofile_is_field_read_only', $readonly, $field_id, $admin_only );
	}

	/** Admin ***********************************************************/

	/**
	 * Display the plugin's profile field metabox
	 *
	 * @since 1.1.0
	 *
	 * @param BP_XProfile_Field $field
	 */
	public function admin_add_metabox( $field ) {

		// Get the field meta
		$enabled = (int) bp_xprofile_get_meta( $field->id, 'field', $this->main_setting );

		?>

		<div id="field-activity-div" class="postbox">
			<h2><?php esc_html_e( 'Read-only', 'bp-xprofile-field-read-only' ); ?></h2>
			<div class="inside">
				<p class="description"><?php esc_html_e( "For non-administrators, disable field editing by removing this field from edit contexts or choose to fully hide the field from the member's profile.", 'bp-xprofile-field-read-only' ); ?></p>

				<p>
					<label for="readonly" class="screen-reader-text"><?php
						/* translators: accessibility text */
						esc_html_e( 'Read-only status for this field', 'bp-xprofile-field-read-only' );
					?></label>
					<select id="readonly" name="<?php echo $this->main_setting; ?>">
						<option value="0" <?php selected( $enabled, 0 ); ?>><?php esc_html_e( 'Disabled',   'bp-xprofile-field-read-only' ); ?></option>
						<option value="1" <?php selected( $enabled, 1 ); ?>><?php esc_html_e( 'Enabled',    'bp-xprofile-field-read-only' ); ?></option>
						<option value="2" <?php selected( $enabled, 2 ); ?>><?php esc_html_e( 'Admin-only', 'bp-xprofile-field-read-only' ); ?></option>
					</select>
				</p>
			</div>

			<input type="hidden" name="has-read-only" value="1" />
		</div>

		<?php
	}

	/**
	 * Save the contents of the plugin's profile field metabox
	 *
	 * @since 1.0.0
	 *
	 * @param BP_XProfile_Field $field
	 */
	public function admin_save_metabox( $field ) {

		// Bail when the metabox was not submitted
		if ( ! isset( $_POST['has-read-only'] ) )
			return;

		// Define meta value
		$value = isset( $_REQUEST[ $this->main_setting ] ) ? (int) $_REQUEST[ $this->main_setting ] : 0;

		// Update field meta
		bp_xprofile_update_field_meta( $field->id, $this->main_setting, $value );
	}

	/**
	 * Append content to the field name legend
	 *
	 * Since BP 2.2.0.
	 *
	 * @since 1.0.0
	 * 
	 * @param object $field Field data
	 */
	public function field_name_legend( $field ) {

		// Bail when field is not marked read-only
		if ( $this->is_field_read_only( $field->id ) ) {
			$label = $this->is_field_read_only( $field->id, true )
				? esc_html__( '(Admin Only)', 'bp-xprofile-field-read-only' )
				: esc_html__( '(Read Only)',  'bp-xprofile-field-read-only' );

			// Display read only legend
			echo '<span class="readonly">' . $label . '</span>';
		}
	}

	/** Filters *********************************************************/

	/**
	 * Modify the queried profile groups' fields
	 *
	 * @since 1.1.0
	 *
	 * @param array $groups Profile groups
	 * @param array $args Query arguments
	 * @return array Profile groups
	 */
	public function filter_profile_groups( $groups, $args ) {

		// Bail when no fields were fetched
		if ( ! isset( $args['fetch_fields'] ) || ! $args['fetch_fields'] )
			return $groups;

		// Are we editing fields? Front or in admin
		$editing = bp_is_user_profile_edit() || ( is_admin() && isset( $_GET['page'] ) && 'bp-profile-edit' === $_GET['page'] );

		// Bail when user is admin
		if ( current_user_can( 'bp_xprofile_field_read_only_moderate' ) )
			return $groups;

		// Walk profile groups
		foreach ( $groups as $gk => $group ) {

			// No fields were queried
			if ( ! isset( $group->fields ) )
				continue;

			// Walk group fields
			foreach ( $group->fields as $fk => $field ) {

				// Remove read-only field
				if ( $this->is_field_read_only( $field->id, ! $editing ) ) {
					unset( $groups[ $gk ]->fields[ $fk ] );
				}
			}

			// Reset numeric keys
			$groups[ $gk ]->fields = array_values( $groups[ $gk ]->fields );

			// Remove empty group
			if ( isset( $args['hide_empty_groups'] ) && $args['hide_empty_groups'] && empty( $group->fields ) ) {
				unset( $groups[ $gk ] );
			}
		}

		return $groups;
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return BP_XProfile_Field_Read_Only
 */
function bp_xprofile_field_read_only() {
	return BP_XProfile_Field_Read_Only::instance();
}

// Initiate on bp_init
add_action( 'bp_init', 'bp_xprofile_field_read_only' );

endif; // class_exists
