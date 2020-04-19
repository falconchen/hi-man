$(document).ready(function() {

    // Automatic fade out after 6 seconds
    window.setTimeout(function() {
        $(".autofadeout").slideUp("slow")
    }, 6000);

    $('iframe[src*="youtube.com"]').parent().addClass('responsive-youtube');

    //remove  <p>&nbsp;</p>  content in front end
    $('.hi-content-wrap >p').each(function (id, el) {
        if ($(el).html() == '&nbsp;') {
            $(el).remove();
        }
    });

});