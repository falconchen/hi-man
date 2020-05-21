$(document).ready(function() {

    // Automatic fade out after 6 seconds
    window.setTimeout(function() {
        $(".autofadeout").slideUp("slow")
    }, 6000);

    $('iframe[src*="youtube.com"]').parent().addClass('responsive-youtube');

    //remove  <p>&nbsp;</p>  content in front end
    $('.hi-content-wrap > .hi-content > p').each(function (id, el) {
        if ($(el).html() == '&nbsp;') {
            $(el).remove();
        }
    });


    /*Scroll to top when arrow up clicked BEGIN*/
    // $(window).scroll(function() {
    //     var height = $(window).scrollTop();
    //     if (height > 100) {
    //         $('#back2Top').fadeIn();
    //     } else {
    //         $('#back2Top').fadeOut();
    //     }
    // });

    
    $("#back2Top").click(function(event) {
        event.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });


});
