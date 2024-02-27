function updateClock (inTimestamp, inAllBreakTime)
{
  var currentTime = new Date ( );
  var currentTimeUnix = currentTime.getTime();
  var timeDifference = new Date(currentTimeUnix - (inTimestamp*1000) - (inAllBreakTime*1000));
  
  
  var diffHours = timeDifference.getHours ( ) - 1;
  var diffMinutes = timeDifference.getMinutes ( );
  var diffSeconds = timeDifference.getSeconds ( );

  // Pad the minutes and seconds with leading zeros, if required
  diffMinutes = ( diffMinutes < 10 ? "0" : "" ) + diffMinutes;
  diffSeconds = ( diffSeconds < 10 ? "0" : "" ) + diffSeconds;
  
  // Compose the string for display
  var diffTimeString = diffHours + ":" + diffMinutes + ":" + diffSeconds;     

  // Update the time display
  return diffTimeString;
}
