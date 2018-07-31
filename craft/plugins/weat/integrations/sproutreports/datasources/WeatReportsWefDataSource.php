<?php
namespace Craft;

class WeatReportsWefDataSource extends SproutReportsBaseDataSource
{
	//private $_settings = craft()->globals->getSetByHandle('wef');
	public function getName()
	{
		return Craft::t('Users from WEF');
	}

	public function getDescription()
	{
		return Craft::t('Create reports about your users and user groups.');
	}

	/**
	 * @param  SproutReports_ReportModel &$report
	 *
	 * @return array|null
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$settings = craft()->globals->getSetByHandle('wef');
		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
		}


		$selectQueryString = '{{users.email}},
		{{content.field_userWefId}} AS wefId';

		$userQuery = craft()->db->createCommand()
			->select($selectQueryString)
			->from('users')
			->join('content', '{{users.id}} = {{content.elementId}}');
		$users = $userQuery->queryAll();
		$emails = array();
		$wefIds = array();
		foreach($users as $user) {
			$emails[] = strtolower($user['email']);
			$wefIds[] = $user['wefId'];
		}


		$beginDate = !empty($options['beginDate']['date']) ? $options['beginDate']['date'] : date_format(new \DateTime('now -30 days'), 'Y-m-d H:i:s');
		$date = '
		<StoredProcedureParameter>
		  <Name>@ip_ChangedOnCutOffDate</Name>
		  <Value>' . $beginDate  .'</Value>
		  <Direction>1</Direction>
		</StoredProcedureParameter>
		';
		//$date = '';
		$xml = '
		<StoredProcedureRequest xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			<StoredProcedureName>USR_USP_GET_MEMBERSHIP_DATA_DUMP_RECORDS</StoredProcedureName>
			<IsUserDefinedFunction>false</IsUserDefinedFunction>
			<IsUDFScalar>false</IsUDFScalar>
			<SPParameterList>
				<StoredProcedureParameter>
					<Name>@ip_MA_Code</Name>
					<Value>' . $settings->wefIpMaCode . '</Value>
					<Direction>1</Direction>
				</StoredProcedureParameter>
				<StoredProcedureParameter>
					<Name>@ip_Password</Name>
					<Value>' . $settings->wefIpPassword . '</Value>
					<Direction>1</Direction>
				</StoredProcedureParameter>
		' . $date . '
			</SPParameterList>
		</StoredProcedureRequest>
		';

		$xmlresult = $this->sendXmlOverPost($xml);
		$xmlelement = new \SimpleXMLElement($xmlresult);
		$username = $settings->wefUsername;
		$password = $settings->wefPassword;

		$xmlelement2 = new \SimpleXMLElement($xmlelement->Data[0]);
		$userArray = array();
		$count = count($xmlelement2);
		for($i = 0; $i<$count; $i++) {
		  $obj = $xmlelement2->Table[$i];
			/*if((string)$obj->Email_Address == 'jim.clarno@att.net') {
				echo '<pre>';
				print_r($obj);
				echo '</pre>';
			} else {
				continue;
			}*/
			// Skip the companies
			if((string)$obj->RECORD_TYPE == 'C') {
				continue;
			}
			if(in_array(strtolower((string)$obj->Email_Address), $emails) != $options['displayUserGroupColumns']) {
				continue;
			}
			if(in_array(strtolower((string)$obj->MASTER_CUSTOMER_ID), $wefIds) != $options['displayUserGroupColumns']) {
				continue;
			}
			$lastName = '';
			$lastName .= (string)$obj->LAST_NAME;
			if((string)$obj->NAME_SUFFIX) {
				$lastName .= ' ' . (string)$obj->NAME_SUFFIX;
		  }
		  $phone = '';
		  if((string)$obj->Business_Phone) {
		    $phone = (string)$obj->Business_Phone;
		  } else if((string)$obj->Mobile) {
		    $phone = (string)$obj->Mobile;
		  } else if((string)$obj->Home_Phone) {
		    $phone = (string)$obj->Home_Phone;
		  } else if((string)$obj->Business_Fax) {
		    $phone = (string)$obj->Business_Fax;
		  }

			switch((string)$obj->WEF_Membership_Product) {
				case 'Professional Member':
					$membershipType = 'professional';
					break;
				case 'Young Professional':
					$membershipType = 'youngProfessional';
					break;
				case 'Life Member':
					$membershipType = 'life';
					break;
				case 'PWO Member':
					$membershipType = 'professionalOperator';
					break;
				case 'Executive Member':
					$membershipType = 'executive';
					break;
				case 'Student Member':
				case 'SJWP':
					$membershipType = 'student';
					break;
				case 'Retired Member':
					$membershipType = 'retired';
					break;
				/*case '':
					$membershipType = '';
					break;*/
				default:
					$membershipType = 'COULD NOT FIND: ' . (string)$obj->WEF_Membership_Product;
			}

