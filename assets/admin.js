/**
 * JavaScript cho Admin - Product Option Setup
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Khởi tạo
    initAdminScripts();
    
    /**
     * Khởi tạo các script admin
     */
    function initAdminScripts() {
        initOptionGroups();
        initExtraInfoGroups();
        initMetaBox();
        initToggles();
    }
    
    /**
     * Khởi tạo quản lý Option Groups
     */
    function initOptionGroups() {
        var groupIndex = $('.option-group-item').length;
        
        // Thêm nhóm mới
        $('#add-option-group').on('click', function() {
            addNewOptionGroup(groupIndex++);
        });
        
        // Xóa nhóm
        $(document).on('click', '.remove-group', function() {
            removeOptionGroup($(this));
        });
        
        // Thêm option mới (xác định đúng group qua phần tử button vừa click)
        $(document).on('click', '.add-option', function() {
            var $group = $(this).closest('.option-group-item');
            var groupIndex = $group.attr('data-index');
            addNewOption($group, parseInt(groupIndex, 10));
        });
        
        // Xóa option
        $(document).on('click', '.remove-option', function() {
            removeOption($(this));
        });
        
        // Cập nhật tên nhóm
        $(document).on('input', '.group-name-input', function() {
            updateGroupTitle($(this));
        });
        
        // Toggle hiển thị options khi chọn group
        $(document).on('change', '.group-checkbox', function() {
            toggleGroupOptions($(this));
        });
    }
    
    /**
     * Khởi tạo quản lý Extra Info Groups
     */
    function initExtraInfoGroups() {
        // Tính chỉ số kế tiếp dựa trên data-index hiện có
        function getNextInfoIndex() {
            var maxIndex = -1;
            $('#extra-info-groups-container .option-item').each(function() {
                var idx = parseInt($(this).attr('data-index'), 10);
                if (!isNaN(idx) && idx > maxIndex) {
                    maxIndex = idx;
                }
            });
            return maxIndex + 1;
        }

        var infoIndex = getNextInfoIndex();
        
        // Thêm Extra Info mới
        $('#add-extra-info-group').on('click', function() {
            addNewExtraInfo(infoIndex++);
        });
        
        // Xóa Extra Info dựa trên markup hiện tại (.remove-option trong container Extra Info)
        $(document).on('click', '#extra-info-groups-container .remove-option', function() {
            removeExtraInfo($(this));
        });
        // Tương thích nếu có class cũ
        $(document).on('click', '.remove-extra-info', function() {
            removeExtraInfo($(this));
        });
        
        // Cập nhật tiêu đề Extra Info (markup hiện tại dùng .option-name)
        $(document).on('input', '#extra-info-groups-container .option-name', function() {
            updateInfoTitle($(this));
        });
        
        // Không cần toggle step row vì tất cả Extra Info đều là number với step 0.5
    }
    
    /**
     * Khởi tạo Meta Box
     */
    function initMetaBox() {
        // Toggle Product Options
        $('input[name="product_options_enabled"]').on('change', function() {
            toggleSection($(this), '.options-content');
        });
        
        // Toggle Matcha Gram Options
        $('input[name="matcha_gram_enabled"]').on('change', function() {
            toggleSection($(this), '.matcha-gram-content');
        });
        
        // Toggle Extra Info
        $('input[name="extra_info_enabled"]').on('change', function() {
            toggleSection($(this), '.extra-info-content');
        });
        
        
        // Không cần toggle hiển thị phần chỉnh sửa giá vì luôn hiển thị
    }
    
    /**
     * Khởi tạo các toggle
     */
    function initToggles() {
        // Toggle sections dựa trên checkbox
        $('input[type="checkbox"][name$="_enabled"]').each(function() {
            var $checkbox = $(this);
            var $content = $checkbox.closest('.options-section, .extra-info-section').find('.options-content, .extra-info-content');
            
            if (!$checkbox.is(':checked')) {
                $content.hide();
            }
        });
    }
    
    /**
     * Thêm nhóm option mới
     */
    function addNewOptionGroup(index) {
        var template = $('#option-group-template').html();
        template = template.replace(/\{\{INDEX\}\}/g, index);
        
        var $newGroup = $(template);
        $('#option-groups-container').append($newGroup);
        
        // Ẩn thông báo "no groups" nếu có
        $('.no-groups').hide();
        
        // Focus vào input tên nhóm
        $newGroup.find('.group-name-input').focus();
        
        // Animation
        $newGroup.hide().slideDown(300);
    }
    
    /**
     * Xóa nhóm option
     */
    function removeOptionGroup($button) {
        if (!confirm('Bạn có chắc chắn muốn xóa nhóm này?')) {
            return;
        }
        
        var $group = $button.closest('.option-group-item');
        
        $group.addClass('removing');
        setTimeout(function() {
            $group.remove();
            
            // Hiển thị thông báo "no groups" nếu không còn nhóm nào
            if ($('.option-group-item').length === 0) {
                $('.no-groups').show();
            }
        }, 300);
    }
    
    /**
     * Thêm option mới
     */
    function addNewOption($group, groupIndex) {
        var $container = $group.find('.options-container');
        var optionIndex = $container.find('.option-item').length;
        
        var template = getOptionTemplate(groupIndex, optionIndex);
        var $newOption = $(template);
        
        $container.append($newOption);
        
        // Ẩn thông báo "no options" nếu có
        $container.find('.no-options').hide();
        
        // Focus vào input tên option
        $newOption.find('.option-name').focus();
        
        // Animation
        $newOption.hide().slideDown(200);
    }
    
    /**
     * Xóa option
     */
    function removeOption($button) {
        var $option = $button.closest('.option-item');
        
        $option.addClass('removing');
        setTimeout(function() {
            $option.remove();
            
            // Hiển thị thông báo "no options" nếu không còn option nào
            var $container = $option.closest('.options-container');
            if ($container.find('.option-item').length === 0) {
                $container.find('.no-options').show();
            }
        }, 200);
    }
    
    /**
     * Lấy template cho option
     */
    function getOptionTemplate(groupIndex, optionIndex) {
        return '<div class="option-item" data-option-index="' + optionIndex + '">' +
            '<input type="text" name="option_groups[' + groupIndex + '][options][' + optionIndex + '][name]" placeholder="Tên tùy chọn" class="option-name" required>' +
            '<input type="number" name="option_groups[' + groupIndex + '][options][' + optionIndex + '][price]" placeholder="Giá cộng thêm (k)" class="option-price" min="0" step="1">' +
            '<span class="price-unit">k</span>' +
            '<button type="button" class="button button-small remove-option">Xóa</button>' +
        '</div>';
    }
    
    /**
     * Thêm Extra Info mới
     */
    function addNewExtraInfo(index) {
        var template = $('#extra-info-template').html();
        template = template.replace(/\{\{INDEX\}\}/g, index);
        
        var $newInfo = $(template);
        $('#extra-info-groups-container').append($newInfo);
        
        // Ẩn thông báo "no groups" nếu có
        $('.no-groups').hide();
        
        // Focus vào input tên (markup hiện tại dùng .option-name)
        $newInfo.find('.option-name').focus();
        
        // Animation
        $newInfo.hide().slideDown(300);
    }
    
    /**
     * Xóa Extra Info
     */
    function removeExtraInfo($button) {
        if (!confirm('Bạn có chắc chắn muốn xóa Extra Info này?')) {
            return;
        }
        
        var $info = $button.closest('.option-item');
        
        $info.addClass('removing');
        setTimeout(function() {
            $info.remove();
            
            // Hiển thị thông báo "no groups" nếu không còn Extra Info nào
            if ($('#extra-info-groups-container .option-item').length === 0) {
                $('.no-groups').show();
            }
        }, 300);
    }
    
    /**
     * Cập nhật tiêu đề nhóm
     */
    function updateGroupTitle($input) {
        var title = $input.val() || 'Nhóm mới';
        $input.closest('.option-group-item').find('.group-title').text(title);
    }
    
    /**
     * Cập nhật tiêu đề Extra Info
     */
    function updateInfoTitle($input) {
        var title = $input.val() || 'Extra Info mới';
        $input.closest('.extra-info-item').find('.info-title').text(title);
    }
    
    /**
     * Toggle hiển thị options của group
     */
    function toggleGroupOptions($checkbox) {
        var $group = $checkbox.closest('.option-group-item');
        var $content = $group.find('.group-content');
        
        if ($checkbox.is(':checked')) {
            $content.slideDown(200);
            $group.removeClass('collapsed');
        } else {
            $content.slideUp(200);
            $group.addClass('collapsed');
            // Không bỏ chọn options khi bỏ tick group
        }
    }
    
    
    /**
     * Toggle hiển thị section
     */
    function toggleSection($checkbox, selector) {
        var $content = $checkbox.closest('.options-section, .extra-info-section').find(selector);
        
        if ($checkbox.is(':checked')) {
            $content.slideDown(300);
        } else {
            $content.slideUp(300);
        }
    }
    
    
    /**
     * Validation form trước khi submit
     */
    $('form').on('submit', function(e) {
        var isValid = validateForm();
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * Validate form
     */
    function validateForm() {
        var isValid = true;
        var errors = [];
        
        // Validate option groups
        $('.option-group-item').each(function() {
            var $group = $(this);
            var groupName = $group.find('.group-name-input').val().trim();
            
            if (!groupName) {
                isValid = false;
                errors.push('Tên nhóm option không được để trống');
                $group.find('.group-name-input').addClass('error');
            } else {
                $group.find('.group-name-input').removeClass('error');
            }
            
            // Validate options trong nhóm
            var hasValidOption = false;
            $group.find('.option-item').each(function() {
                var $option = $(this);
                var optionName = $option.find('.option-name').val().trim();
                
                if (optionName) {
                    hasValidOption = true;
                    $option.find('.option-name').removeClass('error');
                } else {
                    $option.find('.option-name').addClass('error');
                }
            });
            
            if (!hasValidOption) {
                isValid = false;
                errors.push('Mỗi nhóm option phải có ít nhất một tùy chọn hợp lệ');
            }
        });
        
        // Validate extra info groups
        $('.extra-info-item').each(function() {
            var $info = $(this);
            var infoName = $info.find('.info-name-input').val().trim();
            
            if (!infoName) {
                isValid = false;
                errors.push('Tên Extra Info không được để trống');
                $info.find('.info-name-input').addClass('error');
            } else {
                $info.find('.info-name-input').removeClass('error');
            }
        });
        
        // Hiển thị lỗi
        if (!isValid) {
            showValidationErrors(errors);
        } else {
            hideValidationErrors();
        }
        
        return isValid;
    }
    
    /**
     * Hiển thị lỗi validation
     */
    function showValidationErrors(errors) {
        hideValidationErrors();
        
        var $errorDiv = $('<div class="notice notice-error validation-errors"><p><strong>Vui lòng sửa các lỗi sau:</strong></p><ul></ul></div>');
        
        errors.forEach(function(error) {
            $errorDiv.find('ul').append('<li>' + error + '</li>');
        });
        
        $('.wrap h1').after($errorDiv);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    /**
     * Ẩn lỗi validation
     */
    function hideValidationErrors() {
        $('.validation-errors').remove();
        $('.error').removeClass('error');
    }
    
    /**
     * Auto-save draft (optional)
     */
    var autoSaveTimer;
    
    $('input, select, textarea').on('input change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Có thể thêm auto-save logic ở đây
            console.log('Auto-save triggered');
        }, 2000);
    });
    
    /**
     * Debug functions
     */
    window.debugProductOptions = function() {
        console.log('Option Groups:', $('.option-group-item').length);
        console.log('Extra Info Groups:', $('.extra-info-item').length);
        console.log('Total Options:', $('.option-item').length);
    };
    
    // Thêm CSS cho error states
    $('<style>')
        .prop('type', 'text/css')
        .html('.error { border-color: #dc3232 !important; box-shadow: 0 0 2px rgba(220, 50, 50, 0.8) !important; }')
        .appendTo('head');
    
    /**
     * Bulk Edit specific functionality
     */
    function initBulkEdit() {
        // Toggle Product Options (scope theo section hiện tại)
        $('.options-enabled-toggle').on('change', function() {
            var $section = $(this).closest('.woo-product-option-section');
            var $content = $section.find('.options-content');
            if ($(this).is(':checked')) {
                $content.stop(true, true).slideDown(300);
            } else {
                $content.stop(true, true).slideUp(300);
            }
        });
        
        // Toggle Matcha Gram Options (scope theo section hiện tại)
        $('.matcha-enabled-toggle').on('change', function() {
            var $section = $(this).closest('.woo-product-option-section');
            var $content = $section.find('.matcha-gram-content');
            if ($(this).is(':checked')) {
                $content.stop(true, true).slideDown(300);
            } else {
                $content.stop(true, true).slideUp(300);
            }
        });
        
        // Toggle Extra Info (scope theo section hiện tại)
        $('.extra-info-enabled-toggle').on('change', function() {
            var $section = $(this).closest('.woo-product-option-section');
            var $content = $section.find('.extra-info-content');
            if ($(this).is(':checked')) {
                $content.stop(true, true).slideDown(300);
            } else {
                $content.stop(true, true).slideUp(300);
            }
        });
        
        // Toggle group options
        $('.group-checkbox').on('change', function() {
            var $group = $(this).closest('.option-group-item');
            var $content = $group.find('.group-content');
            
            if ($(this).is(':checked')) {
                $content.slideDown(200);
                $group.removeClass('collapsed');
            } else {
                $content.slideUp(200);
                $group.addClass('collapsed');
                // Uncheck all options in this group when group is unchecked
                $group.find('.option-availability-checkbox').prop('checked', false).trigger('change');
                $group.find('.select-all-checkbox').prop('checked', false);
            }
        });
        
        // Select all options in a group
        $('.select-all-checkbox').on('change', function() {
            var groupId = $(this).data('group');
            var isChecked = $(this).is(':checked');
            var $group = $(this).closest('.option-group-item');
            
            // Toggle all options in this group
            $group.find('.option-availability-checkbox[data-group="' + groupId + '"]')
                  .prop('checked', isChecked)
                  .trigger('change');
        });
        
        // Toggle option availability
        $('.option-availability-checkbox').on('change', function() {
            var $option = $(this).closest('.option-item');
            var $priceInput = $option.find('.option-price-input');
            var groupId = $(this).data('group');
            var $group = $(this).closest('.option-group-item');
            
            if ($(this).is(':checked')) {
                $priceInput.prop('disabled', false).css('opacity', '1');
            } else {
                $priceInput.prop('disabled', true).css('opacity', '0.5');
            }
            
            // Update "select all" checkbox state
            updateSelectAllState($group, groupId);
        });
        
        // Initialize disabled state cho các option chưa chọn
        $('.option-availability-checkbox:not(:checked)').each(function() {
            var $option = $(this).closest('.option-item');
            var $priceInput = $option.find('.option-price-input');
            $priceInput.prop('disabled', true).css('opacity', '0.5');
        });

        // Khởi tạo trạng thái hiển thị ban đầu cho các section
        $('.options-enabled-toggle').triggerHandler('change');
        $('.matcha-enabled-toggle').triggerHandler('change');
        $('.extra-info-enabled-toggle').triggerHandler('change');
        
        // Initialize select all states
        $('.option-group-item').each(function() {
            var $group = $(this);
            var groupId = $group.find('.option-availability-checkbox').first().data('group');
            if (groupId) {
                updateSelectAllState($group, groupId);
            }
        });
        
        /**
         * Update "select all" checkbox state based on individual options
         */
        function updateSelectAllState($group, groupId) {
            var $selectAll = $group.find('.select-all-checkbox[data-group="' + groupId + '"]');
            var $options = $group.find('.option-availability-checkbox[data-group="' + groupId + '"]');
            var checkedCount = $options.filter(':checked').length;
            var totalCount = $options.length;
            
            if (checkedCount === 0) {
                $selectAll.prop('checked', false).prop('indeterminate', false);
            } else if (checkedCount === totalCount) {
                $selectAll.prop('checked', true).prop('indeterminate', false);
            } else {
                $selectAll.prop('checked', false).prop('indeterminate', true);
            }
        }
    }
    
    // Initialize bulk edit if on bulk edit page
    if ($('.woo-bulk-edit-container').length > 0) {
        initBulkEdit();
    }
});
