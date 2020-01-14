// Get the Sidebar
var mySidebar = document.getElementById("mySidebar");

// Get the DIV with overlay effect
var overlayBg = document.getElementById("myOverlay");

// Toggle between showing and hiding the sidebar, and add overlay effect
function w3_open() {
  if (mySidebar.style.display === "block") {
    mySidebar.style.display = "none";
    overlayBg.style.display = "none";
  } else {
    mySidebar.style.display = "block";
    overlayBg.style.display = "block";
  }
}

// Close the sidebar with the close button
function w3_close() {
  mySidebar.style.display = "none";
  overlayBg.style.display = "none";
}

$(document).ready(function() {
  //menu
  if ($(".hi-sub-item").hasClass("hi-current")) {
    $(".hi-sub-item")
      .parent()
      .removeClass("w3-dropdown-content");
    $(".hi-dropdown-content")
      .parent()
      .removeClass("w3-dropdown-hover")
      .find(".hi-sub-header")
      .addClass("w3-blue");
  }

  //bind oscer

  $(".hi-oscer-info .hi-close-btn,.hi-oscer-info .hi-cancel-btn").click(
    function() {
      $(".bind-osc-form-wrapper").hide();
    }
  );

  $("form.hi-bind-osc-form").submit(function() {
    var $form = $(this);
    var $password = $form.find('input[name="userPassword"]');
    var inputed_password = $password.val();

    $password.val(sha1($password.val()));

    var $submit_btn = $(".hi-bind-osc-form .hi-submit-btn");
    $submit_btn.addClass("w3-disabled");

    $("#bind-osc-form-wrapper .fa-spinner").removeClass("w3-hide");

    $.ajax({
      url: $form.attr("action"),
      type: $form.attr("method"),
      dataType: "json",
      data: $form.serialize()
    })
      .done(function(response) {
        if (response.success) {
          $(".hi-oscer-info img").attr("src", response.data.avatar);
          $(".hi-oscer-info .hi-oscer-homepage")
            .attr("href", response.data.homepage)
            .text(response.data.userName)
            .attr("title", response.data.userName)
            .removeClass("w3-hide");
          $(".hi-oscer-info .hi-oscer-signature")
            .text(response.data.signature)
            .removeClass("w3-hide");
          $(".hi-bind-osc-form,.hi-oscer-footer > button").addClass("w3-hide");

          $(".hi-oscer-footer .hi-cancel-btn")
            .text(response.msg)
            .removeClass("w3-red")
            .addClass("w3-green")
            .removeClass("w3-hide");
          $(".hi-oscer-footer .hi-oscer-msg").text(
            "你现在可以同步文章或动弹到osc了,重新刷新页面"
          );

          $(".hi-osc-status")
            .removeClass("w3-text-red")
            .addClass("w3-text-green");
          $(".hi-osc-status > span").text("已连接osc");
          setTimeout(function() {
            window.location.reload(true);
          }, 2000);
        } else {
          $(".hi-oscer-footer .hi-oscer-msg").text(response.msg);
        }
      })
      .always(function(data) {
        $submit_btn.removeClass("w3-disabled");
        $password.val(inputed_password);
        $("#bind-osc-form-wrapper .fa-spinner").addClass("w3-hide");
      });

    return false;
  });

  //展开项

  $(".hi-accordion").click(function() {
    if (
      $(this)
        .parent()
        .hasClass("hi-osc-sync-type") &&
      $(this)
        .parent()
        .hasClass("active")
    ) {
      return; //禁止转载展开后重新点击收起
    } else if (
      $(this)
        .parent()
        .hasClass("hi-post-visibility") &&
      $(this)
        .parent()
        .hasClass("active")
    ) {
      return; //禁止密码保护展开后重新点击收起
    } else if (
      $(this)
        .parent()
        .hasClass("hi-post-future") &&
      $(this)
        .parent()
        .hasClass("active")
    ) {
      return; //定时发布
    }
    $(this)
      .parent()
      .toggleClass("active");
    $(this).toggleClass("active");
    var index = $(".hi-accordion").index(this);
    var $detailPanel = $(".hi-detail-panel").eq(index);
  });
  //osc sync 原创/转载
  $(".hi-osc-sync-type input[value=1]").click(function() {
    $(this)
      .parent()
      .removeClass("active");
  });

  //公开度
  $('.hi-post-visibility input[id!="visibility-radio-password"]').click(
    function() {
      $(this)
        .parent()
        .removeClass("active");
    }
  );

  //定时发布
  $(".hi-post-future input[value!=yes]").click(function() {
    $(this)
      .parent()
      .removeClass("active");
  });

  $("input[name=post_future]").change(function() {
    var status = "post_future-radio-" + $(this).val();
    var text = $("label[for=" + status + "]").text();
    var publishBtnVal = $(this).val() == "yes" ? "future" : "publish";
    $(".hi-publish-btn")
      .val(publishBtnVal)
      .text(text);
  });

  $(".hi-post-form").submit(function() {
    var errors = [];
    $(".hi-post-form .hi-error-border").removeClass("hi-error-border");

    var inputs = parseQueryString(
      $(
        ".hi-post-form input,.hi-post-form select ,.hi-post-form  button"
      ).serialize()
    );

    //check date valid
    if (inputs.post_future == "yes") {
      var dateInput =
        inputs.y +
        "-" +
        inputs.m +
        "-" +
        inputs.d +
        " " +
        inputs.h +
        ":" +
        inputs.i;

      if (!isValidDate(dateInput)) {
        errors.push({
          class: "time-wrap",
          message: "无效的定时发布时间: " + dateInput
        });
      } else {
        var dateCurrent = new Date();
        var dateFuture = new Date(dateInput);
        if (dateCurrent.getTime() > dateFuture.getTime()) {
          errors.push({
            class: "time-wrap",
            message:
              "定时发布时间不能比当前时间早，请检查你的设置值: " + dateInput
          });
        }
      }
    }
    if (errors.length > 0) {
      var error_contents = "";
      for (var i = 0; i < errors.length; i++) {
        $("." + errors[i].class).addClass("hi-error-border");
        error_contents += "<p>" + errors[i].message + "</p>";
      }
      $(".hi-post-form .w3-modal").show();
      $(".hi-post-form .hi-modal-header").text("出错了");
      $(".hi-post-form .hi-modal-content").html(error_contents);
      return false;
    }

    return true;
  });
});

