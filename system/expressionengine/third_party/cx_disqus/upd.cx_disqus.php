<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * This file is part of CX Disqus Comments for ExpressionEngine
 *
 * (c) Adrian Macneil <support@exp-resso.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include(PATH_THIRD.'cx_disqus/config.php');

class Cx_disqus_upd {

	public $version = CX_DISQUS_VERSION;

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	public function install()
	{
		if ( ! $this->EE->db->table_exists('comments'))
		{
			show_error(lang('comment_module_not_installed'));
		}

		$this->EE->load->dbforge();

		// register module
		$this->EE->db->insert('modules', array(
			'module_name' => CX_DISQUS_CLASS,
			'module_version' => CX_DISQUS_VERSION,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'));

		// add exp_modules settings column if it doesn't exist
		if ( ! $this->EE->db->field_exists('settings', 'modules'))
		{
			$this->EE->dbforge->add_column('modules', array(
				'settings'	=> array('type' => 'text', 'null' => TRUE)
			));
		}

		// add cx_disqus_id column to comments table if it doesn't exist
		if ( ! $this->EE->db->field_exists('cx_disqus_id', 'comments'))
		{
			$this->EE->dbforge->add_column('comments', array(
				'cx_disqus_id' => array('type' => 'char', 'constraint' => 32, 'null' => TRUE)
			));
		}

		// register sync action
		$this->EE->db->insert('actions', array(
			'class' => CX_DISQUS_CLASS,
			'method' => 'act_sync'
		));

		return TRUE;
	}

	public function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->where('module_name', CX_DISQUS_CLASS);
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', CX_DISQUS_CLASS);
		$this->EE->db->delete('actions');

		return TRUE;
	}

	public function update($current = '')
	{
		if ($current < '1.1.0') $this->_update_110();

		return TRUE;
	}

	protected function _update_110()
	{
		// insert new action
		$this->EE->db->where('class', CX_DISQUS_CLASS);
		$this->EE->db->where('method', 'act_sync');
		if ($this->EE->db->count_all_results('actions') == 0)
		{
			$this->EE->db->insert('actions', array(
				'class' => CX_DISQUS_CLASS,
				'method' => 'act_sync'
			));
		}
	}
}

/* End of file upd.cx_disqus.php */