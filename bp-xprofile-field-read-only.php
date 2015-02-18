<?php

/**
 * The BP XProfile Field Read Only Plugin
 * 
 * @package BP XProfile Field Read Only
 * @subpackage Main
 */

/**
 * Plugin Name:       BP XProfile Field Read Only
 * Description:       Make BuddyPress XProfile fields readonly for non-admins
 * Plugin URI:        https://github.com/lmoffereins/bp-xprofile-field-read-only/
 * Version:           1.0.0
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
		
		$this->version      = '1.0.0';
		
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
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// XProfile field meta
		add_action( 'xprofile_field_submitbox_start',   array( $this, 'field_display_setting' ) );
		add_action( 'xprofile_field_after_save',        array( $this, 'field_save_setting'    ) );
		add_action( 'xprofile_admin_field_name_legend', array( $this, 'field_name_legend'     ) );
		add_action( 'bp_admin_head',                    array( $this, 'admin_scripts'         ) );

		// Not on the registration page
		if ( ! bp_is_register_page() ) {

			// Filter field attributes
			add_filter( 'bp_xprofile_field_edit_html_elements',      array( $this, 'handle_element_attrs' ), 10, 2 );
			add_filter( 'bp_get_the_profile_field_options_checkbox', array( $this, 'handle_input_markup'  ), 10, 5 );
			add_filter( 'bp_get_the_profile_field_options_radio',    array( $this, 'handle_input_markup'  ), 10, 5 );
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
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
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

	/** Public methods **************************************************/

	/**
	 * Return whether the given field is marked read-only
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_xprofile_get_meta()
	 * @uses apply_filters() Calls 'bp_xprofile_is_field_read_only'
	 * 
	 * @param int $field_id Field ID
	 * @return bool Field is marked read-only
	 */
	public function is_field_read_only( $field_id ) {
		return (bool) apply_filters( 'bp_xprofile_is_field_read_only', bp_xprofile_get_meta( $field_id, 'field', $this->main_setting ), $field_id );
	}

	/**
	 * Output the input for the read-only setting
	 *
	 * Since BP 2.1.0.
	 *
	 * @since 1.0.0
	 *
	 * @uses bp_xprofile_get_meta()
	 * @uses wp_nonce_field()
	 *
	 * @param BP_XProfile_Field $field Current xprofile field
	 */
	public function field_display_setting( $field ) {

		// Ignore the primary field
		if ( 1 == $field->id )
			return;

		// Query args for user groups from the parent field group
		$enabled = bp_xprofile_get_meta( $field->id, 'field', $this->main_setting ); ?>

		<div class="misc-pub-section misc-pub-readonly hide-if-js">
			<?php wp_nonce_field( 'readonly', '_wpnonce_readonly' ); ?>

			<label>
				<input id="readonly" name="<?php echo $this->main_setting; ?>" type="checkbox" value="1" <?php checked( $enabled ); ?>/>
				<?php _e( 'Make field read-only for non-admins', 'bp-xprofile-field-read-only' ); ?>
			</label>
		</div>

		<?php
	}

	/**
	 * Save the input for the read-only setting
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_verify_nonce()
	 * @uses bp_xprofile_update_field_meta()
	 *
	 * @param BP_XProfile_Field $field Saved xprofile field
	 */
	public function field_save_setting( $field ) {

		// Bail if nonce does not verify
		if ( ! isset( $_REQUEST['_wpnonce_readonly'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce_readonly'], 'readonly' ) )
			return;

		// Sanitize input
		$enabled = isset( $_REQUEST[ $this->main_setting ] ) ? (int) $_REQUEST[ $this->main_setting ] : 0;

		// Update field meta
		bp_xprofile_update_field_meta( $field->id, $this->main_setting, $enabled );
	}

	/**
	 * Append content to the field name legend
	 *
	 * Since BP 2.2.0.
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_Read_Only::is_field_read_only()
	 * 
	 * @param object $field Field data
	 */
	public function field_name_legend( $field ) {

		// Bail when field is not marked read-only
		if ( ! $this->is_field_read_only( $field->id ) )
			return;

		// Display read only legend
		echo '<span class="readonly">' . __( '(Read Only)', 'bp-xprofile-field-read-only' ) . '</span>';
	}

	/**
	 * Output specific metabox styles for the xprofile admin
	 *
	 * @since 1.0.1
	 *
	 * @uses BP_XProfile_Field_Read_Only::is_xprofile_admin()
	 */
	public function admin_scripts() {

		// Bail when this is not an XProfile admin page
		if ( ! $this->is_xprofile_admin() )
			return; ?>

		<style>
			#major-publishing-actions .misc-pub-section {
				padding: 6px 0 8px;
			}
		</style>

		<script>
			jQuery(document).ready( function( $ ) {
				$( '#submitdiv' )
					// Add styling class 'hndle' to metabox title
					.find( 'h3' )
						.addClass( 'hndle' )
						.end()
					// Move .misc-pub-sections outside #major-publishing-actions
					.find( '#major-publishing-actions .misc-pub-section' )
						.insertBefore( '#submitdiv #major-publishing-actions' )
						.show();
			});
		</script>

		<?php
	}

	/**
	 * Return whether we are on the XProfile admin pages
	 *
	 * @since 1.0.1
	 *
	 * @uses get_current_screen()
	 * 
	 * @return bool This is an XProfile admin page
	 */
	public function is_xprofile_admin() {

		// Bail when not in the admin
		if ( ! is_admin() )
			return false;

		// Define expected screen id
		$screen_id = 'users_page_bp-profile-setup';
		if ( is_network_admin() ) {
			$screen_id .= '-network';
		}

		return $screen_id === get_current_screen()->id;
	}

	/** Filters *********************************************************/

	/**
	 * Filter the HTML element's attributes
	 *
	 * @since 1.0.0
	 *
	 * @uses BP_XProfile_Field_Read_Only::is_field_read_only()
	 * @uses bp_get_the_profile_field_id()
	 * 
	 * @param array $attrs HTML attributes
	 * @param string $class_name Class name of current field type
	 * @return array HTML attributes
	 */
	public function handle_element_attrs( $attrs, $class_name ) {

		// Add readonly attribute when field is read-only. Not for admins
		if ( $this->is_field_read_only( bp_get_the_profile_field_id() ) && ! current_user_can( 'bp_moderate' ) ) {

			// Check the field's class name
			switch ( $class_name ) {

				// Make <input> and <textarea> 'readonly'
				case 'BP_XProfile_Field_Type_Textarea' :
				case 'BP_XProfile_Field_Type_Textbox' :
				case 'BP_XProfile_Field_Type_Number' :
				case 'BP_XProfile_Field_Type_URL' :
					$attrs['readonly'] = 'readonly';
					break;

				// Checkboxes and Radios are filtered elsewhere
				case 'BP_XProfile_Field_Type_Checkbox' :
				case 'BP_XProfile_Field_Type_Radiobutton' :
					break;

				// Set <select> elements to 'disabled', for they cannot be set to 'readonly'
				case 'BP_XProfile_Field_Type_Datebox' :
				case 'BP_XProfile_Field_Type_Multiselectbox' :
				case 'BP_XProfile_Field_Type_Selectbox' :
					$attrs['disabled'] = 'disabled';
					break;

				default :

					// Filter for 'readonly' field class
					if ( apply_filters( 'bp_xprofile_field_readonly_class', false, $class_name ) ) {
						$attrs['readonly'] = 'readonly';

					// Better safe than sorry: default to 'disabled'
					} else {
						$attrs['disabled'] = 'disabled';
					}
			}
		}

		return $attrs;
	}

	/**
	 * Filter input markup for read-only fields
	 *
	 * @since 1.0.0
	 * 
	 * @uses BP_XProfile_Field_Read_Only::is_field_read_only()
	 * 
	 * @param string $html Input HTML element
	 * @param object $option Option data
	 * @param int $field_obj_id Field ID
	 * @param bool $selected Input is selected
	 * @param int $index Option index
	 * @return string Input HTML element
	 */
	public function handle_input_markup( $html, $option = null, $field_obj_id = null, $selected = false, $index = 0 ) {

		// Add readonly attribute when field is read-only. Not for admins
		if ( $this->is_field_read_only( bp_get_the_profile_field_id() ) && ! current_user_can( 'bp_moderate' ) ) {
			$new_html = $html;

			// Make checkbox/radio 'disabled'. See http://www.faqs.org/docs/htmltut/forms/_INPUT_DISABLED.html
			$html = str_replace( 'type="checkbox" ', 'type="checkbox" disabled="disabled" ', $html );
			$html = str_replace( 'type="radio" ',    'type="radio" disabled="disabled" ',    $html );

			// Nothing changed: this was not a checkbox or radio
			if ( $new_html == $html ) {
				$html = str_replace( '<input ', '<input readonly="readonly" ', $html );
			}
		}

		return $html;
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
