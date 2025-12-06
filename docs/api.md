# 奶茶店后端 API 文档（V1）

> **基础信息**
> - 基础 URL：`http://127.0.0.1:8000/api/v1`
> - 认证方式：Laravel Sanctum（Bearer Token）
> - 请求头：`Authorization: Bearer {token}`、`Content-Type: application/json`、`Accept: application/json`
> - 统一返回格式：`{ "code": 0, "message": "xxx", "data": {...} }`
> - 角色权限：`admin`、`manager`、`staff`、`customer`

---

## 1. 认证与账号

### 1.1 顾客注册

**接口**：`POST /api/v1/auth/register`

**认证**：无需认证

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `name` | string | 是 | 姓名 | max:255 |
| `email` | string | 否* | 邮箱 | email, max:255, unique:users,email |
| `phone` | string | 否* | 手机号 | max:20, unique:users,phone |
| `password` | string | 是 | 密码 | min:6 |
| `password_confirmation` | string | 是 | 确认密码 | 需与 password 一致 |

\* `email` 和 `phone` 至少填写一个

**请求示例**：
```json
{
  "name": "张三",
  "email": "zhangsan@example.com",
  "phone": "13800138000",
  "password": "123456",
  "password_confirmation": "123456"
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "1|xxx...",
    "user": {
      "id": 1,
      "name": "张三",
      "email": "zhangsan@example.com",
      "phone": "13800138000",
      "avatar": null,
      "type": "customer",
      "status": "active",
      "balance": "0.00",
      "email_verified_at": null,
      "phone_verified_at": null,
      "last_login_at": null,
      "meta": null,
      "created_at": "2025-11-20T13:16:17.000000Z",
      "updated_at": "2025-11-20T13:16:17.000000Z",
      "deleted_at": null,
      "roles": [
        {
          "id": 4,
          "name": "customer",
          "guard_name": "sanctum",
          "created_at": "...",
          "updated_at": "...",
          "pivot": {
            "model_type": "App\\Models\\User",
            "model_id": 1,
            "role_id": 4
          }
        }
      ]
    }
  }
}
```

---

### 1.2 登录

**接口**：`POST /api/v1/auth/login`

**认证**：无需认证

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `account` | string | 是 | 账号（邮箱或手机号） | - |
| `password` | string | 是 | 密码 | - |

**请求示例**：
```json
{
  "account": "admin@milktea.local",
  "password": "Admin@123"
}
```

**返回数据结构**：同注册接口，包含 `token` 和 `user` 对象（含 `roles` 数组）

---

### 1.3 退出登录

**接口**：`POST /api/v1/auth/logout`

**认证**：需要 Token

**请求参数**：无

**返回数据结构**：
```json
{
  "code": 0,
  "message": "已退出",
  "data": null
}
```

---

### 1.4 获取个人信息

**接口**：`GET /api/v1/auth/profile`

**认证**：需要 Token

**请求参数**：无

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "id": 1,
    "name": "超级管理员",
    "email": "admin@milktea.local",
    "phone": null,
    "avatar": null,
    "type": "admin",
    "status": "active",
    "email_verified_at": null,
    "phone_verified_at": null,
    "last_login_at": "2025-11-20T13:23:28.000000Z",
    "meta": null,
    "created_at": "2025-11-20T13:16:17.000000Z",
    "updated_at": "2025-11-20T13:23:28.000000Z",
    "deleted_at": null,
    "roles": [...]
  }
}
```

---

### 1.5 更新个人信息

**接口**：`PUT /api/v1/auth/profile`

**认证**：需要 Token

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `name` | string | 否 | 姓名 | max:255 |
| `phone` | string | 否 | 手机号 | max:20, unique:users,phone (排除当前用户) |
| `avatar` | string | 否 | 头像URL | max:255 |

**请求示例**：
```json
{
  "name": "新名字",
  "phone": "13900139000",
  "avatar": "https://example.com/avatar.jpg"
}
```

**返回数据结构**：同获取个人信息接口

---

## 2. 公告与促销

### 2.1 获取公告列表（公开）

**接口**：`GET /api/v1/announcements`

**认证**：可选（未登录默认按 customer 过滤）

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `page` | integer | 否 | 页码，默认 1 |
| `per_page` | integer | 否 | 每页数量，默认 10 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "新品上市",
        "content": "xxx",
        "target_audience": "all",
        "status": "published",
        "start_at": "2025-11-20T00:00:00.000000Z",
        "end_at": "2025-12-20T23:59:59.000000Z",
        "created_by": 1,
        "created_at": "...",
        "updated_at": "..."
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

---

### 2.2 获取公告列表（管理员）

**接口**：`GET /api/v1/admin/announcements`

**认证**：需要 Token + `admin` 角色

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `status` | string | 否 | 状态：draft/published/archived |
| `per_page` | integer | 否 | 每页数量，默认 15 |

**返回数据结构**：同公开接口，但包含所有状态的公告

---

### 2.3 创建公告

**接口**：`POST /api/v1/admin/announcements`

**认证**：需要 Token + `admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `title` | string | 是 | 标题 | max:255 |
| `content` | string | 是 | 内容 | - |
| `target_audience` | string | 是 | 受众 | in:all,customer,staff,manager |
| `status` | string | 是 | 状态 | in:draft,published,archived |
| `start_at` | datetime | 否 | 开始时间 | date |
| `end_at` | datetime | 否 | 结束时间 | date, after_or_equal:start_at |

