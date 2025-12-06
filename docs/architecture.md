## 系统整体概览

- **名称**：奶茶店数字化运营平台（后端接口）
- **技术栈**：Laravel 12 + PHP 8.2、MySQL 8.x、Redis（缓存/队列，可选）、Laravel Sanctum（Token 认证）、Laravel Permission（角色/权限）
- **模块划分**：
  - 管理员模块：公告/促销、用户&权限、经营数据。
  - 店员/店长模块：商品、订单、库存、销售统计。
  - 顾客模块：菜单、下单、配送/自取、会员中心。

## 领域模型（核心数据表）

| 表名 | 说明 | 关键字段 |
| --- | --- | --- |
| `users` | 系统用户（顾客、店员、店长、管理员） | `id, name, email, phone, role, status` |
| `personal_access_tokens` | Sanctum Token 存储 | `tokenable_id, token, expires_at` |
| `roles` / `permissions` / `model_has_roles` / ... | RBAC | 参考 spatie/laravel-permission |
| `members` | 会员扩展信息 | `user_id, level, points, address_book(json)` |
| `announcements` | 公告/促销活动 | `title, content, target_audience, status, start_at, end_at` |
| `categories` | 商品分类 | `name, sort_order, status` |
| `products` | 商品基础信息 | `category_id, name, description, image, base_price, status` |
| `product_options` | 规格/口味选项模板 | `type (sweetness/ice/addon)`, `values(json)` |
| `product_option_values` | 商品可用选项（展平后便于库存/定价） | `product_id, option_type, label, extra_price, is_default` |
| `inventories` | 原料库存 | `item_name, current_qty, threshold, unit` |
| `inventory_logs` | 库存变动记录 | `inventory_id, change_qty, reason, user_id` |
| `orders` | 订单头 | `user_id, code, status, channel, total_amount, pay_status, delivery_type, address_json, eta_at` |
| `order_items` | 订单明细 | `order_id, product_id, quantity, unit_price, options_json` |
| `order_status_histories` | 状态流转 | `order_id, status, operator_id` |
| `payments` | 支付记录 | `order_id, method, amount, transaction_no, status` |
| `carts` / `cart_items` | 临时购物车（可选） | `session_key/user_id` |
| `favorites` | 收藏商品 | `user_id, product_id` |
| `delivery_addresses` | 顾客地址簿（如不放在 members） | `user_id, contact, phone, detail` |
| `sales_reports` (物化视图表，可选) | 预计算统计 | `date, metric, value` |

> 说明：可先实现核心表（users、roles、categories、products、orders、order_items、announcements、inventories），其余按迭代逐步补充。

## REST API 设计原则

- 统一前缀：`/api` + `v1`，区分不同客户端的 Guard（`admin`, `staff`, `customer`）。
- 认证：Sanctum Personal Access Token；顾客通过邮箱/手机号+验证码或密码登录，员工通过账号+密码登录。
- 授权：引入 `spatie/laravel-permission`，角色示例：
  - `admin`：系统超级管理员。
  - `manager`：店长/经理。
  - `staff`：店员。
  - `customer`：顾客。
- 响应格式：`{ code, message, data, meta }`；错误码结合 HTTP Status 与业务码。
- 表单验证：统一 FormRequest；错误信息 JSON 返回。
- 审计：关键操作记录操作人、时间、IP。

## 模块拆解与关键接口

### 1. 认证与账号
- `POST /api/v1/auth/login`（多角色通用，返回 token + 角色信息）
- `POST /api/v1/auth/logout`
- `POST /api/v1/auth/register`（顾客注册）
- `GET /api/v1/auth/profile` / `PUT /api/v1/auth/profile`
- 后台账号管理：`GET/POST/PUT/DELETE /api/v1/admin/users`

### 2. 公告与促销
- `GET /api/v1/announcements`（前台展示，按受众过滤）
- `POST/PUT/DELETE /api/v1/admin/announcements`

### 3. 菜单与商品
- `GET /api/v1/menu/categories`
- `GET /api/v1/menu/products`、`GET /api/v1/menu/products/{id}`
- 后台：`POST/PUT/DELETE /api/v1/staff/products`，支持上下架、库存同步。

### 4. 订单与支付
- `POST /api/v1/orders`（创建订单，包含配送/自取信息）
- `GET /api/v1/orders`、`GET /api/v1/orders/{id}`
- `POST /api/v1/orders/{id}/pay`
- `POST /api/v1/orders/{id}/status`（店员更新：制作中/待取餐/已完成/已取消）
- 实时推送可借助队列+WebSocket（后续迭代）。

### 5. 会员中心
- `GET /api/v1/member/profile`
- `GET /api/v1/member/orders`
- `GET /api/v1/member/favorites`、`POST /api/v1/member/favorites`
- `GET /api/v1/member/points-history`

### 6. 库存与采购
- `GET /api/v1/staff/inventories`
- `PUT /api/v1/staff/inventories/{id}`
- `POST /api/v1/staff/inventories/{id}/adjust`
- 库存低于阈值触发通知（基于事件 + 队列 + 通知）。

### 7. 统计分析
- `GET /api/v1/admin/dashboard`：返回销售额、订单数、热销商品、会员增长。
- `GET /api/v1/admin/reports/sales?from=&to=`
- 后续可利用 Laravel Scout / 自定义 SQL 汇总。

## 技术选型与依赖

| 功能 | 建议 |
| --- | --- |
| 认证 | `laravel/sanctum` |
| RBAC | `spatie/laravel-permission` |
| API 文档 | `laravel-swagger` 或 `knuckleswtf/scribe` |
| 队列/通知 | 内置 queue（database/redis），Email/SMS 根据实际情况 |
| 文件上传 | `laravel/filesystem` + OSS 或本地 |
| 测试 | Pest 或 PHPUnit，覆盖核心流程 |

## 开发节奏建议

1. **基础设施**：Sanctum、Permission、用户与角色迁移、Seeder、基类响应。
2. **顾客模块**：菜单、下单、订单查询、会员中心。
3. **后台模块**：商品管理、订单流转、库存管理。
4. **管理员模块**：公告、用户管理、统计报表。
5. **非功能需求**：日志、缓存、队列、监控、API 文档、自动化测试。

## 后续待办（后端）

- 设计 E-R 图并完成全部迁移文件。
- 编写数据库 Seeder（示例商品/用户）。
- 落地模块化路由与控制器骨架。
- 接入 Sanctum + Permission，并实现多角色认证。
- 撰写接口文档 & 单元测试。

