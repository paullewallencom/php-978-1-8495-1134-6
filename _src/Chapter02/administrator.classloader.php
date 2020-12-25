<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more
 * information on this, please see the index.php file or visit
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * This file is solely to hold the admin side smart class mapper.
 *
 * The smartAdminClassMapper is used to find classes.  It has written into it the
 * locations for permanent classes on the user side, and separately holds locations
 * for external classes from third parties outside the Aliro project.  These are
 * from other open source projects.  The third source for class information is the
 * database, which contains details of installed classes.  Unlike the user side,
 * classes must be specifically identified in the map to constrain as much as
 * possible the code that will be loaded on the admin side.
 *
 */

class smartAdminClassMapper extends smartClassMapper {
	private static $instance = __CLASS__;

	protected $classSQL = 'SELECT * FROM #__classmap';
	protected $admindir = '';

	private static $adminmap = array (
	'aliroAdminAuthenticator' => 'aliroAdminAuthenticator',
	'aliroAdminRequest' => 'aliroAdminRequest',
	'aliroAdminDashboard' => 'aliroAdminDashboard',
	'aliroAdminTemplateBase' => 'aliroAdminTemplateBase',
	'defaultAdminTemplate' => 'defaultAdminTemplate',
	'aliroAdminMenu' => 'aliroAdminMenu',
	'aliroAdminMenuHandler' => 'aliroAdminMenu',
	'aliroAdminToolbar' => 'aliroAdminToolbar',
	'aliroAdminPageNav' => 'aliroAdminPageNav',
	'aliroComponentAdminManager' => 'aliroComponentAdminManager',
	'aliroComponentAdminControllers' => 'aliroComponentAdminManager',
	'aliroDBUpdateController' => 'aliroDBUpdateController',
	'aliroMakeManifest' => 'aliroMakeManifest',
	'basicAdminHTML' => 'basicAdminHTML',
	'advancedAdminHTML' => 'basicAdminHTML',
	'widgetAdminHTML' => 'basicAdminHTML',
	'configAdminConfig' => 'cor_config/admin.config',
	'listConfigHTML' => 'cor_config/admin.config.html',
	'sefAdminSef' => 'cor_sef/admin.sef',
	'sefAdminControllers' => 'cor_sef/admin.sef',
	'sefAdminConfig' => 'cor_sef/c-admin-classes/sefAdminConfig',
	'sefAdminMetadata' => 'cor_sef/c-admin-classes/sefAdminMetadata',
	'sefAdminTransform' => 'cor_sef/c-admin-classes/sefAdminTransform',
	'sefAdminUri' => 'cor_sef/c-admin-classes/sefAdminUri',
	'sefAdminPage404' => 'cor_sef/admin.sef',
	'sefAdminHTML' => 'cor_sef/admin.sef.html',
	'HTML_installer' => 'cor_extensions/admin.installer.html',
	'modulesAdminModules' => 'cor_modules/admin.modules',
	'HTML_modules' => 'cor_modules/admin.modules.html',
	'mambotsAdminMambots' => 'cor_mambots/admin.mambots',
	'listMambotsHTML' => 'cor_mambots/admin.mambots.html',
	'templatesAdminTemplates' => 'cor_templates/admin.templates',
	'listTemplatesHTML' => 'cor_templates/admin.templates.html',
	'errorsAdminErrors' => 'cor_errors/admin.errors',
	'listErrorsHTML' => 'cor_errors/admin.errors.html',
	'err404AdminErr404' => 'cor_err404/admin.err404',
	'listErr404HTML' => 'cor_err404/admin.err404.html',
	'foldersAdminFolders' => 'cor_folders/admin.folders',
	'foldersAdminHTML' => 'cor_folders/admin.folders.html',
	'listFoldersHTML' => 'cor_folders/admin.folders.html',
	'editFoldersHTML' => 'cor_folders/admin.folders.html',
	'sysinfoAdminSysinfo' => 'cor_sysinfo/admin.sysinfo',
	'helpAdminHelp' => 'cor_help/admin.help',
	'aliroExtensionInstaller' => 'aliroExtensionInstaller',
	'aliroLanguageHandler' => 'aliroExtensionInstaller',
	'aliroPatchHandler' => 'aliroExtensionInstaller',
	'aliroIncludeHandler' => 'aliroExtensionInstaller',
	'aliroParameterHandler' => 'aliroExtensionInstaller',
	'aliroInstaller' => 'cor_extensions/installer.class',
	'extensionsAdminExtensions' => 'cor_extensions/admin.extensions',
	'listExtensionsHTML' => 'cor_extensions/admin.extensions.html',
	'listMenutypesHTML' => 'cor_menus/admin.menutypes.html',
	'menusAdminMenus' => 'cor_menus/admin.menus',
	'menusAdminType' => 'cor_menus/admin.menus',
	'menuInterface' => 'cor_menus/admin.menus',
	'listMenusHTML' => 'cor_menus/admin.menus.html',
	'languagesControllers' => 'cor_languages/admin.languages',
	'catalogsView' => 'cor_languages/views/catalogs.view',
	'editView' => 'cor_languages/views/edit.view',
	'indexView' => 'cor_languages/views/index.view',
	'languageView' => 'cor_languages/views/language.view',
	'applyAction' => 'cor_languages/actions/apply.action',
	'auto_translateAction' => 'cor_languages/actions/auto_translate.action',
	'cancelAction' => 'cor_languages/actions/cancel.action',
	'convertAction' => 'cor_languages/actions/convert.action',
	'defaultAction' => 'cor_languages/actions/default.action',
	'editAction' => 'cor_languages/actions/edit.action',
	'exportAction' => 'cor_languages/actions/export.action',
	'extractAction' => 'cor_languages/actions/extract.action',
	'indexAction' => 'cor_languages/actions/index.action',
	'installAction' => 'cor_languages/actions/install.action',
	'newAction' => 'cor_languages/actions/new.action',
	'publishAction' => 'cor_languages/actions/publish.action',
	'removeAction' => 'cor_languages/actions/remove.action',
	'saveAction' => 'cor_languages/actions/save.action',
	'sortAction' => 'cor_languages/actions/sort.action',
	'translateAction' => 'cor_languages/actions/translate.action',
	'updateAction' => 'cor_languages/actions/update.action',
	'languagesAdminLanguages' => 'cor_languages/languagesAdmin',
	'languagesAdminCatalogs' => 'cor_languages/catalogsAdmin',
	'catalogsAdminLanguages' => 'cor_languages/catalogsAdmin',
	'tagsAdminTags' => 'cor_tags/admin.tags',
	'aliroTag' => 'cor_tags/admin.tags',
	'listTagsHTML' => 'cor_tags/admin.tags.html',
	'editTagsHTML' => 'cor_tags/admin.tags.html'
	);

	protected function __construct () {
		$this->admindir = substr(_ALIRO_ADMIN_DIR,1).'/';
		parent::__construct();
	}

	public static function getInstance () {
		if (!is_object(self::$instance)) {
			self::$instance = parent::getCachedSingleton(self::$instance);
			self::$instance->reset();
		}
		self::$instance->checkDynamic();
		return self::$instance;
	}

	protected function getClassPath ($classname) {
		if (isset(self::$adminmap[$classname])) {
			$debuginfo = aliroDebug::getInstance();
			$debuginfo->setDebugData ("About to load $classname, current used memory ".(is_callable('memory_get_usage') ? memory_get_usage() : 'not known').$this->timer->mark('seconds'));
			return _ALIRO_ADMIN_CLASS_BASE.'/classes/'.self::$adminmap[$classname].'.php';
		}
	    return parent::getClassPath($classname);
	}
	
}