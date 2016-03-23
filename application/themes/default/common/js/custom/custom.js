$(document).ready(function(){
    /**
     * [NOTES]
     * This allows hyperlinks that span across a whole table row
     *
     * [EXAMPLE]
     * <tr class="tr-link" data-link="website.com/admin/project/[1/view">
     * <td> some data</td>
     * <td> some data</td>
     * <td class="tr-link-excluded"> ignore this cell.</td>
     * <td> some data</td>
     * </tr>
     */
    $('.tr-link').on('click', 'td:not(.tr-link-excluded)', function(){
    
        //get the linl from attribute
        //var tr_link = $(this).attr("data-link");
        var tr_link = $(this).parent().attr("data-link");
        
        //do we have a valid link      
        if (tr_link == '' || typeof tr_link === 'undefined') {
            //do nothing
        }
        else {
            //open the page
            window.location = tr_link;
        }
    });
});
$(document).ready(function(){
    /**
     * [NOTES]
     * This allows hyperlinks that span across a any dom element
     *
     * [EXAMPLE]
     * <li class="url-link" data-link="website.com/admin/project/[1/view">something</li>
     */
    $('.url-link').click(function(e){
    
        //get the linl from attribute
        var url_link = $(this).attr("data-link");
        
        //do we have a valid link      
        if (url_link == '' || typeof url_link === 'undefined') {
            //do nothing
        }
        else {
            //open the page
            window.location = url_link;
        }
    });
});
$(document).ready(function(){
    //uses the 'fullsceen' icon on modal window
    $('.full-screen-modal').on('click', function(e){
        $('.modal-dialog').toggleClass("modal-full-screen");
        e.preventDefault();
    });
});
$(document).ready(function(){

    //close widget
    $('.wclose').click(function(e){
        e.preventDefault();
        var $wbox = $(this).parent().parent().parent();
        $wbox.hide(100);
    });
    
    //minimize widget
    $('.wminimize').click(function(e){
        e.preventDefault();
        var $wcontent = $(this).parent().parent().next('.widget-content');
        if ($wcontent.is(':visible')) {
            $(this).children('i').removeClass('icon-chevron-up');
            $(this).children('i').addClass('icon-chevron-down');
        }
        else {
            $(this).children('i').removeClass('icon-chevron-down');
            $(this).children('i').addClass('icon-chevron-up');
        }
        $wcontent.toggle(500);
    });
    
    /* Toggle a div*/
    $('.divminimize').click(function(e){
        e.preventDefault();
        var $togglediv = $(this).parent().children('.toggle-div');
        
        if ($togglediv.is(':visible')) {
            $(this).children('i').removeClass('icon-chevron-up');
            $(this).children('i').addClass('icon-chevron-down');
        }
        else {
            $(this).children('i').removeClass('icon-chevron-down');
            $(this).children('i').addClass('icon-chevron-up');
        }
        $togglediv.toggle(500);
    });
    
});

$(document).ready(function(){

    //$("#wi_notice_success").delay(5000).slideUp(1000);
    $//("#wi_notice_error").delay(5000).slideUp(1000);
});

$(document).ready(function(){

    $(window).resize(function(){
        if ($(window).width() >= 765) {
            $(".sidebar #nav").slideDown(350);
        }
        else {
            $(".sidebar #nav").slideUp(350);
        }
    });
    
    
    $("#nav > li > a").on('click', function(e){
        if ($(this).parent().hasClass("has_sub")) {
            e.preventDefault();
        }
        
        if (!$(this).hasClass("subdrop")) {
            // hide any open menus and remove all other classes
            $("#nav li ul").slideUp(350);
            $("#nav li a").removeClass("subdrop");
            
            // open our new menu and add the open class
            $(this).next("ul").slideDown(350);
            $(this).addClass("subdrop");
        }
        
        else 
            if ($(this).hasClass("subdrop")) {
                $(this).removeClass("subdrop");
                $(this).next("ul").slideUp(350);
            }
        
    });
});

