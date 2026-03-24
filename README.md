# v7.nswin.cn

一个基于 **PHP 8.2** 的下载站与后台管理项目。

当前已完成：
- 前台首页白天 / 黑夜切换
- 后台导航可收缩
- 后台主要页面响应式适配
- 安装页 / 后台入口 / 下载管理 / 内容管理 / 公告管理 / 密码修改 / 统计页接通

---

## 目录结构

```text
/www/wwwroot/v7.nswin.cn
├── index.php                     # 前台首页
├── download.php                  # 下载跳转与统计入口
├── install/                      # 安装页
├── admin/                        # 后台访问壳目录（实际入口）
├── system/admin-core/            # 后台核心代码
├── config/install.php            # 站点核心配置
├── storage/                      # 安装锁 / 下载统计等运行数据
├── .trash/                       # 保守删除后的临时回收区
└── .gitignore                    # Git 忽略规则
```

---

## 关键说明

### 1. 技术基线
- PHP 8.2
- 配置驱动
- 后台采用入口壳 + `system/admin-core/` 核心目录结构

### 2. 后台入口
后台入口目录由配置项控制：
- `site.admin_slug`

默认可能是：
- `/admin/`
- `/manage/`
- 其他自定义目录

### 3. 下载统计
前台下载通过：
- `download.php`

先计数，再跳转到实际下载地址。

### 4. 删除策略
项目当前遵循保守删除策略：
- 优先移入 `.trash/`
- 不优先硬删除

---

## 当前已完成的前台特性

- 首页右上角明暗切换按钮
- 默认白天模式
- 本地记忆主题选择
- reduced-motion 动效兼容
- 首页顶部按钮精简
- 主视觉区只保留前台操作入口

---

## 当前已完成的后台特性

- 侧边栏可收缩
- 记住侧边栏收缩状态
- 移动端抽屉导航
- 收缩态 tooltip
- 统一顶部栏布局
- 仪表盘 / 设置 / 下载 / 内容 / 公告 / 密码 / 统计页响应式适配

---

## Git 节点

当前关键提交：

- `84cd0a8`  
  `Stabilize frontend theme toggle and responsive admin UI`

- `09c69c8`  
  `Ignore runtime artifacts and cleanup tracked temp files`

---

## 注意事项

- `.trash/`、ACME challenge、日志/临时文件不建议长期纳入版本控制
- 配置文件位于 `config/install.php`
- 修改 PHP 文件后建议执行：

```bash
php -l 文件路径
```

---

## 当前状态

这是一个已经完成一轮前后台整理、可继续迭代的稳定基础版本。
