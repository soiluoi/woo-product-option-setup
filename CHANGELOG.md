# Changelog

## [1.0.5] - 2024-01-16

### Changed
- Luôn hiển thị ô nhập giá cho tất cả options, không cần tick checkbox
- Để trống ô giá = sử dụng giá mặc định từ settings
- Chỉ lưu giá tùy chỉnh khi option được chọn (checkbox được tick)
- Loại bỏ toggle hiển thị ô nhập giá trong JavaScript
- Hiển thị gọn lại: mỗi option và extra info nằm trên 1 dòng
- Cải thiện layout với flexbox để tối ưu không gian
- Loại bỏ các label không cần thiết như "Giá (k):" và "Giá trị:"
- Hiển thị thông tin theo hàng ngang một cách gọn gàng
- Bỏ phần hiển thị giá gốc, sử dụng placeholder cho input
- Cải thiện CSS để các option đều nhau và phù hợp với content

### Fixed
- Logic lưu giá được cải thiện để chỉ lưu khi cần thiết
- UX được cải thiện với việc luôn hiển thị ô nhập giá
- JavaScript được đơn giản hóa
- Layout gọn gàng và dễ sử dụng hơn
- Giao diện sạch sẽ hơn với ít text thừa
- Input focus state được cải thiện
- Text overflow được xử lý tốt hơn

## [1.0.4] - 2024-01-16

### Changed
- Sửa lại layout meta box để đơn giản hơn, tương tự như trang settings
- Cải thiện giao diện admin với layout card-based
- Tối ưu hóa CSS cho meta box
- Cải thiện UX với layout rõ ràng và dễ sử dụng

### Fixed
- Layout meta box được tối ưu hóa
- CSS được cập nhật để phù hợp với layout mới
- JavaScript được cập nhật để hoạt động với layout mới

## [1.0.3] - 2024-01-16

### Changed
- Không tự động nhân 1000 trong settings, chỉ nhân khi hiển thị và tính giá
- Cho phép admin chỉnh sửa giá của từng option cho từng sản phẩm
- Nếu setting không điền giá thì để trống khi edit sản phẩm
- Sửa lỗi không hiển thị đúng các lựa chọn đã lưu khi load edit sản phẩm
- Thay đổi currency hiển thị thành đơn vị (k) thay vì currency symbol
- Hiển thị tất cả options trong group, không cần tick group mới thấy options
- Để trống giá tùy chỉnh = sử dụng giá mặc định từ settings

### Fixed
- Logic lưu và hiển thị giá options được cải thiện
- Giao diện admin cho phép override giá từng option
- JavaScript toggle hiển thị phần chỉnh sửa giá
- Hiển thị đúng trạng thái đã lưu khi load lại trang edit sản phẩm

## [1.0.2] - 2024-01-16

### Changed
- Extra Info Groups có 2 field: **Name** (tên) và **Value** (giá trị mặc định)
- Chỉ hỗ trợ loại "number" với step cố định 0.5
- Loại bỏ tùy chọn chọn loại (number/text) trong Settings
- Đơn giản hóa giao diện admin cho Extra Info Groups
- Cập nhật frontend để chỉ hiển thị number input
- Giá trị mặc định từ settings được sử dụng nếu không có giá trị riêng cho sản phẩm

### Fixed
- Loại bỏ code không cần thiết cho text input trong Extra Info
- Cải thiện UX với giao diện đơn giản hơn
- Logic lưu dữ liệu Extra Info được cải thiện

## [1.0.1] - 2024-01-16

### Added
- Hỗ trợ HPOS (High-Performance Order Storage) của WooCommerce
- Khai báo tương thích với custom order tables
- Hook backup cho legacy order storage
- Debug function hiển thị trạng thái HPOS

### Changed
- Cập nhật hooks để tương thích với cả HPOS và legacy storage
- Cải thiện xử lý order item meta cho HPOS

### Fixed
- Loại bỏ warning "incompatible with High-Performance order storage"
- Đảm bảo plugin hoạt động với cả HPOS enabled và disabled

## [1.0.0] - 2024-01-16

### Added
- Plugin WooCommerce Product Option Setup
- Quản lý Product Option Groups (Radio/Checkbox)
- Quản lý Extra Info Groups (Number/Text)
- Meta box trong Product Edit
- Hiển thị options trên frontend với tính giá động
- Xử lý giỏ hàng và đơn hàng
- Admin interface với CSS/JS
- Hỗ trợ đa ngôn ngữ