$(document).ready(function(){
    $(".sidebar-dropdown a").on('click', function(e){
        e.preventDefault();
        
        if (!$(this).hasClass("open")) {
            // hide any open menus and remove all other classes
            $(".sidebar #nav").slideUp(350);
            $(".sidebar-dropdown a").removeClass("open");
            
            // open our new menu and add the open class
            $(".sidebar #nav").slideDown(350);
            $(this).addClass("open");
        }
        
        else 
            if ($(this).hasClass("open")) {
                $(this).removeClass("open");
                $(".sidebar #nav").slideUp(350);
            }
    });
    
});

$(document).ready(function(){
    $(".totop").hide();
    $(function(){
        $(window).scroll(function(){
            if ($(this).scrollTop() > 300) {
                $('.totop').slideDown();
            }
            else {
                $('.totop').slideUp();
            }
        });
        
        $('.totop a').click(function(e){
            e.preventDefault();
            $('body,html').animate({
                scrollTop: 0
            }, 500);
        });
        
    });
});

$(document).ready(function(){
    $('.tooltips').tooltip();
});

$(document).ready(function(){
    $('.confirm-url-action').popConfirm();
});

$(document).ready(function(){
    $('[data-toggle="popover"]').popover({
        trigger: 'click'
    });
});

$(document).ready(function(){
    $('.heading-menu-toggle').click(function(e){
    
        //remove all active links
        $('.heading-menu-toggle').removeClass("heading-menu-tab-active");
        $('.heading-menu-toggle').addClass("heading-menu-tab");
        
        $(this).removeClass("heading-menu-tab");
        $(this).addClass("heading-menu-tab-active");
        
    });
});

$(document).ready(function(){
    $('.divtoggle').click(function(e){
        e.preventDefault();
        var togglediv = $(this).attr("data-toggle-div");
        var togglediv = $('.' + togglediv);
        
        if (togglediv.is(':visible')) {
            $(this).children('i').removeClass('icon-chevron-up');
            $(this).children('i').addClass('icon-chevron-down');
        }
        else {
            $(this).children('i').removeClass('icon-chevron-down');
            $(this).children('i').addClass('icon-chevron-up');
        }
        togglediv.toggle(500);
    });
});

$(document).ready(function(){

    $('.reload-parent-on-close').on('hidden.bs.modal', function(){
        location.reload();
    });
    
});

jQuery.fn.ucwords = function(){
    $(this[0]).keyup(function(event){
        var box = event.target;
        var txt = $(this).val();
        var start = box.selectionStart;
        var end = box.selectionEnd;
        $(this).val(txt.replace(/^(.)|(\s|\-)(.)/g, function($1){
            return $1.toUpperCase();
        }));
        box.setSelectionRange(start, end);
    });
    return this;
}

$(document).ready(function(){
    //apply to selector (normally form input)
    $('.js_ucwords').ucwords();
});


/** slim scroll */
$(document).ready(function(){
    $('.slimScrollProjectTimeline').slimScroll({
        height: '600px',
        railVisible: false,
        alwaysVisible: false,
        railColor: 'transparent',
        color: '#bfbfbf'
    });
});
$(document).ready(function(){
    $('.slimScrollAdminProjectTimeline').slimScroll({
        height: '548px',
        railVisible: false,
        alwaysVisible: false,
        railColor: 'transparent',
        color: '#bfbfbf'
    });
});
$(document).ready(function(){
    $('.slimScrollHomeTasks').slimScroll({
        height: '320px',
        railVisible: false,
        alwaysVisible: false,
        railColor: 'transparent',
        color: '#bfbfbf'
    });
});
$(document).ready(function(){
    $('.slimScrollBarModal').slimScroll({
        position: 'right',
        height: '450px',
        railVisible: true,
        alwaysVisible: true
    });
});
