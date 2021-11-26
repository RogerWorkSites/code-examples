jQuery(function ($) {
    //30.03 - 14:00
    "use strict";

    /* Function Helpers */
    $.fn.inputFilter = function (inputFilter) {
        // console.log(this);
        // console.log('-----------');
        return this.on("input", function () {
            console.log(
                +this.value,
                this.selectionStart,
                this.selectionEnd,
                this.hasOwnProperty("oldValue"),
                this.oldValue,
                this.value.length,
                /-(?=\d+\d*)/.test(this.value)
            );
        });
    };

    function clearPopup(_this) {
        getParentContainer(_this).find('input:not(:radio)').val('');
        getParentContainer(_this).find('.js_add_base_unit_box, .js_add_coping_box').fadeIn();
        getParentContainer(_this).find('.с-select-wrapp').removeClass('value').removeClass('open-select');
        getParentContainer(_this).find('.с-current-val').html('');
        getParentContainer(_this).find('.select-product, .select_product_base_unit, .select_product_coping').attr('data-product', '');
        getParentContainer(_this).find('.hide-block-combined, .hide_block_base_unit, .hide-block, .for_combined, .error-msg, .for_base_unit, .for_coping').removeClass('open-combined-container').slideUp();
        getParentContainer(_this).find('input[name="err_understand"]').prop('checked', false);
        getParentContainer(_this).find('.add_base_block').addClass('js_not_added_base_unit').addClass('js_not_added_coping').removeClass('js_added_base_unit').removeClass('js_added_coping');
        getParentContainer(_this).find('.for_pillar_cap .с-current-val').text('');
        getParentContainer(_this).find('.input-field-wrapp').removeClass('fail');
        getParentContainer(_this).find('.с-select li').removeClass('active');
        getParentContainer(_this).find('.js_add_calculation').removeClass('js_edit_calculation').removeClass('btn-disabled-base-unit').removeClass('btn-disabled-coping').removeClass('disabled-combined').addClass('btn-disabled');
    }

    function inputFilter(inputItem, type, maxValue) {
        if (maxValue === undefined) {
            maxValue = 100;
        }
        if (type === 'onlyPersentNumber') {
            $(document).on('input', inputItem, function (event) {
                $(this).inputmask({
                    alias: "Regex",
                    regex: "^[1-9]{1}[0-9]{1}$",
                    autoTab: true
                });
            })
        } else if (type === 'onlyNumberNoZero') {
            $(document).on('input', inputItem, function (e) {
                var _this = $(this);
                var maxVal = _this.attr('max');
                var minVal = _this.attr('min');

                $(this).inputmask({
                    alias: "Regex",
                    regex: "^[0-9]{0," + (maxVal.length ? maxVal.length : 9) + "}[.][0-9]{2}$",
                    autoTab: true
                });
            })
        } else if (type === 'onlyNumberZero') {
            $(document).on('input', inputItem, function () {
                var _this = $(this);
                var maxVal = _this.attr('max');
                var minVal = _this.attr('min');

                $(this).inputmask({
                    alias: "Regex",
                    regex: "^[0-9]{0," + (maxVal.length ? maxVal.length : 9) + "}[.][0-9]{2}$",
                    autoTab: true
                });
            })
        } else if (type === 'onlyNumberBaseThickness') {
            $(document).on('input', inputItem, function () {
                var _this = $(this);
                var maxVal = _this.attr('max');
                var minVal = _this.attr('min');

                $(this).inputmask({
                    alias: "Regex",
                    regex: "^[0-9]{0,2}$",
                    autoTab: true
                });
            })
        } else if (type === 'onlyText') {
            $(document).on('input', inputItem, function () {
                $(this).inputmask({
                    alias: "Regex",
                    regex: "^[a-zA-Z0-9]*$",
                    autoTab: true
                });
            })
        }
    }

    function disableButtonFunc(input, checking) {
        if (checking === true) {
            getParentContainer().find(input).each(function (index, element) {
                checkInput($(this));
            });
        } else {
            $(document).on('input', input, function () {
                checkInput($(this));
            });
        }

        function checkInput($this) {
            var val = $this.val();
            var parent = $this.parents('.popup-container');
            var parentButton = parent.find('.btn_calc');

            if (parent.find('.error-msg').length > 0) {
                var disableButton = false;
                var err_height = parent.find('.for_err_height');
                var add_base_block = parent.find('.add_base_block');

                if ($this.hasClass('total_avg')) {
                    var max_height = $this.attr('data-max-height');
                    if (val > +max_height) {
                        parent.find('input[name="err_understand"]').prop('checked', '');
                        err_height.addClass('js_open_error').slideDown();
                        disableButton = true;
                    } else {
                        err_height.removeClass('js_open_error').slideUp();
                        parent.find('input[name="err_understand"]').prop('checked', false);
                    }
                }

                if (!err_height.hasClass('js_open_error') || (err_height.hasClass('js_open_error') && parent.find('input[name="err_understand"]').is(":checked"))) {
                    parent.find(input).each(function () {
                        var _this = $(this);
                        if (_this.val().length === 0 || +_this.val() == 0) {
                            disableButton = true;
                        }
                    });
                } else if (!add_base_block.hasClass('js_not_added_base_unit') || (add_base_block.hasClass('js_open_error') && parent.find('input[name="err_understand"]').is(":checked"))) {
                    parent.find(input).each(function () {
                        var _this = $(this);
                        if (_this.val().length === 0 || +_this.val() == 0) {
                            disableButton = true;
                        }
                    });
                } else if (err_height.hasClass('js_open_error') && parent.find('input[name="err_understand"]').is(":checked")) {
                    disableButton = false;
                } else {
                    disableButton = true;
                }

                buttonDisable(parentButton, disableButton);
            } else {
                var disableButton = false;

                parent.find(input).each(function () {
                    var _this = $(this);
                    if (_this.val().length === 0 || +_this.val() == 0) {
                        disableButton = true;
                    }
                });

                buttonDisable(parentButton, disableButton);
            }
        }
    }

    function buttonDisable(parentButton, disableButton) {
        if (disableButton) {
            parentButton.addClass('btn-disabled');
        } else {
            parentButton.removeClass('btn-disabled');
        }
    }

    function buttonDisableBaseUnit(addedBaseUnit) {
        var disableButton = false,
            add_base_block = getParentContainer().find('.js_add_base_unit'),
            base_block = getParentContainer().find('.select_product_base_unit'),
            parentButton = getParentContainer().find('.btn_calc');

        if (addedBaseUnit) {
            getParentContainer().find('input[name="base_total_ln_feet"]').each(function () {
                var _this = $(this);
                if (_this.val().length === 0 || +_this.val() == 0) {
                    disableButton = true;
                }
            });
            if (base_block.attr('data-product') === '') {
                disableButton = true;
            }
        } else if (!addedBaseUnit) {
            disableButton = false;
        }

        if (disableButton) {
            parentButton.addClass('btn-disabled-base-unit');
        } else {
            parentButton.removeClass('btn-disabled-base-unit');
        }
    }

    function buttonDisableCoping(addedBaseUnit) {
        var disableButton = false,
            add_base_block = getParentContainer().find('.js_add_coping'),
            coping_product = getParentContainer().find('.select_product_coping'),
            parentButton = getParentContainer().find('.btn_calc');

        if (addedBaseUnit) {
            getParentContainer().find('input[name="coping_ln_feet"]').each(function () {
                var _this = $(this);
                if (_this.val().length === 0 || +_this.val() == 0) {
                    disableButton = true;
                }
            });
            if (coping_product.attr('data-product') === '') {
                disableButton = true;
            }
        } else if (!addedBaseUnit) {
            disableButton = false;
        }

        if (disableButton) {
            parentButton.addClass('btn-disabled-coping');
        } else {
            parentButton.removeClass('btn-disabled-coping');
        }
    }

    function getParentContainer(_this) {
        if (_this) {
            return _this.parents('.popup-content');
        } else {
            return $(_functions.currentPopup);
        }
    }

    function slideUpCombined($this, show) {
        if (show === true) {
            getParentContainer($this).find('.for_combined').slideDown();
        } else {
            getParentContainer($this).find('.for_combined').slideUp();
        }
        getParentContainer($this).find('.for_combined_main').removeClass('open-combined-container').slideUp();
        getParentContainer($this).find('.combined-select.с-select-wrapp ul li').removeClass('disabled');

        getParentContainer($this).find('.total_persent_error').fadeIn();
        getParentContainer($this).find('.total-interest .icon').addClass('disabled');
    }

    function getCombinedsResult(_this) {
        var lists = getParentContainer(_this).find('.list-calculate .list-calculate-item');
        var max = 0,
            emptyField = false;

        lists.each(function (index) {
            var item = +lists.eq(index).find('input-field-wrapp');
            var itemVal = +lists.eq(index).find('input').val();
            if (!isNaN(itemVal) && itemVal !== 0) {
                max += itemVal;
            } else {
                emptyField = true;
            }
        });

        if (max < 100 || max > 100) {
            getParentContainer(_this).find('.js_button_submit_adding').addClass('disabled-combined');
            if (max > 100) {
                getParentContainer(_this).find('.amount').find('i').html(100);
            } else if (max < 100) {
                getParentContainer(_this).find('.amount').find('i').html(max);
            }
            getParentContainer(_this).find('.total_persent_error').fadeIn();
            getParentContainer(_this).find('.total-interest .icon').addClass('disabled');
            return max;
        } else {
            if (!emptyField) {
                getParentContainer(_this).find('.js_button_submit_adding').removeClass('disabled-combined');
            }
            getParentContainer(_this).find('.total_persent_error').fadeOut();
            getParentContainer(_this).find('.amount').find('i').html(100);
            getParentContainer(_this).find('.total-interest .icon').removeClass('disabled');
            return max;
        }
    }

    function removeAllCombinedProduct(_this) {
        var listItems = getParentContainer(_this).find('.for_combined_main').find('.list-calculate-item');
        listItems.find('input').val('');
        listItems.each(function (index) {
            if (index > 0) {
                listItems.eq(index).remove();
            }
        });
        getParentContainer(_this).find('.js_button_submit_adding').removeClass('disabled-combined');
        getParentContainer(_this).find('.amount').find('i').html(0);
        getParentContainer(_this).find('.input-field-wrapp').removeClass('fail');
    }

    function getCombinedItemPopup(dataCombaind, data) {
        var comb_pdf = "#",
            pdf_class = 'pdf_disabled';
        if (data.pdf && data.pdf != '') {
            comb_pdf = data.pdf;
            pdf_class = ''
        }
        return `<div class="list-calculate-item" data-id='${data.id}' data-combined='${dataCombaind}'>
                <div class="caption-product-with-control">
                    <div class="caption caption-combined">${data.title}</div>
                    <div class="control-btn">
                        <ul>
                            <li class="copy"><a class="comb_pdf ${pdf_class}" href="${comb_pdf}" target="_blank"><img class="icon" src="/wp-content/themes/unilock/calculator/assets/img/pdf.svg" alt="pdf-icon"></a></li>
                            <li class="remove"><img class="icon" src="/wp-content/themes/unilock/calculator/assets/img/delete-icon.svg" alt="delete-icon"></li>
                        </ul>
                    </div>
                </div>
                <div class="input-field-wrapp">
                    <div class="input-placeholder">Enter % of installation area</div>
                    <input class="input_calc" type="text" inputmode="number" min="0" max="99" step="1" name="area">
                </div>
            </div>`;
    }

    function getItemForCombined(data, dataCombined) {
        var comb_pdf = '#',
            pdf_class = 'pdf_disabled';

        if (data.pdf && data.pdf != '') {
            comb_pdf = data.pdf;
            pdf_class = '';
        }

        return `<div class="list-calculate-item" data-id='${data.id}' data-combined='${dataCombined}'>
                    <div class="caption-product-with-control">
                        <div class="caption caption-first">${data.title}</div>
                        <div class="control-btn">
                            <ul>
                                <li class="copy"><a class="comb_pdf ${pdf_class}" href="${comb_pdf}" target="_blank"><img class="icon" src="/wp-content/themes/unilock/calculator/assets/img/pdf.svg" alt="pdf-icon"></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="input-field-wrapp">
                        <div class="input-placeholder">Enter % of installation area</div>
                        <input class="input_calc" type="text" inputmode="number" min="0" max="99" step="1" name="area" value="">
                    </div>
                </div>`;
    }

    /* END Function Helpers */

    /*CUSTOM 2*/
    inputFilter('.for_combined_main input[name="area"]', 'onlyPersentNumber');
    inputFilter('.input_chacked, .input_optional_number, .base_total_sq, .coping_total_sq', 'onlyNumberNoZero');
    inputFilter('.input_optional_number', 'onlyNumberZero');
    inputFilter('input[name="base_thickness"]', 'onlyNumberBaseThickness');
    // inputFilter('.input_optional_text', 'onlyText');

    $('input[name="base_thickness"]').on('input', function () {
        var inp = $(this),
            max = parseInt(inp.attr('max')) + 1,
            min = parseInt(inp.attr('min')),
            inp_val = parseInt(inp.val());
        /*console.log(inp_val, max, min);
        console.log(inp_val < max, inp_val >= min);*/
        if (inp_val < max && inp_val >= min) {
            $('.js_calc_result').removeClass('disabled');
            inp.parents('.base-thickness').removeClass('err');
        } else {
            $('.js_calc_result').addClass('disabled');
            inp.parents('.base-thickness').addClass('err');
        }
    });

    $(document).on('click', '.calc-requirement-close', function () {
        $('input[name="base_thickness"]').val(4);
        $('.js_calc_result').removeClass('disabled');
        $('.base-thickness').removeClass('err');
    })

    disableButtonFunc('.input_chacked', false);

    $(document).on('click', 'input[name="err_understand"]', function () {
        var _this = $(this);
        var parent = _this.parents('.popup-container');
        var parentButton = parent.find('.btn_calc');

        // console.log('CheckBox: ', !_this.is(":checked"));
        // console.log('CheckBox: ', _this.is(":checked"));
        if (!_this.is(":checked")) {
            buttonDisable(parentButton, true);
        } else {
            // console.log('CheckBox: ', parent.find('.input_chacked'));
            var disableButton = false;

            parent.find('.for_err_height').removeClass('js_open_error');
            parent.find('.for_err_height').slideUp();

            parent.find('.input_chacked').each(function () {
                var _this = $(this);
                if (_this.val().length === 0) {
                    disableButton = true;
                } else if (+_this.val() === 0) {
                    disableButton = true;
                }
            });
            buttonDisable(parentButton, disableButton);
        }
    });

    $('#form_project_submit').on('keyup keypress', function (e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

    $(document).on('click', '.combined-select.с-select-wrapp > .с-select > ul > li', function () {
        var _this = $(this);
        var dataCombaind = _this.attr('data-combined');
        var dataCombaindCornerId = _this.attr('data-corner-id');
        var listContainer = _this.parents('.inner-middle-popup').find('.list-calculate');
        var data = JSON.parse(dataCombaind);
        if (dataCombaindCornerId !== '') data['corner_id'] = dataCombaindCornerId;

        var itemAdding = getCombinedItemPopup(JSON.stringify(data), data);

        if (listContainer.find('.list-calculate-item').length < 4) {
            _this.addClass('disabled');
            listContainer.append(itemAdding);
        } else {
            console.info('ERROR ADDIN COMBINED PRODUCT');
        }
        // max = getCombinedsResult(_this);
    });

    //remove one instalation item
    $(document).on('click', '.list-calculate-item .control-btn .remove', function (e) {
        var _this = $(this);
        var calculate = $(_functions.currentPopup).find('.list-calculate .list-calculate-item');
        var combined = _this.parents('.list-calculate-item');
        var combinedId = combined.attr('data-id');
        var selects = getParentContainer().find('.combined-select ul li');

        selects.each(function (index) {
            var itemId = selects.eq(index).attr('data-id');
            if (itemId === combinedId) {
                selects.eq(index).removeClass('disabled').removeClass('active');
            }
        });
        combined.remove();

        var inputAmount = combined.find('input').val();
        var amount = $(_functions.currentPopup).find('.amount').find('i').text();
        var newAmount = +amount - inputAmount;

        $(_functions.currentPopup).find('.amount').find('i').html(newAmount);

        getParentContainer().find('.total_persent_error').fadeIn();
        getParentContainer().find('.total-interest .icon').addClass('disabled');

        if (newAmount < 100) {
            $(_functions.currentPopup).find('.js_button_submit_adding').addClass('disabled-combined');
        } else {
            $(_functions.currentPopup).find('.js_button_submit_adding').removeClass('disabled-combined');
        }

        if (calculate.length <= 1) {
            slideUpCombined(false, true);
            removeAllCombinedProduct(false);
        }
    });

    //remove all instalation item
    $(document).on('click', '.calculate-instalation-area .remove-all', function () {
        var _this = $(this);
        getCombinedsResult(_this);
        slideUpCombined(_this, true);
        removeAllCombinedProduct(_this);
    });

    $(document).on('click', '.js_data_combined_click', function () {
        var _this = $(this),
            title = _this.parents('.inner-middle-popup').find('.select-product .с-select ul li.active').text();
        if (title !== '') {
            _this.parents('.inner-middle-popup').find('.list-calculate .caption-first').html('');
            _this.parents('.inner-middle-popup').find('.list-calculate .caption-first').html(title);
        }
        _this.parents('.for_combined').slideUp();
        getParentContainer(_this).find('.hide-block-combined').addClass('open-combined-container').slideDown();
        getParentContainer(_this).find('.js_button_submit_adding').removeClass('disabled').addClass('disabled-combined');
    });

    $(document).on('click', '.back_to_product', function () {
        var _this = $(this);
        clearPopup(_this);
        removeAllCombinedProduct(_this);
    });

    $(document).on('input', '.for_combined_main input', function () {
        var max = 0;
        var _this = $(this);
        var val = _this.val();

        max = getCombinedsResult(_this);

        var last = 100 - max;
        if (val.trim() < 0) {
            _this.val(0);
        }
    });

    $(document).on('click', '.js_add_combined, .combined-select', function () {
        var _this = $(this);
        var containerItem = _this.parents('.for_combined_main').find('.combined-select');

        if (containerItem.hasClass('open')) {
            containerItem.removeClass('open');
        } else {
            containerItem.addClass('open');
        }
    });

    /*END CUSTOM 2*/

    /*CUSTOM 1*/
    // $('.popup-wrapper').removeClass('active');

    function ajaxAddProduct(cat_slug, popup, num, callback) {
        loaderPopup('show');
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_add_prod_popup',
            'cat_slug': cat_slug,
        })
            .done(function (response) {
                var res = $.parseJSON(response);

                if (res.success && res.select != '') {
                    popup.find('.popup-caption span').html(num);
                    popup.find('.select-product .с-select').html(res.select);
                    popup.find('input[value="sailor"]').prop('checked', true);
                    _functions.ucalc_openPopup('.popup-content[data-rel="' + cat_slug + '"]');
                }

                loaderPopup('hide');

                if (typeof callback === 'function') {
                    callback();
                }
            })
            .fail(function (response) {
                console.log(response);
                loaderPopup('hide');
            });
    }

    function addCombinedProduct(popup, productJSON) {
        var combined_select = popup.find('.combined-select'),
            type = popup.attr('data-rel');
        // console.log(type);
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_add_combined_select',
            'data_prod': JSON.parse(productJSON),
            'type': type
        })
            .done(function (response) {
                var res = $.parseJSON(response);

                if (res.success) {
                    var dataProduct = JSON.parse(productJSON);
                    combined_select.find('.с-select').html(res.combined_select);
                    var selects = popup.find('.combined-select ul li');
                    var items = popup.find('.list-calculate .list-calculate-item');
                    var idsArray = [];
                    items.each(function (index) {
                        idsArray.push(items.eq(index).attr('data-id'));
                    });

                    selects.each(function (index) {
                        var itemId = selects.eq(index).attr('data-id');
                        if (idsArray.includes(itemId)) {
                            selects.eq(index).addClass('disabled').removeClass('active');
                        }
                    });
                    // combined_select.addClass('open-select');
                } else if (!res.success) {
                    combined_select.find('.с-select').html('');
                }
                //loaderPopup('hide');

            })
            .fail(function (response) {
                console.log(response);
                //loaderPopup('hide');
            });
    }

    // -- Popup add product
    $(document).on('click', '.add_prod_popup', function (e) {
        e.preventDefault();
        var popup_btn = $(this),
            cat_slug = popup_btn.data('rel'),
            popup = $('.popup_' + cat_slug),
            num = num_added_product(cat_slug, false);

        ajaxAddProduct(cat_slug, popup, num);
    });

    $(document).on('input', 'input[name="base_total_ln_feet"]', function () {
        buttonDisableBaseUnit(true);
    })

    $(document).on('input', 'input[name="coping_ln_feet"]', function () {
        buttonDisableCoping(true);
    })

    // -- Popup add product
    $(document).on('click', '.js_remove_prod', function (e) {
        var _this = $(this),
            select_wrapp = _this.parents('.select-product').find('.с-select-wrapp');

        if (select_wrapp.hasClass('open-select')) {
            select_wrapp.removeClass('open-select');
        } else if (!select_wrapp.hasClass('open-select') && !select_wrapp.hasClass('value')) {
            select_wrapp.addClass('open-select');
        } else if (select_wrapp.hasClass('value')) {
            clearPopup(_this);
            removeAllCombinedProduct(_this);
        }
    });

    $(document).on('click', '.js_remove_base_prod', function (e) {
        var _this = $(this),
            select_wrapp = _this.parents('.select_product_base_unit').find('.с-select-wrapp');

        if (select_wrapp.hasClass('open-select')) {
            select_wrapp.removeClass('open-select');
        } else if (!select_wrapp.hasClass('open-select') && !select_wrapp.hasClass('value')) {
            select_wrapp.addClass('open-select');
        } else if (select_wrapp.hasClass('value')) {
            _this.parents('.inner-middle-popup').find('.js_add_base_unit_box').fadeIn();
            _this.parents('.inner-middle-popup').find('.add_base_block').addClass('js_not_added_base_unit').removeClass('js_added_base_unit').slideDown();
            _this.parents('.inner-middle-popup').find('.for_base_unit').slideUp();
            _this.parents('.inner-middle-popup').find('.for_base_unit input.input_calc').val('');
            _this.parents('.inner-middle-popup').find('.select_product_base_unit').attr('data-product', '');
            _this.parents('.inner-middle-popup').find('.for_base_unit .с-current-val').html('');
            _this.parents('.inner-middle-popup').find('.for_base_unit .input-field-wrapp').removeClass('fail').removeClass('value');

            buttonDisableBaseUnit(false);
        }
    });

    $(document).on('click', '.js_remove_coping_prod', function (e) {
        var _this = $(this),
            select_wrapp = _this.parents('.select_product_coping').find('.с-select-wrapp');

        if (select_wrapp.hasClass('open-select')) {
            select_wrapp.removeClass('open-select');
        } else if (!select_wrapp.hasClass('open-select') && !select_wrapp.hasClass('value')) {
            select_wrapp.addClass('open-select');
        } else if (select_wrapp.hasClass('value')) {
            _this.parents('.inner-middle-popup').find('.js_add_coping_box').fadeIn();
            _this.parents('.inner-middle-popup').find('.add_base_block').addClass('js_not_added_coping').removeClass('js_added_coping').slideDown();
            _this.parents('.inner-middle-popup').find('.for_coping').slideUp();
            _this.parents('.inner-middle-popup').find('.for_coping input.input_calc').val('');
            _this.parents('.inner-middle-popup').find('.select_product_coping').attr('data-product', '');
            _this.parents('.inner-middle-popup').find('.for_coping .с-current-val').html('');
            _this.parents('.inner-middle-popup').find('.for_coping .input-field-wrapp').removeClass('fail').removeClass('value');

            buttonDisableCoping(false);
        }
    });

    $(document).on('click', '.js_remove_pillar_cap', function (e) {
        var _this = $(this),
            pillar_cap = _this.parents('.for_pillar_cap'),
            select_wrapp = _this.parents('.for_pillar_cap').find('.с-select-wrapp');

        if (select_wrapp.hasClass('open-select')) {
            select_wrapp.removeClass('open-select');
        } else if (!select_wrapp.hasClass('open-select') && !select_wrapp.hasClass('value')) {
            select_wrapp.addClass('open-select');
        } else if (select_wrapp.hasClass('value')) {
            pillar_cap.find('.с-current-val').html('');
            select_wrapp.removeClass('value');
            pillar_cap.find('.с-select ul li').removeClass('active');
        }
    })


    /*BASE UNIT SELECT*/
    $(document).on('click', '.select_product_base_unit > .с-select-wrapp > .с-select > ul > li', function () {
        var $this = $(this),
            parent = $this.parents('.popup-container'),
            product = $this.attr('data-product'),
            productObject = JSON.parse(product),
            add_base_block = parent.find('.js_add_base_unit'),
            base_units_input = parent.find('input[name="base_total_ln_feet"]'),
            base_block = $this.parents('.select_product_base_unit'),
            parentButton = parent.find('.btn_calc');

        var productJSON = {
            'id': productObject.id,
            'title': productObject.title,
            'family': productObject.family,
            'height': productObject.height,
            'cat': productObject.cat,
            'sailor_or': productObject.sailor_or,
            'soldier_or': productObject.soldier_or,
        };

        //--Orientation
        if (productObject.sailor_or == false) {
            parent.find('.for_base_unit .sailor_or').removeClass('active').addClass('disabled');
            parent.find('.for_base_unit .soldier_or').removeClass('disabled').addClass('active');
            parent.find('.for_base_unit .border-orientation input[value="sailor"]').prop("checked", false);
            parent.find('.for_base_unit .border-orientation input[value="soldier"]').prop("checked", true);
        } else if (productObject.soldier_or == false) {
            parent.find('.for_base_unit .soldier_or').removeClass('active').addClass('disabled');
            parent.find('.for_base_unit .sailor_or').removeClass('disabled').addClass('active');
            parent.find('.for_base_unit .border-orientation input[value="soldier"]').prop("checked", false);
            parent.find('.for_base_unit .border-orientation input[value="sailor"]').prop("checked", true);
        } else {
            parent.find('.for_base_unit .soldier_or').removeClass('active').removeClass('disabled');
            parent.find('.for_base_unit .sailor_or').removeClass('disabled').addClass('active');
            parent.find('.for_base_unit .border-orientation input[value="soldier"]').prop("checked", false);
            parent.find('.for_base_unit .border-orientation input[value="sailor"]').prop("checked", true);
        }

        base_block.attr('data-product', JSON.stringify(productJSON));

        buttonDisableBaseUnit(true);
    })

    $(document).on('click', '.select_product_coping > .с-select-wrapp > .с-select > ul > li', function () {
        var $this = $(this),
            parent = $this.parents('.popup-container'),
            product = $this.attr('data-product'),
            productObject = JSON.parse(product),
            add_coping = parent.find('.js_add_coping'),
            coping_input = parent.find('input[name="coping_ln_feet"]'),
            coping_block = $this.parents('.select_product_coping'),
            parentButton = parent.find('.btn_calc');

        var productJSON = {
            'id': productObject.id,
            'title': productObject.title,
            'family': productObject.family,
            'height': productObject.height,
            'cat': productObject.cat,
            'sailor_or': productObject.sailor_or,
            'soldier_or': productObject.soldier_or,
        };

        //--Orientation
        if (productObject.sailor_or == false) {
            parent.find('.for_coping .sailor_or').removeClass('active').addClass('disabled');
            parent.find('.for_coping .soldier_or').removeClass('disabled').addClass('active');
            parent.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", false);
            parent.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", true);
        } else if (productObject.soldier_or == false) {
            parent.find('.for_coping .soldier_or').removeClass('active').addClass('disabled');
            parent.find('.for_coping .sailor_or').removeClass('disabled').addClass('active');
            parent.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", false);
            parent.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", true);
        } else {
            parent.find('.for_coping .soldier_or').removeClass('active').removeClass('disabled');
            parent.find('.for_coping .sailor_or').removeClass('disabled').addClass('active');
            parent.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", false);
            parent.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", true);
        }

        coping_block.attr('data-product', JSON.stringify(productJSON));

        buttonDisableCoping(true);
    })

    $(document).on('click', '.select-product > .с-select-wrapp > .с-select > ul > li', function () {
        var $this = $(this),
            popup_slug = $this.parents('.popup-content').data('rel'),
            product = $this.attr('data-product'),
            product_corner_id = $this.attr('data-corner-id'),
            productObject = JSON.parse(product);

        // if (productObject.family !== '') {
        //   slideUpCombined($this, true);
        // } else {
        //   slideUpCombined($this, false);
        // }

        removeAllCombinedProduct($this);

        var productJSON = {
            'id': productObject.id,
            'title': productObject.title,
            'corner_title': productObject.corner_title,
            'family': productObject.family,
            'family_name': productObject.family_name,
            'height': productObject.height,
            'cat': productObject.cat,
        };

        if (productObject.steps_info) {
            productJSON['steps_info'] = {
                'height_inch_val': productObject.steps_info.height_inch_val,
                'height_inch': productObject.steps_info.height_inch,
                'width_inch': productObject.steps_info.width_inch,
                'length_inch': productObject.steps_info.length_inch,
            };
        }

        var popup = $(_functions.currentPopup),
            combined_select = popup.find('.combined-select');

        //change family name
        popup.find('.family_option span').text(productObject.family_name);

        //for walls - max height
        if ((popup_slug == 'walls' || popup_slug == 'wall-panel')) {
            hideBaseUnitsBox($this);
            hideCopingBox($this);
        }
        if ((popup_slug == 'walls' || popup_slug == 'wall-panel') && product_corner_id) productJSON['corner_id'] = product_corner_id;
        if ((popup_slug == 'walls' || popup_slug == 'wall-panel') && productObject.max_height) {
            productJSON['max_height'] = productObject.max_height;
            popup.find('.total_avg').attr('data-max-height', productObject.max_height);
        }
        if (popup_slug == 'pillar') {
            productJSON['pillar_unit'] = productObject.pillar_unit;
            if (productObject.pillar_unit) {
                $this.parents('.popup-content').find('.for_pillar_kit').slideDown();
            } else {
                $this.parents('.popup-content').find('.for_pillar_kit').slideUp();
            }
        }

        //for PDF
        if (productObject.pdf) productJSON['pdf'] = productObject.pdf;

        var combinedContainer = $this.parents('.popup-content').find('.list-calculate');
        var combinedContainerItems = $this.parents('.popup-content').find('.list-calculate-item');
        if (combinedContainerItems.length === 0) {
            var itemCombined = getItemForCombined(productJSON, JSON.stringify(productJSON));
            combinedContainer.append(itemCombined);
        }

        productJSON = JSON.stringify(productJSON);
        $this.parents('.popup-container').find('.list-calculate-item').eq(0).attr('data-combined', productJSON).attr('data-id', productObject.id);
        $this.parents('.select-product').attr('data-product', productJSON);

        /*BASE UNIT*/
        $this.parents('.popup-container').find('.add_base_block').addClass('js_not_added_base_unit');
        $this.parents('.popup-container').find('.for_base_unit').slideUp();
        $this.parents('.popup-container').find('.for_base_unit input.input_calc').val('');
        $this.parents('.popup-container').find('.select_product_base_unit').attr('data-product', '');
        $this.parents('.popup-container').find('.for_base_unit .с-current-val').html('');


        //--Orientation
        if (productObject.sailor_or == false) {
            popup.find('.sailor_or').removeClass('active').addClass('disabled');
            popup.find('.soldier_or').removeClass('disabled').addClass('active');
            popup.find('.border-orientation input[value="sailor"]').prop("checked", false);
            popup.find('.border-orientation input[value="soldier"]').prop("checked", true);
        } else if (productObject.soldier_or == false) {
            popup.find('.soldier_or').removeClass('active').addClass('disabled');
            popup.find('.sailor_or').removeClass('disabled').addClass('active');
            popup.find('.border-orientation input[value="soldier"]').prop("checked", false);
            popup.find('.border-orientation input[value="sailor"]').prop("checked", true);
        } else {
            popup.find('.soldier_or').removeClass('active').removeClass('disabled');
            popup.find('.sailor_or').removeClass('disabled').addClass('active');
            popup.find('.border-orientation input[value="soldier"]').prop("checked", false);
            popup.find('.border-orientation input[value="sailor"]').prop("checked", true);
        }

        //--for steps  info
        if (productObject.steps_info) {
            var steps = productObject.steps_info,
                total_el_rise = parseInt(popup.find('input[name="total_el_rise"]').val());
            popup.find('.one-third .unit_height').attr('data-height-val', steps.height_inch_val);
            popup.find('.one-third .unit_height span').text(steps.height_inch);
            // popup.find('.one-third .unit_width span').text(steps.width_inch);
            // popup.find('.one-third .unit_depth span').text(steps.length_inch);
            popup.find('.one-third .unit_width span').text(steps.length_inch);
            popup.find('.one-third .unit_depth span').text(steps.width_inch);

            steps_calc(total_el_rise, popup);
        }

        //for PDF
        if (productObject.pdf && productObject.pdf != '') {
            popup.find('.pdf_url').attr('href', productObject.pdf).removeClass('pdf_disabled');
        } else {
            popup.find('.pdf_url').addClass('pdf_disabled');
        }
        /* AJAX ADD COMBINED */

        combined_select.removeClass('open-select');
        slideUpCombined();
        removeAllCombinedProduct();
        //loaderPopup('show');
        //console.log(popup_slug);
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_add_combined_select',
            'data_prod': JSON.parse(productJSON),
            'type': popup_slug,
        })
            .done(function (response) {
                var res = $.parseJSON(response);

                if (res.success) {
                    combined_select.find('.с-select').html(res.combined_select);
                    popup.find('.for_combined').slideDown();
                    // combined_select.addClass('open-select');

                    productJSON = JSON.parse(productJSON);
                    productJSON['has_combineds'] = true;
                    productJSON = JSON.stringify(productJSON);
                    $this.parents('.popup-container').find('.list-calculate-item').eq(0).attr('data-combined', productJSON).attr('data-id', productObject.id);
                    $this.parents('.select-product').attr('data-product', productJSON);
                } else if (!res.success) {
                    combined_select.find('.с-select').html('');
                    popup.find('.for_combined, .for_combined').slideUp();
                }
                //loaderPopup('hide');

            })
            .fail(function (response) {
                console.log(response);
                //loaderPopup('hide');
            });

        disableButtonFunc('.input_chacked', true);
    });

    // -- add to calculation
    $(document).on('click', '.js_add_calculation', function (e) {
        e.preventDefault();

        var _this = $(this),
            inner_middle = _this.parents('.popup-align'),
            type = _this.parents('.popup-content').data('rel'),
            var_select = inner_middle.find('.select-product'),
            data_prod = JSON.parse(var_select.attr('data-product')),
            orientation = inner_middle.find('input[name="orientation"]:checked').val(),
            total_sq = inner_middle.find('.total_sq').val(),
            num = inner_middle.find('.popup-caption span').text(),
            combined_container = _this.parents('.popup-content').find('.for_combined_main'),
            combineds = _this.parents('.popup-content').find('.list-calculate .list-calculate-item'),
            combined = [];
        _this.addClass('btn-disabled');

        if (combineds.length && combined_container.hasClass('open-combined-container')) {
            combineds.each(function (index) {
                var data = combineds.eq(index).attr('data-combined');
                if (data !== undefined) {
                    var jsonData = JSON.parse(data);
                    jsonData.persent = combineds.eq(index).find('input').val();
                    combined.push(jsonData);
                }
            });
        }

        var data_prod_new = {
            'id': data_prod.id,
            'title': data_prod.title,
            'corner_title': data_prod.corner_title,
            'family': data_prod.family,
            'height': data_prod.height,
            'combined': combined,
            'cat': data_prod.cat,
            'has_combineds': data_prod.has_combineds,
            'type': type,
            'total_sq': total_sq,
            'orientation': orientation,
            'num': num
        };

        if (type == 'pillar') {
            data_prod_new['total_inch'] = inner_middle.find('input[name="total_inch"').val();
            data_prod_new['total_num'] = inner_middle.find('input[name="total_num"').val();
            data_prod_new['pillar_cap'] = inner_middle.find('.for_pillar_cap .с-current-val').text();
            data_prod_new['pillar_cap_id'] = inner_middle.find('.for_pillar_cap .select-pillar-cap ul li.active').attr('data-id');
            data_prod_new['pillar_unit'] = data_prod.pillar_unit;
        } else if (type == 'steps') {
            data_prod_new['total_un_req'] = inner_middle.find('input[name="total_un_req"').val();
            data_prod_new['steps_info'] = data_prod.steps_info;
        } else if (type == 'walls' || type == 'wall-panel') {
            data_prod_new['corner_id'] = data_prod.corner_id;
            data_prod_new['max_height'] = data_prod.max_height;
            data_prod_new['total_avg'] = inner_middle.find('input[name="avg_ht"').val();
            var num_outside_corners = inner_middle.find('input[name="num_outside_corners"').val();
            if (num_outside_corners == '') num_outside_corners = 0;
            data_prod_new['outside_corners'] = num_outside_corners;

            var base_unit = _this.parents('.popup-content').find('.add_base_block');
            var base_unit_orientation = _this.parents('.popup-content').find('.for_base_unit input[name="orientation"]:checked');
            var base_total_sq = _this.parents('.popup-content').find('.for_base_unit input.base_total_sq');
            var product_base_unit = _this.parents('.popup-content').find('.select_product_base_unit');


            var coping_orientation = _this.parents('.popup-content').find('.for_coping input[name="coping_orientation"]:checked');
            var coping_total_sq = _this.parents('.popup-content').find('.for_coping input.coping_total_sq');
            var product_coping = _this.parents('.popup-content').find('.select_product_coping');

            if (base_unit.length && !base_unit.hasClass('js_not_added_base_unit')) {
                data_prod_new['base_unit'] = JSON.parse(product_base_unit.attr('data-product'));
                data_prod_new['base_unit']['orientation'] = base_unit_orientation.val();
                data_prod_new['base_unit']['total_sq'] = base_total_sq.val();
            }

            if (base_unit.length && !base_unit.hasClass('js_not_added_coping')) {
                data_prod_new['coping'] = JSON.parse(product_coping.attr('data-product'));
                if (!_this.hasClass('js_edit_calculation')) {
                    data_prod_new['coping']['num'] = num_added_product('coping', false, false);
                } else {
                    data_prod_new['coping']['num'] = product_coping.attr('data-coping-num');
                }
                data_prod_new['coping']['orientation'] = coping_orientation.val();
                data_prod_new['coping']['total_sq'] = coping_total_sq.val();
            }
        }

        data_prod_new = JSON.stringify(data_prod_new);

        // console.log(data_prod);
        // console.log(data_prod_new);

        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_add_calculation',
            'data_prod': JSON.parse(data_prod_new),
        })
            .done(function (response) {
                var res = $.parseJSON(response);
                //console.log(response);
                if (res.success) {
                    //remove edit prod from table
                    var trasient_reset = false;
                    if (_this.hasClass('js_edit_calculation')) {
                        var dataAdded = _this.attr('data-product');
                        var dataRemove = JSON.parse(dataAdded);
                        remove_edit_prod(dataRemove.type, dataRemove.num, dataRemove.id);
                        trasient_reset = true;
                    }

                    //$('.container-block .add_products table tbody').html(res.table_html);
                    $('.container-block table.added-products > tbody').append(res.table_html);
                    $('.container-block .add_products').removeClass('hide_block');
                    $('.container-block .empty-project-block').addClass('hide_block');
                    inner_middle.find('.total_sq').val('');
                    inner_middle.find('.orientation-item').removeClass('active');
                    inner_middle.find('input[name="orientation"]').prop("checked", false);
                    inner_middle.find('input[value="sailor"]').prop("checked", true);
                    inner_middle.find('input[value="sailor"]').parents('.orientation-item').addClass('active');
                    _this.addClass('btn-disabled');

                    clearPopup(_this);
                    removeAllCombinedProduct(_this);
                    hideCatCheck();
                    if (trasient_reset) {
                        transient_resave();
                    }
                    _functions.ucalc_closePopup();
                } else if (!res.success) {
                    _this.removeClass('btn-disabled');
                }
            })
            .fail(function (response) {
                console.log(response);
                _this.removeClass('btn-disabled');
            });
    });

    // //-- remove product tr from table
    // $(document).on('click', '.js_remove_prod', function (e) {
    //   var _this = $(this),
    //     tr = _this.parents('tr'),
    //     type = tr.data('type');
    //   tr.remove();
    //   num_added_product(type, true);
    //   transient_resave();
    // });

    //-- edit product tr in table
    $(document).on('click', '.js_edit_prod', function (e) {
        var _this = $(this),
            tr = _this.parents('tr'),
            num = tr.find('td.category span i').text(),
            id = tr.data('id'),
            data_prod = tr.attr('data-add-prod'),
            json_data = JSON.parse(data_prod),
            type = tr.data('type'),
            popup = $('.popup_' + type),
            total_sq = popup.find('.total_sq');
        //total_sq = popup.find('.input_chacked');

        popup.find('.popup-caption span').html(num);
        if (json_data.total_un_req) popup.find('input[name="total_un_req"]').val(json_data.total_un_req);

        if (type == 'steps' && json_data.steps_info) {
            popup.find('.unit_height').attr('data-height-val', json_data.steps_info.height_inch_val);
            popup.find('.unit_height span').text(json_data.steps_info.height_inch);
            // popup.find('.unit_width span').text(json_data.steps_info.width_inch);
            // popup.find('.unit_depth span').text(json_data.steps_info.length_inch);
            popup.find('.unit_width span').text(json_data.steps_info.length_inch);
            popup.find('.unit_depth span').text(json_data.steps_info.width_inch);
            steps_calc(parseInt(json_data.total_sq), popup);
        } else if (type == 'pillar') {
            if (json_data.total_inch) popup.find('input[name="total_inch"]').val(json_data.total_inch);
            if (json_data.total_num) popup.find('input[name="total_num"]').val(json_data.total_num);
        }

        if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
            popup.find('.for_combined').slideDown();
        }

        total_sq.val(json_data.total_sq);
        //popup.find('.с-select-wrapp').addClass('value');

        var combinedContainer = popup.find('.list-calculate');
        var combinedContainerItems = popup.find('.list-calculate-item');
        if (combinedContainerItems.length === 0) {
            var itemCombined = getItemForCombined(json_data, JSON.stringify(json_data));
            combinedContainer.append(itemCombined);
        }

        function productEdit(json_data, popup, type) {
            popup.find('.select-product .с-select .с-current-val').text(json_data.title.replace(/\\(.)/g, "in"));
            popup.find('.input-field-wrapp').addClass('focus');
            popup.find('.select-product .с-select-wrapp').addClass('value');
            popup.find('.select-product .с-select ul li').removeClass('active');
            popup.find('.select-product .с-select ul li').each(function () {
                var this_li = $(this),
                    res_data = '';
                if (json_data.id == this_li.data('id')) {
                    if (type === 'walls' || type === 'wall-panel') {
                        this_li.addClass('active');
                        res_data = this_li.attr('data-product');
                        var corder_id = this_li.attr('data-corner-id');
                        var dataParse = JSON.parse(res_data);
                        dataParse['corner_id'] = corder_id;

                        if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
                            dataParse['has_combineds'] = json_data.has_combineds;
                        }

                        popup.find('.select-product').attr('data-product', JSON.stringify(dataParse));
                    } else {
                        this_li.addClass('active');
                        res_data = this_li.attr('data-product');
                        var dataParse = JSON.parse(res_data);
                        if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
                            dataParse['has_combineds'] = json_data.has_combineds;
                        }
                        popup.find('.select-product').attr('data-product', JSON.stringify(dataParse));
                    }
                }
            });
            if (json_data.pillar_cap && type == 'pillar') {
                popup.find('.for_pillar_cap .select-pillar-cap .с-current-val').text(json_data.pillar_cap.replace(/\\(.)/g, "in"));
                popup.find('.for_pillar_cap .с-select-wrapp').addClass('value');
                popup.find('.for_pillar_cap .select-pillar-cap ul li').each(function () {
                    var this_li = $(this),
                        this_li_txt = this_li.text();
                    if (json_data.pillar_cap == this_li_txt) {
                        this_li.addClass('active');
                    }
                });
            }


            popup.find('.js_add_calculation').removeClass('btn-disabled').addClass('js_edit_calculation').attr('data-product', data_prod);
            popup.find('.hide-block').slideDown();

            _functions.ucalc_openPopup('.popup-content[data-rel="' + type + '"]');

            var popupJSON = popup.find('.select-product').attr('data-product');
            if (popupJSON != 'undefined') {
                popupJSON = JSON.parse(popupJSON);
            }

            popup.find('.orientation-item').removeClass('disabled');
            if (popupJSON.sailor_or == false) {
                popup.find('.orientation-item.sailor_or').addClass('disabled');
            }
            if (popupJSON.soldier_or == false) {
                popup.find('.orientation-item.soldier_or').addClass('disabled');
            }

            popup.find('.orientation-item').removeClass('active');
            popup.find('input[name="orientation"]').prop("checked", false);
            popup.find('input[value="' + json_data.orientation + '"]').prop("checked", true);
            popup.find('input[value="' + json_data.orientation + '"]').parents('.orientation-item').addClass('active').removeClass('disabled');
        };
        if (popup.find('.select-product .с-select ul li').length > 0) {
            productEdit(json_data, popup, type);
        } else {
            ajaxAddProduct(type, popup, num, function () {
                productEdit(json_data, popup, type)
            });
        }


    });

    $(document).on('click', '.js_edit_combined_prod', function (e) {
        var _this = $(this),
            tr = _this.parents('tr.nested-tr'),
            // num = tr.find('td.category span i').eq(0).text(),
            id = tr.find('tr.active').eq(0).data('id'),
            data_prod = tr.attr('data-add-prod'),
            data_combined = tr.attr('data-combined'),
            data_type = tr.attr('data-type'),
            json_data = JSON.parse(data_prod),
            type = tr.data('type'),
            popup = $('.popup_' + type),
            total_sq = popup.find('.total_sq');

        var items = '';

        popup.find('.popup-caption span').html(json_data.num);
        total_sq.val(json_data.total_sq);

        //console.log(data_combined);
        function productEdit(json_data, popup, type) {
            popup.find('.list-calculate').html('');

            popup.find('.input-field-wrapp').addClass('focus');
            popup.find('.select-product .с-select-wrapp').addClass('value');
            popup.find('.select-product .с-select .с-current-val').text(json_data.title.replace(/\\(.)/g, "\""));
            popup.find('.select-product .с-select ul li').removeClass('active');
            popup.find('.hide-block').slideDown();
            popup.find('.select-product .с-select ul li').each(function () {
                var this_li = $(this),
                    res_data = '';
                if (json_data.id == this_li.data('id')) {
                    if (type === 'walls' || type === 'wall-panel') {
                        this_li.addClass('active');
                        res_data = this_li.attr('data-product');
                        var corder_id = this_li.attr('data-corner-id');
                        var dataParse = JSON.parse(res_data);
                        dataParse['corner_id'] = corder_id;
                        if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
                            dataParse['has_combineds'] = json_data.has_combineds;
                        }

                        popup.find('.select-product').attr('data-product', JSON.stringify(dataParse));
                    } else {
                        this_li.addClass('active');
                        res_data = this_li.attr('data-product');
                        var dataParse = JSON.parse(res_data);
                        if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
                            dataParse['has_combineds'] = json_data.has_combineds;
                        }
                        popup.find('.select-product').attr('data-product', JSON.stringify(dataParse));
                    }
                }
            });
            popup.find('.js_add_calculation').removeClass('btn-disabled').addClass('js_edit_calculation').attr('data-product', data_prod);

            if (json_data.has_combineds !== '' && json_data.has_combineds === 'true') {
                popup.find('.for_combined').slideDown();
                addCombinedProduct(popup, data_prod);
            }

            if (type === 'walls' || type === 'wall-panel') {
                var total_sq = popup.find('.total_sq'),
                    total_avg = popup.find('.total_avg'),
                    outside_corners = popup.find('input[name="num_outside_corners"]'),
                    base_total_sq = popup.find('.base_total_sq'),
                    coping_total_sq = popup.find('.coping_total_sq');

                total_avg.val(json_data.total_avg);
                outside_corners.val(json_data.outside_corners);

                var not_adding_base_unit = true,
                    not_adding_coping = true;

                if (json_data.base_unit) {
                    popup.find('.for_base_unit').slideDown();

                    popup.find('.js_add_base_unit_box').fadeOut();
                    popup.find('.add_base_block').removeClass('js_not_added_base_unit').addClass('js_added_base_unit');
                    popup.find('.select_product_base_unit').attr('data-product', JSON.stringify(json_data.base_unit));
                    popup.find('.select_product_base_unit .с-current-val').html(json_data.base_unit.title);
                    popup.find('.select_product_base_unit .с-select-wrapp').addClass('value');

                    base_total_sq.val(json_data.base_unit.total_sq)

                    popup.find('.for_base_unit .orientation-item').removeClass('active');
                    popup.find('.for_base_unit input[name="orientation"]').prop("checked", false);
                    popup.find('.for_base_unit input[value="' + json_data.base_unit.orientation + '"]').prop("checked", true);
                    popup.find('.for_base_unit input[value="' + json_data.base_unit.orientation + '"]').parents('.orientation-item').addClass('active');
                    not_adding_base_unit = true;
                }

                if (json_data.coping) {
                    popup.find('.for_coping').slideDown();

                    popup.find('.add_base_block').removeClass('js_not_added_coping').addClass('js_added_coping');
                    popup.find('.select_product_coping').attr('data-product', JSON.stringify(json_data.coping));
                    popup.find('.select_product_coping .с-current-val').html(json_data.coping.title);
                    popup.find('.select_product_coping').attr('data-coping-num', json_data.coping.num);
                    popup.find('.select_product_coping .с-select-wrapp').addClass('value');

                    coping_total_sq.val(json_data.coping.total_sq)

                    //--Orientation
                    if (json_data.coping.sailor_or == 'false') {
                        popup.find('.for_coping .sailor_or').removeClass('active').addClass('disabled');
                        popup.find('.for_coping .soldier_or').removeClass('disabled').addClass('active');
                        popup.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", false);
                        popup.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", true);
                    } else if (json_data.coping.soldier_or == 'false') {
                        popup.find('.for_coping .soldier_or').removeClass('active').addClass('disabled');
                        popup.find('.for_coping .sailor_or').removeClass('disabled').addClass('active');
                        popup.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", false);
                        popup.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", true);
                    } else {
                        popup.find('.for_coping .soldier_or').removeClass('active').removeClass('disabled');
                        popup.find('.for_coping .sailor_or').removeClass('disabled').addClass('active');
                        popup.find('.for_coping .border-orientation input[value="soldier"]').prop("checked", false);
                        popup.find('.for_coping .border-orientation input[value="sailor"]').prop("checked", true);
                    }

                    popup.find('.js_add_coping_box').fadeOut();
                    popup.find('.for_coping .orientation-item').removeClass('active');
                    popup.find('.for_coping input[name="coping_orientation"]').prop("checked", false);
                    popup.find('.for_coping input[value="' + json_data.coping.orientation + '"]').prop("checked", true);
                    popup.find('.for_coping input[value="' + json_data.coping.orientation + '"]').parents('.orientation-item').addClass('active');
                    not_adding_coping = true;
                }

                if (not_adding_base_unit || not_adding_coping) {
                    popup.find('.hide_block_base_unit').slideDown();
                }
            }

            if (data_combined !== 'false') {
                popup.find('.for_combined_main').addClass('open-combined-container');
                popup.find('.for_combined_main').slideDown();
                popup.find('.for_combined').hide();

                var persentAmount = 0;
                for (var i = 0; i < json_data.combined.length; i++) {
                    var data = json_data.combined[i],
                        comb_pdf = '#',
                        pdf_class = 'pdf_disabled',
                        dataCombaind = JSON.stringify(json_data.combined[i]);

                    if (data.pdf && data.pdf != '') {
                        comb_pdf = data.pdf;
                        pdf_class = '';
                    }
                    var removeItem = '<li class="remove"><img class="icon" src="/wp-content/themes/unilock/calculator/assets/img/delete-icon.svg" alt="delete-icon"></li>';

                    persentAmount += +data.persent;
                    popup.find('.list-calculate').append(`<div class="list-calculate-item" data-id='${data.id}' data-combined='${dataCombaind}'>
                        <div class="caption-product-with-control">
                            <div class="caption caption-combined">${data.title}</div>
                            <div class="control-btn">
                                <ul>
                                    <li class="copy"><a class="comb_pdf ${pdf_class}" href="${comb_pdf}" target="_blank"><img class="icon" src="/wp-content/themes/unilock/calculator/assets/img/pdf.svg" alt="pdf-icon"></a></li>
                                    ${(i > 0) ? removeItem : ''}
                                </ul>
                            </div>
                        </div>
                        <div class="input-field-wrapp focus">
                            <div class="input-placeholder">Enter % of installation area</div>
                            <input class="input_calc" type="text" inputmode="number" min="0" max="99" step="1" name="area" value="${data.persent}">
                        </div>
                    </div>`);
                }

                popup.find('.amount i').html(persentAmount);
            } else {
                var combinedContainer = popup.find('.list-calculate');
                var combinedContainerItems = popup.find('.list-calculate-item');
                var itemCombined = getItemForCombined(json_data, JSON.stringify(json_data))
                combinedContainer.append(itemCombined);
            }
            _functions.ucalc_openPopup('.popup-content[data-rel="' + type + '"]');
        }
        if (popup.find('.select-product .с-select ul li').length > 0) {
            productEdit(json_data, popup, type);
        } else {
            ajaxAddProduct(type, popup, json_data.num, function () {
                productEdit(json_data, popup, type)
                var productJSON = popup.find('.select-product > .с-select-wrapp > .с-select > ul > li.active').attr('data-product');
                addCombinedProduct(popup, productJSON);
            });
        }
    });

    // -- Loader for popups
    function loaderPopup(status) {
        if (status == 'show') {
            $('#content-block').addClass('popup-loader');
        } else {
            $('#content-block').removeClass('popup-loader');
        }
    }

    // -- get number of product in table / change number product in table after remove
    function num_added_product(type, rename_true, resave, getCurrentNum) {
        var num = 1;

        $("table.added-products > tbody tr").each(function () {
            var _this = $(this),
                tr_name = _this.find('td.category span i'),
                change_name = '',
                coping_wall = _this.attr('data-coping-wall'),
                data_attr = _this.attr('data-add-prod'),
                data_prod = data_attr ? JSON.parse(data_attr) : false;

            if (coping_wall === 'true') {
                // console.log(type, num)
                data_attr = _this.parents('tr.nested-tr').attr('data-add-prod');
                data_prod = data_attr ? JSON.parse(data_attr) : false;

                if (_this.attr('data-num-type') == type && data_prod) {
                    if (rename_true) {
                        if (_this.attr('data-combined') !== 'false') {
                            change_name = num;
                        } else {
                            change_name = num;
                        }
                        tr_name.html(change_name);
                        _this.attr('data-num', num);
                        data_prod.coping.num = num;
                        _this.parents('tr.nested-tr').attr('data-add-prod', JSON.stringify(data_prod));
                    }
                    num++;
                }
            } else {
                // console.log(type, num)
                if (_this.attr('data-num-type') == type && data_prod) {
                    if (rename_true) {
                        if (_this.attr('data-combined') !== 'false') {
                            change_name = num;
                        } else {
                            change_name = num;
                        }
                        tr_name.html(change_name);
                        _this.attr('data-num', num);
                        data_prod.num = num;
                        _this.attr('data-add-prod', JSON.stringify(data_prod));
                    }
                    num++;
                }
            }

        });
        show_empty_table();

        if (resave) {
            transient_resave();
        }

        hideCatCheck();
        if (getCurrentNum) {
            return num - 1;
        }
        return num;
    }

    // -- remove old prod from table
    function remove_edit_prod(type, num, id) {
        $("table.added-products > tbody > tr").each(function () {
            var _this = $(this),
                tr_name_num = _this.find('td.category span i').text(),
                tr_data_num = _this.attr('data-num'),
                tr_type = _this.attr('data-type'),
                tr_id = _this.attr('data-id');

            // console.log(typeof tr_type, typeof tr_data_num, typeof tr_id);
            if (tr_type == type && tr_data_num == num && tr_id == id)
                _this.remove();
        });
        show_empty_table();
    }

    //--show empty when all tr deleted
    function show_empty_table() {
        if ($("table.added-products tr").length == 1) {
            $('.container-block .add_products').addClass('hide_block');
            $('.container-block .empty-project-block').removeClass('hide_block');
        }
    }


    //--hide prod cat in menu if products > 8
    function menu_hide_cat(qty, type) {
        //if (remove_true) qty = qty - 1;
        $('.add-product-inner ul li').each(function () {
            var this_li = $(this),
                num = 8;
            if (this_li.data('rel') == type) {

                if (type == 'pavers') num = 1;
                else if (type == 'border') num = 3;
                else num = 8;

                if (qty >= num) this_li.addClass('not-select');
                else this_li.removeClass('not-select');
            }
        });
        menu_show_mess();
    }

    function menu_show_mess() {
        var mess = false;
        $('.add-product-inner ul li').each(function () {
            var _this = $(this);
            if (_this.hasClass('not-select')) {
                $('.alert-maximum').removeClass('hide_block');
                mess = true;
                return mess;
            }
        });

        if (mess == false) {
            $('.alert-maximum').addClass('hide_block');
        }
    }

    function hideCatCheck() {
        var dataTypes = ['pavers', 'natural-stones', 'border', 'walls', 'wall-panel', 'coping', 'steps', 'pillar', 'base'];
        for (var i = 0; i < dataTypes.length; i++) {
            var getItemLength = $('table.added-products > tbody tr[data-num-type="' + dataTypes[i] + '"]').length;
            //if (getItemLength >= 8)
            //console.log(getItemLength, dataTypes[i]);
            menu_hide_cat(getItemLength, dataTypes[i]);
        }

    }

    function capitalizeFirstLetter(string) {
        if (typeof string === 'string') {
            return string.charAt(0).toUpperCase() + string.slice(1);
        } else if (string != undefined) {
            return string;
        }
        return false;
    }

    $(document).on('click', '.js_remove_all', function (e) {
        $('.alert-maximum').addClass('hide_block');
        $('.add-product-inner ul li').removeClass('not-select');
        $("table.added-products > tbody > tr").each(function () {
            var _this = $(this);
            _this.remove();
        });

        show_empty_table();
        transient_resave();
        _functions.ucalc_closePopup();
    });

    $(document).ready(function () {
        if ($('#form_project_submit input[name="project_name"]').val() != '') $('#form_project_submit .input-field-wrapp').addClass('focus');

        hideCatCheck();
    });

    //-- remove product tr from table
    $(document).on('click', '.js_remove_prod', function (e) {
        var _this = $(this),
            str = '',
            prod_str = '',
            tr = _this.parents('tr'),
            id = tr.attr('data-id'),
            combined = tr.attr('data-combined'),
            num = tr.attr('data-num'),
            name = tr.find('.name').text(),
            type = tr.attr('data-type'),
            capitalize = capitalizeFirstLetter(type);

        if (capitalize) {
            prod_str = capitalize + ' ' + num;
            if (combined) prod_str = capitalize + ' ' + num + ' (Combined) ' + ' - ' + name;
            else prod_str = capitalize + ' ' + num + ' - ' + name;
            //str = 'Would you like to remove <span>' + prod_str + '</span> ?';
            //console.log(prod_str);
            $('.remove-this-prod-popup').find('.text_calc b').text(prod_str);
            $('.js_remove_this').attr('data-id', id).attr('data-num', num).attr('data-type', type);
            _functions.ucalc_openPopup('.popup-content[data-rel="remove-product-popup"]');
        }
    });

    //-- remove product tr from table
    $(document).on('click', '.js_remove_combined_prod', function (e) {
        var _this = $(this),
            str = '',
            prod_str = '',
            nameAdding = '',
            tr = _this.parents('tr.nested-tr'),
            id = tr.attr('data-id'),
            combined = tr.attr('data-combined'),
            num = tr.attr('data-num'),
            name = tr.find('.name').eq(0).text(),
            type = tr.attr('data-type');

        /*name.each(function(index) {
          nameAdding += index > 0 ? '<br>' + $(this).text() : $(this).text();
        });*/

        // console.log(_this);
        // console.log(tr);
        // console.log(id, combined, num, name, type);

        if (combined) {
            prod_str = capitalizeFirstLetter(type) + ' ' + num + ' (Combined) ' + ' - ' + name;
        } else {
            prod_str = capitalizeFirstLetter(type) + ' ' + num + ' - ' + name;
        }
        // str = 'Would you like to remove <span>' + prod_str + ' ?</span>';
        $('.remove-this-prod-popup').find('.text_calc b').text(prod_str);
        $('.js_remove_this').attr('data-id', id).attr('data-num', num).attr('data-type', type);
        _functions.ucalc_openPopup('.popup-content[data-rel="remove-product-popup"]');
    });

    $(document).on('click', '.js_remove_this', function (e) {
        e.preventDefault();
        var btn = $(this),
            type = btn.attr('data-type'),
            num = btn.attr('data-num'),
            id = btn.attr('data-id');

        $("table.added-products > tbody > tr").each(function () {
            var _this = $(this),
                tr_data_num = _this.attr('data-num'),
                tr_type = _this.attr('data-type'),
                tr_id = _this.attr('data-id');

            if (tr_type == type && tr_data_num == num && tr_id == id) {
                _this.remove();
            }
        });

        num_added_product(type, true, true);
        num_added_product('coping', true, true);
        _functions.ucalc_closePopup();
        hideCatCheck();
    });


    function transient_resave() {
        var all_data_prod_arr = [];
        $("table.added-products > tbody > tr").each(function () {
            var _this = $(this),
                data_prod = _this.attr('data-add-prod');
            if (data_prod != '') all_data_prod_arr.push(JSON.parse(data_prod));
        });

        // console.log(all_data_prod_arr);

        if (typeof all_data_prod_arr === 'object') {
            $.post(ajax_security.ajax_url, {
                'action': 'uni_calc_transient_resave',
                'data_prod': all_data_prod_arr,
            })
                .done(function (response) {
                    var res = $.parseJSON(response);
                })
                .fail(function (response) {
                    console.log(response);
                });
        }
    }
    /*END CUSTOM 1*/

    //--get base unit in walls
    $(document).on('click', '.js_add_base_unit', function (e) {
        e.preventDefault();
        var _this = $(this),
            parents = _this.parents('.popup-container');

        parents.find('.add_base_block').removeClass('js_not_added_base_unit');
        parents.find('.for_base_unit').slideDown();
        parents.find('.add_base_block').addClass('js_added_base_unit');

        parents.find('.js_add_base_unit_box').fadeOut();

        if (parents.find('.add_base_block').hasClass('js_added_coping')) {
            parents.find('.add_base_block').slideUp();
        }

        var mainInputVal = parents.find('input[name="total_ln_ft"]').val();
        var select_product_base_unit = parents.find('.for_base_unit.select_product_base_unit .с-select ul li');
        parents.find('input[name="base_total_ln_feet"]').val(mainInputVal);
        parents.find('.select-square.for_base_unit .input-field-wrapp').addClass('value');

        if (select_product_base_unit.length === 1) {
            select_product_base_unit.eq(0).click();
        }

        buttonDisableBaseUnit(true);
    });

    //--get base unit in walls
    $(document).on('click', '.js_add_coping', function (e) {
        e.preventDefault();
        var _this = $(this),
            parents = _this.parents('.popup-container');

        // console.log('get_base_unit');
        parents.find('.add_base_block').removeClass('js_not_added_coping');
        parents.find('.for_coping').slideDown();
        parents.find('.add_base_block').addClass('js_added_coping');

        parents.find('.js_add_coping_box').fadeOut();

        if (parents.find('.add_base_block').hasClass('js_added_base_unit')) {
            parents.find('.add_base_block').slideUp();
        }

        var mainInputVal = parents.find('input[name="total_ln_ft"]').val();
        parents.find('.for_coping  input[name="coping_ln_feet"]').val(mainInputVal);
        parents.find('.for_coping .select-square .input-field-wrapp').addClass('value');

        buttonDisableCoping(true);
    });

    function hideBaseUnitsBox(_this) {
        _this.parents('.popup-container').find('.js_add_base_unit_box').fadeIn();
        _this.parents('.popup-container').find('.add_base_block').addClass('js_not_added_base_unit').removeClass('js_added_base_unit').slideDown();
        _this.parents('.popup-container').find('.for_base_unit').slideUp();
        _this.parents('.popup-container').find('.for_base_unit input.input_calc').val('');
        _this.parents('.popup-container').find('.select_product_base_unit').attr('data-product', '');
        _this.parents('.popup-container').find('.for_base_unit .с-current-val').html('');
        _this.parents('.popup-container').find('.for_base_unit .input-field-wrapp').removeClass('fail').removeClass('value');

        buttonDisableBaseUnit(false);
    }

    function hideCopingBox(_this) {
        _this.parents('.popup-container').find('.js_add_coping_box').fadeIn();
        _this.parents('.popup-container').find('.add_base_block').addClass('js_not_added_coping').removeClass('js_added_coping').slideDown();
        _this.parents('.popup-container').find('.for_coping').slideUp();
        _this.parents('.popup-container').find('.for_coping input.input_calc').val('');
        _this.parents('.popup-container').find('.select_product_coping').attr('data-product', '');
        _this.parents('.popup-container').find('.for_coping .с-current-val').html('');
        _this.parents('.popup-container').find('.for_coping .input-field-wrapp').removeClass('fail').removeClass('value');

        buttonDisableCoping(false);
    }

    $(document).on('click', '.js_remove_base_unit', function (e) {
        e.preventDefault();
        hideBaseUnitsBox($(this));
    });


    $(document).on('click', '.js_remove_coping', function (e) {
        e.preventDefault();
        hideCopingBox($(this));

        var _this = $(this),
            select_wrapp = _this.parents('.js_coping_hide_block').find('.с-select-wrapp');

        if (select_wrapp.hasClass('open-select')) {
            select_wrapp.removeClass('open-select');
        } else if (select_wrapp.hasClass('value')) {
            select_wrapp.removeClass('value');
        }
    });



    //--rename project
    $(document).on('click', '.js_save_project_name', function (e) {
        e.preventDefault();
        var pr_name = $(this).parents('.popup_calc').find('#edit-name').val();
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_rename_project',
            'project_new_name': pr_name,
        })
            .done(function (response) {
                var res = $.parseJSON(response);
                if (res.success) {
                    $('.project-name span').text(res.new_name);
                    _functions.ucalc_closePopup();
                }
            })
            .fail(function (response) {
                console.log(response);
            });
    });

    //edit name project
    /*$(document).on('click', '.popup-content .save-name', function () {
        let getNewName = $(this).closest('.popup-content').find('#edit-name').val()
        $('.edit-title').find('span').text(getNewName)
    });*/

    /*$(document).on('click', '.edit-name', function (e) {
        e.preventDefault();
        var pr_name = $('.project-name span').text();
        $('#edit-name').val(pr_name);
        $('#edit-name').parents('.input-field-wrapp').addClass('focus');
    });*/


    //--send email
    /*$(document).on('click', '.js_send_email', function (e) {
        e.preventDefault();
        var btn = $(this),
            popup = btn.parents('.popup-container'),
            input = popup.find('input[required="true"]'),
            email_val = popup.find('input[name="email"]').val(),
            project_name = $('.project-name span').text(),
            full_bundles = $('#container-block-result').attr('data-full-bundles'),
            base_thick = $('#container-block-result').attr('data-base-thick'),
            data = '',
            validate = true;

        input.each(function (index) {
           if ($(this).val() === ''){
               validate = false;
               $(this).parents('.input-field-wrapp').addClass('fail')
           }
        });

        $.post(ajax_security.ajax_url, {
                'action': 'uni_calc_send_email',
                'email': email_val,
                'project_name': project_name,
                'data': data,
                'full_bundles': full_bundles,
                'base_thick': base_thick,

            })
            .done(function (response) {
                var res = $.parseJSON(response);
                if (res.success) {
                    input.val('');
                    _functions.ucalc_closePopup();
                }

            })
            .fail(function (response) {
                console.log(response);
            });
    });*/

    $(document).on('submit', '#js_send_to_email_project', function (e) {
        e.preventDefault();
        var form = $(this),
            popup = form.parents('.popup-container'),
            input = popup.find('.input_required'),
            input_val = popup.find('input, textarea'),
            project_name = $('.project-name span').text(),
            full_bundles = $('#container-block-result').attr('data-full-bundles'),
            base_thick = $('#container-block-result').attr('data-base-thick'),
            data = form.serialize(),
            validate = true;

        input.each(function (index) {
            if ($(this).val() === '' || $(this).val() === '0') {
                validate = false;
                $(this).parents('.input-field-wrapp').addClass('fail')
            }
        });

        if (validate) {
            $.post(ajax_security.ajax_url, {
                'action': 'uni_calc_send_email',
                'project_name': project_name,
                'data': data,
                'full_bundles': full_bundles,
                'base_thick': base_thick,

            })
                .done(function (response) {
                    var res = $.parseJSON(response);
                    if (res.success) {
                        input_val.val('');
                        input_val.parents('.input-field-wrapp').removeClass('fail').removeClass('value').removeClass('focus');
                        _functions.ucalc_closePopup();
                    }

                })
                .fail(function (response) {
                    console.log(response);
                });
        }
    })

    $(document).on('click', '.popup_email .layer-close', function (e) {
        $('.popup_email input[name="email"]').val('');
    });

    $(document).on('click', '.empty-project-block .empty-img.add-product', function () {
        $('.add-product-block').toggleClass('open-panel');
    });

    //--error message if (Border Sq.Ft > Paver Sq.Ft || (paver_data=='' && border_data))
    $(document).on('click', '.js_calculate_now', function () {
        loaderPopup('show');
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_check_submit_now',
            'data': 'test',
        })
            .done(function (response) {
                var res = $.parseJSON(response);
                if (!res.success) {
                    $('.error-popup .err-text').html(res.message);
                    _functions.ucalc_openPopup('.popup-content[data-rel="error"]');
                    loaderPopup('hide');
                } else {
                    if (res.paverExist) {
                        // _functions.ucalc_openPopup('.popup-content[data-rel="calculate-popup"]');
                        window.location.href = ajax.result_url;
                    } else {
                        window.location.href = ajax.result_url;
                    }
                    loaderPopup('hide');
                }
            })
            .fail(function (response) {
                console.log(response);
                loaderPopup('hide');
            });
    });

    //--increment / decrement for Calculate requirements
    $(document).on('click', '.js_incr_base', function () {
        var _this = $(this),
            inp = _this.parents('.base-thickness').find('input[name="base_thickness"]');
        change_base_thichness(inp, 'incr');
    });
    $(document).on('click', '.js_decr_base', function () {
        var _this = $(this),
            inp = _this.parents('.base-thickness').find('input[name="base_thickness"]');
        change_base_thichness(inp, 'decr');
    });

    function change_base_thichness(inp, action) {
        var max = +inp.attr('max'),
            min = +inp.attr('min'),
            value = +inp.val(),
            num = '';
        // console.log(max, min, value, action);
        if (action == 'incr') num = value + 1;
        else num = value - 1;
        if (num >= min && num <= max) inp.val(num);
        if (num > max) {
            inp.val(max);
            $('.js_calc_result').removeClass('disabled');
            $('.base-thickness').removeClass('err');
        }
        if (num < min) {
            inp.val(min);
            $('.js_calc_result').removeClass('disabled');
            $('.base-thickness').removeClass('err');
        }
    }

    //--full/partial bundles on result page
    $(document).on('click', '.js_full_bundles', function () {
        $('.table-caption').removeClass('active');
        $('.table-item > .inner-colapse').slideUp(0);
        $('.js_full_bundles').addClass('active');
        $('.js_partial_bundles').removeClass('active');
        $(this).parents('.container-block').removeClass('partial_bundles').addClass('full_bundles');
        $(this).parents('.container-block').attr('data-full-bundles', true);
        setTimeout(function () {
            $('.table-caption').addClass('active');
            $('.table-item > .inner-colapse').slideDown();
        }, 500);
    });

    $(document).on('click', '.js_partial_bundles', function () {
        $('.table-caption').removeClass('active');
        $('.table-item > .inner-colapse').slideUp(0);
        $('.js_full_bundles').removeClass('active');
        $('.js_partial_bundles').addClass('active');
        $(this).parents('.container-block').removeClass('full_bundles').addClass('partial_bundles');
        $(this).parents('.container-block').attr('data-full-bundles', false);
        setTimeout(function () {
            $('.table-caption').addClass('active');
            $('.table-item > .inner-colapse').slideDown();
        }, 500);
    });

    //--delete products if change region
    $(document).on('click', '.region_transient_delete', function (e) {
        e.preventDefault();
        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_region_transient_delete',
        })
            .done(function (response) {
                var res = $.parseJSON(response);
            })
            .fail(function (response) {
                console.log(response);
            });
    });

    //--calculation print
    $(document).on('click', '.js_print', function (e) {
        e.preventDefault();

        var full_bundles = $(this).parents('.container-block').attr('data-full-bundles'),
            base_thick = parseInt($(this).parents('.container-block').attr('data-base-thick'));

        // window.open(ajax.print_url + '/?base_thickness=' + base_thick + '&full_bundles=' + full_bundles, '_blank');
        window.open(ajax.print_url + '/?full_bundles=' + full_bundles, '_blank');
    });

    //--calculation print
    $(document).on('click', '.js_email_open', function (e) {
        e.preventDefault();
        var project_name = $('.project-name span').text(),
            email_form = $('#js_send_to_email_project');

        email_form.find('input[name="subject-line"]').val(project_name + ' - ');
        email_form.find('input[name="subject-line"]').parents('.input-field-wrapp').addClass('value');

        _functions.ucalc_openPopup('.popup-content[data-rel="email"]');
    });

    $(document).on('click', '.js_save_to_my_project', function () {
        var _this = $(this),
            project_name = $('.project-name span').text(),
            content_block = $('#container-block-result'),
            full_bundles = content_block.attr('data-full-bundles'),
            base_thick = content_block.attr('data-base-thick');

        $.post(ajax_security.ajax_url, {
            'action': 'uni_calc_save_to_my_project',
            'project_name': project_name,
            'full_bundles': full_bundles,
            'base_thick': base_thick,

        })
            .done(function (response) {
                var res = $.parseJSON(response);
                if (res.success) {
                    $('.js_hidden_save_data').val(JSON.stringify(res));
                    _functions.openPopup('.popup-content[data-rel="save-to-my-projects"]');
                }

            })
            .fail(function (response) {
                console.log(response);
            });
    });


    $('input[name="total_el_rise"]').on('input', function () {
        var total_el_rise = parseInt($(this).val()),
            popup = $(this).parents('.popup_steps');
        steps_calc(total_el_rise, popup);
    });

    function steps_calc(total_el_rise, popup) {

        var height_inch = popup.find('.unit_height').attr('data-height-val'),
            act_units = '',
            units_res = '';

        act_units = total_el_rise / height_inch;
        units_res = Math.round(act_units);
        $('input[name="actual_units"]').val(act_units.toFixed(2));
        $('input[name="total_un_req"]').val(units_res);
    }
});