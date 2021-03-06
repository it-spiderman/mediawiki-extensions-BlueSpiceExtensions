<?php

class BSApiDashboardTasks extends BSApiTasksBase {

	protected $aTasks = array(
		'saveAdminDashboardConfig',
		'saveUserDashboardConfig'
	);

	protected function getRequiredTaskPermissions() {
		return array(
			'saveAdminDashboardConfig' => array( 'wikiadmin' ),
			'saveUserDashboardConfig' => array( 'read' )
		);
	}

	public function task_saveUserDashboardConfig( $oTaskData, $aParams ) {
		$oResponse = $this->makeStandardReturn();

		if ( $this->getUser()->isAnon() ) {
			$oResponse->message = wfMessage( 'bs-permissionerror' )->plain();
			return $oResponse;
		}

		$aPortletConfig = $oTaskData->portletConfig[0];

		json_decode( $aPortletConfig );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$oResponse->message = wfMessage( 'api-error-missingparam' )->plain();
			return $oResponse;
		}

		$oDbw = wfGetDB( DB_MASTER );
		$iUserId = $this->getUser()->getId();
		$oDbw->replace(
				'bs_dashboards_configs',
				array(
					'dc_identifier'
				),
				array(
					'dc_type' => 'user',
					'dc_identifier' => $iUserId,
					'dc_config' => serialize( $aPortletConfig ),
					'dc_timestamp' => '',
				),
				__METHOD__
		);

		$oResponse->success = true;
		return $oResponse;
	}

	public function task_saveAdminDashboardConfig( $oTaskData, $aParams ) {
		$oResponse = $this->makeStandardReturn();

		$aPortletConfig = $oTaskData->portletConfig[0];

		json_decode( $aPortletConfig );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$oResponse->message = wfMessage( 'api-error-missingparam' )->plain();
			return $oResponse;
		}

		$oDbw = wfGetDB( DB_MASTER );
		$oDbw->delete(
			'bs_dashboards_configs',
			array( 'dc_type' => 'admin' )
		);
		$oDbw->insert(
			'bs_dashboards_configs',
			array(
				'dc_type' => 'admin',
				'dc_identifier' => '',
				'dc_config' => serialize( $aPortletConfig ),
				'dc_timestamp' => '',
			),
			__METHOD__
		);

		$oResponse->success = true;
		return $oResponse;
	}

}
