<?php

/*
 * User Role Editor plugin: advertisement showing class
 * Author: Vladimir Garagulya
 * email: vladimir@shinephp.com
 * site: http://shinephp.com
 * 
 */

class URE_Advertisement {
	
	private $slots = array(0=>'');
				
	function __construct() {
		
		$used = array(-1);
		
		$index = $this->rand_unique( $used );
		$this->slots[$index] = $this->admin_menu_editor();
		$used[] = $index;
		
		$index = $this->rand_unique( $used );
		$this->slots[$index] = $this->clearfy();
		$used[] = $index;
    				
	}
	// end of __construct
	
	
	/**
	 * Returns random number not included into input array
	 * 
	 * @param array $used - array of numbers used already
	 * 
	 * @return int
	 */
	private function rand_unique( $used = array(-1) ) {
		$index = rand(0, 2);
		while (in_array($index, $used)) {
			$index = rand(0, 2);
		}
		
		return $index;
	}
	// return rand_unique()
	
	
	// content of Admin Menu Editor advertisement slot
	private function admin_menu_editor() {
	
		$output = '
			<div style="text-align: center;">
				<a href="https://adminmenueditor.com/?utm_source=UserRoleEditor&utm_medium=banner&utm_campaign=Plugins" target="_new" >
					<img src="'. URE_PLUGIN_URL . 'images/admin-menu-editor-pro.jpg' .'" alt="Admin Menu Editor Pro" 
									title="Move, rename, hide, add admin menu items, restrict access" width="250" height="250" />
				</a>
			</div>  
			';
		
		return $output;
	}
	// end of admin_menu_editor()
	
	
	// content of Clearfy advertisement slot
	private function clearfy() {
	
		$output = '
			<div style="text-align: center;">
				<a href="https://clearfy.pro/?utm_source=wordpress.org&utm_campaign=user-role-editor" target="_new" >
					<img src="'. URE_PLUGIN_URL . 'images/clearfy.jpg' .'" alt="Clearfy" title="Disable unused WordPress features"
									 width="250" height="250" />
				</a>
			</div>  
			';
		
		return $output;
	}
	// end of clearfy()
	  			
	
	/**
     * Output all existed ads slots
     */
    public function display() {
?>
    <div id="ure-sidebar" class="ure_table_cell" >
<?php
        foreach ($this->slots as $slot) {
            echo $slot . "\n";
        }
?>
    </div>     
        <?php
    }

        // end of display()
    }
// end of ure_Advertisement