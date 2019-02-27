<?php

/**
 * @file plugins/themes/manuscriptCaprew/DefaultManuscriptCapThemePlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University Library
 * Copyright (c) 2003-2019 John Willinsky
 *
 * @class DefaultManuscriptCapThemePlugin
 * @ingroup plugins_themes_default_manuscript_cap
 *
 * @brief Default theme
 */
import('lib.pkp.classes.plugins.ThemePlugin');

class DefaultManuscriptCapThemePlugin extends ThemePlugin {

	public function init() {
		$this->setParent('defaultmanuscriptchildthemeplugin');

		HookRegistry::register('TemplateManager::display', array($this, 'issueTocData'));
	}

	/**
	 * @return string
	 * @brief Get the display name of this plugin
	 */
	public function getDisplayName() {
		return __('plugins.themes.manuscriptCaprew.name');
	}

	/**
	 * @return string
	 * @brief Get the description of this plugin
	 */
	public function getDescription() {
		return __('plugins.themes.manuscriptCaprew.description');
	}

	/**
	 * @param $hookname string
	 * @param $args array [
	 *      @option TemplateManager
	 *      @option string relative path to the template
	 * ]
	 * @brief Add additional data to the issue TOC
	 */
	public function issueTocData($hookName, $args) {

		$templateMgr = $args[0];
		$template = $args[1];

		if ($template !== 'frontend/pages/issue.tpl' && $template !== 'frontend/pages/indexJournal.tpl') return false;

		$issue = $templateMgr->get_template_vars('issue');
		$publishedArticlesDao = DAORegistry::getDAO("PublishedArticleDAO");
		$capPublishedArticles = $publishedArticlesDao->getPublishedArticles($issue->getId());

		usort($capPublishedArticles, array($this, "cmp"));

		$templateMgr->assign('capPublishedArticles', $capPublishedArticles);
	}

	/**
	 * @param $a PublishedArticle
	 * @param $b PublishedArticle
	 * @return int
	 * @brief Sorting published articles by page number
	 */
	private function cmp($a, $b) {

		if (!$a->getPages() && !$b->getPages()) {
			return 0;
		} elseif (!$a->getPages()) {
			return 1;
		} elseif (!$b->getPages()) {
			return -1;
		}

		$aPages = $this->_determinePageNumbers($a->getPages());
		$bPages = $this->_determinePageNumbers($b->getPages());

		if ($aPages[0] < $bPages[0]) {
			return -1;
		} elseif ($aPages[0] > $bPages[0]) {
			return 1;
		} else {
			$aLast = end($aPages);
			$bLast = end($bPages);
			if ($aLast < $bLast) {
				return -1;
			} elseif ($aLast > $bLast) {
				return 1;
			}
		}

		// Finally give up
		return 0;
	}

	/**
	 * @param $pages string
	 * @return array
	 * @brief Determine the first and last page
	 */
	private function _determinePageNumbers($pages) {
		$pageNumbers = array();
		$pattern = '-|,|\/|_';

		if (ctype_digit($pages)) {
			$pageNumbers[] = intval($pages);
		} elseif (preg_match("/$pattern/", $pages)) {
			$pageNumbersUnsanitized = preg_split("/$pattern/", $pages, 2);
			foreach ($pageNumbersUnsanitized as $pageNumber) {
				$pageNumbers[] = abs((int) filter_var($pageNumber, FILTER_SANITIZE_NUMBER_INT));
			}
		} else {
			$pageNumbers[] = abs((int) filter_var($pages, FILTER_SANITIZE_NUMBER_INT));
		}

		return $pageNumbers;
	}
}
