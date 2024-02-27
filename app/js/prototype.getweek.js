/**
 * Returns the week number for this date.  dowOffset is the day of week the week
 * "starts" on for your locale - it can be from 0 to 6. If dowOffset is 1 (Monday),
 * the week returned is the ISO 8601 week number.
 * @param int dowOffset
 * @return int
 */
/** 
 * Get the ISO week date week number 
 */  
Date.prototype.getWeek = function () {  
    // Create a copy of this date object  
    var target  = new Date(this.valueOf());  
  
    // ISO week date weeks start on monday  
    // so correct the day number  
    var dayNr   = (this.getDay() + 6) % 7;  
  
    // ISO 8601 states that week 1 is the week  
    // with the first thursday of that year.  
    // Set the target date to the thursday in the target week  
    target.setDate(target.getDate() - dayNr + 3);  
  
    // Store the millisecond value of the target date  
    var firstThursday = target.valueOf();  
  
    // Set the target to the first thursday of the year  
    // First set the target to january first  
    target.setMonth(0, 1);  
    // Not a thursday? Correct the date to the next thursday  
    if (target.getDay() != 4) {  
        target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);  
    }  
  
    // The weeknumber is the number of weeks between the   
    // first thursday of the year and the thursday in the target week  
    return 1 + Math.ceil((firstThursday - target) / 604800000); // 604800000 = 7 * 24 * 3600 * 1000  
}  

/**
* Get the ISO week date year number
*/
Date.prototype.getWeekYear = function () 
{
	// Create a new date object for the thursday of this week
	var target	= new Date(this.valueOf());
	target.setDate(target.getDate() - ((this.getDay() + 6) % 7) + 3);
	
	return target.getFullYear();
}

var w2date = function(year, wn){
    var j10 = new Date( year,0,10,12,0,0),
        j4 = new Date( year,0,4,12,0,0),
        mon1 = j4.getTime() - j10.getDay() * 86400000;
    return new Date(mon1 + ((wn - 1)  * 7) * 86400000);
};