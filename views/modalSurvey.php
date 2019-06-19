<!-- Bootstrap Modal Survey -->
<div id="modal-questionExtraSurvey" class="modal modal-questionExtraSurvey"  tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <div class="h4 modal-title"></div>
      </div>
      <div class="modal-body">
        <iframe name="frame-questionExtraSurvey" id="extra-survey-iframe"></iframe></div>
      <div class="modal-footer">
        <?php
          echo CHtml::htmlButton($language['Close'],array('type'=>'button','class'=>"btn btn-warning btn-close",'data-dismiss'=>"modal"));
          if(!empty($language['Delete'])) {
            echo CHtml::htmlButton($language['Delete'],array('type'=>'button','class'=>"btn btn-danger btn-delete",'data-action'=>"delete",'disabled'=>false));
          }
          if(!empty($language['Previous'])) {
            echo CHtml::htmlButton($language['Previous'],array('type'=>'button','class'=>"btn btn-default btn-moveprevious",'data-action'=>"moveprev",'disabled'=>true));
          }
          if(!empty($language['Save'])) {
            echo CHtml::htmlButton($language['Save'],array('type'=>'button','class'=>"btn btn-info btn-saveall",'data-action'=>"saveall",'disabled'=>true));
          }
          if(!empty($language['Next'])) {
            echo CHtml::htmlButton($language['Next'],array('type'=>'button','class'=>"btn btn-primary btn-movenext",'data-action'=>"movenext",'disabled'=>true));
          }
          if(!empty($language['Submit'])) {
            echo CHtml::htmlButton($language['Submit'],array('type'=>'button','class'=>"btn btn-success btn-movesubmit",'data-action'=>"movesubmit",'disabled'=>true));
          }
        ?>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
