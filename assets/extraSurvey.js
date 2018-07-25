$(document).on('ready pjax:scriptcomplete',function(){
    console.log(window.location != window.parent.location);
    if(window.location != window.parent.location) {
        window.parent.$(window.parent.document).trigger("extrasurveyframe:on");
    }
});
/* pjax reload ? */
window.onbeforeunload = function() {
    if(window.location != window.parent.location) {
        window.parent.$(window.parent.document).trigger("extrasurveyframe:off");
    }
};
function autoclose() {
    if(window.location != window.parent.location) {
        window.parent.$(window.parent.document).trigger("extrasurveyframe:autoclose");
    }
}
