/* 
 * ------------------------------------------------------------------------------------------------------
 * Bootstrap - JUST AN AJAX TEMPLATE
 * NEXTLOOP 17 JULY 2014
 * ------------------------------------------------------------------------------------------------------
 * @EVENT
 * This does something cool
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
 <!--button do something ajax-->
 <button class="btn btn-xs btn-default some-button" href="#"
 data-mysql-record-id = "25"
 data-some-var-one = "foobar"]
 data-some-var-two = "foobar"
 data-ajax-url="[conf.site_url]/admin/ajax/refresh-timer"><i class="icon-refresh bns-padded-right"></i>
 </button>
 <!--button do something ajax-->
 *
 *
 * ------------------------------------------------------------------------------------------------------
 */
$(document).ready(function(){


    //---Run this when user clicks the red "yes" button-------
    $(".some-button").click(function(e){
    
        //prevent default click action
        e.preventDefault();
        
        //get variables for ajax post request
        var data_ajax_url = $(this).attr("data-ajax-url");
        var data_mysql_record_id = $(this).attr("data-mysql-record-id"); //when used for all tasks, id = 0        var data_ajax_url = $(this).attr("data-ajax-url");
        var project_id = $(this).attr("data-project-id");
        var some_var_one = $(this).attr("data-some-var-one");
        var some_var_two = $(this).attr("data-some-var-two");
        
        
        //our ajax loading gif
        var ajax_loading_div = $("#ajax-loading-backups");
        
        
        //flow control
        var next = 1;
        
        //do some checks if you want
        /**
         * if(foo == 'bar'){
         * var next false
         * }
         */
        //______Ajax Bit________________________
        /**
         * If everything is ok, run the ajax rquest
         */
        if (next === 1) {
            $.ajax({
                type: 'post',
                url: data_ajax_url,
                dataType: 'json',
                data: 'data_mysql_record_id=' + data_mysql_record_id + '&foo=' + some_var_one + '&bar=' + some_var_two,
                
                /**
                 * update was successful, update the button and label icon colors.
                 * Show 'noty' success message
                 */
                success: function(data){
                
                    //console.log(data);//debug
                    
                    /**
                     * get a json response for:
                     * data.message             - this is a success error message (if any) we will show on 'noty' popup
                     * data.some_result         - perhaps some data we will want to display somewhere
                     */
                    ajax_some_result = data.some_result;
                    ajax_message = data.message;
                    
                    
                    
                    /**
                     * a momentary pause...just incase our ajax loaded too fast.
                     * lets show the loading gif a little longer :) the finish our process
                     * wrap next process in a setTimeout function
                     */
                    setTimeout(function(){
                    
                        /**
                         * hide the ajax loading div
                         */
                        ajax_loading_div.css('display', 'none');
                        
                        
                        /**
                         * show our results somewhere
                         */
                        $("#results_found").text(ajax_some_result);
                        
                        
                        /**
                         * get ajax response from server, if one exists
                         */
                        ajax_message = data.message;
                        if (ajax_message == '' || typeof ajax_message === 'undefined') {
                            ajax_message = 'Request has been completed'; //lang
                        }
                        
                        //fire up a noty message
                        noty({
                            text: ajax_message,
                            layout: 'bottomRight',
                            type: 'information',
                            timeout: 1500
                        });
                        
                    }, 5000);
                    
                },
                
                
                
                /**
                 * update was NOT successful, update the button and label icon colors.
                 * Show 'noty' error message
                 */
                error: function(data){
                
                    /**
                     * a momentary pause...just incase our ajax loaded too fast.
                     * wrap next process in a setTimeout function
                     * lets show the loading gif a little longer :) the finish our process
                     */
                    setTimeout(function(){
                    
                    
                        /**
                         * hide the ajax loading div
                         */
                        ajax_loading_div.css('display', 'none');
                        
                        
                        var data = data.responseJSON //toggle this if getting data undefined error
                        //console.log(data);//debug
                        
                        //get a json message from server if one exists
                        ajax_message = data.message; //where 'message' is key in php jason output
                        if (ajax_message == '' || typeof ajax_message === 'undefined') {
                            ajax_message = 'Error!- This request could not be completed'; //lang
                        }
                        alert('foo');
                        //fire up a noty message
                        noty({
                            text: '' + ajax_response,
                            layout: 'bottomRight',
                            type: 'warning',
                            timeout: 1500
                        });
                        
                    }, 5000);
                }
                
            });
            //end ajax
        }
        
    });
    
});

