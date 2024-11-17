<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 0px;
        }

        body {
            font-family: DejaVu Sans;
            margin: 0px;
            font-size: 7px;
        }

        .barcode-container > div {
            margin: 0 auto;
            overflow: hidden;

        }

        .barcode {
            padding-top: 5px;
            padding-bottom: 0px;
            padding-left: 10px;
            padding-right: 0px;
            float: left;
            width: <?php $quantity=;echo (100 / $quantity) ?>%;
        }

        .drug-name {
            text-align: center;
            font-size: 13px;
            margin-bottom: 3px;
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cost-unit-info {
            text-align: center;
            font-size: 11px;
            padding-top: 4px;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: space-around;
            width: 100%;
            height: 100%;
        }

        @for ($i = 1; $i < $quantity; $i++)
			.barcode-container-{{$i}}    {
            margin-left: -10px;
        }

        @endfor

	    	@if ($quantity == 2)
			.barcode {
            padding-top: 20px;
        }

        .barcode2 {
            padding-left: -10px;
        }

        .barcode-container {
            padding-left: 0;
            margin-left: -6px;
        }

        .drug-name {
            font-size: 9px;
        }

        .cost-unit-info {
            font-size: 7px;
        }


        @endif
    </style>
</head>
<body>
<div class="container">
    @for ($i = 0; $i < $quantity; $i++)
        @if ($i == 0)
            <div class="barcode">
                @else
                    <div class="barcode barcode2">
                        @endif
                        <table>
                            <tr>
                                <td style="width: 80px;">
                                    <div><strong>{{ mb_substr($drugName, 0, 20) }}</strong></div>
                                    <div>Số lô: <strong>{{ $batchNum }} </strong></div>
                                    <div>Ngày SX: <strong>{{ $manufacturingDate }} </strong></div>
                                    <div>Hạn SD: <strong>{{ $expiryDate }} </strong></div>
                                    <div>Giá:
                                        <strong>{{ mb_substr(number_format($cost). '/' .  $unit, 0, 20)}} </strong>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div> &nbsp;</div>
                                    <div class="barcode-container barcode-container-{{$i}}" style="text-align: center;">
                                        {!! DNS2D::getBarcodeHTML($barcode, "QRCODE", 1.9, 1.9); !!}
                                    </div>
                                    <div style="text-align: center;">{{ $barcode }}</div>
                                </td>
                            </tr>

                        </table>
                    </div>
                    @endfor
            </div>
</body>
</html>
