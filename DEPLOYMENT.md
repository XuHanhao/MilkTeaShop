# Vue 前端部署到 Laravel 指南

本指南说明如何将 Vue 前端项目打包后部署到 Laravel 后端。

## 当前配置

本项目已配置支持**两个前端应用**：
- **管理后台**：访问路径 `/admin` 或 `/admin/*`
- **H5 移动端**：访问路径 `/` 或除 `/admin`、`/api` 外的其他路径

## 部署步骤

### 1. 打包 Vue 前端项目

分别在两个前端项目目录下运行：

```bash
# 管理后台项目
cd ../admin-frontend
npm run build

# H5 移动端项目
cd ../h5-frontend
npm run build
```

这会在各自项目根目录生成 `dist` 目录，包含所有打包后的静态文件。

### 2. 复制文件到 Laravel public 目录

将两个 Vue 项目的 `dist` 目录内容分别复制到 Laravel 的 `public` 目录下的对应子目录：

**Windows (PowerShell):**
```powershell
# 复制管理后台到 public/admin
Copy-Item -Path "../admin-frontend/dist/*" -Destination "./public/admin/" -Recurse -Force

# 复制 H5 移动端到 public/h5
Copy-Item -Path "../h5-frontend/dist/*" -Destination "./public/h5/" -Recurse -Force
```

**Linux/Mac:**
```bash
# 复制管理后台到 public/admin
cp -r ../admin-frontend/dist/* ./public/admin/

# 复制 H5 移动端到 public/h5
cp -r ../h5-frontend/dist/* ./public/h5/
```

### 3. 配置 Vue Router（如果使用）

#### 管理后台项目

在管理后台项目的 `router/index.js` 中配置 base 路径：

```javascript
const router = createRouter({
  history: createWebHistory('/admin'), // 注意：base 路径为 /admin
  routes: [...]
})
```

#### H5 移动端项目

在 H5 项目的 `router/index.js` 中：

```javascript
const router = createRouter({
  history: createWebHistory('/'), // 根路径
  routes: [...]
})
```

### 4. 配置 API 基础 URL

确保两个 Vue 项目中的 API 请求指向正确的后端地址。通常需要配置：

- 开发环境：`http://localhost:8000/api`
- 生产环境：根据实际部署地址配置

### 5. 测试部署

启动 Laravel 开发服务器：

```bash
php artisan serve
```

访问测试：
- **管理后台**：`http://localhost:8000/admin`
- **H5 移动端**：`http://localhost:8000`

## 目录结构

部署后的 `public` 目录结构应该类似：

```
public/
├── index.php          # Laravel 入口文件（保留）
├── admin/             # 管理后台应用
│   ├── index.html
│   └── assets/
│       ├── index-xxx.js
│       └── index-xxx.css
├── h5/                # H5 移动端应用
│   ├── index.html
│   └── assets/
│       ├── index-xxx.js
│       └── index-xxx.css
├── favicon.ico
└── robots.txt
```

## 路由说明

Laravel 路由配置（`routes/web.php`）已自动处理：

1. **管理后台路由**：`/admin` 和 `/admin/*` 会返回 `public/admin/index.html`，并自动修正资源路径
2. **H5 移动端路由**：根路径 `/` 和其他非 `/admin`、`/api` 的路径会返回 `public/h5/index.html`，并自动修正资源路径
3. **API 路由**：所有 `/api/*` 开头的请求会被 Laravel 的 API 路由处理（`routes/api.php`），不会被前端路由捕获
4. **静态资源**：Laravel 会自动从 `public` 目录提供静态资源文件

## 注意事项

1. **资源路径修正**：路由会自动修正 HTML 中的资源路径，将 `/assets/` 替换为 `/admin/assets/` 或 `/h5/assets/`，无需手动修改 HTML 文件。

2. **Vue Router base 配置**：
   - 管理后台必须设置 `base: '/admin'`
   - H5 移动端可以设置 `base: '/'` 或不设置

3. **API 路由**：所有 `/api/*` 开头的请求会被 Laravel 的 API 路由处理，不会被前端路由捕获。

4. **CORS 配置**：如果前后端分离部署，可能需要配置 CORS。Laravel 已经安装了 `fruitcake/laravel-cors`，可以在 `config/cors.php` 中配置。

5. **生产环境**：在生产环境中，建议使用 Nginx 或 Apache 等 Web 服务器，而不是 `php artisan serve`。

## 常见问题

### Q: 访问应用时显示 404 错误
A: 确保已将两个前端项目的 `dist` 目录内容分别复制到 `public/admin/` 和 `public/h5/` 目录。

### Q: 静态资源（JS、CSS）加载失败
A: 路由会自动修正资源路径，如果仍有问题，检查：
1. 文件是否已正确复制到对应目录
2. 文件权限是否正确
3. Web 服务器配置是否正确

### Q: Vue Router 路由不工作
A: 确保在 Vue 项目中正确配置了 `base` 路径：
- 管理后台：`createWebHistory('/admin')`
- H5 移动端：`createWebHistory('/')`

