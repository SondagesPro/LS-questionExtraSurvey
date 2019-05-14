<?php
/**
 * questionExtraSurvey use a question to add survey inside survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017-2019 Denis Chenu <www.sondages.pro>
 * @copyright 2017 OECD (Organisation for Economic Co-operation and Development ) <www.oecd.org>
 * @license AGPL v3
 * @version 1.1.4
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class questionExtraSurvey extends PluginBase
{
  static protected $name = 'questionExtraSurvey';
  static protected $description = 'Add survey inside survey : need a survey not anonymous and with token table for the 2 surveys.';

  protected $storage = 'DbStorage';

  /**
   * actual qid for this survey
   */
  private $qid;

  /**
  * Add function to be used in beforeQuestionRender event and to attriubute
  */
  public function init()
  {
    Yii::setPathOfAlias('questionExtraSurvey',dirname(__FILE__));

    $this->subscribe('beforeQuestionRender');
    $this->subscribe('newQuestionAttributes','addExtraSurveyAttribute');

    $this->subscribe('beforeSurveyPage');
    $this->subscribe('beforeLoadResponse');

    $this->subscribe('newDirectRequest');

    $this->subscribe('afterSurveyComplete');
  }

  /**
   * The attribute, try to set to readonly for no XSS , but surely broken ....
   */
  public function addExtraSurveyAttribute()
  {
    $extraAttributes = array(
      'extraSurvey'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>20, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('If is integer : search the survey id, else search by name of survey (first activated one is choosen)'),
        'caption'=>$this->_translate('Survey to use'),
      ),
      'extraSurveyQuestionLink'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>30, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('The question code in the extra survey to be used.'),
        'caption'=>$this->_translate('Question for response id'),
      ),
      'extraSurveyQuestion'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>40, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('This can be text question type, single choice question type or equation question type.'),
        'caption'=>$this->_translate('Question code for listing.'),
      ),
      'extraSurveyShowId'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>50, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>$this->_translate(''),
        'caption'=>$this->_translate('Show id at end of string.'),
      ),
      'extraSurveyNameInLanguage'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>50, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'i18n'=>true,
        'help'=>$this->_translate('Default to response (translated)'),
        'caption'=>$this->_translate('Show response as'),
      ),
      'extraSurveyQuestionAllowDelete'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>40, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>$this->_translate("Add a button to delete inside modal box, this don't update default LimeSurvey behaviour."),
        'caption'=>$this->_translate('Allow delete response.'),
      ),
      'extraSurveySetSurveySubmittedOnly'=>array(
        'types'=>'T',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>80, /* Own category */
        'inputtype'=>'switch',
        'default'=>1,
        'help'=>$this->_translate(''),
        'caption'=>$this->_translate('Fill answer with question id only if submitted.'),
      ),
    );
    if(method_exists($this->getEvent(),'append')) {
      $this->getEvent()->append('questionAttributes', $extraAttributes);
    } else {
      $questionAttributes=(array)$this->event->get('questionAttributes');
      $questionAttributes=array_merge($questionAttributes,$extraAttributes);
      $this->event->set('questionAttributes',$questionAttributes);
    }

  }

  /**
   * Access control on survey
   */
  public function beforeSurveyPage()
  {
    $iSurveyId=$this->event->get('surveyId');
    $oSurvey = Survey::model()->findByPk($iSurveyId);
    if(!$oSurvey) {
      return;
    }
    $aSessionExtraSurvey=Yii::app()->session["questionExtraSurvey"];
    if(empty($aSessionExtraSurvey)) {
        $aSessionExtraSurvey=array();
    }
    $currentSrid = isset($_SESSION['survey_'.$iSurveyId]['srid']) ? $_SESSION['survey_'.$iSurveyId]['srid'] : null;

    if(Yii::app()->getRequest()->getQuery('extrasurveysrid') && Yii::app()->getRequest()->getParam('extrasurveyqid')) {
      $title=$oSurvey->getLocalizedTitle(); // @todo : get default lang title
      /* search if it's a related survey */
      $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND (value=:sid OR value=:title)  AND qid=:qid',array(
        ':attribute' => 'extraSurvey',
        ':sid' => $iSurveyId,
        ':title' => $title,
        ':qid' => Yii::app()->getRequest()->getParam('extrasurveyqid'),
      ));
      
      /* Validate if usage of extraSurvey is OK here with current qid */
      if($oAttributeExtraSurvey) {
        $aSessionExtraSurvey[$iSurveyId]=$oAttributeExtraSurvey->qid;
        $currentlang = Yii::app()->getLanguage();
        //~ killSurveySession($iSurveyId);
        SetSurveyLanguage($iSurveyId, $currentlang); // frontend_helper function
        $this->qid = Yii::app()->getRequest()->getParam('extrasurveyqid');
        $aSessionExtraSurvey[$iSurveyId]=$oAttributeExtraSurvey->qid;
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      }
    }
    if(!isset($aSessionExtraSurvey[$iSurveyId])) {
      /* Quit if we are not in survey inside surey system */
      return;
    }
    if(version_compare(Yii::app()->getConfig('versionnumber'),"3",">=")) {
      Template::model()->getInstance(null, $iSurveyId)->oOptions->ajaxmode = 'off';
    }
    if((Yii::app()->getRequest()->getParam('move')=='clearall' || Yii::app()->getRequest()->getParam('clearall'))) {
      if(isset($aSessionExtraSurvey[$iSurveyId]) && $currentSrid) {
        $oResponse=Response::model($iSurveyId)->find("id = :srid",array(":srid"=>$_SESSION['survey_'.$iSurveyId]['srid']));
        if($oResponse) {
          $oResponse->delete();
        }
        if(Yii::getPathOfAlias('reloadAnyResponse')) {
          \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
        }
        $renderMessage = new \renderMessage\messageHelper();
        $this->_registerExtraSurveyScript();
        App()->getClientScript()->registerScript("questionExtraSurveyComplete","autoclose();\n",CClientScript::POS_END);
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($aSessionExtraSurvey[$iSurveyId]);
        unset($aSessionExtraSurvey[$iSurveyId]);
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
        $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? strtolower(gT("Response")) : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
        $renderMessage->render(sprintf($this->_translate("%s deleted, you can close this window."),$reponseName));
      }
    }
    if((Yii::app()->getRequest()->getParam('move')=='saveall' || Yii::app()->getRequest()->getParam('saveall'))) {
      if(isset($aSessionExtraSurvey[$iSurveyId]) && $currentSrid ) {
        $oSurvey = Survey::model()->findByPk($iSurveyId);
        //~ App()->getClientScript()->registerScript("questionExtraSurveySaved","autoclose();\n",CClientScript::POS_END);
        if($oSurvey->active == "Y") {
            $script = "if(window.location != window.parent.location) {\n";
            $script = "    window.parent.$(window.parent.document).trigger('extrasurveyframe:autoclose');\n";
            $script = "}\n";
            $step = isset($_SESSION['survey_'.$iSurveyId]['step']) ? $_SESSION['survey_'.$iSurveyId]['step'] : 0;
            LimeExpressionManager::JumpTo($step, false);
            $oResponse = SurveyDynamic::model($iSurveyId)->findByPk($currentSrid);
            $oResponse->lastpage = $step; // Or restart at 1st page ?
            // Save must force always to not submitted (draft)
            $oResponse->submitdate = null;
            $oResponse->save();
        }
        $this->_registerExtraSurveyScript();
        App()->getClientScript()->registerScript("questionExtraSurveyComplete","autoclose();\n",CClientScript::POS_END);
        killSurveySession($iSurveyId);
        unset($aSessionExtraSurvey[$iSurveyId]);
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
        if(Yii::getPathOfAlias('reloadAnyResponse')) {
          \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
        }
        if(Yii::getPathOfAlias('renderMessage')) {
            \renderMessage\messageHelper::renderAlert($this->_translate("Your responses was saved with success, you can close this windows."));
        }
      }
    }
    $this->_registerExtraSurveyScript();

  }

  /**
   *Add script after survey complete
   */
  public function afterSurveyComplete() {
    $iSurveyId = $this->event->get('surveyId');
    $currentSrid = $this->event->get('responseId');
    $aSessionExtraSurvey=Yii::app()->session["questionExtraSurvey"];
    if(isset($aSessionExtraSurvey[$iSurveyId])) {
      unset($aSessionExtraSurvey[$iSurveyId]);
      Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      $script = "if(window.location != window.parent.location) {\n";
      $script.= "  window.parent.$(window.parent.document).trigger('extrasurveyframe:autoclose');\n";
      $script.= "}\n";
      Yii::app()->getClientScript()->registerScript("questionExtraSurveyComplete",$script,CClientScript::POS_END);
      if($currentSrid && Yii::getPathOfAlias('reloadAnyResponse')) {
        \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
      }
      if($currentSrid && Yii::getPathOfAlias('renderMessage')) {
          \renderMessage\messageHelper::renderAlert($this->_translate("Your responses was saved as complete, you can close this windows."));
      }
    }
  }
  /**
   * Recall good survey
   */
  public function beforeLoadResponse() {
    if(!$this->qid){
      return;
    }
    $iSurveyId=$this->getEvent()->get('surveyId');
    if(Yii::app()->getRequest()->getParam('extrasurveysrid')=='new') {
      //~ $this->getEvent()->set('response',false);
      return;
    }
    if(Yii::app()->getRequest()->getParam('extrasurveysrid')) {
      $oResponse=$this->_getResponse($this->getEvent()->get('surveyId'),Yii::app()->getRequest()->getParam('extrasurveysrid'));
      if($oResponse->submitdate) {
        $oResponse->submitdate=null;
        $oResponse->lastpage=0;
        $oResponse->save();
      }
      $this->getEvent()->set('response',$oResponse);
      return;
    }
  }

  /**
   * Add the script when question is rendered
   * Add QID and SGQ replacement forced (because it's before this was added by core
   */
  public function beforeQuestionRender()
  {
    $oEvent=$this->getEvent();
    $aQuestionAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'),Yii::app()->getLanguage());
    $surveyId=$oEvent->get('surveyId');
    if(isset($aQuestionAttributes['extraSurvey']) && trim($aQuestionAttributes['extraSurvey'])) {
      $thisSurvey=Survey::model()->findByPk($surveyId);
      $extraSurveyAttribute=trim($aQuestionAttributes['extraSurvey']);
      if(!ctype_digit($extraSurveyAttribute)) {
          $oLangSurvey=SurveyLanguageSetting::model()->find(array(
              'select'=>'surveyls_survey_id',
              'condition'=>'surveyls_title = :title AND surveyls_language =:language ',
              'params'=>array(
                  ':title' => $extraSurveyAttribute,
                  ':language' => Yii::app()->getLanguage(),
              ),
          ));
          if(!$oLangSurvey) {
              return;
          }
          $extraSurveyAttribute=$oLangSurvey->surveyls_survey_id;
      }
      $extraSurvey=Survey::model()->findByPk($extraSurveyAttribute);
      if(!$extraSurvey) {
        $this->log(sprintf("Invalid survey %s for question %s",$extraSurveyAttribute,$oEvent->get('qid')),'warning');
        return;
      }
      if($extraSurvey->active != "Y") {
        $this->log(sprintf("Survey %s for question %s not activated",$extraSurveyAttribute,$oEvent->get('qid')),'warning');
        return;
      }
      if($this->_accessWithToken($thisSurvey) != $this->_accessWithToken($extraSurvey)) {
        $this->log(sprintf("Survey %s and survey %s for question %s incompatible by token system",$surveyId,$extraSurveyAttribute,$oEvent->get('qid')),'warning');
        return;
      }
      if($this->_accessWithToken($extraSurvey)) {
        $this->_validateToken($extraSurvey,$thisSurvey,Yii::app()->getRequest()->getParam('token'));
      }
      $this->_setSurveyListForAnswer($extraSurvey->sid,$aQuestionAttributes,Yii::app()->getRequest()->getParam('token'));
    }
  }

  public function newDirectRequest()
  {
    $oEvent = $this->event;
    if ($oEvent->get('target') != get_class()) {
      return;
    }
    $surveyId=$this->api->getRequest()->getParam('surveyid');
    $token=$this->api->getRequest()->getParam('token');
    $srid=$this->api->getRequest()->getParam('extrasurveysrid');
    $qid=$this->api->getRequest()->getParam('qid');
    $lang=$this->api->getRequest()->getParam('lang');
    if(!$surveyId || !$srid || !$qid) {
      return;
    }
    $sAction=$oEvent->get('function');
    switch($sAction) {
      case 'update':
        $title=Survey::model()->findByPk($surveyId)->getLocalizedTitle();
        /* search if it's a related survey */
        $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND (value=:sid OR value=:title)  AND qid=:qid',array(
          ':attribute' => 'extraSurvey',
          ':sid' => $surveyId,
          ':title' => $title,
          ':qid' => Yii::app()->getRequest()->getParam('qid'),
        ));
        if($oAttributeExtraSurvey) {
          echo $this->_getHtmlPreviousResponse($surveyId,$srid,$token,$qid,$lang);
          break;
        }
      case 'validate':
        break;
      default:
        // Nothing to do (except log error)
    }
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param array $qAttributes
   * @param string $token
   * @return void
   */
  private function _setSurveyListForAnswer($surveyId,$aQuestionAttributes,$token=null) {
    $oEvent=$this->getEvent();
    $oEvent->set("class",$oEvent->get("class")." questionExtraSurvey");
    if(!$token) {
      $token=isset($_SESSION['survey_'.$oEvent->get('surveyId')]['token']) ? $_SESSION['survey_'.$oEvent->get('surveyId')]['token'] : null;
    }
    $srid=isset($_SESSION['survey_'.$oEvent->get('surveyId')]['srid']) ? $_SESSION['survey_'.$oEvent->get('surveyId')]['srid'] : null;
    Yii::setPathOfAlias('questionExtraSurvey',dirname(__FILE__));
    Yii::app()->clientScript->addPackage( 'questionExtraSurveyManage', array(
        'basePath'    => 'questionExtraSurvey.assets',
        'css'         => array('questionExtraSurvey.css'),
        'js'          => array('questionExtraSurvey.js'),
    ));
    Yii::app()->getClientScript()->registerPackage('questionExtraSurveyManage');
    $listOfReponses = $this->_getHtmlPreviousResponse($surveyId,$srid,$token,$oEvent->get('qid'));
    $ajaxUrl = Yii::app()->getController()->createUrl('plugins/direct', array('plugin' => 'questionExtraSurvey', 'function' => 'update',
      'surveyid'=>$surveyId,
      'token'=>$token,
      'extrasurveysrid'=>$srid,
      'qid'=>$oEvent->get('qid'),
      'lang'=>Yii::app()->getLanguage(),
    ));
    $oSurveyFrame = Survey::model()->findByPk($surveyId);
    $reponseName = empty($aQuestionAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? strtolower(gT("Response")) : $aQuestionAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
    $modalParams = array(
      'buttons' => array(
        'clearall' => (bool)$aQuestionAttributes['extraSurveyQuestionAllowDelete'],
        'saveall' => ($oSurveyFrame->allowsave == "Y"),
        'moveprevious' => ($oSurveyFrame->allowprev == "Y" && $oSurveyFrame->format != "A"),
        'movenext' => ($oSurveyFrame->format != "A"),
        'movesubmit' => true,
      ),
      'language' => array(
        'Are you sure to remove this response.' => sprintf($this->_translate("Are you sure to remove this %s."),$reponseName),
      ),
    );

    $listOfReponses="<div data-update-questionextrasurvey='$ajaxUrl' data-modalparams-questionextrasurvey='".ls_json_encode($modalParams)."'>{$listOfReponses}</div>";
    $oEvent->set("answers",$listOfReponses);
    $this->_addModal($oEvent->get('qid'),$aQuestionAttributes);
  }

  /**
   * Add modal script at end of page with javascript
   * @return void
   */
  private function _addModal($qid,$aQuestionAttributes)
  {
    $renderData=array(
      'qid' => $qid,
      'language' => array(
        'Are you sure to remove this response.' => sprintf($this->_translate("Are you sure to remove this %s."),strtolower(gT("Response"))),
        'Yes'=> gT("Yes"),
        'No'=> gT("No"),
        'Close'=>gT("Close"),
        'Delete'=> gT("Delete"),
        'Save'=>gT("Save"),
        'Previous'=>gT("Previous"),
        'Next'=>gT("Next"),
        'Submit'=>gT("Submit"),
      ),
    );
    $posReady = CClientScript::POS_READY;
    if(!Yii::app()->getClientScript()->isScriptRegistered("questionExtraSurveyModalConfirm",$posReady)) {
      $modalConfirm=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalConfirm',$renderData,1);
      Yii::app()->getClientScript()->registerScript("questionExtraSurveyModalConfirm","$('body').prepend(".json_encode($modalConfirm).");",$posReady);
    }
    if(!Yii::app()->getClientScript()->isScriptRegistered("questionExtraSurveyModalSurvey",$posReady)) {
      $modalSurvey=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalSurvey',$renderData,1);
      Yii::app()->getClientScript()->registerScript("questionExtraSurveyModalSurvey","$('body').prepend(".json_encode($modalSurvey).");",$posReady);
    }
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param int $srid
   * @param string $token
   * @param int $qid question id
   * @return void
   */
  private function _getHtmlPreviousResponse($surveyId,$srid,$token,$qid,$lang=null) {
    if($lang) {
      Yii::app()->setLanguage($lang);
    }
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
    $inputName=null;
    $oQuestion=Question::model()->find("qid=:qid",array(":qid"=>$qid));
    if(in_array($oQuestion->type,array("T","S"))) {
      $inputName = $oQuestion->sid."X".$oQuestion->gid."X".$oQuestion->qid;
    }
    $qCodeText=trim($aAttributes['extraSurveyQuestion']);
    $showId=trim($aAttributes['extraSurveyShowId']);
    $qCodeSrid=trim($aAttributes['extraSurveyQuestionLink']);
    $setSubmittedSrid=trim($aAttributes['extraSurveySetSurveySubmittedOnly']);
    $aResponses=$this->_getPreviousResponse($surveyId,$srid,$token,$qCodeText,$showId,$qCodeSrid);
    $newUrlParam=array(
      'sid' =>$surveyId,
      'extrasurveyqid' => $qid,
      'newtest' =>'Y',
      'token' => $token,
      'extrasurveysrid' => 'new',
    );
    if(!empty($qCodeSrid)) {
      $newUrlParam[$qCodeSrid]=$srid;
    }
    $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? strtolower(gT("Response")) : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
    $renderData=array(
      'aResponses'=>$aResponses,
      'surveyid'=>$surveyId,
      'extrasurveyqid' => $qid,
      'token' => $token,
      'newUrl'=>Yii::app()->getController()->createUrl('survey/index',$newUrlParam),
      'inputName'=>$inputName,
      'setSubmittedSrid'=>$setSubmittedSrid,
      'language' => array(
        'createNewreponse'=>sprintf($this->_translate("Add a new %s"),$reponseName),
      ),
    );
    return Yii::app()->controller->renderPartial("questionExtraSurvey.views.reponsesList",$renderData,1);
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param string $srid
   * @param string $token
   * @param string $qCodeText question code to be used for link
   * @param boolean $bShowId or not
   * @param boolean $qCodeSrid
   * @return void
   */
  private function _getPreviousResponse($surveyId,$srid,$token,$qCodeText,$showId=false,$qCodeSrid=null,$qCodeEmpty=false) {
    $aSelect=array(
      'id',
      'token',
      'submitdate'
    );

    /* Find the question code */
    $oQuestionText=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeText));
    $qCodeText = null;
    if($oQuestionText && in_array($oQuestionText->type,array("S","T","U","L","!","O","N","D","G","Y","*")) ) {
      $qCodeText = "{$oQuestionText->sid}X{$oQuestionText->gid}X{$oQuestionText->qid}";
      $aSelect[] = Yii::app()->db->quoteColumnName($qCodeText);
    }

    $oCriteria = new CDbCriteria;
    $oCriteria->select = $aSelect;
    $oCriteria->condition = "";
    if(!$qCodeEmpty && $qCodeText) {
      $qQuotesCodeText = Yii::app()->db->quoteColumnName($qCodeText);
      $oCriteria->addCondition("$qQuotesCodeText IS NOT NULL AND $qQuotesCodeText != ''");
    }
    if($token) {
      $oCriteria->addInCondition("token",$this->_getTokensList($surveyId,$token));
    }
    if($qCodeSrid && $srid) {
      $oQuestionSrid=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeSrid));
      if($oQuestionSrid && in_array($oQuestionSrid->type,array("T","S","N")) ) {
        $qCodeSrid = "{$oQuestionSrid->sid}X{$oQuestionSrid->gid}X{$oQuestionSrid->qid}";
        $oCriteria->compare(Yii::app()->db->quoteColumnName($qCodeSrid),$srid);
      }
    }
    if(Survey::model()->findByPk($surveyId)->datestamp == "Y") {
      $oCriteria->order = 'datestamp asc';
    }

    $oResponses=Response::model($surveyId)->findAll($oCriteria);
    $aResponses=array();
    if($oResponses) {
      foreach($oResponses as $oResponse){
        $aResponses[$oResponse->id]=array(
          'token'=>$oResponse->token,
          'submitdate'=>$oResponse->submitdate,
        );
        $aResponses[$oResponse->id]['text'] = "";
        if($showId) {
          $aResponses[$oResponse->id]['text'] .= \CHtml::tag('span',array('class'=>'badge'),$oResponse->id);
        }
        if($qCodeText) {
          switch ($oQuestionText->type) {
            case "!":
            case "L":
              $oAnswer=Answer::model()->find("qid=:qid and language=:language and code=:code",array(
                ':qid' => $oQuestionText->qid,
                ':language' => Yii::app()->getLanguage(),
                ':code'=>$oResponse->getAttribute($qCodeText),
              ));
              if($oAnswer) {
                $aResponses[$oResponse->id]['text'] .= $oAnswer->answer;
              } else {
                $aResponses[$oResponse->id]['text'] .= $oResponse->$qCodeText; // Review for other
              }
              break;
            default:
              $aResponses[$oResponse->id]['text'] .= $oResponse->$qCodeText;
          }
        }
        if(empty($aResponses[$oResponse->id]['text'])) {
          $aResponses[$oResponse->id]['text'] .= $oResponse->id;
        }
      }
    }
    return $aResponses;
  }

  /**
   * Management of extra survey (before shown)
   */
  private function _registerExtraSurveyScript()
  {
    Yii::app()->clientScript->addPackage( 'manageExtraSurvey', array(
        'basePath'    => 'questionExtraSurvey.assets',
        'js'          => array('extraSurvey.js'),
    ));
    Yii::app()->getClientScript()->registerPackage('manageExtraSurvey');
  }
  /**
   * Get Response, control access
   * @param integer survey id
   * @param integer response id
   * @return void|Response
  */
  private function _getResponse($surveyid,$srid) {
    $oResponse  = Response::model($surveyid)->findByPk($srid);
    if(!$oResponse) {
      throw new CHttpException(404,$this->_translate("Invalid id"));
    }
    if(empty($oResponse->token)) {
      return $oResponse;
    }
    /* Must control token validity */
    $token=Yii::app()->getRequest()->getParam('token');
    $aTokens = $this->_getTokensList($surveyid,$token);
    if($oResponse->token == $token) {
      return $oResponse;
    }
    if(in_array($oResponse->token,$aTokens)) {
      $oResponse->token=$token;
      $oResponse->save();
      return $oResponse;
    }
    $reponseName = strtolower(gT("Response"));
    $aSessionExtraSurvey = Yii::app()->session["questionExtraSurvey"];
    if(isset($aSessionExtraSurvey[$surveyid])) {
      $aAttributes=QuestionAttribute::model()->getQuestionAttributes($aSessionExtraSurvey[$surveyid]);
      $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? strtolower(gT("Response")) : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
    }
    throw new CHttpException(403,sprintf($this->_translate("Invalid token to edit this %s."),$responseName));
  }

  /**
   * Validate if same token exist, create if not.
   * @param Survey $extraSurvey
   * @param Survey $thisSurvey
   */
  private function _validateToken($extraSurvey,$thisSurvey,$basetoken=null) {
    if(!$this->_accessWithToken($extraSurvey)) {
      return;
    }
    if(!$basetoken) {
      $basetoken = isset($_SESSION['survey_'.$thisSurvey->sid]['token']) ? $_SESSION['survey_'.$thisSurvey->sid]['token'] : null;
    }
    if(!$basetoken) {
      $this->log(sprintf("Unable to find token value for %s.",$thisSurvey->sid),'warning');
      return;
    }
    /* Find if token exist in new survey */
    $oToken = Token::model($extraSurvey->sid)->find("token = :token",array(":token"=>$basetoken));
    if(empty($oToken)) {
      $oBaseToken = Token::model($thisSurvey->sid)->find("token = :token",array(":token"=>$basetoken));
      if(empty($oBaseToken)) {
        $this->log(sprintf("Unable to create token for %s, token for %s seems invalid.",$extraSurvey->sid,$thisSurvey->sid),'error');
        return;
      }
      $oToken = Token::create($extraSurvey->sid);
      $disableAttribute = array("tid","participant_id","emailstatus","blacklisted","sent","remindersent","remindercount","completed","usesleft");
      $updatableAttribute = array_filter($oBaseToken->getAttributes(),
        function ($key) use ($disableAttribute) {
            return !in_array($key, $disableAttribute);
        },
        ARRAY_FILTER_USE_KEY
      );
      $oToken->setAttributes($updatableAttribute,false);
      $oToken->save();
      $this->log(sprintf("Auto create token %s for %s.",$basetoken,$extraSurvey->sid),'info');

    }
  }

