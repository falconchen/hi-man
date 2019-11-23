# Hi Man

## 相关项目

- [w3css管理面板模板一](https://www.w3schools.com/w3css/tryw3css_templates_mail.htm)
- [w3css管理面板模板二](https://www.w3schools.com/w3css/tryw3css_templates_analytics.htm)

- [slim3](http://www.slimframework.com)

- [slim3文档](http://www.slimframework.com/docs/)

- [Slim 3 Skeleton](https://github.com/slimphp/Slim-Skeleton)

- [violin](https://github.com/alexgarrett/violin) 一个便于使用，支持高度定制的PHP验证器

- [Illuminate Database component](https://github.com/illuminate/database)

- [eloquent](https://laravel.com/docs/5.8/eloquent)

- [eloquent6.x](https://laravel.com/docs/6.x/eloquent)

- [Laravel Tips ](https://github.com/seekerliu/laravel-tips) 虽然是针对laravel, 也对很多组件有解释

- [Twig模板引擎](https://twig.symfony.com/)

- [w3css](https://www.w3schools.com/w3css/default.asp)

- [TinyMCE v4](https://www.tiny.cloud/docs-4x/quick-start/)

- [TinyMCE v4 主题/插件等](https://www.tiny.cloud/get-tiny/custom-builds/)

- [TinyMCE v4 外观(menubar和toolbar等)](https://www.tiny.cloud/docs/configure/editor-appearance/#menubar)

- [TinyMCE 自定义一个menubar按钮](https://www.tiny.cloud/docs/demo/custom-toolbar-menu-button/)

- [免费的文件上传管理器组件](https://www.responsivefilemanager.com/demo.php) 可整合到TinyMCE

- [File and image management plugins for TinyMCE](
https://www.tyssendesign.com.au/articles/cms/file-and-image-management-plugins-for-tinymce/#tinybrowser
)
## codes && tips

```迭代twig

{% for type,messages in array %}
<div class="panel panel-{{ type }}">    
    {% for msg in messages %}        
    <div class="panel-{% if loop.first %}heading{% else %}body{% endif%}">{{msg}}</div>        
    {% endfor %}
</div>
{% endfor %}

```