<?php

/**
 * Shoutbox extension for BlueSpice
 *
 * Adds a parser function for embedding your own shoutbox.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.blue-spice.org
 *
 * @author     Markus Glaser <glaser@hallowelt.biz>
 * @author     Tobias Weichart <weichart@hallowelt.biz>
 * @author     Karl Waldmanstetter
 * @version    2.23.1
 * @package    BlueSpice_Extensions
 * @subpackage ShoutBox
 * @copyright  Copyright (C) 2011 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

//Last Code Review RBV (30.06.2011)

/**
 * Base class for ShoutBox extension
 * @package BlueSpice_Extensions
 * @subpackage ShoutBox
 */
class ShoutBox extends BsExtensionMW {

	/**
	 * Constructor of ShoutBox class
	 */
	public function __construct() {
		wfProfileIn( 'BS::' . __METHOD__ );

		// Base settings
		$this->mExtensionFile = __FILE__;
		$this->mExtensionType = EXTTYPE::PARSERHOOK;
		$this->mInfo = array(
			EXTINFO::NAME => 'ShoutBox',
			EXTINFO::DESCRIPTION => 'bs-shoutbox-desc',
			EXTINFO::AUTHOR => 'Karl Waldmannstetter, Markus Glaser',
			EXTINFO::VERSION => 'default',
			EXTINFO::STATUS => 'default',
			EXTINFO::PACKAGE => 'default',
			EXTINFO::URL => 'http://www.hallowelt.biz',
			EXTINFO::DEPS => array( 'bluespice' => '2.22.0' )
		);
		$this->mExtensionKey = 'MW::ShoutBox';
		wfProfileOut( 'BS::' . __METHOD__ );
	}

	/**
	 * Initialization of ShoutBox extension
	 */
	protected function initExt() {
		wfProfileIn( 'BS::' . __METHOD__ );

		// Hooks
		$this->setHook( 'SkinTemplateOutputPageBeforeExec' );
		$this->setHook( 'BeforePageDisplay' );
		$this->setHook( 'BSInsertMagicAjaxGetData' );
		$this->setHook( 'BSStateBarBeforeTopViewAdd', 'onStateBarBeforeTopViewAdd' );
		$this->setHook( 'BeforeCreateEchoEvent' );
		$this->setHook( 'EchoGetDefaultNotifiedUsers' );


		// Permissions
		$this->mCore->registerPermission( 'readshoutbox', array(), array( 'type' => 'global' ) );
		$this->mCore->registerPermission( 'writeshoutbox', array(), array( 'type' => 'global' ) );
		$this->mCore->registerPermission( 'archiveshoutbox', array(), array( 'type' => 'global' ) );

		$this->mCore->registerBehaviorSwitch( 'bs_noshoutbox' );

		BsConfig::registerVar( 'MW::ShoutBox::ShowShoutBoxByNamespace', array( 0 ), BsConfig::LEVEL_PRIVATE | BsConfig::TYPE_ARRAY_INT, 'multiselectplusadd' );
		BsConfig::registerVar( 'MW::ShoutBox::CommitTimeInterval', 15, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_INT, 'bs-shoutbox-pref-committimeinterval', 'int' );
		BsConfig::registerVar( 'MW::ShoutBox::NumberOfShouts', 5, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_INT, 'bs-shoutbox-pref-numberofshouts', 'int' );
		BsConfig::registerVar( 'MW::ShoutBox::ShowAge', true, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_BOOL, 'bs-shoutbox-pref-showage', 'toggle' );
		BsConfig::registerVar( 'MW::ShoutBox::ShowUser', true, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_BOOL, 'bs-shoutbox-pref-showuser', 'toggle' );
		BsConfig::registerVar( 'MW::ShoutBox::Show', true, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_BOOL, 'bs-shoutbox-pref-show', 'toggle' );
		BsConfig::registerVar( 'MW::ShoutBox::AllowArchive', true, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_BOOL, 'bs-shoutbox-pref-allowarchive', 'toggle' );
		BsConfig::registerVar( 'MW::ShoutBox::MaxMessageLength', 255, BsConfig::LEVEL_PUBLIC | BsConfig::TYPE_INT, 'bs-shoutbox-pref-maxmessagelength', 'int' );
		wfProfileOut( 'BS::' . __METHOD__ );
	}

