// write cleave in jquery
(function($, window, undefined) {
    'use strict'
    $.fn.cleave = function(opts) {

        var defaults = {
                autoUnmask: false
            },
            options = $.extend(defaults, opts || {});

        return this.each(function() {

            var cleave = new Cleave(this, options),
                $this = $(this);;

            $this.data('cleave-auto-unmask', options['autoUnmask']);;
            $this.data('cleave', cleave)
        });
    }

    var origGetHook, origSetHook;

    if ($.valHooks.input) {

        origGetHook = $.valHooks.input.get;
        origSetHook = $.valHooks.input.set;

    } else {

        $.valHooks.input = {};
    }

    $.valHooks.input.get = function(el) {

        var $el = $(el),
            cleave = $el.data('cleave');

        if (cleave) {
            return $el.data('cleave-auto-unmask') ? cleave.getRawValue() : el.value;

        } else if (origGetHook) {

            return origGetHook(el);

        } else {

            return undefined;
        }
    }

    $.valHooks.input.set = function(el, val) {

        var $el = $(el),
            cleave = $el.data('cleave');

        if (cleave) {

            cleave.setRawValue(val);
            return $el;

        } else if (origSetHook) {

            return origSetHook(el);

        } else {
            return undefined;
        }
    }
})(jQuery, window);

$("input").on("change", calcAmount);

function calcAmount() {
    var $quantities = $("input[name='quantity[]']");
    var $costs = $("input[name='cost[]']");
    var $vats = $("input[name='vat[]']");
    var $final_costs = $(".return-order_final_cost");
    
    var amount = 0
    var vat_amount = 0;
    var pay_amount = 0;

    $quantities.each(function(index) {
        var quantity = $(this).val();
        var cost = $costs.eq(index).val();
        var vat = $vats.eq(index).val();

        var final_cost = quantity * cost * (1 + vat / 100);
        $final_costs.eq(index).text(final_cost.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));

        amount +=  quantity * cost;
        vat_amount += (quantity * cost * vat) / 100;
        pay_amount += final_cost;
    });

    $("input[name='amount']").val(amount.toFixed(2));
    $("input[name='vat_amount']").val(vat_amount.toFixed(2));
    $("input[name='pay_amount']").val(pay_amount.toFixed(2));

    $(".return-order_pay_amount").text(pay_amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
}

function getQuantityDrug() {
    var quantity = $(".return-order_drug_container").length;
    $("#quantity").text(quantity);
}

function handleClickDelete () {
    var $row = $(this).parent();
    $row.remove();
    calcAmount();
    getQuantityDrug();
}

$(".input-format-number").cleave({
    numeral: true,
    autoUnmask: true
});

function onSubmitForm(e) {
    try {
        var inputsCost = this.getElementsByClassName('input-format-number');
        for (var i = 0; i < inputsCost.length; i++) {
            var rawVal = $(inputsCost[i]).val();
            if (!isNaN(rawVal) && rawVal > 0) {
                inputsCost[i].value = $(inputsCost[i]).val();
            } else {
                alert("Vui lòng nhập giá lớn hơn 0")
                return false;
            }
        }

    } catch (err) {
        console.log(err)
    }
}

$(".form_datetime").datetimepicker({
    format: "yyyy-mm-dd hh:ii",
    autoclose: 1,
    todayBtn:  1,
    forceParse: 0
});