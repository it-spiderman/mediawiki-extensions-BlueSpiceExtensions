<?php

class PageAssignmentsHooks {

	/**
	 * Adds database tables
	 * @param DatabaseUpdater $updater
	 * @return boolean
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'bs_pageassignments', dirname( __DIR__ ).'/db/bs_pageassignments.sql' );
		if( !$updater->tableExists('bs_responsible_editors') ) {
			return true;
		}
		$updater->addPostDatabaseUpdateMaintenance(
			'BSPageAssignmentsMigrateRespEditors'
		);
		return true;
	}

	/**
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return boolean
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		if ( $out->getRequest()->getVal( 'action', 'view') !== 'view' || $out->getTitle()->isSpecialPage() ) {
			return true;
		}

		$out->addModules( 'ext.pageassignments.scripts' );
		$out->addModuleStyles( 'ext.pageassignments.styles' );

		return true;
	}

	/**
	 *
	 * @param array $aOut
	 * @return boolean
	 */
	public static function onBSWikiAdminMenuItems( &$aOut ){
		$sLabel = wfMessage( 'managepageassignments' )->plain();
		$aOut[$sLabel] = Html::rawElement( 'li', array(),
			Linker::link( SpecialPage::getTitleFor( 'ManagePageAssignments' ), $sLabel, array( 'class' => 'bs-admin-link bs-icon-profile' )  )
		);
		return true;
	}

	public static function onPersonalUrls( &$aPersonal_urls, &$oTitle ) {
		$oUser = RequestContext::getMain()->getUser();
		if ( $oUser->isLoggedIn() ) {
			$aPersonal_urls['pageassignments'] = array(
				'href' => SpecialPage::getTitleFor( 'PageAssignments' )->getLocalURL(),
				'text' => SpecialPageFactory::getPage( 'PageAssignments' )->getDescription()
			);
		}

		return true;
	}

	/**
	 * Adds the "Assignments" menu entry in view mode
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 * @return boolean Always true to keep hook running
	 */
	public static function onSkinTemplateNavigation( &$sktemplate, &$links ) {
		if ( $sktemplate->getRequest()->getVal( 'action', 'view') != 'view' ) {
			return true;
		}
		if ( !$sktemplate->getTitle()->userCan( 'pageassignments' ) ) {
			return true;
		}
		$links['actions']['pageassignments'] = array(
			'text' => wfMessage( 'bs-pageassignments-menu-label' )->text(),
			'href' => '#',
			'class' => false,
			'id' => 'ca-pageassignments'
		);

		return true;
	}

	/**
	 * Hook handler for MediaWiki 'TitleMoveComplete' hook. Adapts assignments in case of article move.
	 * @param Title $old
	 * @param Title $nt
	 * @param User $user
	 * @param int $pageid
	 * @param int $redirid
	 * @param string $reason
	 * @return bool Always true to keep other hooks running.
	 */
	public static function onTitleMoveComplete( &$old, &$nt, &$user, $pageid, $redirid, $reason ) {
		$dbr = wfGetDB( DB_MASTER );
		$dbr->update(
			'bs_pageassignments',
			array(
				'pa_page_id' => $nt->getArticleID()
			),
			array(
				'pa_page_id' => $old->getArticleID()
			)
		);
		return true;
	}

	/**
	 * Clears assignments
	 * @param WikiPage $wikiPage
	 * @param user $user
	 * @param string $reason
	 * @param int $id
	 * @param Content $content
	 * @param ManualLogEntry $logEntry
	 * @return boolean
	 */
	public static function onArticleDeleteComplete( &$wikiPage, &$user, $reason, $id, $content, $logEntry ) {
		$dbr = wfGetDB( DB_MASTER );
		$dbr->delete(
			'bs_pageassignments',
			array(
				'pa_page_id' => $wikiPage->getId()
			)
		);
		return true;
	}

	/**
	 * Register tag with UsageTracker extension
	 * @param array $aCollectorsConfig
	 * @return Always true to keep hook running
	 */
	public static function onBSUsageTrackerRegisterCollectors( &$aCollectorsConfig ) {
		$aCollectorsConfig['pageassignments:pages'] = array(
			'class' => 'Database',
			'config' => array(
				'identifier' => 'bs-usagetracker-pageassignments',
				'descriptionKey' => 'bs-usagetracker-pageassignments',
				'table' => 'bs_pageassignments',
				'uniqueColumns' => array( 'pa_page_id' )
			)
		);
		return true;
	}
}