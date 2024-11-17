<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	  	<style>
	    	@page {margin: 0px;}
	    	body {
                font-family: DejaVu Sans;
			    margin: 0px;
                font-size: 9px;
			}

			.barcode-container > div {
				margin: 0 auto;
				overflow: hidden;
			}

	    	.barcode {
				padding-top: 5px;
                padding-bottom: 0px;
                padding-left: 5px;
                padding-right: 0px;
                float: left;
	    		width: <?php echo (100 / $quantity) ?>%;
	    	}
            .barcode {
				padding-top: 5px;
                padding-bottom: 0px;
                padding-left: 0px;
                padding-right: 5px;
                float: left;
	    		width: <?php echo (100 / $quantity) ?>%;
	    	}


			.drug-name {
				text-align: center;
				font-size: 8px;
				margin-bottom: 5px;
                margin-top: 0px;
                padding-top: 0px;
                padding-left: 5px;
                padding-right: 7px;
                word-wrap: break-word;
			}
			.cost-unit-info {
				text-align: center;
				font-size: 10px;
				padding-top: 2px;
			}

			.container {
				display: flex;
				align-items: center;
				justify-content: space-around;
				width: 100%;
				height:100%;
			}
			@for ($i = 1; $i < $quantity; $i++)
			.barcode-container-{{$i}}  {
				margin-left: -10px;
			}
			@endfor

	    	@if ($quantity == 2)
			.barcode {
				padding-top: 25px;
	    	}

	    	.barcode-container {
				padding-left: 0;
				margin-left: -6px;
			}

			.cost-unit-info {
				font-size: 7px;
                padding-top: 0px;
			}
            .batch-info {
				font-size: 7px;
                padding-top: 0px;
			}
            .center-align {
                text-align: center;
            }


	    	@endif
	  	</style>
	</head>
	<body>
		<div class="container">
			@for ($i = 0; $i < $quantity; $i++)
			<div class="barcode">
				<div class="drug-name"><strong>{{ mb_substr($drugName, 0, 50) }}</strong></div>
				<div class="barcode-container barcode-container-{{$i}}">
					{!! DNS1D::getBarcodeHTML($barcode, "C128", 1, 30); !!}
				</div>
				<div class="cost-unit-info center-align"><b>{{ number_format($cost) }} VND/{{ $unit }}</b></div>
                <div class="batch-info center-align">{{ $batch_num  }} - {{ $expiry_date }}</div>

            </div>
			@endfor
		</div>
	</body>
</html>
