<?php
/** @version 2.2.0 **/
?><ul class="list-group">
<?php
foreach($aResponses as $id => $aResponse) {
  $class='list-group-item';
  $aAttribute=array(
    'class'=>$class,
  );
  $name=$aResponse['text'];
  $name='<i class="fa fa-pencil-square" aria-hidden="true"></i> '.$name;
  $aLinkAttribute=array(
    'target'=>'frame-questionExtraSurvey',
    'data-srid'=>$id,
  );
  $extraContent="";
  if(!empty($aResponse['submitdate'])) {
    $aLinkAttribute['class']='text-success';
    $extraContent='<i class="fa fa-check-square-o text-success pull-right" aria-hidden="true"></i>';
  } else {
    //~ $aLinkAttribute['class']='text-warning';
    $extraContent='<i class="fa fa-square-o text-danger pull-right" aria-hidden="true"></i>';
  }
  $content=$extraContent.CHtml::link($name,array("survey/index",'sid'=>$surveyid,'extrasurveyqid'=>$extrasurveyqid,'token'=>$token,'extrasurveysrid'=>$id,'newtest'=>'Y'),$aLinkAttribute);
  echo CHtml::tag('li',$aAttribute,$content);
}
?>
<?php
  if(empty($maxResponse) || $maxResponse > count($aResponses)) {
    $class='list-group-item ';
    $aAttribute=array(
      'class'=>$class,
    );
    $name='<i class="fa fa-plus-circle" aria-hidden="true"></i> '.$language['createNewreponse'];
    $content=CHtml::link($name,$newUrl,array('target'=>'frame-questionExtraSurvey'));
    echo CHtml::tag('li',$aAttribute,$content);
  }
?>
</ul>
<?php if($inputName) {?>
  <?php
  $value = "";
  switch ($fillAnswerWith) {
    case 'listsubmitted':
      $aValidResponse = array_filter($aResponses, function ($aResponse) {
        return (!empty($aResponse['submitdate']));
      });
      $value = implode(",",array_keys($aValidResponse));
      break;
    case 'listall':
      $value = implode(",",array_keys($aResponses));
      break;
    case 'number':
    default:
      $value = count(array_filter($aResponses, function ($aResponse) {
        return (!empty($aResponse['submitdate']));
      }));
      $notsubmitted = count($aResponses) - $value;
      if($notsubmitted) {
        $value = floatval($value.".".$notsubmitted);
      }
      if(!$value) {
        // Move "0" to "" : mandatory system can still be used.
        $value = "";
      }
      break;
  }
  echo \CHtml::tag("div",array(
    'class' => 'answer-item text-item hidden',
    'aria-hidden' => 'true',
    'title' => '',
    ),
    \CHtml::textField($inputName,$value,array(
      'class'=>'form-control',
      'id' => 'answer'.$inputName,
    ))
  );
  ?>
  <script>
    $("#answer<?php echo $inputName?>").trigger("keyup");
  </script>
<?php }?>
<?php foreach($questionExtraSurveyReset as $key=>$value) {
    echo \CHtml::hiddenField("questionExtraSurveyReset[{$key}]",$value,array('aria-hidden'=>true));
}
?>
