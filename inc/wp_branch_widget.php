<?php
/*
 * Wp branch Widget
 * Defines the widget to be used to showcase single or multiple branches
 */


//main widget used for displaying branches
class wp_branch_widget extends WP_widget{
	
	//initialise widget values
	public function __construct(){
		//set base values for the widget (override parent)
		parent::__construct(
			'wp_branch_widget',
			'WP Branch Widget', 
			array('description' => 'A widget that displays your branches')
		);
		add_action('widgets_init',array($this,'register_wp_branch_widgets'));
	}
	
	//handles public display of the widget
	//$args - arguments set by the widget area, $instance - saved values
	public function widget( $args, $instance ) {
		
		//get wp_simple_branch class (as it builds out output)
		global $wp_simple_branches;
		
		//pass any arguments if we have any from the widget
		$arguments = array();
		//if we specify a branch
		
		//if we specify a single branch
		if($instance['branch_id'] != 'default'){
			$arguments['branch_id'] = $instance['branch_id'];
		}
		//if we specify a number of branches
		if($instance['number_of_branches'] != 'default'){
			$arguments['number_of_branches'] = $instance['number_of_branches'];
		}
		
		//get the output
		$html = '';
		
		$html .= $args['before_widget'];
		$html .= $args['before_title'];
		$html .= 'Branches';
		$html .= $args['after_title'];
		//uses the main output function of the branch class
		$html .= $wp_simple_branches->get_branches_output($arguments);
		$html .= $args['after_widget'];
		
		echo $html;
	}
	
	//handles the back-end admin of the widget
	//$instance - saved values for the form
	public function form($instance){
		//collect variables 
		$branch_id = (isset($instance['branch_id']) ? $instance['branch_id'] : 'default');
		$number_of_branches = (isset($instance['number_of_branches']) ? $instance['number_of_branches'] : 5);
		
		?>
		<p>Select your options below</p>
		<p>
			<label for="<?php echo $this->get_field_name('branch_id'); ?>">Branch to display</label>
			<select class="widefat" name="<?php echo $this->get_field_name('branch_id'); ?>" id="<?php echo $this->get_field_id('branch_id'); ?>" value="<?php echo $branch_id; ?>">
				<option value="default">All Branches</option>
				<?php
				$args = array(
					'posts_per_page'	=> -1,
					'post_type'			=> 'wp_branches'
				);
				$branches = get_posts($args);
				if($branches){
					foreach($branches as $branch){
						if($branch->ID == $branch_id){
							echo '<option selected value="' . $branch->ID . '">' . get_the_title($branch->ID) . '</option>';
						}else{
							echo '<option value="' . $branch->ID . '">' . get_the_title($branch->ID) . '</option>';
						}
					}
				}
				?>
			</select>
		</p>
		<p>
			<small>If you want to display multiple branches select how many below</small><br/>
			<label for="<?php echo $this->get_field_id('number_of_branches'); ?>">Number of Branches</label>
			<select class="widefat" name="<?php echo $this->get_field_name('number_of_branches'); ?>" id="<?php echo $this->get_field_id('number_of_branches'); ?>" value="<?php echo $number_of_branches; ?>">
				<option value="default">All</option>
			</select>
		</p>
		<?php
	}
	
	//handles updating the widget 
	//$new_instance - new values, $old_instance - old saved values
	public function update($new_instance, $old_instance){

		$instance = array();
		
		$instance['branch_id'] = $new_instance['branch_id'];
		$instance['number_of_branches'] = $new_instance['number_of_branches'];
		
		return $instance;
	}
	
	//registers our widget for use
	public function register_wp_branch_widgets(){
		register_widget('wp_branch_widget');
	}
}
$wp_branch_widget = new wp_branch_widget;

?>