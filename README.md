# Hi Man

## 相关项目

- [slim3](http://www.slimframework.com)

- [slim3文档](http://www.slimframework.com/docs/)

- [Slim 3 Skeleton](https://github.com/slimphp/Slim-Skeleton)

- [violin](https://github.com/alexgarrett/violin) 一个便于使用，支持高度定制的PHP验证器

- [Illuminate Database component](https://github.com/illuminate/database)

- [eloquent](https://laravel.com/docs/5.8/eloquent)

- [Laravel Tips ](https://github.com/seekerliu/laravel-tips) 虽然是针对laravel, 也对很多组件有解释

- [Twig模板引擎](https://twig.symfony.com/)

- [w3css](https://www.w3schools.com/w3css/default.asp)

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