**请求示例**：
```json
{
  "title": "新品上市",
  "content": "xxx",
  "target_audience": "all",
  "status": "published",
  "start_at": "2025-11-20 00:00:00",
  "end_at": "2025-12-20 23:59:59"
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "公告已创建",
  "data": {
    "id": 1,
    "title": "新品上市",
    "content": "xxx",
    "target_audience": "all",
    "status": "published",
    "start_at": "2025-11-20T00:00:00.000000Z",
    "end_at": "2025-12-20T23:59:59.000000Z",
    "created_by": 1,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

---

### 2.4 更新公告

**接口**：`PUT /api/v1/admin/announcements/{id}`

**认证**：需要 Token + `admin` 角色

**请求参数**：同创建接口，所有字段均为可选（`sometimes`）

---

### 2.5 删除公告

**接口**：`DELETE /api/v1/admin/announcements/{id}`

**认证**：需要 Token + `admin` 角色

**请求参数**：无（路径参数 `id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "公告已删除",
  "data": null
}
```

---

## 3. 菜单与商品

### 3.1 获取分类列表

**接口**：`GET /api/v1/menu/categories`

**认证**：无需认证

**请求参数**：无

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": [
    {
      "id": 1,
      "name": "奶茶",
      "slug": "milktea",
      "status": "active",
      "sort_order": 1,
      "created_at": "...",
      "updated_at": "...",
      "products": [
        {
          "id": 1,
          "name": "珍珠奶茶",
          "base_price": "15.00",
          "status": "active",
          ...
        }
      ]
    }
  ]
}
```

---

### 3.2 分类管理（后台）

**接口前缀**：`/api/v1/staff/categories`

**认证**：需要 Token + `staff|manager|admin` 角色

#### 3.2.1 创建分类

**接口**：`POST /api/v1/staff/categories`

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `name` | string | 是 | 分类名称 | max:255 |
| `slug` | string | 否 | URL 别名 | max:255, unique:categories,slug |
| `sort_order` | integer | 否 | 排序 | integer, min:0 |
| `status` | string | 否 | 状态 | in:active,inactive |

#### 3.2.2 更新分类

**接口**：`PUT /api/v1/staff/categories/{id}`

**请求参数**：同创建接口，字段均为可选

#### 3.2.3 删除分类

**接口**：`DELETE /api/v1/staff/categories/{id}`

**请求参数**：无（路径参数 `id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "分类已删除",
  "data": null
}
```

---

### 3.3 获取商品列表

**接口**：`GET /api/v1/menu/products`

