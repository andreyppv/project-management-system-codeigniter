 
var timerActive = false;

var timerHours = 0; /*Default to current task hours*/
var timerMinutes = 0; /*Default to current task minutes*/
var timerSeconds = 0; /*Default to current task seconds*/
var totalSeconds = 0;

var timer;
var reportTimer;

var timerActionBtn = "#stattimer"; /*Default*/
var timerActionPauseBtn = "#pausetimer"; /*Default*/
var timerActionCompletedBtn = "#completedtimer"; /*Default*/

var timerReportDiv = "#my-project-time"; /*Default*/
var clientReportTimerInterval = 1000; /*Default - Every second*/
var reportTimerInterval = 5000; /*Default - Every 5 seconds*/


/*USER SETTINGS*/
var task = "NULL";
var project = "NULL";
var user = "NULL";
/*End of USER SETTINGS*/

function convertTimer(refresh){
	var secondToAdd = clientReportTimerInterval;

	if(!refresh){ timerSeconds += 1; /*Add second*/ }

	if (timerSeconds >= 60){
		timerMinutes += 1;
		timerSeconds = 0;
	}

	if (timerMinutes >= 60){
		timerHours += 1;
		timerMinutes = 0;
	}

	if ((timerSeconds + (timerMinutes * 60) + ((timerHours * 60) * 60)) > 0){
		totalSeconds = (timerSeconds + (timerMinutes * 60) + ((timerHours * 60) * 60));
	}

	console.log(timerHours + ":" + timerMinutes + ":" + timerSeconds);
	$(timerReportDiv).text( timerHours + ":" + timerMinutes + ":" + timerSeconds );
}

function edit(seconds, minutes, hours){
	timerSeconds = seconds;
	timerMinutes = minutes;
	timerHours = hours;
	totalSeconds = (timerSeconds + (timerMinutes * 60) + ((timerHours * 60) * 60));
	console.log(timerHours + ":" + timerMinutes + ":" + timerSeconds);
	$(timerReportDiv).text( timerHours + ":" + timerMinutes + ":" + timerSeconds );
	timer_update();
}

function report(actionType){
	console.log("Reporting time...");
	
	/*
	If task is marked as completed 
	alter if started to be uncompleted.
	*/

	/*
	Possible actions: 
	start, stop, update, pause, completed
	*/
	
	$.ajax({
		url: "http://pms.isodeveloper.com/__freshbooksapi/_timeraction.php?taskid=" + task + "&projectid=" + project + "&userid=" + user + "&action=" + actionType + "&totalseconds=" + totalSeconds
	}).done(function(e){ console.log(e); });
}

function timer_start(){
	$.ajax({ 
		url: "http://pms.isodeveloper.com/__freshbooksapi/_timeraction.php?taskid=" + task + "&projectid=" + project + "&userid=" + user + "&action=checkforrunning&totalseconds=0" 
	}).done(function(e){ 
		console.log(e);
		if (e == 'running') { timerActive = true; }else{ timerActive = false; } 
		if(!timerActive){
			report('start'); /*Start timer*/
			timer = setInterval(function(){
				convertTimer(false);	/*Report to client time worked so far.*/
			}, clientReportTimerInterval);

			reportTimer = setInterval(function(){
				timer_update(); /*Report to server time worked so far.*/
			}, reportTimerInterval);

			console.log("Timer started.");
			timerActive = true;
		}else{
			alert('Timer is already running.');
			console.log("Timer already running.");
		}
	});
}

function timer_stop(){
	if(timerActive){
		report('stop'); /*Stop timer*/
		timerActive = false;
		clearInterval(timer);
		clearInterval(reportTimer);
		console.log("Timer ended.");
	}else{
		alert('Timer is not running.');
		console.log("Timer is not running.");
	}
}

function timer_update(){
	report('update'); /*Update timer*/
}

function timer_pause(){
	if(timerActive){
		report('pause'); /*Pause timer*/
		timerActive = false;
		clearInterval(timer);
		clearInterval(reportTimer);
		console.log("Timer ended.");
	}else{
		alert('Timer is not running.');
		console.log("Timer is not running.");
	}
}

function timer_completed(){
	report('completed'); /*Completed timer*/
	if(timerActive){
		timerActive = false;
		clearInterval(timer);
		clearInterval(reportTimer);
		console.log("Timer ended.");
	}
	alert('Task Completed');
}

$(document).ready(function(){
	$("#starttimer").click(function(){
		console.log("starttimer clicked");
		timer_start();	
	});

	$("#pausetimer").click(function(){
		console.log("pausetimer clicked");
		timer_pause();
	});

	$("#completedtimer").click(function(){
		console.log("completedtimer clicked");
		timer_completed();
	});

	convertTimer(true); /*Update timer to reflect current time when loaded.*/
});