/**
 * Test is survey have token table
 * @param $iSurvey
 * @return boolean
 */
  private function _hasToken($iSurvey) {
    if(version_compare(Yii::app()->getConfig('versionnumber'),"3",">=")) {
        return Survey::model()->findByPk($iSurvey)->getHasTokensTable();
    }
    return Survey::model()->hasTokens($iSurvey);
  }

  private function _getTokensList($surveyId,$token)
  {
    $tokensList = array($token=>$token);
    $oPluginResponseListAndManage = Plugin::model()->find("name = :name",array(":name"=>'responseListAndManage'));
    if(empty($oPluginResponseListAndManage) || !$oPluginResponseListAndManage->active) {
      return $tokensList;
    }
    $oPluginResponseListAndManage = PluginSetting::model()->find(
      "plugin_id = :plugin_id AND model = :model AND model_id = :model_id AND ".Yii::app()->db->quoteColumnName('key')." = :setting",
      array(":plugin_id"=>$oPluginResponseListAndManage->id,':model'=>"Survey",':model_id'=>$surveyId,':setting'=>"tokenAttributeGroup")
    );
    if(empty($oPluginResponseListAndManage)) {
      return $tokensList;
    }
    $tokenAttributeGroup = trim(json_decode($oPluginResponseListAndManage->value));
    if(empty($tokenAttributeGroup)) {
      return $tokensList;
    }
    if(!is_string($tokenAttributeGroup)) {
      return $tokensList;
    }
    $oTokenGroup = Token::model($surveyId)->find("token = :token",array(":token"=>$token));
    $tokenGroup = (isset($oTokenGroup->$tokenAttributeGroup) && trim($oTokenGroup->$tokenAttributeGroup)!='') ? $oTokenGroup->$tokenAttributeGroup : null;
    if(empty($tokenGroup)) {
      return $tokensList;
    }
    $oTokenGroup = Token::model($surveyId)->findAll($tokenAttributeGroup."= :group",array(":group"=>$tokenGroup));
    return CHtml::listData($oTokenGroup,'token','token');
  }

  /**
  * Did this survey have token with reload available
  * @var \Survey
  * @return boolean
  */
  private function _accessWithToken($oSurvey)
  {
    Yii::import('application.helpers.common_helper', true);
    return $oSurvey->anonymized != "Y" && tableExists("{{tokens_".$oSurvey->sid."}}");
  }

    /** Common for a lot of plugin, helper for compatibility */

    /**
     * get translation
     * @param string
     * @return string
     */
    private function _translate($string){
        return Yii::t('',$string,array(),'Messages'.get_class($this));
    }

    /**
     * Add this translation just after loaded all plugins
     * @see event afterPluginLoad
     */
    public function afterPluginLoad(){
        // messageSource for this plugin:
        $messageSource=array(
            'class' => 'CGettextMessageSource',
            'cacheID' => get_class($this).'Lang',
            'cachingDuration'=>3600,
            'forceTranslation' => true,
            'useMoFile' => true,
            'basePath' => __DIR__ . DIRECTORY_SEPARATOR.'locale',
            'catalog'=>'messages',// default from Yii
        );
        Yii::app()->setComponent('Messages'.get_class($this),$messageSource);
    }

    /**
    * @inheritdoc adding string, by default current event
    * @param string $message
    * @param string $level From CLogger, defaults to CLogger::LEVEL_TRACE
    * @param string $logDetail
    */
    public function log($message, $level = \CLogger::LEVEL_TRACE,$logDetail = null)
    {
        if(!$logDetail && $this->getEvent()) {
            $logDetail = $this->getEvent()->getEventName();
        } // What to put if no event ?
        if($logDetail) {
            $logDetail = ".".$logDetail;
        }
        $category = get_class($this);
        \Yii::log($message, $level, 'plugin.'.$category.$logDetail);
    }

}