**认证**：无需认证

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `category_id` | integer | 否 | 分类ID |
| `only_active` | boolean | 否 | 仅显示在售，默认 true |
| `per_page` | integer | 否 | 每页数量，默认 15 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "category_id": 1,
        "name": "珍珠奶茶",
        "slug": "pearl-milktea-xxx",
        "image": null,
        "description": "xxx",
        "base_price": "15.00",
        "stock": 100,
        "status": "active",
        "options": null,
        "sort_order": 0,
        "created_at": "...",
        "updated_at": "...",
        "optionValues": [
          {
            "id": 1,
            "product_id": 1,
            "type": "sweetness",
            "label": "少糖",
            "extra_price": "0.00",
            "is_default": false,
            "sort_order": 1
          }
        ]
      }
    ],
    "per_page": 15,
    "total": 10
  }
}
```

---

### 3.3 获取商品详情

**接口**：`GET /api/v1/menu/products/{id}`

**认证**：无需认证

**请求参数**：无（路径参数 `id`）

**返回数据结构**：同商品列表中的单个商品对象

---

### 3.4 创建商品

**接口**：`POST /api/v1/staff/products`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `category_id` | integer | 否 | 分类ID | exists:categories,id |
| `name` | string | 是 | 商品名称 | max:255 |
| `slug` | string | 否 | URL别名 | max:255, unique:products,slug |
| `image` | string | 否 | 图片URL | max:255 |
| `description` | string | 否 | 描述 | - |
| `base_price` | decimal | 是 | 基础价格 | numeric, min:0 |
| `stock` | integer | 否 | 库存 | integer, min:0 |
| `status` | string | 否 | 状态 | in:draft,active,inactive |
| `options` | array | 否 | 选项模板（JSON） | array |
| `sort_order` | integer | 否 | 排序 | integer, min:0 |
| `option_values` | array | 否 | 规格选项数组 | array |

**`option_values` 数组项结构**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `type` | string | 是 | 类型 | in:sweetness,ice,size,addon |
| `label` | string | 是 | 标签 | max:255 |
| `extra_price` | decimal | 否 | 加价 | numeric, min:0 |
| `is_default` | boolean | 否 | 是否默认 | boolean |
| `sort_order` | integer | 否 | 排序 | integer, min:0 |

**请求示例**：
```json
{
  "category_id": 1,
  "name": "珍珠奶茶",
  "base_price": 15.00,
  "stock": 100,
  "status": "active",
  "option_values": [
    {
      "type": "sweetness",
      "label": "少糖",
      "extra_price": 0,
      "is_default": false,
      "sort_order": 1
    },
    {
      "type": "ice",
      "label": "少冰",
      "extra_price": 0,
      "is_default": false,
      "sort_order": 1
    }
  ]
}
```

**返回数据结构**：同商品详情，包含新创建的 `optionValues`

---

### 3.5 更新商品

**接口**：`PUT /api/v1/staff/products/{id}`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：同创建接口，所有字段均为可选（`sometimes`）

**注意**：如果提供了 `option_values`，会删除原有规格并重新创建

---

### 3.6 删除商品

**接口**：`DELETE /api/v1/staff/products/{id}`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：无（路径参数 `id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "商品已删除",
  "data": null
}
```

---

## 4. 会员中心

### 4.1 获取余额

**接口**：`GET /api/v1/member/balance`

**认证**：需要 Token

**请求参数**：无

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "balance": 100.5
  }
}
```

---

### 4.2 余额充值（演示用）

**接口**：`POST /api/v1/member/balance/recharge`

**认证**：需要 Token

> 说明：仅用于演示充值，不接入真实支付渠道。

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `amount` | decimal | 是 | 充值金额 | numeric, min:0.01 |

**请求示例**：
```json
{
  "amount": 50
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "余额已充值",
  "data": {
    "balance": 150.5
  }
}
```

---

### 4.3 获取收藏列表

**接口**：`GET /api/v1/member/favorites`

**认证**：需要 Token

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `page` | integer | 否 | 页码，默认 1 |
| `per_page` | integer | 否 | 每页数量，默认 15 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "product_id": 1,
        "created_at": "...",
        "updated_at": "...",
        "product": {
          "id": 1,
          "name": "珍珠奶茶",
          "base_price": "15.00",
          ...
        }
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

---

### 4.2 添加收藏

**接口**：`POST /api/v1/member/favorites`

**认证**：需要 Token

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `product_id` | integer | 是 | 商品ID | exists:products,id |

**请求示例**：
```json
{
  "product_id": 1
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "已收藏",
  "data": {
    "id": 1,
    "user_id": 1,
    "product_id": 1,
    "created_at": "...",
    "updated_at": "...",
    "product": {...}
  }
}
```

---

### 4.3 取消收藏

**接口**：`DELETE /api/v1/member/favorites/{product_id}`

**认证**：需要 Token

**请求参数**：无（路径参数 `product_id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "已取消收藏",
  "data": null
}
```

---

## 5. 顾客订单

### 5.1 获取订单列表

**接口**：`GET /api/v1/orders`

