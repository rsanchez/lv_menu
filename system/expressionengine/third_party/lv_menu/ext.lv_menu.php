<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lv_menu_ext
{
	public $settings = array();
	public $name = 'LV Menu';
	public $version = '1.0.0';
	public $description = 'Adds Low Variables menu to Content menu.';
	public $settings_exist = 'n';
	public $docs_url = 'http://github.com/rsanchez/lv_menu';
	
	/**
	 * constructor
	 * 
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	/**
	 * activate_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		$hook_defaults = array(
			'class' => __CLASS__,
			'settings' => '',
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);
		
		$hooks[] = array(
			'method' => 'cp_menu_array',
			'hook' => 'cp_menu_array'
		);
		
		foreach ($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
	}
	
	/**
	 * update_extension
	 * 
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
	}
	
	/**
	 * disable_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @return	void
	 */
	public function settings()
	{
		$settings = array();
		
		return $settings;
	}
	
	public function cp_menu_array($menu)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$menu = $this->EE->extensions->last_call;
		}

		if (isset($menu['content']) && is_array($menu['content']) && $this->EE->cp->allowed_group('can_access_addons', 'can_access_modules'))
		{
			$variables = array();
            
            $query = $this->EE->db->join('module_member_groups', 'module_member_groups.module_id = modules.module_id')
                                    ->where('module_member_groups.group_id', $this->EE->session->userdata('group_id'))
                                    ->where('module_name', 'Low_variables')
                                    ->get('modules');
            
            if ($query->num_rows() === 0)
            {
                return $menu;
            }
            
            $query->free_result();
			
			$query = $this->EE->db->select('settings')
						 ->limit(1)
						 ->where('class', 'Low_variables_ext')
						 ->get('extensions');
			
			if ($query->num_rows() === 0)
			{
				return $menu;
			}
			
			$settings = @unserialize($query->row('settings'));

            $can_manage = TRUE;
			
			$query->free_result();
			
			if (isset($settings['can_manage']) && ! in_array($this->EE->session->userdata('group_id'), $settings['can_manage']))
			{
                $can_manage = FALSE;

                $this->EE->db->where('is_hidden', 'n');
			}

            $query = $this->EE->db->join('global_variables', 'global_variables.variable_id = low_variables.variable_id')
                         ->where('site_id', $this->EE->config->item('site_id'))
                         ->order_by('variable_order')
                         ->get('low_variables');

            if ($query->num_rows() === 0)
            {
                return $menu;
            }
			
			$all_variables = $query->result();
			
			$query->free_result();
			
			$query = $this->EE->db->where('site_id', $this->EE->config->item('site_id'))
									->order_by('group_order', 'asc')
									->get('low_variable_groups');
			
			foreach ($query->result() as $row)
			{
				$this->EE->lang->language['nav_'.$row->group_label] = $row->group_label;
				
				$variable_groups[$row->group_id] = $row->group_label;
			}
			
			$query->free_result();
			
			foreach ($all_variables as $row)
			{
                $group = $row->group_id ? $variable_groups[$row->group_id] : 'ungrouped';
				
				if ( ! isset($variables[$group]))
				{
					$variables[$group] = array('nav_edit_all' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables'.AMP.'group_id='.$row->group_id);
				}
				
				$this->EE->lang->language['nav_'.$row->variable_name] = $row->variable_label;
				
				$variables[$group][$row->variable_name] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables'.AMP.'group_id='.$row->group_id;
			}

			$variables['nav_edit_all'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables';

			$this->EE->lang->loadfile('lv_menu', 'lv_menu');

            if ($can_manage)
            {
    			if ($variables)
    			{
    				$variables[] = '----';
    			}

    			$variables['manage_variables'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables'.AMP.'method=manage';

    			$variables['create_variable'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables'.AMP.'method=manage'.AMP.'id=new';

    			$variables['create_group'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=low_variables'.AMP.'method=edit_group'.AMP.'id=new';

    			$variables['variable_settings'] = BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=low_variables';
            }

            if (count($variables) === 2 && is_array(current($variables)))
            {
                $variables = current($variables);
            }

			$menu['content'] = array_merge(
				array_slice($menu['content'], 0, 3),
				array('variables' => $variables),
				array_slice($menu['content'], 3)
			);
		}

		return $menu;
	}
}

/* End of file ext.lv_menu.php */
/* Location: ./system/expressionengine/third_party/lv_menu/ext.lv_menu.php */