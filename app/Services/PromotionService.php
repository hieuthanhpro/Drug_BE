<?php

namespace App\Services;

use App\LibExtension\Utils;
use App\Repositories\PriceRule\PriceRuleRepositoryInterface;
use App\Repositories\Promotion\PromotionRepositoryInterface;
use App\LibExtension\LogEx;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * Class PromotionService
 * @package App\Services
 */
class PromotionService
{
    protected $className = "PromotionService";
    protected $promotion;
    protected $priceRule;

    public function __construct(PromotionRepositoryInterface $promotion, PriceRuleRepositoryInterface $priceRule)
    {
        LogEx::constructName($this->className, '__construct');
        $this->promotion = $promotion;
        $this->priceRule = $priceRule;
    }

    public function createOrUpdatePromotion($requestInput, $user): ?array
    {
        $requestInput["drug_store_id"] = $user["drug_store_id"];
        $requestInput["created_by"] = $user["id"];
        if (Carbon::parse($requestInput['start_date']) > Carbon::now()) {
            $requestInput["status"] = "pending";
        } else if (!isset($requestInput['end_date']) || Carbon::parse($requestInput['end_date']) > Carbon::now()) {
            $requestInput["status"] = "running";
        } else {
            $requestInput["status"] = "ended";
        }

        DB::beginTransaction();
        try {
            if (isset($requestInput["id"])) {
                $promotion = $this->promotion->findOneById($requestInput["id"]);
                if ($promotion->status === 'pause') {
                    $requestInput["status"] = "pause";
                }
                $this->promotion->updateOneById($requestInput["id"], $requestInput);
                $promotion = $this->promotion->findOneById($requestInput["id"]);
            } else {
                $promotion = $this->promotion->create($requestInput);
            }
            if ($promotion) {
                $priceRules = [];
                if (isset($requestInput["id"])) {
                    $this->priceRule->deleteManyBy("promotion_id", $requestInput["id"]);
                }
                if (isset($requestInput["price_rules"])) {
                    foreach ($requestInput["price_rules"] as $item) {
                        $item["promotion_id"] = $promotion->id;
                        array_push($priceRules, $item);
                    }
                    $this->priceRule->insertBatchWithChunk($priceRules, sizeof($priceRules));
                }
                DB::commit();
                return array_merge($promotion->toArray(), ["price_rules" => $priceRules]);
            } else {
                DB::rollback();
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return null;
        }
        return null;
    }

    public function validation($requestInput): \Illuminate\Contracts\Validation\Validator
    {
        $validation = Validator::make($requestInput, [
            'name' => 'required|max:255',
            'start_date' => 'required',
            'discount_type' => 'required'
        ], [
            'name.required' => 'Tên chương trình không được để trống',
            'name.max' => 'Tên chương trình tối đa 255 kí tự',
            'start_date.required' => 'Ngày bắt đầu không được để trống',
            'discount_type.required' => 'Hình thức khuyến mãi không được để trống'
        ]);
        if ($requestInput['discount_type'] === 'discount') {
            $validation->after(function ($validator) use ($requestInput) {
                if (!isset($requestInput['value'])) {
                    $validator->errors()->add('value', 'Chưa nhập giá trị chiết khấu');
                }
                if (!isset($requestInput['discount_type_selection'])) {
                    $validator->errors()->add('discount_type_selection', 'Chưa chọn đối tượng chiết khấu');
                } else if ($requestInput['discount_type_selection'] !== 'order') {
                    switch ($requestInput['discount_type_selection']) {
                        case 'product':
                            if (!isset($requestInput['entitled_product_ids']) || sizeof(json_decode($requestInput['entitled_product_ids'])) === 0) {
                                $validator->errors()->add('entitled_product_ids', 'Chưa chọn sản phẩm chiết khấu');
                            }
                            break;
                        case 'category':
                            if (!isset($requestInput['entitled_category_ids']) || sizeof(json_decode($requestInput['entitled_category_ids'])) === 0) {
                                $validator->errors()->add('entitled_category_ids', 'Chưa chọn danh mục chiết khấu');
                            }
                            break;
                        case 'group':
                            if (!isset($requestInput['entitled_group_ids']) || sizeof(json_decode($requestInput['entitled_group_ids'])) === 0) {
                                $validator->errors()->add('entitled_group_ids', 'Chưa chọn nhóm sản phẩm chiết khấu');
                            }
                            break;
                        default:
                            break;
                    }
                }
            });
        } else {
            $validation->after(function ($validator) use ($requestInput) {
                if (!isset($requestInput['entitled_product_ids']) || sizeof(json_decode($requestInput['entitled_product_ids'])) === 0) {
                    $validator->errors()->add('entitled_product_ids', 'Chưa chọn sản phẩm quà tặng');
                }
            });
        }

        $validation->after(function ($validator) use ($requestInput) {
            if (isset($requestInput['customer_selection']) && $requestInput['customer_selection'] === 'prerequisite' && (!isset($requestInput['prerequisite_customer']) || sizeof(json_decode($requestInput['prerequisite_customer'])) === 0)) {
                $validator->errors()->add('prerequisite_customer', 'Chưa chọn khách hàng áp dụng');
            }
            if (isset($requestInput['drug_store_selection']) && $requestInput['drug_store_selection'] === 'prerequisite' && (!isset($requestInput['prerequisite_drug_store']) || sizeof(json_decode($requestInput['prerequisite_drug_store'])) === 0)) {
                $validator->errors()->add('prerequisite_customer', 'Chưa chọn nhà thuốc áp dụng');
            }
        });

        if (isset($requestInput['price_rules']) && sizeof($requestInput['price_rules']) > 0) {
            $validation = Validator::make($requestInput['price_rules'], [
                '*.type' => 'required',
                '*.prerequisite_selection' => 'required',
                '*.value' => 'required',
                '*.target_selection' => 'required'
            ], [
                '*.type.required' => 'Chưa chọn loại khuyến mãi',
                '*.prerequisite_selection.required' => 'Chưa chọn điều kiện khuyến mãi',
                '*.value.required' => 'Chưa nhập giá trị của điều kiện khuyến mãi',
                '*.target_selection.required' => 'Chưa chọn sản phẩm hoặc danh mục áp dụng',
            ]);

            foreach ($requestInput['price_rules'] as $priceRule) {
                $validation->after(function ($validator) use ($priceRule) {
                    switch ($priceRule['target_selection']) {
                        case 'product':
                            if (!isset($priceRule['entitled_product_ids']) || sizeof(json_decode($priceRule['entitled_product_ids'])) === 0) {
                                $validator->errors()->add('entitled_product_ids', 'Chưa chọn sản phẩm điều kiện');
                            }
                            break;
                        case 'category':
                            if (!isset($priceRule['entitled_category_ids']) || sizeof(json_decode($priceRule['entitled_category_ids'])) === 0) {
                                $validator->errors()->add('entitled_category_ids', 'Chưa chọn danh mục điều kiện');
                            }
                            break;
                        case 'group':
                            if (!isset($priceRule['entitled_group_ids']) || sizeof(json_decode($priceRule['entitled_group_ids'])) === 0) {
                                $validator->errors()->add('entitled_group_ids', 'Chưa chọn nhóm sản phẩm điều kiện');
                            }
                            break;
                        default:
                            break;
                    }
                });
            }
        }

        return $validation;
    }

    public function getPromotion($requestInput, $storeId): array
    {
        $isOrder = $requestInput['is_order'] ?? false;
        $listPromotion = $this->promotion->getPromotionCondition($storeId);
        if (isset($listPromotion) && sizeof($listPromotion) > 0) {
            $listPromotionFilter = [];
            foreach ($listPromotion as $promotion) {
                // Check xem có đạt điều kiện khách hàng phù hợp ko?
                if (!$isOrder && $promotion->customer_selection !== 'all' && (!isset($requestInput['customer_id']) || (isset($promotion->prerequisite_customer) && !in_array($requestInput['customer_id'], json_decode($promotion->prerequisite_customer))))) {
                    LogEx::info(1);
                    continue;
                }
                // Check xem có đạt điều kiện nhà thuốc phù hợp ko?
                if ($isOrder && $promotion->drug_store_selection !== 'all' && (!isset($requestInput['drug_store_id']) || (isset($promotion->prerequisite_drug_store) && !in_array($requestInput['drug_store_id'], json_decode($promotion->prerequisite_drug_store))))) {
                    LogEx::info(2);
                    continue;
                }
                // Check xem có đạt điều kiện giá trên hóa đơn
                if (isset($requestInput['total_price']) && $promotion->prerequisite_subtotal > 0 && $requestInput['total_price'] < $promotion->prerequisite_subtotal) {
                    continue;
                }
                // Check xem có điều kiện thêm ko? Nếu có check tiếp theo hàm validatePromotion
                if (isset($promotion->price_rules)) {
                    $priceRules = json_decode($promotion->price_rules);
                    if ($this->validatePromotion($requestInput, $priceRules) === false) {
                        continue;
                    }
                }
                // Đẩy promotion phù hợp vào array
                array_push($listPromotionFilter, $promotion);
            }
            return $listPromotionFilter;
        }
        return [];
    }

    public function validatePromotionByInvoiceOrOrder($storeId, $requestInput, $isOrder = false): bool
    {
        $promotionIds = $requestInput["promotion_ids"] ?? [];
        $giftItems = $requestInput["gift_items"] ?? [];
        $invoiceOrOrderItems = $requestInput["invoice_detail"] ?? $requestInput["order_detail"] ?? [];
        $discountInvoice = $requestInput['discount_promotion'] ?? 0;
        $listPromotion = $this->promotion->getPromotionCondition($storeId, $promotionIds);

        if (sizeof($listPromotion) !== sizeof($promotionIds)) {
            return false;
        }

        // Build body request giống trên frontend
        $bodyValidPromotion = $this->buildBodyValidPromotion($requestInput, $invoiceOrOrderItems, $isOrder);

        $listPromotionFilter = [];
        //Valid
        foreach ($listPromotion as $promotion) {
            // Check xem có đạt điều kiện khách hàng phù hợp ko?
            if ($promotion->customer_selection !== 'all' && isset($bodyValidPromotion['customer_id']) && !in_array($bodyValidPromotion['customer_id'], json_decode($promotion->prerequisite_customer))) {
                continue;
            }
            // Check xem có đạt điều kiện nhà thuốc phù hợp ko?
            if ($isOrder && $promotion->drug_store_selection !== 'all' && isset($bodyValidPromotion['drug_store_id']) && isset($promotion->prerequisite_drug_store) && !in_array($bodyValidPromotion['drug_store_id'], json_decode($promotion->prerequisite_drug_store))) {
                continue;
            }
            // Check xem có đạt điều kiện giá trên hóa đơn
            if (isset($bodyValidPromotion['total_price']) && $promotion->prerequisite_subtotal > 0 && $bodyValidPromotion['total_price'] < $promotion->prerequisite_subtotal) {
                continue;
            }
            // Check xem có điều kiện thêm ko? Nếu có check tiếp theo hàm validatePromotion
            if (isset($promotion->price_rules)) {
                $priceRules = json_decode($promotion->price_rules);
                if ($this->validatePromotion($bodyValidPromotion, $priceRules) === false) {
                    continue;
                }
            }
            // Đẩy promotion phù hợp vào array
            array_push($listPromotionFilter, $promotion);
        }
        // End valid promotion avaiable

        // Check promotion truyền vào mà promotion check đã đủ điều kiện nếu giống nhau thì tiếp tục khác nhau thì trả ra lỗi
        if ($listPromotionFilter === $listPromotion) {
            $listPromotionGift = array_filter($listPromotionFilter, function ($promotion) {
                return $promotion->discount_type === 'gift';
            }, ARRAY_FILTER_USE_BOTH);
            $listPromotionDiscount = array_filter($listPromotionFilter, function ($promotion) {
                return $promotion->discount_type === 'discount';
            }, ARRAY_FILTER_USE_BOTH);
            $isValid = true;
            // Check danh sách sản phẩm quà tặng có phù hợp hay ko?
            if (sizeof($listPromotionGift) > 0) {
                $isValid = $this->checkGiftItem($listPromotionGift, $bodyValidPromotion, $giftItems);
            }
            // Check danh sách sản phẩm được giảm giá có phù hợp hay ko?
            if (sizeof($listPromotionDiscount) > 0 && $isValid === true) {
                $discountPromotion = $this->getDiscountPromotion($listPromotionDiscount, $bodyValidPromotion);
                $isValid = $this->checkDiscountItem($discountPromotion, $invoiceOrOrderItems, $discountInvoice);
            }
            return $isValid;
        } else {
            return false;
        }
    }

    private function getDiscountPromotion($listPromotion, $bodyValidPromotion): array
    {
        $productsDiscount = [];
        $orderDiscounts = [];
        foreach ($listPromotion as $promotion) {
            $priceRules = $promotion->price_rules ? json_decode($promotion->price_rules) : [];
            $isEachOrder = $promotion->subtotal_selection === 'each';
            $keyRuleEach = array_search('each', array_column($priceRules, 'prerequisite_selection'), true);
            $ruleEach = null;
            if (false !== $keyRuleEach) {
                $ruleEach = $priceRules[$keyRuleEach];
            }
            //Check xem có điều kiện khoảng nào trong rule không?
            $exchangeCondition = 1;
            if ($isEachOrder) {
                $exchangeCondition = floor($bodyValidPromotion['total_price'] / $promotion->prerequisite_subtotal);
            } else if (isset($ruleEach)) {
                $entitledListRuleEach = [];
                switch ($ruleEach->target_selection) {
                    case 'product':
                        $entitledListRuleEach = array_filter($ruleEach->entitled_product_ids, function ($entitledProductId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledProductId) {
                                    return $product['id'] === $entitledProductId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    case 'group':
                        $entitledListRuleEach = array_filter($ruleEach->entitled_group_ids, function ($entitledGroupId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledGroupId) {
                                    return isset($product['drug_group_id']) && $product['drug_group_id'] === $entitledGroupId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    case 'category':
                        $entitledListRuleEach = array_filter($ruleEach->entitled_category_ids, function ($entitledCategoryId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledCategoryId) {
                                    return isset($product['drug_category_id']) && $product['drug_category_id'] === $entitledCategoryId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    default:
                        break;
                }
                if (sizeof($entitledListRuleEach) > 0) {
                    foreach ($entitledListRuleEach as $entitled) {
                        $productsRequest = [];
                        switch ($ruleEach->target_selection) {
                            case 'product':
                                $productsRequest = array_filter($bodyValidPromotion['products'], function ($product) use ($entitled) {
                                    return $product['id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'group':
                                $productsRequest = array_filter($bodyValidPromotion['groups'], function ($group) use ($entitled) {
                                    return $group['id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'category':
                                $productsRequest = array_filter($bodyValidPromotion['categories'], function ($category) use ($entitled) {
                                    return $category['id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            default:
                                break;
                        }
                        if (sizeof($productsRequest) > 0) {
                            foreach ($productsRequest as $product) {
                                $exchangeCondition = $this->caculatorExchange($entitled, $ruleEach, $product, $exchangeCondition);
                            }
                        }
                    }
                }
            }
            if ($exchangeCondition > 0) {
                //Check xem kiểu khuyến mãi
                if ($promotion->discount_type_selection === 'order') {
                    array_push($orderDiscounts, array(
                        'promotionId' => $promotion->id,
                        'discount' => $promotion->value_type === 'amount' ? $promotion->value * $exchangeCondition : ($bodyValidPromotion['total_price'] / 100) * $promotion->value * $exchangeCondition,
                    ));
                    continue;
                }
                switch ($promotion->discount_type_selection) {
                    case 'product':
                        $entitledList = json_decode($promotion->entitled_product_ids);
                        break;
                    case 'group':
                        $entitledList = json_decode($promotion->entitled_group_ids);
                        break;
                    default:
                        $entitledList = json_decode($promotion->entitled_category_ids);
                        break;
                }
                if (sizeof($entitledList) > 0) {
                    foreach ($entitledList as $entitled) {
                        $productsRequest = [];
                        switch ($promotion->discount_type_selection) {
                            case 'product':
                                $productsRequest = array_filter($bodyValidPromotion['products'], function ($product) use ($entitled) {
                                    return $product['id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'group':
                                $productsRequest = array_filter($bodyValidPromotion['products'], function ($product) use ($entitled) {
                                    return isset($product['drug_group_id']) && $product['drug_group_id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'category':
                                $productsRequest = array_filter($bodyValidPromotion['products'], function ($product) use ($entitled) {
                                    return isset($product['drug_category_id']) && $product['drug_category_id'] === $entitled->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            default:
                                break;
                        }
                        if (sizeof($productsRequest) > 0) {
                            foreach ($productsRequest as $product) {
                                array_push($productsDiscount, array(
                                    'unitId' => $product['unit_id'],
                                    'drugId' => $product['id'],
                                    'discount' => $promotion->value_type === 'amount' ? $promotion->value * $exchangeCondition : ($product['total_price'] / $product['total_quantity'] / 100) * $promotion->value * $exchangeCondition,
                                    'orgCost' => $product['total_price'] / $product['total_quantity'],
                                    'promotionId' => $promotion->id
                                ));
                            }
                        }
                    }
                }
            }
        }
        return array('productsDiscount' => $productsDiscount, 'orderDiscounts' => $orderDiscounts);
    }

    private function checkGiftItem($listPromotionGift, $bodyValidPromotion, $giftItems): bool
    {
        $unitMappings = [];
        foreach ($listPromotionGift as $promotionGift) {
            $priceRules = $promotionGift->price_rules ? json_decode($promotionGift->price_rules) : [];
            $isEachOrder = $promotionGift->subtotal_selection === 'each';
            $keyRuleEach = array_search('each', array_column($priceRules, 'prerequisite_selection'), true);
            $ruleEach = null;
            if (false !== $keyRuleEach) {
                $ruleEach = $priceRules[$keyRuleEach];
            }

            //Check xem có điều kiện khoảng nào trong rule không?
            $exchangeCondition = 1;
            if ($isEachOrder) {
                $exchangeCondition = floor($bodyValidPromotion['total_price'] / $promotionGift->prerequisite_subtotal);
            } else if (isset($ruleEach)) {
                // Lấy danh sách item rule trùng với body request
                $entitledList = [];
                switch ($ruleEach->target_selection) {
                    case 'product':
                        $entitledList = array_filter($ruleEach->entitled_product_ids, function ($entitledProductId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledProductId) {
                                    return $product['id'] === $entitledProductId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    case 'group':
                        $entitledList = array_filter($ruleEach->entitled_group_ids, function ($entitledGroupId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledGroupId) {
                                    return isset($product['drug_group_id']) && $product['drug_group_id'] === $entitledGroupId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    case 'category':
                        $entitledList = array_filter($ruleEach->entitled_category_ids, function ($entitledCategoryId) use ($bodyValidPromotion) {
                            return sizeof(array_filter($bodyValidPromotion['products'], function ($product) use ($entitledCategoryId) {
                                    return isset($product['drug_category_id']) && $product['drug_category_id'] === $entitledCategoryId->id;
                                }, ARRAY_FILTER_USE_BOTH)) > 0;
                        }, ARRAY_FILTER_USE_BOTH);
                        break;
                    default:
                        break;
                }

                if (sizeof($entitledList) > 0) {
                    // Lặp xử lý từng item trong price rule
                    foreach ($entitledList as $entitledPriceRule) {
                        $productsRequest = [];
                        switch ($ruleEach->target_selection) {
                            case 'product':
                                $productsRequest = array_filter($bodyValidPromotion['products'], function ($product) use ($entitledPriceRule) {
                                    return $product['id'] === $entitledPriceRule->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'group':
                                $productsRequest = array_filter($bodyValidPromotion['groups'], function ($group) use ($entitledPriceRule) {
                                    return $group['id'] === $entitledPriceRule->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            case 'category':
                                $productsRequest = array_filter($bodyValidPromotion['categories'], function ($category) use ($entitledPriceRule) {
                                    return $category['id'] === $entitledPriceRule->id;
                                }, ARRAY_FILTER_USE_BOTH);
                                break;
                            default:
                                break;
                        }
                        // Lấy ra đơn vị quy đổi theo danh sách
                        // VD Mua 2A tặng 1B thì exchange = 1 => 4A = 2B
                        if (sizeof($productsRequest) > 0) {
                            foreach ($productsRequest as $productGift) {
                                $exchangeCondition = $this->caculatorExchange($entitledPriceRule, $ruleEach, $productGift, $exchangeCondition);
                            }
                        }
                    }
                }
            }
            if ($exchangeCondition > 0) {
                foreach (json_decode($promotionGift->entitled_product_ids) as $drug) {
                    // Tạo danh sách sản phẩm quà tặng
                    $unitMappings = array_merge($unitMappings, array_map(function ($item) use ($drug, $exchangeCondition) {
                        return array(
                            'id' => $item->id,
                            'name' => $item->name,
                            'quantity' => $item->quantity * $exchangeCondition,
                            'drugId' => $drug->id
                        );
                    }, $drug->units));
                }
            }
        }

        // Gom nhóm danh sách quà tặng theo drugId và unitId
        $unitMappingsFinal = [];
        foreach ($unitMappings as $unit) {
            if (sizeof($unitMappingsFinal) === 0) {
                array_push($unitMappingsFinal, $unit);
            } else {
                $key = false;
                foreach ($unitMappingsFinal as $k => $value) {
                    if ($value['id'] === $unit['id'] && $value['drugId'] === $unit['drugId']) {
                        $key = $k;
                        break;
                    }
                }
                if (false !== $key) {
                    $unitMappingsFinal[$key]['quantity'] = $unitMappingsFinal[$key]['quantity'] + $unit['quantity'];
                } else {
                    array_push($unitMappingsFinal, $unit);
                }
            }
        }

        // So sánh số lượng sản phẩm quà tặng giữa request và rule phía db
        $isValid = true;
        foreach ($giftItems as $giftItem) {
            $key = false;
            foreach ($unitMappingsFinal as $k => $value) {
                if ($value['id'] === $giftItem['unit_id'] && $value['drugId'] === $giftItem['drug_id']) {
                    $key = $k;
                    break;
                }
            }
            if (false !== $key && $unitMappingsFinal[$key]['quantity'] < $giftItem['quantity']) {
                $isValid = false;
                break;
            }
        }
        return $isValid;
    }

    private function checkDiscountItem($discountPromotion, $invoiceOrOrderItems, $discountInvoice): bool
    {
        $isValidOrderDiscount = true;
        $isValidItemDiscount = true;
        if (sizeOf($discountPromotion['orderDiscounts']) > 0) {
            $discountOrderSum = array_reduce($discountPromotion['orderDiscounts'], function ($discount, $item) {
                return $discount + $item['discount'];
            });
            if ($discountOrderSum < $discountInvoice) {
                $isValidOrderDiscount = false;
            }
        } else if ($discountInvoice > 0) {
            $isValidOrderDiscount = false;
        }
        foreach ($invoiceOrOrderItems as $item) {
            $discountProductSum = array_reduce(array_filter($discountPromotion['productsDiscount'], function ($value) use ($item) {
                return $item['drug_id'] === $value['drugId'] && $item['unit_id'] === $value['unitId'];
            }, ARRAY_FILTER_USE_BOTH), function ($discount, $value) {
                return $discount + $value["discount"];
            });
            if ($discountProductSum < $item['discount_promotion']) {
                $isValidItemDiscount = false;
                break;
            }
        }

        if ($isValidOrderDiscount && $isValidItemDiscount) {
            return true;
        }
        return false;
    }

    private function buildBodyValidPromotion($requestInput, $invoiceOrOrderItems, $isOrder): array
    {
        // Build body request valid
        $drugGroupIds = [];
        $drugCategoryIds = [];
        foreach ($invoiceOrOrderItems as $item) {
            if (isset($item["drug_group_id"])) {
                $key = array_search($item["drug_group_id"], array_column($drugGroupIds, 'id'), true);
                if (sizeof($drugGroupIds) > 0 && false !== $key) {
                    if (!in_array($item["drug_id"], $drugGroupIds[$key]["drug_ids"])) {
                        $drugGroupIds[$key]["drug_ids"] = array_merge($drugGroupIds[$key]["drug_ids"], [$item["drug_id"]]);
                    }
                } else {
                    array_push($drugGroupIds, ['id' => $item["drug_group_id"], 'drug_ids' => [$item["drug_id"]]]);
                }
            }

            if (isset($item["drug_category_id"])) {
                $key = array_search($item["drug_category_id"], array_column($drugCategoryIds, 'id'), true);
                if (sizeof($drugCategoryIds) > 0 && false !== $key) {
                    if (!in_array($item["drug_id"], $drugCategoryIds[$key]["drug_ids"])) {
                        $drugCategoryIds[$key]["drug_ids"] = array_merge($drugCategoryIds[$key]["drug_ids"], [$item["drug_id"]]);
                    }
                } else {
                    array_push($drugCategoryIds, ['id' => $item["drug_category_id"], 'drug_ids' => [$item["drug_id"]]]);
                }
            }
        }

        return array(
            'customer_id' => $requestInput['customer_id'] ?? null,
            'drug_store_id' => $requestInput['drug_store_id'] ?? null,
            'total_price' => array_reduce($invoiceOrOrderItems, function ($total_price, $item) use ($isOrder) {
                return $total_price + ($isOrder ? ($item['out_price'] * $item['out_quantity']) : ($item['quantity'] * $item['org_cost']));
            }),
            'groups' => array_map(function ($group) use ($invoiceOrOrderItems, $isOrder) {
                return array(
                    'id' => $group['id'],
                    'total_quantity' => array_reduce(array_filter($invoiceOrOrderItems, function ($value) use ($group) {
                        return !empty($value) && in_array($value["drug_id"], $group["drug_ids"]);
                    }, ARRAY_FILTER_USE_BOTH), function ($carry, $value) use ($isOrder) {
                        return $carry + ($isOrder ? $value["out_quantity"] : $value["quantity"]);
                    }),
                    'total_price' => array_reduce(array_filter($invoiceOrOrderItems, function ($value) use ($group) {
                        return !empty($value) && in_array($value["drug_id"], $group["drug_ids"]);
                    }, ARRAY_FILTER_USE_BOTH), function ($carry, $value) use ($isOrder) {
                        return $carry + ($isOrder ? ($value["out_quantity"] * $value["out_price"]) : ($value["quantity"] * $value["org_cost"]));
                    })
                );
            }, $drugGroupIds),
            'categories' => array_map(function ($category) use ($invoiceOrOrderItems, $isOrder) {
                return array(
                    'id' => $category['id'],
                    'total_quantity' => array_reduce(array_filter($invoiceOrOrderItems, function ($value) use ($category) {
                        return !empty($value) && in_array($value["drug_id"], $category["drug_ids"]);
                    }, ARRAY_FILTER_USE_BOTH), function ($carry, $value) use ($isOrder) {
                        return $carry + ($isOrder ? $value["out_quantity"] : $value["quantity"]);
                    }),
                    'total_price' => array_reduce(array_filter($invoiceOrOrderItems, function ($value) use ($category) {
                        return !empty($value) && in_array($value["drug_id"], $category["drug_ids"]);
                    }, ARRAY_FILTER_USE_BOTH), function ($carry, $value) use ($isOrder) {
                        return $carry + ($isOrder ? ($value["out_quantity"] * $value["out_price"]) : ($value["quantity"] * $value["org_cost"]));
                    })
                );
            }, $drugCategoryIds),
            'products' => array_map(function ($item) use ($isOrder) {
                return array(
                    'id' => $item['drug_id'],
                    'unit_id' => $isOrder ? $item['out_unit_id'] : $item['unit_id'],
                    'total_quantity' => $isOrder ? $item['out_quantity'] : $item['quantity'],
                    'total_price' => $isOrder ? $item['out_quantity'] * $item['out_price'] : $item['quantity'] * $item['org_cost'],
                    'drug_group_id' => $item['drug_group_id'] ?? null,
                    'drug_category_id' => $item['drug_category_id'] ?? null
                );
            }, $invoiceOrOrderItems)
        );
    }

    private function caculatorExchange($entitledPriceRule, $ruleEach, $productGift, $exchangeCondition)
    {
        // Tính toán trả về exchange của sản phẩm quà tặng
        $acceptCaculator =
            $ruleEach->target_selection !== 'product' || (in_array($productGift['unit_id'], array_column($entitledPriceRule->units, 'id')));
        $valueCompare = $ruleEach->type === 'item_quantity' ? $productGift['total_quantity'] : $productGift['total_price'];
        if ($acceptCaculator && $valueCompare >= $ruleEach->value) {
            if (floor($valueCompare / $ruleEach->value) > $exchangeCondition || floor($valueCompare / $ruleEach->value) === $exchangeCondition) {
                return floor($valueCompare / $ruleEach->value);
            }
        }
        return 0;
    }

    private function validatePromotion($requestInput, $priceRules): bool
    {
        $isEligible = false;
        foreach ($priceRules as $priceRule) {
            $isCategory = false;
            $isGroup = false;
            $isProduct = false;
            switch ($priceRule->target_selection) {
                case "category":
                    if (isset($requestInput["categories"]) && isset($priceRule->entitled_category_ids)) {
                        $sameCategory = array_intersect($requestInput["categories"], Utils::getIds(json_decode(json_encode($priceRule->entitled_category_ids), true)));
                        if (isset($sameCategory) && sizeof($sameCategory) > 0) {
                            foreach ($requestInput["categories"] as $category) {
                                if (isset($category["id"]) && in_array($category["id"], $sameCategory)) {
                                    switch ($priceRule->type) {
                                        case "item_quantity":
                                            if (isset($category["total_quantity"]) && $priceRule->value <= $category["total_quantity"]) {
                                                $isCategory = true;
                                            }
                                            break;
                                        case "item_price":
                                            if (isset($category["total_price"]) && $priceRule->value <= $category["total_price"]) {
                                                $isCategory = true;
                                            }
                                            break;
                                    }
                                }
                                if ($isCategory === true) {
                                    break;
                                }
                            }
                        }
                    }
                    break;
                case "group":
                    if (isset($requestInput["groups"]) && isset($priceRule->entitled_group_ids)) {
                        $sameGroup = array_intersect(Utils::getIds($requestInput["groups"]), Utils::getIds(json_decode(json_encode($priceRule->entitled_group_ids), true)));
                        if (isset($sameGroup) && sizeof($sameGroup) > 0) {
                            foreach ($requestInput["groups"] as $group) {
                                if (isset($group["id"]) && in_array($group["id"], $sameGroup)) {
                                    switch ($priceRule->type) {
                                        case "item_quantity":
                                            if (isset($group["total_quantity"]) && $priceRule->value <= $group["total_quantity"]) {
                                                $isGroup = true;
                                            }
                                            break;
                                        case "item_price":
                                            if (isset($group["total_price"]) && $priceRule->value <= $group["total_price"]) {
                                                $isGroup = true;
                                            }
                                            break;
                                    }
                                }
                                if ($isGroup === true) {
                                    break;
                                }
                            }
                        }
                    }
                    break;
                default:
                    if (isset($requestInput["products"]) && isset($priceRule->entitled_product_ids)) {
                        foreach ($requestInput["products"] as $product) {
                            foreach (json_decode(json_encode($priceRule->entitled_product_ids)) as $priceRuleProduct) {
                                $unitIds = Utils::getIds(json_decode(json_encode($priceRuleProduct->units), true));
                                if (isset($product["id"]) && $priceRuleProduct->id === $product["id"] && in_array($product["unit_id"], $unitIds)) {
                                    switch ($priceRule->type) {
                                        case "item_quantity":
                                            if (isset($product["total_quantity"]) && $priceRule->value <= $product["total_quantity"]) {
                                                $isProduct = true;
                                            }
                                            break;
                                        case "item_price":
                                            if (isset($product["total_price"]) && $priceRule->value <= $product["total_price"]) {
                                                $isProduct = true;
                                            }
                                            break;
                                    }
                                }
                                if ($isProduct === true) {
                                    break;
                                }
                            }
                            if ($isProduct === true) {
                                break;
                            }
                        }
                    }
                    break;
            }
            if ($isCategory || $isGroup || $isProduct) {
                $isEligible = true;
            } else {
                $isEligible = false;
                break;
            }
        }
        return $isEligible;
    }
}