	/**
	 * Sets up required database tables
	 * @param DatabaseUpdater $updater Provided by MediaWikis update.php
	 * @return boolean Always true to keep the hook running
	 */
	public static function getSchemaUpdates( $updater ) {
		global $wgDBtype, $wgExtNewTables, $wgExtNewIndexes, $wgExtNewFields;
		$sDir = __DIR__ . DS;

		if ( $wgDBtype == 'mysql') {
			$wgExtNewTables[] = array( 'bs_shoutbox', $sDir . 'db/mysql/ShoutBox.sql' );
			$wgExtNewIndexes[] = array( 'bs_shoutbox', 'sb_page_id', $sDir . 'db/mysql/ShoutBox.patch.sb_page_id.index.sql' );
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_user_id', $sDir . 'db/mysql/ShoutBox.patch.sb_user_id.sql' );
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_archived', $sDir . 'db/mysql/ShoutBox.patch.sb_archived.sql' );

			//TODO: also do this for oracle and postgres
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_title', $sDir . 'db/mysql/ShoutBox.patch.sb_title.sql' );
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_touched', $sDir . 'db/mysql/ShoutBox.patch.sb_touched.sql' );
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_parent_id', $sDir . 'db/mysql/ShoutBox.patch.sb_parent_id.sql' );
		} elseif ( $wgDBtype == 'sqlite' ) {
			$wgExtNewTables[] = array( 'bs_shoutbox', $sDir . 'db/mysql/ShoutBox.sql' );
		} elseif ( $wgDBtype == 'postgres' ) {
			$wgExtNewTables[] = array( 'bs_shoutbox', $sDir . 'db/postgres/ShoutBox.pg.sql' );
			$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_archived', $sDir . 'db/postgres/ShoutBox.patch.sb_archived.pg.sql' );
			/*
			  $wgExtNewIndexes[] = array( 'bs_shoutbox', 'sb_page_id', $sDir . 'db/postgres/ShoutBox.patch.sb_page_id.index.pg.sql' );
			  $wgExtNewFields[]  = array( 'bs_shoutbox', 'sb_user_id', $sDir . 'db/postgres/ShoutBox.patch.sb_user_id.pg.sql' );
			 */
		} elseif ( $wgDBtype == 'oracle' ) {
			$wgExtNewTables[] = array( 'bs_shoutbox', $sDir . 'db/oracle/ShoutBox.oci.sql' );
			$dbr = wfGetDB( DB_SLAVE );
			if ( !$dbr->fieldExists( 'bs_shoutbox', 'sb_archived' ) && $dbr->tableExists( 'bs_shoutbox' ) ) {
				#$wgExtNewFields[] = array( 'bs_shoutbox', 'sb_archived', $sDir . 'db/oracle/ShoutBox.patch.sb_archived.oci.sql' );
			}
			$wgExtNewIndexes[] = array( 'bs_shoutbox', 'sb_page_id', $sDir . 'db/oracle/ShoutBox.patch.sb_page_id.index.oci.sql' );
			/*
			  $wgExtNewFields[]  = array( 'bs_shoutbox', 'sb_user_id', $sDir . 'db/oracle/ShoutBox.patch.sb_user_id.oci.sql' );
			 */
		}
		return true;
	}

	/**
	 * Inject tags into InsertMagic
	 * @param Object $oResponse reference
	 * $param String $type
	 * @return always true to keep hook running
	 */
	public function onBSInsertMagicAjaxGetData( &$oResponse, $type ) {
		if ( $type != 'switches' )
			return true;

		$oResponse->result[] = array(
			'id' => 'bs:shoutbox',
			'type' => 'switch',
			'name' => 'NOSHOUTBOX',
			'desc' => wfMessage( 'bs-shoutbox-switch-description' )->plain(),
			'code' => '__NOSHOUTBOX__',
		);

		return true;
	}