			switch((string)$obj->Status) {
				case 'Current':
					$status = 'current';
					break;
				case 'Reinstate':
					$status = 'reinstate';
					break;
				case 'Delinquent':
					$status = 'delinquent';
					break;
				case 'New':
					$status = 'new';
					break;
				default:
					$status = 'COULD NOT FIND: ' . (string)$obj->Status;
			}
		  $userArray[] = array(
				'Email' => trim((string)$obj->Email_Address),
				'WEF ID' => (string)$obj->MASTER_CUSTOMER_ID,
				'First Name' => (string)$obj->FIRST_NAME,
				//'middleName' => (string)$obj->MIDDLE_NAME,
				'Last Name'=> $lastName,
				'Title' => (string)$obj->PRIMARY_JOB_TITLE,
				'Organization Name' => (string)$obj->Organization_Name,
				'Company name' => (string)$obj->COMPANY_NAME,
				'Address 1' => (string)$obj->ADDRESS_1,
				'Address 2 ' => (string)$obj->ADDRESS_2,
				'City' => (string)$obj->CITY,
				'State' => (string)$obj->STATE,
				'Zip' => (string)$obj->POSTAL_CODE,
				'Phone number' => $phone,
				'Status' => $status,
				//'recordType' => (string)$obj->RECORD_TYPE,
				'Start Date' => $this->formatDate($obj->WEF_Join_Date),
				'Subscription End Date' => $this->formatDate($obj->Membership_Paid_Through_Date),
				//'Delinquent_Date' => $this->formatDate($obj->Delinquent_Date),
				//'GRACE_DATE' => $this->formatDate($obj->GRACE_DATE),
				'ReinstateDate' => (string)$obj->ReinstateDate,
				'Membership Type' => $membershipType,
				//'CHANGED_ON_DATE' => $this->formatDate($obj->CHANGED_ON_DATE),

				//'' => (string)$obj->,
		/*
		    UPP



		    WEF_Membership_Rate
		    Primary_MA,
		    Last_Payment_Date,
		    LastRebate,
		    Employer_Code,
		    Position_Code,
		    usr_concentration_area_code,
		    Sponsor_Name,
		    MA_Membership_Rate,
		    Bad_Address_Date,
		    BirthYear,
		    ORDER_METHOD_CODE,
		    LEVEL1,
		    LEVEL2,
		    LEVEL3,
		    DESCR,
		    Contact,
		    Sponsor,
		    CommitteFlag,
		    CHANGED_BY_USER,
		    CHANGED_ON_DATE*/
			);
		}
		/*$userArray = array_map("unserialize", array_unique(array_map("serialize", $userArray)));
		usort($userArray, function($a, $b) {
			return strcmp($a['WEF ID'], $b['WEF ID']);
		});*/

		return $userArray;
	}

	private function formatDate($date) {
		$dt = new DateTime($date, new \DateTimeZone("America/Chicago"));
		return $dt->format("Y-m-d");
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function getOptionsHtml(array $options = array())
	{
		$userStatuses = craft()->fields->getFieldByHandle('userStatus');

		foreach ($userStatuses->settings['options'] as $userStatus)
		{
			$userStatusOptions[] = array(
				'label' => $userStatus['label'],
				'value' => $userStatus['value']
			);
		}

		$optionErrors = $this->report->getErrors('options');
		$optionErrors = array_shift($optionErrors);

		return craft()->templates->render('weat/datasources/_options/wef', array(
			'userGroupOptions' => $userStatusOptions,//$userStatuses->settings->options, //$userGroupOptions,
			'options'          => count($options) ? $options : $this->report->getOptions(),
			'errors'           => $optionErrors
		));
	}

	/**
	 * Validate our data source options
	 *
	 * @param array $options
	 * @return array|bool
	 */
	public function validateOptions(array $options = array(), array &$errors = array())
	{
		/*if (empty($options['userGroups']))
		{
			$errors['userGroups'][] = Craft::t('Select at least one User Group.');

			return false;
		}*/

		return true;
	}


	private function sendXmlOverPost($xml) {

		$settings = craft()->globals->getSetByHandle('wef');
		$username = $settings->wefUsername;
		$password = $settings->wefPassword;
		if($settings->wefLive) {
			$url = $settings->wefDataServiceLiveUrl;
		} else {
			$url = $settings->wefDataServiceTestUrl;
		}
		//$url = 'https://weftst3.personifycloud.com/PersonifyDataServices/PersonifyDataWEF.svc/GetStoredProcedureDataXML'; // Test
		//$url = 'https://wefprod2.personifycloud.com/PersonifyDataServices/PersonifyDataWEF.svc/GetStoredProcedureDataXML'; // Production
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);

		// For xml, change the content-type.
		curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned

		// Send to remote and return data to caller.
		$result = curl_exec($ch);
		curl_close($ch);
		$result = str_replace('utf-16', 'utf-8', $result);
		//$result = iconv('euc-kr', 'utf-8', $result);
		//$result = mb_convert_encoding($result, 'UTF-8');
		return $result;
	}
}