**认证**：需要 Token

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `per_page` | integer | 否 | 每页数量，默认 10 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "code": "NOXXXXXXXX",
        "user_id": 1,
        "status": "pending",
        "delivery_type": "pickup",
        "pay_status": "unpaid",
        "channel": "web",
        "subtotal_amount": "30.00",
        "discount_amount": "0.00",
        "delivery_fee": "0.00",
        "total_amount": "30.00",
        "delivery_address": null,
        "notes": null,
        "eta_at": null,
        "paid_at": null,
        "created_at": "...",
        "updated_at": "...",
        "items": [
          {
            "id": 1,
            "order_id": 1,
            "product_id": 1,
            "product_name": "珍珠奶茶",
            "product_image": "https://example.com/images/pearl-milktea.jpg",
            "quantity": 2,
            "unit_price": "15.00",
            "total_price": "30.00",
            "selected_options": [1, 2],
            "created_at": "...",
            "updated_at": "..."
          }
        ]
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

---

### 5.2 获取订单详情

**接口**：`GET /api/v1/orders/{id}`

**认证**：需要 Token（仅能查看自己的订单）

**请求参数**：无（路径参数 `id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "id": 1,
    "code": "NOXXXXXXXX",
    "user_id": 1,
    "status": "pending",
    "delivery_type": "pickup",
    "pay_status": "unpaid",
    "channel": "web",
    "subtotal_amount": "30.00",
    "discount_amount": "0.00",
    "delivery_fee": "0.00",
    "total_amount": "30.00",
    "delivery_address": null,
    "notes": null,
    "eta_at": null,
    "paid_at": null,
    "created_at": "...",
    "updated_at": "...",
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "product_name": "珍珠奶茶",
        "product_image": "https://example.com/images/pearl-milktea.jpg",
        "quantity": 2,
        "unit_price": "15.00",
        "total_price": "30.00",
        "selected_options": [1, 2],
        "created_at": "...",
        "updated_at": "..."
      }
    ],
    "histories": [
      {
        "id": 1,
        "order_id": 1,
        "status": "pending",
        "operator_id": null,
        "remark": "顾客提交订单",
        "created_at": "...",
        "updated_at": "..."
      }
    ],
    "payments": []
  }
}
```

---

### 5.3 创建订单

**接口**：`POST /api/v1/orders`

**认证**：需要 Token

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `delivery_type` | string | 是 | 配送方式 | in:pickup,delivery |
| `delivery_address` | object | 否 | 配送地址（JSON对象） | array |
| `notes` | array | 否 | 备注（JSON数组） | array |
| `items` | array | 是 | 订单项数组 | array, min:1 |
| `items.*.product_id` | integer | 是 | 商品ID | exists:products,id |
| `items.*.quantity` | integer | 是 | 数量 | integer, min:1 |
| `items.*.selected_options` | array | 否 | 选中的规格ID数组 | array |
| `pay_with_balance` | boolean | 否 | 是否使用余额支付 | boolean |

**请求示例**：
```json
{
  "delivery_type": "pickup",
  "delivery_address": {
    "contact": "张三",
    "phone": "13800138000",
    "detail": "北京市朝阳区xxx"
  },
  "notes": ["少冰", "打包"],
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "selected_options": [1, 2]
    }
  ],
  "pay_with_balance": true
}
```

**返回数据结构**：同订单详情（包含 `items`）

**说明**：
- 系统自动计算单价（基础价格 + 选中规格的加价）
- 自动生成订单号（格式：`NO` + 8位随机大写字母）
- 配送方式为 `delivery` 时，配送费为 5 元；`pickup` 为 0 元
- 自动创建订单状态历史记录
- 当 `pay_with_balance = true` 时，若余额充足，则直接扣减余额并将订单标记为已支付；余额不足时返回验证错误
- **商品图片**：系统会自动保存下单时商品的图片地址到订单项中（`product_image` 字段），确保即使商品图片后续被修改或删除，订单历史记录中的图片仍然可以正常显示
- **库存扣减**：创建订单时会锁定商品记录并立即扣减库存，若库存不足会返回验证错误

---

## 6. 店员/店长后台

### 6.1 获取订单列表（后台）

**接口**：`GET /api/v1/staff/orders`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `status` | string | 否 | 订单状态：pending/confirmed/preparing/ready/completed/cancelled |
| `per_page` | integer | 否 | 每页数量，默认 20 |

**返回数据结构**：同顾客订单列表，但包含 `customer` 关联（用户信息）

---

