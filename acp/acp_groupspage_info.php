<?php
/**
*
* @package phpBB Extension - Groups Page
* @copyright (c) 2021 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\groupspage\acp;

class acp_groupspage_info
{
	function module()
	{
		return [
			'filename'	=> '\dmzx\groupspage\acp\acp_groupspage_module',
			'title'		=> 'ACP_GROUPSPAGE_TITLE',
			'modes'		=> [
				'settings'	=> ['title' => 'ACP_GROUPSPAGE_TITLE_SETTINGS', 'auth' => 'ext_dmzx/groupspage && acl_a_board', 'cat' => ['ACP_GROUPSPAGE_TITLE']],
			],
		];
	}
}
