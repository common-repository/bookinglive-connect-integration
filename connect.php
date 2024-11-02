<?php
/**
 * Plugin Name: BookingLive Free Online Booking & Scheduling System
 * Plugin URI: https://www.bookinglive.com
 * Description: Display your BookingLive Free Online Booking & Scheduling System directly in your WordPress site.
 * Author: BookingLive.com
 * Author URI: http://www.bookinglive.com
 * Version: 1.0.1
 */


/**
 * Register the booking page post type
 */
function bl_bookingpage()
{
	register_post_type('book',
		array(
			'labels'      => array(
				'name'          => __('Booking Pages'),
				'singular_name' => __('Booking Page'),
			),
			'public'      => true,
			'has_archive' => true,
		)
	);
}
add_action('init', 'bl_bookingpage');


/**
 * Settings page
 */
// Add page to menu
function bl_add_setting_to_menu()
{
	add_menu_page(
		"BookingLive Connect",
		"BookingLive Connect",
		"manage_options",
		"bookinglive-connect",
		"bl_connect_settings_page",
		null,
		99
	);
}
add_action("admin_menu", "bl_add_setting_to_menu");


// Create the page content
function bl_connect_settings_page()
{
	?>
		<div class="wrap">
			<h1>BookingLive Connect Integration</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields("section");
				do_settings_sections("connect-options");
				submit_button();
				?>
			</form>
		</div>
	<?php
}


// Company setting field
function bl_display_slug_field()
{
	?>
	    <input type="text" name="connect_company_name" id="connect_company_name" value="<?php echo get_option('connect_company_name'); ?>" /><input type="submit" id="connect-company-name-btn" value="Check" />
        <div id="connect_company_name_feedback"></div>

        <p>
            You can find your <a href="https://bookinglive.io" target="_blank">Connect</a> <i>slug</i> by
            logging into your system under Settings -> System Setup -> Account URL
        </p>

        <script>
          jQuery(document).ready(function(){
            jQuery('#connect-company-name-btn').click(function(){

              jQuery('#connect_company_name_feedback').html(
                '<div style="margin:15px;">'
                    + '<img src="<?php echo plugin_dir_url( __FILE__ ) . 'loading.gif'; ?>" style="vertical-align:middle;margin-right:15px;" />'
                    + 'Loading'
                + '</div>'
              );

              jQuery.get( "https://bookinglive.io/api/company/" + jQuery("#connect_company_name").val() + "/item", function( data ) {
                var html = '<p>Account found with the following items</p><ul style="list-style:disc;margin-left:20px;">';
                jQuery.each(data.data.page_results, function(key, item) {
                  html += '<li>' + item.name + '</li>';
                });
                html += '</ul>';
                jQuery('#connect_company_name_feedback').html(html);
              }, "json" ).fail(function(){
                jQuery('#connect_company_name_feedback').html(
                  '<p>Error: Company slug could not be found.</p>'
                );
              });
              return false;
            });
          });
        </script>
	<?php
}

function bl_display_connect_fields()
{
	add_settings_section("section", "All Settings", null, "connect-options");

	add_settings_field("connect_company_name", "Connect slug", "bl_display_slug_field", "connect-options", "section");
	register_setting("section", "connect_company_name");
}

add_action("admin_init", "bl_display_connect_fields");



// Display the meta box
add_action( 'admin_menu', 'bl_connect_metabox' );
function bl_connect_metabox() {
    add_meta_box( 'bookxx', 'BookingLive Connect', 'bl_connect_metabox_content', 'book', 'side', 'default' );
}
function bl_connect_metabox_content( $post_object ) {
    $postMeta = get_post_meta($post_object->ID, '_connect-item-select');
    $value = $postMeta ? $postMeta[0] : '';

    if (!get_option('connect_company_name')) {
        echo '<p>Please link WordPress to your BookingLive Connect account.</p>';
        return;
    }

    echo '<div id="connect-item-select-container"><img src="'. plugin_dir_url( __FILE__ ) . 'loading.gif" style="display: block;margin:15px auto;" /></div>
        <script>
            jQuery(document).ready(function(){
                 jQuery.get( "https://bookinglive.io/api/company/'.get_option('connect_company_name').'/item", function( data ){
                  var html = "<label>Display booking process for:</label>"
                      + "<select id=\"connect-item-select\" name=\"connect-item-select\" style=\"width: 100%;\">"
                      +"<option value=\"\">Choose product</option>";
                    jQuery.each(data.data.page_results, function(key, item) {
                        var selected = "'.$value.'"==item.id ? "selected=\"selected\"" : ""; 
                        html += "<option "+selected+" value=\""+item.id+"\">"+item.name+"</option>";  
                    });
                    html += "</select>";
                    jQuery("#connect-item-select-container").html(html);
                }, "json" ).fail(function(){
                  jQuery("#connect-item-select-container").html("Error retrieving item list");
                });
            });
        </script>
    ';
}


function connect_save_post_class_meta($post_id) {
    if (array_key_exists('connect-item-select', $_POST)) {
        update_post_meta(
            $post_id,
            '_connect-item-select',
            $_POST['connect-item-select']
        );
    }
}
add_action( 'save_post', 'connect_save_post_class_meta', 10, 2 );



function my_the_content_filter($content) {
    if ($GLOBALS['post']->post_type == 'book') {
        $postMeta = get_post_meta($GLOBALS['post']->ID, '_connect-item-select');
        $itemID = $postMeta ? $postMeta[0] : false;
        $customContent = $itemID
            ? '<iframe src="https://bookinglive.io/c/'.get_option('connect_company_name').'/book/'.$itemID.'?embed=true" data-bookinglive-embed="iframe"></iframe><script src="https://bookinglive.io/js/embed.js"></script>'
            : '';
        return $content . $customContent;
    }
    return $content;
}

add_filter( 'the_content', 'my_the_content_filter' );
