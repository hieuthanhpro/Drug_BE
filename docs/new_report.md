# API REPORT

## MỤC LỤC

 - [1. API SỔ KIỂM SOÁT CHẤT LƯỢNG ĐỊNH KỲ VÀ ĐỘT XUẤT](#1-api-sổ-kiểm-soát-chất-lượng-định-kỳ-và-đột-xuất)

BASE URL: 

    
## 1. API SỔ KIỂM SOÁT CHẤT LƯỢNG ĐỊNH KỲ VÀ ĐỘT XUẤT


> URL: 
 
 ```    
/api/new-report/create-control-regular-and-irregular-quality-book
```    
    
> Method: 
```    
POST  
```  
    
> Header: 
```      
Authorization: Bearer fc98e8be71120f42cad70705927e4177c4b49bc5196281bfe820713b4718b2e0 
```    

POST BODY

```json
{
	"charge_person": "Nguyễn Văn A",
	"tracking_staff": "Nguyễn Văn B",
	"data": [
		{
			"date": "2019-07-28",
			"drug_id": 1,
			"unit_id": 1,
			"number": "aaaaa",
			"expire_date": "2020-07-28",
			"quantity": "",
			"sensory_quality": "",
			"conclude": "",
			"reason": ""
		}
	]
}
```

> response

```json
{
    "success": true,
    "message": "Thành công"
}
```