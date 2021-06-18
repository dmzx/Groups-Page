<?php
/**
*
* @package phpBB Extension - Groups Page
* @copyright (c) 2021 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\groupspage\migrations;

class groupspage_install extends \phpbb\db\migration\migration
{
	public function update_data()
	{
		return [
			// Add config
			['config.add', ['groupspage_version', '1.0.1']],
			['config.add', ['groupspage_group_exceptions', '3', '0']],

			// ACP module
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_GROUPSPAGE_TITLE'
			]],
			['module.add', [
				'acp',
				'ACP_GROUPSPAGE_TITLE',
				[
					'module_basename'	=> '\dmzx\groupspage\acp\acp_groupspage_module',
				],
			]],
		];
	}
}
