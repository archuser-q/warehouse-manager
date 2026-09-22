# Quản lý Nhà kho & Vật dụng (PHP + MongoDB)

Dự án fullstack quản lý nhà kho và vật dụng bên trong, có dashboard thống kê.

## Công nghệ sử dụng

- **Backend:** PHP 8.1+ với [Slim Framework 4](https://www.slimframework.com/) (routing, MVC)
- **Database:** MongoDB (qua thư viện chính thức `mongodb/mongodb`)
- **Template engine:** Twig
- **Frontend:** Bootstrap 5 + Chart.js (qua CDN, không cần cài npm)

## Cấu trúc thư mục

```
warehouse-manager/
├── public/
│   └── index.php          # Entry point (front controller)
├── src/
│   ├── Config/Database.php  # Kết nối MongoDB
│   ├── Models/               # Warehouse, Item
│   └── Controllers/          # Dashboard, Warehouse, Item
├── routes/web.php            # Định nghĩa routes
├── templates/                # Giao diện Twig (layout, dashboard, warehouses, items)
├── seed.php                  # Script tạo dữ liệu mẫu
├── composer.json
└── .env.example
```

## Cài đặt

### 1. Yêu cầu hệ thống
- PHP >= 8.1 kèm extension `mongodb` (`pecl install mongodb`)
- Composer
- MongoDB Server (local hoặc MongoDB Atlas)

### 2. Cài dependencies

```bash
composer install
```

### 3. Cấu hình môi trường

```bash
cp .env.example .env
```

Sửa `.env` với thông tin kết nối MongoDB của bạn:

```
MONGODB_URI=mongodb://127.0.0.1:27017
MONGODB_DATABASE=warehouse_manager
```

Nếu dùng MongoDB Atlas, `MONGODB_URI` sẽ có dạng:
`mongodb+srv://<user>:<password>@cluster.mongodb.net/`

### 4. (Tuỳ chọn) Tạo dữ liệu mẫu

```bash
php seed.php
```

### 5. Chạy server

```bash
php -S localhost:8000 -t public
```

Truy cập: http://localhost:8000

## Chức năng

- **Dashboard:** tổng số kho, tổng loại vật dụng, tổng số lượng tồn, cảnh báo vật dụng sắp hết hàng, biểu đồ số lượng theo từng kho.
- **Quản lý nhà kho:** thêm / sửa / xoá kho (xoá kho sẽ xoá luôn vật dụng thuộc kho đó).
- **Quản lý vật dụng:** thêm / sửa / xoá vật dụng, gán vào kho, theo dõi số lượng tồn, ngưỡng cảnh báo tối thiểu, đơn giá; lọc vật dụng theo kho.

## Mở rộng gợi ý

- Thêm đăng nhập/phân quyền (admin, nhân viên kho)
- Lịch sử nhập/xuất kho (collection `stock_transactions`)
- Export báo cáo Excel/PDF
- Tìm kiếm & phân trang cho danh sách vật dụng khi dữ liệu lớn