//预览
$(".hi-preview-link").click(function() {
  $(".hi-post-form textarea[name=post_content]").val(
    tinyMCE.get("hi-editor").getContent()
  );
  var data = $(".hi-post-form").serialize();

  $.ajax({
    type: "POST",
    url: savePreviewUrl,
    data: data,
    success: function(response) {
      //location.href = response.url;
      window.open(response.url, "preview_" + parseInt(Math.random() * 1000000));
    },
    dataType: "json"
  });

  return false;
});

tinymce.init({
  selector: "#hi-editor",
  language: "zh_CN",
  //menubar: 'edit insert view format table tools help',
  menubar: false,
  height: 667,
  //plugins: 'code',
  plugins: "image imagetools code link fullscreen autosave wordcount",

  toolbar: " undo redo | styleselect | bold italic | link image  | code ",
  language_url: "/js/node_modules/tinymce/langs/zh_CN.js",
  //skin: 'oxide-dark'

  autosave_interval: "20s",
  autosave_prefix: "hi-autosave-{path}{query}-{id}-",
  //autosave_restore_when_empty: true,
  autosave_retention: "30m",
  content_style: [
    'body{font-size:14px;font-family:Lato,"Helvetica Neue",Helvetica,Arial,sans-serif; line-height:1.6;}'
  ]
});

//parse Query to obj
function parseQueryString(queryString) {
  var params = {},
    queries,
    temp,
    i,
    l;

  // Split into key/value pairs
  queries = queryString.split("&");

  // Convert the array of strings into an object
  for (i = 0, l = queries.length; i < l; i++) {
    temp = queries[i].split("=");
    params[temp[0]] = temp[1];
  }

  return params;
}

/**
 *
 * check date validate
 * ref https://medium.com/@esganzerla/simple-date-validation-with-javascript-caea0f71883c
 * @param  dateString format : 2019-12-31 15:49
 */
function isValidDate(dateString) {
  var d = new Date(dateString);
  if (isNaN(d)) {
    return false;
  }
  var day = parseInt(dateString.split(" ")[0].split("-")[2]);
  if (d.getDate() != day) {
    return false;
  }
  return true;
}
