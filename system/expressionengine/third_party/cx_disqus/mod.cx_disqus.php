<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * This file is part of CX Disqus Comments for ExpressionEngine
 *
 * (c) Adrian Macneil <support@exp-resso.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Cx_disqus {

	const API_THROTTLE = 600; // seconds between API requests

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->model('cx_disqus_model');
	}

	public function comments()
	{
		$this->EE->load->library('javascript');

		$forum = $this->EE->cx_disqus_model->settings['forum_shortname'];

		$entry_id = (int)$this->EE->TMPL->fetch_param('entry_id');
		if ($entry_id <= 0)
		{
			return lang('invalid_parameter').' entry_id';
		}

		$title = $this->EE->TMPL->fetch_param('title');
		if (empty($title))
		{
			$entry = $this->EE->cx_disqus_model->get_entry($entry_id);
			if (empty($entry))
			{
				return lang('invalid_parameter').' entry_id';
			}

			$title = $entry['title'];
		}

		$dev_mode = $this->EE->TMPL->fetch_param('developer');

		$tagdata = trim($this->EE->TMPL->tagdata);
		if (empty($tagdata))
		{
			$tagdata = <<<EOT
<div>
	{comment}<br />
	<small>Posted by {name} on {comment_date format="%Y-%m-%d %H:%i:%s"}</small>
</div>
EOT;
		}

		$tag_vars = $this->EE->cx_disqus_model->find_comments($entry_id);

		$out = '<div id="disqus_thread"><noscript>';

		if ( ! empty($tag_vars))
		{
			$out .= $this->EE->TMPL->parse_variables($tagdata, $tag_vars);
		}

		$out .= '</noscript></div>';

		$out .= '
<script type="text/javascript">
	var disqus_shortname = '.json_encode($forum).';
	var disqus_identifier = '.json_encode((string)$entry_id).';
	var disqus_title = '.json_encode($title).';';

		if ($dev_mode == 'yes')
		{
			$out .= "\n\tvar disqus_developer = 1;";
		}

		$out .= '
	(function() {
		var dsq = document.createElement("script"); dsq.type = "text/javascript"; dsq.async = true;
		dsq.src = "https://" + disqus_shortname + ".disqus.com/embed.js";
		(document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0]).appendChild(dsq);
	})();';

		// determine whether we need to run a comment sync
		$sync_interval = self::API_THROTTLE;
		if ($this->EE->TMPL->fetch_param('sync') !== FALSE)
		{
			$sync_interval = (int)$this->EE->TMPL->fetch_param('sync');
		}

		if ($this->EE->cx_disqus_model->settings['last_api_request'] < ($this->EE->localize->now - $sync_interval))
		{
			// ask the client to call our cron function via AJAX
			$act_url = $this->EE->functions->fetch_site_index().QUERY_MARKER.
				'ACT='.$this->EE->functions->fetch_action_id('Cx_disqus', 'act_sync').'&sync='.$sync_interval;
			$out .= '
	(function() { if (window.XMLHttpRequest) {
		var xmlhttp = new XMLHttpRequest(); xmlhttp.open("GET", "'.$act_url.'", true); xmlhttp.send();
	}})();';
		}

		$out .= "\n</script>";
		return $out;
	}

	/**
	 * Sync cron function
	 *
	 * Sync comments with Disqus. Normally called via AJAX every 10 minutes
	 */
	public function act_sync()
	{
		// determine whether we need to run the comment sync
		// (another client may have already called this function)
		$sync_interval = self::API_THROTTLE;
		if ($this->EE->input->get('sync') !== FALSE)
		{
			$sync_interval = (int)$this->EE->input->get('sync');
		}

		if ($this->EE->cx_disqus_model->settings['last_api_request'] >= ($this->EE->localize->now - $sync_interval))
		{
			exit('NOT REQUIRED');
		}

		// record the current time, to prevent another sync starting
		$this->EE->cx_disqus_model->settings['last_api_request'] = $this->EE->localize->now;
		$this->EE->cx_disqus_model->save_settings();

		$this->EE->load->library('disqusapi');
		$this->EE->disqusapi->setKey($this->EE->cx_disqus_model->settings['secretkey']);

		$query = array(
			'forum' => $this->EE->cx_disqus_model->settings['forum_shortname'],
			'related' => 'thread',
			'limit' => 100,
			'order' => 'asc'
		);

		$latest_comment_date = $this->EE->cx_disqus_model->settings['last_comment_date'];
		if ( ! empty($latest_comment_date) > 0)
		{
			$query['since'] = $latest_comment_date + 1;
		}

		$response = $this->EE->disqusapi->forums->listPosts($query);
		$entry_ids = array();

		foreach ($response as $comment)
		{
			$entry_ids[] = $this->EE->cx_disqus_model->insert_disqus_comment($comment);
		}

		$this->EE->cx_disqus_model->save_settings();

		// update comment counts
		if ( ! empty($entry_ids))
		{
			$this->EE->cx_disqus_model->recount_entry_comments($entry_ids);
		}

		exit('OK');
	}
}

/* End of file mod.cx_disqus.php */
