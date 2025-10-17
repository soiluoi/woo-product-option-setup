/**
 * JavaScript cho frontend - tính giá động
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Khởi tạo tính giá
    initPriceCalculation();
    
    /**
     * Khởi tạo tính giá
     */
    function initPriceCalculation() {
        var calculationTimeout;
        
        // Lắng nghe sự kiện thay đổi options với debounce
        $('.woo-product-options-container').on('change', 'input[type="radio"], input[type="checkbox"]', function() {
            clearTimeout(calculationTimeout);
            calculationTimeout = setTimeout(function() {
                calculateTotalPrice();
            }, 100); // Debounce 100ms
        });
        
        // Tính giá ban đầu
        calculateTotalPrice();
    }
    
    /**
     * Tính tổng giá
     */
    function calculateTotalPrice() {
        try {
            var originalPrice = parseFloat($('#original-price').data('price')) || 0;
            var additionalPrice = 0;
            
            // Validate original price
            if (isNaN(originalPrice) || originalPrice < 0) {
                console.error('Invalid original price:', originalPrice);
                originalPrice = 0;
            }
        
        // Tính giá từ radio groups (chỉ lấy 1 option được chọn)
        $('.option-group[data-group-type="radio"]').each(function() {
            var $group = $(this);
            var $selectedOption = $group.find('input[type="radio"]:checked');
            
            if ($selectedOption.length > 0) {
                var price = parseFloat($selectedOption.data('price')) || 0;
                additionalPrice += price;
            }
        });
        
        // Tính giá từ checkbox groups (cộng dồn tất cả được chọn)
        $('.option-group[data-group-type="checkbox"]').each(function() {
            var $group = $(this);
            var $checkedOptions = $group.find('input[type="checkbox"]:checked');
            
            $checkedOptions.each(function() {
                var price = parseFloat($(this).data('price')) || 0;
                additionalPrice += price;
            });
        });
        
        // Cập nhật hiển thị
        updatePriceDisplay(originalPrice, additionalPrice);
        
        } catch (error) {
            console.error('Error calculating total price:', error);
            // Fallback: hiển thị giá gốc
            var $totalPrice = $('#total-price');
            if ($totalPrice.length) {
                $totalPrice.text($('#original-price').text());
            }
        }
    }
    
    /**
     * Cập nhật hiển thị giá
     */
    function updatePriceDisplay(originalPrice, additionalPrice) {
        var $additionalPriceDiv = $('#additional-price');
        var $additionalPriceAmount = $('#additional-price-amount');
        var $totalPrice = $('#total-price');
        
        // Hiển thị/ẩn phần phụ phí
        if (additionalPrice > 0) {
            $additionalPriceDiv.show();
            $additionalPriceAmount.text('+' + formatPrice(additionalPrice));
        } else {
            $additionalPriceDiv.hide();
        }
        
        // Cập nhật tổng giá
        var totalPrice = originalPrice + additionalPrice;
        $totalPrice.text(formatPrice(totalPrice));
        
        // Cập nhật data attribute để sử dụng sau này
        $('.woo-product-options-container').data('total-price', totalPrice);
    }
    
    /**
     * Format giá tiền
     */
    function formatPrice(price) {
        // Format theo yêu cầu: chỉ hiển thị số + "k"
        return Math.round(price / 1000) + 'k';
    }
    
    /**
     * Lấy giá gốc từ WooCommerce
     */
    function getOriginalPrice() {
        var $priceElement = $('#original-price');
        if ($priceElement.length > 0) {
            var price = parseFloat($priceElement.data('price')) || 0;
            return price;
        }
        return 0;
    }
    
    /**
     * Xử lý validation trước khi thêm vào giỏ hàng
     */
    $('form.cart').on('submit', function(e) {
        var isValid = validateProductOptions();
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * Validate product options
     */
    function validateProductOptions() {
        var isValid = true;
        var errorMessages = [];
        
        // Kiểm tra radio groups - phải chọn ít nhất 1 option
        $('.option-group[data-group-type="radio"]').each(function() {
            var $group = $(this);
            var $checkedOptions = $group.find('input[type="radio"]:checked');
            
            if ($checkedOptions.length === 0) {
                isValid = false;
                var groupName = $group.find('.group-title').text();
                errorMessages.push('Vui lòng chọn một tùy chọn cho "' + groupName + '"');
            }
        });
        
        // Hiển thị lỗi nếu có
        if (!isValid) {
            showValidationErrors(errorMessages);
        } else {
            hideValidationErrors();
        }
        
        return isValid;
    }
    
    /**
     * Hiển thị lỗi validation
     */
    function showValidationErrors(messages) {
        hideValidationErrors(); // Xóa lỗi cũ trước
        
        var $errorDiv = $('<div class="woo-product-option-errors" style="color: red; margin: 10px 0; padding: 10px; background: #ffe6e6; border: 1px solid #ffcccc; border-radius: 3px;"></div>');
        
        var errorList = $('<ul style="margin: 0; padding-left: 20px;"></ul>');
        messages.forEach(function(message) {
            errorList.append('<li>' + message + '</li>');
        });
        
        $errorDiv.append(errorList);
        $('.woo-product-options-container').before($errorDiv);
    }
    
    /**
     * Ẩn lỗi validation
     */
    function hideValidationErrors() {
        $('.woo-product-option-errors').remove();
    }
    
    /**
     * Lấy dữ liệu options đã chọn để gửi lên server
     */
    window.getSelectedOptions = function() {
        var selectedOptions = {};
        
        // Lấy product options
        $('.option-group').each(function() {
            var $group = $(this);
            var groupId = $group.data('group-id');
            var groupType = $group.data('group-type');
            var selected = [];
            
            if (groupType === 'radio') {
                var $checked = $group.find('input[type="radio"]:checked');
                if ($checked.length > 0) {
                    selected.push($checked.val());
                }
            } else if (groupType === 'checkbox') {
                $group.find('input[type="checkbox"]:checked').each(function() {
                    selected.push($(this).val());
                });
            }
            
            if (selected.length > 0) {
                selectedOptions[groupId] = selected;
            }
        });
        
        
        return {
            options: selectedOptions
        };
    };
    
    /**
     * Debug function - có thể xóa trong production
     */
    window.debugProductOptions = function() {
        console.log('Selected Options:', getSelectedOptions());
        console.log('Total Price:', $('.woo-product-options-container').data('total-price'));
    };
});