### 6.2 更新订单状态

**接口**：`POST /api/v1/staff/orders/{id}/status`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `status` | string | 是 | 新状态 | in:confirmed,preparing,ready,completed,cancelled |
| `remark` | string | 否 | 备注 | max:255 |

**请求示例**：
```json
{
  "status": "preparing",
  "remark": "正在制作中"
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "订单状态已更新",
  "data": {
    "id": 1,
    "code": "NOXXXXXXXX",
    "status": "preparing",
    ...
  }
}
```

**说明**：会自动创建订单状态历史记录，记录操作人（`operator_id`）

---

### 6.3 获取库存列表

**接口**：`GET /api/v1/staff/inventory`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `per_page` | integer | 否 | 每页数量，默认 20 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "item_name": "珍珠",
        "unit": "kg",
        "current_qty": 50.00,
        "threshold_qty": 10.00,
        "created_at": "...",
        "updated_at": "...",
        "logs": [
          {
            "id": 1,
            "inventory_id": 1,
            "user_id": 1,
            "change_qty": 10.00,
            "reason": "采购入库",
            "created_at": "...",
            "updated_at": "..."
          }
        ]
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

---

### 6.4 创建库存项

**接口**：`POST /api/v1/staff/inventory`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `item_name` | string | 是 | 物品名称 | max:255 |
| `unit` | string | 否 | 单位 | max:10 |
| `current_qty` | decimal | 是 | 当前数量 | numeric, min:0 |
| `threshold_qty` | decimal | 否 | 预警阈值 | numeric, min:0 |

**请求示例**：
```json
{
  "item_name": "珍珠",
  "unit": "kg",
  "current_qty": 50.00,
  "threshold_qty": 10.00
}
```

**返回数据结构**：同库存列表中的单个库存项（不含 `logs`）

---

### 6.5 调整库存

**接口**：`POST /api/v1/staff/inventory/{id}/adjust`

