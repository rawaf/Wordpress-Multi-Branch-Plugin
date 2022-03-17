<?php
/*
Plugin Name: Wordpress Multi Branch Plugin
Plugin URI:  https://github.com/rawaf
Description: Creates an interfaces to manage store / business branches on your website. Useful for showing branch based information quickly. Includes both a widget and shortcode for ease of use.
Version:     1.0.0
Author:      Ahmed Rawaf
Author URI:  http://www.rawaf.net
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

class wp_simple_branch{
	
	//properties
	private $wp_branch_trading_hour_days = array();
	
	//magic function (triggered on initialization)
	public function __construct(){
		
		add_action('init', array($this,'set_branch_trading_hour_days')); //sets the default trading hour days (used by the content type)
		add_action('init', array($this,'register_branch_content_type')); //register branch content type
		add_action('add_meta_boxes', array($this,'add_branch_meta_boxes')); //add meta boxes
		add_action('save_post_wp_branches', array($this,'save_branch')); //save branch
		add_action('admin_enqueue_scripts', array($this,'enqueue_admin_scripts_and_styles')); //admin scripts and styles
		add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles')); //public scripts and styles
		add_filter('the_content', array($this,'prepend_branch_meta_to_content')); //gets our meta data and dispayed it before the content
		
		register_activation_hook(__FILE__, array($this,'plugin_activate')); //activate hook
		register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); //deactivate hook
		
	}
	
	//set the default trading hour days (used in our admin backend)
	public function set_branch_trading_hour_days(){
		
		//set the default days to use for the trading hours
		$this->wp_branch_trading_hour_days = apply_filters('wp_branch_trading_hours_days', 
			array('monday' => 'Monday',
				  'tuesday' => 'Tuesday',
				  'wednesday' => 'Wednesday',
				  'thursday' => 'Thursday',
				  'friday' => 'Friday',
				  'saturday' => 'Saturday',
				  'sunday' => 'Sunday',
			)
		);		
	}
	
	//register the branch content type
	public function register_branch_content_type(){
		 //Labels for post type
		 $labels = array(
            'name'               => 'Branches',
            'singular_name'      => 'Branch',
            'menu_name'          => 'Branches',
            'name_admin_bar'     => 'Branch',
            'add_new'            => 'Add New', 
            'add_new_item'       => 'Add New Branch',
            'new_item'           => 'New Branch', 
            'edit_item'          => 'Edit Branch',
            'view_item'          => 'View Branch',
            'all_items'          => 'All Branches',
            'search_items'       => 'Search Branches',
            'parent_item_colon'  => 'Parent Branch:', 
            'not_found'          => 'No Branches found.', 
            'not_found_in_trash' => 'No Branches found in Trash.',
        );
        //arguments for post type
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable'=> true,
            'show_ui'           => true,
            'show_in_nav'       => true,
            'query_var'         => true,
            'hierarchical'      => false,
            'supports'          => array('title','thumbnail','editor'),
            'has_archive'       => true,
            'menu_position'     => 20,
            'show_in_admin_bar' => true,
            'menu_icon'         => 'dashicons-location-alt',
            'rewrite'			=> array('slug' => 'branches', 'with_front' => 'true')
        );
        //register post type
        register_post_type('wp_branches', $args);
	}

	//adding meta boxes for the branch content type*/
	public function add_branch_meta_boxes(){
		
		add_meta_box(
			'wp_branch_meta_box', //id
			'Branch Information', //name
			array($this,'branch_meta_box_display'), //display function
			'wp_branches', //post type
			'normal', //branch
			'default' //priority
		);
	}
	
	//display function used for our custom branch meta box*/
	public function branch_meta_box_display($post){
		
		//set nonce field
		wp_nonce_field('wp_branch_nonce', 'wp_branch_nonce_field');
		
		//collect variables
		$wp_branch_phone = get_post_meta($post->ID,'wp_branch_phone',true);
		$wp_branch_email = get_post_meta($post->ID,'wp_branch_email',true);
		$wp_branch_address = get_post_meta($post->ID,'wp_branch_address',true);
		
		?>
		<p>Enter additional information about your branch </p>
		<div class="field-container">
			<?php 
			//before main form elementst hook
			do_action('wp_branch_admin_form_start'); 
			?>
			<div class="field">
				<label for="wp_branch_phone">Contact Phone</label>
				<small>main contact number</small>
				<input type="tel" name="wp_branch_phone" id="wp_branch_phone" value="<?php echo $wp_branch_phone;?>"/>
			</div>
			<div class="field">
				<label for="wp_branch_email">Contact Email</label>
				<small>Email contact</small>
				<input type="email" name="wp_branch_email" id="wp_branch_email" value="<?php echo $wp_branch_email;?>"/>
			</div>
			<div class="field">
				<label for="wp_branch_address">Address</label>
				<small>Physical address of your branch</small>
				<textarea name="wp_branch_address" id="wp_branch_address"><?php echo $wp_branch_address;?></textarea>
			</div>
			<?php
			//trading hours
			if(!empty($this->wp_branch_trading_hour_days)){
				echo '<div class="field">';
					echo '<label>Trading Hours </label>';
					echo '<small> Trading hours for the branch (e.g 9am - 5pm) </small>';
					//go through all of our registered trading hour days
					foreach($this->wp_branch_trading_hour_days as $day_key => $day_value){
						//collect trading hour meta data
						$wp_branch_trading_hour_value =  get_post_meta($post->ID,'wp_branch_trading_hours_' . $day_key, true);
						//dsiplay label and input
						echo '<label for="wp_branch_trading_hours_' . $day_key . '">' . $day_key . '</label>';
						echo '<input type="text" name="wp_branch_trading_hours_' . $day_key . '" id="wp_branch_trading_hours_' . $day_key . '" value="' . $wp_branch_trading_hour_value . '"/>';
					}
				echo '</div>';
			}		
			?>
		<?php 
		//after main form elementst hook
		do_action('wp_branch_admin_form_end'); 
		?>
		</div>
		<?php
		
	}
	
	//triggered on activation of the plugin (called only once)
	public function plugin_activate(){
		
		//call our custom content type function
	 	$this->register_branch_content_type();
		//flush permalinks
		flush_rewrite_rules();
	}
	
	//trigered on deactivation of the plugin (called only once)
	public function plugin_deactivate(){
		//flush permalinks
		flush_rewrite_rules();
	}
	
	//append our additional meta data for the branch before the main content (when viewing a single branch)
	public function prepend_branch_meta_to_content($content){
			
		global $post, $post_type;
		
		//display meta only on our branches (and if its a single branch)
		if($post_type == 'wp_branches' && is_singular('wp_branches')){
			
			//collect variables
			$wp_branch_id = $post->ID;
			$wp_branch_phone = get_post_meta($post->ID,'wp_branch_phone',true);
			$wp_branch_email = get_post_meta($post->ID,'wp_branch_email',true);
			$wp_branch_address = get_post_meta($post->ID,'wp_branch_address',true);
			
			//display
			$html = '';
	
			$html .= '<section class="meta-data">';
			
			//hook for outputting additional meta data (at the start of the form)
			do_action('wp_branch_meta_data_output_start',$wp_branch_id);
			
			$html .= '<p>';
			//phone
			if(!empty($wp_branch_phone)){
				$html .= '<b>Branch Phone</b>' . $wp_branch_phone . '</br>';
			}
			//email
			if(!empty($wp_branch_email)){
				$html .= '<b>Branch Email</b>' . $wp_branch_email . '</br>';
			}
			//address
			if(!empty($wp_branch_address)){
				$html .= '<b>Branch Address</b>' . $wp_branch_address . '</br>';
			}
			$html .= '</p>';

			//branch
			if(!empty($this->wp_branch_trading_hour_days)){
				$html .= '<p>';
				$html .= '<b>Branch Trading Hours </b></br>';
				foreach($this->wp_branch_trading_hour_days as $day_key => $day_value){
					$trading_hours = get_post_meta($post->ID, 'wp_branch_trading_hours_' . $day_key , true);
					$html .= '<b>' . $day_key . '</b>' . $trading_hours . '</br>';
				}
				$html .= '</p>';
			}

			//hook for outputting additional meta data (at the end of the form)
			do_action('wp_branch_meta_data_output_end',$wp_branch_id);
			
			$html .= '</section>';
			$html .= $content;
			
			return $html;	
				
			
		}else{
			return $content;
		}

	}

	//main function for displaying branches (used for our shortcodes and widgets)
	public function get_branches_output($arguments = ""){
			
		//default args
		$default_args = array(
			'branch_id'	=> '',
			'number_of_branches'	=> -1
		);
		
		//update default args if we passed in new args
		if(!empty($arguments) && is_array($arguments)){
			//go through each supplied argument
			foreach($arguments as $arg_key => $arg_val){
				//if this argument exists in our default argument, update its value
				if(array_key_exists($arg_key, $default_args)){
					$default_args[$arg_key] = $arg_val;
				}
			}
		}
		
		//output
		$html = '';

		$branch_args = array(
			'post_type'		=> 'wp_branches',
			'posts_per_page'=> $default_args['number_of_branches'],
			'post_status'	=> 'publish'
		);
		//if we passed in a single branch to display
		if(!empty($default_args['branch_id'])){
			$branch_args['include'] = $default_args['branch_id'];
		}
		
		$branches = get_posts($branch_args);
		//if we have branches 
		if($branches){
			$html .= '<article class="branch_list cf">';
			//foreach branch
			foreach($branches as $branch){
				$html .= '<section class="branch">';
					//collect branch data
					$wp_branch_id = $branch->ID;
					$wp_branch_title = get_the_title($wp_branch_id);
					$wp_branch_thumbnail = get_the_post_thumbnail($wp_branch_id,'thumbnail');
					$wp_branch_content = apply_filters('the_content', $branch->post_content);
					if(!empty($wp_branch_content)){
						$wp_branch_content = strip_shortcodes(wp_trim_words($wp_branch_content, 40, '...'));
					}
					$wp_branch_permalink = get_permalink($wp_branch_id);
					$wp_branch_phone = get_post_meta($wp_branch_id,'wp_branch_phone',true);
					$wp_branch_email = get_post_meta($wp_branch_id,'wp_branch_email',true);
					
					//title
					$html .= '<h2 class="title">';
						$html .= '<a href="' . $wp_branch_permalink . '" title="view branch">';
							$html .= $wp_branch_title;
						$html .= '</a>';
					$html .= '</h2>';
					
				
					//image & content
					if(!empty($wp_branch_thumbnail) || !empty($wp_branch_content)){
								
						$html .= '<p class="image_content">';
						if(!empty($wp_branch_thumbnail)){
							$html .= $wp_branch_thumbnail;
						}
						if(!empty($wp_branch_content)){
							$html .=  $wp_branch_content;
						}
						
						$html .= '</p>';
					}
					
					//phone & email output
					if(!empty($wp_branch_phone) || !empty($wp_branch_email)){
						$html .= '<p class="phone_email">';
						if(!empty($wp_branch_phone)){
							$html .= '<b>Phone: </b>' . $wp_branch_phone . '</br>';
						}
						if(!empty($wp_branch_email)){
							$html .= '<b>Email: </b>' . $wp_branch_email;
						}
						$html .= '</p>';
					}
					
					//readmore
					$html .= '<a class="link" href="' . $wp_branch_permalink . '" title="view branch">View Branch</a>';
				$html .= '</section>';
			}
			$html .= '</article>';
			$html .= '<div class="cf"></div>';
		}
		
		return $html;
	}
	
	
	
	//triggered when adding or editing a branch
	public function save_branch($post_id){
		
		//check for nonce
		if(!isset($_POST['wp_branch_nonce_field'])){
			return $post_id;
		}	
		//verify nonce
		if(!wp_verify_nonce($_POST['wp_branch_nonce_field'], 'wp_branch_nonce')){
			return $post_id;
		}
		//check for autosave
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
			return $post_id;
		}
	
		//get our phone, email and address fields
		$wp_branch_phone = isset($_POST['wp_branch_phone']) ? sanitize_text_field($_POST['wp_branch_phone']) : '';
		$wp_branch_email = isset($_POST['wp_branch_email']) ? sanitize_text_field($_POST['wp_branch_email']) : '';
		$wp_branch_address = isset($_POST['wp_branch_address']) ? sanitize_text_field($_POST['wp_branch_address']) : '';
		
		//update phone, memil and address fields
		update_post_meta($post_id, 'wp_branch_phone', $wp_branch_phone);
		update_post_meta($post_id, 'wp_branch_email', $wp_branch_email);
		update_post_meta($post_id, 'wp_branch_address', $wp_branch_address);
		
		//search for our trading hour data and update
		foreach($_POST as $key => $value){
			//if we found our trading hour data, update it
			if(preg_match('/^wp_branch_trading_hours_/', $key)){
				update_post_meta($post_id, $key, $value);
			}
		}
		
		//branch save hook 
		//used so you can hook here and save additional post fields added via 'wp_branch_meta_data_output_end' or 'wp_branch_meta_data_output_end'
		do_action('wp_branch_admin_save',$post_id);
		
	}
	
	//enqueue scripts and styles on the back end
	public function enqueue_admin_scripts_and_styles(){
		wp_enqueue_style('wp_branch_admin_styles', plugin_dir_url(__FILE__) . '/css/wp_branch_admin_styles.css');
	}
	
	//enqueues scripts and styled on the front end
	public function enqueue_public_scripts_and_styles(){
		wp_enqueue_style('wp_branch_public_styles', plugin_dir_url(__FILE__). '/css/wp_branch_public_styles.css');
		
	}
	
}
$wp_simple_branches = new wp_simple_branch;

//include shortcodes
include(plugin_dir_path(__FILE__) . 'inc/wp_branch_shortcode.php');
//include widgets
include(plugin_dir_path(__FILE__) . 'inc/wp_branch_widget.php');



?>