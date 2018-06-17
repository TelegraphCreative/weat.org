<?php
/**
 Weat plugin for Craft CMS
 *
 Weat Command
 *
 --snip--
 Craft is built on the Yii framework and includes a command runner, yiic in ./craft/app/etc/console/yiic
 *
 Action methods are mapped to command-line commands, and begin with the prefix “action”, followed by
 a description of what the method does (for example, actionPrint().  The actionIndex() method is what
 is executed if no sub-commands are supplied, e.g.:
 *
 ./craft/app/etc/console/yiic index
 *
 The actionPrint() method above would be invoked via:
 *
 kjh print
 *
 http://spin.atomicobject.com/2015/06/16/craft-console-plugin/
 --snip--
 *
 @author    Bo Bartlett - Telegraph Creative
 @copyright Copyright (c) 2018 Bo Bartlett - Telegraph Creative
 @link      https://telegraphcreative.co/
 @package   Weat
 @since     1.0.0
 */

namespace Craft;

class WeatCommand extends BaseCommand
{
  /**
   Handle our plugin's index action command, e.g.: ./craft/app/etc/console/yiic kjh
   */
  public function actionIndex($param="")
  {
    echo 'Index';
  }

  public function formatDate($date) {
    //1970-01-01 11:55:48
    //UNIX_DATE = (EXCEL_DATE - 25569) * 86400
    //EXCEL_DATE = 25569 + (UNIX_DATE / 86400)
    if(is_numeric($date)) {
      //$unix = ($date - 25569) * 86400;
      $unix = (($date - 25569) * 86400) + 43200; //combat the UTC stuff
      return date("Y-m-d H:i:s", $unix);
    } else {
      return NULL;
    }
  }

  private function writeFailure($data, $reason = 'catchall') {
    echo $reason . "\n";
    if (($handle = fopen("craft/plugins/weat/consolecommands/" . $reason . ".csv", "a")) !== FALSE) {
      fputcsv($handle, $data);
      fclose($handle);
    } else {
      echo "something went wrong\n";
    }
  }

  private function writeLog($data, $reason = 'catchall') {
    echo $reason . "\n";
    if (($handle = fopen("craft/plugins/weat/consolecommands/" . $reason . ".csv", "a")) !== FALSE) {
      fwrite($handle, $data);
      fclose($handle);
    } else {
      echo "something went wrong\n";
    }
  }
  public function actionGroup() {
    //$i = 10;
    //$j = 30;
    //while($i++ < $j) {
      //$user = craft()->users->getUserById($i);
      //https://docs.craftcms.com/api/v2/services/UserGroupsService.html#assignUserToGroups-detail
      //craft()->userGroups->assignUserToGroups(5380, 1);
    //}
    /*
    $records = UserRecord::model()->findAllByAttributes(['elementId' => $key], ['order' => 'id desc']);
    return Weat_RegistrationModel::populateModels($records, 'id');

    $eventRecords = Venti_EventRecord::model()->findAllByAttributes(["groupId"=>$groupId]);
    $eventModels = Venti_EventModel::populateModels($eventRecords, 'id');
    $criteria = new CDbCriteria;
    $criteria->condition = "date_start >= '$date_start' AND date_end <= '$date_end'";
    $model = TemporadaAlta::model()->find($criteria);
    $this->_usersById[$userId] = UserModel::populateModel($userRecord);*/

    /*$userRecord = UserRecord::model()->find(array(
      'condition' => "userSubscriptionEndDate >= :userSubscriptionEndDate",
      'params' => array(':userSubscriptionEndDate' => '2020-10-10'),
      //'condition' => 'username=:usernameOrEmail OR email=:usernameOrEmail',
      //'params' => array(':usernameOrEmail' => $usernameOrEmail),
    ));

    if ($userRecord)
    {
      $users = UserModel::populateModel($userRecord);
      echo $users->first()->email;
    }*/
    /*$userRecords = UserRecord::model()->findAll();
    if ($userRecords)
    {
      $i = 0;
      foreach($userRecords as $userRecord) {
        //echo $userRecord->email . "\n";
        //echo $userRecord->userSubscriptionEndDate . "\n";
        echo craft()->users->getUserById($userRecord->id)->getContent()->getAttribute('userSubscriptionEndDate') . "\n";

        //echo $userRecord->getContent()->getAttribute('userSubscriptionEndDate');
        if($i++ > 100){
          break;
        }
      }
    } else {
      echo 'nothing found';
    }*/
    //$users = craft()->users->find()->one();
    $criteria = craft()->elements->getCriteria(ElementType::User);
    //$criteria->affiliateGroup = 'myGroup';
    //$criteria->order = 'studentCount ASC';
    //$criteria->userSubscriptionEndDate = array('and', '> 2017-12-31 23:59:59', '< 2018-05-31 00:00:00');
    $criteria->userSubscriptionEndDate = array('and', '> 2018-06-01 00:00:00', '< 2030-05-31 00:00:00');
    $criteria->status = null;
    $criteria->limit = null;
    //$criteria->limit = 1;
    $users = $criteria->find();
    foreach($users as $user) {
      echo $user->email . ' : ' . $user->userSubscriptionEndDate . "\n";
    }

  }

