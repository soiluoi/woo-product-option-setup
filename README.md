# Woo Product Option Setup

Plugin WooCommerce cho phép cấu hình nhiều **Product Option Groups** (có giá cộng thêm) và **Extra Info Groups** (thông tin phụ không cộng tiền).

## Tính năng chính

### 🧱 Product Option Groups
- Tạo nhiều nhóm option cho sản phẩm
- Hỗ trợ 2 loại: **Radio** (chọn 1) và **Checkbox** (chọn nhiều)
- Mỗi option có thể có giá cộng thêm
- Tính giá động trên frontend
- Validation: Radio bắt buộc chọn, Checkbox tùy chọn

### 📝 Extra Info Groups
- Tạo thông tin phụ không cộng tiền
- 2 field: **Name** (tên) và **Value** (giá trị mặc định)
- Hỗ trợ loại **Number** với step cố định 0.5
- Có thể filter sản phẩm theo Extra Info
- Lưu dữ liệu dưới dạng meta keys riêng lẻ

### 🛒 Tích hợp giỏ hàng
- Lưu lựa chọn options và extra info vào cart
- Hiển thị trong giỏ hàng, checkout, email, admin order
- Tính giá tự động với options đã chọn

## Cài đặt

1. Upload thư mục `woo-product-option-setup` vào `/wp-content/plugins/`
2. Kích hoạt plugin trong WordPress Admin
3. Đảm bảo WooCommerce đã được kích hoạt

## Hướng dẫn sử dụng

### 1. Cấu hình Settings

Vào **Settings > Product Options** để:

#### Tạo Product Option Groups:
1. Nhấn "Thêm nhóm"
2. Nhập tên nhóm (vd: "Size", "Color")
3. Chọn loại: Radio hoặc Checkbox
4. Thêm các tùy chọn với giá cộng thêm (đơn vị: k = 1000đ)
5. Lưu cài đặt

#### Tạo Extra Info Groups:
1. Nhấn "Thêm Extra Info"
2. Nhập tên (vd: "Chiều cao", "Cân nặng")
3. Nhập giá trị mặc định (vd: 1, 2.5, 3) với step 0.5
4. Lưu cài đặt

### 2. Cấu hình sản phẩm

Vào **Products > Edit Product**:

#### Product Options:
1. Tick "Enable Product Options"
2. Chọn các nhóm option muốn dùng
3. Với mỗi nhóm: chọn các tùy chọn "Available for this product"
4. Lưu sản phẩm

#### Extra Info:
1. Tick "Enable Extra Info"
2. Với mỗi Extra Info: tick "Hiển thị" và nhập giá trị mặc định
3. Lưu sản phẩm

### 3. Frontend

Trên trang sản phẩm sẽ hiển thị:
- **Tùy chọn sản phẩm**: Radio/Checkbox với giá cộng thêm
- **Thông tin bổ sung**: Input fields cho Extra Info
- **Tính giá động**: Tổng giá cập nhật khi chọn options

## Cấu trúc dữ liệu

### Settings (wp_options):
- `woo_product_option_groups`: JSON array các nhóm option
- `woo_extra_info_groups`: JSON array các Extra Info

### Product Meta (post_meta):
- `_product_option_groups_data`: JSON cấu hình options cho sản phẩm
- `_extra_info_enabled`: "yes/no" bật/tắt Extra Info
- `_extra_info_{slug}`: Giá trị của từng Extra Info

## Hooks sử dụng

- `woocommerce_before_add_to_cart_button`: Hiển thị options
- `woocommerce_add_cart_item_data`: Lưu dữ liệu vào cart
- `woocommerce_before_calculate_totals`: Tính giá với options
- `woocommerce_get_item_data`: Hiển thị trong cart
- `woocommerce_add_order_item_meta`: Lưu vào order

## Tương thích

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- **Hỗ trợ HPOS (High-Performance Order Storage)** - WooCommerce 7.1+

## Lưu ý kỹ thuật

- Sử dụng `wp_nonce` cho security
- Sanitize và validate tất cả input
- Text domain: `woo-product-option-setup`
- Không tạo custom table, chỉ dùng wp_options và post_meta
- Extra Info lưu dưới dạng meta keys riêng để dễ filter
- **Hỗ trợ đầy đủ HPOS (High-Performance Order Storage)** của WooCommerce
- Sử dụng hooks tương thích với cả HPOS và legacy order storage

## Debug

Thêm `?debug_cart=1` vào URL cart để xem dữ liệu cart item (chỉ admin).

## Hỗ trợ

Nếu gặp vấn đề, vui lòng kiểm tra:
1. WooCommerce đã kích hoạt chưa
2. Có lỗi JavaScript trong console không
3. Dữ liệu đã được lưu đúng trong Settings chưa
