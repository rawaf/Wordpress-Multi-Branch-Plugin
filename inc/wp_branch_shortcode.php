<?php
/*
 * Wp Branch Shortcode
 * A shortcode created to display a branch or series of branches when used in the editor or other areas
 */

 
 //defines the functionality for the branch shortcode
 class wp_branch_shortcode{
 	
	//on initialize
	public function __construct(){
		add_action('init', array($this,'register_branch_shortcodes')); //shortcodes
	}

	//branch shortcode
	public function register_branch_shortcodes(){
		add_shortcode('wp_branches', array($this,'branch_shortcode_output'));
	}
	
	//shortcode display
	public function branch_shortcode_output($atts, $content = '', $tag){
			
		//get the global wp_simple_branches class
		global $wp_simple_branches;
			
		//build default arguments
		$arguments = shortcode_atts(array(
			'branch_id' => '',
			'number_of_branches' => -1)
		,$atts,$tag);
		
		//uses the main output function of the branch class
		$html = $wp_simple_branches->get_branches_output($arguments);
		
		return $html;
	}

 }
 $wp_branch_shortcode = new wp_branch_shortcode;

 
 

?>