	/**
	 *
	 * @param OutputPage $oOutputPage
	 * @param SkinTemplate $oSkinTemplate
	 * @return boolean
	 */
	public function onBeforePageDisplay( $oOutputPage, $oSkinTemplate ) {
		if ( BsConfig::get( 'MW::ShoutBox::Show' ) === false )
			return true;
		$oTitle = $oOutputPage->getTitle();
		if ( $oOutputPage->isPrintable() )
			return true;

		if ( is_object( $oTitle ) && $oTitle->exists() == false )
			return true;
		if ( !$oTitle->userCan( 'readshoutbox' ) )
			return true;
		if ( $this->getRequest()->getVal( 'action', 'view' ) != 'view' )
			return true;
		if ( $oTitle->isSpecialPage() )
			return true;
		if ( !$oTitle->userCan( 'read' ) )
			return true;

		$aNamespacesToDisplayShoutBox = BsConfig::get( 'MW::ShoutBox::ShowShoutBoxByNamespace' );
		if ( !in_array( $oTitle->getNsText(), $aNamespacesToDisplayShoutBox ) )
			return true;

		$vNoShoutbox = BsArticleHelper::getInstance( $oTitle )->getPageProp( 'bs_noshoutbox' );
		if ( $vNoShoutbox === '' )
			return true;

		$oOutputPage->addModuleStyles( 'ext.bluespice.shoutbox.styles' );
		$oOutputPage->addModules( 'ext.bluespice.shoutbox' );
		$oOutputPage->addModules( 'ext.bluespice.shoutbox.mention' );

		BsExtensionManager::setContext( 'MW::ShoutboxShow' );
		return true;
	}

	/**
	 * Adds the shoutbox output to be rendered from skin.
	 * @param SkinTemplate $sktemplate a collection of views. Add the view that needs to be displayed
	 * @param BaseTemplate $tpl currently logged in user. Not used in this context.
	 * @return bool always true
	 */
	public function onSkinTemplateOutputPageBeforeExec( &$sktemplate, &$tpl ) {
		if ( !BsExtensionManager::isContextActive( 'MW::ShoutboxShow' ) ) {
			return true;
		}

		$tpl->data['bs_dataAfterContent']['bs-shoutbox'] = array(
			'position' => 30,
			'label' => wfMessage( 'bs-shoutbox-title' )->text(),
			'content' => $this->getShoutboxViewForAfterContent( $sktemplate )
		);
		return true;
	}

	private function getShoutboxViewForAfterContent( $sktemplate ) {
		$oShoutBoxView = new ViewShoutBox();

		wfRunHooks( 'BSShoutBoxBeforeAddViewAfterArticleContent', array( &$oShoutBoxView ) );

		if ( $sktemplate->getTitle()->userCan( 'writeshoutbox' ) ) {
			$oShoutBoxView->setOption( 'showmessageform', true );
		}

		return $oShoutBoxView;
	}

