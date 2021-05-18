# HiCMS

- [演示网站](https://hi.cellmean.com)

- 一个简单实用的CMS，最早是为小小编辑写的乱弹定时发布网站。支持将文章定时发布，同步到`oschina`博客，支持动弹备份。

- 同时本项目作为本人想法和技术的实验场，个人化风格明显，充斥着各种不成熟的想法和实现，请谨慎使用代码，后果自负。👻

## 配置

 将 `app/settings.sample.php` 复制到 `app/settings.php` ，修改相应的选项

以下目录需要开启 *写入* 权限

- log/
- cache/
- media/




## 相关项目

- [w3css 管理面板模板一](https://www.w3schools.com/w3css/tryw3css_templates_mail.htm)
- [w3css 管理面板模板二](https://www.w3schools.com/w3css/tryw3css_templates_analytics.htm)

- [slim3](http://www.slimframework.com)

- [slim3 文档](http://www.slimframework.com/docs/)

- [Slim 3 Skeleton](https://github.com/slimphp/Slim-Skeleton)

- [violin](https://github.com/alexgarrett/violin) 一个便于使用，支持高度定制的 PHP 验证器

- [Illuminate Database component](https://github.com/illuminate/database)

- [eloquent](https://laravel.com/docs/5.8/eloquent)

- [eloquent6.x](https://learnku.com/docs/laravel/6.x/eloquent/5176)


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

- [Schema Builder 数据库类型](https://laravel.com/docs/5.0/schema)

- [Schema Builder 添加/删除索引](http://laravelhowto.blogspot.com/2017/04/how-to-add-and-drop-indexes-in-laravel.html)

- [Symfony 翻译组件](https://symfony.com/doc/current/components/translation.html)

- [Symfony 翻译组件（中文文档）](http://www.symfonychina.com/doc/current/translation.html)

- [Guzzle6 文档(并发请求)](http://docs.guzzlephp.org/en/stable/quickstart.html#concurrent-requests)

- [ stackoverflow Guzzle并发请求示例 ](https://stackoverflow.com/questions/53271764/how-to-perform-concurrent-requests-with-guzzlehttp/53272469)

- [关于laraval 和Eloquent各种 howto的网站](http://laravelhowto.blogspot.com/)

- [搜狗机器翻译API](https://deepi.sogou.com/)

- [Prism在线选项](https://prismjs.com/download.html#themes=prism-okaidia&languages=markup+css+clike+javascript+c+go+java+lua+markup-templating+perl+php+python+ruby+twig&plugins=line-highlight+line-numbers+toolbar+copy-to-clipboard)

- [twig1.x文档 readthedocs](https://twig.readthedocs.io/en/1.x/advanced_legacy.html)

- [w3css年度流行配色](https://www.w3schools.com/w3css/w3css_color_libraries.asp)

- [Eloquent 一对一，一对多，多对多关系](https://learnku.com/docs/laravel/5.5/eloquent-relationships/1333#one-to-many)

- [基于 Laravel + Vue 组件实现文件异步上传](https://laravelacademy.org/post/9677)

- [PHP图像处理类，非常强大](http://image.intervention.io/)

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

## 输出Eloquent / ORM的sql

```
function getSQL($builder) {
    $sql = $builder->toSql();
    foreach ( $builder->getBindings() as $binding ) {
        $value = is_numeric($binding) ? $binding : "'".$binding."'";
        $sql = preg_replace('/\?/', $value, $sql, 1);
    }
    return $sql;
}

用法:
$tweet = Post::where('post_name','6276890')->limit(1)->get();

//先转成这样的形式,因为get()方法后得到的是Collection对象，所以必须在这之前截取出Buider对象，去除get
$tweetBuider = Post::where('post_name','6276890')->limit(1);
echo $this->getSQL($tweetBuider);

//目前getSql作为HelperTrait的一个方法，在Action里可用
echo $this->getSQL($tweetBuider);

```
## 添加了Post和PostMeta表的一对多和多对多关系
```

$tweet = Post::where('post_name',$tweetId)->first();
if(!is_null($tweet)){
   $tweetDatas = $tweet->metas('meta_key','like','tweet%')->get();
}

var_dump(  $tweetDatas );

$post = PostMeta::find(8025)->post()->first();
var_dump($post);


```        


### 备份动弹

参数:
- userId=12&pageToken=DBA816934CD0AA59&forceUpdate=0
- forceUpdate 强制更新所有动弹，否则只更新未入库的动弹

```
php public/index.php BackupDongDan [args]
```

### 备份动弹评论/精彩评论/点赞

参数：
- userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10
- tweetId=123456 特定动弹id
```
php public/index.php BackupDongDanComments [args]
```

### 备份动弹图片和评论/点赞者头像
- userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10
- tweetId=123456 特定动弹id
```
php public/index.php BackupDongDanImages [args]
```

### 更新旧osc静态文件服务器50x50的头像为200x200的，部分404

```
php public/index.php UpdateDongDanOldImages
```


带 热门评论 like /comments的动弹id(不带图)

`21147461`

带图/评论/likes的动弹id（不带热门评论)

`12620768`

### twig 不解析模板内容直接引入 ,相当于 `file_get_contents` 的效果

Twig 1.15  以上可用

```
{{ source('imagely/item-tmpl-js.html') }} 
```

<https://twig.symfony.com/doc/3.x/functions/source.html>

### 自动部署

- 2020.07.08
今天用`nodemon`和`git webhook`做了一个监控`git push`后在服务器执行自动更新的小玩意，这样就简单实现自动部署了
用了`systemd`自动运行失败，目前暂时使用`screen`后台运行.


### 多级嵌套 `where` ，需要加 ( ) 的方法：

<https://stackoverflow.com/questions/30434037/laravel-5-eloquent-where-and-or-in-clauses>

可以参考 `SearchAction::index` 的实现

### 搜索功能
- 2020.07.09
加入搜索功能，可以搜索文章和动弹，目前简单使用 `like` 模糊查询，之后可能使用全文索引。


### 获取当前页面路由名称并传到模板

```
$args['route'] = $request->getAttribute('route')->getName();
return $this->view->render($response,'test.twig', $args);
```

### `Twig` 扩展加入一个判断变量是否为数组的`Test`

```
public function getTests(){
        return [
            new \Twig_SimpleTest('array', function ($value) {
                return is_array($value);
            })
        ];
    }
```    
模板调用 
```
{% if myVar is array %}
...
{% endif %}
```
内置Test,判断空值

```
is null checks whether the value is null:

{% if var is null %}
    {# do something #}
{% endif %}
is defined checks whether the variable is defined:

{% if var is not defined %}
    {# do something #}
{% endif %}
Additionally the is sameas test, which does a type strict comparison of two values, might be of interest for checking values other than null (like false):

{% if var is sameas(false) %}
    {# do something %}
{% endif %}
```

### 加入在`modal`中查看大图的功能
2020.07.12
@todo: 长图（高度超过一屏的图片查看会有问题) 


### twig 核心扩展 ，filter 和 function

`vendor/twig/twig/src/Extension/CoreExtension.php`

### 加入展示动弹评论和部分元信息的详情页
2020.07.18
第一次使用`grid`布局


### 将动弹内的 url字符串转换成可以在新窗口打开的 `a`标签，同时保留动弹原有链接。
2020.07.21

问题：<https://stackoverflow.com/questions/1188129/replace-urls-in-text-with-html-links>

最终采用的答案：<https://stackoverflow.com/posts/40039631/revisions>

做了一个函数`makeLinks`和一个同名`twig filter`。

### 迁移到coding 私密仓库,github的webhook失败率太高， 导致服务器经常更新失败
2020.07.23
测试一下 webhook 同步


### todo

[拖拽上传文件](https://css-tricks.com/drag-and-drop-file-uploading/)


