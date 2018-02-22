var $ = jQuery.noConflict();

$(function(){

  var monthNames = ["January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
  ];
  var current = new Date();
  var monthData = {};
  for (var x = 0; x <= 11; x++){
    var monthIndex = current.getMonth();
    var currentMonth = current.getMonth();
    var currentYear = current.getFullYear();
    var numberOfDays = new Date(currentYear, currentMonth+1, 0).getDate();

    if(!monthData.month)
      monthData.month = [ monthNames[currentMonth] ];
    else
      monthData.month.push(monthNames[currentMonth]);

    if(!monthData.month_num)
      monthData.month_num = [currentMonth + 1];
    else
      monthData.month_num.push(currentMonth + 1);

    if(!monthData.year)
      monthData.year = [ currentYear ];
    else
      monthData.year.push(currentYear);

    if(!monthData.days)
      monthData.days = [ numberOfDays ];
    else
      monthData.days.push(numberOfDays);

    current = (monthIndex === 11)
      ? new Date(current.getFullYear() + 1, 0, 1)
      : new Date(current.getFullYear(), current.getMonth() + 1, 1);
  }


  var $edButtonHTML = $("#edButtonHTML");
  var $eventMonth = $("select#event_month");
  var $linkUrl = $("input.link-url");
  var $endDisable = $("input#end_disable");
  var $startDisable = $("input#start_disable");

  $edButtonHTML.remove();
  $eventMonth.change(function(){
    var selectIndex = $(this).find("option:selected").index();
    var $yearLabel = $("span#year-label");
    var $year = $("span#year");
    var $day = $("select#day");

    $yearLabel.html( monthData.year[ selectIndex ] );
    $year.val( monthData.year[ selectIndex ] );
    $day.find("option").remove();
    for( var y = 1; y <= monthData.days[ selectIndex ]; y++ ){
      $day.append('<option value="'+ y +'">'+ y +'</option>');
    }

  });

  $linkUrl.change(function(){
    var linkValue = $(this).val();
    if( linkValue.slice(0,7) !== "http://" ){
      $(this).addClass("texterror");
    }else{
      $(this).removeClass("texterror");
    }
  });

  $endDisable.change(function(){
    var $divider = $("select#end_hour, select#end_minute, select#end_convention, span#end_divider")
    if( $(this).is(":checked") ){
      $divider.fadeTo("slow", 0.0);
    }else{
      $divider.fadeTo("slow", 1.0);
    }
  });

  $startDisable.change(function(){
    var $divider = $("select#start_hour, select#start_minute, select#start_convention, span#start_divider");
    if( $(this).is(":checked") ){
      $divider.fadeTo("slow", 0.0);
    }else{
      $divider.fadeTo("slow", 1.0);
    }
  });

});
