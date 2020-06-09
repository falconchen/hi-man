# Hi Man

## 相关项目

- [w3css 管理面板模板一](https://www.w3schools.com/w3css/tryw3css_templates_mail.htm)
- [w3css 管理面板模板二](https://www.w3schools.com/w3css/tryw3css_templates_analytics.htm)

- [slim3](http://www.slimframework.com)

- [slim3 文档](http://www.slimframework.com/docs/)

- [Slim 3 Skeleton](https://github.com/slimphp/Slim-Skeleton)

- [violin](https://github.com/alexgarrett/violin) 一个便于使用，支持高度定制的 PHP 验证器

- [Illuminate Database component](https://github.com/illuminate/database)

- [eloquent](https://laravel.com/docs/5.8/eloquent)

- [eloquent6.x](https://laravel.com/docs/6.x/eloquent)

- [Laravel Tips ](https://github.com/seekerliu/laravel-tips) 虽然是针对 laravel, 也对很多组件有解释

- [Twig 模板引擎](https://twig.symfony.com/)

- [w3css](https://www.w3schools.com/w3css/default.asp)

- [TinyMCE v4](https://www.tiny.cloud/docs-4x/quick-start/)

- [TinyMCE v4 主题/插件等](https://www.tiny.cloud/get-tiny/custom-builds/)

- [TinyMCE v4 外观(menubar 和 toolbar 等)](https://www.tiny.cloud/docs/configure/editor-appearance/#menubar)

- [TinyMCE 自定义一个 menubar 按钮](https://www.tiny.cloud/docs/demo/custom-toolbar-menu-button/)

- [免费的文件上传管理器组件](https://www.responsivefilemanager.com/demo.php) 可整合到 TinyMCE

- [File and image management plugins for TinyMCE](https://www.tyssendesign.com.au/articles/cms/file-and-image-management-plugins-for-tinymce/#tinybrowser)

- [eloquent-tips-tricks](https://laravel-news.com/eloquent-tips-tricks)

- [ Symfony Console Components documentation](https://symfony.com/doc/current/components/console.html)

- [markdown-js](https://github.com/evilstreak/markdown-js)

- [一个 PHP markdown 解析器](https://packagist.org/packages/league/commonmark)

- [事件分发器](https://symfony.com/doc/current/components/event_dispatcher.html)

- [anti-xss](https://github.com/voku/anti-xss)

- [html-sanitizer](https://github.com/tgalopin/html-sanitizer)

- [Eloquent Slim 3 分页](https://stackoverflow.com/questions/40395805/pagination-with-eloquent-5-3-using-slim-3)

- [Eloquent raw 查询](https://laravel.com/docs/5.8/queries)

- [Symfony 翻译组件](https://symfony.com/doc/current/components/translation.html)

- [Symfony 翻译组件（中文文档）](http://www.symfonychina.com/doc/current/translation.html)

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
## 备份动弹

```

php public/index.php BackupDongDanTask [user_id] [pageToken]
```
