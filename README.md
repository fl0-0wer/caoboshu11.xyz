# caoboshu11.xyz · 个人主页 + 留言板

一个纯手写的静态个人主页（HTML/CSS/JS）配一套 PHP + MySQL 的轻量后端，用来做自我介绍、日常记录、
资源下载和留言打卡。整站没有用任何框架，前后端都是自己一行行写的，部署在宝塔面板的 LNMP 环境上。

线上地址：https://caoboshu11.xyz

## 功能

| 模块 | 说明 |
| --- | --- |
| `index.html` | 首页，横幅大图 + 居中导航 + 分栏内容，纯 CSS 手写布局与 hover 交互 |
| `我的简介.html` | 个人介绍、技能栈、项目经历卡片 |
| `我的日常.html` | 日常记录 / 图集，瀑布式图片墙 |
| `资源下载与留言打卡.html` | 资源下载列表 + 留言板（Ajax 无刷新、管理端操作） |
| `download.php` | 文件下载接口，白名单 + 目录穿越过滤 + 强制 `Content-Disposition` 下载头 |
| `backend_api.php` | 留言板 REST 接口：`get` / `add` / `delete` / `toggle_top` |
| `404.html` | 自定义错误页 |

## 留言板设计

- **接口形态**：`backend_api.php?action=xxx`，统一返回 JSON，前端用 `fetch` 调用，页面不刷新。
- **动作**：拉取列表（置顶优先 + 时间倒序）、发表留言、删除、切换置顶。
- **身份与权限**：访客身份靠 `REMOTE_ADDR` 识别，只能删自己的留言；管理员在页面输入暗号后
  存在 `localStorage`，请求带 `token`，后端比对 `$ADMIN_TOKEN` 才允许删任意留言与置顶。
- **防刷**：同一 IP 每天只能留一条（`DATE(post_time) = CURDATE()` 校验），管理员不受限。
- **数据安全**：返回列表时把 `visitor_ip` 从响应里 `unset` 掉，只回传 `can_manage` 布尔值，
  前端拿不到别人的 IP。
- **排序**：`ORDER BY is_top DESC, post_time DESC`，置顶用 `is_top = 1 - IFNULL(is_top,0)` 翻转，
  兼容历史数据里 `is_top` 为 `NULL` 的行。

## 目录结构

```
.
├── index.html                  首页
├── 我的简介.html                个人介绍
├── 我的日常.html                日常图集
├── 资源下载与留言打卡.html       下载 + 留言板（含全部前端逻辑）
├── backend_api.php             留言板接口
├── download.php                下载接口
├── config.example.php          配置模板（复制为 config.php 使用）
├── downloads/                  可下载文件
├── 个人网页图片/                页面用图
├── .htaccess / .user.ini        Apache / PHP 运行配置
└── 404.html
```

## 本地 / 服务器部署

1. 装 LNMP（宝塔面板一键装 Nginx + PHP 8 + MySQL 5.7/8）。
2. 建库建表：

```sql
CREATE DATABASE pipi_comments_db DEFAULT CHARSET utf8mb4;
USE pipi_comments_db;
CREATE TABLE user_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(50) NOT NULL,
  message_content VARCHAR(500) NOT NULL,
  visitor_ip VARCHAR(64) NOT NULL,
  post_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  is_top TINYINT(1) DEFAULT 0
);
```

3. 把站点文件传到网站根目录，`cp config.example.php config.php` 并填入数据库账号与管理员暗号。
4. 给 `downloads/` 目录读写权限，浏览器打开域名即可。

> `config.php` 已在 `.gitignore` 中排除，真实密码不会进版本库。

## 技术栈

HTML5 / CSS3（Flex 布局、媒体查询）/ 原生 JavaScript（fetch、localStorage、DOM 渲染）/
PHP 8（mysqli、JSON 接口）/ MySQL（utf8mb4）/ Nginx + 宝塔面板部署。
