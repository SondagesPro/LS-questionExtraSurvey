<?php
/**
 * questionExtraSurvey use a question to add survey inside survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017 Denis Chenu <www.sondages.pro>
 * @copyright 2017 OECD (Organisation for Economic Co-operation and Development ) <www.oecd.org>
 * @license AGPL v3
 * @version 0.1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class questionExtraSurvey extends \ls\pluginmanager\PluginBase
{

  static protected $name = 'questionExtraSurvey';
  static protected $description = 'Add survey inside survey.';

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
        'category'=>gT('Extra survey'),
        'sortorder'=>20, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>gT('If is integer : search the survey id, else search by name of survey (first activated one is choosen)'),
        'caption'=>gT('Survey to use'),
      ),
      'extraSurveyQuestionLink'=>array(
        'types'=>'XT',
        'category'=>gT('Extra survey'),
        'sortorder'=>30, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>gT('The question code in the extra survey to be used.'),
        'caption'=>gT('Question for response id'),
      ),
      'extraSurveyQuestion'=>array(
        'types'=>'XT',
        'category'=>gT('Extra survey'),
        'sortorder'=>40, /* Own category */
        'inputtype'=>'text',
        'default'=>'',
        'help'=>gT('This can be text question type, numeric question type or single choice question type.'),
        'caption'=>gT('Question code for listing.'),
      ),
      'extraSurveyShowId'=>array(
        'types'=>'XT',
        'category'=>gT('Extra survey'),
        'sortorder'=>50, /* Own category */
        'inputtype'=>'switch',
        'default'=>0,
        'help'=>gT(''),
        'caption'=>gT('Show id at end of string.'),
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
    $aSessionExtraSurvey=Yii::app()->session["questionExtraSurvey"];
    if(empty($aSessionExtraSurvey)) {
        $aSessionExtraSurvey=array();
    }
    if((Yii::app()->getRequest()->getParam('move')=='clearall' || Yii::app()->getRequest()->getParam('clearall')) && Yii::app()->getRequest()->getParam('extraSurvey')) {
      if(isset($aSessionExtraSurvey[$iSurveyId]) && isset($_SESSION['survey_'.$iSurveyId]['srid'])) {
        $oResponse=Response::model($iSurveyId)->find("id = :srid",array(":srid"=>$_SESSION['survey_'.$iSurveyId]['srid']));
        if($oResponse) {
          $oResponse->delete();
        }
        unset($aSessionExtraSurvey[$iSurveyId]);
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
        $renderMessage = new \renderMessage\messageHelper();
        $script = "if(window.location != window.parent.location && jQuery.isFunction(window.parent.surveySubmitted)) {\n";
        $script.= "  window.parent.surveySubmitted();\n";
        $script.= "}\n";
        Yii::app()->getClientScript()->registerScript("questionExtraSurveyComplete",$script,CClientScript::POS_LOAD);
        $renderMessage->render("Instrument deleted, you can close this window.");
      }
    }
    if(Yii::app()->getRequest()->getQuery('srid') && Yii::app()->getRequest()->getParam('extrasurveyqid')) {
      $title=Survey::model()->findByPk($iSurveyId)->getLocalizedTitle(); // @todo : get default lang title
      /* search if it's a related survey */
      $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND (value=:sid OR value=:title)  AND qid=:qid',array(
        ':attribute' => 'extraSurvey',
        ':sid' => $iSurveyId,
        ':title' => $title,
        ':qid' => Yii::app()->getRequest()->getParam('extrasurveyqid'),
      ));
      if($oAttributeExtraSurvey) {
        $token=Yii::app()->getRequest()->getParam('token');
        $oToken=Token::model($iSurveyId)->findByToken($token);
        if(!$oToken) {
            $oToken=Token::create($iSurveyId);
            $oToken->token=$token;
            $oToken->save();
        }
        unset($_SESSION['survey_'.$iSurveyId]);
        LimeExpressionManager::SetDirtyFlag();
        $this->qid = Yii::app()->getRequest()->getParam('extrasurveyqid');
        $this->token = $oToken->token;
        $aSessionExtraSurvey[$iSurveyId]=$oAttributeExtraSurvey->qid;
        Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      }
    }
    if(isset($aSessionExtraSurvey[$iSurveyId])) {
        $this->_manageExtraSurvey();
    }
  }

  /**
   *Ad script after survey complete
   */
  public function afterSurveyComplete() {
    $iSurveyId=$this->event->get('surveyId');
    $aSessionExtraSurvey=Yii::app()->session["questionExtraSurvey"];
    if(isset($aSessionExtraSurvey[$iSurveyId])) {
      unset($aSessionExtraSurvey[$iSurveyId]);
      Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
      $script = "if(window.location != window.parent.location && jQuery.isFunction(window.parent.surveySubmitted)) {\n";
      $script.= "  window.parent.surveySubmitted();\n";
      $script.= "}\n";
      Yii::app()->getClientScript()->registerScript("questionExtraSurveyComplete",$script,CClientScript::POS_END);
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
    if(Yii::app()->getRequest()->getParam('srid')=='new') {
      $this->getEvent()->set('response',false);
      return;
    }
    if(Yii::app()->getRequest()->getParam('srid')) {
      $oResponse=$this->_getResponse($this->getEvent()->get('surveyId'),Yii::app()->getRequest()->getParam('srid'));
      $oResponse->submitdate=null;
      $oResponse->save();
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
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'));
    $surveyId=$oEvent->get('surveyId');
    if(isset($aAttributes['extraSurvey']) && trim($aAttributes['extraSurvey'])) {
      $thisSurvey=Survey::model()->findByPk($surveyId);
      if(!$thisSurvey->hasTokens || $thisSurvey->anonymized == "Y") {
        return; // System need token
      }
      $extraSurveyAttribute=trim($aAttributes['extraSurvey']);
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
          return;
      }
      if(!$extraSurvey->hasTokens || $extraSurvey->anonymized == "Y"  || $extraSurvey->active != "Y") {
        return; // System need token and response
      }
      $this->qid=$oEvent->get("qid");
      $this->setSurveyListForAnswer($extraSurvey->sid,trim($aAttributes['extraSurveyQuestionLink']),trim($aAttributes['extraSurveyQuestion']),(bool)$aAttributes['extraSurveyShowId']);
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
    $srid=$this->api->getRequest()->getParam('srid');
    $qid=$this->api->getRequest()->getParam('qid');
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
          echo $this->_getHtmlPreviousResponse($surveyId,$srid,$token,$qid);
          break;
        }
    }
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param string $qCode question code to be used for link
   * @param boolean $bShowId or not
   * @return void
   */
  public function setSurveyListForAnswer($surveyId,$qCodeSrid,$qCodeText,$showId) {
    $oEvent=$this->getEvent();
    $oEvent->set("class",$oEvent->get("class")." questionExtraSUrvey");
    $token=isset($_SESSION['survey_'.$oEvent->get('surveyId')]['token']) ? $_SESSION['survey_'.$oEvent->get('surveyId')]['token'] : null;
    $srid=isset($_SESSION['survey_'.$oEvent->get('surveyId')]['srid']) ? $_SESSION['survey_'.$oEvent->get('surveyId')]['srid'] : null;
    $answer="";
    if(in_array($oEvent->get('type'),array("T","S")) ) {
      $name = "{$oEvent->get('surveyId')}X{$oEvent->get('gid')}X{$oEvent->get('qid')}";
      // $value = $_SESSION['survey_'.$oEvent->get('surveyId')][$name] || "";
      $value = isset($_SESSION['survey_'.$oEvent->get('surveyId')][$name]) ? $_SESSION['survey_'.$oEvent->get('surveyId')][$name] : "";
      $answer=\CHtml::tag("div",array(
        'class' => 'answer-item text-item hidden',
        'aria-hidden' => 'true',
        'title' => '',
        ),
        \CHtml::textField($name,$value,array(
          'id' => 'answer'.$name,
        ))
      );
    }
    Yii::setPathOfAlias('questionExtraSurvey',dirname(__FILE__));
    Yii::app()->clientScript->addPackage( 'questionExtraSurvey', array(
        'basePath'    => 'questionExtraSurvey.assets',
        'css'         => array('questionExtraSurvey.css'),
        'js'          => array('questionExtraSurvey.js'),
    ));
    Yii::app()->getClientScript()->registerPackage('questionExtraSurvey');
    $listOfReponses = $this->_getHtmlPreviousResponse($surveyId,$srid,$token,$oEvent->get('qid'));
    $ajaxUrl=$this->api->createUrl('plugins/direct', array('plugin' => 'questionExtraSurvey', 'function' => 'update',
      'surveyid'=>$surveyId,
      'token'=>$token,
      'srid'=>$srid,
      'qid'=>$oEvent->get('qid'),
    ));
    $listOfReponses="<div data-update-questionExtraSurvey='$ajaxUrl'>{$listOfReponses}</div>";
    $oEvent->set("answers",$answer.$listOfReponses);
    $modalConfirm=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalConfirm',array(),1);
    Yii::app()->getClientScript()->registerScript("questionExtraSurveyModalConfirm","$('body').prepend(".json_encode($modalConfirm).");",CClientScript::POS_READY);
    $modalSurvey=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalSurvey',array(),1);
    Yii::app()->getClientScript()->registerScript("questionExtraSurvey","$('body').prepend(".json_encode($modalSurvey).");",CClientScript::POS_READY);
  }

  /**
   * Set the answwer and other parameters for the system
   * @param int $surveyId
   * @param int $srid
   * @param string $token
   * @param string $qCodeText question code to be used for link
   * @param boolean $bShowId or not
   * @return void
   */
  private function _getHtmlPreviousResponse($surveyId,$srid,$token,$qid) {
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
    $qCodeText=trim($aAttributes['extraSurveyQuestion']);
    $showId=trim($aAttributes['extraSurveyShowId']);
    $qCodeSrid=trim($aAttributes['extraSurveyQuestionLink']);
    $aResponses=$this->_getPreviousResponse($surveyId,$srid,$token,$qCodeText,$showId,$qCodeSrid);
    $newUrlParam=array(
      'sid' =>$surveyId,
      'extrasurveyqid' => $qid,
      'newtest' =>'Y',
      'token' => $token,
      'srid' => 'new',
    );
    if(!empty($qCodeSrid)) {
      $newUrlParam[$qCodeSrid]=$srid;
    }
    $renderData=array(
      'aResponses'=>$aResponses,
      'surveyid'=>$surveyId,
      'extrasurveyqid' => $qid,
      'token' => $token,
      'newUrl'=>Yii::app()->getController()->createUrl('survey/index',$newUrlParam),
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
  private function _getPreviousResponse($surveyId,$srid,$token,$qCodeText,$showId=false,$qCodeSrid=null) {
    $aSelect=array(
      'id',
      'token',
      'submitdate'
    );

    /* Find the question code */
    $oQuestionText=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeText));
    $qCodeText = null;
    if($oQuestionText && in_array($oQuestionText->type,array("T","L","!","S")) ) {
      $qCodeText = $aSelect[] = "{$oQuestionText->sid}X{$oQuestionText->gid}X{$oQuestionText->qid}";
    }

    $oCriteria = new CDbCriteria;
    $oCriteria->select = $aSelect;
    $oCriteria->condition="token=:token";
    $oCriteria->params = array(":token"=>$token);
    if($qCodeSrid && $srid) {
      $oQuestionSrid=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeSrid));
      if($oQuestionSrid && in_array($oQuestionSrid->type,array("T","S")) ) {
        $qCodeSrid = "{$oQuestionSrid->sid}X{$oQuestionSrid->gid}X{$oQuestionSrid->qid}";
        $oCriteria->compare($qCodeSrid,$srid);
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
          $aResponses[$oResponse->id]['text'] .= \CHtml::tag('span',array('class'=>'label label-info'),$oResponse->id);
        }
        if($qCodeText) {
          switch ($oQuestionText->type) {
            case "!":
            case "L":
              $oAnswer=Answer::model()->find("qid=:qid and language=:language and code=:code",array(
                ':qid' => $oQuestionText->qid,
                ':language' => Yii::app()->getLanguage(),
                ':code'=>$oResponse->$qCodeText,
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
  private function _manageExtraSurvey()
  {
    $script = "if(window.location != window.parent.location && jQuery.isFunction(window.parent.surveyLoaded)) {\n";
    $script.= "  window.parent.surveyLoaded();\n";
    $script.= "}\n";
    Yii::app()->getClientScript()->registerScript("questionExtraSurveyPage",$script,CClientScript::POS_READY);
    // Add as option in qid ?
    
    //~ $jsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets/extraSurvey.js');
    //~ App()->getClientScript()->registerScriptFile($jsUrl,CClientScript::POS_READY);
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
      throw new CHttpException(404,gT("Invalid initiative id"));
    }
    /* Must control token validity */
    $token=Yii::app()->getRequest()->getParam('token');
    if(!empty($oResponse->token) && $oResponse->token != $token ) {
      throw new CHttpException(403,gT("This initiative is not for you"));
    }
    if(empty($oResponse->token) && !empty($token) ) {
      $oResponse->token=$token;
      $oResponse->save();
    }
    return $oResponse;
  }
  
}
