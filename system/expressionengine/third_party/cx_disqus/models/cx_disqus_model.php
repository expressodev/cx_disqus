<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * This file is part of CX Disqus Comments for ExpressionEngine
 *
 * (c) Adrian Macneil <support@exp-resso.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Cx_disqus_model extends CI_Model
{
	public $settings;
	protected $_site_id;
	protected $_all_site_settings;

	public function __construct()
	{
		parent::__construct();

		$this->_site_id = $this->config->item('site_id');

		$this->db->where('module_name', 'Cx_disqus');
		$row = $this->db->get('modules')->row_array();
		$this->_all_site_settings = unserialize(base64_decode($row['settings']));

		if (isset($this->_all_site_settings['forum_shortname']))
		{
			// upgrade settings array to be MSM-compatible
			$this->settings = $this->_all_site_settings;
			$this->_all_site_settings = array($this->_site_id => $this->settings);
			$this->save_settings();
		}
		elseif (isset($this->_all_site_settings[$this->_site_id]))
		{
			// load settings for current site
			$this->settings = $this->_all_site_settings[$this->_site_id];
		}
		else
		{
			// no settings for current site
			$this->settings = array();
		}

		foreach (array('forum_shortname', 'secretkey', 'last_api_request', 'last_comment_date') as $field)
		{
			if ( ! isset($this->settings[$field]))
			{
				$this->settings[$field] = '';
			}
		}
	}

	public function save_settings()
	{
		$this->_all_site_settings[$this->_site_id] = $this->settings;
		$this->db->where('module_name', 'Cx_disqus');
		$this->db->update('modules', array('settings' => base64_encode(serialize($this->_all_site_settings))));
	}

	public function find_comments($entry_id)
	{
		$this->db->where('entry_id', (int)$entry_id);
		$this->db->order_by('comment_date', 'desc');
		return $this->db->get('comments')->result_array();
	}

	public function count_export_comments()
	{
		$this->db->from('comments');
		$this->db->where('site_id', $this->_site_id);
		$this->db->where('cx_disqus_id IS NULL');
		return $this->db->count_all_results();
	}

	public function find_export_comments()
	{
		$this->db->where('cx_disqus_id IS NULL');
		$this->db->where('site_id', $this->_site_id);
		$this->db->order_by('comment_date', 'asc');
		$this->db->limit(1000);
		return $this->db->get('comments')->result_array();
	}

	public function update_comment($comment_id, $data)
	{
		$this->db->where('comment_id', (int)$comment_id);
		$this->db->update('comments', $data);
	}

	public function get_entry($entry_id)
	{
		$this->db->where('entry_id', (int)$entry_id);
		return $this->db->get('channel_titles')->row_array();
	}

	public function insert_disqus_comment($comment)
	{
		$data = array('status' => 'o');
		$data['cx_disqus_id'] = $comment->id;

		$data['entry_id'] = (int)$comment->thread->identifiers[0];
		if ($data['entry_id'])
		{
			// figure out the which channel the comment entry belongs to
			$this->db->where('entry_id', $data['entry_id']);
			$entry = $this->db->get('channel_titles')->row_array();
			$data['channel_id'] = $entry['channel_id'];
			$data['site_id'] = $entry['site_id'];
		}

		$data['name'] = $comment->author->name;
		$data['email'] = $comment->author->email;
		$data['url'] = $comment->author->url;
		$data['ip_address'] = $comment->ipAddress;
		$data['comment_date'] = strtotime($comment->createdAt.' UTC');
		$data['comment'] = $comment->message;

		// check we haven't already imported this comment
		$this->db->where('cx_disqus_id', $data['cx_disqus_id']);
		if ($this->db->count_all_results('comments') == 0)
		{
			$this->db->insert('comments', $data);
		}

		if ($this->settings['last_comment_date'] < $data['comment_date'])
		{
			$this->settings['last_comment_date'] = $data['comment_date'];
		}

		return $data['entry_id'];
	}

	/**
	 * Recount entry comments
	 *
	 * Based on code from comment_model.php
	 */
	public function recount_entry_comments($entry_ids)
	{
		foreach (array_unique($entry_ids) as $entry_id)
		{
			$this->db->select('max(comment_date) as max_date, count(*) as count');
			$this->db->where('status', 'o');
			$this->db->where('entry_id', (int)$entry_id);
			$comments = $this->db->get('comments')->row_array();

			$data = array('comment_total' => $comments['count'], 'recent_comment_date' => $comments['max_date']);
			$this->db->where('entry_id', (int)$entry_id);
			$this->db->update('channel_titles', $data);
		}
	}
}

/* End of file cx_disqus_model.php */