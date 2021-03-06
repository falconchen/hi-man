$(document).ready(function () {
  // Automatic fade out after 6 seconds
  window.setTimeout(function () {
    $(".autofadeout").slideUp("slow");
  }, 6000);

  $('iframe[src*="youtube.com"]').parent().addClass("responsive-youtube");

  //remove  <p>&nbsp;</p>  content in front end
  $(".hi-content-wrap > .hi-content > p").each(function (id, el) {
    if ($(el).html() == "&nbsp;") {
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

  $("#back2Top").click(function (event) {
    event.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "slow");
    return false;
  });

  var serachCloseBtn =$('.hi-search-form-wrap .hi-close-btn')
  var navBtn = $(".hi-nav-search-button")
  var searchFunc = function () {
    
    logEvent({
      eventCategory: "search",
      eventAction: "click",
    });
    
    var area = $(".hi-search-form");
    
    if (area.length > 0) {
      if (area.hasClass("non-close")) {
        area.find('input[type="text"]').focus();
        return false;
      }
      if (area.hasClass("closed")) {
        area.removeClass("closed");
        navBtn.addClass("on");
        area.show().find('input[type="text"]').focus()
        serachCloseBtn.removeClass('w3-hide').fadeIn()
      } else {
        navBtn.removeClass("on");
        area.addClass("closed");
        serachCloseBtn.addClass('w3-hide')
        serachCloseBtn.addClass('w3-hide').fadeOut()
      }
      return false;
    }
  }
  navBtn.click(searchFunc);
  serachCloseBtn.click(searchFunc)



  // function viewFullImages() {
  //     document.querySelectorAll
  // }

  ViewFullImage(".hi-content img,.gallery img,.portrait img", false);
});

function logEvent(opt) {
  if (["localhost", "127.0.0.1", ""].includes(window.location.hostname)) {
    console.log("Analytics ignored as it's localhost");
    return;
  }
  if (navigator.doNotTrack) {
    console.log("Analytics ignored as DNT is enabled for this user");
    return;
  }
  if (typeof ga === "function") {
    ga("send", opt);
  }
}

/**
 * view full image in modal
 * @param string images
 * @param boolean showAlt
 */
function ViewFullImage(images, showAlt) {
  const hiModal = document.querySelector(".hi-modal");
  const previews = document.querySelectorAll(images);
  const original = document.querySelector(".hi-modal .full-img");
  const caption = document.querySelector(".hi-modal .caption");
  const imageOpener = document.querySelector('.hi-modal >a');
  const blankImgSrc = original.src;
  previews.forEach((preview) => {
    preview.style.cursor = "pointer";

    preview.addEventListener("click", () => {

      if(preview.parentElement.tagName.toLowerCase() == 'a'){
          return false;
      }

      hiModal.classList.add("open");
      original.classList.add("open");
      let originalSrc = preview.getAttribute("data-original");
      if (!originalSrc) {
        originalSrc = preview.src;
      }
      //console.log(originalSrc);
      original.src = `${originalSrc}`; //just for fun
      imageOpener.href = originalSrc;

      if (showAlt) {
        const originCaption = preview.alt;
        if (originCaption.length > 0) {
          caption.textContent = originCaption;
        }
      }
    });
  });

  hiModal.addEventListener("click", (e) => {
    if (e.target.classList.contains("hi-modal")) {
      hiModal.classList.remove("open");
      original.classList.remove("open");
      original.src = blankImgSrc;
      original.alt = "";
      imageOpener.href = 'javascript:;';
    }
  });
}
