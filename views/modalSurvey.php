<!-- Bootstrap Modal Survey -->
<div id="modal-questionExtraSurvey" class="modal"  tabindex="-1" role="dialog" data-backdrop="static" data-keyboard=0>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> -->
        <div class="h4 modal-title"></div>
      </div>
      <div class="modal-body">
        <iframe name="frame-questionExtraSurvey" class="extra-survey"></iframe></div>
      <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close without saving</button>
          <button type="button" class="btn btn-danger btn-survey" data-clearall="clearall">Delete</button>
          <button type="button" class="btn btn-info btn-survey hidden" data-action="movenext">Next</button>
          <button type="button" class="btn btn-success btn-survey" data-action="movesubmit">Save</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
