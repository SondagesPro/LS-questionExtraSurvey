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
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").removeClass("hidden");
    }else{
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").addClass("hidden");
    }
  });
  if(typeof modalparams.language['Are you sure to remove this response.'] === 'string' ) {
    $("#label-questionExtraSurvey-confirm-clearall").text(modalparams.language['Are you sure to remove this response.']);
  }
  $('#modal-questionExtraSurvey').find('.modal-title').text($(this).text());
  $("#modal-questionExtraSurvey iframe").html("").attr('src',$(this).attr('href'));
  $("#modal-questionExtraSurvey").modal('show');
});

$(document).on("shown.bs.modal","#modal-questionExtraSurvey",function(e) {
  if(window.location != window.parent.location) {
    window.parent.$(window.parent.document).trigger("modaliniframe:on");
  }
  updateHeightModalExtraSurvey("#modal-questionExtraSurvey");
});
$(document).on("hide.bs.modal",function(e) {
  if(window.location != window.parent.location) {
    window.parent.$(window.parent.document).trigger("modaliniframe:off");
  }
});
$(document).on("hide.bs.modal",'#modal-questionExtraSurvey',function(e) {
  $("[data-update-questionextrasurvey]").each(function(){
    updateList($(this));
  });
  $("#modal-questionExtraSurvey iframe").html("").attr("src", "");
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

function updateHeightModalExtraSurvey(modal) {
    var navbarFixed=0;
    if($(".navbar-fixed-top").filter(":visible").length) {
      navbarFixed=$(".navbar-fixed-top").filter(":visible").outerHeight();
    }
    if(isNaN(navbarFixed)) {
      navbarFixed=0;
    }
    var modalHeader=$(modal).find(".modal-header").outerHeight();
    var modalFooter=$(modal).find(".modal-footer").outerHeight();
    var finalHeight=Math.max(400,$(window).height()-(navbarFixed+modalHeader+modalFooter+28));// Not less than 150px
    $(modal).find(".modal-dialog").css("margin-top",navbarFixed+4);
    $(modal).find(".modal-body").css("height",finalHeight);
    $(modal).find(".modal-body iframe").css("height",finalHeight);
}
$(document).on('extrasurveyframe:on',function(event,data) {
  $("#modal-questionExtraSurvey .modal-footer button[data-action]").each(function(){
    $(this).prop('disabled',$("#extra-survey-iframe").contents().find("form#limesurvey button:submit[value='"+$(this).data('action')+"']").length < 1);
    if($("#extra-survey-iframe").contents().find(".completed-text").length) {
        $("#modal-questionExtraSurvey").modal('hide');
    }
    updateHeightModalExtraSurvey("#modal-questionExtraSurvey");
    // todo : add it in option $("#extra-survey-iframe").contents().find(".navigator").addClass("hidden");
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
  if($(this).data('action') == 'clearall') {
    $("#modal-confirm-clearall-extrasurvey").show();
    $("#modal-confirm-clearall-extrasurvey .btn-confirm").on('click',function(){
      $("#modal-questionExtraSurvey iframe").contents().find("input[name='confirm-clearall']").prop("checked",true);
      $("#modal-questionExtraSurvey iframe").contents().find("#limesurvey").append("<input type='hidden' name='extraSurvey' value='1'>");
      $("#modal-questionExtraSurvey iframe").contents().find("button[value='clearall']").removeAttr('data-confirmedby').click();
    });
    $("#modal-confirm-clearall-extrasurvey [data-dismiss]").on('click',function(){
      // LimeSurve 3.13 have an issue with dialog box not closed …
      $("#modal-confirm-clearall-extrasurvey").hide();
    });
    return;
  }
    $("#extra-survey-iframe").contents().find("form#limesurvey button:submit[value='"+$(this).data('action')+"']").last().click();
});
