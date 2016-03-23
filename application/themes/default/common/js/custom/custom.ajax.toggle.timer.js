/* 
 * ------------------------------------------------------------------------------------------------------
 * Bootstrap - AJAX START AND STOP TIMER
 * NEXTLOOP 16 JULY 2014
 * ------------------------------------------------------------------------------------------------------
 * @EVENT
 * Click control timer button or link. This is used by the 'timers.html' or timers list page
 *
 * @ACTIONS
 *
 *
 * @DEPENDS
 * bootsrap.js - Uses bootstrap css etc
 * popconfirm.js - for the confirm prompt box
 * noty.js - for the warning alert when ajax php file returns an error status
 *
 *
 * @SAMPLE CODE
 *
 *
 <!--start timer-->
 <span class="btn-start-time [vars.css_start_timer_btn]">
 <button class="btn btn-success ajax-toggle-timer"
 data-popconfirm-yes="[lang.lang_yes;noerr]"
 data-popconfirm-no="[lang.lang_no;noerr]"
 data-popconfirm-title="[lang.lang_start_stop_timer;noerr]"
 data-popconfirm-placement="left"
 data-mysql-record-id="[vars.my_project_timer_id]"
 data-timer-new-status="running"
 data-ajax-url="[conf.site_url]/admin/ajax/toggle-timer">Start My Timer</button>
 </span>
 <!--start timer end-->
 
 
 <!--JS_TOGGLE_TIMER-->
 <script>
 //language
 var lang_request_could_not_be_completed = "[lang.lang_request_could_not_be_completed;noerr]";
 var lang_timer_has_been_updated = "[lang.lang_timer_has_been_updated;noerr]";
 </script>
 <script type="text/javascript" src="[conf.site_url_themes_common;noerr]/js/custom/custom.uploadfile.js"></script>
 <!--JS_TOGGLE_TIMER-->
 *
 *
 * ------------------------------------------------------------------------------------------------------
 */
$(document).ready(function(){


    //---Run this when user clicks the red "yes" button-------
    $(".ajax-toggle-timers-list").click(function(){
    
        //get variables for ajax post request
        var data_mysql_record_id = $(this).attr("data-mysql-record-id");
		var data_project_id = $(this).attr("data-project-id");
        var data_ajax_url = $(this).attr("data-ajax-url");
        var data_timer_task = $(this).attr('data-timer-task');
        var data_timer_userid = $(this).attr('data-timer-userid');
        //new status of timer after update
        var timer_new_status = $(this).attr("data-timer-new-status");
        
        
        
        //flow control
        var $next = 1;
        
        //______Ajax Bit________________________
        /**
         * If everything is ok, run the ajax rquest
         */
        if ($next === 1) {
            $.ajax({
                type: 'post',
                url: data_ajax_url,
                dataType: 'json',
                data: 'data_timer_userid='+data_timer_userid+'&data_timer_task='+data_timer_task+'&data_mysql_record_id=' + data_mysql_record_id + '&data_timer_new_status=' + timer_new_status + '&data_project_id=' + data_project_id,
                
                /**
                 * update was successful, update the button and label icon colors.
                 * Show 'noty' success message
                 */
                success: function(data){
                
                    //console.log(data);//debug
                    
                    /**
                     * get a json response for:
                     * data.message             - this is the message we will show on 'noty' popup
                     * data.current_time        - this will be used to update the task with current time worked
                     * data.project_total_time  - this is the total for all tasks. It will show on left menu
                     */
                    ajax_response = data.message;
                    current_time = data.current_time;
                    project_total_time = data.project_total_time;
                    
                    if (ajax_response == '' || ajax_response == 'undefined') {
                        ajax_response = lang_timer_has_been_updated;
                    }
                    
                    /**
                     * Update time for this timer
                     */
                    $("#my-project-time-"+data_mysql_record_id).text(current_time);
                    
                    
                    /**
                     *  Toggle the start/stop buttons vice versa
                     */
                    if (timer_new_status == 'running') {
                    
                        $("#btn-start-timer-"+data_mysql_record_id).removeClass("visible").addClass("invisible");
                        $("#btn-stop-timer-"+data_mysql_record_id).removeClass("invisible").addClass("visible");
                    }
                    
                    if (timer_new_status == 'stopped') {
                    
                        $("#btn-start-timer-"+data_mysql_record_id).removeClass("invisible").addClass("visible");
                        $("#btn-stop-timer-"+data_mysql_record_id).removeClass("visible").addClass("invisible");
                    }
                    
                    //show noty notification 1 sec later
                    setTimeout(function(){
                        noty({
                            text: ajax_response,
                            layout: 'bottomRight',
                            type: 'information',
                            timeout: 1500
                        });
                    }, 300);
                },
                
                /**
                 * update was NOT successful
                 */
                error: function(data){
                
                    var data = data.responseJSON
                    //console.log(data);//debug
                    
                    //get a json message from server if one exists
                    ajax_response = data.message; //where 'message' is key in php jason output
                    if (ajax_response == '' || ajax_response == 'undefined') {
                        ajax_response = lang_request_could_not_be_completed; //lang
                    }
                    
                    //fire up a noty message
                    noty({
                        text: '' + ajax_response,
                        layout: 'bottomRight',
                        type: 'warning',
                        timeout: 1500
                    });
                    
                }
                
            });
            //end ajax
        }
        
        
    });
    
});

