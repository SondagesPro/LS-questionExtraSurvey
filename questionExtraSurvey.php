<?php
/**
 * questionExtraSurvey use a question to add survey inside survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017-2020 Denis Chenu <www.sondages.pro>
 * @copyright 2017 OECD (Organisation for Economic Co-operation and Development ) <www.oecd.org>
 * @license AGPL v3
 * @version 3.0.2
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
    protected static $name = 'questionExtraSurvey';
    protected static $description = 'Add survey inside survey : need a survey not anonymous and with token table for the 2 surveys.';

    protected $storage = 'DbStorage';

    /**
     * actual qid for this survey
     */
    private $qid;

    /**
     * @var integer the DB version needed
     */
    private static $DBversion = 1;

    /**
    * Add function to be used in beforeQuestionRender event and to attriubute
    */
    public function init()
    {
        Yii::setPathOfAlias('questionExtraSurvey', dirname(__FILE__));

        $this->subscribe('beforeQuestionRender');
        $this->subscribe('newQuestionAttributes', 'addExtraSurveyAttribute');

        $this->subscribe('beforeSurveyPage');
        $this->subscribe('beforeLoadResponse');

        $this->subscribe('newDirectRequest');

        $this->subscribe('afterSurveyComplete');

        $this->subscribe('beforeCloseHtml');
    }

    /**
     * The attribute, try to set to readonly for no XSS , but surely broken ....
     */
    public function addExtraSurveyAttribute()
    {
        $this->fixDbByVersion();
        $extraAttributes = array(
            'extraSurvey'=> array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>10, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'help'=>$this->translate('If is integer : search the survey id, else search by name of survey (first activated one is choosen)'),
                'caption'=>$this->translate('Survey to use'),
            ),
            'extraSurveyQuestionLink'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>20, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'help'=>$this->translate('The question code in the extra survey to be used. If empty : only token or optionnal fields was used for the link.'),
                'caption'=>$this->translate('Question for response id'),
            ),
            'extraSurveyQuestionLinkUse'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>25, /* Own category */
                'inputtype'=>'switch',
                'default'=>1,
                'help'=>$this->translate('Choose if you want only related to current response. If survey use token persistence and allow edition, not needed and id can be different after import an old response database.'),
                'caption'=>$this->translate('Get only response related to current response id.'),
            ),
            'extraSurveyTokenUsage'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>28, /* Own category */
                'inputtype'=>'singleselect',
                'options'=>array(
                    //~ //'no'=>gT('No'),
                    'token'=>gT('Yes'),
                    'group'=>gT('Token Group (with responseListAndManage plugin)')
                ),
                'default'=>'token',
                'help'=>$this->translate('If you have responseListAndManage, the response list can be found using the group of current token.'),
                'caption'=>$this->translate('Usage of token.'),
            ),
            'extraSurveyQuestion'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>30, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'help'=>$this->translate('This can be text question type, single choice question type or equation question type.'),
                'caption'=>$this->translate('Question code for listing.'),
            ),
            'extraSurveyAutoDelete'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>35, /* Own category */
                'inputtype'=>'switch',
                'default'=>1,
                'help'=>$this->translate('When dialog box is closed, the new survey was checked, if title is empty : survey was deleted. This doesn\'t guarantee you never have empty title, just less.'),
                'caption'=>$this->translate('Delete survey without title when close.'),
            ),
            'extraSurveyOtherField'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>60, /* Own category */
                'inputtype'=>'textarea',
                'default'=>"",
                'expression'=>1,
                'help'=>$this->translate('One field by line, field must be a valid question code (single question only). Field and value are separated by colon (<code>:</code>), you can use Expressiona Manager in value.'),
                'caption'=>$this->translate('Other question fields for relation.'),
            ),
            'extraSurveyQuestionAllowDelete'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>70, /* Own category */
                'inputtype'=>'switch',
                'default'=>0,
                'help'=>$this->translate("Add a button to delete inside modal box, this allow user to really delete the reponse."),
                'caption'=>$this->translate('Allow delete response.'),
            ),
            'extraSurveyDeleteUnsubmitted'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>75, /* Own category */
                'inputtype'=>'switch',
                'default'=>0,
                'help'=>$this->translate("If a survey is unsubmitted : disallow close of dialog before submitting."),
                'caption'=>$this->translate('Disallow close without submit.'),
            ),
            'extraSurveyFillAnswer' => array(
                'types'=>'T',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>85, /* Own category */
                'inputtype'=>'singleselect',
                'options' => array(
                    'listall' => $this->translate('List of all answers.'),
                    'listsubmitted' => $this->translate('List of submitted answers.'),
                    'number' => $this->translate('Number of submitted and not submitted answers.'),
                ),
                'default'=>'number',
                'help'=>$this->translate('Recommended method is number : submlitted answer as set as integer part, and not submitted as decimal part (<code>submitted[.not-submitted]</code>).You can check if all answer are submitted with <code>intval(self)==self</code>.'),
                'caption'=>$this->translate('Way for filling the answer.'),
            ),
            'extraSurveyShowId'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>90, /* Own category */
                'inputtype'=>'switch',
                'default'=>0,
                'help'=>$this->translate('This shown survey without title too.'),
                'caption'=>$this->translate('Show id at end of string.'),
            ),
            'extraSurveyOrderBy'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>95, /* Own category */
                'inputtype'=>'text',
                'default'=>"",
                'help'=>sprintf($this->translate('You can use %sSGQA identifier%s or question code iof you have getQuestionInformation plugin for the columns to be ordered. The default order is ASC, you can use DESC. You can use <code>,</code> for multiple order.'), '<a href="https://manual.limesurvey.org/SGQA_identifier" target="_blank">', '</a>'),
                'caption'=>$this->translate('Order by (default “id DESC”, “datestamp ASC” for datestamped surveys)'),
            ),
            'extraSurveyNameInLanguage'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>100, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'i18n'=>true,
                'expression'=>1,
                'help'=>$this->translate('Default to “response“ (translated)'),
                'caption'=>$this->translate('Show response as'),
            ),
            'extraSurveyAddNewInLanguage'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>101, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'i18n'=>true,
                'expression'=>1,
                'help'=>$this->translate('Default to “Add new response”, where response is the previous parameter (translated)'),
                'caption'=>$this->translate('Add new line text'),
            ),
            'extraSurveyAutoCloseSubmit'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>200, /* Own category */
                'inputtype'=>'singleselect',
                'options' => array(
                    'replace' => $this->translate('Replace totally the content and close dialog box, this can disable other plugin system.'),
                    'addjs' => $this->translate('Add information and close dialog box'),
                    'add' => $this->translate('Add information'),
                    'js' => $this->translate('Only close dialog box'),
                ),
                'default'=>'addjs',
                'i18n'=>false,
                'expression'=>0,
                'help'=>$this->translate('Using replace disable all other plugin event, dialog box are closed using javascript solution.'),
                'caption'=>$this->translate('Auto close when survey is submitted.'),
            ),
            'extraSurveyMaxresponse'=>array(
                'types'=>'XT',
                'category'=>$this->translate('Extra survey'),
                'sortorder'=>210, /* Own category */
                'inputtype'=>'text',
                'default'=>'',
                'i18n'=>false,
                'expression'=>1,
                'help'=>$this->translate('Show the add button until this value is reached. This do not disable adding response by other way.'),
                'caption'=>$this->translate('Maximum reponse.'),
            ),
        );
        if (Yii::getPathOfAlias('getQuestionInformation')) {
            $extraAttributes['extraSurveyOrderBy']['help'] = sprintf(
                $this->translate('You can use %sexpression manager variables%s (question title for example) for the value to be orderd.For the order default is ASC, you can use DESC.'),
                '<a href="https://manual.limesurvey.org/Expression_Manager_-_presentation#Access_to_variables" target="_blank">',
                '</a>'
            );
        }
        if (method_exists($this->getEvent(), 'append')) {
            $this->getEvent()->append('questionAttributes', $extraAttributes);
        } else {
            $questionAttributes=(array)$this->event->get('questionAttributes');
            $questionAttributes=array_merge($questionAttributes, $extraAttributes);
            $this->event->set('questionAttributes', $questionAttributes);
        }
    }

    /**
     * Add extra script
     */
    public function beforeCloseHtml()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        if (empty($surveyId)) {
            return;
        }
        $aSessionExtraSurvey = Yii::app()->session["questionExtraSurvey"];
        if (!isset($aSessionExtraSurvey[$surveyId])) {
            return;
        }
        $currentSrid = isset($_SESSION['survey_'.$surveyId]['srid']) ? $_SESSION['survey_'.$surveyId]['srid'] : null;
        if (empty($currentSrid)) {
            return;
        }
        $html = Chtml::hiddenField(
            'sridQuestionExtraSurvey',
            $currentSrid,
            array(
                'disable' => true,
                'id' => 'sridQuestionExtraSurvey'
            )
        );
        $event->set('html', $html);
    }
    /**
     * Access control on survey
     */
    public function beforeSurveyPage()
    {
        $this->fixDbByVersion();
        $iSurveyId=$this->event->get('surveyId');
        $oSurvey = Survey::model()->findByPk($iSurveyId);
        if (!$oSurvey) {
            return;
        }
        $aSessionExtraSurvey = Yii::app()->session["questionExtraSurvey"];
        if (empty($aSessionExtraSurvey)) {
            $aSessionExtraSurvey=array();
        }
        /* Fill session if it's in another survey */
        if (Yii::app()->getRequest()->getQuery('extrasurveysrid') && Yii::app()->getRequest()->getParam('extrasurveyqid')) {
            if ($this->validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('extrasurveyqid'), $iSurveyId)) {
                $currentlang = Yii::app()->getLanguage();
                //~ killSurveySession($iSurveyId);
                SetSurveyLanguage($iSurveyId, $currentlang); // frontend_helper function
                $this->qid = Yii::app()->getRequest()->getParam('extrasurveyqid');
                $aSessionExtraSurvey[$iSurveyId]=Yii::app()->getRequest()->getParam('extrasurveyqid');
                Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
            } else {
                throw new CHttpException(400, "Invalid $iSurveyId");
            }
        }
        if (Yii::app()->getRequest()->getPost('questionExtraSurveyQid')) {
            if ($this->validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('questionExtraSurveyQid'), $iSurveyId)) {
                $aSessionExtraSurvey[$iSurveyId] = Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
                Yii::app()->session["questionExtraSurvey"] = $aSessionExtraSurvey;
            }
        }
        if (!isset($aSessionExtraSurvey[$iSurveyId])) {
            /* Quit if we are not in survey inside survey system */
            return;
        }
        if (version_compare(Yii::app()->getConfig('versionnumber'), "3", ">=")) {
            Template::model()->getInstance(null, $iSurveyId)->oOptions->ajaxmode = 'off';
        }
        $this->resetEMIfNeeded($iSurveyId);

        $currentSrid = isset($_SESSION['survey_'.$iSurveyId]['srid']) ? $_SESSION['survey_'.$iSurveyId]['srid'] : null;

        if ((Yii::app()->getRequest()->getParam('move')=='delete')) {
            if (isset($aSessionExtraSurvey[$iSurveyId]) && $currentSrid) {
                $qid = $aSessionExtraSurvey[$iSurveyId];
                /* check if qid with this survey allow delete */
                $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid', array(
                    ':attribute' => 'extraSurvey',
                    ':qid' => $qid,
                ));
                if (empty($oAttributeExtraSurvey) || ($oAttributeExtraSurvey->value != $iSurveyId && $oAttributeExtraSurvey->value != $title)) {
                    return;
                }
                $oAttributeExtraSurveyDelete=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid', array(
                    ':attribute' => 'extraSurveyQuestionAllowDelete',
                    ':qid' => $qid,
                ));
                if (empty($oAttributeExtraSurveyDelete) || empty($oAttributeExtraSurveyDelete->value)) {
                    return;
                }
                $oResponse=Response::model($iSurveyId)->find("id = :srid", array(":srid"=>$currentSrid));
                if ($oResponse) {
                    $oResponse->delete();
                }
                if (Yii::getPathOfAlias('reloadAnyResponse')) {
                    \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
                }
                $renderMessage = new \renderMessage\messageHelper();
                $this->qesRegisterExtraSurveyScript();
                App()->getClientScript()->registerScript("questionExtraSurveyComplete", "autoclose();\n", CClientScript::POS_END);
                $aAttributes=QuestionAttribute::model()->getQuestionAttributes($aSessionExtraSurvey[$iSurveyId]);
                unset($aSessionExtraSurvey[$iSurveyId]);
                Yii::app()->session["questionExtraSurvey"] = $aSessionExtraSurvey;
                $reponseName = mb_strtolower(gT("Response"), 'UTF-8');
                if(!empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()])) {
                    $reponseName = $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
                }
                $renderMessage->render(sprintf($this->translate("%s deleted, you can close this window."), $reponseName));
            }
        }
        if ((Yii::app()->getRequest()->getParam('move')=='saveall' || Yii::app()->getRequest()->getParam('saveall'))) {
            if (isset($aSessionExtraSurvey[$iSurveyId]) && $currentSrid) {
                $oSurvey = Survey::model()->findByPk($iSurveyId);
                //~ App()->getClientScript()->registerScript("questionExtraSurveySaved","autoclose();\n",CClientScript::POS_END);
                $hiddenElement = "";
                if ($oSurvey->active == "Y") {
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
                    $hiddenElement = Chtml::hiddenField(
                        'sridQuestionExtraSurvey',
                        $oResponse->id,
                        array(
                            'disable' => true,
                            'id' => 'sridQuestionExtraSurvey'
                        )
                    );
                }
                $this->qesRegisterExtraSurveyScript();
                App()->getClientScript()->registerScript("questionExtraSurveyComplete", "autoclose();\n", CClientScript::POS_END);
                killSurveySession($iSurveyId);
                unset($aSessionExtraSurvey[$iSurveyId]);
                Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
                if (Yii::getPathOfAlias('reloadAnyResponse')) {
                    \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
                }
                if (Yii::getPathOfAlias('renderMessage')) {
                    \renderMessage\messageHelper::renderAlert($this->translate("Your responses was saved with success, you can close this windows.").$hiddenElement);
                }
            }
        }
        $this->qesRegisterExtraSurveyScript();
    }

    /**
     *Add script after survey complete
     */
    public function afterSurveyComplete()
    {
        $iSurveyId = $this->event->get('surveyId');
        $currentSrid = $this->event->get('responseId');
        $aSessionExtraSurvey=Yii::app()->session["questionExtraSurvey"];
        $currentQid = null;
        if (Yii::app()->getRequest()->getPost('questionExtraSurveyQid')) {
            if ($this->validateQuestionExtraSurvey(Yii::app()->getRequest()->getParam('questionExtraSurveyQid'), $iSurveyId)) {
                $aSessionExtraSurvey[$iSurveyId]=Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
                $currentQid = Yii::app()->getRequest()->getPost('questionExtraSurveyQid');
                Yii::app()->session["questionExtraSurvey"]=$aSessionExtraSurvey;
            }
        }
        if (!isset($aSessionExtraSurvey[$iSurveyId])) {
            /* Quit if we are not in survey inside surey system */
            return;
        }
        $extraSurveyAutoCloseSubmit = 'addjs';
        if ($currentQid) {
            $oQuestionAttribute = QuestionAttribute::model()->find(
                "qid =:qid AND attribute = :attribute",
                array(":qid"=>$currentQid,":attribute"=>'extraSurveyAutoCloseSubmit')
            );
            if ($oQuestionAttribute && $oQuestionAttribute->value) {
                $extraSurveyAutoCloseSubmit = $oQuestionAttribute->value;
            }
        }

        unset($aSessionExtraSurvey[$iSurveyId]);
        Yii::app()->session["questionExtraSurvey"] = $aSessionExtraSurvey;
        if (in_array($extraSurveyAutoCloseSubmit, array('replace','addjs','js'))) {
            $script = "if(window.location != window.parent.location) {\n";
            $script.= "  window.parent.$(window.parent.document).trigger('extrasurveyframe:autoclose');\n";
            $script.= "}\n";
            Yii::app()->getClientScript()->registerScript("questionExtraSurveyComplete", $script, CClientScript::POS_END);
        }
        if ($currentSrid && Yii::getPathOfAlias('reloadAnyResponse')) {
            \reloadAnyResponse\models\surveySession::model()->deleteByPk(array('sid'=>$iSurveyId,'srid'=>$currentSrid));
        }
        if ($currentSrid && $extraSurveyAutoCloseSubmit == 'replace' && Yii::getPathOfAlias('renderMessage')) {
            \renderMessage\messageHelper::renderAlert($this->translate("Your responses was saved as complete, you can close this windows."));
            return;
        }
        if (in_array($extraSurveyAutoCloseSubmit, array('add','addjs'))) {
            $this->getEvent()->getContent($this)
                ->addContent("<p class='alert alert-success'>".$this->translate("Your responses was saved as complete, you can close this windows.")."</p>");
        }
    }
    /**
     * Recall good survey
     * And need to reset partially extra survey
     */
    public function beforeLoadResponse()
    {
        if (!$this->qid) {
            return;
        }
        $beforeLoadResponseEvent = $this->getEvent();
        $iSurveyId=$beforeLoadResponseEvent->get('surveyId');
        if (Yii::app()->getRequest()->getParam('extrasurveysrid')=='new') {
            $beforeLoadResponseEvent->set('response', false);
            return;
        }
        if (Yii::app()->getRequest()->getParam('extrasurveysrid')) {
            $oResponse=$this->getResponse($iSurveyId, Yii::app()->getRequest()->getParam('extrasurveysrid'), $this->qid);
            if ($oResponse->submitdate) {
                if (Survey::model()->findByPk($iSurveyId)->alloweditaftercompletion != "Y") {
                    $oResponse->submitdate=null;
                }
                $oResponse->lastpage=0;
                $oResponse->save();
            }
            $beforeLoadResponseEvent->set('response', $oResponse);
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
        $aQuestionAttributes=QuestionAttribute::model()->getQuestionAttributes($oEvent->get('qid'), Yii::app()->getLanguage());
        $surveyId=$oEvent->get('surveyId');
        if (isset($aQuestionAttributes['extraSurvey']) && trim($aQuestionAttributes['extraSurvey'])) {
            $token = Yii::app()->getRequest()->getParam('token');
            if (empty($token)) {
                $token = !empty(Yii::app()->session["survey_$surveyId"]['token']) ? Yii::app()->session["survey_$surveyId"]['token'] : null;
            }
            $thisSurvey=Survey::model()->findByPk($surveyId);
            $extraSurveyAttribute=trim($aQuestionAttributes['extraSurvey']);
            if (!ctype_digit($extraSurveyAttribute)) {
                $oLangSurvey = SurveyLanguageSetting::model()->find(array(
                    'select'=>'surveyls_survey_id',
                    'condition'=>'surveyls_title = :title AND surveyls_language =:language ',
                    'params'=>array(
                        ':title' => $extraSurveyAttribute,
                        ':language' => Yii::app()->getLanguage(),
                    ),
                ));
                if ($oLangSurvey) {
                    $extraSurveyAttribute = $oLangSurvey->surveyls_survey_id;
                }
            }
            $extraSurvey=Survey::model()->findByPk($extraSurveyAttribute);
            $disableMessage = "";
            if (!$extraSurvey) {
                $disableMessage = sprintf($this->translate("Invalid survey %s for question %s."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && $extraSurvey->active != "Y") {
                $disableMessage = sprintf($this->translate("Survey %s for question %s not activated."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && !$this->surveyAccessWithToken($thisSurvey) && $this->surveyAccessWithToken($extraSurvey)) {
                $disableMessage = sprintf($this->translate("Survey %s for question %s can not be used with a survey without tokens."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && $this->surveyAccessWithToken($thisSurvey) && $extraSurvey->anonymized == "Y") {
                $disableMessage = sprintf($this->translate("Survey %s for question %s need to be not anonymized."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && $this->surveyAccessWithToken($thisSurvey) && $extraSurvey->anonymized == "Y") {
                $disableMessage = sprintf($this->translate("Survey %s for question %s need to be not anonymized."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && $this->surveyAccessWithToken($extraSurvey) && Yii::app()->getConfig('previewmode') && !Yii::app()->request->getQuery('token')) {
                $disableMessage = sprintf($this->translate("Survey %s for question %s can not be loaded in preview mode without a valid token."), $extraSurveyAttribute, $oEvent->get('qid'));
            }
            if (!$disableMessage && $this->surveyAccessWithToken($extraSurvey)) {
                if (!$this->validateToken($extraSurvey, $thisSurvey, $token)) {
                    $disableMessage = sprintf($this->translate("Survey %s for question %s token can not ne found or created."), $extraSurveyAttribute, $oEvent->get('qid'));
                }
            }

            if ($disableMessage) {
                $oEvent->set("answers", CHtml::tag("div", array('class'=>'alert alert-warning'), $disableMessage));
                return;
            }
            $this->setSurveyListForAnswer($extraSurvey->sid, $aQuestionAttributes, $token);
        }
    }

    public function newDirectRequest()
    {
        $oEvent = $this->event;
        if ($oEvent->get('target') != get_class()) {
            return;
        }
        $surveyId = $this->api->getRequest()->getParam('surveyid');
        $token = $this->api->getRequest()->getParam('token');
        $srid = $this->api->getRequest()->getParam('extrasurveysrid');
        $qid = $this->api->getRequest()->getParam('qid');
        $lang = $this->api->getRequest()->getParam('lang');
        if (!$surveyId || !$srid || !$qid) {
            return;
        }
        $sAction=$oEvent->get('function');
        switch ($sAction) {
            case 'update':
                $title=Survey::model()->findByPk($surveyId)->getLocalizedTitle();
                /* search if it's a related survey */
                $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid', array(
                    ':attribute' => 'extraSurvey',
                    ':qid' => $qid,
                ));
                if ($oAttributeExtraSurvey && ($oAttributeExtraSurvey->value == $surveyId || $oAttributeExtraSurvey->value == $title)) {
                    echo $this->getHtmlPreviousResponse($surveyId, $srid, $qid, $token, $lang, true);
                    break;
                }
                break;
            case 'check':
                $title=Survey::model()->findByPk($surveyId)->getLocalizedTitle();
                /* search if it's a related survey */
                $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid', array(
                    ':attribute' => 'extraSurvey',
                    ':qid' => $qid,
                ));
                $sridToCheck =  $this->api->getRequest()->getParam('srid');
                if ($oAttributeExtraSurvey && ($oAttributeExtraSurvey->value == $surveyId || $oAttributeExtraSurvey->value == $title)) {
                    echo $this->checkIfTitleIsEmpty($qid, $surveyId, $sridToCheck);
                    break;
                }
                 break;
            // no break
            default:
                throw new CHttpException(400, "Invalid function $sAction");
            // Nothing to do (except log error)
        }
    }

    /**
     * Check if the final title is empty, delete if true (accordinng to setting)
     * @param integer $qid questio id source
     * @param $sridToCheck : the srid of extra survey
     * @return echo an integer number of deleted srid (0 or 1)
     */
    private function checkIfTitleIsEmpty($qid, $extraSurveyId, $sridToCheck)
    {
        $oQuestion = Question::model()->find("qid = :qid",array(":qid" => $qid));
        if(empty($oQuestion)) {
            return;
        }
        if(!$this->validateQuestionExtraSurvey($qid, $extraSurveyId)) {
            return;
        }
        $thisSurveyId = $oQuestion->sid;
        $aAttributes = QuestionAttribute::model()->getQuestionAttributes($qid);
        if(empty($aAttributes['extraSurveyQuestion'])) {
            return;
        }
        if(empty($aAttributes['extraSurveyAutoDelete'])) {
            return;
        }
        $qCodeText = trim($aAttributes['extraSurveyQuestion']);
        if(empty($qCodeText)) {
            return;
        }

        /* validate 2 survey … TODO ! move to a new function */
        $thisSurvey = Survey::model()->findByPk($extraSurveyId);
        $extraSurvey = Survey::model()->findByPk($extraSurveyId);
        if (!$extraSurvey) {
            return;
        }
        if ($extraSurvey->active != "Y") {
            return;
        }
        if (!$this->surveyAccessWithToken($thisSurvey) && $this->surveyAccessWithToken($extraSurvey)) {
            return;
        }
        if ($this->surveyAccessWithToken($thisSurvey) && $extraSurvey->anonymized == "Y") {
            return;
        }
        if ($this->surveyAccessWithToken($extraSurvey) && Yii::app()->getConfig('previewmode') && !Yii::app()->request->getQuery('token')) {
            return;
        }
        /* Validate what we need */
        $token = isset($_SESSION["survey_$thisSurveyId"]['token']) ? $_SESSION["survey_$thisSurveyId"]['token'] : null;
        $srid = isset($_SESSION["survey_$thisSurveyId"]['srid']) ? $_SESSION["survey_$thisSurveyId"]['srid'] : null;
        /* Search and delete */
        $qCodeSrid = trim($aAttributes['extraSurveyQuestionLink']);

        $relatedTokens = $aAttributes['extraSurveyTokenUsage'] == 'group';
        $aOtherFields = $this->getOtherField($qid);
        if (!$aAttributes['extraSurveyQuestionLinkUse']) {
            $qCodeSrid = null;
        }
        /* Find the question code */
        $oQuestionText = Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$extraSurveyId,":title"=>$qCodeText));
        $qCodeText = null;
        if ($oQuestionText && in_array($oQuestionText->type, array("S","T","U","L","!","O","N","D","G","Y","*"))) {
            $qCodeText = "{$oQuestionText->sid}X{$oQuestionText->gid}X{$oQuestionText->qid}";
            $aSelect[] = Yii::app()->db->quoteColumnName($qCodeText);
        }
        if(empty($qCodeText)) {
            return;
        }
        $oCriteria = new CDbCriteria;
        $qQuotesCodeText = Yii::app()->db->quoteColumnName($qCodeText);
        $oCriteria->condition = "$qQuotesCodeText IS NULL OR $qQuotesCodeText = ''";
        if ($token) {
            $tokens = array($token=>$token);
            if ($relatedTokens) {
                $tokens = $this->getTokensList($extraSurveyId, $token);
            }
            $oCriteria->addInCondition("token", $tokens);
        }
        if ($qCodeSrid && $srid) {
            $oQuestionSrid=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$extraSurveyId,":title"=>$qCodeSrid));
            if ($oQuestionSrid && in_array($oQuestionSrid->type, array("T","S","N"))) {
                $qCodeSrid = "{$oQuestionSrid->sid}X{$oQuestionSrid->gid}X{$oQuestionSrid->qid}";
                $oCriteria->compare(Yii::app()->db->quoteColumnName($qCodeSrid), $srid);
            }
        }
        if (!empty($aOtherFields)) {
            foreach ($aOtherFields as $questionCode => $value) {
                $oQuestionOther=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$extraSurveyId,":title"=>$questionCode));
                if ($oQuestionOther && in_array($oQuestionOther->type, array("5","D","G","I","L","N","O","S","T","U","X","Y","!","*"))) {
                    $qCode = "{$oQuestionOther->sid}X{$oQuestionOther->gid}X{$oQuestionOther->qid}";
                    $oCriteria->compare(Yii::app()->db->quoteColumnName($qCode), $value);
                }
            }
        }
        $oCriteria->compare(Yii::app()->db->quoteColumnName("id"), $sridToCheck);
        echo Response::model($extraSurveyId)->deleteAll($oCriteria);
    }
    /**
     * Set the answwer and other parameters for the system
     * @param int $surveyId for answers
     * @param array $qAttributes
     * @param string $token
     * @return void
     */
    private function setSurveyListForAnswer($surveyId, $aQuestionAttributes, $token = null)
    {
        $oEvent=$this->getEvent();
        $qid = $oEvent->get('qid');
        $currentSurveyId = $oEvent->get('surveyId');
        $oEvent->set("class", $oEvent->get("class")." questionExtraSurvey");
        if (!$token && $this->surveyAccessWithToken(Survey::model()->findByPk($currentSurveyId))) {
            $token = (!empty(Yii::app()->session["survey_{$currentSurveyId}"]['token'])) ? Yii::app()->session["survey_{$currentSurveyId}"]['token'] : null;
        }
        $srid = !empty(Yii::app()->session["survey_{$currentSurveyId}"]['srid']) ? Yii::app()->session["survey_{$currentSurveyId}"]['srid'] : null;
        $this->setOtherField($qid, $aQuestionAttributes['extraSurveyOtherField'], $surveyId);
        Yii::setPathOfAlias('questionExtraSurvey', dirname(__FILE__));
        Yii::app()->clientScript->addPackage('questionExtraSurveyManage', array(
            'basePath'    => 'questionExtraSurvey.assets',
            'css'         => array('questionExtraSurvey.css'),
            'js'          => array('questionExtraSurvey.js'),
        ));
        Yii::app()->getClientScript()->registerPackage('questionExtraSurveyManage');
        $listOfReponses = $this->getHtmlPreviousResponse($surveyId, $srid, $oEvent->get('qid'), $token);
        $ajaxUrl = Yii::app()->getController()->createUrl(
            'plugins/direct', array(
                'plugin' => 'questionExtraSurvey',
                'function' => 'update',
                'surveyid'=>$surveyId,
                'token'=>$token,
                'extrasurveysrid'=>$srid,
                'qid'=>$oEvent->get('qid'),
                'lang'=>Yii::app()->getLanguage(),
            )
        );
        $ajaxCheckUrl = Yii::app()->getController()->createUrl(
            'plugins/direct', array(
                'plugin' => 'questionExtraSurvey',
                'function' => 'check',
                'surveyid' => $surveyId,
                'token '=> $token,
                'extrasurveysrid' => $srid,
                'qid'=>$oEvent->get('qid'),
                'lang'=>Yii::app()->getLanguage(),
            )
        );
        $oSurveyFrame = Survey::model()->findByPk($surveyId);
        $reponseName = empty($aQuestionAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? mb_strtolower(gT("Response"), 'UTF-8') : $aQuestionAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
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
            '   Are you sure to delete this response.' => sprintf($this->translate("Are you sure to delete this %s."), $reponseName),
            ),
            'qid'=>$oEvent->get('qid'),
        );
        $listOfReponses = CHtml::tag(
            "div",
            array(
                'data-update-questionextrasurvey' => $ajaxUrl,
                'data-closecheck-questionextrasurvey' => $ajaxCheckUrl,
                'data-modalparams-questionextrasurvey' => ls_json_encode($modalParams)
            ),
            $listOfReponses
        );
        $oEvent->set("answers", $listOfReponses);
        $this->qesAddModal($oEvent->get('qid'), $aQuestionAttributes);
    }

    /**
     * Add modal script at end of page with javascript
     * @return void
     */
    private function qesAddModal($qid, $aQuestionAttributes)
    {
        $renderData=array(
            'qid' => $qid,
            'language' => array(
                'Are you sure to delete this response.' => sprintf($this->translate("Are you sure to delete this %s."), mb_strtolower(gT("Response"), 'UTF-8')),
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
        if (!Yii::app()->getClientScript()->isScriptRegistered("questionExtraSurveyModalConfirm", $posReady)) {
            $modalConfirm=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalConfirm', $renderData, 1);
            Yii::app()->getClientScript()->registerScript("questionExtraSurveyModalConfirm", "$('body').prepend(".json_encode($modalConfirm).");", $posReady);
        }
        if (!Yii::app()->getClientScript()->isScriptRegistered("questionExtraSurveyModalSurvey", $posReady)) {
            $modalSurvey=Yii::app()->controller->renderPartial('questionExtraSurvey.views.modalSurvey', $renderData, 1);
            Yii::app()->getClientScript()->registerScript("questionExtraSurveyModalSurvey", "$('body').prepend(".json_encode($modalSurvey).");", $posReady);
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
    private function getHtmlPreviousResponse($surveyId, $srid, $qid, $token = null, $lang = null, $reloaded = false)
    {
        if ($lang) {
            Yii::app()->setLanguage($lang);
        }
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
        $inputName=null;
        $oQuestion=Question::model()->find("qid=:qid", array(":qid"=>$qid));
        if (in_array($oQuestion->type, array("T","S"))) {
            $inputName = $oQuestion->sid."X".$oQuestion->gid."X".$oQuestion->qid;
        }
        if (Survey::model()->findByPk($surveyId)->active != "Y") {
            return "<p class='alert alert-warning'>".sprintf($this->translate("Related Survey is not activate, related response deactivated."), $surveyId)."</p>";
        }

        $qCodeText = trim($aAttributes['extraSurveyQuestion']);
        $showId = trim($aAttributes['extraSurveyShowId']);
        $extraSurveyMaxresponse = trim($aAttributes['extraSurveyMaxresponse']);
        if ($extraSurveyMaxresponse) {
            $extraSurveyMaxresponse = $this->qesEMProcessString($extraSurveyMaxresponse, true);
        }
        $orderBy = isset($aAttributes['extraSurveyOrderBy']) ? trim($aAttributes['extraSurveyOrderBy']) : null;
        $qCodeSrid = $qCodeSridUsed = trim($aAttributes['extraSurveyQuestionLink']);
        $extraSurveyFillAnswer=trim($aAttributes['extraSurveyFillAnswer']);
        $relatedTokens = $aAttributes['extraSurveyTokenUsage'] == 'group';
        $extraSurveyOtherField=$this->getOtherField($qid);
        if (!$aAttributes['extraSurveyQuestionLinkUse']) {
            $qCodeSridUsed = null;
        }
        $aResponses=$this->getPreviousResponse($surveyId, $srid, $token, $qid);
        $newUrlParam=array(
            'sid' =>$surveyId,
            'extrasurveyqid' => $qid,
            'newtest' =>'Y',
            'token' => $token,
            'extrasurveysrid' => 'new',
            'lang' => Yii::app()->getLanguage()
        );
        if (!empty($qCodeSrid)) {
            $newUrlParam[$qCodeSrid]=$srid;
        }
        if (!empty($extraSurveyOtherField)) {
            foreach ($extraSurveyOtherField as $key => $value) {
                $newUrlParam[$key]=$value;
            }
        }
        /* Need some information on current Srid */
        $currentSusrveyId = $oQuestion->sid;
        $currentStep = isset($_SESSION['survey_'.$currentSusrveyId]) ? $_SESSION['survey_'.$currentSusrveyId]['step'] : null;
        $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? mb_strtolower(gT("Response"), 'UTF-8') : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
        $reponseAddNew = empty($aAttributes['extraSurveyAddNewInLanguage'][Yii::app()->getLanguage()]) ? sprintf($this->translate("Add a new %s"), mb_strtolower($reponseName, 'UTF-8')) : $aAttributes['extraSurveyAddNewInLanguage'][Yii::app()->getLanguage()];

        $renderData=array(
            'aResponses'=>$aResponses,
            'surveyid'=>$surveyId,
            'extrasurveyqid' => $qid,
            'token' => $token,
            'newUrl'=>Yii::app()->getController()->createUrl('survey/index', $newUrlParam),
            'maxResponse' => $extraSurveyMaxresponse,
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
        return Yii::app()->controller->renderPartial("questionExtraSurvey.views.reponsesList", $renderData, 1);
    }

    /**
     * Set the answer and other parameters for the system
     * @param int $surveyId
     * @param string $srid
     * @param null|string $token
     * @param null|integer $qid for attributes
     * @return array[]
     */
    private function getPreviousResponse($surveyId, $srid, $token = null, $qid = null)
    {
        if(!$qid) {
            return array();
        }
        $aSelect=array(
            'id',
            'submitdate'
        );
        if ($token) {
            $aSelect[] = 'token';
        }
        $aAttributes = QuestionAttribute::model()->getQuestionAttributes($qid);
        $qCodeText=isset($aAttributes['extraSurveyQuestion']) ? trim($aAttributes['extraSurveyQuestion']) : "";
        $showId =trim($aAttributes['extraSurveyShowId']);
        $orderBy = isset($aAttributes['extraSurveyOrderBy']) ? trim($aAttributes['extraSurveyOrderBy']) : null;
        $qCodeSrid = trim($aAttributes['extraSurveyQuestionLink']);
        $relatedTokens = $aAttributes['extraSurveyTokenUsage'] == 'group';
        $aOtherFields = $this->getOtherField($qid);
        if (!$aAttributes['extraSurveyQuestionLinkUse']) {
            $qCodeSrid = null;
        }
        /* Find the question code */
        $oQuestionText=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeText));
        $qCodeText = null;
        if ($oQuestionText && in_array($oQuestionText->type, array("S","T","U","L","!","O","N","D","G","Y","*"))) {
            $qCodeText = "{$oQuestionText->sid}X{$oQuestionText->gid}X{$oQuestionText->qid}";
            $aSelect[] = Yii::app()->db->quoteColumnName($qCodeText);
        }

        $oCriteria = new CDbCriteria;
        $oCriteria->select = $aSelect;
        $oCriteria->condition = "";
        if ($qCodeText && !$showId) {
            $qQuotesCodeText = Yii::app()->db->quoteColumnName($qCodeText);
            $oCriteria->addCondition("$qQuotesCodeText IS NOT NULL AND $qQuotesCodeText != ''");
        }
        if ($token) {
            $tokens = array($token=>$token);
            if ($relatedTokens) {
                $tokens = $this->getTokensList($surveyId, $token);
            }
            $oCriteria->addInCondition("token", $tokens);
        }
        if ($qCodeSrid && $srid) {
            $oQuestionSrid=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$qCodeSrid));
            if ($oQuestionSrid && in_array($oQuestionSrid->type, array("T","S","N"))) {
                $qCodeSrid = "{$oQuestionSrid->sid}X{$oQuestionSrid->gid}X{$oQuestionSrid->qid}";
                $oCriteria->compare(Yii::app()->db->quoteColumnName($qCodeSrid), $srid);
            }
        }
        if (!empty($aOtherFields)) {
            foreach ($aOtherFields as $questionCode => $value) {
                $oQuestionOther=Question::model()->find("sid=:sid and title=:title and parent_qid=0", array(":sid"=>$surveyId,":title"=>$questionCode));
                if ($oQuestionOther && in_array($oQuestionOther->type, array("5","D","G","I","L","N","O","S","T","U","X","Y","!","*"))) {
                    $qCode = "{$oQuestionOther->sid}X{$oQuestionOther->gid}X{$oQuestionOther->qid}";
                    $oCriteria->compare(Yii::app()->db->quoteColumnName($qCode), $value);
                }
            }
        }
        $sFinalOrderBy = "";
        if (!empty($orderBy)) {
            $aOrdersBy = explode(",", $orderBy);
            $aOrderByFinal = array();
            foreach ($aOrdersBy as $sOrderBy) {
                $aOrderBy = explode(" ", trim($sOrderBy));
                $arrangement = "ASC";
                if (!empty($aOrderBy[1]) and strtoupper($aOrderBy[1]) == 'DESC') {
                    $arrangement = "DESC";
                }
                if (!empty($aOrderBy[0])) {
                    $orderColumn = null;
                    $availableColumns = SurveyDynamic::model($surveyId)->getAttributes();
                    if (array_key_exists($aOrderBy[0], $availableColumns)) {
                        $aOrderByFinal[] = Yii::app()->db->quoteColumnName($aOrderBy[0])." ".$arrangement;
                    } elseif (Yii::getPathOfAlias('getQuestionInformation')) {
                        $aEmToColumns = \getQuestionInformation\helpers\surveyCodeHelper::getAllQuestions($surveyId);
                        if (in_array($aOrderBy[0], $aEmToColumns)) {
                            $aValidColumns = array_keys($aEmToColumns, $aOrderBy[0]);
                            $aOrderByFinal[] = Yii::app()->db->quoteColumnName($aValidColumns[0])." ".$arrangement;
                        }
                    }
                }
            }
            $sFinalOrderBy = implode(",", $aOrderByFinal);
        }
        if (empty($sFinalOrderBy)) {
            $sFinalOrderBy = Yii::app()->db->quoteColumnName('id')." DESC";
            if (Survey::model()->findByPk($surveyId)->datestamp == "Y") {
                $sFinalOrderBy = Yii::app()->db->quoteColumnName('datestamp')." ASC";
            }
        }
        $oCriteria->order = $sFinalOrderBy;

        $oResponses=Response::model($surveyId)->findAll($oCriteria);
        $aResponses=array();
        if ($oResponses) {
            foreach ($oResponses as $oResponse) {
                $aResponses[$oResponse->id]=array(
                    'submitdate'=>$oResponse->submitdate,
                );
                /* Todo : check token related survey */
                $aResponses[$oResponse->id]['text'] = "";
                if ($showId) {
                    $aResponses[$oResponse->id]['text'] .= \CHtml::tag('span', array('class'=>'badge'), $oResponse->id);
                }
                if ($qCodeText) {
                    switch ($oQuestionText->type) {
                        case "!":
                        case "L":
                            $oAnswer=Answer::model()->find("qid=:qid and language=:language and code=:code", array(
                                ':qid' => $oQuestionText->qid,
                                ':language' => Yii::app()->getLanguage(),
                                ':code'=>$oResponse->getAttribute($qCodeText),
                            ));
                            if ($oAnswer) {
                                $aResponses[$oResponse->id]['text'] .= $oAnswer->answer;
                            } else {
                                $aResponses[$oResponse->id]['text'] .= $oResponse->$qCodeText; // Review for other
                            }
                            break;
                        default:
                            $aResponses[$oResponse->id]['text'] .= $oResponse->$qCodeText;
                    }
                }
                if (empty($aResponses[$oResponse->id]['text'])) {
                    $aResponses[$oResponse->id]['text'] .= $oResponse->id;
                }
            }
        }
        return $aResponses;
    }

    /**
     * Management of extra survey (before shown)
     */
    private function qesRegisterExtraSurveyScript()
    {
        Yii::app()->clientScript->addPackage('manageExtraSurvey', array(
            'basePath'    => 'questionExtraSurvey.assets',
            'js'          => array('extraSurvey.js'),
        ));
        Yii::app()->getClientScript()->registerPackage('manageExtraSurvey');
    }
    /**
     * Get Response, control access
     * @param integer survey id
     * @param integer response id
     * @param integer qid
     * @throw CHttpException
     * @return void|Response
    */
    private function getResponse($surveyid, $srid, $qid )
    {
        /* Validate attribute */
        $aAttributes = QuestionAttribute::model()->getQuestionAttributes($qid);
        if($aAttributes['extraSurvey'] != $surveyid) {
            throw new CHttpException(400);
        }
        $oResponse  = Response::model($surveyid)->findByPk($srid);
        if (!$oResponse) {
            throw new CHttpException(404, $this->translate("Invalid id"));
        }
        if (empty($oResponse->token)) {
            /* Must check that … */
            return $oResponse;
        }
        /* Must control token validity */
        $token = Yii::app()->getRequest()->getParam('token');
        $aTokens = $this->getTokensList($surveyid, $token);
        if ($oResponse->token == $token) {
            return $oResponse;
        }
        if ($aAttributes['extraSurveyTokenUsage'] == 'group' && in_array($oResponse->token, $aTokens)) {
            $oResponse->token=$token;
            $oResponse->save();
            return $oResponse;
        }
        $reponseName = mb_strtolower(gT("Response"), 'UTF-8');
        $aSessionExtraSurvey = Yii::app()->session["questionExtraSurvey"];
        if (isset($aSessionExtraSurvey[$surveyid])) {
            $aAttributes = QuestionAttribute::model()->getQuestionAttributes($aSessionExtraSurvey[$surveyid]);
            $reponseName = empty($aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()]) ? mb_strtolower(gT("Response"), 'UTF-8') : $aAttributes['extraSurveyNameInLanguage'][Yii::app()->getLanguage()];
        }
        throw new CHttpException(403, sprintf($this->translate("Invalid token to edit this %s."), $reponseName));
    }

    /**
     * Validate if same token exist, create if not.
     * @param Survey $extraSurvey
     * @param Survey $thisSurvey
     * @return boolean (roken is valid)
     */
    private function validateToken($extraSurvey, $thisSurvey, $basetoken = null)
    {
        if (!$this->surveyAccessWithToken($extraSurvey)) {
            return false;
        }
        if (!$basetoken) {
            $basetoken = isset($_SESSION['survey_'.$thisSurvey->sid]['token']) ? $_SESSION['survey_'.$thisSurvey->sid]['token'] : null;
        }
        if (!$basetoken) {
            $this->log(sprintf("Unable to find token value for %s.", $thisSurvey->sid), 'warning');
            return false;
        }
        /* Find if token exist in new survey */
        $oToken = Token::model($extraSurvey->sid)->find("token = :token", array(":token"=>$basetoken));
        if (empty($oToken)) {
            $oBaseToken = Token::model($thisSurvey->sid)->find("token = :token", array(":token"=>$basetoken));
            if (empty($oBaseToken)) {
                $this->log(sprintf("Unable to create token for %s, token for %s seems invalid.", $extraSurvey->sid, $thisSurvey->sid), 'error');
                return false;
            }
            $oToken = Token::create($extraSurvey->sid);
            $disableAttribute = array("tid","participant_id","emailstatus","blacklisted","sent","remindersent","remindercount","completed","usesleft");
            $updatableAttribute = array_filter(
                $oBaseToken->getAttributes(),
                function ($key) use ($disableAttribute) {
                    return !in_array($key, $disableAttribute);
                },
                ARRAY_FILTER_USE_KEY
            );
            $oToken->setAttributes($updatableAttribute, false);
            if ($oToken->save()) {
                $this->log(sprintf("Auto create token %s for %s.", $basetoken, $extraSurvey->sid), 'info');
                return true;
            } else {
                $this->log(sprintf("Unable to create auto create token %s for %s.", $basetoken, $extraSurvey->sid), 'error');
                $this->log(CVarDumper::dumpAsString($oToken->getErrors), 'info');
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
    private function surveyHasToken($iSurvey)
    {
        if (version_compare(Yii::app()->getConfig('versionnumber'), "3", ">=")) {
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
    private function getTokensList($surveyId, $token)
    {
        $tokensList = array($token=>$token);
        $oPluginResponseListAndManage = Plugin::model()->find("name = :name", array(":name"=>'responseListAndManage'));
        if (empty($oPluginResponseListAndManage) || !$oPluginResponseListAndManage->active) {
            return $tokensList;
        }
        if (!$this->surveyHasToken($surveyId)) {
            return $tokensList;
        }
        $oPluginResponseListAndManage = PluginSetting::model()->find(
            "plugin_id = :plugin_id AND model = :model AND model_id = :model_id AND ".Yii::app()->db->quoteColumnName('key')." = :setting",
            array(":plugin_id"=>$oPluginResponseListAndManage->id,':model'=>"Survey",':model_id'=>$surveyId,':setting'=>"tokenAttributeGroup")
        );
        if (empty($oPluginResponseListAndManage)) {
            return $tokensList;
        }
        $tokenAttributeGroup = trim(json_decode($oPluginResponseListAndManage->value));
        if (empty($tokenAttributeGroup)) {
            return $tokensList;
        }
        if (!is_string($tokenAttributeGroup)) {
            return $tokensList;
        }
        $oTokenGroup = Token::model($surveyId)->find("token = :token", array(":token"=>$token));
        $tokenGroup = (isset($oTokenGroup->$tokenAttributeGroup) && trim($oTokenGroup->$tokenAttributeGroup)!='') ? $oTokenGroup->$tokenAttributeGroup : null;
        if (empty($tokenGroup)) {
            return $tokensList;
        }
        $oTokenGroup = Token::model($surveyId)->findAll($tokenAttributeGroup."= :group", array(":group"=>$tokenGroup));
        return CHtml::listData($oTokenGroup, 'token', 'token');
    }

    /**
    * Did this survey have token with reload available
    * @var \Survey
    * @return boolean
    */
    private function surveyAccessWithToken($oSurvey)
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
    private function setOtherField($qid, $otherField, $surveyId)
    {
        $aOtherFieldsLines = preg_split('/\r\n|\r|\n/', $otherField, -1, PREG_SPLIT_NO_EMPTY);
        $aOtherFields = array();
        foreach ($aOtherFieldsLines as $otherFieldLine) {
            if (!strpos($otherFieldLine, ":")) {
                continue; // Invalid line
            }
            $key = substr($otherFieldLine, 0, strpos($otherFieldLine, ":"));
            $value = substr($otherFieldLine, strpos($otherFieldLine, ":")+1);
            $value = self::qesEMProcessString($value);
            $aOtherFields[$key] = $value;
        }
        Yii::app()->getSession()->add("questionExtraOtherField{$qid}", $aOtherFields);
    }

    /**
     * Set the other field for current qid
     * @param integer $qid
     * @return null|srting[]
     */
    private function getOtherField($qid)
    {
        return Yii::app()->getSession()->get("questionExtraOtherField{$qid}", null);
    }

    /**
     * Reset current survey if EM sid updated
     * Needed for full index
     */
    private function resetEMIfNeeded($surveyId)
    {
        $questionExtraSurveyReset = Yii::app()->getRequest()->getPost('questionExtraSurveyReset');
        if (empty($questionExtraSurveyReset)) {
            return;
        }
        if (empty($questionExtraSurveyReset['reloaded'])) {
            // Can log it
            return;
        }
        if ($questionExtraSurveyReset['surveyId'] != $surveyId) {
            // throw Exceptioon ?
            return;
        }
        if (!class_exists('\\reloadAnyResponse\\helpers\\reloadResponse')) {
            if (Yii::app()->getConfig('debug') > 1) {
                throw new Exception("You must have reloadAnyResponse version 1.2.0 minimum");
            }
            return;
        }
        $step = $questionExtraSurveyReset['step'];
        $srid = $_SESSION['survey_'.$surveyId]['srid'];
        if (empty($srid) || empty($step)) {
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
    private function validateQuestionExtraSurvey($qid, $extraSid)
    {
        $title=Survey::model()->findByPk($extraSid)->getLocalizedTitle(); // @todo : get default lang title
        /* search if it's a related survey */
        $oAttributeExtraSurvey=QuestionAttribute::model()->find('attribute=:attribute AND qid=:qid', array(
            ':attribute' => 'extraSurvey',
            ':qid' => $qid,
        ));
        /* Validate if usage of extraSurvey is OK here with current qid */
        if ($oAttributeExtraSurvey && ($oAttributeExtraSurvey->value == $extraSid || $oAttributeExtraSurvey->value == $title)) {
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
    private static function qesEMProcessString($string, $static = true)
    {
        $replacementFields=array();
        if (intval(Yii::app()->getConfig('versionnumber'))<3) {
            return \LimeExpressionManager::ProcessString($string, null, $replacementFields, false, 3, 0, false, false, $static);
        }
        if (version_compare(Yii::app()->getConfig('versionnumber'), "3.6.2", "<")) {
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
    private function translate($string, $sEscapeMode = 'unescaped', $sLanguage = null)
    {
        if (is_callable(array($this, 'gT'))) {
            return $this->gT($string, $sEscapeMode, $sLanguage);
        }
        return $string;
    }

    /**
     * allow to fix some DB value when update
     * - 0 to 1 : remove extraSurveyResponseListAndManage usage to extraSurveyTokenUsage : if null, set it to 'group'
     * @return void
     * @throw Exception
     */
    private function fixDbByVersion()
    {
        $currentDbVersion = $this->get("dbVersion",null,null,0);
        if($currentDbVersion >= self::$DBversion) {
            return;
        }
        if($currentDbVersion < 1) {
            /* Get all qid with extraSurvey set */
            $oQuestionsAttributeExtraSurvey = QuestionAttribute::model()->findAll("attribute = :attribute", array(":attribute" => "extraSurvey"));
            foreach ($oQuestionsAttributeExtraSurvey as $oQuestionExtraSurvey) {
                $oAttributeResponseListAndManage = QuestionAttribute::model()->find(
                    "qid = :qid AND attribute = :attribute",
                    array(
                        ":qid" => $oQuestionExtraSurvey->qid,
                        ":attribute"=>"extraSurveyResponseListAndManage",
                    )
                );
                if (!empty($oAttributeResponseListAndManage)) {
                    $oAttributeResponseListAndManage->delete();
                }
                if (empty($oAttributeResponseListAndManage)) {
                    $oAttributeResponseTokenUsage = QuestionAttribute::model()->find(
                        "qid = :qid AND attribute = :attribute",
                        array(
                            ":qid" => $oQuestionExtraSurvey->qid,
                            ":attribute"=>"extraSurveyTokenUsage",
                        )
                    );
                    if(empty($oAttributeResponseTokenUsage)) {
                        $oAttributeResponseTokenUsage = new QuestionAttribute;
                        $oAttributeResponseTokenUsage->qid = $oQuestionExtraSurvey->qid;
                        $oAttributeResponseTokenUsage->attribute = 'extraSurveyTokenUsage';
                        $oAttributeResponseTokenUsage->value = 'group';
                        $oAttributeResponseTokenUsage->save();
                    }
                }
            }
            $this->set("dbVersion",1);
        }
        $this->set("dbVersion",self::$DBversion);
    }

    /**
    * @inheritdoc adding string, by default current event
    * @param string $message
    * @param string $level From CLogger, defaults to CLogger::LEVEL_TRACE
    * @param string $logDetail
    */
    public function log($message, $level = \CLogger::LEVEL_TRACE, $logDetail = null)
    {
        if (!$logDetail && $this->getEvent()) {
            $logDetail = $this->getEvent()->getEventName();
        } // What to put if no event ?
        if ($logDetail) {
            $logDetail = ".".$logDetail;
        }
        $category = get_class($this);
        \Yii::log($message, $level, 'plugin.'.$category.$logDetail);
    }
}
