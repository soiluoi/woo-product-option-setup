# Hướng dẫn cài đặt nhanh

## Cài đặt plugin

1. **Upload folder `woo-product-option-setup`** vào thư mục `/wp-content/plugins/` của WordPress
2. **Kích hoạt plugin** trong WordPress Admin > Plugins
3. **Đảm bảo WooCommerce đã được cài đặt và kích hoạt**

## Cấu hình ban đầu

### Bước 1: Tạo Product Option Groups
1. Vào **Settings > Product Options**
2. Trong phần "Product Option Groups":
   - Nhấn "Thêm nhóm"
   - Nhập tên nhóm (vd: "Size", "Color")
   - Chọn loại: Radio hoặc Checkbox
   - Thêm các tùy chọn với giá cộng thêm (đơn vị: k = 1000đ)
   - Lưu cài đặt

### Bước 2: Tạo Extra Info Groups
1. Trong cùng trang Settings:
2. Trong phần "Extra Info Groups":
   - Nhấn "Thêm Extra Info"
   - Nhập tên (vd: "Chiều cao", "Cân nặng")
   - Chọn loại: Number hoặc Text
   - Nếu Number: thiết lập step (mặc định 0.5)
   - Lưu cài đặt

### Bước 3: Cấu hình sản phẩm
1. Vào **Products > Edit Product**
2. Tìm meta box "Custom Options & Extra Info"
3. **Product Options**:
   - Tick "Enable Product Options"
   - Chọn các nhóm option muốn dùng
   - Chọn options "Available for this product"
4. **Extra Info**:
   - Tick "Enable Extra Info"
   - Với mỗi Extra Info: tick "Hiển thị" và nhập giá trị mặc định
5. **Lưu sản phẩm**

## Kiểm tra hoạt động

1. Vào trang sản phẩm trên frontend
2. Kiểm tra:
   - Options hiển thị đúng (Radio/Checkbox)
   - Giá tính động khi chọn options
   - Extra Info hiển thị với input phù hợp
3. Thêm vào giỏ hàng và kiểm tra:
   - Thông tin options hiển thị trong cart
   - Giá được tính đúng
   - Thông tin lưu trong order

## Troubleshooting

- **Plugin không hoạt động**: Kiểm tra WooCommerce đã kích hoạt chưa
- **Options không hiển thị**: Kiểm tra đã enable trong product edit chưa
- **Giá không tính đúng**: Kiểm tra JavaScript console có lỗi không
- **Dữ liệu không lưu**: Kiểm tra quyền user và nonce
- **Warning HPOS**: Plugin đã hỗ trợ HPOS, warning sẽ biến mất sau khi kích hoạt

## Hỗ trợ

Xem file README.md để biết thêm chi tiết về tính năng và cấu trúc dữ liệu.
