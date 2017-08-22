/**
 * @file questionExtraSurvey javascript system
 * @author Denis Chenu
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 */
$(document).on('click','[target="frame-questionExtraSurvey"]',function(event) {
  event.preventDefault();
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
$(document).on("click","#modal-questionExtraSurvey button[data-clearall]",function(event) {
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
            //alert("Sorry, there was a problem!");
        },
        complete: function (xhr, status) {
            //$('#showresults').slideDown('slow')
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

function surveySubmitted() {
  $('#modal-questionExtraSurvey').modal('hide');
}
function surveyLoaded() {
  if($("#modal-questionExtraSurvey iframe").contents().find("button[name='movenext']").length) {
    $('#modal-questionExtraSurvey .btn-info').removeClass("hidden");
    $('#modal-questionExtraSurvey .btn-success').addClass("hidden");
  } else {
    $('#modal-questionExtraSurvey .btn-success').removeClass("hidden");
    $('#modal-questionExtraSurvey .btn-info').addClass("hidden");
  }
}
$(document).on('click','[data-clearall]',function(event) {
    $("#modal-confirm-clearall").modal('show');
    $("#modal-confirm-clearall .btn-confirm").on('click',function(){
      $("#modal-questionExtraSurvey iframe").contents().find("input[name='confirm-clearall']").prop("checked",true);
      $("#modal-questionExtraSurvey iframe").contents().find("#limesurvey").append("<input type='hidden' name='extraSurvey' value='1'>");
      $("#modal-questionExtraSurvey iframe").contents().find("button[name='clearall']").removeAttr('data-confirmedby').click();
    });
  
});
/* helper for disable enable close */
function disableClose() {
  $('#modal-questionExtraSurvey .btn-default').addClass("hidden");
}
function enableClose() {
  $('#modal-questionExtraSurvey .btn-default').removeClass("hidden");
}
