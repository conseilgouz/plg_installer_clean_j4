<?php
/**
* CG Clean  - Joomla Plugin
* Version			: 1.0.0
* Package			: CG Clean
* copyright 		: Copyright (C) 2021 ConseilGouz. All rights reserved.
* license    		: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/
// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Version;
use Joomla\CMS\Filesystem\File;

class Plginstallercgcleanj4InstallerScript
{
	private $min_joomla_version      = '3.9.0';
	private $min_php_version         = '7.2';
	private $exttype                 = 'plugin';
	private $previous_version        = '';
	private $dir           = null;
	private $installerName = 'system_cgcleanj4installer';
	public function __construct()
	{
		$this->dir = __DIR__;
		$this->lang = Factory::getLanguage();
		$this->lang->load($this->extname);
	}

    function preflight($type, $parent)
    {
		if ( ! $this->passMinimumJoomlaVersion())
		{
			$this->uninstallInstaller();
			return false;
		}

		if ( ! $this->passMinimumPHPVersion())
		{
			$this->uninstallInstaller();
			return false;
		}
		return true;
	}
    function postflight($type, $parent)
    {
	
		if (($type=='install') || ($type == 'update')) { // remove obsolete dir/files
			$this->_go_cleanup();
		Factory::getApplication()->enqueueMessage(
					'CG Clean UP done.',
				'notice'
		);
		}
		return false;
	}    
	private function _go_cleanup() {
	// fix wrong type in update_sites
		$db = Factory::getDbo();

        $query = $db->getQuery(true);
		$query->update($db->quoteName('#__update_sites'))
			  ->set($db->qn('type') . ' = "extension"')
			  ->where($db->qn('type') . ' = "plugin"');
		$db->setQuery($query);
        try {
	        $db->execute();
        }
        catch (RuntimeException $e) {
            JLog::add('unable to enable Plugin site_form_override', JLog::ERROR, 'jerror');
        }
	// remove obsolete update sites
		$query = $db->getQuery(true)
			->delete('#__update_sites')
			->where($db->quoteName('location') . ' like "%432473037d.url-de-test.ws/%"');
		$db->setQuery($query);
		$db->execute();
	
	// kill myself 
		$f = JPATH_SITE . '/plugins/installer/cgcleanj4';
		Folder::delete($f);
		$query = $db->getQuery(true)
			->delete('#__extensions')
			->where($db->quoteName('element') . ' = "cgcleanj4"')
			->where($db->quoteName('type') . ' = "plugin"');
		$db->setQuery($query);
		$db->execute();
		$query = $db->getQuery(true)
			->delete('#__update_sites')
			->where($db->quoteName('name') . ' = "plgcgcleanj4"')
			->where($db->quoteName('type') . ' = "extension"');
		$db->setQuery($query);
		$db->execute();
	}

	// Check if Joomla version passes minimum requirement
	private function passMinimumJoomlaVersion()
	{
		$j = new Version();
		$version=$j->getShortVersion(); 
		if (version_compare($version, $this->min_joomla_version, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				'Incompatible Joomla version : found <strong>' . $version . '</strong>, Minimum : <strong>' . $this->min_joomla_version . '</strong>',
				'error'
			);

			return false;
		}

		return true;
	}

	// Check if PHP version passes minimum requirement
	private function passMinimumPHPVersion()
	{

		if (version_compare(PHP_VERSION, $this->min_php_version, '<'))
		{
			Factory::getApplication()->enqueueMessage(
					'Incompatible PHP version : found  <strong>' . PHP_VERSION . '</strong>, Minimum <strong>' . $this->min_php_version . '</strong>',
				'error'
			);
			return false;
		}

		return true;
	}
	private function uninstallInstaller()
	{
		if ( ! JFolder::exists(JPATH_PLUGINS . '/system/' . $this->installerName)) {
			return;
		}
		$this->delete([
			JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
			JPATH_PLUGINS . '/system/' . $this->installerName,
		]);
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
		$db->setQuery($query);
		$db->execute();
		Factory::getCache()->clean('_system');
	}
	
}