**认证**：需要 Token + `staff|manager|admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `change_qty` | decimal | 是 | 变动数量（可正可负） | numeric |
| `reason` | string | 否 | 原因 | max:255 |

**请求示例**：
```json
{
  "change_qty": -5.00,
  "reason": "使用消耗"
}
```

**返回数据结构**：
```json
{
  "code": 0,
  "message": "库存已调整",
  "data": {
    "id": 1,
    "item_name": "珍珠",
    "unit": "kg",
    "current_qty": 45.00,
    "threshold_qty": 10.00,
    "logs": [...]
  }
}
```

**说明**：会自动更新 `current_qty`，并创建库存变动日志记录

---

## 7. 管理员模块

### 7.1 获取用户列表

**接口**：`GET /api/v1/admin/users`

**认证**：需要 Token + `admin` 角色

**请求参数（Query）**：

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `keyword` | string | 否 | 搜索关键词（姓名/邮箱/手机号） |
| `type` | string | 否 | 用户类型：customer/staff/manager/admin |
| `per_page` | integer | 否 | 每页数量，默认 15 |

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "超级管理员",
        "email": "admin@milktea.local",
        "phone": null,
        "avatar": null,
        "type": "admin",
        "status": "active",
        "email_verified_at": null,
        "phone_verified_at": null,
        "last_login_at": "...",
        "meta": null,
        "created_at": "...",
        "updated_at": "...",
        "deleted_at": null,
        "roles": [...]
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

---

### 7.2 创建用户账号

**接口**：`POST /api/v1/admin/users`

**认证**：需要 Token + `admin` 角色

**请求参数**：

| 参数名 | 类型 | 必填 | 说明 | 验证规则 |
| --- | --- | --- | --- | --- |
| `name` | string | 是 | 姓名 | max:255 |
| `email` | string | 否 | 邮箱 | email, max:255, unique:users,email |
| `phone` | string | 否 | 手机号 | max:20, unique:users,phone |
| `type` | string | 是 | 用户类型 | in:customer,staff,manager,admin |
| `status` | string | 是 | 状态 | in:active,inactive,suspended |
| `password` | string | 是 | 密码 | min:6 |
| `roles` | array | 否 | 角色数组 | array, 若为空则自动分配与 type 同名的角色 |

**请求示例**：
```json
{
  "name": "店员A",
  "email": "staff@milktea.local",
  "phone": "13800138001",
  "type": "staff",
  "status": "active",
  "password": "123456",
  "roles": ["staff"]
}
```

**返回数据结构**：同用户列表中的单个用户对象（含 `roles`）

---

### 7.3 获取用户详情

**接口**：`GET /api/v1/admin/users/{id}`

**认证**：需要 Token + `admin` 角色

**请求参数**：无（路径参数 `id`）

**返回数据结构**：同用户列表中的单个用户对象

---

### 7.4 更新用户账号

**接口**：`PUT /api/v1/admin/users/{id}`

**认证**：需要 Token + `admin` 角色

**请求参数**：同创建接口，所有字段均为可选（`sometimes`），`password` 为可选

**注意**：如果提供了 `roles`，会同步替换用户的所有角色

---

### 7.5 删除用户账号

**接口**：`DELETE /api/v1/admin/users/{id}`

**认证**：需要 Token + `admin` 角色

**请求参数**：无（路径参数 `id`）

**返回数据结构**：
```json
{
  "code": 0,
  "message": "账号已删除",
  "data": null
}
```

**说明**：软删除，`deleted_at` 会被设置

---

### 7.6 获取仪表盘概览

**接口**：`GET /api/v1/admin/dashboard/summary`

**认证**：需要 Token（`admin` 与 `manager` 均可访问）

**请求参数**：无

**返回数据结构**：
```json
{
  "code": 0,
  "message": "OK",
  "data": {
    "users_total": 1234,
    "customers_total": 860,
    "staff_total": 45,
    "today_revenue": 2536.84,
    "today_orders": 96,
    "inventory_total": 4210,
    "sales_trend": [
      { "date": "2025-11-16", "amount": 1980.5 },
      { "date": "2025-11-17", "amount": 2236.1 },
      { "date": "2025-11-18", "amount": 2536.8 }
    ],
    "orders_trend": [
      { "date": "2025-11-16", "count": 76 },
      { "date": "2025-11-17", "count": 88 },
      { "date": "2025-11-18", "count": 96 }
    ],
    "inventory_top": [
      { "name": "珍珠奶茶", "stock": 320 },
      { "name": "芝士抹茶", "stock": 280 },
      { "name": "草莓酸奶", "stock": 260 }
    ]
  }
}
```

**字段说明**：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| `users_total` | integer | 用户总数 |
| `customers_total` | integer | 顾客数量 |
| `staff_total` | integer | 店员+店长数量 |
| `today_revenue` | decimal | 今日营业额（已支付订单金额） |
| `today_orders` | integer | 今日订单数（按创建时间统计） |
| `inventory_total` | integer | 商品库存总量（`products.stock` 之和） |
| `sales_trend` | array | 近 7 天营业额趋势；元素包含 `date`、`amount` |
| `orders_trend` | array | 近 7 天订单数量趋势；元素包含 `date`、`count` |
| `inventory_top` | array | 库存最高的若干商品；元素包含 `name`、`stock` |

---

## 错误响应格式

### 验证错误（HTTP 422）

```json
{
  "code": 1,
  "message": "验证失败",
  "errors": {
    "email": ["邮箱格式不正确"],
    "password": ["密码至少需要6个字符"]
  }
}
```

### 认证错误（HTTP 401）

```json
{
  "code": 1,
  "message": "Unauthenticated."
}
```

### 权限错误（HTTP 403）

```json
{
  "code": 1,
  "message": "无权访问该订单"
}
```

### 资源不存在（HTTP 404）

```json
{
  "code": 1,
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

---

## 数据类型说明

- **string**：字符串
- **integer**：整数
- **decimal/numeric**：小数（价格、数量等）
- **boolean**：布尔值（true/false）
- **array**：JSON 数组
- **object**：JSON 对象
- **datetime**：日期时间（ISO 8601 格式，如 `2025-11-20T13:23:28.000000Z`）
- **enum**：枚举值（见各接口说明）

---

## 注意事项

1. 所有需要认证的接口必须在请求头中携带 `Authorization: Bearer {token}`
2. 分页接口默认返回 Laravel 分页格式，包含 `current_page`、`data`、`per_page`、`total` 等字段
3. 时间字段统一使用 ISO 8601 格式（UTC 时区）
4. 价格字段统一为 `decimal:2`（保留两位小数）
5. 软删除的资源在查询时默认不包含，除非使用 `withTrashed()`
6. 角色权限通过 `spatie/laravel-permission` 管理，角色名与用户 `type` 对应（customer/staff/manager/admin）
