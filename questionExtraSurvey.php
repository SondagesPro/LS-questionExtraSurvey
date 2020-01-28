<?php
/**
 * questionExtraSurvey use a question to add survey inside survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017-2020 Denis Chenu <www.sondages.pro>
 * @copyright 2017 OECD (Organisation for Economic Co-operation and Development ) <www.oecd.org>
 * @license AGPL v3
 * @version 2.1.1
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
        'sortorder'=>10, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('If is integer : search the survey id, else search by name of survey (first activated one is choosen)'),
        'caption'=>$this->_translate('Survey to use'),
      ),
      'extraSurveyQuestionLink'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>20, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('The question code in the extra survey to be used. If empty : only token or optionnal fields was used for the link.'),
        'caption'=>$this->_translate('Question for response id'),
      ),
      'extraSurveyQuestionLinkUse'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>25, /* Own category */
        'inputtype'=>'switch',
        'default'=>1,
        'help'=>$this->_translate('Choose if you want only related to current response. If survey use token persistence and allow edition, not needed and id can be different after import an old response database.'),
        'caption'=>$this->_translate('Get only response related to current response id.'),
      ),
      'extraSurveyResponseListAndManage'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>28, /* Own category */
        'inputtype'=>'switch',
        'default'=>1,
        'help'=>$this->_translate('If you have responseListAndManage, the response list can be found using the group of current token.'),
        'caption'=>$this->_translate('Use responseListAndManage group for token.'),
      ),
      'extraSurveyQuestion'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>30, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>$this->_translate('This can be text question type, single choice question type or equation question type.'),
        'caption'=>$this->_translate('Question code for listing.'),
      ),
      'extraSurveyOtherField'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>60, /* Own category */
        'inputtype'=>'textarea',
        'default'=>"",
        'expression'=>1,
        'help'=>$this->_translate('One field by line, field must be a valid question code (single question only). Field and value are separated by colon (<code>:</code>), you can use Expressiona Manager in value.'),
        'caption'=>$this->_translate('Other question fields for relation.'),
      ),
      'extraSurveyQuestionAllowDelete'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>70, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>$this->_translate("Add a button to delete inside modal box, this allow user to really delete the reponse."),
        'caption'=>$this->_translate('Allow delete response.'),
      ),
      'extraSurveyDeleteUnsubmitted'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>75, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>$this->_translate("If a survey is unsubmitted : disallow close of dialog before submitting."),
        'caption'=>$this->_translate('Disallow close without submit.'),
      ),
      'extraSurveyFillAnswer' => array(
        'types'=>'T',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>85, /* Own category */
        'inputtype'=>'singleselect',
        'options' => array(
          'listall' => $this->_translate('List of all answers.'),
          'listsubmitted' => $this->_translate('List of submitted answers.'),
          'number' => $this->_translate('Number of submitted and not submitted answers.'),
        ),
        'default'=>'number',
        'help'=>$this->_translate('Recommended method is number : submlitted answer as set as integer part, and not submitted as decimal part (<code>submitted[.not-submitted]</code>).You can check if all answer are submitted with <code>intval(self)==self</code>.'),
        'caption'=>$this->_translate('Way for filling the answer.'),
      ),
      'extraSurveyShowId'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>90, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>'',
        'caption'=>$this->_translate('Show id at end of string.'),
      ),
      'extraSurveyOrderBy'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>95, /* Own category */
        'inputtype'=>'text',
        'default'=>"",
        'help'=>sprintf($this->_translate('You can use %sSGQA identifier%s or question code iof you have getQuestionInformation plugin for the columns to be ordered. The default order is ASC, you can use DESC. You can use <code>,</code> for multiple order.'),'<a href="https://manual.limesurvey.org/SGQA_identifier" target="_blank">','</a>'),
        'caption'=>$this->_translate('Order by (default “id DESC”, “datestamp ASC” for datestamped surveys)'),
      ),
      'extraSurveyNameInLanguage'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>100, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'i18n'=>true,
        'expression'=>1,
        'help'=>$this->_translate('Default to “response“ (translated)'),
        'caption'=>$this->_translate('Show response as'),
      ),
      'extraSurveyAddNewInLanguage'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>101, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'i18n'=>true,
        'expression'=>1,
        'help'=>$this->_translate('Default to “Add new response”, where response is the previous parameter (translated)'),
        'caption'=>$this->_translate('Add new line text'),
      ),
      'extraSurveyAutoCloseSubmit'=>array(
        'types'=>'XT',
        'category'=>$this->_translate('Extra survey'),
        'sortorder'=>200, /* Own category */
        'inputtype'=>'singleselect',
        'options' => array(
          'replace' => $this->_translate('Replace totally the content and close dialog box, this can disable other plugin system.'),
          'addjs' => $this->_translate('Add information and close dialog box'),
          'add' => $this->_translate('Add information'),
          'js' => $this->_translate('Only close dialog box'),
        ),
        'default'=>'addjs',
        'i18n'=>false,
        'expression'=>0,
        'help'=>$this->_translate('Using replace disable all other plugin event, dialog box are closed using javascript solution.'),
        'caption'=>$this->_translate('Auto close when survey is submitted.'),
      ),
    );
    if(Yii::getPathOfAlias('getQuestionInformation')) {
      $extraAttributes['extraSurveyOrderBy']['help'] = sprintf($this->_translate('You can use %sexpression manager variables%s (question title for example) for the value to be orderd.For the order default is ASC, you can use DESC.'),'<a href="https://manual.limesurvey.org/Expression_Manager_-_presentation#Access_to_variables" target="_blank">','</a>');
    }
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
    /* Fill session if it's in another survey */
    if(Yii::app()->getRequest()->getQuery('extrasurveysrid') && Yii::app()->getRequest()->getParam('extrasurveyqid')) {
      if($this->_validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('extrasurveyqid'),$iSurveyId)) {
        $currentlang = Yii::app()->getLanguage();
        //~ killSurveySession($iSurveyId);
        SetSurveyLanguage($iSurveyId, $currentlang); // frontend_helper function
        $this->qid = Yii::app()->getRequest()->getParam('extrasurveyqid');
        $aSessionExtraSurvey[$iSurveyId]=Yii::app()->getRequest()->getParam('extrasurveyqid');
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      }
    }
    if(Yii::app()->getRequest()->getPost('questionExtraSurveyQid')) {
      if($this->_validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('questionExtraSurveyQid'),$iSurveyId)) {
        $aSessionExtraSurvey[$iSurveyId]=Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      }
    }
    if(!isset($aSessionExtraSurvey[$iSurveyId])) {
      /* Quit if we are not in survey inside survey system */
      return;
    }
    if(version_compare(Yii::app()->getConfig('versionnumber'),"3",">=")) {
      Template::model()->getInstance(null, $iSurveyId)->oOptions->ajaxmode = 'off';
    }
    $this->_resetEMIfNeeded($iSurveyId);

    $currentSrid = isset($_SESSION['survey_'.$iSurveyId]['srid']) ? $_SESSION['survey_'.$iSurveyId]['srid'] : null;
    if((Yii::app()->getRequest()->getParam('move')=='delete')) {
      if(isset($aSessionExtraSurvey[$iSurveyId]) && $currentSrid) {
        $qid = $aSessionExtraSurvey[$iSurveyId];
        /* check if qid with this survey allow delete */
        $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid',array(
          ':attribute' => 'extraSurvey',
          ':qid' => $qid,
        ));
        if(empty($oAttributeExtraSurvey) || ($oAttributeExtraSurvey->value != $iSurveyId && $oAttributeExtraSurvey->value != $title)) {
          return;
        }
        $oAttributeExtraSurveyDelete=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid',array(
          ':attribute' => 'extraSurveyQuestionAllowDelete',
          ':qid' => $qid,
        ));
        if(empty($oAttributeExtraSurveyDelete) || empty($oAttributeExtraSurveyDelete->value)) {
          return;
        }
        $oResponse=Response::model($iSurveyId)->find("id = :srid",array(":srid"=>$currentSrid));
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
    $currentQid = null;
    if(Yii::app()->getRequest()->getPost('questionExtraSurveyQid')) {
      if($this->_validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('questionExtraSurveyQid'),$iSurveyId)) {
        $aSessionExtraSurvey[$iSurveyId]=Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
        $currentQid = Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      }
    }
    if(!isset($aSessionExtraSurvey[$iSurveyId])) {
      /* Quit if we are not in survey inside surey system */
      return;
    }
    $extraSurveyAutoCloseSubmit = 'addjs';
    if ($currentQid) {
      $oQuestionAttribute = QuestionAttribute::model()->find(
        "qid =:qid AND attribute = :attribute",
        array (":qid"=>$currentQid,":attribute"=>'extraSurveyAutoCloseSubmit')
      );
      if($oQuestionAttribute && $oQuestionAttribute->value) {
        $extraSurveyAutoCloseSubmit = $oQuestionAttribute->value;
      }
    }

    unset($aSessionExtraSurvey[$iSurveyId]);
    Yii::app()->session["questionExtraSurvey"] = $aSessionExtraSurvey;
    if(in_array($extraSurveyAutoCloseSubmit,array('replace','addjs','js'))) {
      $script = "if(window.location != window.parent.location) {\n";
      $script.= "  window.parent.$(window.parent.document).trigger('extrasurveyframe:autoclose');\n";
      $script.= "}\n";
      Yii::app()->getClientScript()->registerScript("questionExtraSurveyComplete",$script,CClientScript::POS_END);
    }
    if($currentSrid && Yii::getPathOfAlias('reloadAnyResponse')) {
      \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
    }
    if($currentSrid && $extraSurveyAutoCloseSubmit == 'replace' && Yii::getPathOfAlias('renderMessage')) {
      \renderMessage\messageHelper::renderAlert($this->_translate("Your responses was saved as complete, you can close this windows."));
      return;
    }
    if(in_array($extraSurveyAutoCloseSubmit,array('add','addjs'))) {
      $this->getEvent()->getContent($this)
          ->addContent("<p class='alert alert-success'>".$this->_translate("Your responses was saved as complete, you can close this windows.")."</p>");
    }
  }
  /**
   * Recall good survey
   * And need to reset partially extra survey
   */
  public function beforeLoadResponse() {
    if(!$this->qid){
      return;
    }
    $iSurveyId=$this->getEvent()->get('surveyId');
    if(Yii::app()->getRequest()->getParam('extrasurveysrid')=='new') {
      $this->getEvent()->set('response',false);
      return;
    }
    if(Yii::app()->getRequest()->getParam('extrasurveysrid')) {
      $oResponse=$this->_getResponse($iSurveyId,Yii::app()->getRequest()->getParam('extrasurveysrid'));
      if($oResponse->submitdate) {
        if(Survey::model()->findByPk($iSurveyId)->alloweditaftercompletion != "Y") {
          $oResponse->submitdate=null;
        }
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
      $token = Yii::app()->getRequest()->getParam('token');
      if(empty($token)) {
        $token = !empty(Yii::app()->session['survey_$surveyId']['token']) ? Yii::app()->session['survey_$surveyId']['token'] : null;
      }
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
      $disableMessage = "";
      if(!$extraSurvey) {
        $disableMessage = sprintf($this->_translate("Invalid survey %s for question %s."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if(!$disableMessage && $extraSurvey->active != "Y") {
        $disableMessage = sprintf($this->_translate("Survey %s for question %s not activated."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if(!$disableMessage && !$this->_accessWithToken($thisSurvey) && $this->_accessWithToken($extraSurvey)) {
        $disableMessage = sprintf($this->_translate("Survey %s for question %s can not be used with a survey without tokens."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if(!$disableMessage && $this->_accessWithToken($thisSurvey) && $extraSurvey->anonymized == "Y") {
        $disableMessage = sprintf($this->_translate("Survey %s for question %s need to be not anonymized."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if(!$disableMessage && $this->_accessWithToken($thisSurvey) && $extraSurvey->anonymized == "Y") {
        $disableMessage = sprintf($this->_translate("Survey %s for question %s need to be not anonymized."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if (!$disableMessage && $this->_accessWithToken($extraSurvey) && Yii::app()->getConfig('previewmode') && !Yii::app()->request->getQuery('token')) {
        $disableMessage = sprintf($this->_translate("Survey %s for question %s can not be loaded in preview mode without a valid token."),$extraSurveyAttribute,$oEvent->get('qid'));
      }
      if(!$disableMessage && $this->_accessWithToken($extraSurvey)) {
        if (!$this->_validateToken($extraSurvey,$thisSurvey,$token)) {
          $disableMessage = sprintf($this->_translate("Survey %s for question %s token can not ne found or created."),$extraSurveyAttribute,$oEvent->get('qid'));
        }
      }

      if($disableMessage) {
        $oEvent->set("answers",CHtml::tag("div",array('class'=>'alert alert-warning'),$disableMessage));
        return;
      }
      $this->_setSurveyListForAnswer($extraSurvey->sid,$aQuestionAttributes,$token);
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
        $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid',array(
          ':attribute' => 'extraSurvey',
          ':qid' => $qid,
        ));
        if($oAttributeExtraSurvey && ($oAttributeExtraSurvey->value == $surveyId || $oAttributeExtraSurvey->value == $title)) {
          echo $this->_getHtmlPreviousResponse($surveyId,$srid,$qid,$token,$lang,true);
          break;
        }
      default:
        // Nothing to do (except log error)
    }
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId for answers
   * @param array $qAttributes
   * @param string $token
   * @return void
   */
  private function _setSurveyListForAnswer($surveyId,$aQuestionAttributes,$token=null) {
    $oEvent=$this->getEvent();
    $qid = $oEvent->get('qid');
    $currentSurveyId = $oEvent->get('surveyId');
    $oEvent->set("class",$oEvent->get("class")." questionExtraSurvey");
    if(!$token && $this->_accessWithToken(Survey::model()->findByPk($currentSurveyId))) {
      $token = (!empty(Yii::app()->session["survey_{$currentSurveyId}"]['token'])) ? Yii::app()->session["survey_{$currentSurveyId}"]['token'] : null;
    }
    $srid = !empty(Yii::app()->session["survey_{$currentSurveyId}"]['srid']) ? Yii::app()->session["survey_{$currentSurveyId}"]['srid'] : null;
    $this->_setOtherField($qid,$aQuestionAttributes['extraSurveyOtherField'],$surveyId);
    Yii::setPathOfAlias('questionExtraSurvey',dirname(__FILE__));
    Yii::app()->clientScript->addPackage( 'questionExtraSurveyManage', array(
      'basePath'    => 'questionExtraSurvey.assets',
      'css'         => array('questionExtraSurvey.css'),
      'js'          => array('questionExtraSurvey.js'),
    ));
    Yii::app()->getClientScript()->registerPackage('questionExtraSurveyManage');
    $listOfReponses = $this->_getHtmlPreviousResponse($surveyId,$srid,$oEvent->get('qid'),$token);
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
        'delete' => (bool)$aQuestionAttributes['extraSurveyQuestionAllowDelete'],
        'saveall' => ($oSurveyFrame->allowsave == "Y"),
        'moveprevious' => ($oSurveyFrame->allowprev == "Y" && $oSurveyFrame->format != "A"),
        'movenext' => ($oSurveyFrame->format != "A"),
        'movesubmit' => true,
      ),
      'close' => !(bool)$aQuestionAttributes['extraSurveyDeleteUnsubmitted'],
      'language' => array(
        'Are you sure to delete this response.' => sprintf($this->_translate("Are you sure to delete this %s."),$reponseName),
      ),
      'qid'=>$oEvent->get('qid'),
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
        'Are you sure to delete this response.' => sprintf($this->_translate("Are you sure to delete this %s."),strtolower(gT("Response"))),
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
   * @param int $surveyId the related survey
   * @param int $srid the related identifier
   * @param int $qid the original question id
   * @param null|string $token
   * @param null|string $lang
   * @return string
   */
  private function _getHtmlPreviousResponse($surveyId,$srid,$qid,$token=null,$lang=null, $reloaded=false) {
    if($lang) {
      Yii::app()->setLanguage($lang);
    }
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
    $inputName=null;
    $oQuestion=Question::model()->find("qid=:qid",array(":qid"=>$qid));
    if(in_array($oQuestion->type,array("T","S"))) {
      $inputName = $oQuestion->sid."X".$oQuestion->gid."X".$oQuestion->qid;
    }
    if(Survey::model()->findByPk($surveyId)->active != "Y") {
      return "<p class='alert alert-warning'>".sprintf($this->_translate("Related Survey is not activate, related response deactivated."),$surveyId)."</p>";
    }

    $qCodeText=trim($aAttributes['extraSurveyQuestion']);
    $showId=trim($aAttributes['extraSurveyShowId']);
    $orderBy = isset($aAttributes['extraSurveyOrderBy']) ? trim($aAttributes['extraSurveyOrderBy']) : null;
    $qCodeSrid = $qCodeSridUsed = trim($aAttributes['extraSurveyQuestionLink']);
    $extraSurveyFillAnswer=trim($aAttributes['extraSurveyFillAnswer']);
    $relatedTokens = boolval($aAttributes['extraSurveyResponseListAndManage']);
    $extraSurveyOtherField=$this->_getOtherField($qid);
    if(!$aAttributes['extraSurveyQuestionLinkUse']) {
      $qCodeSridUsed = null;
    }
    $aResponses=$this->_getPreviousResponse($surveyId,$srid,$token,$qid);
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
    if(!empty($extraSurveyOtherField)) {
      foreach($extraSurveyOtherField as $key=>$value) {
        $newUrlParam[$key]=$value;
      }
    }
    /* Need some information on current Srid */
    $currentSusrveyId = $oQuestion->sid;
    $currentStep = isset($_SESSION['survey_'.$currentSusrveyId]) ? $_SESSION['survey_'.$currentSusrveyId]['step'] : null;
    $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? strtolower(gT("Response")) : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
    $reponseAddNew = empty($aAttributes['extraSurveyAddNewInLanguage'][Yii::app()->getLanguage()]) ? sprintf($this->_translate("Add a new %s"),strtolower($reponseName)) : $aAttributes['extraSurveyAddNewInLanguage'][Yii::app()->getLanguage()];

    $renderData=array(
      'aResponses'=>$aResponses,
      'surveyid'=>$surveyId,
      'extrasurveyqid' => $qid,
      'token' => $token,
      'newUrl'=>Yii::app()->getController()->createUrl('survey/index',$newUrlParam),
      'inputName'=>$inputName,
      'fillAnswerWith'=>$extraSurveyFillAnswer,
      'language' => array(
        'createNewreponse'=> $reponseAddNew
      ),
      'questionExtraSurveyReset'=>array(
        'surveyId'=> $currentSusrveyId,
        'step' => $currentStep,
        'reloaded' => $reloaded,
      ),
    );
    return Yii::app()->controller->renderPartial("questionExtraSurvey.views.reponsesList",$renderData,1);
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param string $srid
   * @param null|string $token
   * @param null|integer $qid for attributes
   * @return void
   */
  private function _getPreviousResponse($surveyId,$srid,$token = null,$qid=null)
  {
    $aSelect=array(
      'id',
      'submitdate'
    );
    if($token) {
      $aSelect[] = 'token';
    }
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
    $qCodeText=isset($aAttributes['extraSurveyQuestion']) ? trim($aAttributes['extraSurveyQuestion']) : "";
    $showId=trim($aAttributes['extraSurveyShowId']);
    $orderBy = isset($aAttributes['extraSurveyOrderBy']) ? trim($aAttributes['extraSurveyOrderBy']) : null;
    $qCodeSrid = trim($aAttributes['extraSurveyQuestionLink']);
    $relatedTokens = boolval($aAttributes['extraSurveyResponseListAndManage']);
    $aOtherFields=$this->_getOtherField($qid);
    if(!$aAttributes['extraSurveyQuestionLinkUse']) {
      $qCodeSridUsed = null;
    }
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
    if($qCodeText) {
      $qQuotesCodeText = Yii::app()->db->quoteColumnName($qCodeText);
      $oCriteria->addCondition("$qQuotesCodeText IS NOT NULL AND $qQuotesCodeText != ''");
    }
    if($token) {
      $tokens = array($token=>$token);
      if($relatedTokens) {
        $tokens = $this->_getTokensList($surveyId,$token);
      }
      $oCriteria->addInCondition("token",$tokens);
    }
    if($qCodeSrid && $srid) {
      $oQuestionSrid=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeSrid));
      if($oQuestionSrid && in_array($oQuestionSrid->type,array("T","S","N")) ) {
        $qCodeSrid = "{$oQuestionSrid->sid}X{$oQuestionSrid->gid}X{$oQuestionSrid->qid}";
        $oCriteria->compare(Yii::app()->db->quoteColumnName($qCodeSrid),$srid);
      }
    }
    if(!empty($aOtherFields)) {
      foreach($aOtherFields as $questionCode => $value) {
        $oQuestionOther=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$questionCode));
        if($oQuestionOther && in_array($oQuestionOther->type,array("5","D","G","I","L","N","O","S","T","U","X","Y")) ) {
          $qCode = "{$oQuestionOther->sid}X{$oQuestionOther->gid}X{$oQuestionOther->qid}";
          $oCriteria->compare(Yii::app()->db->quoteColumnName($qCode),$value);
        }
      }
    }
    $sFinalOrderBy = "";
    if(!empty($orderBy)) {
      $aOrdersBy = explode(",",$orderBy);
      $aOrderByFinal = array();
      foreach ($aOrdersBy as $sOrderBy) {
        $aOrderBy = explode(" ",trim($sOrderBy));
        $arrangement = "ASC";
        if(!empty($aOrderBy[1]) and strtoupper($aOrderBy[1]) == 'DESC') {
          $arrangement = "DESC";
        }
        if(!empty($aOrderBy[0])) {
          $orderColumn = null;
          $availableColumns = SurveyDynamic::model($surveyId)->getAttributes();
          if (array_key_exists($aOrderBy[0], $availableColumns)) {
              $aOrderByFinal[] = Yii::app()->db->quoteColumnName($aOrderBy[0])." ".$arrangement;
          } elseif (Yii::getPathOfAlias('getQuestionInformation')) {
            $aEmToColumns = \getQuestionInformation\helpers\surveyCodeHelper::getAllQuestions($surveyId);
            if (in_array($aOrderBy[0], $aEmToColumns)) {
              $aValidColumns = array_keys($aEmToColumns,$aOrderBy[0]);
              $aOrderByFinal[] = Yii::app()->db->quoteColumnName($aValidColumns[0])." ".$arrangement;
            }
          }
        }
      }
      $sFinalOrderBy = implode(",",$aOrderByFinal);
    }
    if(empty($sFinalOrderBy)) {
      $sFinalOrderBy = Yii::app()->db->quoteColumnName('id')." DESC";
      if(Survey::model()->findByPk($surveyId)->datestamp == "Y") {
        $sFinalOrderBy = Yii::app()->db->quoteColumnName('datestamp')." ASC";
      }
    }
    $oCriteria->order = $sFinalOrderBy;

    $oResponses=Response::model($surveyId)->findAll($oCriteria);
    $aResponses=array();
    if($oResponses) {
      foreach($oResponses as $oResponse){
        $aResponses[$oResponse->id]=array(
          'submitdate'=>$oResponse->submitdate,
        );
        /* Todo : check token related survey */
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
   * @return boolean (roken is valid)
   */
  private function _validateToken($extraSurvey,$thisSurvey,$basetoken=null) {
    if(!$this->_accessWithToken($extraSurvey)) {
      return false;
    }
    if(!$basetoken) {
      $basetoken = isset($_SESSION['survey_'.$thisSurvey->sid]['token']) ? $_SESSION['survey_'.$thisSurvey->sid]['token'] : null;
    }
    if(!$basetoken) {
      $this->log(sprintf("Unable to find token value for %s.",$thisSurvey->sid),'warning');
      return false;
    }
    /* Find if token exist in new survey */
    $oToken = Token::model($extraSurvey->sid)->find("token = :token",array(":token"=>$basetoken));
    if(empty($oToken)) {
      $oBaseToken = Token::model($thisSurvey->sid)->find("token = :token",array(":token"=>$basetoken));
      if(empty($oBaseToken)) {
        $this->log(sprintf("Unable to create token for %s, token for %s seems invalid.",$extraSurvey->sid,$thisSurvey->sid),'error');
        return false;
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
      if($oToken->save()) {
        $this->log(sprintf("Auto create token %s for %s.",$basetoken,$extraSurvey->sid),'info');
        return true;
      } else {
        $this->log(sprintf("Unable to create auto create token %s for %s.",$basetoken,$extraSurvey->sid),'error');
        $this->log(CVarDumper::dumpAsString($oToken->getErrors),'info');
        return false;
      }
    }
    return true;
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

  /**
   * Return the list of token related by responseListAndManage
   * @todo : move this to a responseListAndManage helper
   * @param integer $surveyId
   * @param string $token
   * @return string[]
   */
  private function _getTokensList($surveyId,$token)
  {
    $tokensList = array($token=>$token);
    $oPluginResponseListAndManage = Plugin::model()->find("name = :name",array(":name"=>'responseListAndManage'));
    if(empty($oPluginResponseListAndManage) || !$oPluginResponseListAndManage->active) {
      return $tokensList;
    }
    if(!$this->_hasToken($surveyId)) {
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

  /**
   * Set the other field for current qid
   * @param integer $qid
   * @param string $otherField to analyse
   * @param integer $surveyId in this survey
   * @return void
   */
  private function _setOtherField($qid,$otherField,$surveyId)
  {
    $aOtherFieldsLines = preg_split('/\r\n|\r|\n/',$otherField, -1, PREG_SPLIT_NO_EMPTY);
    $aOtherFields = array();
    foreach($aOtherFieldsLines as $otherFieldLine) {
      if(!strpos($otherFieldLine,":")) {
        continue; // Invalid line
      }
      $key = substr($otherFieldLine,0,strpos($otherFieldLine,":"));
      $value = substr($otherFieldLine,strpos($otherFieldLine,":")+1);
      $value = self::_EMProcessString($value);
      $aOtherFields[$key] = $value;
    }
    Yii::app()->getSession()->add("questionExtraOtherField{$qid}",$aOtherFields);
  }

  /**
   * Set the other field for current qid
   * @param integer $qid
   * @return null|srting[]
   */
  private function _getOtherField($qid)
  {
    return Yii::app()->getSession()->get("questionExtraOtherField{$qid}",null);
  }

  /**
   * Reset current survey if EM sid updated
   * Needed for full index
   */
  private function _resetEMIfNeeded($surveyId){
    $questionExtraSurveyReset = Yii::app()->getRequest()->getPost('questionExtraSurveyReset');
    if(empty($questionExtraSurveyReset)) {
      return;
    }
    if(empty($questionExtraSurveyReset['reloaded'])) {
      // Can log it
      return;
    }
    if($questionExtraSurveyReset['surveyId'] != $surveyId) {
      // throw Exceptioon ?
      return;
    }
    if(!class_exists('\\reloadAnyResponse\\helpers\\reloadResponse')) {
      if(Yii::app()->getConfig('debug') > 1) {
        Throw new Exception("You must have reloadAnyResponse version 1.2.0 minimum");
      }
      return;
    }
    $step = $questionExtraSurveyReset['step'];
    $srid = $_SESSION['survey_'.$surveyId]['srid'];
    if(empty($srid) || empty($step)) {
      return;
    }
    $reloadReponse = new \reloadAnyResponse\helpers\reloadResponse($surveyId, $srid);
    $reloadReponse->startSurvey($step);
  }

  /**
   * Validate a question have extra survey attribute to extra sis
   * @param integer $qid
   * @param integer $extraSid
   * @return boolean
   */
  private function _validateQuestionExtraSurvey($qid,$extraSid) {
    $title=Survey::model()->findByPk($extraSid)->getLocalizedTitle(); // @todo : get default lang title
    /* search if it's a related survey */
    $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid',array(
      ':attribute' => 'extraSurvey',
      ':qid' => $qid,
    ));
    /* Validate if usage of extraSurvey is OK here with current qid */
    if($oAttributeExtraSurvey && ($oAttributeExtraSurvey->value == $extraSid || $oAttributeExtraSurvey->value == $title)) {
      return true;
    }
    return false;
  }
  /*******************************************************
   * Common for a lot of plugin, helper for compatibility
   *******************************************************/

  /**
   * Process a string via expression manager (static way) anhd API independant
   * @param string $string
   * @param boolean $static
   * @return string
   */
  private static function _EMProcessString($string,$static = true)
  {
    $replacementFields=array();
    if(intval(Yii::app()->getConfig('versionnumber'))<3) {
      return \LimeExpressionManager::ProcessString($string, null, $replacementFields, false, 3, 0, false, false, $static);
    }
    if(version_compare(Yii::app()->getConfig('versionnumber'),"3.6.2","<")) {
      return \LimeExpressionManager::ProcessString($string, null, $replacementFields, 3, 0, false, false, $static);
    }
    return \LimeExpressionManager::ProcessStepString($string, $replacementFields, 3, $static);
  }

  /**
   * get translation
   * @param string $string to translate
   * @param string escape mode
   * @param string language, current by default
   * @return string
   */
    private function _translate($string, $sEscapeMode = 'unescaped', $sLanguage = null)
    {
      
        if(is_callable(array($this, 'gT'))) {
            return $this->gT($string,$sEscapeMode,$sLanguage);
        }
        return $string;
  }

  /**
   * Add this translation just after loaded all plugins
   * @see event afterPluginLoad
   */
  public function afterPluginLoad(){

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
