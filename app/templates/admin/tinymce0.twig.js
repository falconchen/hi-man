tinymce.init({
    selector: '#hi-editor',
    language: 'zh_CN',
    //menubar: 'edit insert view format table tools help',
    menubar: false,
    height : 600,
    //plugins: 'code',
    plugins: "image imagetools code link fullscreen autosave",

    toolbar: ' undo redo | styleselect | bold italic | link image  | code ',
    language_url: '/js/node_modules/tinymce/langs/zh_CN.js',
    //skin: 'oxide-dark'
    
    autosave_interval: "20s",
    autosave_prefix: "hi-autosave-{path}{query}-{id}-",
    autosave_restore_when_empty: true,
    autosave_retention: "30m"


});

// tinymce.init({
//     selector: "textarea",  // change this value according to your HTML
//     plugins: "media",
//     menubar: "insert",
//     toolbar: "media",
//     language: 'zh_CN',
//     language_url: '/js/node_modules/tinymce/langs/zh_CN.js',
//     media_filter_html: false
// });

// tinymce.init({
//     selector: "textarea",  // change this value according to your html
//     plugins: "textcolor",
//     toolbar: "forecolor backcolor"
// });

// tinymce.init({
//     selector: "textarea",  // change this value according to your HTML
//     plugins: "paste",
//     menubar: "edit",
//     toolbar: "paste",
//     paste_data_images: true
//
// });

//
// tinymce.init({
//     selector: '#myeditablediv',
//     toolbar: 'undo redo',
//     inline: true
// });
// tinymce.init({
//     selector: "textarea",  // change this value according to your HTML
//     plugins: "link",
//     menubar: "insert",
//     toolbar: "link",
//     default_link_target: "_blank"
// });

// tinymce.init({
//     selector: "textarea",  // change this value according to your HTML
//     plugins: "autosave",
//     toolbar: "restoredraft",
//     autosave_interval: "20s",
//     autosave_prefix: "tinymce-autosave-{path}{query}-{id}-",
//     autosave_restore_when_empty: true,
//     autosave_retention: "30m"
//
//
// });