<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * This file is part of CX Disqus Comments for ExpressionEngine
 *
 * (c) Adrian Macneil <support@exp-resso.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('CX_DISQUS_CP', 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=cx_disqus');

class Cx_disqus_mcp {

	private $_thread_map;

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library(array('table', 'disqusapi'));
		$this->EE->load->model('cx_disqus_model');

		$this->EE->cp->set_right_nav(array(
			'settings' => BASE.AMP.CX_DISQUS_CP,
			'export' => BASE.AMP.CX_DISQUS_CP.AMP.'method=export',
			'documentation' => $this->EE->cp->masked_url('http://cx-addons.com/docs/cx-disqus-comments')
		));
	}

	public function index()
	{
		$this->EE->view->cp_page_title = lang('cx_disqus');

		$data = array(
			'post_url' => CX_DISQUS_CP,
			'forum_shortname' => $this->EE->cx_disqus_model->settings['forum_shortname'],
			'secretkey' => $this->EE->cx_disqus_model->settings['secretkey'],
			'display_welcome' => FALSE,
			'error_code' => 0,
			'error_message' => '',
		);

		if ( ! empty($_POST))
		{
			if ($this->EE->input->post('reset_import') == 'y')
			{
				$this->EE->cx_disqus_model->settings = array();
			}

			$this->EE->cx_disqus_model->settings['forum_shortname'] = $this->EE->input->post('forum_shortname', TRUE);
			$this->EE->cx_disqus_model->settings['secretkey'] = $this->EE->input->post('secretkey', TRUE);
			$this->EE->cx_disqus_model->save_settings();

			$this->EE->session->set_flashdata('message_success', lang('settings_updated'));
			$this->EE->functions->redirect(BASE.AMP.$data['post_url']);
		}

		if (empty($data['forum_shortname']) OR empty($data['secretkey']))
		{
			$data['display_welcome'] = TRUE;
		}
		else
		{
			$response = $this->_test_api();
			$data['error_code'] = $response->code;
			$data['error_message'] = $response->message;
		}

		// mask links to external Disqus URLs
		$this->EE->lang->language['forum_shortname_desc'] = str_replace('DISQUS_DASHBOARD_URL',
			'<a href="'.$this->EE->cp->masked_url('http://disqus.com/dashboard/').'" target="_blank">http://disqus.com/dashboard/</a>',
			$this->EE->lang->language['forum_shortname_desc']);

		$this->EE->lang->language['secretkey_desc'] = str_replace('DISQUS_APPLICATIONS_URL',
			'<a href="'.$this->EE->cp->masked_url('http://disqus.com/api/applications/').'" target="_blank">http://disqus.com/api/applications/</a>',
			$this->EE->lang->language['secretkey_desc']);

		$this->EE->lang->language['api_error_more_info'] = str_replace('DISQUS_API_ERRORS_URL',
			'<a href="'.$this->EE->cp->masked_url('http://disqus.com/api/docs/errors/').'" target="_blank">'.lang('disqus_api_errors').'</a>',
			$this->EE->lang->language['api_error_more_info']);

		return $this->EE->load->view('settings', $data, TRUE);
	}

	public function export()
	{
		if (empty($this->EE->cx_disqus_model->settings['forum_shortname']) OR
			empty($this->EE->cx_disqus_model->settings['secretkey']) OR
			$this->_test_api()->code > 0)
			{
				$this->EE->session->set_flashdata('message_failure', lang('settings_required'));
				$this->EE->functions->redirect(BASE.AMP.CX_DISQUS_CP);
			}

		$this->EE->cp->set_breadcrumb(BASE.AMP.CX_DISQUS_CP, lang('cx_disqus'));
		$this->EE->view->cp_page_title = lang('export');

		$data = array(
			'post_url' => CX_DISQUS_CP.AMP.'method=export',
			'comment_export_count' => $this->EE->cx_disqus_model->count_export_comments()
		);

		if (isset($_POST['submit']))
		{
			$this->_export_comments();

			$this->EE->session->set_flashdata('message_success', lang('settings_updated'));
			$this->EE->functions->redirect(BASE.AMP.$data['post_url']);
		}

		return $this->EE->load->view('export', $data, TRUE);
	}

	public function advanced()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.CX_DISQUS_CP, lang('cx_disqus_module_name'));
		$this->EE->view->cp_page_title = lang('advanced');

		$data = array(
			'settings' => $this->EE->cx_disqus_model->settings
		);

		return $this->EE->load->view('advanced', $data, TRUE);
	}

	private function _export_comments()
	{
		$this->EE->disqusapi->setKey($this->EE->cx_disqus_model->settings['secretkey']);

		$this->_thread_map = array();

		// find all comments that need to be exported
		$comments = $this->EE->cx_disqus_model->find_export_comments();
		foreach ($comments as $comment)
		{
			$this->_push_comment($comment);
		}
	}

	private function _push_comment($comment)
	{
		// find out the disqus thread id
		$entry_id = (int)$comment['entry_id'];

		if (empty($this->_thread_map[$entry_id]))
		{
			// query disqus for an existing thread
			try
			{
				$thread = $this->EE->disqusapi->threads->details(array(
					'forum' => $this->EE->cx_disqus_model->settings['forum_shortname'],
					'thread' => 'ident:'.(int)$comment['entry_id']
				));
				$this->_thread_map[$entry_id] = $thread->id;
			}
			catch (DisqusAPIError $e)
			{
				// no thread found, need to create one
				$entry = $this->EE->cx_disqus_model->get_entry($comment['entry_id']);
				if (empty($entry)) return FALSE;
				$thread = $this->_push_thread($entry);

				// check it worked...
				if (empty($thread)) return FALSE;
				$this->_thread_map[$entry_id] = $thread->id;
			}
		}

		$comment_data = array(
			'thread' => $this->_thread_map[$entry_id],
			'message' => $comment['comment'],
			'date' => $comment['comment_date']
		);

		if ( ! empty($comment['name']))
		{
			// author_name longer than 30 chars throws a 500 error
			$comment_data['author_name'] = substr($comment['name'], 0, 30);
		}

		if ( ! empty($comment['email'])) $comment_data['author_email'] = trim($comment['email']);
		if ( ! empty($comment['url'])) $comment_data['author_url'] = trim($comment['url']);
		if ( ! empty($comment['ip_address'])) $comment_data['ip_address'] = $comment['ip_address'];


		try
		{
			$post = $this->EE->disqusapi->posts->create($comment_data);
			$update_data = array('cx_disqus_id' => $post->id);
			$this->EE->cx_disqus_model->update_comment($comment['comment_id'], $update_data);
		}
		catch (DisqusAPIError $e)
		{
			$this->_show_export_error($e, $comment_data);
		}
	}

	private function _push_thread($entry)
	{
		$thread_data = array(
			'forum' => $this->EE->cx_disqus_model->settings['forum_shortname'],
			'title' => $entry['title'],
			'identifier' => $entry['entry_id'],
			'date' => $entry['entry_date']
		);

		try
		{
			return $this->EE->disqusapi->threads->create($thread_data);
		}
		catch (DisqusAPIError $e)
		{
			$this->_show_export_error($e, $thread_data);
		}
	}

	private function _test_api()
	{
		$this->EE->disqusapi->setKey($this->EE->cx_disqus_model->settings['secretkey']);
		try
		{
			$response = $this->EE->disqusapi->forums->details(array('forum' => $this->EE->cx_disqus_model->settings['forum_shortname']));
			return (object)array('code' => 0, 'message' => '');
		}
		catch (DisqusAPIError $e)
		{
			return $e;
		}
	}

	private function _show_export_error($error, $data)
	{
		// display the error and offending thread/comment
		$message = '<strong>'.lang('disqus_export_error').'</strong>'.BR.BR;
		$message .= 'Error '.$error->code.': '.$error->message.BR;

		ob_start();
		print_r($data);
		$data_str = ob_get_contents();
		ob_end_clean();
		$message .= '<pre>'.htmlspecialchars($data_str).'</pre>';

		show_error($message);
	}
}

/* End of file mcp.cx_disqus.php */
