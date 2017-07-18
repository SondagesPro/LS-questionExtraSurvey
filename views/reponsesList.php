<ul class="list-unstyled">
<?php
foreach($aResponses as $id => $aResponse) {
  $class='';
  $aAttribute=array(
    'class'=>$class,
  );
  $name=$aResponse['text'];
  $name='<i class="fa fa-pencil-square" aria-hidden="true"></i> '.$name;
  $aLinkAttribute=array(
    'target'=>'frame-questionExtraSurvey',
  );
  $extraContent="";
  if(!empty($aResponse['submitdate'])) {
    $aLinkAttribute['class']='text-success';
    $extraContent='<i class="fa fa-check-square-o text-success pull-right" aria-hidden="true"></i>';
  } else {
    //~ $aLinkAttribute['class']='text-warning';
    $extraContent='<i class="fa fa-square-o text-danger pull-right" aria-hidden="true"></i>';
  }
  $content=$extraContent.CHtml::link($name,array("survey/index",'sid'=>$surveyid,'extrasurveyqid'=>$extrasurveyqid,'token'=>$token,'srid'=>$id),$aLinkAttribute);
  echo CHtml::tag('li',$aAttribute,$content);
}
?>
<?php
  $class='';
  $aAttribute=array(
    'class'=>$class,
  );
  $name='<i class="fa fa-plus-circle" aria-hidden="true"></i> Add a new instrument';
  $content=CHtml::link($name,$newUrl,array('target'=>'frame-questionExtraSurvey'));
  echo CHtml::tag('li',$aAttribute,$content);
?>
</ul>
