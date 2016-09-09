# Typecho2Pelican

很有意思的一个Typecho插件，主要功能是新建和编辑文章时生成符合Pelican要求的md文件。如果你有自己的vps的话，可以这个插件配合Pelican生成静态博客。

## How to use

最简单的方法是到Typecho的插件目录usr/plugins/下执行：
```
git clone git@github.com:learso/Typecho2Pelican.git
```

到Typecho后台启用插件，然后修改参数：

`pelican内容目录`设定的是生成md文件的目录，根目录是Typecho的安装目录，默认是content。
```
cd path/to/your/pelican/
rm -rf content
ln -s path/to/your/typecho/content content
```
这样设置后就基本可以运行了，每次新增或修改完文章后，到pelican目录下执行：
```
make html
```

> pelican的具体配置可以参考[Pelican Document](http://docs.getpelican.com/en/3.6.3/)。