	/**
	 * Delivers a rendered list of shouts for the current page to be displayed in the shoutbox.
	 * This function is called remotely via AJAX-Handler.
	 * @param string $sOutput contains the rendered list
	 * @return bool allow other hooked methods to be executed. Always true
	 */
	public static function getShouts( $iArticleId, $iLimit ) {
		if ( BsCore::checkAccessAdmission( 'readshoutbox' ) === false )
			return "";

		// do not allow negative page ids and pages that have 0 as id (e.g. special pages)
		if ( $iArticleId <= 0 )
			return true;

		if ( $iLimit <= 0 )
				$iLimit = BsConfig::get( 'MW::ShoutBox::NumberOfShouts' );

		$sKey = BsCacheHelper::getCacheKey( 'BlueSpice', 'ShoutBox', $iArticleId, $iLimit );
		$aData = BsCacheHelper::get( $sKey );
$aData = false;
		if ( $aData !== false ) {
			wfDebugLog( 'BsMemcached', __CLASS__ . ': Fetching shouts from cache' );
			$sOutput = $aData;
		} else {
			wfDebugLog( 'BsMemcached', __CLASS__ . ': Fetching shouts from DB' );

			$sOutput = '';
			//return false on hook handler to break here

			$aTables = array( 'bs_shoutbox' );
			$aFields = array( '*' );
			$aConditions = array(
				'sb_page_id' => $iArticleId,
				'sb_archived' => '0',
				'sb_parent_id' => '0',
				'sb_title' => '',
			);
			$aOptions = array(
				'ORDER BY' => 'sb_timestamp DESC',
				'LIMIT' => $iLimit + 1, // One more than iLimit in order to know if there are more shouts left.
			);

			if ( !wfRunHooks( 'BSShoutBoxGetShoutsBeforeQuery', array( &$sOutput, $iArticleId, &$iLimit, &$aTables, &$aFields, &$aConditions, &$aOptions ) ) ) {
				return $sOutput;
			}

			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
					$aTables, $aFields, $aConditions, __METHOD__, $aOptions
			);

			$oShoutBoxMessageListView = new ViewShoutBoxMessageList();

			if ( $dbr->numRows( $res ) > $iLimit ) {
				$oShoutBoxMessageListView->setMoreLimit( $iLimit + BsConfig::get( 'MW::ShoutBox::NumberOfShouts' ) );
			}

			$bShowAge = BsConfig::get( 'MW::ShoutBox::ShowAge' );
			$bShowUser = BsConfig::get( 'MW::ShoutBox::ShowUser' );

			$iCount = 0;
			while ( $row = $dbr->fetchRow( $res ) ) {
				$oUser = User::newFromId( $row['sb_user_id'] );
				$oProfile = BsCore::getInstance()->getUserMiniProfile( $oUser );
				$sMessage = preg_replace_callback("#@(\S*)#", "self::replaceUsernameInMessage", $row['sb_message']);
				$oShoutBoxMessageView = new ViewShoutBoxMessage();
				if ( $bShowAge )
					$oShoutBoxMessageView->setDate( BsFormatConverter::mwTimestampToAgeString( $row['sb_timestamp'], true ) );
				if ( $bShowUser )
					$oShoutBoxMessageView->setUsername( $row['sb_user_name'] );
				$oShoutBoxMessageView->setUser( $oUser );
				$oShoutBoxMessageView->setMiniProfile( $oProfile );
				$oShoutBoxMessageView->setMessage( $sMessage );
				$oShoutBoxMessageView->setShoutID( $row['sb_id'] );
				$oShoutBoxMessageListView->addItem( $oShoutBoxMessageView );
				// Since we have one more shout than iLimit, we need to count :)
				$iCount++;
				if ( $iCount >= $iLimit )
					break;
			}

			$sOutput .= $oShoutBoxMessageListView->execute();
			$iTotelShouts = self::getTotalShouts( $iArticleId );
			$sOutput .= "<div id='bs-sb-count-all' style='display:none'>" . $iTotelShouts . "</div>";

			//expire after 5 minutes in order to keep time tracking somewhat up-to-date
			BsCacheHelper::set( $sKey, $sOutput, 60*5 );
			$dbr->freeResult( $res );
		}
		return $sOutput;
	}

	/**
	 * Returns total number of shouts for the article id
	 * @param int $iArticleId
	 * @return int number of shouts
	 */
	public static function getTotalShouts( $iArticleId = 0 ) {
		$sKey = BsCacheHelper::getCacheKey( 'BlueSpice', 'ShoutBox', 'totalCount' . $iArticleId );
		$iData = BsCacheHelper::get( $sKey );

		if ( $iData === false ) {
			wfDebugLog( 'BsMemcached', __CLASS__ . ': Fetching total count from DB' );
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'bs_shoutbox',
				'sb_id',
				array(
					'sb_page_id' => $iArticleId,
					'sb_archived' => '0',
					'sb_parent_id' => '0',
					'sb_title' => '',
				),
				__METHOD__
			);
			$iTotalShouts = $res !== false ? $dbr->numRows( $res ) : 0;

			BsCacheHelper::set( $sKey, $iTotalShouts );
		} else {
			wfDebugLog( 'BsMemcached', __CLASS__ . ': Fetching total count from cache' );
			$iTotalShouts = $iData;
		}
		return $iTotalShouts;
	}

	/**
	 * Inserts a shout for the current page.
	 * This function is called remotely via AJAX-Handler.
	 * @param string $sOutput success state of database action
	 * @return bool allow other hooked methods to be executed
	 */
	public static function insertShout( $iArticleId, $sMessage ) {
		if ( BsCore::checkAccessAdmission( 'readshoutbox' ) === false || BsCore::checkAccessAdmission( 'writeshoutbox' ) === false )
			return true;

		$oRequest = RequestContext::getMain()->getRequest();
		$oUser = RequestContext::getMain()->getUser();

		// prevent spam by enforcing a interval between two commits
		$iCommitTimeInterval = BsConfig::get( 'MW::ShoutBox::CommitTimeInterval' );
		$iCurrentCommit = time();

		$vLastCommit = $oRequest->getSessionData( 'MW::ShoutBox::lastCommit' );
		if ( is_numeric( $vLastCommit ) && $vLastCommit + $iCommitTimeInterval > $iCurrentCommit ) {
			return FormatJson::encode(
				array(
					'success' => false,
					'msg' => 'bs-shoutbox-too-early'
				)
			);
		}
		$oRequest->setSessionData( 'MW::ShoutBox::lastCommit', $iCurrentCommit );

		$sNick = BsCore::getUserDisplayName( $oUser );
		$iUserId = $oUser->getId();
		$sTimestamp = wfTimestampNow();

		if ( strlen( $sMessage ) > BsConfig::get( 'MW::ShoutBox::MaxMessageLength' ) ) {
			$sMessage = substr( $sMessage, 0, BsConfig::get( 'MW::ShoutBox::MaxMessageLength' ) );
		}

		// TODO MRG (08.09.10 01:57): error message
		if ( $iArticleId <= 0 )
			return false;

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
				'bs_shoutbox',
				array(
					'sb_page_id' => $iArticleId,
					'sb_user_id' => $iUserId,
					'sb_user_name' => $sNick,
					'sb_message' => $sMessage,
					'sb_timestamp' => $sTimestamp,
					'sb_archived' => '0'
				)
		); // TODO RBV (21.10.10 17:21): Send error / success to client.

		wfRunHooks( 'BSShoutBoxAfterInsertShout', array( $iArticleId, $iUserId, $sNick, $sMessage, $sTimestamp ) );
		self::notify("insert", $iArticleId, $iUserId, $sNick, $sMessage, $sTimestamp);

		self::invalidateShoutBoxCache( $iArticleId );
		return FormatJson::encode(
			array(
				'success' => true,
				'msg' => self::getShouts( $iArticleId, 0 )
			)
		);
	}

	/**
	 * Archivess a shout for the current page.
	 * This function is called remotely via AJAX-Handler.
	 * @param string $sOutput success state of database action
	 * @return bool allow other hooked methods to be executed
	 */
	public static function archiveShout( $iShoutId, $iArticleId ) {
		if ( BsCore::checkAccessAdmission( 'readshoutbox' ) === false || BsCore::checkAccessAdmission( 'writeshoutbox' ) === false )
			return true;

		global $wgUser;
		$iUserId = $wgUser->getId();

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
				'bs_shoutbox', 'sb_user_id', array( 'sb_id' => $iShoutId ), __METHOD__, array( 'LIMIT' => '1' )
		);

		$row = $dbw->fetchRow( $res );
		// If we don't have archiveshoutbox rights, maybe we can delete our own shout?
		if ( !BsCore::checkAccessAdmission( 'archiveshoutbox' ) ) {
			//if setting for "allow own entries to be archived" is set + username != shoutbox-entry-username => exit
			if ( BsConfig::get( 'MW::ShoutBox::AllowArchive' ) && $iUserId != $row['sb_user_id'] ) {
				$sOutput = wfMessage( 'bs-shoutbox-archive-failure-user' )->plain();
				return true;
			}
		}
		$res = $dbw->update(
				'bs_shoutbox', array( 'sb_archived' => '1' ), array( 'sb_id' => $iShoutId )
		);

		self::invalidateShoutBoxCache( $iArticleId );
		$sResponse = $res == true ? 'bs-shoutbox-archive-success' : 'bs-shoutbox-archive-failure';
		$sOutput = wfMessage( $sResponse )->plain();
		return $sOutput;
	}

	public static function invalidateShoutBoxCache( $iArticleId ) {
		// A better solution might be to store all possible limits and their values in one key.
		for ( $iLimit = 0; $iLimit < 300; $iLimit++ ) {
			BsCacheHelper::invalidateCache( BsCacheHelper::getCacheKey( 'BlueSpice', 'ShoutBox', $iArticleId, $iLimit ) );
		}
		BsCacheHelper::invalidateCache( BsCacheHelper::getCacheKey( 'BlueSpice', 'ShoutBox', 'totalCount' . $iArticleId ) );
	}

	/**
	 * Ads Shoutbox icon with number of shouts to the statebar
	 * @global String $wgScriptPath
	 * @param StateBar $oStateBar
	 * @param array $aTopViews
	 * @param User $oUser
	 * @param Title $oTitle
	 * @return boolean
	 */
	public function onStateBarBeforeTopViewAdd( $oStateBar, &$aTopViews, $oUser, $oTitle ) {
		$oShoutboxView = new ViewStateBarTopElement();
		$iTotalShouts = self::getTotalShouts( $oTitle->getArticleID() );
		if ( !is_object( $this->getTitle() ) || $iTotalShouts == 0 ) {
			return true;
		}
		global $wgScriptPath;
		$oShoutboxView->setKey( 'Shoutbox' );
		$oShoutboxView->setIconSrc( $wgScriptPath . '/extensions/BlueSpiceExtensions/ShoutBox/resources/images/icon-shoutbox.png' );
		$oShoutboxView->setIconAlt( wfMessage( 'bs-shoutbox-title' )->plain() );
		$oShoutboxView->setText(wfMessage( 'bs-shoutbox-n-shouts',self::getTotalShouts( $oTitle->getArticleID() ) )->text() );
		$oShoutboxView->setTextLink('#bs-shoutbox');
		$aTopViews['statebartopshoutbox'] = $oShoutboxView;
		return true;
	}

	/**
	 * Notifies a User for different actions
	 * @param String $sAction
	 * @param int $iArticleId
	 * @param int $iUserId
	 * @param String $sNick
	 * @param String $sMessage
	 * @param String $sTimestamp
	 */
	public static function notify( $sAction, $iArticleId, $iUserId, $sNick, $sMessage, $sTimestamp ) {
		switch ( $sAction ) {
			case "insert":
				$aUsers = self::getUsersMentioned( $sMessage );
				if ( count( $aUsers ) < 1 )
					break;
				self::notifyUser( "mention", $aUsers, $iArticleId, $iUserId );
		}
	}

	/**
	 * Returns an array of users being mentioned in a shoutbox message
	 * @param String $sMessage
	 * @return array Array filled with users of type User
	 */
	public static function getUsersMentioned( $sMessage ) {
		if ( empty( $sMessage ) )
			return array();
		$bResult = preg_match_all( "#@(\S*)#", $sMessage, $aMatches );
		if ( $bResult === false || $bResult < 1 )
			return array();
		$aReturn = array();
		foreach ( $aMatches[1] as $sUserName ) {
			$aReturn [] = User::newFromName( $sUserName );
		}
		return $aReturn;
	}

	/**
	 * Notifies all Users specified in the array via Echo extension if it's turned on
	 * @param String $sAction
	 * @param array $aUsers
	 * @param int $iArticleId
	 * @param int $iUserId
	 */
	public static function notifyUser( $sAction, $aUsers, $iArticleId, $iUserId ) {
		if ( class_exists( 'EchoEvent' ) ) {
			$oCurrentUser = RequestContext::getMain()->getUser();
			foreach ( $aUsers as $oUser ) {
				#if you're mentioning yourself don't send notification
				if ( $oUser->getId() === $iUserId ) {
					continue;
				}
				EchoEvent::create( array(
					'type' => 'bs-shoutbox-' . $sAction,
					'title' => Article::newFromID( $iArticleId )->getTitle(),
					'agent' => User::newFromId( $iUserId ),
					'extra' => array(
						'summary' => "",
						'titlelink' => true,
						'difflink' => array( 'diffparams' => array() ),
						'agentlink' => true,
						'mentioned-user-id' => $oUser->getId(),
						'realname' => BsCore::getUserDisplayName($oCurrentUser),
						'title' => Article::newFromID( $iArticleId )->getTitle()->getText(),
					),
				) );
			}
		} else {
			//PW(23.04.2015): TODO - get rid of BsMailer | Echo is required
			$sSubject = wfMessage(
					'bs-shoutbox-notifications-title-message-subject'
					)->plain();
			$oUser = User::newFromId( $iUserId );
			$sMessage = wfMessage(
					'bs-shoutbox-notifications-title-message-text', $oUser, BsCore::getUserDisplayName($oUser), Linker::link( Article::newFromID( $iArticleId )->getTitle() ), Linker::link( $oUser )
					)->plain();
			BsMailer::getInstance( 'MW' )->send( $aUsers, $sSubject, $sMessage );
		}
	}

	/**
	 * Handler for EchoGetDefaultNotifiedUsers hook.
	 * @param EchoEvent $event EchoEvent to get implicitly subscribed users for
	 * @param array &$users Array to append implicitly subscribed users to.
	 * @return bool true in all cases
	 */
	public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		switch ( $event->getType() ) {
			case 'bs-shoutbox-mention':
				$extra = $event->getExtra();
				if ( !$extra || !isset( $extra['mentioned-user-id'] ) ) {
					break;
				}
				$recipientId = $extra['mentioned-user-id'];
				//really ugly, but newFromId appears to be broken...
				$oDBr = wfGetDB( DB_SLAVE );
				$row = $oDBr->selectRow( 'user', '*', array( 'user_id' => (int) $recipientId ) );
				$recipient = User::newFromRow( $row );
				$users[$recipientId] = $recipient;
				//$event->setExtra('username', $recipient->);
				break;
		}
		return true;
	}

	/**
	 * Adds the Shoutbox mentions notification option
	 * @param array $notifications
	 * @param array $notificationCategories
	 * @return boolean
	 */
	public function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories ) {
		$notificationCategories['bs-shoutbox-mention-cat'] = array( 'priority' => 3 );
		$notifications['bs-shoutbox-mention'] = array( // HINT: http://www.mediawiki.org/wiki/Echo_(Notifications)/Developer_guide#Defining_a_notification
			'category' => 'bs-shoutbox-mention-cat',
			'group' => 'neutral',
			'formatter-class' => 'BsNotificationsFormatter',
			'title-message' => 'bs-shoutbox-notifications-title-message-subject',
			'flyout-message' => 'bs-shoutbox-notifications-title-message-text',
			'flyout-params' => array( 'agent', 'agentlink', 'titlelink' ),
			'email-subject-message' => 'bs-shoutbox-notifications-title-message-subject',
			'email-body-message' => 'bs-shoutbox-notifications-title-message-text',
			'email-body-params' => array( 'agent', 'agentlink', 'titlelink', 'realname', 'title' ),
			'email-body-batch-message' => 'bs-shoutbox-notifications-title-message-text',
			'email-body-batch-params' => array( 'agent', 'agentlink', 'titlelink', 'realname', 'title' ),
		);
		return true;
	}

	/**
	 * Callback from preg_replace, replaces the mention with a link to the user page
	 * @param array $sMatch
	 * @return String The link to the user page
	 */
	public static function replaceUsernameInMessage( $sMatch ) {
		$oUser = User::newFromName( $sMatch[1] );
		return Linker::link( $oUser->getUserPage(), BsCore::getUserDisplayName( $oUser ) );
	}

}
