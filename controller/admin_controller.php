<?php
/**
*
* @package phpBB Extension - Delete Inactive Users
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\groupspage\controller;

use phpbb\config\config;
use phpbb\template\template;
use phpbb\log\log_interface;
use phpbb\user;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;

class admin_controller
{
	/** @var config */
	protected $config;

	/** @var template */
	protected $template;

	/** @var log_interface */
	protected $log;

	/** @var user */
	protected $user;

	/** @var request_interface */
	protected $request;

	/** @var driver_interface */
	protected $db;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * Constructor
	 *
	 * @param config				$config
	 * @param template				$template
	 * @param log_interface			$log
	 * @param user					$user
	 * @param request_interface		$request
	 * @param driver_interface		$db
	 *
	 */
	public function __construct(
		config $config,
		template $template,
		log_interface $log,
		user $user,
		request_interface $request,
		driver_interface $db
	)
	{
		$this->config 			= $config;
		$this->template 		= $template;
		$this->log 				= $log;
		$this->user 			= $user;
		$this->request 			= $request;
		$this->db				= $db;
	}

	public function display_options()
	{
		add_form_key('acp_groupspage');

		// Is the form being submitted to us?
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('acp_groupspage'))
			{
				trigger_error('FORM_INVALID');
			}

			// Set the options the user configured
			$this->set_options();

			// Add option settings change action to the admin log
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_GROUPSPAGE_SAVED');

			trigger_error($this->user->lang('GROUPSPAGE_SAVED') . adm_back_link($this->u_action));
		}

		$sql = 'SELECT group_id, group_type, group_name
			FROM ' . GROUPS_TABLE;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$groupspage_group_exceptions_options = '';

		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['group_name'] != 'BOTS')
			{
				$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];

				if (in_array($row['group_id'], explode(',', $this->config['groupspage_group_exceptions'])))
				{
					$groupspage_group_exceptions_options .= '<option value="' . $row['group_id'] . '" selected="selected">' . $group_name . '</option>';
				}
				else
				{
					$groupspage_group_exceptions_options .= '<option value="' . $row['group_id'] . '">' . $group_name . '</option>';
				}
			}
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'U_ACTION'									=> $this->u_action,
			'GROUPSPAGE_VERSION'						=> $this->config['groupspage_version'],
			'GROUPSPAGE_EXCEPTIONS' 					=> $groupspage_group_exceptions_options
		]);
	}

	protected function set_options()
	{
		$groupspage_group_exceptions = $this->request->variable('groupspage_group_exceptions', [0 => 0]);

		$this->config->set('groupspage_group_exceptions', implode(',' ,$groupspage_group_exceptions));
	}

	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
