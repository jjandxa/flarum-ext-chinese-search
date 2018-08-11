# flarum-ext-chinese-search
Flarum 论坛中文搜索插件 - 基于 xunsearch 搜索引擎开发

## 如何安装
### 安装 xunsearch 服务端
参照 [官网指南](http://www.xunsearch.com/doc/php/guide/start.installation)
> 注意安装 xunsearch 需要以下依赖库  
>`gawk make gcc g++ zlib1g-dev`

### 安装插件
`sudo composer require jjandxa/flarum-ext-chinese-search`
> 如果遇到权限问题， 则使用 `sudo` 进行安装, 安装完成后对相关文件权限进行设置

### 其他问题
Flarum 所有文件权限最好是 apache 或 nginx 有权限的用户才行，例如 *www-data* 用户，如果有各种权限问题，就把 flarum 的文件设置为相关的用户和用户组即可
``
sudo chown 
``