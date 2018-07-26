/**
 * @file questionExtraSurvey javascript system
 * @author Denis Chenu
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 */
$(document).on('click','[target="frame-questionExtraSurvey"]',function(event) {
  event.preventDefault();
  var modalbuttons = { "clearall":false,"saveall":false,"moveprevious":false,"movenext":true,"movesubmit":true };
  var modalparams = $(this).closest("[data-modalparams-questionextrasurvey]").data('modalparams-questionextrasurvey');
  $.extend(modalbuttons, modalparams.buttons);
  $.each(modalbuttons,function( key, value ) {
    if(value){
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").show();
    }else{
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").hide();
    }
  });
  if(modalparams.language.confirmDelete) {
    $("#label-questionExtraSurvey-confirm-clearall").text(modalparams.language.confirmDelete);
  }
  $('#modal-questionExtraSurvey').find('.modal-title').text($(this).text());
  $("#modal-questionExtraSurvey iframe").attr({'src':$(this).attr('href')});
  $("#modal-questionExtraSurvey").modal('show');
});
$(document).on("shown.bs.modal",function(e) {
  if(window.location != window.parent.location) {
    window.parent.$(window.parent.document).trigger("modaliniframe:on");
  }
  if(e.target && $(e.target).attr('id')=='modal-questionExtraSurvey' ) {
     updateHeightModalbody("#modal-questionExtraSurvey");
  }
});
$(document).on("hide.bs.modal",function(e) {
  if(window.location != window.parent.location) {
    window.parent.$(window.parent.document).trigger("modaliniframe:off");
  }
  if(e.target) {
    if($(e.target).attr('id')=='modal-questionExtraSurvey') {
      $("[data-update-questionextrasurvey]").each(function(){
        updateList($(this));
      });
      $("#modal-questionExtraSurvey iframe").attr({'src':""});
    }
  }
});
$(document).on("click","#modal-questionExtraSurvey button[data-action]",function(event) {
  event.preventDefault();
  $("#modal-questionExtraSurvey iframe").contents().find("button[name='"+$(this).data('action')+"']").click();
});

function updateList(element) {
  if($(element).data('update-questionextrasurvey')) {
    $.ajax({
        url: $(element).data('update-questionextrasurvey'),
        data: { },
        type: "GET",
        dataType: "html",
        success: function (data) {
          $(element).html(data);
        },
        error: function (xhr, status) {
            //TODO
        }
    });
  }
}

function updateHeightModalbody(modal) {
    var navbarFixed=0;
    if((".navbar-fixed-top").length) {
      navbarFixed=$(".navbar-fixed-top").outerHeight();
    }
    var modalHeader=$(modal).find(".modal-header").outerHeight();
    var modalFooter=$(modal).find(".modal-footer").outerHeight();
    var finalHeight=Math.max(400,$(window).height()-(navbarFixed+modalHeader+modalFooter+28));// Not less than 150px
    $(modal).find(".modal-lg").css("margin-top",navbarFixed+4);
    $(modal).find(".modal-body").css("height",finalHeight);
    $(modal).find(".modal-body iframe").css("height",finalHeight);
}
$(document).on('extrasurveyframe:on',function(event,data) {
  $("#modal-questionExtraSurvey .modal-footer button[data-action]").each(function(){
    $(this).prop('disabled',$("#extra-survey-iframe").contents().find("form#limesurvey button:submit[value='"+$(this).data('action')+"']").length < 1);
    if($("#extra-survey-iframe").contents().find(".completed-text").length) {
        $("#modal-questionExtraSurvey").modal('hide');
    }
  });
});
$(document).on('extrasurveyframe:off',function(event,data) {
  $("#modal-questionExtraSurvey .modal-footer button[data-action]").each(function(){
    $(this).prop('disabled',true);
  });
});
$(document).on('extrasurveyframe:autoclose',function(event,data) {
  $("#modal-questionExtraSurvey").modal('hide');
});
$(document).on('click',"#modal-questionExtraSurvey button[data-action]:not('disabled')",function(e) {
    $("#extra-survey-iframe").contents().find("form#limesurvey button:submit[value='"+$(this).data('action')+"']").last().click();
});
