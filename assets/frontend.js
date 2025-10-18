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
            }, 50); // Giảm debounce để phản hồi nhanh hơn
        });
        
        // Lắng nghe sự kiện thay đổi matcha gram
        $('.matcha-gram-select').on('change', function() {
            clearTimeout(calculationTimeout);
            calculationTimeout = setTimeout(function() {
                calculateTotalPrice();
            }, 50);
        });
        
        // Lắng nghe sự kiện quantity change
        $('input[name="quantity"]').on('change', function() {
            calculateTotalPrice();
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
            var quantity = parseInt($('input[name="quantity"]').val()) || 1;
            var additionalPrice = 0;
            
            // Validate original price
            if (isNaN(originalPrice) || originalPrice < 0) {
                console.error('Invalid original price:', originalPrice);
                originalPrice = 0;
            }
            
            // Validate quantity
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
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
            
            // Tính giá từ matcha gram
            var $matchaGramSelect = $('.matcha-gram-select');
            if ($matchaGramSelect.length > 0) {
                var selectedGrams = parseInt($matchaGramSelect.val()) || 0;
                var pricePerGram = parseFloat($matchaGramSelect.data('price-per-gram')) || 0;
                
                if (selectedGrams > 0 && pricePerGram > 0) {
                    var matchaGramPrice = selectedGrams * pricePerGram;
                    additionalPrice += matchaGramPrice;
                }
            }
            
            // Nhân với quantity
            var totalOriginalPrice = originalPrice * quantity;
            var totalAdditionalPrice = additionalPrice * quantity;
            
            // Cập nhật hiển thị
            updatePriceDisplay(totalOriginalPrice, totalAdditionalPrice, quantity);
            
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
    function updatePriceDisplay(originalPrice, additionalPrice, quantity) {
        var $additionalPriceDiv = $('#additional-price');
        var $additionalPriceAmount = $('#additional-price-amount');
        var $totalPrice = $('#total-price');
        var $originalPriceSpan = $('#original-price');
        
        // Cập nhật giá gốc nếu có quantity > 1
        if (quantity > 1) {
            var singleOriginalPrice = parseFloat($originalPriceSpan.data('price')) || 0;
            $originalPriceSpan.text(formatPrice(singleOriginalPrice) + ' × ' + quantity + ' = ' + formatPrice(originalPrice));
        } else {
            $originalPriceSpan.text(formatPrice(originalPrice));
        }
        
        // Hiển thị/ẩn phần phụ phí
        if (additionalPrice > 0) {
            $additionalPriceDiv.show();
            if (quantity > 1) {
                var singleAdditionalPrice = additionalPrice / quantity;
                $additionalPriceAmount.text('+' + formatPrice(singleAdditionalPrice) + ' × ' + quantity + ' = +' + formatPrice(additionalPrice));
            } else {
                $additionalPriceAmount.text('+' + formatPrice(additionalPrice));
            }
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
        // price đã ở đơn vị gốc (đồng), chia 1000 để hiển thị "k"
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
     * Xử lý AJAX Add to Cart
     */
    $('form.cart').on('submit', function(e) {
        e.preventDefault();
        
        var isValid = validateProductOptions();
        if (!isValid) {
            return false;
        }
        
        // Show loading state
        var $button = $(this).find('button[type="submit"]');
        var originalText = $button.text();
        $button.prop('disabled', true).text('Đang thêm...');
        
        // Get form data
        var formData = $(this).serializeArray();
        var productId = $('input[name="add-to-cart"]').val();
        var quantity = $('input[name="quantity"]').val() || 1;
        var variationId = $('input[name="variation_id"]').val() || 0;
        
        // Get selected options
        var selectedOptions = getSelectedOptions();
        
        // Prepare AJAX data
        var ajaxData = {
            action: 'woo_add_to_cart_with_options',
            nonce: wooProductOption.nonce,
            product_id: productId,
            quantity: quantity,
            variation_id: variationId,
            product_options: selectedOptions.options
        };
        
        // Make AJAX request
        $.ajax({
            url: wooProductOption.ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage(response.data.message, 'success');
                    
                    // Update cart fragments if provided
                    if (response.data.fragments) {
                        $.each(response.data.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Trigger cart updated event
                    $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash, $button]);
                } else {
                    showMessage('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
                }
            },
            error: function() {
                showMessage('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
            },
            complete: function() {
                // Reset button
                $button.prop('disabled', false).text(originalText);
            }
        });
        
        return false;
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
     * Hiển thị message cho user
     */
    function showMessage(message, type) {
        // Remove existing messages
        $('.woo-product-option-message').remove();
        
        var messageClass = type === 'success' ? 'woocommerce-message' : 'woocommerce-error';
        var $message = $('<div class="woo-product-option-message ' + messageClass + '" style="margin: 10px 0; padding: 10px; border-radius: 3px;"></div>');
        $message.text(message);
        
        // Insert before options container
        $('.woo-product-options-container').before($message);
        
        // Auto hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
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
