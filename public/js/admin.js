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
        prefixInteger(inputs.m,2) +
        "-" +
        prefixInteger(inputs.d,2) +
        " " +
        prefixInteger(inputs.h,2) +
        ":" +
        prefixInteger(inputs.i,2);

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

tinyMCE.PluginManager.add('stylebuttons', function(editor, url) {
  ['pre', 'p', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(name){
      editor.addButton("style-" + name, {
          tooltip: "Toggle " + name,
          text: name.toUpperCase(),
          onClick: function() { editor.execCommand('mceToggleFormat', false, name); },
          onPostRender: function() {
              var self = this, setup = function() {
                  editor.formatter.formatChanged(name, function(state) {
                      self.active(state);
                  });
              };
              editor.formatter ? setup() : editor.on('init', setup);
          }
      })
  });
});

tinymce.init({
  selector: "#hi-editor",
  language: "zh_CN",
  //menubar: "edit insert format table tools help",
  toolbar_sticky: true,
  menubar: false,
  height: 667,
  
  //plugins: 'code',
  plugins: "image imagetools code link fullscreen autosave wordcount codesample",

  codesample_languages: [
		{text: 'HTML/XML', value: 'markup'},
		{text: 'JavaScript', value: 'javascript'},
		{text: 'CSS', value: 'css'},
		{text: 'PHP', value: 'php'},
		{text: 'Ruby', value: 'ruby'},
		{text: 'Python', value: 'python'},
		{text: 'Java', value: 'java'},
		{text: 'C', value: 'c'},
		{text: 'C#', value: 'csharp'},
    { text: 'C++', value: 'cpp' },
    {text: 'Swift', value: 'swift'}
	],

  toolbar:
    "codesample  | undo redo | styleselect basicDateButton menuDateButton |codeSC  bold italic forecolor  |removeformat link image  | code  ", //
  
  

  language_url: "/js/node_modules/tinymce/langs/zh_CN.js",
  //skin: 'oxide-dark'

  autosave_interval: "20s",
  autosave_prefix: "hi-autosave-{path}{query}-{id}-",
  //autosave_restore_when_empty: true,
  autosave_retention: "30m",
  content_style: [
    'body{font-size:1em;font-family:"Helvetica Neue", "Luxi Sans", "DejaVu Sans", Tahoma, "Hiragino Sans GB", STHeiti !important; line-height:1.8;}h1{font-size:22px;}h2{font-size:20px;}p{margin:0}'
  ],

  
  setup: function(editor) {

    editor.ui.registry.addButton("codeSC", {
      text:"💡",
      tooltip: "insert code Element Here",
      onAction: function(_) {
        //console.log("<code>"+editor.selection.getContent()+"</code>");
        //editor.insertContent( "<code>"+editor.selection.getContent()+"</code>" );
        editor.execCommand('mceToggleFormat', false, 'code'); //格式化
      }
    });

    /* Helper functions */
    var toTimeHtml = function(time) {
      return (
        '<time datetime="' +
        time.toString() +
        '">' +
        time.toString() +
        "</time>"
      );
    };
    var toAuthorHtml = function(name) {
      return (
        '<cite title="' + name.toString() + '">' + name.toString() + "</cite>"
      );
    };
    var toDateHtml = function(date) {
      return (
        '<time datetime="' +
        date.toString() +
        '">' +
        date.toDateString() +
        "</time>"
      );
    };
    var toGmtHtml = function(date) {
      return (
        '<time datetime="' +
        date.toString() +
        '">' +
        date.toGMTString() +
        "</time>"
      );
    };
    var toIsoHtml = function(date) {
      return (
        '<time datetime="' +
        date.toString() +
        '">' +
        date.toISOString() +
        "</time>"
      );
    };

    Date.prototype.format = function(fmt) {
      var o = {
        "M+": this.getMonth() + 1, //月份
        "d+": this.getDate(), //日
        "h+": this.getHours(), //小时
        "m+": this.getMinutes(), //分
        "s+": this.getSeconds(), //秒
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度
        S: this.getMilliseconds() //毫秒
      };
      if (/(y+)/.test(fmt)) {
        fmt = fmt.replace(
          RegExp.$1,
          (this.getFullYear() + "").substr(4 - RegExp.$1.length)
        );
      }
      for (var k in o) {
        if (new RegExp("(" + k + ")").test(fmt)) {
          fmt = fmt.replace(
            RegExp.$1,
            RegExp.$1.length == 1
              ? o[k]
              : ("00" + o[k]).substr(("" + o[k]).length)
          );
        }
      }
      return fmt;
    };

    editor.ui.registry.addButton("basicDateButton", {
      icon: "insert-time",
      tooltip: "Insert Current Time",
      onAction: function(_) {
        var time = new Date().format("yyyy-MM-dd hh:mm");
        editor.insertContent(toTimeHtml(time));
      }
    });

    editor.ui.registry.addMenuButton("menuDateButton", {
      icon: "browse",
      //text: "DateTime",
      fetch: function(callback) {
        var items = [
          {
            type: "menuitem",
            text: "Author",
            onAction: function(_) {
              var name = currentUser.name;
              editor.insertContent(toAuthorHtml(name));
              //editor.insertContent(toDateHtml(new Date()));
            }
          },
          {
            type: "menuitem",
            text: "Date",
            onAction: function(_) {
              var time = new Date().format("yyyy-MM-dd");
              editor.insertContent(toTimeHtml(time));
              //editor.insertContent(toDateHtml(new Date()));
            }
          },
          {
            type: "menuitem",
            text: "ShortTime",
            onAction: function(_) {
              var time = new Date().format("hh:mm:ss");
              editor.insertContent(toTimeHtml(time));
              //editor.insertContent(toDateHtml(new Date()));
            }
          },
          {
            type: "menuitem",
            text: "FullTime",
            onAction: function(_) {
              var time = new Date().format("yyyy-MM-dd hh:mm:ss");
              editor.insertContent(toTimeHtml(time));
              //editor.insertContent(toDateHtml(new Date()));
            }
          },

          {
            type: "nestedmenuitem",
            text: "Others Formats",
            getSubmenuItems: function() {
              return [
                {
                  type: "menuitem",
                  text: "EN",
                  onAction: function(_) {
                    editor.insertContent(toDateHtml(new Date()));
                  }
                },
                {
                  type: "menuitem",
                  text: "GMT",
                  onAction: function(_) {
                    editor.insertContent(toGmtHtml(new Date()));
                  }
                },
                {
                  type: "menuitem",
                  text: "ISO",
                  onAction: function(_) {
                    editor.insertContent(toIsoHtml(new Date()));
                  }
                }
              ];
            }
          }
        ];
        callback(items);
      }
    });
  }
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
  var d = new Date(dateString.replace(/\s/, "T"));
  if (isNaN(d)) {
    return false;
  }
  var day = parseInt(dateString.split(" ")[0].split("-")[2]);
  if (d.getDate() != day) {
    return false;
  }
  return true;
}

function prefixInteger(num, length) {
  return (Array(length).join('0') + num).slice(-length);
}