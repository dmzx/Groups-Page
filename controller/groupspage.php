<?php
/**
*
* @package phpBB Extension - Groups Page
* @copyright (c) 2017 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\groupspage\controller;

use phpbb\exception\http_exception;
use phpbb\template\template;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\cache\service;

class groupspage
{
	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var auth */
	protected $auth;

	/** @var db_interface */
	protected $db;

	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/** @var service */
	protected $cache;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/**
	* Constructor
	* @param template		 	$template
	* @param user				$user
	* @param auth				$auth
	* @param db_interface		$db
	* @param config				$config
	* @param helper		 		$helper
	* @param service			$cache
	* @param string 			$root_path
	* @param string 			$php_ext
	*/
	public function __construct(
		template $template,
		user $user,
		auth $auth,
		db_interface $db,
		config $config,
		helper $helper,
		service $cache,
		$root_path,
		$php_ext
	)
	{
		$this->template 	= $template;
		$this->user 		= $user;
		$this->auth 		= $auth;
		$this->db 			= $db;
		$this->config 		= $config;
		$this->helper 		= $helper;
		$this->cache 		= $cache;
		$this->root_path 	= $root_path;
		$this->php_ext 		= $php_ext;
	}

	public function handle_groupspage()
	{
		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			if (!$this->user->data['is_registered'])
			{
				login_box();
			}
			throw new http_exception(403, 'NOT_AUTHORISED');
		}

		$board_url = generate_board_url() . '/';

		$this->user->add_lang_ext('dmzx/groupspage', 'common');

		// what groups is the user a member of?
		// this allows hidden groups to display
		$sql = 'SELECT group_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_id = ' . (int) $this->user->data['user_id'] . '
				AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$in_group = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		// groups not displayed
		// you can add to the array if wanted
		// by adding the group name to ignore into the array
		// default group names are GUESTS REGISTERED REGISTERED_COPPA GLOBAL_MODERATORS ADMINISTRATORS BOTS
		$groups_not_display = array('GUESTS', 'BOTS');

		// don't want coppa group?
		if (!$this->config['coppa_enable'])
		{
			$no_coppa = array('REGISTERED_COPPA');
			$groups_not_display = array_merge($groups_not_display, $no_coppa);

			//free up a bit 'o memory
			unset($no_coppa);
		}

		// get the groups
		$sql = 'SELECT *
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $this->db->sql_in_set('group_name', $groups_not_display, true) . '
			ORDER BY group_name';
		$result = $this->db->sql_query($sql);

		$group_rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		// Grab rank information for later
		$ranks = $this->cache->obtain_ranks();

		if ($total_groups = count($group_rows))
		{
			// Obtain list of users of each group
			$sql = 'SELECT u.user_id, u.username, u.username_clean, u.user_colour, ug.group_id, ug.group_leader
				FROM ' . USER_GROUP_TABLE . ' ug, ' . USERS_TABLE . ' u
				WHERE ug.user_id = u.user_id
					AND ug.user_pending = 0
					AND u.user_id <> ' . ANONYMOUS . '
				ORDER BY ug.group_leader DESC, u.username ASC';
			$result = $this->db->sql_query($sql);

			$group_users = $group_leaders = array();
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($row['group_leader'])
				{
					$group_leaders[$row['group_id']][] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				}
				else
				{
					$group_users[$row['group_id']][] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				}
			}
			$this->db->sql_freeresult($result);

			for ($i = 0; $i < $total_groups; $i++)
			{
				$group_id = (int) $group_rows[$i]['group_id'];

				if ($group_rows[$i]['group_type'] == GROUP_HIDDEN && !$this->auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel') && !in_array($group_id, $in_group[0]))
				{
					continue;
				}

				// Do we have a Group Rank?
				if ($group_rows[$i]['group_rank'])
				{
					if (isset($ranks['special'][$group_rows[$i]['group_rank']]))
					{
						$rank_title = $ranks['special'][$group_rows[$i]['group_rank']]['rank_title'];
					}
					$rank_img = (!empty($ranks['special'][$group_rows[$i]['group_rank']]['rank_image'])) ? '<img src="' . $board_url . '' . $this->config['ranks_path'] . '/' . $ranks['special'][$group_rows[$i]['group_rank']]['rank_image'] . '" alt="' . $ranks['special'][$group_rows[$i]['group_rank']]['rank_title'] . '" title="' . $ranks['special'][$group_rows[$i]['group_rank']]['rank_title'] . '" /><br />' : '';
				}
				else
				{
					$rank_title = '';
					$rank_img = '';
				}

				$this->template->assign_block_vars('groups', array(
					'GROUP_ID'			=> $group_rows[$i]['group_id'],
					'GROUP_NAME'		=> ($group_rows[$i]['group_type'] == GROUP_SPECIAL) ? $this->user->lang['G_' . $group_rows[$i]['group_name']] : $group_rows[$i]['group_name'],
					'GROUP_DESC'		=> generate_text_for_display($group_rows[$i]['group_desc'], $group_rows[$i]['group_desc_uid'], $group_rows[$i]['group_desc_bitfield'], $group_rows[$i]['group_desc_options']),
					'GROUP_COLOUR'		=> $group_rows[$i]['group_colour'],
					'GROUP_RANK'		=> $rank_title,
					'RANK_IMG'			=> $rank_img,
					'U_VIEW_GROUP'		=> append_sid("{$this->root_path}memberlist.$this->php_ext", 'mode=group&amp;g=' . $group_id),
					'S_SHOW_RANK'		=> true,
				));

				if (!empty($group_leaders[$group_id]))
				{
					foreach ($group_leaders[$group_id] as $group_leader)
					{
						$this->template->assign_block_vars('groups.leaders', array(
							'U_VIEW_PROFILE' => $group_leader,
						));
					}
				}

				if (!empty($group_users[$group_id]))
				{
					foreach ($group_users[$group_id] as $group_user)
					{
						$this->template->assign_block_vars('groups.members', array(
							'U_VIEW_PROFILE' => $group_user,
						));
					}
				}
			}
		}

		// Set up the Navlinks for the forums navbar
		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME' => $this->user->lang['GROUPS'],
		));

		// Send all data to the template file
		return $this->helper->render('groups_body.html', $this->user->lang('GROUP_TITLE'));
	}
}