  public function actionUrl($param) {
    $user = craft()->users->getUserById($param);
    $url = craft()->users->getPasswordResetUrl($user);
    //$url = craft()->users->sendActivationEmail($user);
    echo $url;
  }
  public function actionEmailwelcome($param = 1) {
    /*
    Come back and do this
    for(users id n <> m){
      do all this stuff
    }
    */
    $user = craft()->users->getUserById($param);
    $url = craft()->users->getPasswordResetUrl($user);
    $email = new EmailModel();
    $email->toEmail = $user->email; //'bo@wearetelegraph.com';
    $email->subject = 'WEAT Website Login and Password – Important Information!';
    $email->body    =
'Dear ' . $user->fullName . ',

First, thank you for your support and membership to the leading water quality organization in Texas!

You may have noticed that WEAT’s logo and website have changed. We’re delighted at our evolution and hope you’re pleased to use our streamlined database and website.

Please take a moment to go to www.weat.org and log into the new WEAT website. Your email address is your login and you will need to [reset your password](' . $url . ').

The new website and database will allow for a much improved member experience with easy online membership and renewal, conference registration, and public information at your fingertips. Peruse the [upcoming events](https://www.weat.org/events) and check out our [featured committee page](https://www.weat.org/learn) with public information and timely posts. While many excellent pages are now live, we have quite a few others that will come online soon and are geared specifically to various member demographics, public education, and our water quality advocacy work. So, check back in the next few weeks.

And as always, please let us know how we can improve our programs and communication!

Best regards,

Your WEAT Team';
    //$email->body    = 'Testing to, ' . $user->name . "?\n\n" . $url;
    if (!$email->htmlBody) {
      // Auto-generate the HTML content
      $email->htmlBody = StringHelper::parseMarkdown($email->body);
    }
    $email->htmlBody = "{% extends '_emails/template' %}\n".
			"{% set body %}\n".
			$email->htmlBody.
			"{% endset %}\n";
    craft()->email->sendEmail($email);
    $data = $user->email . "," . $user->id . "," . $user->userWefId . "," . time() . "\n";
    $this->writeLog($data, 'sent-welcome-email');
  }
  public function actionImport($param) {
    $row = 1;
    //if (($handle = fopen("craft/plugins/weat/consolecommands/import.csv", "r")) !== FALSE) {
    //if (($handle = fopen("craft/plugins/weat/consolecommands/weat.csv", "r")) !== FALSE) {
    //if (($handle = fopen("craft/plugins/weat/consolecommands/final.csv", "r")) !== FALSE) {
    if (($handle = fopen("craft/plugins/weat/consolecommands/" . $param . ".csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {

        if($row++ > 5) {
          //break;
        }
        $user = new UserModel();
        //$user->username  = $data[16];
        $user->getContent()->abilaId = $data[0];
        //$user-> = $data[1];
        $user->email = $user->username = $data[2];
        $user->getContent()->userStartDate = $this->formatDate($data[3]); //mbr_join_date
        $user->getContent()->userAddress1 = $data[4];
        $user->getContent()->userAddress2 = $data[5];
        //$user-> = $data[6];
        $user->getContent()->userAddressCity = $data[7];
        $user->getContent()->userAddressState = $data[8];
        $user->getContent()->userAddressZip = $data[9];
        //$user-> = $data[10];
        //$user-> = $data[11];
        $user->getContent()->userPhoneNumber = $data[12];
        $user->getContent()->userPhoneExtension = $data[13];
        $user->getContent()->userCompanyName = $data[14];
        $user->getContent()->userSalutation = $data[15];
        $user->firstName = $data[16];
        //$user-> = $data[17];
        $user->lastName  = $data[18];
        $user->getContent()->userTitle = $data[19];
        //$user->getContent()->userTitle = $data[20]; //mbr_mbt_key
        switch($data[20]) {
          case "CE280C6D-7634-4737-AF89-D16C6C39CCA2":
            $user->getContent()->userMembershipType = 'weat';
            break;
            case "3AD405D2-6D2F-4268-A309-7385AB7778D4":
              $user->getContent()->userMembershipType = 'academic';
              break;
            case "E12C2D72-F4F4-475E-8B0B-2513EC3DA8AE": //	Corporate Membership
              $user->getContent()->userMembershipType = 'corporate';
              break;
            case "7D52EF50-367A-4437-AFFA-98C5825BCD34": //	Executive
              $user->getContent()->userMembershipType = 'executive';
              break;
            case "72DA2D91-6DA0-48AD-A4E2-A0A32C2E1CFE"://	Student
              $user->getContent()->userMembershipType = 'student';
              break;
            case "925CE75C-7DD0-4E92-937C-3AC63A2D1A89": //	Professional Wastewater Operators
            case "A44B7695-272F-41D9-B619-3C6868BBD5A2": //	PWO
              $user->getContent()->userMembershipType = 'professionalOperator';
              break;
            case "B6E65705-65A3-403A-87AB-8B447DBD0C9E": //	Professional
              $user->getContent()->userMembershipType = 'professional';
              break;
            case "2C1AAA1E-9F8F-49B9-86EF-A62765F5B241": //	Young Professional
              $user->getContent()->userMembershipType = 'youngProfessional';
              break;
            case "B41A1FD0-D5D5-4471-ABF8-9BFBEE24ED68": //	Young Professional
              $user->getContent()->userMembershipType = 'weatOnlyUtility';
              break;
            case "AA8AE950-7146-428A-BDB3-F644FB1F5BDC":  //Life
              $user->getContent()->userMembershipType = 'life';
              break;
            case "0438575E-9922-4BA3-8ED2-61A3BFEDD4AC":  //Honorary
            case "098F2DC2-F28F-486D-A72D-268E979DAA49":  //Retired
            case "E7EA47A4-167F-4369-93CE-AFB7E326AD75":  //Test Membership
            case "DF54EE04-8DE5-47A9-A1C0-412F6F42D805":  //Half-Life Active
            case "EB31C151-072D-4E97-806C-9C8D2E97AD5A":	//Half Life Active
            case "09E161CC-C30C-43ED-9A21-DDA3992FAF40":	//Complimentary
            case "F49618F1-A7BC-4D14-8274-24E91262AEDF":	//Contact
            case "": //Nothing
              $user->getContent()->userMembershipType = 'bad';
              break;
            default:
              echo $data[20] . "\n";
              break;

        }
        //$user->getContent()->userTitle = $data[21]; //mbr_join_date2
        $user->getContent()->userSubscriptionStartDate = $this->formatDate($data[22]); //mbr_renew_date
        $user->getContent()->userSubscriptionEndDate = $this->formatDate($data[23]); //mbr_expire_date
        if(empty($data[2])) {
          $this->writeFailure($data, 'emptyemail');
          continue;
        }
        if(empty($data[23])) {
          $this->writeFailure($data, 'emptyexpire');
          continue;
        }
        if($user->getContent()->userMembershipType == 'bad') {
          $this->writeFailure($data, 'membershiptype');
          continue;
        }
        $duplicateEmails = array('cynde.berger@cleburne.net','pw@cityofkyle.com','publicworks@cityofforney.org','smb@jandsvalve.com','channys@casengineers.com','pcfwsd2@eastex.net','mbrogdon@ntmwd.com','hromine@BeaumontTexas.gov','rmo@freese.com','grubbsr@ci.nacogdoches.tx.us','cbain@bmbi.com','mike712010@hotmail.com','jtheurer@nbutexas.com','ziqbal@ci.pasadena.tx.us','davidr@plano.gov','svazquez@gbra.org','hlainc@austin.rr.com','julie@weat.org','ljuarez@sanmarcostx.gov','jamf@suddenlinkmail.com','mpaz@addisontx.gov','bwsa@bwsa.org', 'hneedham@sptc.net','siegerc@trinityra.org','spinkston@jonesborocwl.org','wgilliland@ntmwd.com','adt@jacobmartin.com','kwalker@cityofconroe.org','emontana@gbra.org','silviam@cctexas.com','k2@k2svc.com','nooreen5@yahoo.com','cbelsky@crespoinc.com','jcolunga@LMWD.org','revans@longviewtexas.gov','apoints@beltontexas.gov','tws@freese.com','phyllis.jones@dallascityhall.com','margretc@cctexas.com','lruiz@weatherfordtx.gov','keith.oconnor@aecom.com','Lhicks@huntsvilletx.gov','rnelson@hobaspipe.com','rogerf@austin.rr.com','joan@uteconsultants.com','bakerc@ci.nacogdoches.tx.us');
        $inthesystem = array('vxwresources@gmail.com', 'victoria@wearetelegraph.com', 'tyler@wearetelegraph.com', 'tim@odorexpert.com', 'tiffanycurrie11@hotmail.com', 'stp@valveandequipment.com', 'smiller@ntmwd.com', 'SCOTTY@jcsindustries.us.com', 'rudyv92@gmail.com', 'RSchafer@Willdan.com', 'ptsunster@gmail.com', 'pmattocks@precisionpumpsystems.com', 'pgupta07@gmail.com', 'petersencm@cdmsmith.com', 'Paul.Brochi@tceq.texas.gov', 'natalie@wearetelegraph.com', 'mzerbe@precisionpumpsystems.com', 'melissa@weat.org', 'md_ahsan.ullah@students.tamuk.edu', 'matias@weat.org', 'marc@cruzhogan.net', 'lindseyb@wearetelegraph.com', 'kyle@hayesengineering.net', 'kwchan7511@gmail.com', 'kstowers@cpyi.com', 'ksmohammed@sig-auto.com', 'krflinn@garverusa.com', 'kpf928@yahoo.com', 'Josalyn.Millon@tceq.texas.gov', 'jon-paul.dixon@hatch.com', 'John.Crawford@cityofcarrollton.com', 'jody.whitcomb@dallascityhall.com', 'jmnahrgang@gmail.com', 'jguerra@apaienv.com', 'jalbertm@trinityra.org', 'huntj@trinityra.org', 'heather.cooke@austintexas.gov', 'gluke@gaiconsulting.com', 'george.bowden@freese.com', 'EvansCC@bv.com', 'erin.mills@freese.com', 'einlong@gmail.com', 'CRYSTAL.BOWMAN@CITYOFCARROLLTON.COM', 'crhidalgo@sig-auto.com', 'courtneyb@wearetelegraph.com', 'chuck@egsw.us', 'bo@wearetelegraph.com', 'batsonam@cdmsmith.com', 'arudolph@weatherfordtx.gov', 'armartin@uspipe.com', 'aface@me.com', 'admin@weat.org');
        if(in_array($user->email, $inthesystem)) {
          $this->writeFailure($data, 'inthesystem');
          continue;
        } elseif(in_array($user->email, $duplicateEmails)) {
          $this->writeFailure($data, 'duplicatemails');
          continue;
        }
        $user->getContent()->userTerminateDate = $this->formatDate($data[24]); //mbr_terminate_date

        $user->pending = true;
        $success = craft()->users->saveUser($user);
        //print_r($user);
        //$this->writeFailure($data, 'finalfinal');

        //$success = true;
        if (!$success)
        {
            echo 'save failed' . $data[0];
            $this->writeFailure($data, 'failed');
            echo $user->firstName . " " . $user->lastName . " " . $user->email . "\n";
            continue;
        } else {
          echo 'success ' . $data[2] . "\n";
        }
      }
      fclose($handle);
      }
  }



  public function actionImportDuplicates() {
    $row = 1;
    if (($handle = fopen("craft/plugins/weat/consolecommands/process/inthesystem.csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {


        $user = craft()->users->getUserByUsernameOrEmail($data[2]);
        //$user->username  = $data[16];
        $user->getContent()->abilaId = $data[0];
        //$user-> = $data[1];
        //$user->email = $user->username = $data[2];
        if(empty($user->getContent()->userStartDate)) {
          $user->getContent()->userStartDate = $this->formatDate($data[3]); //mbr_join_date
        }
        if(empty($user->getContent()->userAddress1)) {
          $user->getContent()->userAddress1 = $data[4];
        }
        if(empty($user->getContent()->userAddress2)) {
          $user->getContent()->userAddress2 = $data[5];
        }
        if(empty($user->getContent()->userAddressCity)) {
          //$user-> = $data[6];
          $user->getContent()->userAddressCity = $data[7];
        }
        if(empty($user->getContent()->userAddressState)) {
          $user->getContent()->userAddressState = $data[8];
        }
        if(empty($user->getContent()->userAddressZip)) {
          $user->getContent()->userAddressZip = $data[9];
        }
        if(empty($user->getContent()->userPhoneNumber)) {
          //$user-> = $data[10];
          //$user-> = $data[11];
          $user->getContent()->userPhoneNumber = $data[12];
        }
        if(empty($user->getContent()->userPhoneExtension)) {
          $user->getContent()->userPhoneExtension = $data[13];
        }
        if(empty($user->getContent()->userCompanyName)) {
          $user->getContent()->userCompanyName = $data[14];
        }
        if(empty($user->getContent()->userSalutation)) {
          $user->getContent()->userSalutation = $data[15];
        }
        if(empty($user->firstName)) {
          $user->firstName = $data[16];
        }
          //$user-> = $data[17];
        if(empty($user->lastName)) {
          $user->lastName  = $data[18];
        }

        if(empty($user->getContent()->userTitle)) {
          $user->getContent()->userTitle = $data[19];
        }
        //$user->getContent()->userTitle = $data[20]; //mbr_mbt_key
        if(empty($user->getContent()->userMembershipType)) {
          switch($data[20]) {
            case "CE280C6D-7634-4737-AF89-D16C6C39CCA2":
              $user->getContent()->userMembershipType = 'weat';
              break;
              case "3AD405D2-6D2F-4268-A309-7385AB7778D4":
                $user->getContent()->userMembershipType = 'academic';
                break;
              case "E12C2D72-F4F4-475E-8B0B-2513EC3DA8AE": //	Corporate Membership
                $user->getContent()->userMembershipType = 'corporate';
                break;
              case "7D52EF50-367A-4437-AFFA-98C5825BCD34": //	Executive
                $user->getContent()->userMembershipType = 'executive';
                break;
              case "72DA2D91-6DA0-48AD-A4E2-A0A32C2E1CFE"://	Student
                $user->getContent()->userMembershipType = 'student';
                break;
              case "925CE75C-7DD0-4E92-937C-3AC63A2D1A89": //	Professional Wastewater Operators
              case "A44B7695-272F-41D9-B619-3C6868BBD5A2": //	PWO
                $user->getContent()->userMembershipType = 'professionalOperator';
                break;
              case "B6E65705-65A3-403A-87AB-8B447DBD0C9E": //	Professional
                $user->getContent()->userMembershipType = 'professional';
                break;
              case "2C1AAA1E-9F8F-49B9-86EF-A62765F5B241": //	Young Professional
                $user->getContent()->userMembershipType = 'youngProfessional';
                break;
              case "B41A1FD0-D5D5-4471-ABF8-9BFBEE24ED68": //	Young Professional
                $user->getContent()->userMembershipType = 'weatOnlyUtility';
                break;
              case "AA8AE950-7146-428A-BDB3-F644FB1F5BDC":  //Life
              case "0438575E-9922-4BA3-8ED2-61A3BFEDD4AC":  //Honorary
              case "098F2DC2-F28F-486D-A72D-268E979DAA49":  //Retired
              case "E7EA47A4-167F-4369-93CE-AFB7E326AD75":  //Test Membership
              case "DF54EE04-8DE5-47A9-A1C0-412F6F42D805":  //Half-Life Active
              case "EB31C151-072D-4E97-806C-9C8D2E97AD5A":	//Half Life Active
              case "09E161CC-C30C-43ED-9A21-DDA3992FAF40":	//Complimentary
              case "F49618F1-A7BC-4D14-8274-24E91262AEDF":	//Contact
              case "": //Nothing
                $user->getContent()->userMembershipType = 'bad';
                break;
              default:
                echo $data[20] . "\n";
                break;

          }
        }
        //$user->getContent()->userTitle = $data[21]; //mbr_join_date2
        if(empty($user->getContent()->userSubscriptionStartDate)) {
          $user->getContent()->userSubscriptionStartDate = $this->formatDate($data[22]); //mbr_renew_date
        }
        if(empty($user->getContent()->userSubscriptionEndDate)) {
          $user->getContent()->userSubscriptionEndDate = $this->formatDate($data[23]); //mbr_expire_date
        }
        if(empty($data[2])) {
          $this->writeFailure($data, 'emptyemail');
          continue;
        }
        if(empty($data[23])) {
          $this->writeFailure($data, 'emptyexpire');
          continue;
        }
        if($user->getContent()->userMembershipType == 'bad') {
          $this->writeFailure($data, 'membershiptype');
          continue;
        }
        if(empty($user->getContent()->userTerminateDate)) {
          $user->getContent()->userTerminateDate = $this->formatDate($data[24]); //mbr_terminate_date
        }
        $success = craft()->users->saveUser($user);
        echo "\n\n";

        //$success = true;
        if (!$success)
        {
            echo 'save failed' . $data[0];
            echo $user->firstName . " " . $user->lastName . " " . $user->email . "\n";
            $this->writeFailure($data, 'failed');
            break;
        }
      }
      fclose($handle);
      }
  }

  public function actionSection() {
    if (($handle = fopen("craft/plugins/weat/consolecommands/_workingfiles/sections3.csv", "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
        $email = $data[0];
        $section = $data[1];
        if(empty($email) || empty($section)) {
          continue;
        }
        $user = craft()->users->getUserByUsernameOrEmail($email);
        if(empty($user)) {
          //echo $email . "," . $section . "\n";
          $this->writeFailure($data, 'couldnotfinduser');
          continue;
        }
        if($section == 'Abilene') {
          $section = 'abilene';
        } else if($section == 'Amarillo') {
          $section = 'amarillo';
        } else if($section == 'Beaumont/Port Arthur') {
          $section = 'beaumontPortArthur';
        } else if($section == 'Bryan/College Station') {
          $section = 'bryanCollegeStation';
        } else if($section == 'Austin') {
          $section = 'centralTexas';
        } else if($section == 'Harlingen/Brownsville') {
          $section = 'harlingenBrownsville';
        } else if($section == 'Out of State') {
          $section = 'outOfState';
        } else if($section == 'Dallas/Ft. Worth') {
          $section = 'northTexas';
        } else if($section == 'Houston/Galveston') {
          $section = 'southeastTexas';
        } else if($section == 'San Antonio') {
          $section = 'sanAntonio';
        } else if($section == 'Midland/Odessa') {
          $section = 'midlandOdessa';
        } else if($section == 'Corpus Christi') {
          $section = 'coastalBend';
        } else if($section == 'Longview/Tyler/Texarkana') {
          $section = 'northeastTexas';
        } else if($section == 'El Paso') {
          $section = 'elPaso';
        } else if($section == 'Lubbock') {
          $section = 'lubbock';
        } else if($section == 'Wichita Falls') {
          $section = 'wichitaFalls';
        } else if($section == 'Waco') {
          $section = 'waco';
        } else if($section == 'Del Rio/Ulvalde') {
          $section = 'delRioUvalde';
        } else if($section == 'Port Lavaca') {
          $section = 'portLavaca';
        } else if($section == 'Laredo') {
          $section = 'laredo';
        } else {
          echo "-" . $section . "-\n";
        }
        $user->getContent()->userSection = $section;
        $success = craft()->users->saveUser($user);
        if (!$success) {
          $this->writeFailure($data, 'couldnotsave');
        }
      }
      fclose($handle);
    }
  }


    public function actionMissing() {
      if (($handle = fopen("craft/plugins/weat/consolecommands/_workingfiles/findmissing.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
          $email = $data[2];
          $first = $data[16];
          $last = $data[18];
          $user = craft()->users->getUserByUsernameOrEmail($email);
          if(empty($user)) {
            echo $email . "," . $first . "," . $last . "\n";
            $this->writeFailure($data, 'whyaretheymissing');
            continue;
          }
        }
        fclose($handle);
      }
    }

  public function actionExpiring() {
    $criteria = craft()->elements->getCriteria(ElementType::User);
    //$criteria->affiliateGroup = 'myGroup';
    //$criteria->order = 'studentCount ASC';
    //$criteria->userSubscriptionEndDate = array('and', '> 2017-12-31 23:59:59', '< 2018-05-31 00:00:00');
    $criteria->userSubscriptionEndDate = array('and', '> 2018-06-01 00:00:00', '< 2018-08-01 00:00:00');
    $criteria->userMembershipType = array('or', '= weatOnlyUtility', '= weat');
    $criteria->status = null;
    $criteria->limit = null;
    //$criteria->limit = 1;
    $users = $criteria->find();
    foreach($users as $user) {
      echo($user->fullName . ',' . $user->userSubscriptionEndDate . ',' . $user->userMembershipType . ',' . $user->userWefId . "\n");
      $this->actionExpire30($user->id);
    }
  }


  public function actionEmailexpired() {
    $criteria = craft()->elements->getCriteria(ElementType::User);
    //$criteria->affiliateGroup = 'myGroup';
    //$criteria->order = 'studentCount ASC';
    //$criteria->userSubscriptionEndDate = array('and', '> 2017-12-31 23:59:59', '< 2018-05-31 00:00:00');
    $criteria->userSubscriptionEndDate = array('and', '> 2018-01-01 00:00:00', '< 2018-06-01 00:00:00');
    $criteria->userMembershipType = array('or', '= weatOnlyUtility', '= weat');
    $criteria->status = null;
    $criteria->limit = null;
    //$criteria->limit = 1;
    $users = $criteria->find();
    foreach($users as $user) {
      if($user->id == 5534 || $user->id == 3474 || $user->id == 1270) {
        continue;
      }
      echo($user->fullName . ',' . $user->userSubscriptionEndDate . ',' . $user->userMembershipType . ',' . $user->userWefId . "\n");
      $this->actionExpired($user->id);
    }
  }


  public function actionFresh() {
    //if (($handle = fopen("craft/plugins/weat/consolecommands/_workingfiles/full-weat-list.csv", "r")) !== FALSE) {
    if (($handle = fopen("craft/plugins/weat/consolecommands/last-duplicates-good.csv", "r")) !== FALSE) {
      //$duplicateEmails = array();
      //$duplicateEmailArray = array('cbain@bmbi.com','bakerc@ci.nacogdoches.tx.us','mbrogdon@ntmwd.com','hromine@beaumonttexas.gov','lhicks@huntsvilletx.gov','mike712010@hotmail.com','mpaz@addisontx.gov','david.dits@cityofcarrollton.com','hlainc@austin.rr.com','revans@longviewtexas.gov','pw@cityofkyle.com','rwidboom@ntmwd.com','ljuarez@sanmarcostx.gov','kiran.makanji@dallascityhall.com','channys@casengineers.com','hneedham@sptc.net','kwalker@cityofconroe.org','spinkston@jonesborocwl.org');
      $duplicateEmailArray = array('cbain@bmbi.com','bakerc@ci.nacogdoches.tx.us','mbrogdon@ntmwd.com','hromine@beaumonttexas.gov','lhicks@huntsvilletx.gov','mike712010@hotmail.com','mpaz@addisontx.gov','david.dits@cityofcarrollton.com','hlainc@austin.rr.com','revans@longviewtexas.gov','pw@cityofkyle.com','rwidboom@ntmwd.com','ljuarez@sanmarcostx.gov','kiran.makanji@dallascityhall.com','channys@casengineers.com','hneedham@sptc.net','kwalker@cityofconroe.org','spinkston@jonesborocwl.org');
      while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
        $email = $data[18];
        if(empty($email)) {
          //echo "MISSING EMAIL\n";//$data[0] . "\n";
          continue;
        } else {
          //$duplicateEmails[] = strtolower($email);
          if(in_array(strtolower($email), $duplicateEmailArray)) {
            //$this->writeFailure($data, 'last-duplicates');
            //echo $email . " Duplicate\n";
            //continue;
          }
        }
        $user = craft()->users->getUserByUsernameOrEmail($email);
        if(!$user) {
          //echo "Not a Duplicate\n";
          $user = new UserModel();
        } else {
          if($user->status == 'active') {
            //echo "Active\n";
            //$this->writeFailure($data, 'last-active');
            //continue;
          } else {
            //$this->writeFailure($data, 'last-step2');
          }
          //continue;
        }

        //$user->username  = $data[16];
        //$user->getContent()->abilaId = $data[0];
        //$user-> = $data[1];

        //$user->getContent()->userStartDate = $this->formatDate($data[3]); //mbr_join_date

        //$user->getContent()->abilaId = $data[0];

        //$this->formatDate($data[3]); //mbr_join_date
        //$data[8];
        //$data[9];

        //$user->getContent()->userSalutation = $data[15];
        //$data[0]; //Customer Type
        //$data[1];//Member Flag
        //$data[2];// EMPTY
        //$data[3];//Life Member Flag
        //$data[4];//Sort Name
        //$data[5];//Full Name
        //$data[6];//EMPTY

        //$user->getContent()->userCompanyName = $data[7];//Organization Name
        $user->getContent()->userWefId = $data[8];//ID
        //$data[9];//Add Date
        $user->getContent()->userAddress1 = $data[10];//Address Line 1
        $user->getContent()->userAddress2 = $data[11];//Address Line 2
        $user->getContent()->userAddressCity = $data[12];//City
        $user->getContent()->userAddressState = $data[13];//State/Territory
        $user->getContent()->userAddressZip = $data[14];//Postal Code
        $data[15];//Country
        //$data[16];//Add Date
        //$data[17];//Change Date
        $user->email = $user->username = $data[18];//E-Mail Address
        //$data[19];//Add Date
        //$data[20];//Change Date
        $user->getContent()->userPhoneNumber = $data[21];//Phone Number
        $user->getContent()->userPhoneExtension = $data[22];//Extension
        //$data[23];//Add Date
        //$data[24];//Change Date
        //$data[25];//Fax Number
        //$data[26];//Extension
        $user->firstName = $data[27];//First Name
        $user->lastName  = $data[28];//Last Name
        $user->getContent()->userMiddleName = $data[29];//Middle Name
        //$data[30];//Designation
        $user->getContent()->userTitle = $data[31];//Title
        //$data[1];//Individual Type
        //$data[1];//Deceased
        //$data[1];//String 1
        //$data[1];//Full Name
        //$data[1];//String 1*/
        $user->getContent()->userStartDate = $this->_translateDate($data[37]);//Join Date
        //$data[1];//Rejoin Date
        $user->getContent()->userSubscriptionStartDate = $this->_translateDate($data[39]);//Effective Date
        $user->getContent()->userSubscriptionEndDate = $this->_translateDate($data[40]);//Expire Date
        //$data[1];//Termination Date
        //$data[1];//Terminate Reason Code
        //$data[1];//Referral Name
        $user->getContent()->userStatus = $this->_translateStatus($data[44]);//Member Status
        $user->getContent()->userMembershipType = $this->_translateType($data[45]);//Product Name
        //$data[46];//Join Date
        //$data[47];//Rejoin Date
        //$data[48];//Effective Date
        //$data[49];//Expire Date
        //$data[50];//Chapter Affiliate Name
        $user->getContent()->userSection = $this->_translateSection($data[51]);//Chapter Affiliate Name
        echo $user->firstName . "\n";
        echo $user->lastName . "\n";
        echo $user->email . "\n";
        echo $user->getContent()->userStatus. "\n";
        echo $user->getContent()->userMembershipType . "\n";
        echo $user->getContent()->userSubscriptionEndDate . "\n";
        echo $user->getContent()->userSection . "\n";
        $success = craft()->users->saveUser($user);

        //$success = true;
        if (!$success) {
            echo 'save failed' . $data[0];
            echo $user->firstName . " " . $user->lastName . " " . $user->email . "\n";
            $this->writeFailure($data, 'last-failed-active');
        }



        //$userWefId = $data[6];
        //$section = $data[18];
        //echo $user->getContent()->userSubscriptionStartDate . "\n";
        //echo $data[40] . "\n";
        //echo $user->lastName . " ". $user->getContent()->userAddressTitle . " " . $user->getContent()->userAddressZip . " " . $user->getContent()->userCompanyName . "\n";

        //echo $email . "\n";


      }
      /*$dups = array();
      foreach(array_count_values($duplicateEmails) as $val => $c)
      if($c > 1) {
        echo "'" . $val . "',";
      }*/
    }

  }


  private function _translateDate($date) {
    return date('Y-m-d H:i:s', strtotime($date) + 43200);
  }
  private function _translateType($type) {
    if($type == 'Young Professional') {
      $type = 'youngProfessional';
    } else if($type == 'Professional') {
      $type = 'professional';
    } else if($type == 'MA - WEAT ONLY') {
      $type = 'weat';
    } else if($type == 'Life') {
      $type = 'life';
    } else if($type == 'Student') {
      $type = 'student';
    } else if($type == 'PWO' || $type == 'Professional Wastewater Operators') {
      $type = 'professionalOperator';
    } else if($type == 'Executive') {
      $type = 'executive';
    } else if($type == 'Corporate Membership') {
      $type = 'corporate';
    } else if($type == "Academic") {
      $type = 'academic';
    } else {
      //echo $type . "\n";
      $type = '';
    }
    return $type;
  }

  private function _translateStatus($status) {
    if($status == 'NEW') {
      $status = 'new';
    } else if($status == 'CURRENT') {
      $status = 'current';
    } else if($status == 'DELINQUENT') {
      $status = 'delinquent';
    } else if($status == 'REINSTATE') {
      $status = 'reinstate';
    } else if($status == 'On Hold') {
      $status = 'onHold';
    } else if($status == 'Dropped') {
      $status = 'dropped';
    } else {
      //echo $status . "\n";
      $status = '';
    }
    return $status;/*
			<option value="dropped">Dropped</option>
			<option value="">On Hold</option>
			<option value="complimentary">Complimentary</option>*/
  }

  private function _translateSection($section) {
    if($section == 'Abilene') {
      $section = 'abilene';
    } else if($section == 'Amarillo') {
      $section = 'amarillo';
    } else if($section == 'Beaumont/Port Arthur') {
      $section = 'beaumontPortArthur';
    } else if($section == 'Bryan/College Station') {
      $section = 'bryanCollegeStation';
    } else if($section == 'Austin') {
      $section = 'centralTexas';
    } else if($section == 'Harlingen/Brownsville') {
      $section = 'harlingenBrownsville';
    } else if($section == 'Out of State') {
      $section = 'outOfState';
    } else if($section == 'Dallas/Ft. Worth') {
      $section = 'northTexas';
    } else if($section == 'Houston/Galveston') {
      $section = 'southeastTexas';
    } else if($section == 'San Antonio') {
      $section = 'sanAntonio';
    } else if($section == 'Midland/Odessa') {
      $section = 'midlandOdessa';
    } else if($section == 'Corpus Christi') {
      $section = 'coastalBend';
    } else if($section == 'Longview/Tyler/Texarkana') {
      $section = 'northeastTexas';
    } else if($section == 'El Paso') {
      $section = 'elPaso';
    } else if($section == 'Lubbock') {
      $section = 'lubbock';
    } else if($section == 'Wichita Falls') {
      $section = 'wichitaFalls';
    } else if($section == 'Waco') {
      $section = 'waco';
    } else if($section == 'Del Rio/Ulvalde') {
      $section = 'delRioUvalde';
    } else if($section == 'Port Lavaca') {
      $section = 'portLavaca';
    } else if($section == 'Laredo') {
      $section = 'laredo';
    } else {
      //echo $section . "\n";
      $section = '';
    }
    return $section;
  }

  public function actionExisting(){

  }

  public function actionExpire30($param = 1) {
    $user = craft()->users->getUserById($param);
    $email = new EmailModel();
    $email->toEmail = $user->email;
    $email->subject = 'WEAT Membership Renewal Notice';
    $email->body    =
'Dear ' . $user->fullName . ',

We’d like to take this opportunity to thank you for choosing to be a WEAT member over the past year. I hope you were able to take advantage of the member discounts at the many knowledge sharing opportunities through conferences and webinars; learn from information on the latest trends in technology and best in class projects around the state featured in *Texas WET*; benefit from WEAT advocacy efforts at the Texas Legislature, TCEQ, and EPA; and enjoy professional growth and networking space with your WEAT colleagues and WEAT community at Texas Water!

It is that time of the year again that we ask that you consider the value and benefit of a WEAT membership and renew as your membership **expires on ' .  date('F j, Y', strtotime($user->userSubscriptionEndDate)) . '**. An annual WEAT membership remains only $50, which allows for discounts to conferences, free WEAT webinars, section membership, and a $200 savings on Texas Water registration. Your WEAT team has worked diligently to develop a new website and database that provides for a much easier renewal process and an annual auto-renewal function! Please go to www.weat.org and login to renew your membership today.

' . $user->fullName . '  ' . '
' . ($user->userCompanyName ? $user->userCompanyName .'  ' . '' : '') . '
' . ($user->userAddress1 ? $user->userAddress1 . '  ' . '' : '') . '
' . ($user->userAddress2 ? $user->userAddress2 . '  ' . '' : '') . '
' . $user->userAddressCity . ', ' . $user->userAddressState . ' ' . $user->userAddressZip . '

**INVOICE**

Customer ID: ' . $user->userWefId . '  ' . '
Invoice Number: ' . $user->id . '  ' . '
Invoice Date: ' . date("F j, Y") . '  ' . '
Notes: Renewal invoice

Item: MA - WEAT ONLY  ' . '
Unit Price: $50.00  ' . '
Quantity: 1  ' . '
Amount: $50.00  ' . '
Term: ' . date('F j, Y', strtotime($user->userSubscriptionEndDate)) . ' - ' . date('F j, Y', strtotime(date('F j, Y', strtotime($user->userSubscriptionEndDate)) . " + 365 day")) . '

**Subtotal:** $50.00  ' . '
Discount: $0.00  ' . '
**Invoice Total:** $50.00  ' . '
**Balance Due:** $50.00

WEAT cannot be the leading water quality organization in Texas without you and your input. So please consider renewing and remaining part of the WEAT community. We need your help in continuing to protect and enhance the water quality of Texas!

Best regards,  ' . '
Your WEAT Team';
    if (!$email->htmlBody) {
      // Auto-generate the HTML content
      $email->htmlBody = StringHelper::parseMarkdown($email->body);
    }
    $email->htmlBody = "{% extends '_emails/template' %}\n".
      "{% set body %}\n".
      $email->htmlBody.
      "{% endset %}\n";
    craft()->email->sendEmail($email);
    $data = $user->email . "," . $user->id . "," . $user->userWefId . "," . time() . "\n";
    $this->writeLog($data, 'sent-expiring-email');
  }


  public function actionExpired($param = 1) {
    $user = craft()->users->getUserById($param);
    $email = new EmailModel();
    $email->toEmail = $user->email;
    $email->subject = 'WEAT Membership Expiration Notice';
    $email->body    =
'Dear ' . $user->fullName . ',

We’d like to take this opportunity to thank you for choosing to be a WEAT member over the past year. I hope you were able to take advantage of the member discounts at the many knowledge sharing opportunities through conferences and webinars; learn from information on the latest trends in technology and best in class projects around the state featured in Texas WET; benefit from WEAT advocacy efforts at the Texas Legislature, TCEQ, and EPA; and enjoy professional growth and networking space with your WEAT colleagues and WEAT community at Texas Water!

**We’re writing to let you know that your WEAT membership is now expired as of ' .  date('F j, Y', strtotime($user->userSubscriptionEndDate)) . '**. We
do not want to lose you as a member and hope that you consider remaining part of your WEAT
community. Your $50 WEAT membership provides for discounts to conferences, free WEAT webinars,
section membership, and a $200 savings on Texas Water registration. Go to www.weat.org and login to
renew your WEAT membership today and remain part of the WEAT community. If you are unable to
renew with WEAT, please email or call to let us know what programs would better interest you and fit
your professional needs. We’d like your feedback to improve our services.

WEAT ONLY MEMBERSHIP INVOICE

' . $user->fullName . '  ' . '
' . ($user->userCompanyName ? $user->userCompanyName .'  ' . '' : '') . '
' . ($user->userAddress1 ? $user->userAddress1 . '  ' . '' : '') . '
' . ($user->userAddress2 ? $user->userAddress2 . '  ' . '' : '') . '
' . $user->userAddressCity . ', ' . $user->userAddressState . ' ' . $user->userAddressZip . '

**INVOICE**

Customer ID: ' . $user->userWefId . '  ' . '
Invoice Number: ' . $user->id . '  ' . '
Invoice Date: ' . date("F j, Y") . '  ' . '
Notes: Renewal invoice

Item: MA - WEAT ONLY  ' . '
Unit Price: $50.00  ' . '
Quantity: 1  ' . '
Amount: $50.00  ' . '
Term: ' . date('F j, Y', strtotime($user->userSubscriptionEndDate)) . ' - ' . date('F j, Y', strtotime(date('F j, Y', strtotime($user->userSubscriptionEndDate)) . " + 365 day")) . '

**Subtotal:** $50.00  ' . '
Discount: $0.00  ' . '
**Invoice Total:** $50.00  ' . '
**Balance Due:** $50.00

Best regards,  ' . '
Your WEAT Team';
    if (!$email->htmlBody) {
      // Auto-generate the HTML content
      $email->htmlBody = StringHelper::parseMarkdown($email->body);
    }
    $email->htmlBody = "{% extends '_emails/template' %}\n".
      "{% set body %}\n".
      $email->htmlBody.
      "{% endset %}\n";
    craft()->email->sendEmail($email);
    $data = $user->email . "," . $user->id . "," . $user->userWefId . "," . time() . "\n";
    $this->writeLog($data, 'sent-expired-email');
  }

  function actionSendwelcomeemail($offset, $limit) {
    $criteria = craft()->elements->getCriteria(ElementType::User);
    $criteria->status = null;
    $criteria->limit = $limit;
    $criteria->offset = $offset;
    $users = $criteria->find();
    foreach($users as $user) {
      echo $user->email . "\n";
      $this->actionEmailwelcome($user->id);
    }
  }

}
