$(function () {
  $("#type").change(function () {
    if ($(this).val() == "radio") {
      $("#options").show();
    } else {
      $("#options").hide();
      $("#optionvalues").val("");
    }
  });
});

$( document ).ready(function() {
  if ($("#type").val() == "radio") {
    $("#options").show();
  }

  $('.opt > .rsp-option').on('click', function() {
    var value = $(this).val();
    var rsp = '.' + $(this).attr('data-response');
    $('.opt').find(rsp).val(value);
  //  alert(value);
  });
});