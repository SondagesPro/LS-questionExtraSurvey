/**
 * @file questionExtraSurvey javascript system
 * @author Denis Chenu
 * @version 1.1.0
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 */
$(document).on('click','[target="frame-questionExtraSurvey"]',function(event) {
  event.preventDefault();
  if(!$(this).closest("[data-modalparams-questionextrasurvey]").length) {
    return;
  }
  // Set the buttons
  var modalbuttons = { "delete":false,"saveall":false,"moveprevious":false,"movenext":true,"movesubmit":true,"close":true };
  var modalparams = $(this).closest("[data-modalparams-questionextrasurvey]").data('modalparams-questionextrasurvey');
  $.extend(modalbuttons, modalparams.buttons);
  $.each(modalbuttons,function( key, value ) {
    if(value){
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").removeClass("hidden");
    }else{
      $("#modal-questionExtraSurvey button[data-action='"+key+"']").addClass("hidden");
    }
  });
  if(!modalbuttons.close) {
    $("#modal-questionExtraSurvey button[data-dismiss").addClass("hidden");
  } else {
    $("#modal-questionExtraSurvey button[data-dismiss").removeClass("hidden");
  }
  if(typeof modalparams.language['Are you sure to remove this response.'] === 'string' ) {
    $("#label-questionExtraSurvey-confirm-clearall").text(modalparams.language['Are you sure to remove this response.']);
  }
  // Set the data for close event
  $("#modal-questionExtraSurvey").data("checkwhenclose-questionextrasurvey",false);
  if($(this).closest("[data-closecheck-questionextrasurvey]").length) {
    var checkUrl = $(this).closest("[data-closecheck-questionextrasurvey]").data('closecheck-questionextrasurvey');
    $("#modal-questionExtraSurvey").data("checkwhenclose-questionextrasurvey",checkUrl);
  }
  // OK, open it
  $('#modal-questionExtraSurvey').find('.modal-title').text($(this).text());
  $("#modal-questionExtraSurvey iframe").html("").attr('src',$(this).attr('href'));
  $("#modal-questionExtraSurvey").data("questionExtraSurveyQid",modalparams.qid);
  $("#modal-questionExtraSurvey").modal({
    show: true,
    keyboard : modalbuttons.close,
    backdrop : (modalbuttons.close ? true : 'static')
  });
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
  var sridElement = $("#modal-questionExtraSurvey iframe").contents().find("#sridQuestionExtraSurvey");
  if($(sridElement).length == 1) {
      var srid = $(sridElement).val().trim();
      var defer = $.Deferred();
      checkIsEmpty($("#modal-questionExtraSurvey"),srid);
  } else {
    updateLists();
  }
  $("#modal-questionExtraSurvey iframe").html("").attr("src", "");
});

function checkIsEmpty(element, srid) {
  if($(element).data('checkwhenclose-questionextrasurvey')) {
    $.when($.ajax({
        url: $(element).data('checkwhenclose-questionextrasurvey'),
        data: {
          'srid' : srid
        },
        type: "GET", // Todo : set to POST (add CRSF)
        dataType: "text",
        success: function (data) {
          //
        },
        error: function (xhr, status) {
            //TODO
        }
    })).done(function( ) {
      updateLists();
    });
  }
}
function updateLists() {
  $("[data-update-questionextrasurvey]").each(function(){
    var element = $(this);
    $.ajax({
        url: $(this).data('update-questionextrasurvey'),
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
  });
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
    // todo : add it in option $("#extra-survey-iframe").contents().find(".navigator").addClass("hidden");
  });
  $("#modal-questionExtraSurvey .modal-footer button[data-action='delete']").prop('disabled',false);
  updateHeightModalExtraSurvey("#modal-questionExtraSurvey");
});
$(document).on('extrasurveyframe:off',function(event,data) {
  $("#modal-questionExtraSurvey .modal-footer button[data-action]").each(function(){
    $(this).prop('disabled',true);
  });
});
$(document).on('extrasurveyframe:autoclose',function(event,data) {
  $("#modal-questionExtraSurvey").modal('hide');
});
$(document).on('click',"#modal-questionExtraSurvey button[data-action]",function(e) {
  if($(this).data('action')=="delete") {
    return;
  }
  var questionExtraSurveyQid = $("#modal-questionExtraSurvey").data("questionExtraSurveyQid");
  $("#extra-survey-iframe").contents().find("form#limesurvey").append("<input type='hidden' name='questionExtraSurveyQid' value='"+questionExtraSurveyQid+"'>");
  $("#extra-survey-iframe").contents().find("form#limesurvey button:submit[value='"+$(this).data('action')+"']").last().click();
});
$(document).on('click',"#modal-questionExtraSurvey button[data-action='delete']:not('disabled')",function(e) {
  $("#modal-confirm-clearall-extrasurvey").show();
  $("#modal-confirm-clearall-extrasurvey .btn-confirm").on('click',function(){
    $("#modal-questionExtraSurvey iframe").contents().find("#limesurvey").append("<input type='hidden' name='move' value='delete'>");
    $("#modal-questionExtraSurvey iframe").contents().find("#limesurvey").submit();
  });
  $("#modal-confirm-clearall-extrasurvey [data-dismiss]").on('click',function(){
    // LimeSurve 3.13 have an issue with dialog box not closed …
    $("#modal-confirm-clearall-extrasurvey").hide();
  });
  return;
});
