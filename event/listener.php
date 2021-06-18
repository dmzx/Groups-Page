<?php
/**
*
* @package phpBB Extension - Groups Page
* @copyright (c) 2017 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\groupspage\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\user;
use phpbb\template\template;
use phpbb\controller\helper;

class listener implements EventSubscriberInterface
{
	/** @var user */
	protected $user;

	/** @var template */
	protected $template;

	/** @var helper */
	protected $helper;

	/**
	* Constructor
	*
	* @param user		$user
	* @param template	$template
	* @param helper		$helper
	*/
	public function __construct(
		user $user,
		template $template,
		helper $helper
	)
	{
		$this->user			= $user;
		$this->template		= $template;
		$this->helper 		= $helper;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.page_header'	=> 'page_header',
		];
	}

	public function page_header($event)
	{
		$this->user->add_lang_ext('dmzx/groupspage', 'common');

		$this->template->assign_vars([
			'U_GROUPS'	=> $this->helper->route('dmzx_groupspage_controller'),
		]);
	}
}
