<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <!--bootstrap -->
    <link href="{{asset('admin')}}/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <style>
        * {
            font-family: "DejaVu Serif", "Times New Roman";
            color: black;
        }

        .title {
            margin-top: 10px;
            padding: 15px 0px;
            text-align: center;
        }

        .header {
            margin-top: 50px;
            margin-bottom: 30px;
        }

        .header, .table, .footer-text, .footer-sign {
            font-size: 13px;
        }

        .header, .footer-text {
            margin-left: 20px;
            margin-right: 10px;
        }

        .header_line, .footer-text_line {
            margin: 5px 0px;
            font-size: 0px;
        }

        .header_line_content, .text_line_content {
            font-size: 16px;
        }

        thead {
            text-align: center;
        }

        .footer-text {
            margin-top: 30px;
        }

        .footer-sign {
            text-align: center;
            margin-top: 50px;
        }

        .table-bordered > tbody > tr > td,
        .table-bordered > tbody > tr > th,
        .table-bordered > thead > tr > td,
        .table-bordered > thead > tr > th {
            border: 1px solid black;
        }

        .container {
            padding: 0px;
            width: 100%;
            max-width: 100% !important;
        }

        .footer-sign > .table {
            text-align: center;
            border: none;
        }

        .borderless td, .borderless th {
            border: none;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<div class="container">
    <h5 class="title"><b>{{ $title }}</b></h5>
    <div class="header">
        @foreach ($header as $header_row)
            <div class="header_line">
                @foreach ($header_row as $key => $header_val)
                    @if (empty($header_val))
                        <span class="header_line_content"
                              style="display: inline-block;width:{{ 100/count($header_row) }}%"></span>
                    @else
                        <span class="header_line_content"
                              style="display: inline-block;width:{{ 100/count($header_row) }}%">{{$header_val['key']}}: {{$header_val['value']}}</span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="table">
        <table class="display table-bordered" style="width:100%;overflow-x: visible!important;">
            <thead>
            @if($tableHeader)
                <tr>
                    @foreach($tableHeader as $item)
                        <th>{{$item}}</th>
                    @endforeach
                </tr>
            @endif
            </thead>
            <tbody>
            @if($tableData)
                @foreach($tableData as $items_data)
                    <tr>
                        @foreach($items_data as $item_data)
                            <td>{{$item_data}}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
    @if ($footerText)
        <div class="footer-text">
            @foreach ($footerText as $footer_text_row)
                <div class="footer-text_line">
                    @foreach ($footer_text_row as $key => $footer_text_val)
                        @if (empty($footer_text_val))
                            <span class="footer-text_line_content"
                                  style="display: inline-block;width:{{ 100/count($footer_text_row) }}%"></span>
                        @else
                            <span class="header_line_content"
                                  style="display: inline-block;width:{{ 100/count($footer_text_row) }}%">{{$footer_text_val['key']}}: {{$footer_text_val['value']}}</span>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
    @if ($footerSign)
        <div class="footer-sign row">
            <table class="table borderless">
                @foreach ($footerSign as $key => $footer_sign_val)
                    <tr class="">
                        @foreach ($footer_sign_val as $item)
                            @if ($key == 0)
                                <td class="footer-sign_key"><b>{{$item}}</b></td>
                            @else
                                <td class="footer-sign_key">{{$item}}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</div>
<!-- start js include path -->
<script src="{{asset('admin')}}/assets/plugins/jquery/jquery.min.js"></script>
<!-- bootstrap -->
<script src="{{asset('admin')}}/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
