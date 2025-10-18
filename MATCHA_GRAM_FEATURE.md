# Chức năng Tăng Gram Matcha - Hướng dẫn sử dụng

## Tổng quan

Phiên bản 1.0.9 đã thêm chức năng cho phép khách hàng chọn thêm gram matcha (từ 1-5g) khi mua sản phẩm, với giá được cấu hình riêng cho từng sản phẩm.

## Cách sử dụng

### 1. Cấu hình cho sản phẩm

Khi chỉnh sửa sản phẩm trong WordPress Admin:

1. Cuộn xuống meta box "Custom Options & Extra Info"
2. Tìm section "Matcha Gram Options"
3. Tick vào checkbox "Enable Matcha Gram Addition"
   - Nếu sản phẩm thuộc category "matcha", checkbox sẽ tự động được tick
4. Nhập "Giá mỗi gram thêm (k)" - ví dụ: 2 (= 2000đ/gram)
5. Lưu sản phẩm

### 2. Trên trang sản phẩm

Khách hàng sẽ thấy:
- Dropdown "Thêm gram matcha" với các lựa chọn:
  - Không thêm
  - +1g (+2k)
  - +2g (+4k)
  - +3g (+6k)
  - +4g (+8k)
  - +5g (+10k)
- Giá sẽ tự động cập nhật khi chọn

### 3. Trong giỏ hàng và đơn hàng

Thông tin gram thêm sẽ hiển thị:
- Giỏ hàng: "Thêm gram matcha: +2g (+4k)"
- Checkout: Tương tự giỏ hàng
- Email đơn hàng: Hiển thị đầy đủ thông tin
- Admin order: Hiển thị trong order details

## Cấu trúc dữ liệu

### Post Meta (sản phẩm)
- `_matcha_gram_enabled`: 'yes'/'no' - Bật/tắt chức năng
- `_matcha_price_per_gram`: float - Giá mỗi gram (đơn vị: k)

### Cart Item Data
- `woo_matcha_extra_gram`: int (1-5) - Số gram thêm
- `woo_matcha_gram_price`: int - Giá thêm (đã nhân 1000)

## Tính năng kỹ thuật

### Validation
- Số gram: 1-5
- Giá: >= 0 và <= 999999k
- Tự động sanitize và validate input

### Tương thích
- Hoạt động độc lập với Product Options hiện có
- Tương thích đầy đủ với HPOS (High-Performance Order Storage)
- Hỗ trợ cả legacy order storage

### Tự động phát hiện
- Plugin tự động phát hiện sản phẩm có category chứa từ "matcha"
- Tự động tick checkbox "Enable" cho sản phẩm matcha mới

## Files đã thay đổi

1. **admin/meta-box.php**
   - Thêm helper function `woo_is_matcha_product()`
   - Thêm section "Matcha Gram Options" trong meta box
   - Xử lý lưu dữ liệu matcha gram

2. **frontend/display-options.php**
   - Hiển thị dropdown chọn gram
   - Thêm CSS cho matcha gram section

3. **assets/frontend.js**
   - Lắng nghe sự kiện change của dropdown
   - Tính giá động khi chọn gram

4. **frontend/cart-hooks.php**
   - Lưu matcha gram vào cart item data
   - Hiển thị trong cart/checkout
   - Lưu vào order meta (HPOS + legacy)

5. **assets/admin.js**
   - Toggle hiển thị matcha gram content

6. **woo-product-option-setup.php**
   - Cập nhật version lên 1.0.9

7. **CHANGELOG.md**
   - Ghi nhận thay đổi version 1.0.9

## Ví dụ sử dụng

### Ví dụ 1: Matcha Latte
- Giá gốc: 45k (đã bao gồm 2g matcha mặc định)
- Giá mỗi gram thêm: 3k
- Khách chọn +2g
- Tổng giá: 45k + 6k = 51k

### Ví dụ 2: Matcha Smoothie
- Giá gốc: 55k (đã bao gồm 3g matcha mặc định)
- Giá mỗi gram thêm: 2.5k
- Khách chọn +5g
- Tổng giá: 55k + 12.5k = 67.5k

## Lưu ý

- Gram mặc định của sản phẩm chỉ là thông tin, không cần nhập vào plugin
- Chỉ gram **thêm** mới tính tiền
- Giá được tính theo công thức: `số_gram_thêm × giá_mỗi_gram × 1000`
- Dropdown chỉ hiển thị khi:
  1. Sản phẩm có enable matcha gram
  2. Giá mỗi gram > 0

